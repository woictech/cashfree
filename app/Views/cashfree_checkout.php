<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Cashfree</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .checkout-card {
            max-width: 450px;
            margin: 80px auto;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background: white;
        }
    </style>
</head>
<body>

<div class="checkout-card text-center">
    <h3 class="mb-4">Confirm Your Payment</h3>

    <p><strong>Amount:</strong> ₹<?= number_format($order_amount, 2) ?></p>

    <div class="d-grid gap-2 mt-4">
        <button id="payBtn" class="btn btn-success">Pay Now</button>
        <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>

<script>
    const cashfree = Cashfree({ mode: "sandbox" });
    document.getElementById("payBtn").addEventListener("click", () => {
        cashfree.checkout({
            paymentSessionId: "<?= $session_id ?>",
            redirectTarget: "_self"
        });
    });
</script>

</body>
</html>
