# Laravel Project

## Installation

Follow the steps below to set up the project:

1. **Clone or Download**
   ```sh
   git clone <repository-url>
   cd <project-folder>
   ```

2. **Install Dependencies**
   ```sh
   composer install
   ```

3. **Set Up Environment Variables**
   ```sh
   cp .env.example .env
   ```
   Configure your `.env` file, including SMTP settings and local database connection.

4. **Generate Application Key**
   ```sh
   php artisan key:generate
   ```

5. **Run Migrations**
   ```sh
   php artisan migrate
   ```

6. **Seed the Database**
   ```sh
   php artisan db:seed
   ```

7. **Build Frontend Assets**
   - To compile assets for production:
     ```sh
     yarn build
     ```
   - For development with live reload:
     ```sh
     yarn dev
     ```
     Running `yarn dev` allows changes to be reflected instantly during development.

8. **Start the Development Server**
   ```sh
   php artisan serve
   ```
   If a custom port is needed, specify it with:
   ```sh
   php artisan serve --port=8081
   ```

## Features

This template includes Laravel Jetstream authentication and provides:

1. User Registration
2. Email Verification
3. User Login
4. User Logout
5. Multi-Factor Authentication (MFA) (enable/disable in user profile)
6. Basic CRUD (User Management)
7. Role and Permission Management (via Spatie)

## Documentation

- Laravel Jetstream: [Jetstream Documentation](https://jetstream.laravel.com/introduction.html)
- Sneat Template: [Sneat Documentation](https://demos.themeselection.com/sneat-bootstrap-html-admin-template/documentation/laravel-introduction.html)

---

Enjoy building with Laravel! 🚀

