# Stayvia Laravel Application

This project uses Laravel 12, PHP 8.2 or newer, and Blade templates.

## Project structure

- `routes/web.php` defines web routes.
- `app/Http/Controllers/` contains request handling and validation.
- `resources/views/` contains Blade templates and components.
- `public/css/app.css` and `public/js/app.js` contain the frontend styles and browser interactions.
- `public/assets/` contains the hotel photography.
- `app/Models/User.php`, `config/auth.php`, and `resources/views/auth/` implement email/password authentication.
- `database/migrations/` defines hotel, room, reservation, and user tables.

## Development

Install PHP dependencies with `composer install`, copy `.env.example` to `.env`, generate an application key with `php artisan key:generate`, and run `php artisan serve`.

Validate PHP syntax with `php -l` and run the feature suite with `composer test`.
Configure the database connection in `.env`, then apply schema and sample hotel data with `php artisan migrate --seed`.
