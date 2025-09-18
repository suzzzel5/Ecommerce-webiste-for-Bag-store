<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
   exit;
};

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch order by id and user
$select_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ? AND user_id = ? LIMIT 1");
$select_order->execute([$order_id, $user_id]);
$order = $select_order->fetch(PDO::FETCH_ASSOC);

if(!$order){
   header('location:orders.php');
   exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Order #<?= htmlspecialchars($order['id']); ?> Receipt</title>
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <style>
      .receipt-wrapper{max-width:900px;margin:2rem auto;background:#fff;border:var(--border);box-shadow:var(--box-shadow);border-radius:1rem;overflow:hidden}
      .receipt-header{display:flex;justify-content:space-between;align-items:center;background:var(--light-bg);padding:1.5rem 2rem}
      .receipt-body{padding:2rem}
      .receipt-row{display:flex;gap:2rem;flex-wrap:wrap;margin-bottom:1.5rem}
      .receipt-col{flex:1;min-width:260px}
      .receipt-title{font-size:2rem;margin-bottom:.8rem;color:var(--black)}
      .receipt-line{display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px dashed #ddd}
      .actions{display:flex;gap:1rem;padding:1rem 2rem;background:#fff;border-top:var(--border);}
      .btn-print{background:var(--main-color);color:#fff;padding:.8rem 1.2rem;border-radius:.5rem;text-decoration:none}
      .btn-back{background:#777;color:#fff;padding:.8rem 1.2rem;border-radius:.5rem;text-decoration:none}
      @media print{
         .actions, .receipt-header a{display:none}
         body{background:#fff}
         .receipt-wrapper{box-shadow:none;border:none}
      }
   </style>
</head>
<body>

<?php include 'components/user_header.php'; ?>

<div class="receipt-wrapper">
   <div class="receipt-header">
      <div>
         <h2 style="margin:0;color:var(--black);">Order Receipt</h2>
         <small>Order #<?= htmlspecialchars($order['id']); ?> • Placed on <?= htmlspecialchars($order['placed_on']); ?></small>
      </div>
      <a href="orders.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
   </div>

   <div class="receipt-body">
      <div class="receipt-row">
         <div class="receipt-col">
            <div class="receipt-title">Customer</div>
            <div class="receipt-line"><span>Name</span><span><?= htmlspecialchars($order['name']); ?></span></div>
            <div class="receipt-line"><span>Email</span><span><?= htmlspecialchars($order['email']); ?></span></div>
            <div class="receipt-line"><span>Phone</span><span><?= htmlspecialchars($order['number']); ?></span></div>
         </div>
         <div class="receipt-col">
            <div class="receipt-title">Shipping</div>
            <div class="receipt-line"><span>Address</span><span><?= htmlspecialchars($order['address']); ?></span></div>
            <div class="receipt-line"><span>Method</span><span><?= htmlspecialchars($order['method']); ?></span></div>
            <div class="receipt-line"><span>Payment Status</span><span style="color:<?= $order['payment_status']=='pending'?'red':'green' ?>; text-transform:capitalize;"><?= htmlspecialchars($order['payment_status']); ?></span></div>
         </div>
      </div>

      <div class="receipt-title">Items</div>
      <div class="receipt-line"><span>Products</span><span><?= htmlspecialchars($order['total_products']); ?></span></div>
      <div class="receipt-line" style="font-weight:700"><span>Total</span><span>Nrs.<?= htmlspecialchars($order['total_price']); ?>/-</span></div>
   </div>

   <div class="actions">
      <a href="#" class="btn-print" onclick="window.print(); return false;"><i class="fas fa-print"></i> Print / Download PDF</a>
      <a href="orders.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
   </div>
</div>

<script src="js/script.js"></script>

</body>
</html>

