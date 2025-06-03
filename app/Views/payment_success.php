<!-- <!DOCTYPE html>
<html>
<head>
    <title>Payment Success</title>
</head>
<body>
    <h2>✅ Payment Successful!</h2>
    <p>Order ID: <?= esc($order_id) ?></p>
    <p>Amount Paid: ₹<?= esc($amount) ?></p>
    <a href="<?= site_url('/') ?>">
        <button>Go to Homepage</button>
    </a>
</body>
</html> -->


<?php 
    // Extract donationId from $data if available
    $donationId = isset($donationId) ? $donationId : (isset($data['donationId']) ? $data['donationId'] : '');
    $trackingId = isset($trackingId) ? $trackingId : (isset($data['trackingId']) ? $data['trackingId'] : '');
    $paymentMode = isset($paymentMode) ? $paymentMode : (isset($data['paymentMode']) ? $data['paymentMode'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Donation Receipt - Voters Party International</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    html, body {
      height: 100%;
    }
    .party-header img {
      max-height: 60px;
    }
    .party-header h2 {
      margin: 0;
      font-size: 1.8rem;
      color: #004080;
    }
    .party-header p {
      margin: 0;
      font-size: 0.95rem;
      color: #666;
    }
    @media print {
    .no-print {
      display: none !important;
    }
  }
  </style>
</head>
<body class="bg-light d-flex align-items-center justify-content-center pt-5">

<div class="container d-flex justify-content-center align-items-start min-vh-100 pt-5">
  <div class="card shadow-sm" style="max-width: 700px; width: 100%;">
    <div class="card-body">

      <!-- Header with Logo, Title, and Right Image -->
        <div class="text-center flex-grow-1">
          <p class="text-center border-bottom pb-3 mb-2">
            <span class="fw-bold">Registration No. : </span>56/100/2012-16/PPS-I
          </p>
</div>
<div class="text-center flex-grow-1">
 <div class="justify-content-between align-items-center party-header border-bottom pb-3 mb-4">
        <img src="<?= base_url('assets/logo.png') ?>" alt="Logo" class="img-fluid">          
<h2>Voters Party International</h2>
          <p>Uniting Voters, Empowering Democracy</p>
          <p>
            <a href="https://www.votersparty.in">www.votersparty.in</a> |
            <a href="mailto:vpimedia.central@gmail.com">vpimedia.central@gmail.com</a>
          </p>
        </div>

        
      </div>

      <!-- Receipt Info -->
      <div class="mb-4 mx-auto">
        <h5 class="border-bottom pb-2 text-center">Donation Receipt</h5>
        <div class="row">
          <div class="col-sm-6"><strong>Receipt No:</strong><span id="receiptNo"></div>
          <div class="col-sm-6"><strong>Date:</strong> <span id="date"></div>
          <div class="col-sm-6">
            <strong>Transaction ID:</strong> <?= isset($trackingId) ? ($trackingId) : '-' ?>
          </div>
          <div class="col-sm-6">
            <strong>Payment Method:</strong> <?= isset($paymentMode) ? ($paymentMode) : '-' ?>
          </div>
        </div>
      </div>
      <!-- Donor Info -->
      <div class="mb-4">
        <h5 class="border-bottom pb-2">Donor Information</h5>
        <div class="row">
          <div class="col-sm-6"><strong>Name:</strong> <span id="donorName"></span></div>
          <div class="col-sm-6"><strong>Email:</strong> <span id="donorEmail"></span></div>
          <div class="col-sm-6"><strong>Phone:</strong> <span id="donorPhone"></span></div>
        </div>
      </div>

      <!-- Donation Details -->
      <div class="mb-4">
        <h5 class="border-bottom pb-2">Donation Details</h5>
        <div class="row">
          <div class="col-sm-6"><strong>Purpose:</strong> Support for party activities</div>
          <div class="col-sm-6"><strong>Amount:</strong> ₹<span id="donationAmount"></span></div>
        </div>
      </div>

      <!-- Thank You Note -->
      <div class="alert alert-success mt-4" role="alert">
        Thank you for your generous contribution. Your support strengthens democracy and empowers millions of voters of India. We dont receive foreign donations.
      </div>

      <!-- Footer -->
      <div class="text-center text-muted small mt-4">
        This receipt can be used for your records or tax purposes as applicable.<br><br>
        — Voters Party International<br>
        Registered Office: 385, Lane No. 12/A, Wazirabad Village, Delhi-110084<br>
      </div>
    </div>
    <div class="text-center mt-4">
      <button class="btn btn-primary me-2" onclick="window.print()">🖨️ Print</button>
      <button class="btn btn-success" onclick="redirectToCCAvenueStatus()">✅ Done</button>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    console.log("Payment Mode:", "<?= isset($data['donationId']) ? esc($data['donationId']) : 'Not Available' ?>");
    $(document).ready(function() {
        const donationId = "<?= esc($donationId) ?>";
        const formData = new FormData();
        formData.append('id', donationId);
        const ajaxOptions = {
            url: "<?= base_url('getDonation') ?>",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success' && response.data) {
                  console.log(response.data);
                    populateDonationData(response.data);
                } else {
                    console.error("Invalid response:", response.msg);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
            }
        };

        if (donationId.startsWith("D-")) {
            // Public donation – no auth required
            $.ajax(ajaxOptions);
        } else if (donationId.startsWith("UD-")) {
            // Authenticated donation – add token
            const token = getCookie('authToken');
            ajaxOptions.headers = {
                'Authorization': `Bearer ${token}`
            };
            $.ajax(ajaxOptions);
        }

        function populateDonationData(data) {
            $("#donorName").text(data.name || 'N/A');
            $("#donorEmail").text(data.email || 'N/A');
            $("#donorPhone").text(data.mobile || 'N/A');
            $("#donationAmount").text(data.amount || '0');
            $("#receiptNo").text(data.receipt_no || 'N/A');
            $("#date").text(data.date || 'N/A');
        }
    });
    function redirectToCCAvenueStatus() {
      // If you need to pass donationId, append it as a query param
      const donationId = "<?= esc($donationId) ?>";
      window.location.href = "<?= base_url('ccavenueStatusPage') ?>" + (donationId ? `?donationId=${donationId}` : "");
    }
</script>

</body>
</html>