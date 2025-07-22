<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Login Code</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #4f46e5;">Your Connectly Login Code</h2>
        
        <p>Hi there!</p>
        
        <p>Your login code is:</p>
        
        <div style="background: #f8f9fa; border: 2px solid #4f46e5; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
            <h1 style="font-size: 32px; letter-spacing: 8px; color: #4f46e5; margin: 0;">{{ $code }}</h1>
        </div>
        
        <p>This code will expire in 10 minutes.</p>
        
        <p>If you didn't request this code, please ignore this email.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        
        <p style="color: #666; font-size: 14px;">
            Best regards,<br>
            The Connectly Team
        </p>
    </div>
</body>
</html>