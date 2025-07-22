<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Connectly</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #4f46e5;">Welcome to Connectly!</h2>
        
        <p>Hi {{ $name }},</p>
        
        <p>Welcome to Connectly - your modern CRM solution! We're excited to have you on board.</p>
        
        <p>You can now login to your account and start managing your customer relationships effectively.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ config('app.url') }}" 
               style="background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">
                Access Your Dashboard
            </a>
        </div>
        
        <p>If you have any questions, feel free to reach out to our support team.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        
        <p style="color: #666; font-size: 14px;">
            Best regards,<br>
            The Connectly Team
        </p>
    </div>
</body>
</html>