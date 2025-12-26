<?php

if(isset($_POST['add_to_wishlist'])){

   $pid = $_POST['pid'];
   $pid = filter_var($pid, FILTER_SANITIZE_STRING);
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $price = $_POST['price'];
   $price = filter_var($price, FILTER_SANITIZE_STRING);
   $image = $_POST['image'];
   $image = filter_var($image, FILTER_SANITIZE_STRING);

   if($user_id == ''){
      // Guest user - store in session
      if(!isset($_SESSION['guest_wishlist'])){
         $_SESSION['guest_wishlist'] = [];
      }
      
      // Check if already in session wishlist
      $already_in_wishlist = false;
      foreach($_SESSION['guest_wishlist'] as $item){
         if($item['pid'] == $pid || $item['name'] == $name){
            $already_in_wishlist = true;
            break;
         }
      }
      
      // Check if already in session cart
      $already_in_cart = false;
      if(isset($_SESSION['guest_cart'])){
         foreach($_SESSION['guest_cart'] as $item){
            if($item['pid'] == $pid || $item['name'] == $name){
               $already_in_cart = true;
               break;
            }
         }
      }
      
      if($already_in_wishlist){
         $message[] = 'already added to wishlist!';
      }elseif($already_in_cart){
         $message[] = 'already added to cart!';
      }else{
         $_SESSION['guest_wishlist'][] = [
            'pid' => $pid,
            'name' => $name,
            'price' => $price,
            'image' => $image
         ];
         $message[] = 'added to wishlist!';
      }
   }else{
      // Logged in user - store in database
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

if(isset($_POST['add_to_cart'])){

   $pid = $_POST['pid'];
   $pid = filter_var($pid, FILTER_SANITIZE_STRING);
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $price = $_POST['price'];
   $price = filter_var($price, FILTER_SANITIZE_STRING);
   $image = $_POST['image'];
   $image = filter_var($image, FILTER_SANITIZE_STRING);
   $qty = $_POST['qty'];
   $qty = filter_var($qty, FILTER_SANITIZE_STRING);

   // Check product stock before any cart operations
   $product_stmt = $conn->prepare("SELECT stock_quantity FROM `products` WHERE id = ?");
   $product_stmt->execute([$pid]);
   $can_add = true;
   $available_stock = null;
   if($product_stmt->rowCount() > 0){
      $product_row = $product_stmt->fetch(PDO::FETCH_ASSOC);
      $available_stock = isset($product_row['stock_quantity']) ? (int)$product_row['stock_quantity'] : null;
      if($available_stock !== null && $available_stock <= 0){
         $message[] = 'product is out of stock!';
         $can_add = false;
      } elseif($available_stock !== null && (int)$qty > $available_stock){
         $message[] = 'only '. $available_stock .' left in stock!';
         $can_add = false;
      }
   }

   if($can_add){
      if($user_id == ''){
         // Guest user - store in session
         if(!isset($_SESSION['guest_cart'])){
            $_SESSION['guest_cart'] = [];
         }
         
         // Check if already in session cart
         $already_in_cart = false;
         $cart_index = -1;
         foreach($_SESSION['guest_cart'] as $index => $item){
            if($item['pid'] == $pid || $item['name'] == $name){
               $already_in_cart = true;
               $cart_index = $index;
               break;
            }
         }
         
         if($already_in_cart){
            $message[] = 'already added to cart!';
         }else{
            // Remove from session wishlist if exists
            if(isset($_SESSION['guest_wishlist'])){
               foreach($_SESSION['guest_wishlist'] as $index => $item){
                  if($item['pid'] == $pid || $item['name'] == $name){
                     unset($_SESSION['guest_wishlist'][$index]);
                     $_SESSION['guest_wishlist'] = array_values($_SESSION['guest_wishlist']); // Re-index array
                     break;
                  }
               }
            }
            
            $_SESSION['guest_cart'][] = [
               'pid' => $pid,
               'name' => $name,
               'price' => $price,
               'quantity' => $qty,
               'image' => $image
            ];
            $message[] = 'added to cart!';
         }
      }else{
         // Logged in user - store in database
         $check_cart_numbers = $conn->prepare("SELECT * FROM `cart` WHERE name = ? AND user_id = ?");
         $check_cart_numbers->execute([$name, $user_id]);

         if($check_cart_numbers->rowCount() > 0){
            $message[] = 'already added to cart!';
         }else{

            $check_wishlist_numbers = $conn->prepare("SELECT * FROM `wishlist` WHERE name = ? AND user_id = ?");
            $check_wishlist_numbers->execute([$name, $user_id]);

            if($check_wishlist_numbers->rowCount() > 0){
               $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE name = ? AND user_id = ?");
               $delete_wishlist->execute([$name, $user_id]);
            }

            $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
            $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
            $message[] = 'added to cart!';
            
         }
      }
   }

}

?>
