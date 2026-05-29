<!-- resources/views/emails/otp.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} - OTP Verification</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            margin: 20px 0;
            letter-spacing: 5px;
            color: #667eea;
            border: 2px dashed #667eea;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #666;
            font-size: 12px;
        }
        .warning {
            color: #e74c3c;
            font-size: 14px;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>
        
        <div class="content">
            <h2>OTP Verification</h2>
            
            @if($purpose === 'registration')
                <p>Thank you for registering with {{ $appName }}! Please use the following OTP to verify your email address:</p>
            @else
                <p>You have requested to reset your password. Please use the following OTP to proceed:</p>
            @endif
            
            <div class="otp-code">
                {{ $otp }}
            </div>
            
            <p>This OTP is valid for <strong>{{ $expiryMinutes }} minutes</strong>.</p>
            
            <p>If you didn't request this, please ignore this email.</p>
            
            <div class="warning">
                <strong>⚠️ Important:</strong> Never share this OTP with anyone.
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>