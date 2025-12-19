<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Category</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="products">

   <h1 class="heading">Category</h1>

   <div class="box-container">

   <?php
     $category = $_GET['category'];
     $select_products = $conn->prepare("SELECT *, 
             CASE 
                WHEN stock_quantity IS NULL OR stock_quantity > 0 THEN 1 
                ELSE 0 
             END as in_stock
             FROM `products` WHERE name LIKE '%{$category}%'"); 
     $select_products->execute();
     if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
         $is_in_stock = $fetch_product['in_stock'];
         $stock_quantity = $fetch_product['stock_quantity'] ?? 0;

         // Calculate Discount
         $original_price = $fetch_product['price'];
         $discount = $fetch_product['discount_percentage'] ?? 0;
         $final_price = $original_price;
         if($discount > 0){
            $final_price = round($original_price - ($original_price * ($discount / 100)));
         }
   ?>
   <form action="" method="post" class="box <?= !$is_in_stock ? 'out-of-stock' : '' ?>">
      <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
      <input type="hidden" name="price" value="<?= $final_price; ?>">
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
         <div class="price">
            <?php if($discount > 0): ?>
               <span>Nrs.</span><?= $final_price; ?><span>/-</span> <span style="text-decoration: line-through; color: #999; font-size: 0.8em;">Nrs.<?= $original_price; ?></span>
            <?php else: ?>
               <span>Nrs.</span><?= $original_price; ?><span>/-</span>
            <?php endif; ?>
         </div>
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
