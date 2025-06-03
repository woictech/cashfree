<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
</head>
<body>
    <h2>❌ Payment Failed!</h2>
    <p>Order ID: <?= esc($order_id) ?></p>
    <p>Status: <?= esc($status) ?></p>
    <a href="<?= site_url('/') ?>">
        <button>Go to Homepage</button>
    </a>
</body>
</html>
