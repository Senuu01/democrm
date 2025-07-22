# Email Configuration Guide

## The Issue
You're getting "Failed to send reset code. Please try again" because your Laravel application can't send emails properly. This usually happens due to missing or incorrect email configuration.

## Quick Fix

### Step 1: Configure Your .env File
Add these lines to your `.env` file (create one if it doesn't exist):

```env
# Basic App Settings
APP_NAME="Connectly CRM"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Mail Configuration - Choose ONE option below:

# OPTION 1: Use Mailtrap (Recommended for testing)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@connectlycrm.com"
MAIL_FROM_NAME="Connectly CRM"

# OPTION 2: Use Gmail SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-gmail@gmail.com"
MAIL_FROM_NAME="Connectly CRM"

# OPTION 3: Use Resend (Modern alternative)
MAIL_MAILER=resend
RESEND_KEY=your_resend_api_key
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Connectly CRM"

# OPTION 4: Log emails to file (for testing only)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@connectlycrm.com"
MAIL_FROM_NAME="Connectly CRM"
```

### Step 2: Test Your Email Configuration

Visit this URL to test if emails work:
```
http://your-domain.com/test-email?test_email=your-email@example.com
```

Replace `your-email@example.com` with your actual email address.

## Detailed Setup Options

### Option 1: Mailtrap (Easiest for Development)

1. Go to [mailtrap.io](https://mailtrap.io) and create a free account
2. Create a new inbox
3. Copy the SMTP credentials to your `.env` file
4. All emails will be caught by Mailtrap instead of being sent to real users

### Option 2: Gmail SMTP

1. Enable 2-factor authentication on your Gmail account
2. Generate an "App Password" in your Google Account settings
3. Use the app password (not your regular password) in the `.env` file

### Option 3: Resend (Recommended for Production)

1. Sign up at [resend.com](https://resend.com)
2. Get your API key from the dashboard
3. Add your domain and verify it
4. Use the API key in your `.env` file

### Option 4: Log Only (Testing)

This option writes emails to `storage/logs/laravel.log` instead of sending them. Good for debugging.

## After Configuration

1. Clear your config cache:
   ```bash
   php artisan config:clear
   ```

2. Test the password reset again

3. Check your logs in `storage/logs/laravel.log` for any error messages

## Common Issues

### "Connection refused"
- Check your SMTP host and port
- Make sure your firewall allows outbound connections on the SMTP port

### "Authentication failed"  
- Verify your username and password
- For Gmail, make sure you're using an app password, not your regular password

### "SSL/TLS errors"
- Try changing `MAIL_ENCRYPTION` from `tls` to `ssl` or vice versa
- Some servers use port 465 with SSL instead of 587 with TLS

### Still not working?
- Set `MAIL_MAILER=log` temporarily to see if the email content is being generated correctly
- Check `storage/logs/laravel.log` for detailed error messages
- Use the test email URL to get more specific error messages

## Need Help?

If you're still having issues, please share:
1. Your current `.env` mail settings (hide passwords)
2. The exact error message from the test email URL
3. Any relevant logs from `storage/logs/laravel.log` 