# 📱 SMS Notification Feature

## Overview

The SB Session Minutes & Resolution Portal sends **SMS text notifications** to requesters via the [Semaphore](https://semaphore.co) SMS gateway API. When a citizen requests a certified copy of a document, the system automatically sends them SMS updates at key milestones:

| Event | SMS Trigger | Who Triggers It |
|---|---|---|
| **New Request Submitted** | Requester submits the form | Automatic (public portal) |
| **Status Updated** | Admin changes status (e.g., to "Ready for Pickup") | Admin panel |
| **Request Cancelled** | Requester cancels their pending request | Public portal |

---

## Prerequisites

1. **A Semaphore Account** — Sign up at [https://semaphore.co](https://semaphore.co)
2. **An API Key** — After signing up, go to your Semaphore dashboard and copy your API key
3. **SMS Credits** — Ensure your Semaphore account has sufficient SMS credits/balance

---

## Setup Instructions

### 1. Configure Environment Variables

Open your `.env` file (project root) and set the following values:

```dotenv
# Semaphore SMS API (https://semaphore.co)
SEMAPHORE_API_KEY=your_actual_api_key_here
SEMAPHORE_SENDER_NAME=BontocSB
```

| Variable | Required | Description |
|---|---|---|
| `SEMAPHORE_API_KEY` | ✅ Yes | Your Semaphore API key from the dashboard |
| `SEMAPHORE_SENDER_NAME` | No | The sender name shown on the SMS (default: `BontocSB`). Must be pre-registered with Semaphore for branded SMS. |

### 2. Clear Config Cache (if previously cached)

If you've previously run `php artisan config:cache`, clear and re-cache:

```bash
php artisan config:clear
php artisan config:cache
```

### 3. Verify Setup

Check that your config is loaded correctly:

```bash
php artisan tinker
>>> config('services.semaphore.api_key')
# Should output your API key (not null)
```

---

## How It Works

### SMS Flow

```
Citizen submits request
        │
        ▼
Portal\RequestController::store()
        │
        ├── Saves request to database
        └── Calls SmsService::send()
                │
                ▼
        SmsService normalizes phone number
                │
                ▼
        HTTP POST → Semaphore API
                │
                ▼
        SMS delivered to citizen's phone
```

### When Admin Updates Status

```
Admin changes status to "Ready for Pickup"
        │
        ▼
Admin\RequestController::update()
        │
        ├── Updates request in database
        └── Calls SmsService::send()
                │
                ▼
        Citizen receives SMS:
        "Your request (REQ-123) status has been
         updated to 'Ready for Pickup'.
         Expected fee: PHP 50.00."
```

---

## File Map — Where the Code Lives

### Core SMS Logic

| File | Purpose |
|---|---|
| `app/Services/SmsService.php` | **Main SMS service class** — handles Semaphore API calls, phone number normalization, response validation, and logging |
| `config/services.php` | Semaphore configuration (API key, sender name, API URL) read from `.env` |

### Controllers That Send SMS

| File | Method | When SMS Is Sent |
|---|---|---|
| `app/Http/Controllers/Portal/RequestController.php` | `store()` | When a citizen submits a new document request |
| `app/Http/Controllers/Portal/RequestController.php` | `cancel()` | When a citizen cancels their pending request |
| `app/Http/Controllers/Admin/RequestController.php` | `update()` | When an admin updates the request status (e.g., "Ready for Pickup") |

### Environment & Configuration

| File | Purpose |
|---|---|
| `.env` | Your actual Semaphore API key (not committed to git) |
| `.env.example` | Template showing required SMS env variables |

### Views (User-Facing)

| File | What It Shows |
|---|---|
| `resources/views/public/requests/success.blade.php` | Shows "We will notify you via SMS" confirmation |
| `resources/views/admin/requests/index.blade.php` | Admin panel with "Save & Notify" button |

---

## Phone Number Format

The system accepts Philippine mobile numbers in these formats:

| Input Format | Normalized To |
|---|---|
| `09171234567` | `09171234567` |
| `+639171234567` | `09171234567` |
| `639171234567` | `09171234567` |
| `0917-123-4567` | `09171234567` |

Phone number normalization is handled automatically by `SmsService::normalizePhoneNumber()`.

The request form validates that the phone number matches the pattern: `^(09|\+639)\d{9}$`

---

## SMS Message Templates

### New Request Confirmation
```
Bontoc SB Portal: Your request (REQ-1234567890) has been received. 
We will notify you once it is ready for pickup.
```

### Status Update (with fee)
```
Bontoc SB Portal: Your request (REQ-1234567890) status has been 
updated to 'Ready for Pickup'. Expected fee: PHP 50.00.
```

### Status Update (without fee)
```
Bontoc SB Portal: Your request (REQ-1234567890) status has been 
updated to 'Completed'.
```

### Cancellation
```
Bontoc SB Portal: Your request (REQ-1234567890) has been cancelled.
```

---

## Troubleshooting

### SMS not being sent

1. **Check your API key** — Run `php artisan tinker` and check `config('services.semaphore.api_key')`. If it returns `null`, your `.env` is not configured correctly.

2. **Check the Laravel log** — Open `storage/logs/laravel.log` and search for `SMS Failed`. The error message will tell you what went wrong.

3. **Common errors:**

   | Log Message | Cause | Fix |
   |---|---|---|
   | `SEMAPHORE_API_KEY is not set` | Missing API key in `.env` | Add your key to `.env` |
   | `Semaphore returned a non-success response` | Invalid API key or no credits | Check your Semaphore dashboard |
   | `Connection timed out` | Network issue | Check your server's internet connectivity |

4. **Clear config cache** — If you changed `.env` but SMS still fails:
   ```bash
   php artisan config:clear
   ```

### Admin sees "SMS notification could not be sent"

This means the SMS API call failed. Check `storage/logs/laravel.log` for the detailed error. Common causes:
- Invalid or expired API key
- Insufficient Semaphore credits
- Invalid phone number format
- Network connectivity issues

### Testing SMS locally

You can test SMS sending from the command line:

```bash
php artisan tinker
>>> app(\App\Services\SmsService::class)->send('09171234567', 'Test message from Bontoc SB Portal')
# Returns true if sent, false if failed
```

---

## Semaphore API Reference

- **API Docs:** [https://semaphore.co/docs](https://semaphore.co/docs)
- **API Endpoint:** `https://api.semaphore.co/api/v4/messages`
- **Method:** POST
- **Parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `apikey` | string | Yes | Your Semaphore API key |
| `number` | string | Yes | Recipient phone number |
| `message` | string | Yes | SMS message body |
| `sendername` | string | No | Registered sender name |

---

## Security Notes

- **Never commit** your actual `SEMAPHORE_API_KEY` to version control. The `.env` file is in `.gitignore`.
- The `.env.example` file contains only placeholder values.
- SMS API calls are wrapped in try-catch blocks so a Semaphore outage will not crash your application.
- The SMS service has a 15-second timeout to prevent requests from hanging indefinitely.
