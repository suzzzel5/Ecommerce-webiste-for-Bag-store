<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

// Handle sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shop</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="products">

   <h1 class="heading">Latest Products.</h1>

   <!-- Sort dropdown -->
   <div class="sort-container" style="text-align: center; margin-bottom: 30px; padding: 20px 0;">
      <form method="GET" action="" style="display: inline-block;">
         <div style="position: relative; display: inline-block;">
            <i class="fas fa-sort" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #666; z-index: 1;"></i>
            <select name="sort" onchange="this.form.submit()" style="
               padding: 12px 40px 12px 35px; 
               border: 2px solid #e0e0e0; 
               border-radius: 25px; 
               font-size: 14px; 
               background: white; 
               color: #333; 
               cursor: pointer; 
               outline: none; 
               transition: all 0.3s ease; 
               box-shadow: 0 2px 5px rgba(0,0,0,0.1);
               min-width: 200px;
               appearance: none;
               -webkit-appearance: none;
               -moz-appearance: none;
               background-image: url('data:image/svg+xml;utf8,<svg fill="%23666" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
               background-repeat: no-repeat;
               background-position: right 10px center;
               background-size: 20px;
            " onmouseover="this.style.borderColor='#007bff'; this.style.boxShadow='0 4px 8px rgba(0,123,255,0.2)'" 
               onmouseout="this.style.borderColor='#e0e0e0'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)'">
               <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>🕒 Latest Products</option>
               <option value="high_to_low" <?= $sort == 'high_to_low' ? 'selected' : '' ?>>💰 Price: High to Low</option>
               <option value="low_to_high" <?= $sort == 'low_to_high' ? 'selected' : '' ?>>💸 Price: Low to High</option>
               <option value="a_to_z" <?= $sort == 'a_to_z' ? 'selected' : '' ?>>📝 Name: A to Z</option>
            </select>
         </div>
      </form>
   </div>

   <div class="box-container">

   <?php
     // Build the SQL query based on sorting
     $sql = "SELECT *, 
             CASE 
                WHEN stock_quantity IS NULL OR stock_quantity > 0 THEN 1 
                ELSE 0 
             END as in_stock
             FROM `products`";
     
     switch($sort) {
         case 'high_to_low':
             $sql .= " ORDER BY price DESC";
             break;
         case 'low_to_high':
             $sql .= " ORDER BY price ASC";
             break;
         case 'a_to_z':
             $sql .= " ORDER BY name ASC";
             break;
         case 'latest':
         default:
             $sql .= " ORDER BY id DESC";
             break;
     }
     
     $select_products = $conn->prepare($sql); 
     $select_products->execute();
     if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
         $is_in_stock = $fetch_product['in_stock'];
         $stock_quantity = $fetch_product['stock_quantity'] ?? 0;
   ?>
   <form action="" method="post" class="box <?= !$is_in_stock ? 'out-of-stock' : '' ?>">
      <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
      <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
      <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
      
      <!-- Stock Status Badge -->
      <?php if(!$is_in_stock): ?>
         <div class="stock-badge out-of-stock-badge">
            <i class="fas fa-times-circle"></i>
            Out of Stock
         </div>
      <?php elseif($stock_quantity <= 5 && $stock_quantity > 0): ?>
         <div class="stock-badge low-stock-badge">
            <i class="fas fa-exclamation-triangle"></i>
            Only <?= $stock_quantity; ?> left!
         </div>
      <?php endif; ?>
      
      <button class="fas fa-heart" type="submit" name="add_to_wishlist" <?= !$is_in_stock ? 'disabled' : '' ?>></button>
      <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="" class="<?= !$is_in_stock ? 'out-of-stock-img' : '' ?>">
      <div class="name"><?= $fetch_product['name']; ?></div>
      
      <!-- Stock Information -->
      <div class="stock-info">
         <?php if($is_in_stock): ?>
            <span class="in-stock">
               <i class="fas fa-check-circle"></i>
               In Stock
               <?php if($stock_quantity > 0): ?>
                  (<?= $stock_quantity; ?> available)
               <?php endif; ?>
            </span>
         <?php else: ?>
            <span class="out-of-stock-text">
               <i class="fas fa-times-circle"></i>
               Out of Stock
            </span>
         <?php endif; ?>
      </div>
      
      <div class="flex">
         <div class="price"><span>Nrs.</span><?= $fetch_product['price']; ?><span>/-</span></div>
         <input type="number" name="qty" class="qty" min="1" max="<?= min(99, $stock_quantity); ?>" onkeypress="if(this.value.length == 2) return false;" value="1" <?= !$is_in_stock ? 'disabled' : '' ?>>
      </div>
      
      <?php if($is_in_stock): ?>
         <input type="submit" value="add to cart" class="btn" name="add_to_cart">
      <?php else: ?>
         <button type="button" class="btn out-of-stock-btn" disabled>
            <i class="fas fa-ban"></i>
            Out of Stock
         </button>
         <button type="button" class="notify-btn" onclick="notifyWhenAvailable(<?= $fetch_product['id']; ?>, '<?= $fetch_product['name']; ?>')">
            <i class="fas fa-bell"></i>
            Notify When Available
         </button>
      <?php endif; ?>
   </form>
   <?php
      }
   }else{
      echo '<p class="empty">no products found!</p>';
   }
   ?>

   </div>

</section>



<script src="js/script.js"></script>

<script>
// Notify When Available Functionality
function notifyWhenAvailable(productId, productName) {
   if(confirm(`Would you like to be notified when "${productName}" is back in stock?`)) {
      // Here you can implement the notification system
      // For now, we'll just show a success message
      alert('You will be notified when this product is back in stock!');
      
      // You can add AJAX call here to save the notification request
      // Example:
      // fetch('notify_back_in_stock.php', {
      //    method: 'POST',
      //    headers: {'Content-Type': 'application/json'},
      //    body: JSON.stringify({product_id: productId, user_id: <?= $user_id; ?>})
      // });
   }
}

// Add visual feedback for stock status
document.addEventListener('DOMContentLoaded', function() {
   const outOfStockBoxes = document.querySelectorAll('.box.out-of-stock');
   
   outOfStockBoxes.forEach(box => {
      // Add hover effect to show it's unavailable
      box.addEventListener('mouseenter', function() {
         this.style.cursor = 'not-allowed';
      });
      
      // Disable all form submissions for out of stock items
      const forms = box.querySelectorAll('form');
      forms.forEach(form => {
         form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('This product is currently out of stock!');
         });
      });
   });
});
</script>

</body>
</html>
