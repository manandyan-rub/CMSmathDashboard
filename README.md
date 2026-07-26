# CMS Math Dashboard

Operational CMS for managing MathDashboard content, users, learning activity, and platform
configuration.

This repo is a Laravel 12 + Vue 3 application for:

- live dashboard analytics and exports
- students, lecturers, administrators, progress, XP, and permission overrides
- paths, levels, questions, formulas, calculator metadata, and practice templates
- publishing workflows, version restoration, and localized content
- Exercise Studio moderation and generation-log inspection
- AI providers, models, prompt versions, live tests, usage, failures, and fallback models
- gamification, games, feature flags, platform settings, and audit history

## Stack

- PHP 8.2+
- Laravel 12
- Vue 3
- Inertia.js
- Vite
- Tailwind CSS

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+
- npm
- SQLite or MySQL

## Local setup

1. Install PHP dependencies:

```bash
composer install
```

2. Install frontend dependencies:

```bash
npm install
```

3. Create environment file:

```bash
copy .env.example .env
```

4. Generate app key:

```bash
php artisan key:generate
```

5. Configure the database in `.env`.

6. Run migrations and seeders:

```bash
php artisan migrate --seed
```

7. Start the app:

```bash
composer run dev
```

8. Build frontend assets for production:

```bash
npm run build
```

## CMS API

All CMS routes live under `/cms-api` and are protected by:

- `auth`
- `verified`
- `cms.access`
- route, request, policy, or controller-level granular permissions

The sidebar uses the same permission keys and hides unauthorized modules and write actions.

## MathDashboard integration

The CMS uses the shared application database for core users and learning content. It does not
load PHP classes or files from a sibling MathDashboard checkout.

`GET /api/internal/v1/platform-config` exposes only settings and feature flags explicitly
marked public. Administrator data and secret values are never returned by that endpoint.

Calculator records contain presentation metadata, supported fields, units, examples, and
relationships. Mathematical solver code remains owned by MathDashboard.

## AI configuration

AI provider rows store the name of an environment variable in `secret_env_key`, never the
credential itself. Define that named variable in the deployment environment after configuring
the provider in the CMS. Prompt tests and retries are rate-limited and written to generation
logs.

## Notes

- Generation Log detail currently exposes raw payload fields to technical admins.
- If the admin role ever expands to non-technical staff, `request_json` and `response_raw` must be redacted for student-identifying data before broader rollout.
- Level prompt preview is currently a mock CMS-only flow and does not call the real MathDashboard AI generation service yet.
- Keep shared AI generation logic out of cross-repository filesystem coupling.

## Follow-up TODOs

- Replace mock level prompt preview with a real shared integration path.
- Revisit Generation Log payload redaction if admin access broadens.

## Scheduler

Run Laravel's scheduler every minute in production so scheduled content receives its
`published_at` timestamp at the requested time:

```bash
php artisan schedule:run
```
