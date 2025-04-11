# Marco Polo Home Assignment

Local-Only Social App Admin Panel (Laravel + Filament)

## Installation

Clone the repo locally:

```sh
git clone git@github.com:idea399/test-marco-polo-local-social-app.git test-marco-polo-local-social-app && cd test-marco-polo-local-social-app
```

Install PHP dependencies:

```sh
composer install
```

Setup configuration:

```sh
cp .env.example .env
```

Generate application key:

```sh
php artisan key:generate
```

Create an SQLite database. You can also use another database (MySQL, Postgres), simply update your configuration accordingly.

```sh
touch database/database.sqlite
```

Run database migrations:

```sh
php artisan migrate
```

Run database seeder:

```sh
php artisan db:seed
```


Create a symlink to the storage:

```sh
php artisan storage:link
```

Run the dev server (the output will give the address):

```sh
php artisan serve
```

You're ready to go! Visit the url in your browser, and login with:

- **Username:** admin@example.com
- **Password:** password

## Features to explore

### Resources (CRUD)
- User (name, email, avatar, location)
- Post (user_id, content, image, location, created_at)
- Comment (post_id, user_id, body, created_at)

### Static Location Support
- Saved as static in config
- Available in resource tables/forms
- Filterable
- Searchable

### Dashboard Widget
- Total users, posts, comments
- Recent activity (posts in last 24 hours)
- Computed columns (comment_count on Post table)
- Sorting and column-specific search
- Form validation (required fields, image format and size check)

### Automated Testing (All resource operations)
- User
- Post
- Comment

## Technical Constraints
- No external services or APIs
- Fully self-contained and locally hosted
- Store images locally (storage/app/public)
- Use Laravel's default DB driver (SQLite)
- Add test cases for all resource operations
- Follow Laravel and Filament best practices