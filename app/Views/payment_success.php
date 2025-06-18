<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= base_url('public/assets/logo.png') ?>" />
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
<body class="bg-light pt-3">


<div class="container d-flex justify-content-center align-items-start pt-4" style="min-height: 100vh;">

  <div class="card shadow-sm" style="max-width: 700px; width: 100%;">
    <div class="card-body">

      <!-- Header with Logo, Title, and Right Image -->
        <div class="text-center flex-grow-1">
        <p class="text-center border-bottom pb-3 mb-2">
          <span class="fw-bold">Registration No. : </span>
          <span id="registrationNumber">N/A</span>
        </p>
        </div>
    <div class="text-center flex-grow-1">
      <div class="justify-content-between align-items-center party-header border-bottom pb-3 mb-4">
         <img id="adminLogo" src="" alt="Logo" class="img-fluid">       
            <h2 id="adminName"></h2>
            <p>
          <a id="websiteLink" href="#" target="_blank"></a> |
          <a id="adminEmail" href="#"></a>
        </p>
      </div>

        
    </div>

      <!-- Receipt Info -->
      <div class="mb-4 mx-auto">
        <h5 class="border-bottom pb-2 text-center">Donation Receipt</h5>
        <div class="row">
          <div class="col-sm-6"><strong>Receipt No:</strong> <?= esc($donationId) ?></div>
          <div class="col-sm-6">
            <strong>Date:</strong>
            <?= isset($paymentData['payment_time']) ? date('d-m-Y', strtotime($paymentData['payment_time'])) : '' ?>
          </div>
          <div class="col-sm-6">
            <strong>Transaction ID:</strong> <?= esc($paymentData['bank_reference'] ?? 'N/A' ) ?>
          </div>
          <div class="col-sm-6">
            <strong>Payment Method:</strong> <?= esc(strtoupper($paymentData['payment_group'] ?? 'Offline')) ?>
          </div>
        </div>
      </div>
      <!-- Donor Info -->
      <div class="mb-4">
        <h5 class="border-bottom pb-2">Donor Information</h5>
        <div class="row">
          <div class="col-sm-6"><strong>Name:</strong> <?= esc($customerDetails['customer_name'] ?? '') ?></span></div>
          <div class="col-sm-6"><strong>Email:</strong> <?= esc($customerDetails['customer_email'] ?? '') ?></span></div>
          <div class="col-sm-6"><strong>Phone:</strong> <?= esc($customerDetails['customer_phone'] ?? '') ?></span></div>
        </div>
      </div>

      <!-- Donation Details -->
      <div class="mb-4">
        <h5 class="border-bottom pb-2">Donation Details</h5>
        <div class="row">
          <div class="col-sm-6"><strong>Purpose:</strong> Support for NGO activities</div>
          <div class="col-sm-6"><strong>Amount:</strong> ₹<?= esc($paymentData['order_amount'] ?? '') ?></span></div>
        </div>
      </div>

      <!-- Thank You Note -->
      <div class="alert alert-success mt-4" role="alert">
        Thank you for your generous contribution. Your support strengthens democracy and empowers millions of voters of India. We dont receive foreign donations.
      </div>

      <!-- Footer -->
      <div class="text-center text-muted small mt-4">
        This receipt can be used for your records or tax purposes as applicable.<br><br>
        — <span id="adminFooterName" class="fw-bold fs-6"></span><br>
        Registered Office: <span id="adminAddress" class="fw-bold fs-6"></span><br>
      </div>
    </div>
    <div class="text-center mt-4">
      <button class="btn btn-primary me-2" onclick="window.print()">🖨️ Print</button>
      <button class="btn btn-success" onclick="redirectToUserPage()">✅ Done</button>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<?php
$redirectPath = str_starts_with($customerDetails['customer_id'], 'UD') 
    ? '/user/dashboard' 
    : '/donate';
?>
<script>
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    console.log("Payment Mode:", "<?= esc($donationId) ?>");
    $(document).ready(function() {
        $.ajax({
          url: '<?= env('NGO_API_BASE_URL') ?>/admin/details',
          type: "GET",
          success: function(response) {
              if (response.status === 'success' && response.data) {
                console.log(response.data);
                  populateAdminData(response.data);
              } else {
                  console.error("Failed to fetch admin data");
              }
          },
          error: function() {
              console.error("AJAX error fetching admin data");
          }
      });

      function populateAdminData(adminData) {
        $("#adminName").text(adminData.name || 'N/A');
        // Example: update title dynamically
        document.title = `Donation Receipt - ${adminData.name || '-'}`;
        const websiteURL = '<?= env('NGO_BASE_URL') ?>';
        const displayURL = websiteURL.replace(/^https?:\/\//, ''); // strips http(s)
        console.log(websiteURL);
        $("#websiteLink")
            .attr("href", websiteURL)
            .text(displayURL);
        $("#adminEmail")
            .attr("href", "mailto:" + adminData.email)
            .text(adminData.email);
        $("#registrationNumber").text(adminData.registration_number);
        $("#adminLogo").attr("src", adminData.logo);
        $("#adminAddress").text(adminData.full_address);
        $("#adminFooterName").text(adminData.name);
        // Similarly update other admin related fields if you add placeholders in your HTML
    }

       
    });
    function redirectToUserPage() {
      const baseUrl = "<?= getenv('NGO_BASE_URL') ?>";
      window.location.href = baseUrl + "<?= $redirectPath ?>";
    }

</script>

</body>
</html>