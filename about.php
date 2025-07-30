<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>About</title>

   <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css" />
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="about">

   <div class="row">
<!-- image haru ra detail haru product ko bare ma !-->
      <div class="image">
         <img src="project images/101311912_141703554106009_5636948270224769024_n - Edited.png" alt="">
      </div>

      <div class="content">
         <h3>SHOP OWNER MESSAGE </h3>
         <p>At Nexus Bags, we are committed to delivering products that blend quality, style, and functionality. Our collection includes premium bags, durable luggage, fashionable caps, and versatile side bags — all crafted with high-grade materials to ensure longevity and everyday comfort. Since our establishment in March 2019, we’ve earned the trust of our local customers by providing reliable products that meet both practical needs and modern design standards.

As a seller, we take pride in offering items that are not only aesthetically appealing but also reasonably priced, giving our customers true value for their money. Whether it’s for school, travel, or casual use, our products are thoughtfully curated to serve multiple purposes. We stay updated with the latest fashion trends and continuously refine our inventory based on customer feedback. With the launch of our e-commerce platform, we aim to extend the same trust, convenience, and satisfaction to a wider online audience.</p>

         <p> <a href="https://www.facebook.com/er.ashokbasnet" target="_blank"></a>  </p>
         <a href="contact.php" class="btn">Contact Us</a>
      </div>

   </div>

</section>

<section class="reviews">
   
   <h1 class="heading">Buyers Reviews</h1>

   <div class="swiper reviews-slider">

   <div class="swiper-wrapper">

      <div class="swiper-slide slide">
         <img src="project images/Screenshot 2025-07-06 at 22-36-28 Stories • Instagram.png" alt="">
         <p>Been using their services for quite a bit and have never had an issue with the quality of their products. Online e-products working great as well. Only issue I have is they usually deliver when I'm a little caught up, though I've set a preferred delivery time. Everything else has been good.</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3> <a href="https://www.instagram.com/suzal_mhz/" target="_blank">Sujal Maharjan</a></h3>
      </div>

      <div class="swiper-slide slide">
         <img src="project images/Screenshot 2025-07-06 at 22-34-47 (2) Instagram.png" alt="">
         <p>It is the first online services in Nepal which we can trust completely.I always unbox making a video and instantly complain if there's anything wrong. Sometimes even don't need to return the item and they process the refund. KinBech do heavy fine to sellers who send wrong products thats why its platform getting better day by day.</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3><a href="https://www.instagram.com/__yogishaa/" target="_blank">Yogisha Maharjan</a></h3>
      </div>

      <div class="swiper-slide slide">
         <img src="project images/Screenshot 2025-07-06 at 22-35-24 (1) Instagram.png" alt="">
         <p>KinBech is great if you choose good sellers . A variety of required item available . Customers can return and refund full amount within 7 days easily . KinBech is boosting eCommerce business in Kathmandu.It provides great opportunity to sale items online with ease.</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3><a href="https://www.instagram.com/__yoon.hh" target="_blank">Cwani shahi</a></h3>
      </div>

      <div class="swiper-slide slide">
         <img src="project images/Screenshot 2025-07-06 at 22-35-24 (1) Instagram copy.png" alt="">
         <p>Using KinBech for online shopping from almost 3 years. Outstanding experience with them. Game vouchers and pick up point as delivery with 0 shipping charges are super saving services.</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3><a href="https://www.instagram.com/direct/t/102368714497197/" target="_blank">Sthait Suzel</a></h3>
      </div>

      <div class="swiper-slide slide">
         <img src="images/pic-2.jpg" alt="">
         <p>I have been using their services for the last 2 years and I have found them extremely reliable.Their return policy is what gives you an extra layer of reliance and peace of mind. In case the product doesn't meet your expectations or if there is any fault in it. then you can return the product within seven days from the date of delivery.</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3><a href="https://www.instagram.com/bi_sesh_" target="_blank">Bishesh Maharjan</a></h3>
      </div>

      <div class="swiper-slide slide">
         <img src="images/pic-6.jpg" alt="">
         <p>KinBech is cool! I have ordered hundreds of products from it and never got any scam. It delivers products in time with out delay. Packaging of products are strong and delivery rates are too low. Just amazing Website will keep shopping from KinBech.</p>
         <div class="stars">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
         </div>
         <h3><a href="https://www.instagram.com/sshresthaatinsta"  target="_blank">Siddhartha Shretha</a></h3>
      </div>

   </div>

   <div class="swiper-pagination"></div>

   </div>

</section>


<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

<script src="js/script.js"></script>

<script>

var swiper = new Swiper(".reviews-slider", {
   loop:true,
   spaceBetween: 20,
   pagination: {
      el: ".swiper-pagination",
      clickable:true,
   },
   breakpoints: {
      0: {
        slidesPerView:1,
      },
      768: {
        slidesPerView: 2,
      },
      991: {
        slidesPerView: 3,
      },
   },
});

</script>

</body>
</html>