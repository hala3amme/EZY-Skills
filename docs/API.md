# EZY Skills API Guide (Postman Collection)

This guide explains how to use the endpoints inside the Postman collection at `docs/postman/EZY-Skills.postman_collection.json`.

For typed clients / codegen, see `openapi.yaml` at the repo root.

## 1) Quickstart (recommended Postman flow)

1. Start the API:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

2. Prepare the database:

```bash
php artisan migrate --seed
```

3. Import the Postman collection:

- File: `docs/postman/EZY-Skills.postman_collection.json`

4. Generate demo tokens + a demo course id:

```bash
php artisan ezy:dev-tokens --reset
```

5. In Postman → Collection Variables, set:

- `baseUrl` = `http://127.0.0.1:8000`

Token options:

- Option A (recommended): run these requests, and they will store tokens automatically:
  - Auth → **Login** (stores into `{{token}}`)
  - Auth → **Login (Teacher)** (stores into `{{teacherToken}}`)
- Option B: set `token` and `teacherToken` from `php artisan ezy:dev-tokens --reset`

Course id options:

- Run Courses → **List courses** (it auto-stores the first result into `{{courseId}}`)
- Or set `courseId` from `php artisan ezy:dev-tokens --reset`

After that, you can run requests in this order:

- Courses → **List courses**
- Courses → **Course details**
- Enrollments → **Request enrollment (student)**
- Enrollments → **Teacher enrollments**
- Enrollments → **Approve enrollment (teacher)**
- Courses → **Course content (locked/unlocked)** (now unlocked for the student)

## 2) Auth (how tokens are used)

Protected endpoints require the header:

- `Authorization: Bearer <token>`

In the Postman collection:

- Student endpoints use `Bearer {{token}}`
- Teacher endpoints use `Bearer {{teacherToken}}`

## 3) IDs used by the collection variables

Some requests require ids that you obtain from earlier responses (and the collection now auto-saves them for you):

- `courseId`
  - Auto-set by Courses → **List courses**
  - Or comes from the seed data / `php artisan ezy:dev-tokens --reset`
- `enrollmentId`
  - Auto-set by Enrollments → **Request enrollment (student)** response (`enrollment.id`)
  - Also auto-set by Enrollments → **Teacher enrollments** (first item)
- `notificationId`
  - Auto-set by Notifications → **My notifications** (first item)

Additional variables used by the broadcasting request:

- `userId`
  - Auto-set by Auth → **Register**, **Login**, **Login (Teacher)**, or **Me**
- `socketId`
  - Dummy example socket id used by Broadcasting → **Authorize private channel** (Echo will send a real value at runtime)

## 4) Endpoints and parameters

Base prefix: `/api`

### Auth

#### POST `/api/auth/register`
Creates a new **student** user (role is always `student`).

Body (JSON):
- `name` (string, required)
- `email` (string, required, unique)
- `phone_number` (string, optional)
- `password` (string, required, min 8)
- `password_confirmation` (string, required)

Success: `201`
- Returns `{ token, user }`

Common errors:
- `422` validation errors

#### POST `/api/auth/login`
Logs in an existing user and returns a new Sanctum token.

Body (JSON):
- `email` (string, required)
- `password` (string, required)
- `device_name` (string, optional)

Success: `200`
- Returns `{ token, user }`

Common errors:
- `422` with `{ "message": "Invalid credentials." }`

#### GET `/api/auth/me` (auth)
Returns the authenticated user.

Headers:
- `Authorization: Bearer {{token}}`

Success: `200`
- Returns `{ user }`

#### POST `/api/auth/logout` (auth)
Revokes the **current** access token.

Headers:
- `Authorization: Bearer {{token}}`

Success: `200`
- Returns `{ "message": "Logged out." }`

---

### Courses

#### GET `/api/courses`
Lists courses (paginated).

Query parameters:
- `search` (string, optional) – matches `title` or `description`
- `tag` (string, optional) – matches tag `slug` or tag `name`
- `page` (int, optional) – standard Laravel pagination

Success: `200`
- Returns a paginated object with `data` containing course summaries.

