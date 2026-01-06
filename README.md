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
8. **Comprehensive Password Policy:** Enforces strong password rules: minimum length (12) ✅, complexity (uppercase, lowercase, numbers, symbols) ✅, password history, and expiry.
9. **Automated Account Lifecycle Management:** The system automatically deactivates user accounts that have been inactive for a specified period (e.g., 90 days) to enhance security by removing dormant access.
10. **Comprehensive Audit Trail & Activity Log:** Logs all critical actions (logins, data changes, failed attempts) for monitoring and forensic analysis.
11. **Session Management & Clear Screen Policy:** Automatically logs out users after a period of inactivity to prevent unauthorized access to unattended sessions.
12. **Data Encryption & Masking:** Provides helpers and methods to encrypt sensitive data (like IC numbers) in the database and mask it when displayed on screen.
13. **Secure & Validated File Uploads:** Provides a secure file upload mechanism that includes robust backend validation for file types (MIME) and size. The handler is built with security hooks, allowing for easy integration with a server-side file scanning engine.
14. **Robust Input Validation:** A mechanism to filter, sanitize, and validate all user inputs (from forms, URL parameters, etc.) to prevent common attacks like SQL Injection and Cross-Site Scripting (XSS).
15. **HTTPS Connection Enforcement:** Includes a middleware that automatically forces all connections to the application to use a secure HTTPS connection, redirecting any insecure HTTP requests. This ensures all data transmitted between the user and the server is encrypted.
16. **Content Security Policy (CSP) Header:** Adds an HTTP security header that tells the browser which sources of content are allowed on the site, helping to prevent the injection of malicious content like in Cross-Site Scripting (XSS) attacks.

## How-to

### Implementing Automated Account Lifecycle Management (Logic & Flow)

This section outlines the conceptual flow for automatically deactivating user accounts that have been inactive for a specified period. The entire process is automated and does not require manual intervention.

**1. Data Foundation: Tracking User Status**

* **Logic:** The system must be able to track two key pieces of information for every user: their last known activity time and their current account status (active or inactive).
* **Flow:** The user data model in the database is extended to store two new attributes:
    * `last_login_at`: A timestamp that records the exact date and time of the user's last successful login.
    * `is_active`: A status flag, typically a boolean (true/false), that indicates whether the account is currently enabled. By default, all new accounts are set to active.

**2. Activity Recording: Updating Last Login Time**

* **Logic:** The `last_login_at` timestamp must be updated automatically upon a successful login to remain accurate.
* **Flow:** An automated trigger is set within the application's authentication process. Whenever a user successfully authenticates and logs in, this trigger fires and updates the `last_login_at` field for that specific user to the current time.

**3. The Automated Janitor: Daily System Check**

* **Logic:** A recurring, automated task is needed to periodically scan for and handle dormant accounts.
* **Flow:** A scheduled task (often called a cron job) is configured to run automatically at a fixed interval, typically once every 24 hours. This task is responsible for initiating the deactivation process.

**4. Deactivation Process: Identifying and Flagging**

* **Logic:** The daily task needs to identify which users have become inactive based on a predefined business rule.
* **Flow:** During its daily run, the scheduled task queries the user database. It searches for all users that meet **both** of the following criteria:
    1.  Their `is_active` status is currently `true`.
    2.  Their `last_login_at` timestamp is older than a set duration (e.g., 90 days ago).
* For every user that matches these conditions, the task proceeds to change their `is_active` status from `true` to `false`.

**5. Enforcing Deactivation: Blocking Login**

* **Logic:** An inactive status must effectively prevent a user from accessing the system.
* **Flow:** The core login function of the application is modified. Before attempting to validate a user's password, it performs a preliminary check on the user's `is_active` status. If the status is `false`, the login process is immediately halted, and the user is denied access, typically with a message indicating their account is inactive.

### Implementing a Password Expiry Policy

This section outlines the logic and flow for forcing users to change their password after a specific period (e.g., 90 days). The core of this implementation relies on a custom middleware to act as a gatekeeper.

**1. Data Foundation: Tracking Password Age**

