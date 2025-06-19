<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #4f46e5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .invoice-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4f46e5;
        }
        .pay-button {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
        }
        .pay-button:hover {
            background: #059669;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payment Request</h1>
        <p>Invoice #{{ $invoice->invoice_number }}</p>
    </div>
    
    <div class="content">
        <p>Dear {{ $invoice->customer->name }},</p>
        
        <p>This is a payment request for the following invoice:</p>
        
        <div class="invoice-details">
            <h3>Invoice Details</h3>
            <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Issue Date:</strong> {{ $invoice->issue_date->format('F j, Y') }}</p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('F j, Y') }}</p>
            <p><strong>Amount:</strong> ${{ number_format($invoice->amount, 2) }}</p>
            <p><strong>Tax:</strong> ${{ number_format($invoice->tax_amount, 2) }}</p>
            <p><strong>Total:</strong> ${{ number_format($invoice->total_amount, 2) }}</p>
            
            @if($invoice->notes)
                <p><strong>Notes:</strong> {{ $invoice->notes }}</p>
            @endif
        </div>
        
        <p>Please click the button below to make your payment securely:</p>
        
        <div style="text-align: center;">
            <a href="{{ url('/pay/' . $invoice->id) }}" class="pay-button">
                Pay Now - ${{ number_format($invoice->total_amount, 2) }}
            </a>
        </div>
        
        <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>
        
        <p>Thank you for your business!</p>
    </div>
    
    <div class="footer">
        <p>This is an automated payment request. Please do not reply to this email.</p>
    </div>
</body>
</html> 