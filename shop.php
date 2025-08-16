<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

include 'components/wishlist_cart.php';

// Handle sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Shop</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="products">

   <h1 class="heading">Latest Products.</h1>

   <!-- Sort dropdown -->
   <div class="sort-container" style="text-align: center; margin-bottom: 30px; padding: 20px 0;">
      <form method="GET" action="" style="display: inline-block;">
         <div style="position: relative; display: inline-block;">
            <i class="fas fa-sort" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #666; z-index: 1;"></i>
            <select name="sort" onchange="this.form.submit()" style="
               padding: 12px 40px 12px 35px; 
               border: 2px solid #e0e0e0; 
               border-radius: 25px; 
               font-size: 14px; 
               background: white; 
               color: #333; 
               cursor: pointer; 
               outline: none; 
               transition: all 0.3s ease; 
               box-shadow: 0 2px 5px rgba(0,0,0,0.1);
               min-width: 200px;
               appearance: none;
               -webkit-appearance: none;
               -moz-appearance: none;
               background-image: url('data:image/svg+xml;utf8,<svg fill="%23666" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
               background-repeat: no-repeat;
               background-position: right 10px center;
               background-size: 20px;
            " onmouseover="this.style.borderColor='#007bff'; this.style.boxShadow='0 4px 8px rgba(0,123,255,0.2)'" 
               onmouseout="this.style.borderColor='#e0e0e0'; this.style.boxShadow='0 2px 5px rgba(0,0,0,0.1)'">
               <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>🕒 Latest Products</option>
               <option value="high_to_low" <?= $sort == 'high_to_low' ? 'selected' : '' ?>>💰 Price: High to Low</option>
               <option value="low_to_high" <?= $sort == 'low_to_high' ? 'selected' : '' ?>>💸 Price: Low to High</option>
               <option value="a_to_z" <?= $sort == 'a_to_z' ? 'selected' : '' ?>>📝 Name: A to Z</option>
            </select>
         </div>
      </form>
   </div>

   <div class="box-container">

   <?php
     // Build the SQL query based on sorting
     $sql = "SELECT * FROM `products`";
     
     switch($sort) {
         case 'high_to_low':
             $sql .= " ORDER BY price DESC";
             break;
         case 'low_to_high':
             $sql .= " ORDER BY price ASC";
             break;
         case 'a_to_z':
             $sql .= " ORDER BY name ASC";
             break;
         case 'latest':
         default:
             $sql .= " ORDER BY id DESC";
             break;
     }
     
     $select_products = $conn->prepare($sql); 
     $select_products->execute();
     if($select_products->rowCount() > 0){
      while($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)){
   ?>
   <form action="" method="post" class="box">
      <input type="hidden" name="pid" value="<?= $fetch_product['id']; ?>">
      <input type="hidden" name="name" value="<?= $fetch_product['name']; ?>">
      <input type="hidden" name="price" value="<?= $fetch_product['price']; ?>">
      <input type="hidden" name="image" value="<?= $fetch_product['image_01']; ?>">
      <button class="fas fa-heart" type="submit" name="add_to_wishlist"></button>
      <a href="quick_view.php?pid=<?= $fetch_product['id']; ?>" class="fas fa-eye"></a>
      <img src="uploaded_img/<?= $fetch_product['image_01']; ?>" alt="">
      <div class="name"><?= $fetch_product['name']; ?></div>
      <div class="flex">
         <div class="price"><span>Nrs.</span><?= $fetch_product['price']; ?><span>/-</span></div>
         <input type="number" name="qty" class="qty" min="1" max="99" onkeypress="if(this.value.length == 2) return false;" value="1">
      </div>
      <input type="submit" value="add to cart" class="btn" name="add_to_cart">
   </form>
   <?php
      }
   }else{
      echo '<p class="empty">no products found!</p>';
   }
   ?>

   </div>

</section>



<script src="js/script.js"></script>

</body>
</html>