# EZY Skills

EZY Skills is an upskilling / e-learning backend API built with **Laravel**. It supports a simple marketplace-style workflow where **teachers** publish courses and **students** enroll, with an approval step and teacher notifications.

This repository is structured as a mono-repo. Today it contains the backend API; a frontend/admin portal can be added later.

## What’s included (current MVP)

- **Auth**: Register / login / logout with **Laravel Sanctum** personal access tokens.
- **Users & roles**: `student`, `teacher`, `admin` (admin reserved for later phases).
- **Courses**: Course metadata + objectives + videos + projects + tools + tags.
- **Enrollment workflow**:
	- Student requests enrollment
	- Teacher receives a **database notification**
	- Teacher can also receive the notification in **real-time over WebSockets** (Laravel broadcasting)
	- Teacher approves/declines
	- Course content links are **locked** until enrollment is approved (or you’re the teacher)
- **Dashboards**: basic endpoints for student and teacher views.
- **Tests**: Pest feature tests cover auth and the enrollment/content-unlock flow.
- **Postman collection**: importable collection for quick manual testing.

## Repo structure

- Laravel 12 REST API (this repo root)
	- `docs/TECHNICAL.md` — technical notes + setup
	- `docs/postman/EZY-Skills.postman_collection.json` — Postman collection

## Tech stack

- PHP 8.4+
- Laravel 12
- MySQL
- Laravel Sanctum (API tokens)
- Pest (tests)

## Quickstart (local)

### 1) Install backend dependencies

```bash
composer install
```

### 2) Configure environment

Copy `.env.example` to `.env` and set your MySQL values:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ezy_skills
DB_USERNAME=ezy_skills
DB_PASSWORD=...
```

Then generate the app key:

```bash
php artisan key:generate
```

### 3) Migrate + seed

```bash
php artisan migrate --seed
```

### 4) (Optional) Issue demo tokens for Postman

This prints a `teacherToken`, a student `token`, and a `courseId` so you can immediately try the full flow.

```bash
php artisan ezy:dev-tokens --reset
```

### 5) Run the API

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Base API URL: `http://127.0.0.1:8000/api`

### 5.1) (Optional) Real-time notifications (WebSockets)

This project supports broadcasting notifications to a React SPA using Laravel broadcasting.

- Default is disabled (`BROADCAST_CONNECTION=log`).
- To enable WebSockets locally, use **Laravel Reverb**:

```bash
composer require laravel/reverb
php artisan reverb:install
```

Then set `BROADCAST_CONNECTION=reverb` in `.env` and start the server:

```bash
php artisan reverb:start
```

The React SPA will use `POST /broadcasting/auth` (Bearer token) to authorize private channel subscriptions.

See `docs/TECHNICAL.md` for the channel name, auth route, and SPA listener notes.

### 6) Run tests

```bash
./vendor/bin/pest
```

## Postman

- Import: `docs/postman/EZY-Skills.postman_collection.json`
- Collection variables you’ll typically set:
	- `baseUrl` (example: `http://127.0.0.1:8000`)
	- `token` (student token)
	- `teacherToken` (teacher token)
	- `courseId`

## Documentation

- Technical documentation: `docs/TECHNICAL.md`
- API usage (Postman collection guide): `docs/API.md`

## Roadmap ideas (later phases)

- Teacher course management (CRUD)
- Admin portal
- Real-time notifications UI in a frontend/admin portal
- Social login
- Chatbot/course recommendation flows