#### GET `/api/courses/{courseId}`
Public course details.

Path parameters:
- `courseId` (int, required)

Success: `200`
- Returns full course details.
- Video links are **always locked** on this endpoint: each video has `is_locked: true` and `video_url: null`.

#### GET `/api/courses/{courseId}/content` (auth)
Returns course details with video links **unlocked** only if:
- the requester is the course teacher, OR
- the requester is a student with an **approved** enrollment

Headers:
- `Authorization: Bearer {{token}}`

Success: `200`
- Same shape as course details, but videos include `video_url` and `is_locked: false` when authorized.

---

### Enrollments

#### POST `/api/courses/{courseId}/enroll` (auth, student)
Creates an enrollment request (status `pending`) and notifies the teacher.

Headers:
- `Authorization: Bearer {{token}}`

Body:
- none

Success:
- `201` when created: `{ message, enrollment: { id, status } }`
- `200` when already exists: `{ message, enrollment: { id, status } }`

Common errors:
- `403` if the authenticated user is not a student

#### GET `/api/me/enrollments` (auth)
Returns the authenticated student’s enrollments.

Headers:
- `Authorization: Bearer {{token}}`

Success: `200`
- Returns `{ enrollments: [...] }`

#### GET `/api/teacher/enrollments` (auth, teacher)
Returns enrollments for courses owned by the authenticated teacher.

Headers:
- `Authorization: Bearer {{teacherToken}}`

Query parameters:
- `status` (optional): one of `pending`, `approved`, `declined`

Success: `200`
- Returns `{ enrollments: [...] }`

Common errors:
- `403` if the authenticated user is not a teacher

#### POST `/api/teacher/enrollments/{enrollmentId}/approve` (auth, teacher)
Approves an enrollment request.

Path parameters:
- `enrollmentId` (int, required)

Success: `200`
- Returns `{ message, enrollment: { id, status, reviewed_at } }`

#### POST `/api/teacher/enrollments/{enrollmentId}/decline` (auth, teacher)
Declines an enrollment request.

Path parameters:
- `enrollmentId` (int, required)

Success: `200`
- Returns `{ message, enrollment: { id, status, reviewed_at } }`

---

### Dashboards

#### GET `/api/me/courses` (auth, student)
Returns approved courses for the student.

Headers:
- `Authorization: Bearer {{token}}`

Success: `200`
- Returns `{ courses: [...] }`

#### GET `/api/teacher/dashboard` (auth, teacher)
Returns teacher-owned courses and pending enrollment request counts.

Headers:
- `Authorization: Bearer {{teacherToken}}`

Success: `200`
- Returns `{ courses: [...] }` where each course includes `pending_enrollment_requests_count`.

---

### Notifications

#### GET `/api/me/notifications` (auth)
Returns the authenticated user’s latest notifications (up to 50).

Headers:
- `Authorization: Bearer {{teacherToken}}` (works with student token too, but notifications are most useful for teachers in the current MVP)

Success: `200`
- Returns `{ notifications: [...] }`

##### Optional: real-time notifications over WebSockets

In addition to fetching notifications via REST, notifications can be delivered in real-time via Laravel broadcasting.

- Private channel: `private-App.Models.User.{id}`
- Auth endpoint used by Echo for private channels: `POST /broadcasting/auth`
  - Must include `Authorization: Bearer <token>` (Sanctum)

Note: `/broadcasting/auth` is now documented in `openapi.yaml` to support typed clients.

When listening with Echo, use the notification helper:

```js
echo.private(`App.Models.User.${user.id}`).notification((payload) => {
  // payload contains the notification data + id/type
});
```

For `EnrollmentRequested`, the broadcasted payload type is `"enrollment_requested"`.

#### POST `/api/me/notifications/{notificationId}/read` (auth)
Marks a notification as read.

Path parameters:
- `notificationId` (string, required) – database notification id

Headers:
- `Authorization: Bearer {{teacherToken}}`

Success: `200`
- Returns `{ "message": "Notification marked as read." }`

Common errors:
- `404` if the notification does not belong to the authenticated user
