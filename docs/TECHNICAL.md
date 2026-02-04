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

## Notes for later phases

- Admin portal and teacher course management can be layered on top of the existing models and roles.
- “Real-time notifications” can be expanded from database notifications to broadcasting via Laravel broadcasting (Reverb/Pusher/etc.) with minimal API changes.
