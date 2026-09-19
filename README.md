# IveSeenThis

IveSeenThis is a personal engineering knowledge base for recording problems, failed attempts, working fixes, and lessons worth finding again.

## What It Stores

- **Issues**: problems, errors, environments, root causes, attempts, and tags.
- **Solutions**: reusable fixes linked to the issue where they were discovered.
- **Projects**: the products, applications, games, and services connected to your issues.
- **Don't do this again**: a short reminder for mistakes that should not be repeated.

The app includes searchable issue and solution views, project organization, authenticated user data, and a dashboard with recent activity.

## Stack

- Laravel 13
- Livewire 4
- Flux UI
- MySQL 8.4 for local runtime
- PHPUnit, Pint, and Larastan
- Vite and Tailwind CSS

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- MySQL 8+

## Installation

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
npm run build
```

Configure MySQL in `.env`:

```dotenv
APP_NAME=IveSeenThis
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inqord_iveseenthis
DB_USERNAME=root
DB_PASSWORD=root
```

Create the database, then run the migrations:

```sql
CREATE DATABASE inqord_iveseenthis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate
php artisan serve
```

Open `http://localhost:8000` and create an account.

## Development

Start the application and asset watcher with:

```bash
composer dev
```

Useful commands:

```bash
php artisan test
composer lint:check
composer types:check
npm run build
```

The test suite uses an isolated test database configuration and does not modify the local MySQL database.

## Main Routes

- `/dashboard` - overview and recent issues
- `/issues` - search and browse issues
- `/issues/create` - capture a new issue, attempts, and solution
- `/projects` - create and organize projects
- `/solutions` - search reusable fixes

## Branch Workflow

Create a feature branch for new work:

```bash
git switch -c feature/short-description
```

After testing, commit the feature and merge it into `main`:

```bash
git add .
git commit -m "Describe the feature"
git switch main
git pull --ff-only origin main
git merge --no-ff feature/short-description -m "Merge feature"
git push origin main
```

## Project Structure

- `app/Models` - Eloquent models and relationships
- `database/migrations` - DevLog database schema
- `resources/views/pages` - full-page Livewire screens
- `resources/views/dashboard.blade.php` - dashboard overview
- `tests/Feature/DevLogTest.php` - core DevLog behavior tests
