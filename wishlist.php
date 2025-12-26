<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   // Allow guests to view wishlist
};

include 'components/wishlist_cart.php';

if(isset($_POST['delete'])){
   if($user_id != ''){
      // Logged in user - delete from database
      $wishlist_id = $_POST['wishlist_id'];
      $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE id = ?");
      $delete_wishlist_item->execute([$wishlist_id]);
   }else{
      // Guest user - delete from session
      $wishlist_index = isset($_POST['wishlist_index']) ? (int)$_POST['wishlist_index'] : -1;
      if($wishlist_index >= 0 && isset($_SESSION['guest_wishlist'][$wishlist_index])){
         unset($_SESSION['guest_wishlist'][$wishlist_index]);
         $_SESSION['guest_wishlist'] = array_values($_SESSION['guest_wishlist']); // Re-index array
      }
   }
}

if(isset($_GET['delete_all'])){
   if($user_id != ''){
      // Logged in user - delete from database
      $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?");
      $delete_wishlist_item->execute([$user_id]);
   }else{
      // Guest user - clear session
      unset($_SESSION['guest_wishlist']);
   }
   header('location:wishlist.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Wishlist</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="products">

   <h3 class="heading">Your Wishlist.</h3>

   <div class="box-container">

   <?php
      $grand_total = 0;
      $has_items = false;
      
      if($user_id != ''){
         // Logged in user - get from database
         $select_wishlist = $conn->prepare("SELECT w.*, p.price AS real_price, p.discount_percentage, p.stock_quantity FROM `wishlist` w LEFT JOIN `products` p ON w.pid = p.id WHERE w.user_id = ?");
         $select_wishlist->execute([$user_id]);
         if($select_wishlist->rowCount() > 0){
            $has_items = true;
            while($fetch_wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC)){
               // Calculate Discount
               $original_price = $fetch_wishlist['real_price'] ?? $fetch_wishlist['price'];
               $discount = $fetch_wishlist['discount_percentage'] ?? 0;
               $final_price = $original_price;
               if($discount > 0){
                  $final_price = round($original_price - ($original_price * ($discount / 100)));
               }
               $grand_total += $final_price;
               $stock_quantity = $fetch_wishlist['stock_quantity'] ?? 99;
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= $fetch_wishlist['pid']; ?>">
      <input type="hidden" name="wishlist_id" value="<?= $fetch_wishlist['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_wishlist['name']; ?>">
      <input type="hidden" name="price" value="<?= $final_price; ?>">
      <input type="hidden" name="image" value="<?= $fetch_wishlist['image']; ?>">
      <?php if($discount > 0): ?>
         <div class="discount-badge" style="position: absolute; top: 1rem; left: 1rem; background: #e74c3c; color: white; padding: 0.5rem 1rem; font-size: 1.5rem; border-radius: .5rem; z-index: 10;"><i class="fas fa-tags"></i> -<?= $discount; ?>%</div>
      <?php endif; ?>
      <a href="quick_view.php?pid=<?= $fetch_wishlist['pid']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $fetch_wishlist['image']; ?>" alt="">
      <div class="name"><?= $fetch_wishlist['name']; ?></div>
      <div class="flex">
         <div class="price">
            <?php if($discount > 0): ?>
               <span>Nrs.</span><?= $final_price; ?><span>/-</span> <span style="text-decoration: line-through; color: #999; font-size: 0.8em;">Nrs.<?= $original_price; ?></span>
            <?php else: ?>
               <span>Nrs.</span><?= $original_price; ?><span>/-</span>
            <?php endif; ?>
         </div>
         <input type="number" name="qty" class="qty" min="1" max="<?= $stock_quantity > 0 ? $stock_quantity : 99; ?>" onkeypress="if(this.value.length == <?= strlen((string)($stock_quantity > 0 ? $stock_quantity : 99)); ?>) return false;" value="1">
      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
      <input type="submit" value="delete item" onclick="return confirm('delete this from wishlist?');" class="delete-btn" name="delete">
   </form>
   <?php
            }
         }
      }else{
         // Guest user - get from session
         if(isset($_SESSION['guest_wishlist']) && is_array($_SESSION['guest_wishlist']) && count($_SESSION['guest_wishlist']) > 0){
            $has_items = true;
            foreach($_SESSION['guest_wishlist'] as $index => $item){
               $pid = $item['pid'] ?? '';
               $name = $item['name'] ?? '';
               $price = $item['price'] ?? 0;
               $image = $item['image'] ?? '';
               
               // Get product details for discount and stock
               $original_price = $price;
               $discount = 0;
               $final_price = $price;
               $stock_quantity = 99;
               
               if(!empty($pid)){
                  $product_stmt = $conn->prepare("SELECT price, discount_percentage, stock_quantity FROM `products` WHERE id = ?");
                  $product_stmt->execute([$pid]);
                  if($product_stmt->rowCount() > 0){
                     $product_row = $product_stmt->fetch(PDO::FETCH_ASSOC);
                     $original_price = $product_row['price'] ?? $price;
                     $discount = $product_row['discount_percentage'] ?? 0;
                     if($discount > 0){
                        $final_price = round($original_price - ($original_price * ($discount / 100)));
                     }else{
                        $final_price = $original_price;
                     }
                     $stock_quantity = isset($product_row['stock_quantity']) && $product_row['stock_quantity'] !== null
                        ? (int)$product_row['stock_quantity']
                        : 99;
                  }
               }
               
               $grand_total += $final_price;
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= $pid; ?>">
      <input type="hidden" name="wishlist_index" value="<?= $index; ?>">
      <input type="hidden" name="name" value="<?= $name; ?>">
      <input type="hidden" name="price" value="<?= $final_price; ?>">
      <input type="hidden" name="image" value="<?= $image; ?>">
      <?php if($discount > 0): ?>
         <div class="discount-badge" style="position: absolute; top: 1rem; left: 1rem; background: #e74c3c; color: white; padding: 0.5rem 1rem; font-size: 1.5rem; border-radius: .5rem; z-index: 10;"><i class="fas fa-tags"></i> -<?= $discount; ?>%</div>
      <?php endif; ?>
      <a href="quick_view.php?pid=<?= $pid; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $image; ?>" alt="">
      <div class="name"><?= $name; ?></div>
      <div class="flex">
         <div class="price">
            <?php if($discount > 0): ?>
               <span>Nrs.</span><?= $final_price; ?><span>/-</span> <span style="text-decoration: line-through; color: #999; font-size: 0.8em;">Nrs.<?= $original_price; ?></span>
            <?php else: ?>
               <span>Nrs.</span><?= $original_price; ?><span>/-</span>
            <?php endif; ?>
         </div>
         <input type="number" name="qty" class="qty" min="1" max="<?= $stock_quantity > 0 ? $stock_quantity : 99; ?>" onkeypress="if(this.value.length == <?= strlen((string)($stock_quantity > 0 ? $stock_quantity : 99)); ?>) return false;" value="1">
      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
      <input type="submit" value="delete item" onclick="return confirm('delete this from wishlist?');" class="delete-btn" name="delete">
   </form>
   <?php
            }
         }
      }
      
      if(!$has_items){
         echo '<p class="empty">your wishlist is empty</p>';
      }
   ?>
   </div>

   <div class="wishlist-total">
      <p>Grand Total : <span>Nrs.<?= $grand_total; ?>/-</span></p>
      <a href="shop.php" class="option-btn">Continue Shopping.</a>
      <a href="wishlist.php?delete_all" class="delete-btn <?= ($grand_total > 1)?'':'disabled'; ?>" onclick="return confirm('delete all from wishlist?');">delete all item</a>
   </div>

</section>

 
<script src="js/script.js"></script>

</body>
</html>
