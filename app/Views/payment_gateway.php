<!DOCTYPE html>
<html>
<head>
    <title>Checkout - Cashfree</title>
    <script src="https://sdk.cashfree.com/js/ui/2.0.0/dropin.min.js"></script>
</head>
<body>
    <h2>Processing Payment</h2>
    <div id="cashfree-dropin"></div>

    <script>
        // Step 1: Call your backend to create an order
        async function fetchPaymentSession() {
            const res = await fetch('https://your-backend.com/create_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    order_id: "ORDER12345",
                    amount: 499.00,
                    email: "john@example.com",
                    phone: "9876543210"
                })
            });
            const data = await res.json();
            return data.payment_session_id;
        }

        // Step 2: Render Cashfree drop-in UI
        fetchPaymentSession().then(sessionId => {
            const cashfree = Cashfree({ mode: "sandbox" }); // or "production"

            cashfree.dropin.create({
                paymentSessionId: sessionId,
                container: "#cashfree-dropin",
                style: {
                    layout: "VERTICAL",
                    color: "#2d3748",
                    fontFamily: "Arial",
                    fontSize: "14px"
                }
            });
        }).catch(err => {
            console.error("Failed to initialize payment:", err);
        });
    </script>
</body>
</html>
