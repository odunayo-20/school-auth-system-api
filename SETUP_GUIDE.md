# Setup Guide - Email Authentication System

## Quick Start

Follow these steps to get the authentication system running.

### Step 1: Run Migrations

```bash
php artisan migrate
```

This creates the necessary database fields:

-   `email_verification_code`
-   `email_verification_code_expires_at`
-   `signin_code`
-   `signin_code_expires_at`

### Step 2: Configure Email

Update your `.env` file with email configuration. For development, you can use Mailtrap:

```env
# Email Configuration
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Auth System"
```

#### Alternative Email Providers:

**Gmail (with App Password):**

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Auth System"
```

**Sendgrid:**

```env
MAIL_DRIVER=sendgrid
SENDGRID_API_KEY=your_sendgrid_key
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Auth System"
```

### Step 3: Start the Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### Step 4: (Optional) Monitor Emails

For development, check your Mailtrap inbox to see sent emails.

---

## Testing

Run the complete test suite:

```bash
php artisan test
```

Or run only authentication tests:

```bash
php artisan test tests/Feature/CompleteAuthenticationTest.php
```

---

## Architecture Overview

### Authentication Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                   AUTHENTICATION FLOW                             │
├──────────────────────────────────────────────────────────────────┤
│                                                                    │
│  1. REGISTER                                                       │
│     POST /api/auth/register                                       │
│     ↓ Sends verification email with 6-digit code                │
│                                                                    │
│  2. VERIFY EMAIL                                                   │
│     POST /api/auth/verify-email                                  │
│     ↓ User enters code from email                                │
│     ↓ Email marked as verified                                    │
│                                                                    │
│  3. LOGIN OPTIONS                                                 │
│     Option A: Traditional Login                                   │
│     POST /api/auth/login (email + password)                      │
│                                                                    │
│     Option B: Passwordless Sign-In                               │
│     POST /api/auth/request-signin-code (email)                   │
│     ↓ Sends sign-in code via email                               │
│     POST /api/auth/signin-with-code (email + code)               │
│                                                                    │
│  4. AUTHENTICATED REQUESTS                                        │
│     Use token in Authorization header                             │
│     GET /api/auth/me                                             │
│     POST /api/auth/logout                                        │
│                                                                    │
└──────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### Users Table

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    email_verified_at TIMESTAMP NULL,
    email_verification_code VARCHAR(255) NULL,
    email_verification_code_expires_at TIMESTAMP NULL,
    signin_code VARCHAR(255) NULL,
    signin_code_expires_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## File Structure

```
app/
├── Http/
│   └── Controllers/
│       └── AuthController.php          (All authentication logic)
├── Mail/
│   ├── SendVerificationCode.php        (Email for verification)
│   └── SendSignInCode.php              (Email for sign-in)
└── Models/
    └── User.php                         (Updated with new fields)

database/
├── migrations/
│   └── 2026_06_07_200000_add_auth_fields_to_users.php
└── seeders/
    └── DatabaseSeeder.php

resources/
└── views/
    └── emails/
        ├── verification-code.blade.php  (Verification email template)
        └── signin-code.blade.php        (Sign-in email template)

routes/
└── api.php                              (Updated with auth routes)

tests/
└── Feature/
    └── CompleteAuthenticationTest.php  (Comprehensive tests)
```

---

## Code Expiration Times

| Code Type          | Expiration | Purpose                              |
| ------------------ | ---------- | ------------------------------------ |
| Email Verification | 15 minutes | Verify new email during registration |
| Sign-In Code       | 10 minutes | Passwordless sign-in                 |

---

## Security Best Practices

✅ **Passwords hashed** - Using bcrypt  
✅ **Codes hashed** - Using bcrypt (never stored in plain text)  
✅ **Sensitive fields hidden** - Codes excluded from API responses  
✅ **Token-based auth** - Using Laravel Sanctum  
✅ **Code expiration** - Enforced server-side  
✅ **Email verification required** - Cannot login without verified email  
✅ **Rate limiting** - Implement with middleware for production

---

## Troubleshooting

### Emails not sending?

1. Check `.env` mail configuration
2. Verify MAIL_FROM_ADDRESS is valid
3. Test with Mailtrap first (no actual email account needed)
4. Check server logs: `php artisan tail`

### Migration errors?

```bash
# Fresh migration (warning: drops all tables)
php artisan migrate:fresh

# Or specific migration
php artisan migrate --path=database/migrations/2026_06_07_200000_add_auth_fields_to_users.php
```

### Tests failing?

```bash
# Refresh test database
php artisan migrate:refresh --env=testing

# Run tests with debug
php artisan test --verbose
```

---

## Production Checklist

-   [ ] Configure production mail service
-   [ ] Set `APP_DEBUG=false` in `.env`
-   [ ] Set `APP_ENV=production` in `.env`
-   [ ] Run `composer install --no-dev`
-   [ ] Add rate limiting to auth routes
-   [ ] Configure CORS if frontend is on different domain
-   [ ] Enable HTTPS
-   [ ] Set up database backups
-   [ ] Monitor error logs
-   [ ] Consider adding 2FA for extra security

---

## Example Postman Collection

```json
{
    "info": {
        "name": "Auth System API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "Register",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/api/auth/register",
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"name\": \"John Doe\",\n  \"email\": \"john@example.com\",\n  \"password\": \"password123\",\n  \"password_confirmation\": \"password123\"\n}"
                }
            }
        },
        {
            "name": "Verify Email",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/api/auth/verify-email",
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"email\": \"john@example.com\",\n  \"code\": \"123456\"\n}"
                }
            }
        },
        {
            "name": "Request Sign-In Code",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/api/auth/request-signin-code",
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"email\": \"john@example.com\"\n}"
                }
            }
        },
        {
            "name": "Sign In With Code",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/api/auth/signin-with-code",
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"email\": \"john@example.com\",\n  \"code\": \"654321\"\n}"
                }
            }
        },
        {
            "name": "Login",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/api/auth/login",
                "body": {
                    "mode": "raw",
                    "raw": "{\n  \"email\": \"john@example.com\",\n  \"password\": \"password123\"\n}"
                }
            }
        },
        {
            "name": "Get Profile",
            "request": {
                "method": "GET",
                "url": "{{base_url}}/api/auth/me",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    }
                ]
            }
        },
        {
            "name": "Logout",
            "request": {
                "method": "POST",
                "url": "{{base_url}}/api/auth/logout",
                "header": [
                    {
                        "key": "Authorization",
                        "value": "Bearer {{token}}"
                    }
                ]
            }
        }
    ]
}
```

---

## Support

For issues or questions, check:

1. The API_DOCUMENTATION.md file
2. Test cases in tests/Feature/CompleteAuthenticationTest.php
3. Laravel documentation: https://laravel.com/docs
4. Sanctum documentation: https://laravel.com/docs/sanctum
