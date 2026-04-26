<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Simple Chat Service</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; color: #333; }
        h1 { color: #4A90E2; }
        .footer { margin-top: 40px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <h1>Welcome, {{ $user->name }}!</h1>
    <p>Thank you for registering with <strong>Simple Chat Service</strong>.</p>
    <p>You can now log in and start chatting with others, create chat rooms, and send direct messages.</p>
    <p>Happy chatting!</p>
    <div class="footer">
        <p>If you did not create this account, please ignore this email.</p>
    </div>
</body>
</html>
