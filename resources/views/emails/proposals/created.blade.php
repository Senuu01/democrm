<!DOCTYPE html>
<html>
<head>
    <title>New Proposal</title>
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
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            margin: 20px 0;
        }
        .valid-until {
            color: #e53e3e;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Proposal</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $proposal->customer->name }},</p>
            
            <p>We are pleased to present you with a new proposal for your consideration.</p>
            
            <h2>{{ $proposal->title }}</h2>
            
            <div class="amount">
                ${{ number_format($proposal->amount, 2) }}
            </div>
            
            <div class="description">
                <h3>Description:</h3>
                <p>{{ $proposal->description }}</p>
            </div>
            
            @if($proposal->terms_conditions)
                <div class="terms">
                    <h3>Terms and Conditions:</h3>
                    <p>{{ $proposal->terms_conditions }}</p>
                </div>
            @endif
            
            <p class="valid-until">
                This proposal is valid until: {{ $proposal->valid_until->format('F j, Y') }}
            </p>
            
            <p>Please review this proposal and let us know if you have any questions or if you would like to proceed with the agreement.</p>
            
            <p>You can reply to this email or contact us directly to discuss this proposal further.</p>
            
            <p>Best regards,<br>
            The CRM Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html> 