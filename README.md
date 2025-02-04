# Laravel Project Setup Guide

## Installation Steps

1. **Clone or Download**
   ```sh
   git clone <repository_url>
   cd <project_directory>
   ```

2. **Install Dependencies**
   ```sh
   composer install
   ```

3. **Setup Environment**
   ```sh
   cp .env.example .env
   ```
   - Configure SMTP and local database settings in `.env`

4. **Generate Application Key**
   ```sh
   php artisan key:generate
   ```

5. **Run Migrations**
   ```sh
   php artisan migrate
   ```

6. **Seed Database**
   ```sh
   php artisan db:seed
   ```

7. **Install Frontend Dependencies**
   ```sh
   yarn
   ```
   - If this step is skipped, frontend assets such as styles and scripts may not load correctly.
   - This installs necessary JavaScript dependencies required for the frontend.

8. **Build Frontend Assets**
   ```sh
   yarn build
   ```
   - Alternatively, for development mode:
     ```sh
     yarn dev
     ```
   - This step compiles frontend assets (CSS & JS). Running `yarn build` optimizes assets for production, while `yarn dev` runs a development build with hot reload.

9. **Serve the Application**
   ```sh
   php artisan serve
   ```
   - To run on a custom port (e.g., 8081):
     ```sh
     php artisan serve --port=8081
     ```

---

## Features Included

This template includes Laravel Jetstream authentication and contains the following features:

1. **User Authentication:**
   - Register
   - Verify email
   - Login
   - Logout
   - Multi-Factor Authentication (MFA) (Can be enabled/disabled in user profile)
2. **User Management:**
   - Basic CRUD operations
   - Role and permission management using Spatie package

---

## Documentation References

- [Laravel Jetstream Documentation](https://jetstream.laravel.com/introduction.html)
- [Sneat Template Documentation](https://demos.themeselection.com/sneat-bootstrap-html-admin-template/documentation/laravel-introduction.html)

---

## Possible Enhancements

1. **UUID in Migrations**
   - UUIDs are already implemented in migrations, so controllers should reference UUID instead of the default integer IDs.

2. **Thin Controller, Fat Service Approach**
   - Promotes better separation of concerns by keeping controllers lightweight.
   - Business logic should be handled in service classes rather than controllers, improving maintainability and reusability.

