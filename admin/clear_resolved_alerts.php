<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
   exit();
}

// Clear all resolved alerts (where stock is now sufficient)
$clear_resolved = $conn->prepare("
   UPDATE `stock_alerts` sa 
   JOIN `products` p ON sa.product_id = p.id 
   SET sa.is_read = 1 
   WHERE p.stock_quantity > p.min_stock_level 
   AND sa.alert_type IN ('low_stock', 'out_of_stock')
");

$clear_resolved->execute();
$affected_rows = $clear_resolved->rowCount();

$message = "Cleared $affected_rows resolved alerts!";

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Clear Resolved Alerts</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="clear-alerts">

   <div class="message">
      <span><?= $message; ?></span>
   </div>

   <div style="text-align: center; margin: 50px 0;">
      <h2>Alerts Cleared Successfully!</h2>
      <p>All resolved stock alerts have been cleared.</p>
      <a href="stock_alerts.php" class="btn">Go Back to Stock Alerts</a>
   </div>

</section>

<script src="../js/admin_script.js"></script>
   
</body>
</html>
