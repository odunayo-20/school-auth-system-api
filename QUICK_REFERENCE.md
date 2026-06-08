# Quick Reference - Email Auth API

## 🚀 Setup (3 Steps)

```bash
php artisan migrate
# Update .env with MAIL_* config
php artisan serve
```

## 📡 Endpoints

### Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John",
    "email": "john@example.com",
    "password": "pass123",
    "password_confirmation": "pass123"
  }'
```

### Verify Email

```bash
curl -X POST http://localhost:8000/api/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "code": "123456"
  }'
```

### Request Sign-In Code

```bash
curl -X POST http://localhost:8000/api/auth/request-signin-code \
  -H "Content-Type: application/json" \
  -d '{"email": "john@example.com"}'
```

### Sign-In With Code

```bash
curl -X POST http://localhost:8000/api/auth/signin-with-code \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "code": "654321"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "pass123"
  }'
```

### Get Profile (with token)

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer TOKEN_HERE"
```

### Logout (with token)

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer TOKEN_HERE"
```

## 🔑 Response Format

### Success (200/201)

```json
{
    "message": "...",
    "user": { "id": 1, "email": "..." },
    "token": "..."
}
```

### Error (422)

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field": ["error message"]
    }
}
```

## 📊 Code Expiration

-   Verification: 15 minutes
-   Sign-In: 10 minutes

## 🧪 Testing

```bash
php artisan test
php artisan test --filter register
```

## 📧 Email Config (.env)

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_user
MAIL_PASSWORD=your_pass
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Auth System"
```

## 📁 Key Files

-   `app/Http/Controllers/AuthController.php` - All logic
-   `app/Mail/SendVerificationCode.php` - Verification email
-   `app/Mail/SendSignInCode.php` - Sign-in email
-   `resources/views/emails/*.blade.php` - Email templates
-   `routes/api.php` - API routes
-   `tests/Feature/CompleteAuthenticationTest.php` - Tests

## 🔐 Fields Hidden in Responses

-   password
-   email_verification_code
-   email_verification_code_expires_at
-   signin_code
-   signin_code_expires_at
-   remember_token

## ✅ Validation Rules

### Register

-   name: required, string, max 255
-   email: required, email, unique
-   password: required, min 8, confirmed

### Verify Email

-   email: required, exists in users
-   code: required, 6 digits

### Request Sign-In Code

-   email: required, exists, verified

### Sign-In With Code

-   email: required, exists
-   code: required, 6 digits

### Login

-   email: required, exists, verified
-   password: required

## 🚨 Common Issues

**Emails not sending?**

-   Check `.env` mail settings
-   Verify MAIL_FROM_ADDRESS

**Migration failed?**

-   Run: `php artisan migrate:fresh`
-   Check database connection

**Test fails?**

-   Run: `php artisan migrate:refresh --env=testing`

## 🎯 Authentication Flow

```
Register → Verify Email → Login/Sign-In → Get Token → Access Protected Routes
```

## 📚 Full Documentation

-   API_DOCUMENTATION.md - Detailed endpoint docs
-   SETUP_GUIDE.md - Setup & troubleshooting
-   IMPLEMENTATION_SUMMARY.md - What's been built
