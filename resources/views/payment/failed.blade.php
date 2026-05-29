<!-- resources/views/payment/failed.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #fff0f0; }
        .failed-box { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #dc3545; }
        .order-number { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 20px 0; }
        .btn { background: #007bff; color: white; padding: 10px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
        .retry-btn { background: #28a745; color: white; padding: 10px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="failed-box">
        <h1>❌ Payment Failed</h1>
        <p>Unfortunately, your payment could not be processed.</p>
        <div class="order-number">
            <strong>Order Number:</strong> {{ $order_number }}
        </div>
        <p>Reason: {{ $reason }}</p>
        <a href="/packages" class="retry-btn">Try Again</a>
        <a href="/" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>