<?php
   // Initialize message array if not set
   if(!isset($message)) {
      $message = [];
   }
   
   // Display messages if any exist
   if(!empty($message) && is_array($message)){
      foreach($message as $msg){
         echo '
         <div class="message">
            <span>'.htmlspecialchars($msg).'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<header class="header">

   <section class="flex">

      <a href="../admin/dashboard.php" class="logo">Nexus<span>/ADMIN</span></a>

      <nav class="navbar">
         <a href="../admin/dashboard.php" <?= (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'class="active"' : ''; ?>>Home</a>
         <a href="../admin/products.php" <?= (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'products.php') ? 'class="active"' : ''; ?>>Products</a>
         <a href="../admin/stock_alerts.php" <?= (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'stock_alerts.php') ? 'class="active"' : ''; ?>>Stock Alerts</a>
         <a href="../admin/placed_orders.php" <?= (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'placed_orders.php') ? 'class="active"' : ''; ?>>Orders</a>
         <a href="../admin/admin_accounts.php" <?= (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'admin_accounts.php') ? 'class="active"' : ''; ?>>Admins</a>
         <a href="../admin/users_accounts.php" <?= (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'users_accounts.php') ? 'class="active"' : ''; ?>>Users</a>
         <a href="../admin/messages.php" <?= (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) == 'messages.php') ? 'class="active"' : ''; ?>>Messages</a>
      </nav>

      <div class="icons">
         <div id="menu-btn" class="fas fa-bars"></div>
         <div id="user-btn" class="fas fa-user"></div>
      </div>

      <div class="profile">
         <?php
            // Check if database connection and admin_id are available
            if(isset($conn) && isset($admin_id)) {
               $select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
               $select_profile->execute([$admin_id]);
               $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
               
               if($fetch_profile) {
                  echo '<p>'.htmlspecialchars($fetch_profile['name']).'</p>';
               } else {
                  echo '<p>Admin</p>';
               }
            } else {
               echo '<p>Admin</p>';
            }
         ?>
         <a href="../admin/update_profile.php" class="btn">Update Profile</a>
         <div class="flex-btn">
            <a href="../admin/register_admin.php" class="option-btn">Register</a>
            <a href="../admin/admin_login.php" class="option-btn">Login</a>
         </div>
         <a href="../components/admin_logout.php" class="delete-btn" onclick="return confirm('logout from the website?');">logout</a> 
      </div>

   </section>

</header>
