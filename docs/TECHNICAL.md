# EZY Skills – Backend (Laravel)

## Stack

- PHP: 8.4+
- Laravel: 12
- DB: MySQL
- Auth: Sanctum personal access tokens

## Local setup (macOS)

### 1) Install dependencies

```bash
composer install
```

### 2) Environment

Create `.env` (or copy from `.env.example`) and set MySQL values:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ezy_skills
DB_USERNAME=ezy_skills
DB_PASSWORD=...
```

### 3) Migrate + seed

```bash
php artisan migrate --seed
```

### 3.1) Issue Postman tokens (optional)

This creates fresh Sanctum tokens for the demo teacher/student and prints them so you can paste into the Postman collection variables.

```bash
php artisan ezy:dev-tokens --reset
```

### 4) Run tests

```bash
./vendor/bin/pest
```

## Core domain

### Users

- Students: can register/login, browse courses, request enrollments, view approved courses.
- Teachers: assigned to courses, can approve/decline enrollments, and receive notifications.
- Admin: reserved for later phases.

### Courses

Course includes:
- metadata (title, description, image_url)
- objectives
- content videos
- projects
- tools
- tags

### Enrollment workflow

- Student requests enrollment for a course.
- A notification is created for the course’s teacher.
- Teacher approves/declines.
- Course video links are locked until enrollment is approved.

## API overview

For an endpoint-by-endpoint guide (parameters, required ids, and the recommended Postman flow), see: `docs/API.md`.

Base URL: `/api`

### Auth
- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout` (auth)
- `GET /api/auth/me` (auth)

### Courses
- `GET /api/courses?search=&tag=`
- `GET /api/courses/{course}`
- `GET /api/courses/{course}/content` (auth) – unlocks video links only for:
  - the course teacher
  - approved enrolled students

### Enrollments
- `POST /api/courses/{course}/enroll` (auth, student)
- `GET /api/me/enrollments` (auth)
- `GET /api/teacher/enrollments?status=pending|approved|declined` (auth, teacher)
- `POST /api/teacher/enrollments/{enrollment}/approve` (auth, teacher)
- `POST /api/teacher/enrollments/{enrollment}/decline` (auth, teacher)

### Dashboards
- `GET /api/me/courses` (auth, student)
- `GET /api/teacher/dashboard` (auth, teacher)

### Notifications
- `GET /api/me/notifications` (auth)
- `POST /api/me/notifications/{notification}/read` (auth)

## Real-time notifications (WebSockets)

This project supports real-time delivery of notifications via **Laravel broadcasting**.

### How it works

- Notifications are still stored in the database (so you can fetch missed items with REST).
- The same notification is also broadcast over a **private channel**.
- Channel authorization is protected by `auth:sanctum`.

### Channel name

Laravel’s default per-user private channel naming is used:

- `private-App.Models.User.{id}`

Authorization logic is in `routes/channels.php` and allows a user to subscribe only to their own `{id}`.

### Broadcast auth route

When the SPA subscribes to a private channel, Echo will call:

- `POST /broadcasting/auth`

This route is registered by the `BroadcastServiceProvider` and is protected by `auth:sanctum`, so the SPA must send:

- `Authorization: Bearer <token>`

### Local WebSocket server (recommended: Reverb)

Reverb is the easiest local option for Laravel 12.

1) Install Reverb:

```bash
composer require laravel/reverb
php artisan reverb:install
```

2) Configure `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

3) Start the WebSocket server:

```bash
php artisan reverb:start
```

### React SPA listener (Echo)

In a React SPA, use Laravel Echo with the Pusher protocol (works with Reverb):

```bash
npm i laravel-echo pusher-js
```

Then subscribe to the private channel and handle notifications:

```js
echo.private(`App.Models.User.${user.id}`).notification((payload) => {
  // payload contains the notification data + id/type
});
```

#### Suggested SPA environment variables (Vite)

Example `.env` values for the React SPA:

```env
VITE_API_BASE_URL=http://127.0.0.1:8000

VITE_REVERB_APP_KEY=YOUR_REVERB_APP_KEY
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

The SPA should send `Authorization: Bearer <token>` when Echo calls `POST /broadcasting/auth`.

Notes:
- For API parity, you can still call `GET /api/me/notifications` on page load, then merge in live updates from WebSockets.
- Broadcast notifications include an `id` and a `type` field. In this project, `EnrollmentRequested` broadcasts with `type: "enrollment_requested"`.

## Notes for later phases

- Admin portal and teacher course management can be layered on top of the existing models and roles.
- Notifications are already broadcast-capable; later phases can add a frontend/admin UI that listens over WebSockets.
