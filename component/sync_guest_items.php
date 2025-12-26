<?php
/**
 * Sync guest cart and wishlist items from session to database
 * This function should be called after user login or registration
 */
function sync_guest_items_to_database($conn, $user_id){
   if(empty($user_id)){
      return;
   }
   
   // Sync guest cart items
   if(isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart']) && count($_SESSION['guest_cart']) > 0){
      foreach($_SESSION['guest_cart'] as $item){
         $pid = $item['pid'] ?? '';
         $name = $item['name'] ?? '';
         $price = $item['price'] ?? 0;
         $qty = $item['quantity'] ?? 1;
         $image = $item['image'] ?? '';
         
         if(!empty($pid) && !empty($name)){
            // Check if item already exists in user's cart
            $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE pid = ? AND user_id = ?");
            $check_cart->execute([$pid, $user_id]);
            
            if($check_cart->rowCount() == 0){
               // Insert into database
               $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, pid, name, price, quantity, image) VALUES(?,?,?,?,?,?)");
               $insert_cart->execute([$user_id, $pid, $name, $price, $qty, $image]);
            }
         }
      }
      // Clear session cart after syncing
      unset($_SESSION['guest_cart']);
   }
   
   // Sync guest wishlist items
   if(isset($_SESSION['guest_wishlist']) && is_array($_SESSION['guest_wishlist']) && count($_SESSION['guest_wishlist']) > 0){
      foreach($_SESSION['guest_wishlist'] as $item){
         $pid = $item['pid'] ?? '';
         $name = $item['name'] ?? '';
         $price = $item['price'] ?? 0;
         $image = $item['image'] ?? '';
         
         if(!empty($pid) && !empty($name)){
            // Check if item already exists in user's wishlist or cart
            $check_wishlist = $conn->prepare("SELECT * FROM `wishlist` WHERE pid = ? AND user_id = ?");
            $check_wishlist->execute([$pid, $user_id]);
            
            $check_cart = $conn->prepare("SELECT * FROM `cart` WHERE pid = ? AND user_id = ?");
            $check_cart->execute([$pid, $user_id]);
            
            if($check_wishlist->rowCount() == 0 && $check_cart->rowCount() == 0){
               // Insert into database
               $insert_wishlist = $conn->prepare("INSERT INTO `wishlist`(user_id, pid, name, price, image) VALUES(?,?,?,?,?)");
               $insert_wishlist->execute([$user_id, $pid, $name, $price, $image]);
            }
         }
      }
      // Clear session wishlist after syncing
      unset($_SESSION['guest_wishlist']);
   }
}

?>
