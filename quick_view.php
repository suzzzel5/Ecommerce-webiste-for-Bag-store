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
   <title>Quick view</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <!-- Google Fonts -->
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

   <style>
      /* Modern Quick View Styles */
      .quick-view {
         padding: 3rem 2rem;
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         min-height: 100vh;
      }

      .quick-view .heading {
         font-size: 3.5rem;
         color: white;
         margin-bottom: 3rem;
         text-align: center;
         text-transform: uppercase;
         font-weight: 600;
         text-shadow: 0 2px 4px rgba(0,0,0,0.3);
         letter-spacing: 2px;
      }

      .quick-view-container {
         max-width: 1200px;
         margin: 0 auto;
         background: white;
         border-radius: 20px;
         box-shadow: 0 20px 60px rgba(0,0,0,0.1);
         overflow: hidden;
         backdrop-filter: blur(10px);
      }

      .quick-view form {
         padding: 0;
         border: none;
         background: transparent;
         box-shadow: none;
         margin: 0;
      }

      .quick-view form .row {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 0;
         flex-wrap: nowrap;
         min-height: 600px;
      }

      .quick-view form .row .image-container {
         margin: 0;
         flex: none;
         background: linear-gradient(45deg, #f8f9fa, #e9ecef);
         padding: 3rem;
         display: flex;
         flex-direction: column;
         justify-content: center;
         position: relative;
         overflow: hidden;
      }

      .quick-view form .row .image-container::before {
         content: '';
         position: absolute;
         top: 0;
         left: 0;
         right: 0;
         bottom: 0;
         background: linear-gradient(45deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
         z-index: 1;
      }

      .quick-view form .row .image-container .main-image {
         position: relative;
         z-index: 2;
         text-align: center;
         margin-bottom: 2rem;
      }

      .quick-view form .row .image-container .main-image img {
         height: 350px;
         width: 100%;
         object-fit: contain;
         border-radius: 15px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
         transition: transform 0.3s ease;
      }

      .quick-view form .row .image-container .main-image img:hover {
         transform: scale(1.05);
      }

      .quick-view form .row .image-container .sub-image {
         display: flex;
         gap: 1rem;
         justify-content: center;
         margin-top: 2rem;
         position: relative;
         z-index: 2;
      }

      .quick-view form .row .image-container .sub-image img {
         height: 80px;
         width: 80px;
         object-fit: cover;
         border-radius: 10px;
         cursor: pointer;
         transition: all 0.3s ease;
         border: 3px solid transparent;
         box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      }

      .quick-view form .row .image-container .sub-image img:hover {
         transform: translateY(-5px);
         border-color: #667eea;
         box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
      }

      .quick-view form .row .content {
         flex: none;
         padding: 4rem;
         display: flex;
         flex-direction: column;
         justify-content: center;
         background: white;
      }

      .quick-view form .row .content .name {
         font-size: 2.8rem;
         color: #2c3e50;
         font-weight: 600;
         margin-bottom: 1.5rem;
         line-height: 1.2;
      }

      .quick-view form .row .flex {
         display: flex;
         align-items: center;
         justify-content: space-between;
         gap: 2rem;
         margin: 2rem 0;
         padding: 1.5rem;
         background: #f8f9fa;
         border-radius: 12px;
         border: 2px solid #e9ecef;
      }

      .quick-view form .row .flex .qty {
         width: 100px;
         padding: 1rem;
         border: 2px solid #dee2e6;
         font-size: 1.6rem;
         color: #495057;
         border-radius: 8px;
         text-align: center;
         background: white;
         transition: all 0.3s ease;
      }

      .quick-view form .row .flex .qty:focus {
         border-color: #667eea;
         box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
         outline: none;
      }

      .quick-view form .row .flex .price {
         font-size: 2.4rem;
         color: #e74c3c;
         font-weight: 700;
         display: flex;
         align-items: center;
         gap: 0.5rem;
      }

      .quick-view form .row .flex .price span {
         font-size: 1.8rem;
         color: #6c757d;
         font-weight: 500;
      }

      .quick-view form .row .content .details {
         font-size: 1.6rem;
         color: #6c757d;
         line-height: 1.8;
         margin-bottom: 2rem;
         padding: 1.5rem;
         background: #f8f9fa;
         border-radius: 10px;
         border-left: 4px solid #667eea;
      }

      .quick-view form .flex-btn {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 1.5rem;
         margin-top: 2rem;
      }

      .quick-view form .flex-btn .btn,
      .quick-view form .flex-btn .option-btn {
         padding: 1.5rem 2rem;
         font-size: 1.6rem;
         font-weight: 600;
         border-radius: 12px;
         transition: all 0.3s ease;
         text-transform: uppercase;
         letter-spacing: 1px;
         position: relative;
         overflow: hidden;
      }

      .quick-view form .flex-btn .btn {
         background: linear-gradient(45deg, #667eea, #764ba2);
         color: white;
         border: none;
      }

      .quick-view form .flex-btn .btn:hover {
         transform: translateY(-2px);
         box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
      }

      .quick-view form .flex-btn .option-btn {
         background: linear-gradient(45deg, #f39c12, #e67e22);
         color: white;
         border: none;
      }

      .quick-view form .flex-btn .option-btn:hover {
         transform: translateY(-2px);
         box-shadow: 0 10px 25px rgba(243, 156, 18, 0.4);
      }

      .quick-view form .flex-btn .btn::before,
      .quick-view form .flex-btn .option-btn::before {
         content: '';
         position: absolute;
         top: 0;
         left: -100%;
         width: 100%;
         height: 100%;
         background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
         transition: left 0.5s;
      }

      .quick-view form .flex-btn .btn:hover::before,
      .quick-view form .flex-btn .option-btn:hover::before {
         left: 100%;
      }

      .quick-view .empty {
         background: white;
         padding: 3rem;
         border-radius: 15px;
         text-align: center;
         color: #e74c3c;
         font-size: 2rem;
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
         margin: 2rem auto;
         max-width: 500px;
      }

      /* Responsive Design */
      @media (max-width: 768px) {
         .quick-view form .row {
            grid-template-columns: 1fr;
         }
         
         .quick-view form .row .image-container {
            padding: 2rem;
         }
         
         .quick-view form .row .content {
            padding: 2rem;
         }
         
         .quick-view form .flex-btn {
            grid-template-columns: 1fr;
         }
         
         .quick-view .heading {
            font-size: 2.8rem;
         }
      }

      /* Animation for page load */
      .quick-view-container {
         animation: slideInUp 0.6s ease-out;
      }

      @keyframes slideInUp {
         from {
            opacity: 0;
            transform: translateY(30px);
         }
         to {
            opacity: 1;
            transform: translateY(0);
         }
      }

      /* Product badge */
      .product-badge {
         position: absolute;
         top: 2rem;
         right: 2rem;
         background: linear-gradient(45deg, #e74c3c, #c0392b);
         color: white;
         padding: 0.5rem 1rem;
         border-radius: 20px;
         font-size: 1.2rem;
         font-weight: 600;
         z-index: 3;
         box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
      }
   </style>

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="quick-view">

   <h1 class="heading">Quick View</h1>

   <div class="quick-view-container">
      <?php
        $pid = $_GET['pid'];
        $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?"); 
        $select_products->execute([$pid]);
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
      <form action="" method="post" class="box">
         <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
         <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
         <input type="hidden" name="price" value="<?= $final_price; ?>">
         <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
         
         <div class="product-badge">
            <i class="fas fa-star"></i> Premium Product
         </div>
         
         <div class="row">
            <div class="image-container">
               <div class="main-image">
                  <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="<?= $fetch_product['name']; ?>">
               </div>
               <div class="sub-image">
                  <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="<?= $fetch_product['name']; ?>">
                  <img src="uploaded_img/<?= $fetch_product['image_02']; ?>" alt="<?= $fetch_product['name']; ?>">
                  <img src="uploaded_img/<?= $fetch_product['image_03']; ?>" alt="<?= $fetch_product['name']; ?>">
               </div>
            </div>
            <div class="content">
               <div class="name"><?= $fetch_product['name']; ?></div>
               <div class="flex">
                  <div class="price">
                     <?php if($discount > 0): ?>
                        <span>Nrs.</span><?= number_format($final_price); ?><span>/-</span> <span style="text-decoration: line-through; color: #999; font-size: 0.8em;">Nrs.<?= number_format($original_price); ?></span>
                     <?php else: ?>
                        <span>Nrs.</span><?= number_format($original_price); ?><span>/-</span>
                     <?php endif; ?>
                  </div>
                  <input type="number" name="qty" class="qty" min="1" max="<?= $stock_quantity; ?>" onkeypress="if(this.value.length == <?= strlen((string)$stock_quantity); ?>) return false;" value="1">
               </div>
               <div class="details"><?= $fetch_product['details']; ?></div>
               <div class="flex-btn">
                  <input type="submit" value="Add to Cart" class="btn" name="add_to_cart">
                  <input class="option-btn" type="submit" name="add_to_wishlist" value="Add to Wishlist">
               </div>
            </div>
         </div>
      </form>
      <?php
         }
      }else{
         echo '<p class="empty">No products found!</p>';
      }
      ?>
   </div>

</section>

<script src="js/script.js"></script>

<script>
   // Image gallery functionality
   document.addEventListener('DOMContentLoaded', function() {
      const mainImage = document.querySelector('.main-image img');
      const subImages = document.querySelectorAll('.sub-image img');
      
      subImages.forEach(img => {
         img.addEventListener('click', function() {
            mainImage.src = this.src;
            subImages.forEach(sub => sub.style.borderColor = 'transparent');
            this.style.borderColor = '#667eea';
         });
      });
   });
</script>

</body>
</html>
