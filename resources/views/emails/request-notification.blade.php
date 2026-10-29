<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #1a4b8c 0%, #2d6dc4 100%);
            padding: 28px 32px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 20px;
            margin: 0;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .email-header p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin: 6px 0 0;
        }
        .email-body {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 18px;
            color: #222;
        }
        .message-content {
            background: #f8f9fb;
            border-left: 4px solid #2d6dc4;
            padding: 16px 20px;
            border-radius: 0 6px 6px 0;
            font-size: 15px;
            line-height: 1.65;
            color: #333;
            margin-bottom: 24px;
        }
        .email-footer {
            background: #f8f9fb;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #eee;
        }
        .email-footer a {
            color: #2d6dc4;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>Bontoc SB Secretary Portal</h1>
            <p>Official Notification</p>
        </div>
        <div class="email-body">
            <p class="greeting">Dear <strong>{{ $recipientName }}</strong>,</p>
            <div class="message-content">
                {!! nl2br(e($body)) !!}
            </div>
            <p style="font-size: 14px; color: #555;">
                If you have questions, please visit the SB Secretary's Office during office hours or contact us directly.
            </p>
        </div>
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} Sangguniang Bayan ng Bontoc. All rights reserved.</p>
            <p>This is an automated notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
