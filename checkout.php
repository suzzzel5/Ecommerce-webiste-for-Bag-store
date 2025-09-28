<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:user_login.php');
   exit();
}

// CSRF Protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      $message[] = 'Invalid request!';
   }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
   $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Prefill user details from profile for the checkout form
$prefill = [
   'name' => '',
   'email' => '',
   'number' => '',
   'flat' => '',
   'street' => '',
   'city' => '',
   'state' => '',
   'country' => '',
   'pin_code' => ''
];

// Always prefill name and email
$select_basic_profile = $conn->prepare("SELECT name, email FROM `users` WHERE id = ?");
if ($select_basic_profile && $select_basic_profile->execute([$user_id])) {
   if ($select_basic_profile->rowCount() > 0) {
      $basic_profile = $select_basic_profile->fetch(PDO::FETCH_ASSOC);
      $prefill['name'] = $basic_profile['name'] ?? '';
      $prefill['email'] = $basic_profile['email'] ?? '';
   }
}

// Try to also prefill phone and address if available
try {
   $select_extended_profile = $conn->prepare("SELECT phone, address FROM `users` WHERE id = ?");
   if ($select_extended_profile && $select_extended_profile->execute([$user_id])) {
      if ($select_extended_profile->rowCount() > 0) {
         $extended_profile = $select_extended_profile->fetch(PDO::FETCH_ASSOC);
         $prefill['number'] = preg_replace('/\\D+/', '', $extended_profile['phone'] ?? '');
         if (strlen($prefill['number']) > 10) { $prefill['number'] = substr($prefill['number'], 0, 10); }

         $saved_address = trim($extended_profile['address'] ?? '');
         if ($saved_address !== '') {
            $parts = array_map('trim', explode(',', $saved_address));
            if (!empty($parts[0])) { $prefill['flat'] = $parts[0]; }
            if (!empty($parts[1])) { $prefill['street'] = $parts[1]; }
            if (!empty($parts[2])) { $prefill['city'] = $parts[2]; }
            if (!empty($parts[3])) { $prefill['state'] = $parts[3]; }
            if (!empty($parts[4])) {
               if (preg_match('/^(.*)\s*-\s*(\\d{5,6})$/', $parts[4], $m)) {
                  $prefill['country'] = trim($m[1]);
                  $prefill['pin_code'] = $m[2];
               } else {
                  $prefill['country'] = $parts[4];
               }
            }
         }
      }
   }
} catch (Exception $e) {
   // Ignore prefill errors in rendering
}

