<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
};

if(isset($_POST['delete'])){
   $cart_id = $_POST['cart_id'];
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE id = ?");
   $delete_cart_item->execute([$cart_id]);
}

if(isset($_GET['delete_all'])){
   $delete_cart_item = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
   $delete_cart_item->execute([$user_id]);
   header('location:cart.php');
}

if(isset($_POST['update_qty'])){
   $cart_id = $_POST['cart_id'];
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_STRING);
   $update_qty = $conn->prepare("UPDATE `cart` SET quantity = ? WHERE id = ?");
   $update_qty->execute([$qty, $cart_id]);
   $message[] = 'cart quantity updated';
}

// ADD TO CART FUNCTIONALITY FOR RECOMMENDED PRODUCTS
if(isset($_POST['add_to_cart'])){
   if($user_id == ''){
      header('location:user_login.php');
   }else{
      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $image = $_POST['image'];
      $qty = $_POST['qty'];

      $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
      $check_cart_numbers->execute([$name, $user_id]);

      if($check_cart_numbers->rowCount() > 0){
         $message[] = 'already added to cart!';
      }else{
         $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
         $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
         $message[] = 'added to cart!';
      }
   }
}

// ADD TO WISHLIST FUNCTIONALITY FOR RECOMMENDED PRODUCTS
if(isset($_POST['add_to_wishlist'])){
   if($user_id == ''){
      header('location:user_login.php');
   }else{
      $pid = $_POST['pid'];
      $name = $_POST['name'];
      $price = $_POST['price'];
      $image = $_POST['image'];

      $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
      $check_wishlist_numbers->execute([$name, $user_id]);

      $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
      $check_cart_numbers->execute([$name, $user_id]);

      if($check_wishlist_numbers->rowCount() > 0){
         $message[] = 'already added to wishlist!';
      }elseif($check_cart_numbers->rowCount() > 0){
         $message[] = 'already added to cart!';
      }else{
         $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
         $insert_wishlist->execute([$user_id, $pid, $name, $price, $image]);
         $message[] = 'added to wishlist!';
      }
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shopping Cart</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="products shopping-cart">

   <h3 class="heading">Shopping cart</h3>

   <?php
      // Count total items in cart
      $count_cart = $conn->prepare("SELECT COUNT(*) as total_items FROM `cart` WHERE user_id = ?");
      $count_cart->execute([$user_id]);
      $cart_count = $count_cart->fetch(PDO::FETCH_ASSOC)['total_items'];
   ?>

   <div class="cart-info">
      <div class="cart-summary">
         <span class="item-count"><?= $cart_count; ?> item<?= $cart_count != 1 ? 's' : ''; ?> in cart</span>
         <span class="cart-status"><?= $cart_count > 0 ? 'Ready to checkout' : 'Cart is empty'; ?></span>
      </div>
   </div>

   <div class="box-container">

   <?php
      $grand_total = 0;
      $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
      $select_cart->execute([$user_id]);
      if($select_cart->rowCount() > 0){
         while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
   ?>
   <div class="box">
      <a href="quick_view.php?pid=<?= $fetch_cart['pid']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $fetch_cart['image']; ?>" alt="">
      <div class="name"><?= $fetch_cart['name']; ?></div>
      
      <!-- Update Quantity Form -->
      <form action="" method="post" class="update-form">
         <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
         <div class="flex">
            <div class="price">Nrs.<?= $fetch_cart['price']; ?>/-</div>
            <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="<?= $fetch_cart['quantity']; ?>">
            <button type="submit" class="fas fa-edit" name="update_qty"></button>
         </div>
      </form>
      
      <div class="sub-total"> Sub Total : <span>Nrs<?= $sub_total = ($fetch_cart['price'] * $fetch_cart['quantity']); ?>/-</span> </div>
      
      <!-- Delete Form -->
      <form action="" method="post" class="delete-form">
         <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
         <input type="submit" value="delete item" onclick="return confirm('delete this from cart?');" class="delete-btn" name="delete">
      </form>
   </div>
   <?php
   $grand_total += $sub_total;
      }
   }else{
      echo '<div class="empty-cart">
               <div class="empty-cart-icon">
                  <i class="fas fa-shopping-cart"></i>
               </div>
               <h3>Your cart is empty</h3>
               <p>Looks like you haven\'t added any items to your cart yet.</p>
               <a href="shop.php" class="btn">Start Shopping</a>
            </div>';
   }
   ?>
   </div>

   <?php if($grand_total > 0): ?>
   <div class="cart-total">
      <div class="total-summary">
         <div class="total-line">
            <span>Subtotal:</span>
            <span>Nrs.<?= $grand_total; ?>/-</span>
         </div>
         <div class="total-line shipping">
            <span>Shipping:</span>
            <span>Free</span>
         </div>
         <div class="total-line final-total">
            <span>Total:</span>
            <span>Nrs.<?= $grand_total; ?>/-</span>
         </div>
      </div>
      <div class="cart-actions">
         <a href="shop.php" class="option-btn">
            <i class="fas fa-arrow-left"></i>
            Continue Shopping
         </a>
         <a href="cart.php?delete_all" class="delete-btn" onclick="return confirm('delete all from cart?');">
            <i class="fas fa-trash"></i>
            Clear Cart
         </a>
         <a href="checkout.php" class="btn">
            <i class="fas fa-credit-card"></i>
            Proceed to Checkout
         </a>
      </div>
   </div>
   <?php endif; ?>

</section>

<!-- for prd recommendation on the basis of product you added to cart !-->
<section class="products">

   <h3 class="heading">You May Also Like</h3>

   <div class="box-container">

   <?php
      // Step 1: Get all cart items of current user
      $get_cart_items = $conn->prepare("SELECT pid FROM cart WHERE user_id = ?");
      $get_cart_items->execute([$user_id]);

      $cart_product_ids = [];
      $cart_categories = [];

      while($cart_row = $get_cart_items->fetch(PDO::FETCH_ASSOC)){
         $cart_product_ids[] = $cart_row['pid'];

         // Get category for each product
         $get_cat = $conn->prepare("SELECT category FROM products WHERE id = ?");
         $get_cat->execute([$cart_row['pid']]);
         if($get_cat->rowCount() > 0){
            $cat = $get_cat->fetch(PDO::FETCH_ASSOC)['category'];
            if(!in_array($cat, $cart_categories)){
               $cart_categories[] = $cat;
            }
         }
      }

      if(!empty($cart_categories)){
         // Step 2: Get recommended products from those categories
         $category_placeholders = str_repeat('?,', count($cart_categories) - 1) . '?';

         $query = "SELECT * FROM products 
                   WHERE category IN ($category_placeholders)";

         // Step 3: Exclude already added cart items
         if(!empty($cart_product_ids)){
            $id_placeholders = str_repeat('?,', count($cart_product_ids) - 1) . '?';
            $query .= " AND id NOT IN ($id_placeholders)";
         }

         $query .= " LIMIT 6";

         $params = array_merge($cart_categories, $cart_product_ids);
         $get_recommended = $conn->prepare($query);
         $get_recommended->execute($params);

         if($get_recommended->rowCount() > 0){
            while($rec = $get_recommended->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= $rec['id']; ?>">
      <input type="hidden" name="name" value="<?= $rec['name']; ?>">
      <input type="hidden" name="price" value="<?= $rec['price']; ?>">
      <input type="hidden" name="image" value="<?= $rec['image_01']; ?>">
      <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
      <a href="quick_view.php?pid=<?= $rec['id']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $rec['image_01']; ?>" alt="">
      <div class="name"><?= $rec['name']; ?></div>
      <div class="flex">
         <div class="price"><span>Nrs.</span><?= $rec['price']; ?><span>/-</span></div>
         <input type="number" name="qty" class="qty" min="1" max="99" value="1">
      </div>
      <input type="submit" value="Add to Cart" class="btn" name="add_to_cart">
   </form>
   <?php
            }
         } else {
            echo '<p class="empty">No recommended items available yet!</p>';
         }
      } else {
         echo '<p class="empty">Add items to cart to see recommendations!</p>';
      }
   ?>
   </div>
</section>

<script src="js/script.js"></script>

</body>
</html>
