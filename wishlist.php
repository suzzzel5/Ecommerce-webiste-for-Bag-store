<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
};

include 'components/wishlist_cart.php';

if(isset($_POST['delete'])){
   $wishlist_id = $_POST['wishlist_id'];
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE id = ?");
   $delete_wishlist_item->execute([$wishlist_id]);
}

if(isset($_GET['delete_all'])){
   $delete_wishlist_item = $conn->prepare("DELETE FROM `wishlist` WHERE user_id = ?");
   $delete_wishlist_item->execute([$user_id]);
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
      $select_wishlist = $conn->prepare("SELECT w.*, p.price AS real_price, p.discount_percentage, p.stock_quantity FROM `wishlist` w LEFT JOIN `products` p ON w.pid = p.id WHERE w.user_id = ?");
      $select_wishlist->execute([$user_id]);
      if($select_wishlist->rowCount() > 0){
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
   }else{
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