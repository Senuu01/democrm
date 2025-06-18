<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Our CRM System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            padding: 20px 0;
            background-color: #f8f9fa;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Our CRM System</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $customer->name }},</p>
            
            <p>Welcome to our CRM system! We're excited to have you on board.</p>
            
            <p>Your account has been successfully created with the following details:</p>
            
            <ul>
                <li><strong>Name:</strong> {{ $customer->name }}</li>
                <li><strong>Email:</strong> {{ $customer->email }}</li>
                @if($customer->company_name)
                    <li><strong>Company:</strong> {{ $customer->company_name }}</li>
                @endif
            </ul>
            
            <p>You can now access our system and manage your business relationships more effectively.</p>
            
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br>
            The CRM Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html> 