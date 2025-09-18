<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Orders</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="orders">

   <h1 class="heading">Placed Orders.</h1>

   <div class="box-container">

   <?php
      if($user_id == ''){
         echo '<p class="empty">please login to see your orders</p>';
      }else{
         $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ?");
         $select_orders->execute([$user_id]);
         if($select_orders->rowCount() > 0){
            echo '<div class="table-responsive">';
            echo '<table class="orders-table" style="width:100%; border-collapse:collapse; background:#fff; border: var(--border); box-shadow: var(--box-shadow);">';
            echo '<thead style="background: var(--light-bg);">'
               .'<tr>'
               .'<th style="padding:1rem; text-align:left;">Placed On</th>'
               .'<th style="padding:1rem; text-align:left;">Name</th>'
               .'<th style="padding:1rem; text-align:left;">Email</th>'
               .'<th style="padding:1rem; text-align:left;">Phone</th>'
               .'<th style="padding:1rem; text-align:left;">Address</th>'
               .'<th style="padding:1rem; text-align:left;">Method</th>'
               .'<th style="padding:1rem; text-align:left;">Items</th>'
               .'<th style="padding:1rem; text-align:left;">Total</th>'
               .'<th style="padding:1rem; text-align:left;">Status</th>'
               .'<th style="padding:1rem; text-align:left;">Actions</th>'
               .'</tr>'
            .'</thead>';
            echo '<tbody>';
            while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){
               $statusColor = $fetch_orders['payment_status'] == 'pending' ? 'red' : 'green';
               echo '<tr style="border-top: var(--border);">'
                  .'<td style="padding:1rem;">'.htmlspecialchars($fetch_orders['placed_on']).'</td>'
                  .'<td style="padding:1rem;">'.htmlspecialchars($fetch_orders['name']).'</td>'
                  .'<td style="padding:1rem;">'.htmlspecialchars($fetch_orders['email']).'</td>'
                  .'<td style="padding:1rem;">'.htmlspecialchars($fetch_orders['number']).'</td>'
                  .'<td style="padding:1rem;">'.htmlspecialchars($fetch_orders['address']).'</td>'
                  .'<td style="padding:1rem;">'.htmlspecialchars($fetch_orders['method']).'</td>'
                  .'<td style="padding:1rem;">'.htmlspecialchars($fetch_orders['total_products']).'</td>'
                  .'<td style="padding:1rem;">Nrs.'.htmlspecialchars($fetch_orders['total_price']).'/-</td>'
                  .'<td style="padding:1rem; color:'.$statusColor.'; text-transform:capitalize;">'.htmlspecialchars($fetch_orders['payment_status']).'</td>'
                  .'<td style="padding:1rem;">'
                     .'<a class="btn" style="display:inline-block; padding:.6rem 1rem; border-radius:.5rem; background:var(--main-color); color:#fff;" href="orders_receipt.php?order_id='.$fetch_orders['id'].'">View Details</a>'
                  .'</td>'
               .'</tr>';
            }
            echo '</tbody></table></div>';
         }else{
            echo '<p class="empty">no orders placed yet!</p>';
         }
      }
   ?>

   </div>

</section>


<script src="js/script.js"></script>

</body>
</html>
