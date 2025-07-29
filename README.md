# Laravel 12 Project

## Introduction
This project is built using Laravel 12. Please ensure that your environment is properly set up for Laravel 12.

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

1. **User Registration:** Standard process for new users to create an account. ✅
2. **Email Verification:** Ensures every user account is tied to a valid and confirmed email address. ✅
3. **User Login:** Secure mechanism for registered users to access the system. ✅
4. **User Logout:** Allows users to safely terminate their application session. ✅
5. **Multi-Factor Authentication (MFA):** An optional extra security layer that users can enable in their profile, requiring a second verification step (e.g., from an authenticator app). ✅
6. **Basic CRUD for user management:** An admin interface to Create, Read, Update, and Delete user records. ✅
7. **Role and permission management using Spatie:** A powerful system to define user roles (e.g., 'Admin', 'Supervisor') and assign specific permissions, enforcing the Principle of Least Privilege. ✅
8. **Comprehensive Password Policy:** Enforces strong password rules: minimum length, complexity (uppercase, lowercase, numbers, symbols), password history, and expiry.
9. **Automated Account Lifecycle Management:** The system automatically deactivates user accounts that have been inactive for a specified period (e.g., 90 days) to enhance security by removing dormant access.
10. **Comprehensive Audit Trail & Activity Log:** Logs all critical actions (logins, data changes, failed attempts) for monitoring and forensic analysis.
11. **Session Management & Clear Screen Policy:** Automatically logs out users after a period of inactivity to prevent unauthorized access to unattended sessions.
12. **Data Encryption & Masking:** Provides helpers and methods to encrypt sensitive data (like IC numbers) in the database and mask it when displayed on screen.
13. **Secure File Upload Scanning:** Automatically scans all user-uploaded files for malware and viruses to protect the server and other users.

## **How-to**
Implementing Automated User Deactivation

Here is a conceptual guide on how to implement the automated user deactivation feature. This approach focuses on tracking user activity to automatically disable accounts that have been dormant for a security-enhancing period.

**Step 1: Database Schema Enhancements**

You only need to add two columns to your `users` table migration to track user activity and status.

```php
// In database/migrations/xxxx_xx_xx_xxxxxx_create_users_table.php

Schema::table('users', function (Blueprint $table) {
    // Add these columns after the existing ones
    $table->timestamp('last_login_at')->nullable();
    $table->boolean('is_active')->default(true);
});

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
