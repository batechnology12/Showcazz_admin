<!-- resources/views/payment/success.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f0f8ff; }
        .success-box { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #28a745; }
        .order-number { background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 20px 0; }
        .btn { background: #007bff; color: white; padding: 10px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>✅ Payment Successful!</h1>
        <p>Thank you for your purchase. Your package has been activated.</p>
        <!-- <div class="order-number">
            <strong>Order Number:</strong> {{ $order_number }}
        </div>
       <a href="/" class="btn">Go to Dashboard</a> -->
    </div>
</body>
</html>