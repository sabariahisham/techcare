# Laravel 11 Project

## Introduction
This project is built using Laravel 11. Please ensure that your environment is properly set up for Laravel 11.

## Installation Steps

1. Clone or download the project repository.
   ```sh
   git clone <repository-url>
   cd <project-directory>
   ```

2. Install dependencies using Composer.
   ```sh
   composer install
   ```

3. Copy the environment file and set up SMTP and local database configurations.
   ```sh
   cp .env.example .env
   ```

4. Generate the application key.
   ```sh
   php artisan key:generate
   ```

5. Run database migrations.
   ```sh
   php artisan migrate
   ```

6. Seed the database.
   ```sh
   php artisan db:seed
   ```

7. Install frontend dependencies using Yarn.
   ```sh
   yarn
   ```
   **Why is this needed?**
   - This installs necessary JavaScript dependencies required for the frontend.
   - If this step is skipped, frontend components may not function correctly.
   
   **If `yarn` is not recognized, install it using:**
   ```sh
   npm install -g yarn
   ```

8. Build frontend assets.
   ```sh
   yarn build
   ```
   - `yarn build` compiles assets for production.
   - `yarn dev` is used for local development.
   - **Which command to use?**
     - Local PC: `yarn dev`
     - Development: `yarn dev`
     - Production: `yarn build`

9. Serve the application.
   ```sh
   php artisan serve
   ```
   - To specify a custom port (e.g., 8081), use:
     ```sh
     php artisan serve --port=8081
     ```

## Features
This template comes with Laravel Jetstream authentication and includes the following features:

1. User Registration
2. Email Verification
3. User Login
4. User Logout
5. Multi-Factor Authentication (MFA) (can be enabled/disabled in the user profile)
6. Basic CRUD for user management
7. Role and permission management using Spatie

## Documentation
- [Laravel Jetstream Documentation](https://jetstream.laravel.com/introduction.html)
- [Sneat Template Documentation](https://demos.themeselection.com/sneat-bootstrap-html-admin-template/documentation/laravel-introduction.html)

## Possible Enhancements
1. The migrations already include UUIDs. Ensure that UUIDs are properly referenced in controllers.
2. Implement the **Thin Controller, Fat Service** approach.
   - **Why?**
     - Keeps controllers lightweight and focused on request handling.
     - Moves business logic to service classes, improving maintainability and testability.
   - Consider creating a service layer to handle business logic instead of bloating controllers.
