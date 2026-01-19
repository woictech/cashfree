<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="icon" type="image/png" href="<?= base_url('public/assets/logo.png') ?>" />

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
        .status-container {
            text-align: center;
        }
        .status-icon {
            font-size: 100px;
            color: <?= (isset($status) && $status == 'success') ? 'green': 'red'; ?>;
        }
        .status-text {
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="status-container">
        <div class="status-icon">❌</div>
        <p class="status-text">Payment failed</p>
        <a id="redirectBtn" href="<?= getenv('NGO_BASE_URL/donate') ?>" class="btn btn-primary mt-3">Go to Home Page</a>
    </div>
</body>
<!-- jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</html>
