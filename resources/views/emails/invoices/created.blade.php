<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9fafb;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #666;
        }
        .amount {
            font-size: 24px;
            color: #4F46E5;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .due-date {
            color: #DC2626;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4F46E5;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Invoice</h1>
        </div>
        
        <div class="content">
            <p>Dear {{ $invoice->customer->name }},</p>
            
            <p>A new invoice has been created for your account. Here are the details:</p>
            
            <div class="amount">
                ${{ number_format($invoice->total_amount, 2) }}
            </div>
            
            <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Issue Date:</strong> {{ $invoice->issue_date->format('F j, Y') }}</p>
            <p><strong>Due Date:</strong> <span class="due-date">{{ $invoice->due_date->format('F j, Y') }}</span></p>
            
            <p><strong>Amount:</strong> ${{ number_format($invoice->amount, 2) }}</p>
            <p><strong>Tax Amount:</strong> ${{ number_format($invoice->tax_amount, 2) }}</p>
            
            @if($invoice->notes)
                <p><strong>Notes:</strong><br>{{ $invoice->notes }}</p>
            @endif
            
            <p>You can view and pay this invoice by clicking the button below:</p>
            
            <div style="text-align: center;">
                <a href="{{ route('invoices.payment', $invoice) }}" class="button">View & Pay Invoice</a>
            </div>
            
            <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br>Your CRM Team</p>
        </div>
        
        <div class="footer">
            <p>This is an automated message, please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html> 