* **Logic:** The system needs to know when a user last changed their password to calculate its age.
* **Flow:** The `users` table in the database is modified to include a new timestamp column: `password_changed_at`.
    * This field is populated with the current date and time whenever a user successfully sets or resets their password.
    * For new user registrations, this field is also set to the registration time.

**2. The Gatekeeper: `CheckPasswordExpiry` Middleware**

* **Logic:** A middleware is created to intercept user requests after they log in but before they access protected pages. It checks if the user's password has expired and takes action if it has.
* **Flow:**
    1.  **Creation:** A new middleware is created (e.g., `CheckPasswordExpiryMiddleware`).
    2.  **Registration:** This middleware is applied to all web routes that require an authenticated user (e.g., dashboard, settings, etc.). It should **not** be applied to the login/logout routes or the password reset page itself to avoid infinite redirect loops.
    3.  **Execution:** After a user logs in, for every subsequent request to a protected page, this middleware runs.
    4.  **Verification:** Inside the middleware, it retrieves the logged-in user's `password_changed_at` date. It then calculates if the duration since that date has exceeded the policy limit (e.g., 90 days).

**3. The Logic Branch: Handling an Expired Password**

* **Logic:** The middleware must decide what to do based on the password's age.
* **Flow:**
    * **IF Password is OK (Not Expired):** The middleware does nothing and allows the user's request to proceed to the intended page (e.g., `/dashboard`).
    * **IF Password HAS EXPIRED:** The middleware intercepts the request. It redirects the user to a dedicated "password reset" page. A message is often included to inform the user why they were redirected (e.g., "Your password has expired and must be changed.").

**4. The Forced Reset Page**

* **Logic:** A secure page where the user can only set a new password and cannot navigate elsewhere.
* **Flow:**
    1.  The user is "trapped" on this page after being redirected by the middleware.
    2.  The page provides a form to enter and confirm a new password.
    3.  Upon submission, the system validates the new password against any rules (e.g., complexity, not same as old password).
    4.  If valid, the new password is saved, and critically, the `password_changed_at` timestamp for that user is updated to the current time.
    5.  The user is then redirected to the application's main dashboard, and the expiry "timer" is now reset.

### Implementing Password History Policy

This section details the logic for preventing users from reusing their recent passwords (e.g., their last 5 passwords). This check is integrated directly into the password update process.

**1. Data Foundation: Storing Old Passwords Securely**

* **Logic:** The system needs a dedicated, secure place to store a user's previously used passwords.
* **Flow:**
    * A new database table is created, for example, `password_histories`.
    * This table will have columns like `user_id` (to link to the user) and `password`.
    * **Crucial Security Note:** Passwords in this history table **must be stored as a hash**, using the same secure hashing algorithm as the primary password. **Never store old passwords in plain text.**

**2. The Validation Gate: Checking Against History**

* **Logic:** Before accepting a new password from a user, the system must check it against their recent password history.
* **Flow:** This logic is added as a custom validation rule during any password change event (forced expiry or voluntary change).
    1.  When a user submits a new password, the validation process begins.
    2.  The system retrieves the user's most recent 'N' (e.g., 5) hashed passwords from the `password_histories` table.
    3.  The system takes the **newly submitted password** and hashes it.
    4.  It then compares this new hash against the list of historical hashes.
    5.  **Decision Point:**
        * **If a match is found**, the validation fails. An error message is returned to the user (e.g., "You cannot reuse one of your last 5 passwords."). The password is not changed.
        * **If no match is found**, the validation for this rule passes, and the system can proceed to the next step.

**3. The Update Process: Recording and Housekeeping**

* **Logic:** Once a new, valid password is confirmed, it must be saved as the current password, and also added to the history. The history log should also be kept tidy.
* **Flow:**
    1.  **Save Current Password:** The system updates the `password` field in the main `users` table with the new hashed password. It also updates the `password_changed_at` timestamp as per the expiry policy.
    2.  **Record in History:** Immediately after, a new entry is created in the `password_histories` table containing the `user_id` and the new hashed password.
    3.  **Cleanup (Optional but Recommended):** To prevent the history table from growing indefinitely, a cleanup task runs. It checks if the user now has more than 'N' passwords in their history. If so, it deletes the oldest record, ensuring only the most recent 'N' passwords are retained for future checks.
    
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
