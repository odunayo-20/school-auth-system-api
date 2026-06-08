<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; }
        .code-box { background-color: #e9ecef; padding: 15px; text-align: center; border-radius: 5px; margin: 20px 0; }
        .code-box .code { font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #28a745; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Your Sign In Code</h2>
        </div>
        <div class="content">
            <p>Hi {{ $user->name }},</p>
            <p>Here's your secure sign-in code:</p>

            <div class="code-box">
                <p>Sign In Code:</p>
                <div class="code">{{ $code }}</div>
            </div>

            <p><strong>This code will expire in 10 minutes.</strong></p>
            <p>If you didn't request this code, please ignore this email.</p>

            <div class="footer">
                <p>&copy; {{ date('Y') }} Auth System. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
