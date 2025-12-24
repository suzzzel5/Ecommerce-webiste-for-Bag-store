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
   <title>Nexus-Bag Ecommerce website</title>

   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/categories.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<div class="home-bg">

<section class="home">

   <div class="swiper home-slider">
   
   <div class="swiper-wrapper">

      <div class="swiper-slide slide">
         <div class="image">
         </div>
      </div>

      <div class="swiper-slide slide">
         <div class="image">
         </div>
         <div class="content">
         </div>
      </div>

      <div class="swiper-slide slide">
         <div class="image">
            <!-- Removed img tag as requested -->
         </div>
      </div>
   </div>

      <div class="swiper-pagination"></div>
   </div>

</section>

</div>

<!-- Updated Categories Section -->
<section class="category-section">
   <div class="section-header">
      <div class="collection-label">OUR COLLECTIONS</div>
      <h1 class="section-title">Shop By Categories</h1>
   </div>
   <div class="categories-grid">
      <a href="category.php?category=bags" class="category-card premium-bags">
         <div class="category-image">
            <img src="project images/boyschoolbag.jpg" alt="Premium Bags">
         </div>
         <div class="category-content">
            <h3 class="category-title">Premium Bags</h3>
         </div>
      </a>
      
      <a href="category.php?category=luggage" class="category-card luggage">
         <div class="category-image">
            <img src="project images/girlwithluggagebag.jpg" alt="Durable Luggage">
         </div>
         <div class="category-content">
            <h3 class="category-title">Durable Luggage</h3>
         </div>
      </a>
      
      <a href="category.php?category=caps" class="category-card caps">
         <div class="category-image">
            <img src="project images/boywithcap.jpg" alt="Fashionable Caps">
         </div>
         <div class="category-content">
            <h3 class="category-title">Fashionable Caps</h3>
         </div>
      </a>
      
      <a href="category.php?category=sidebag" class="category-card side-bags">
         <div class="category-image">
            <img src="project images/girlwithsidebag.jpg" alt="Side Bags">
         </div>
         <div class="category-content">
            <h3 class="category-title">Side Bags</h3>
         </div>
      </a>
   </div>
</section>

<section class="home-products">

   <h1 class="heading">Latest products</h1>

   <div class="swiper products-slider">

   <div class="swiper-wrapper">

   <?php
     $select_products = $conn->prepare("SELECT * FROM `products` LIMIT 6"); 
      $select_products->execute();
      if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
         // Calculate Discount
         $original_price = $fetch_product['price'];
         $discount = $fetch_product['discount_percentage'] ?? 0;
         $final_price = $original_price;
         if($discount > 0){
            $final_price = round($original_price - ($original_price * ($discount / 100)));
         }
         $stock_quantity = $fetch_product['stock_quantity'];
   ?>
   <form action="" method="post" class="swiper-slide slide">
      <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
      <input type="hidden" name="price" value="<?= $final_price; ?>">
      <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
      <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
      <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="">
      <div class="name"><?= $fetch_product['name']; ?></div>
      <div class="flex">
         <div class="price">
            <?php if($discount > 0): ?>
               <span>Nrs.</span><?= $final_price; ?><span>/-</span> <span style="text-decoration: line-through; color: #999; font-size: 0.8em;">Nrs.<?= $original_price; ?></span>
            <?php else: ?>
               <span>Nrs.</span><?= $original_price; ?><span>/-</span>
            <?php endif; ?>
         </div>
         <input type="number" name="qty" class="qty" min="1" max="<?= $stock_quantity; ?>" onkeypress="if(this.value.length == <?= strlen((string)$stock_quantity); ?>) return false;" value="1">
      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
   </form>
   <?php
      }
      
   }else{
      echo '<p class="empty">no products added yet!</p>';
   }
   ?>

   </div>

   <div class="swiper-pagination"></div>

   </div>
</section>

<section class="services" style="padding: 4rem 2rem; text-align: center;">

   <h1 class="heading" style="margin-bottom: 3rem; text-align: center; font-size: 3rem; color: #333; text-transform: uppercase;">Our Services</h1>

   <div class="box-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr)); gap: 2rem; max-width: 1200px; margin: 0 auto;">

      <div class="box" style="background: #fff; padding: 3rem 2rem; text-align: center; border: 0.1rem solid rgba(0,0,0,.1); border-radius: .5rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);">
         <i class="fas fa-shipping-fast" style="font-size: 4rem; color: #e74c3c; margin-bottom: 1.5rem;"></i>
         <h3 style="font-size: 2rem; color: #333; margin-bottom: 1rem;">Fast Delivery</h3>
         <p style="font-size: 1.5rem; color: #666; line-height: 2;">We deliver within 24 hours</p>
      </div>

      <div class="box" style="background: #fff; padding: 3rem 2rem; text-align: center; border: 0.1rem solid rgba(0,0,0,.1); border-radius: .5rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);">
         <i class="fas fa-lock" style="font-size: 4rem; color: #e74c3c; margin-bottom: 1.5rem;"></i>
         <h3 style="font-size: 2rem; color: #333; margin-bottom: 1rem;">Secure Payment</h3>
         <p style="font-size: 1.5rem; color: #666; line-height: 2;">100% secure payment methods</p>
      </div>

      <div class="box" style="background: #fff; padding: 3rem 2rem; text-align: center; border: 0.1rem solid rgba(0,0,0,.1); border-radius: .5rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.1);">
         <i class="fas fa-headset" style="font-size: 4rem; color: #e74c3c; margin-bottom: 1.5rem;"></i>
         <h3 style="font-size: 2rem; color: #333; margin-bottom: 1rem;">24/7 Support</h3>
         <p style="font-size: 1.5rem; color: #666; line-height: 2;">We are here to help you</p>
      </div>

   </div>

</section>
</section>

<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

<script src="js/script.js"></script>

<script>

var swiper = new Swiper(".home-slider", {
   loop:true,
   spaceBetween: 20,
   pagination: {
      el: ".swiper-pagination",
      clickable:true,
   }, 
});

var swiper = new Swiper(".products-slider", {
   loop:true,
   spaceBetween: 20,
   pagination: {
      el: ".swiper-pagination",
      clickable:true,
   },
   breakpoints: {
      550: {
        slidesPerView: 2,
      },
      768: {
        slidesPerView: 2,
      },
      1024: {
        slidesPerView: 3,
      },
   },
});

// Categories interaction script
document.querySelectorAll('.love-icon').forEach(icon => {
      icon.addEventListener('click', function(e) {
         e.preventDefault();
         e.stopPropagation();
         
         // Toggle heart animation
         this.style.transform = 'scale(1.3)';
         this.style.background = '#ff6b6b';
         this.style.color = 'white';
         
         setTimeout(() => {
               this.style.transform = 'scale(1)';
         }, 200);
         
         // Here you can add AJAX call to add/remove from wishlist
         console.log('Added to wishlist');
      });
   });

</script>

</body>
</html>