if(isset($_POST['order'])){

   $errors = [];
   $success = false;

   // Validate and sanitize name
   $name = trim($_POST['name'] ?? '');
   if (empty($name)) {
      $errors[] = 'Name is required';
   } elseif (strlen($name) < 2 || strlen($name) > 50) {
      $errors[] = 'Name must be between 2 and 50 characters';
   } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
      $errors[] = 'Name can only contain letters and spaces';
   }

   // Validate and sanitize phone number
   $number = trim($_POST['number'] ?? '');
   if (empty($number)) {
      $errors[] = 'Phone number is required';
   } elseif (!preg_match("/^[0-9]{10}$/", $number)) {
      $errors[] = 'Phone number must be exactly 10 digits';
   }

   // Validate and sanitize email
   $email = trim($_POST['email'] ?? '');
   if (empty($email)) {
      $errors[] = 'Email is required';
   } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'Please enter a valid email address';
   } elseif (strlen($email) > 100) {
      $errors[] = 'Email is too long';
   }

   // Validate payment method
   $method = trim($_POST['method'] ?? '');
   $allowed_methods = ['cash on delivery', 'paytm', 'khalti'];
   if (empty($method)) {
      $errors[] = 'Payment method is required';
   } elseif (!in_array($method, $allowed_methods)) {
      $errors[] = 'Invalid payment method selected';
   }

   // Validate address fields
   $flat = trim($_POST['flat'] ?? '');
   $street = trim($_POST['street'] ?? '');
   $city = trim($_POST['city'] ?? '');
   $state = trim($_POST['state'] ?? '');
   $country = trim($_POST['country'] ?? '');
   $pin_code = trim($_POST['pin_code'] ?? '');

   if (empty($flat)) {
      $errors[] = 'Flat/Address line 1 is required';
   } elseif (strlen($flat) > 50) {
      $errors[] = 'Flat/Address line 1 is too long';
   }

   if (empty($street)) {
      $errors[] = 'Street name is required';
   } elseif (strlen($street) > 50) {
      $errors[] = 'Street name is too long';
   }

   if (empty($city)) {
      $errors[] = 'City is required';
   } elseif (strlen($city) > 50) {
      $errors[] = 'City name is too long';
   }

   if (empty($state)) {
      $errors[] = 'Province is required';
   } elseif (strlen($state) > 50) {
      $errors[] = 'Province name is too long';
   }

   if (empty($country)) {
      $errors[] = 'Country is required';
   } elseif (strlen($country) > 50) {
      $errors[] = 'Country name is too long';
   }

   if (empty($pin_code)) {
      $errors[] = 'ZIP code is required';
   } elseif (!preg_match("/^[0-9]{5,6}$/", $pin_code)) {
      $errors[] = 'ZIP code must be 5-6 digits';
   }

   // Validate total products and price
   $total_products = $_POST['total_products'] ?? '';
   $total_price = $_POST['total_price'] ?? '';

   if (empty($total_products)) {
      $errors[] = 'Cart is empty';
   }

   if (empty($total_price) || !is_numeric($total_price) || $total_price <= 0) {
      $errors[] = 'Invalid total price';
   }

   // Build address string
   $address = 'Flat no. ' . htmlspecialchars($flat) . ', ' . 
              htmlspecialchars($street) . ', ' . 
              htmlspecialchars($city) . ', ' . 
              htmlspecialchars($state) . ', ' . 
              htmlspecialchars($country) . ' - ' . 
              htmlspecialchars($pin_code);

   // Verify cart integrity and stock availability
   $check_cart = $conn->prepare("SELECT c.*, p.stock_quantity, p.stock_status FROM `cart` c JOIN `products` p ON c.pid = p.id WHERE c.user_id = ?");
   $check_cart->execute([$user_id]);

   if($check_cart->rowCount() == 0){
      $errors[] = 'Your cart is empty';
   } else {
      // Verify cart total matches submitted total and check stock
      $cart_total = 0;
      $cart_items = [];
      $insufficient_stock_items = [];
      
      while($fetch_cart = $check_cart->fetch(PDO::FETCH_ASSOC)){
         $cart_items[] = $fetch_cart['name'].' ('.$fetch_cart['price'].' x '. $fetch_cart['quantity'].') - ';
         $cart_total += ($fetch_cart['price'] * $fetch_cart['quantity']);
         
         // Check stock availability
         if($fetch_cart['quantity'] > $fetch_cart['stock_quantity']) {
            $insufficient_stock_items[] = $fetch_cart['name'] . ' (Available: ' . $fetch_cart['stock_quantity'] . ', Requested: ' . $fetch_cart['quantity'] . ')';
         }
      }
      
      if(abs($cart_total - $total_price) > 0.01) { // Allow for small floating point differences
         $errors[] = 'Cart total mismatch detected';
      }
      
      if(!empty($insufficient_stock_items)) {
         $errors[] = 'Insufficient stock for: ' . implode(', ', $insufficient_stock_items);
      }
   }

   // If no errors, proceed with order placement
   if (empty($errors)) {
      // Handle Khalti payment
      if ($method === 'khalti') {
         // Store order data in session for Khalti payment
         $_SESSION['khalti_order'] = [
            'user_id' => $user_id,
            'name' => $name,
            'number' => $number,
            'email' => $email,
            'address' => $address,
            'total_products' => $total_products,
            'total_price' => $total_price,
            'cart_items' => $cart_items
         ];
         
         // Redirect to Khalti payment
         header('Location: khalti/process-khalti-payment.php');
         exit();
      }
      
      // Handle cash on delivery
      try {
            // Begin transaction
            $conn->beginTransaction();

            $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on) VALUES(?,?,?,?,?,?,?,?,NOW())");
            $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $total_price]);

            // Update stock levels and log stock history
            $update_cart = $conn->prepare("SELECT c.*, p.stock_quantity, p.min_stock_level FROM `cart` c JOIN `products` p ON c.pid = p.id WHERE c.user_id = ?");
            $update_cart->execute([$user_id]);
            
            while($cart_item = $update_cart->fetch(PDO::FETCH_ASSOC)) {
               $new_stock = $cart_item['stock_quantity'] - $cart_item['quantity'];
               
               // Update stock status
               $stock_status = 'out_of_stock';
               if($new_stock > $cart_item['min_stock_level']) {
                  $stock_status = 'in_stock';
               } elseif($new_stock > 0) {
                  $stock_status = 'low_stock';
               }
               
               // Update product stock
               $update_product_stock = $conn->prepare("UPDATE `products` SET stock_quantity = ?, stock_status = ? WHERE id = ?");
               $update_product_stock->execute([$new_stock, $stock_status, $cart_item['pid']]);
               
               // Log stock history for sale
               $insert_stock_history = $conn->prepare("INSERT INTO `stock_history`(product_id, action_type, quantity_change, previous_stock, new_stock, notes, admin_id) VALUES(?,?,?,?,?,?,?)");
               $insert_stock_history->execute([$cart_item['pid'], 'sale', -$cart_item['quantity'], $cart_item['stock_quantity'], $new_stock, 'Order placed by user', null]);
               
               // Create stock alerts if needed
               if($new_stock <= $cart_item['min_stock_level'] && $new_stock > 0) {
                  $check_alert = $conn->prepare("SELECT id FROM `stock_alerts` WHERE product_id = ? AND alert_type = 'low_stock' AND is_read = 0");
                  $check_alert->execute([$cart_item['pid']]);
                  if($check_alert->rowCount() == 0) {
                     $insert_alert = $conn->prepare("INSERT INTO `stock_alerts`(product_id, alert_type, message) VALUES(?,?,?)");
                     $insert_alert->execute([$cart_item['pid'], 'low_stock', 'Product is running low on stock. Current stock: ' . $new_stock]);
                  }
               } elseif($new_stock == 0) {
                  $check_alert = $conn->prepare("SELECT id FROM `stock_alerts` WHERE product_id = ? AND alert_type = 'out_of_stock' AND is_read = 0");
                  $check_alert->execute([$cart_item['pid']]);
                  if($check_alert->rowCount() == 0) {
                     $insert_alert = $conn->prepare("INSERT INTO `stock_alerts`(product_id, alert_type, message) VALUES(?,?,?)");
                     $insert_alert->execute([$cart_item['pid'], 'out_of_stock', 'Product is out of stock!']);
                  }
               }
            }

            $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
            $delete_cart->execute([$user_id]);

            // Commit transaction
            $conn->commit();

            $message[] = 'Order placed successfully!';
            $success = true;

            // Clear cart items from session if any
            if (isset($_SESSION['cart_count'])) {
               unset($_SESSION['cart_count']);
            }

         } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message[] = 'An error occurred while placing your order. Please try again.';
            error_log("Order placement error: " . $e->getMessage());
         }
   } else {
      // Display validation errors
      foreach ($errors as $error) {
         $message[] = $error;
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
   <title>checkout</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="checkout-orders">

   <?php
   if(isset($_SESSION['error'])){
      echo '<div class="message"><span>'.$_SESSION['error'].'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
      unset($_SESSION['error']);
   }
   if(isset($_SESSION['success'])){
      echo '<div class="message"><span>'.$_SESSION['success'].'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
      unset($_SESSION['success']);
   }
   ?>

   <form action="" method="POST">

   <h3>Your Orders</h3>

      <div class="display-orders">
      <?php
         $grand_total = 0;
         $cart_items = [];
         $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
         $select_cart->execute([$user_id]);
         if($select_cart->rowCount() > 0){
            while($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)){
               $cart_items[] = $fetch_cart['name'].' ('.$fetch_cart['price'].' x '. $fetch_cart['quantity'].') - ';
               $total_products = implode($cart_items);
               $grand_total += ($fetch_cart['price'] * $fetch_cart['quantity']);
      ?>
         <p> <?= htmlspecialchars($fetch_cart['name']); ?> <span>(<?= 'Rs'.htmlspecialchars($fetch_cart['price']).'/- x '. htmlspecialchars($fetch_cart['quantity']); ?>)</span> </p>
      <?php
            }
         }else{
            echo '<p class="empty">your cart is empty!</p>';
         }
      ?>
         <input type="hidden" name="total_products" value="<?= htmlspecialchars($total_products ?? ''); ?>">
         <input type="hidden" name="total_price" value="<?= htmlspecialchars($grand_total); ?>">
         <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
         <div class="grand-total">Grand Total : <span>Nrs.<?= htmlspecialchars($grand_total); ?>/-</span></div>
      </div>

      <h3>place your orders</h3>

      <div class="flex">
         <div class="inputBox">
            <span>Your Name :</span>
            <input type="text" name="name" placeholder="enter your name" class="box" maxlength="50" value="<?= htmlspecialchars($_POST['name'] ?? ($prefill['name'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>Your Number :</span>
            <input type="tel" name="number" placeholder="enter your number" class="box" pattern="[0-9]{10}" maxlength="10" value="<?= htmlspecialchars($_POST['number'] ?? ($prefill['number'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>Your Email :</span>
            <input type="email" name="email" placeholder="enter your email" class="box" maxlength="100" value="<?= htmlspecialchars($_POST['email'] ?? ($prefill['email'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>Payment method :</span>
            <select name="method" class="box" required>
               <option value="">Select payment method</option>
               <option value="cash on delivery" <?= (isset($_POST['method']) && $_POST['method'] == 'cash on delivery') ? 'selected' : ''; ?>>Cash On Delivery</option>
               <option value="khalti" <?= (isset($_POST['method']) && $_POST['method'] == 'khalti') ? 'selected' : ''; ?>>Khalti Digital Wallet</option>
            </select>
         </div>
         <div class="inputBox">
            <span>Address line 01 :</span>
            <input type="text" name="flat" placeholder="e.g. Flat number" class="box" maxlength="50" value="<?= htmlspecialchars($_POST['flat'] ?? ($prefill['flat'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>Address line 02 :</span>
            <input type="text" name="street" placeholder="Street name" class="box" maxlength="50" value="<?= htmlspecialchars($_POST['street'] ?? ($prefill['street'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>City :</span>
            <input type="text" name="city" placeholder="Kathmandu" class="box" maxlength="50" value="<?= htmlspecialchars($_POST['city'] ?? ($prefill['city'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>Province:</span>
            <input type="text" name="state" placeholder="Bagmati" class="box" maxlength="50" value="<?= htmlspecialchars($_POST['state'] ?? ($prefill['state'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>Country :</span>
            <input type="text" name="country" placeholder="Nepal" class="box" maxlength="50" value="<?= htmlspecialchars($_POST['country'] ?? ($prefill['country'] ?? '')); ?>" required>
         </div>
         <div class="inputBox">
            <span>ZIP CODE :</span>
            <input type="text" name="pin_code" placeholder="e.g. 56400" pattern="[0-9]{5,6}" maxlength="6" value="<?= htmlspecialchars($_POST['pin_code'] ?? ($prefill['pin_code'] ?? '')); ?>" class="box" required>
         </div>
      </div>

      <input type="submit" name="order" class="btn <?= ($grand_total > 1)?'':'disabled'; ?>" value="place order">

   </form>

</section>

<script src="js/script.js"></script>

</body>
</html>
