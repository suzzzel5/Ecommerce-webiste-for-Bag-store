<?php
   // Show legacy top-of-page messages on most pages, but NOT on user_register.php
   if(isset($message) && basename($_SERVER['PHP_SELF']) !== 'user_register.php'){
      foreach($message as $msg){
         echo '
         <div class="message">
            <span>'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
?>

<header class="header">

   <section class="flex">

      <a href="home.php" class="logo">Nexus<span>Bag</span></a>

      <nav class="navbar">
         <a href="home.php">Home</a>
         <a href="about.php">Reviews</a>
         <a href="orders.php">Orders</a>
         <a href="shop.php">Shop Now</a>
         <a href="contact.php">Contact Us</a>
      </nav>

      <div class="icons">
         <?php
            if($user_id != ''){
               // Logged in user - count from database
               $count_wishlist_items = $conn->prepare("SELECT * FROM `wishlist` WHERE user_id = ?");
               $count_wishlist_items->execute([$user_id]);
               $total_wishlist_counts = $count_wishlist_items->rowCount();

               $count_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
               $count_cart_items->execute([$user_id]);
               $total_cart_counts = $count_cart_items->rowCount();
            }else{
               // Guest user - count from session
               $total_wishlist_counts = isset($_SESSION['guest_wishlist']) && is_array($_SESSION['guest_wishlist']) ? count($_SESSION['guest_wishlist']) : 0;
               $total_cart_counts = isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart']) ? count($_SESSION['guest_cart']) : 0;
            }
         ?>
         <div id="menu-btn" class="fas fa-bars"></div>
         <a href="search_page.php"><i class="fas fa-search"></i>Search</a>
         <a href="wishlist.php"><i class="fas fa-heart"></i><span>(<?= $total_wishlist_counts; ?>)</span></a>
         <a href="cart.php"><i class="fas fa-shopping-cart"></i><span>(<?= $total_cart_counts; ?>)</span></a>
         <div id="user-btn" class="fas fa-user"></div>
      </div>

      <div class="profile">
         <?php          
            $select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
            $select_profile->execute([$user_id]);
            if($select_profile->rowCount() > 0){
               $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
         ?>
         <p class="profile-name">Welcome, <?= htmlspecialchars($fetch_profile["name"], ENT_QUOTES, 'UTF-8'); ?>!</p>
         <div class="flex-btn">
            <a href="update_user.php" class="btn">Update Profile</a>
            <a href="orders.php" class="option-btn">My Orders</a>
         </div>
         <a href="components/user_logout.php" class="delete-btn" id="user-logout-link">Logout</a> 
         <?php
            }else{
         ?>
         <p>Please login or register first to proceed!</p>
         <div class="flex-btn">
            <a href="user_register.php" class="option-btn">Register</a>
            <a href="user_login.php" class="option-btn">Login</a>
         </div>
         <?php
            }
         ?>      
      </div>

   </section>

</header>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// SweetAlert confirm for user logout
document.addEventListener('DOMContentLoaded', function () {
   var logoutLink = document.getElementById('user-logout-link');
   if (logoutLink && typeof Swal !== 'undefined') {
      logoutLink.addEventListener('click', function (e) {
         e.preventDefault();
         var href = this.getAttribute('href');
         Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to logout from the website?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
         }).then(function (result) {
            if (result.isConfirmed) {
               window.location.href = href;
            }
         });
      });
   }
});
</script>
