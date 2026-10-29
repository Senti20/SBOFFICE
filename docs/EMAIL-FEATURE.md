# 📧 Gmail Email Notification Feature

## Overview

The SB Session Minutes & Resolution Portal can send **email notifications** to requesters via **Gmail SMTP** alongside the existing SMS feature. When a citizen submits a request for a certified copy of a document, they can optionally provide an email address. If provided, the system sends email notifications in parallel with SMS at every key milestone:

| Event | Email Trigger | Who Triggers It |
|---|---|---|
| **New Request Submitted** | Requester submits the form (with email) | Automatic (public portal) |
| **Status Updated** | Admin changes status (e.g., to "Ready for Pickup") | Admin panel |
| **Request Cancelled** | Requester cancels their pending request | Public portal |

> **Note:** Email is **optional**. If the requester does not provide an email address, only SMS notifications are sent (existing behavior).

---

## Prerequisites

1. **A Gmail Account** — Any Gmail or Google Workspace account
2. **2-Step Verification Enabled** — Required to generate an App Password
3. **A Gmail App Password** — A 16-character password generated specifically for this application

---

## Setup Instructions

### 1. Generate a Gmail App Password

Gmail does not allow third-party apps to use your regular password. You must generate an **App Password**:

1. Go to [https://myaccount.google.com/security](https://myaccount.google.com/security)
2. Ensure **2-Step Verification** is turned ON
3. Go to [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
4. Select **"Mail"** as the app and **"Windows Computer"** (or your platform) as the device
5. Click **Generate**
6. Copy the 16-character password (e.g., `abcd efgh ijkl mnop`)

### 2. Configure Environment Variables

Open your `.env` file (project root) and set the following values:

```dotenv
# Gmail SMTP Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_gmail@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your_gmail@gmail.com"
MAIL_FROM_NAME="Bontoc SB Portal"
```

| Variable | Required | Description |
|---|---|---|
| `MAIL_MAILER` | ✅ Yes | Must be `smtp` to enable email sending |
| `MAIL_HOST` | ✅ Yes | Gmail SMTP server: `smtp.gmail.com` |
| `MAIL_PORT` | ✅ Yes | Gmail SMTP port: `587` (TLS) |
| `MAIL_USERNAME` | ✅ Yes | Your full Gmail address |
| `MAIL_PASSWORD` | ✅ Yes | Your 16-character Gmail App Password (NOT your Gmail login password) |
| `MAIL_ENCRYPTION` | ✅ Yes | Must be `tls` |
| `MAIL_FROM_ADDRESS` | ✅ Yes | The "From" address shown in emails (typically the same as `MAIL_USERNAME`) |
| `MAIL_FROM_NAME` | No | The sender name shown in emails (default: `Bontoc SB Portal`) |

### 3. Clear Config Cache (if previously cached)

If you've previously run `php artisan config:cache`, clear and re-cache:

```bash
php artisan config:clear
php artisan config:cache
```

### 4. Run the Migration

The email feature adds an `email` column to the `requests` table:

```bash
php artisan migrate
```

### 5. Verify Setup

Check that your mail config is loaded correctly:

```bash
php artisan tinker
>>> config('mail.mailers.smtp.host')
# Should output: "smtp.gmail.com"
>>> config('mail.mailers.smtp.username')
# Should output your Gmail address
```

---

## How It Works

### Email Flow (New Request)

```
Citizen submits request (with email)
        │
        ▼
Portal\RequestController::store()
        │
        ├── Saves request to database (including email)
        ├── Calls SmsService::send()  →  SMS delivered
        └── Calls EmailService::send()
                │
                ▼
        Creates RequestNotification Mailable
                │
                ▼
        Gmail SMTP → Email delivered to requester
```

### When Admin Updates Status

```
Admin changes status to "Ready for Pickup"
        │
        ▼
Admin\RequestController::update()
        │
        ├── Updates request in database
        ├── Calls SmsService::send()  →  SMS delivered
        └── Calls EmailService::send() (if email exists)
                │
                ▼
        Requester receives email:
        Subject: "Request REQ-123 — Status Update"
        Body: "Your request (REQ-123) status has been
               updated to 'Ready for Pickup'.
               Expected fee: PHP 50.00."
```

---

## File Map — Where the Code Lives

### Core Email Logic

| File | Purpose |
|---|---|
| `app/Services/EmailService.php` | **Main email service class** — handles mail sending, logging, and error handling |
| `app/Mail/RequestNotification.php` | **Mailable class** — defines the email subject, view, and data |
| `resources/views/emails/request-notification.blade.php` | **HTML email template** — professional branded layout with inline styles |
| `config/mail.php` | Gmail SMTP configuration (host, port, credentials) read from `.env` |

### Controllers That Send Email

| File | Method | When Email Is Sent |
|---|---|---|
| `app/Http/Controllers/Portal/RequestController.php` | `store()` | When a citizen submits a new request (if email provided) |
| `app/Http/Controllers/Portal/RequestController.php` | `cancel()` | When a citizen cancels their pending request (if email provided) |
| `app/Http/Controllers/Admin/RequestController.php` | `update()` | When an admin updates the request status (if email provided) |

### Database

| File | Purpose |
|---|---|
| `database/migrations/2026_10_03_000001_add_email_to_requests_table.php` | Adds nullable `email` column to `requests` table |
| `app/Models/Request.php` | Updated `$fillable` to include `email` |

### Views (User-Facing)

| File | What Changed |
|---|---|
| `resources/views/public/requests/create.blade.php` | Added optional email input field |
| `resources/views/public/requests/success.blade.php` | Shows "We will notify you via SMS and email" when email is provided |
| `resources/views/admin/requests/index.blade.php` | Added Email column to admin requests table |

---

## Email Templates

### New Request Confirmation

**Subject:** `Request REQ-1234567890 — Received`

**Body:**
```
Your request (REQ-1234567890) has been received.
We will notify you once it is ready for pickup.
```

### Status Update (with fee)

**Subject:** `Request REQ-1234567890 — Status Update`

**Body:**
```
Your request (REQ-1234567890) status has been updated
to 'Ready for Pickup'. Expected fee: PHP 50.00.
```

### Status Update (without fee)

**Subject:** `Request REQ-1234567890 — Status Update`

**Body:**
```
Your request (REQ-1234567890) status has been updated
to 'Completed'.
```

### Cancellation

**Subject:** `Request REQ-1234567890 — Cancelled`

**Body:**
```
Your request (REQ-1234567890) has been cancelled.
```

---

## Troubleshooting

### Email not being sent

1. **Check your mail config** — Run `php artisan tinker` and check `config('mail.mailers.smtp.username')`. If it returns `null`, your `.env` is not configured correctly.

2. **Check the Laravel log** — Open `storage/logs/laravel.log` and search for `Email Failed`. The error message will tell you what went wrong.

3. **Common errors:**

   | Log Message | Cause | Fix |
   |---|---|---|
   | `MAIL_HOST is not configured` | Missing SMTP host in `.env` | Add Gmail SMTP settings to `.env` |
   | `Expected response code 235 but got 534` | Wrong password or App Password not used | Generate a Gmail App Password |
   | `Connection could not be established` | Port blocked or wrong host | Ensure port 587 is open and host is `smtp.gmail.com` |
   | `Username and Password not accepted` | Regular Gmail password used instead of App Password | Use a Gmail App Password, not your login password |

4. **Clear config cache** — If you changed `.env` but email still fails:
   ```bash
   php artisan config:clear
   ```

### "Less secure app access" error

Google no longer supports "Less secure app access." You **must** use an App Password. See [Setup Instructions](#1-generate-a-gmail-app-password).

### Admin sees "notifications could not be sent"

This means both SMS and email failed. Check `storage/logs/laravel.log` for detailed errors. Common causes:
- Invalid or missing SMTP credentials
- Gmail App Password revoked
- Network connectivity issues
- Requester provided an invalid email address

### Testing email locally

You can test email sending from the command line:

```bash
php artisan tinker
>>> app(\App\Services\EmailService::class)->send('test@example.com', 'Test User', 'Test Subject', 'This is a test email from Bontoc SB Portal.')
# Returns true if sent, false if failed
```

To test without actually sending emails, set `MAIL_MAILER=log` in `.env`. Emails will be written to `storage/logs/laravel.log` instead.

---

## Gmail SMTP Reference

- **SMTP Host:** `smtp.gmail.com`
- **SMTP Port:** `587` (TLS) or `465` (SSL)
- **Encryption:** TLS (recommended)
- **Authentication:** Gmail App Password (16-character)
- **Daily Send Limit:** 500 emails/day (free Gmail) or 2,000 emails/day (Google Workspace)
- **App Passwords Docs:** [https://support.google.com/accounts/answer/185833](https://support.google.com/accounts/answer/185833)

---

## Security Notes

- **Never commit** your actual `MAIL_PASSWORD` (Gmail App Password) to version control. The `.env` file is in `.gitignore`.
- Use a **Gmail App Password**, not your regular Gmail login password.
- If your App Password is compromised, revoke it immediately at [https://myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords) and generate a new one.
- Email API calls are wrapped in try-catch blocks so a Gmail/SMTP outage will not crash your application.
- The SMTP connection has a 30-second timeout to prevent requests from hanging indefinitely.
- The email field is **optional** — requesters are never forced to provide an email address.
