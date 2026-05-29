<!-- resources/views/emails/reset-password.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }} - Password Reset</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            background: #fff;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .code-container {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            margin: 25px 0;
            border: 2px dashed #667eea;
        }
        .verification-code {
            font-size: 48px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #667eea;
            font-family: 'Courier New', monospace;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        .expiry {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            text-align: center;
        }
        .warning {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        .warning strong {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            color: #666;
            font-size: 13px;
            border-top: 1px solid #eee;
        }
        .footer p {
            margin: 5px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
                width: auto;
            }
            .verification-code {
                font-size: 36px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $appName }}</h1>
        </div>
        
        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $userName }}</strong>,
            </div>
            
            <p>We received a request to reset the password for your account ({{ $userType }}).</p>
            
            <p>Please use the following verification code to reset your password:</p>
            
            <div class="code-container">
                <div class="verification-code">{{ $code }}</div>
            </div>
            
            <div class="expiry">
                ⏰ This code will expire in <strong>{{ $expiryMinutes }} minutes</strong>
            </div>
            
            <div class="warning">
                <strong>⚠️ Security Alert</strong>
                Never share this code with anyone. Our team will never ask for this code.
            </div>
            
            <p>If you didn't request a password reset, please ignore this email or contact support if you have concerns.</p>
            
            <p>For your security, this code can only be used once and will expire after {{ $expiryMinutes }} minutes.</p>
            
            <center>
                <a href="#" class="btn">Reset Password</a>
            </center>
        </div>
        
        <div class="footer">
            <p>&copy; {{ $year }} . All rights reserved.</p>
            <p>This is an automated message, please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>