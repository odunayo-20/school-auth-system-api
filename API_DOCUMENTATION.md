# Authentication System API

A simple, readable, and clear email-based authentication system built with Laravel.

## Features

✅ **User Registration** - Create new accounts with email verification  
✅ **Email Verification** - Send verification codes (6-digit) to confirm email  
✅ **Code-based Sign In** - Sign in using email and a code sent via email  
✅ **Traditional Login** - Sign in with email and password  
✅ **Session Management** - Secure token-based authentication with Laravel Sanctum

## API Endpoints

### Authentication Routes (Public)

#### 1. Register

**POST** `/api/auth/register`

Create a new user account and send verification email.

```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Response (201):**

```json
{
    "message": "User registered successfully. Please verify your email.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": null,
        "created_at": "2026-06-07T20:00:00.000000Z",
        "updated_at": "2026-06-07T20:00:00.000000Z"
    }
}
```

---

#### 2. Verify Email

**POST** `/api/auth/verify-email`

Confirm email address using the verification code sent via email.

```json
{
    "email": "john@example.com",
    "code": "123456"
}
```

**Response (200):**

```json
{
    "message": "Email verified successfully.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2026-06-07T20:05:00.000000Z",
        "created_at": "2026-06-07T20:00:00.000000Z",
        "updated_at": "2026-06-07T20:05:00.000000Z"
    }
}
```

---

#### 3. Request Sign-In Code

**POST** `/api/auth/request-signin-code`

Request a sign-in code to be sent to the user's email.

```json
{
    "email": "john@example.com"
}
```

**Response (200):**

```json
{
    "message": "Sign-in code sent to your email."
}
```

**Requirements:**

-   Email must be registered
-   Email must be verified

---

#### 4. Sign In With Code

**POST** `/api/auth/signin-with-code`

Authenticate using email and the code sent via email.

```json
{
    "email": "john@example.com",
    "code": "654321"
}
```

**Response (200):**

```json
{
    "message": "Signed in successfully.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2026-06-07T20:05:00.000000Z",
        "created_at": "2026-06-07T20:00:00.000000Z",
        "updated_at": "2026-06-07T20:10:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
}
```

---

#### 5. Login

**POST** `/api/auth/login`

Traditional authentication using email and password.

```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response (200):**

```json
{
    "message": "Logged in successfully.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2026-06-07T20:05:00.000000Z",
        "created_at": "2026-06-07T20:00:00.000000Z",
        "updated_at": "2026-06-07T20:10:00.000000Z"
    },
    "token": "2|abcdefghijklmnopqrstuvwxyz123456789"
}
```

---

### Authenticated Routes (Require Token)

Include the token in the Authorization header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

#### 6. Get Current User

**GET** `/api/auth/me`

Get the authenticated user's information.

**Response (200):**

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "email_verified_at": "2026-06-07T20:05:00.000000Z",
        "created_at": "2026-06-07T20:00:00.000000Z",
        "updated_at": "2026-06-07T20:10:00.000000Z"
    }
}
```

---

#### 7. Logout

**POST** `/api/auth/logout`

Revoke the current authentication token.

**Response (200):**

```json
{
    "message": "Logged out successfully."
}
```

---

## Code Expiration

-   **Verification Code:** 15 minutes
-   **Sign-In Code:** 10 minutes

Both codes are hashed and stored in the database for security.

---

## Email Configuration

The system sends emails automatically. Make sure you configure your mail settings in `.env`:

```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="Auth System"
```

---

## Setup Instructions

1. **Run Migrations**

    ```bash
    php artisan migrate
    ```

2. **Update `.env` with Mail Configuration**

    ```
    MAIL_DRIVER=smtp
    MAIL_HOST=your_mail_host
    MAIL_USERNAME=your_username
    MAIL_PASSWORD=your_password
    ```

3. **Start the Server**
    ```bash
    php artisan serve
    ```

---

## Security Features

✅ Passwords hashed with bcrypt  
✅ Verification and sign-in codes hashed with bcrypt  
✅ Sensitive fields hidden from API responses  
✅ Token-based authentication with Laravel Sanctum  
✅ Code expiration enforcement  
✅ Email verification required before sign-in

---

## Error Handling

All errors return appropriate HTTP status codes with clear messages:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

---

## Example Usage Flow

### 1. Register User

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Verify Email (code sent via email)

```bash
curl -X POST http://localhost:8000/api/auth/verify-email \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "code": "123456"
  }'
```

### 3. Request Sign-In Code

```bash
curl -X POST http://localhost:8000/api/auth/request-signin-code \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com"
  }'
```

### 4. Sign In With Code

```bash
curl -X POST http://localhost:8000/api/auth/signin-with-code \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "code": "654321"
  }'
```

### 5. Get User Profile (using token)

```bash
curl -X GET http://localhost:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Database Schema

The `users` table includes:

-   `id` - Primary key
-   `name` - User's name
-   `email` - User's email (unique)
-   `password` - Hashed password
-   `email_verified_at` - Timestamp when email was verified
-   `email_verification_code` - Hashed verification code
-   `email_verification_code_expires_at` - Code expiration time
-   `signin_code` - Hashed sign-in code
-   `signin_code_expires_at` - Code expiration time
-   `created_at` / `updated_at` - Timestamps

---

## License

MIT
