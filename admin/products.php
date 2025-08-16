<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
};

if(isset($_POST['add_product'])){

   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);

   $name = $_POST['name'];
$name = filter_var($name, FILTER_SANITIZE_STRING);

// Auto-set category based on product name
   if (stripos($name, 'luggage') !== false) {
      $category = 'Luggage';
   } elseif (stripos($name, 'sidebag') !== false || stripos($name, 'side bag') !== false) {
      $category = 'Side Bag';
   } elseif (stripos($name, 'cap') !== false) {
      $category = 'Cap';
   } elseif (stripos($name, 'bag') !== false || stripos($name, 'bags') !== false) {
      $category = 'Bags';
   } else {
      $category = 'Others';
   }

   $price = $_POST['price'];
   $price = filter_var($price, FILTER_SANITIZE_STRING);
   $details = $_POST['details'];
   $details = filter_var($details, FILTER_SANITIZE_STRING);

   // Stock management fields
   $stock_quantity = $_POST['stock_quantity'];
   $stock_quantity = filter_var($stock_quantity, FILTER_SANITIZE_NUMBER_INT);
   $min_stock_level = $_POST['min_stock_level'];
   $min_stock_level = filter_var($min_stock_level, FILTER_SANITIZE_NUMBER_INT);
   $sku = $_POST['sku'];
   $sku = filter_var($sku, FILTER_SANITIZE_STRING);

   // Generate SKU if not provided
   if(empty($sku)) {
      $sku = 'SKU-' . strtoupper(substr($category, 0, 3)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
   }

   // Determine stock status
   $stock_status = 'out_of_stock';
   if($stock_quantity > $min_stock_level) {
      $stock_status = 'in_stock';
   } elseif($stock_quantity > 0) {
      $stock_status = 'low_stock';
   }

   $image_01 = $_FILES['image_01']['name'];
   $image_01 = filter_var($image_01, FILTER_SANITIZE_STRING);
   $image_size_01 = $_FILES['image_01']['size'];
   $image_tmp_name_01 = $_FILES['image_01']['tmp_name'];
   $image_folder_01 = '../uploaded_img/'.$image_01;

   $image_02 = $_FILES['image_02']['name'];
   $image_02 = filter_var($image_02, FILTER_SANITIZE_STRING);
   $image_size_02 = $_FILES['image_02']['size'];
   $image_tmp_name_02 = $_FILES['image_02']['tmp_name'];
   $image_folder_02 = '../uploaded_img/'.$image_02;

   $image_03 = $_FILES['image_03']['name'];
   $image_03 = filter_var($image_03, FILTER_SANITIZE_STRING);
   $image_size_03 = $_FILES['image_03']['size'];
   $image_tmp_name_03 = $_FILES['image_03']['tmp_name'];
   $image_folder_03 = '../uploaded_img/'.$image_03;

   $select_products = $conn->prepare("SELECT * FROM `products` WHERE name = ?");
   $select_products->execute([$name]);

   if($select_products->rowCount() > 0){
      $message[] = 'product name already exist!';
   }else{

      $insert_products = $conn->prepare("INSERT INTO `products`(name, category, details, price, stock_quantity, min_stock_level, stock_status, sku, image_01, image_02, image_03) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
      $insert_products->execute([$name, $category, $details, $price, $stock_quantity, $min_stock_level, $stock_status, $sku, $image_01, $image_02, $image_03]);

      if($insert_products){
         if($image_size_01 > 2000000 OR $image_size_02 > 2000000 OR $image_size_03 > 2000000){
            $message[] = 'image size is too large!';
         }else{
            move_uploaded_file($image_tmp_name_01, $image_folder_01);
            move_uploaded_file($image_tmp_name_02, $image_folder_02);
            move_uploaded_file($image_tmp_name_03, $image_folder_03);
            
            // Log stock history for new product
            $product_id = $conn->lastInsertId();
            $insert_stock_history = $conn->prepare("INSERT INTO `stock_history`(product_id, action_type, quantity_change, previous_stock, new_stock, notes, admin_id) VALUES(?,?,?,?,?,?,?)");
            $insert_stock_history->execute([$product_id, 'restock', $stock_quantity, 0, $stock_quantity, 'Initial stock', $admin_id]);
            
            $message[] = 'new product added!';
         }

      }

   }  

};

if(isset($_GET['delete'])){

   $delete_id = $_GET['delete'];
   $delete_product_image = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
   $delete_product_image->execute([$delete_id]);
   $fetch_delete_image = $delete_product_image->fetch(PDO::FETCH_ASSOC);
   unlink('../uploaded_img/'.$fetch_delete_image['image_01']);
   unlink('../uploaded_img/'.$fetch_delete_image['image_02']);
   unlink('../uploaded_img/'.$fetch_delete_image['image_03']);
   $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
   $delete_product->execute([$delete_id]);
   $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE pid = ?");
   $delete_cart->execute([$delete_id]);
   $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
   $delete_wishlist->execute([$delete_id]);
   header('location:products.php');
}

// Stock management functions
if(isset($_POST['restock'])) {
   $product_id = $_POST['product_id'];
   $restock_quantity = $_POST['restock_quantity'];
   $notes = $_POST['notes'];
   
   // Get current stock
   $get_current_stock = $conn->prepare("SELECT stock_quantity, min_stock_level FROM `products` WHERE id = ?");
   $get_current_stock->execute([$product_id]);
   $current_stock_data = $get_current_stock->fetch(PDO::FETCH_ASSOC);
   $current_stock = $current_stock_data['stock_quantity'];
   $min_stock = $current_stock_data['min_stock_level'];
   
   $new_stock = $current_stock + $restock_quantity;
   
   // Update stock status
   $stock_status = 'out_of_stock';
   if($new_stock > $min_stock) {
      $stock_status = 'in_stock';
   } elseif($new_stock > 0) {
      $stock_status = 'low_stock';
   }
   
   // Update product stock
   $update_stock = $conn->prepare("UPDATE `products` SET stock_quantity = ?, stock_status = ?, last_restocked = NOW() WHERE id = ?");
   $update_stock->execute([$new_stock, $stock_status, $product_id]);
   
   // Log stock history
   $insert_stock_history = $conn->prepare("INSERT INTO `stock_history`(product_id, action_type, quantity_change, previous_stock, new_stock, notes, admin_id) VALUES(?,?,?,?,?,?,?)");
   $insert_stock_history->execute([$product_id, 'restock', $restock_quantity, $current_stock, $new_stock, $notes, $admin_id]);
   
   $message[] = 'Stock updated successfully!';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Products</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="add-products">

   <h1 class="heading">Add Product</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <div class="flex">
         <div class="inputBox">
            <span>Product Name (required)</span>
            <input type="text" class="box" required maxlength="100" placeholder="enter product name" name="name">
         </div>
         <div class="inputBox">
            <span>Product Price (required)</span>
            <input type="number" min="0" class="box" required max="9999999999" placeholder="enter product price" onkeypress="if(this.value.length == 10) return false;" name="price">
         </div>
         <div class="inputBox">
            <span>Stock Quantity (required)</span>
            <input type="number" min="0" class="box" required placeholder="enter stock quantity" name="stock_quantity">
         </div>
         <div class="inputBox">
            <span>Minimum Stock Level</span>
            <input type="number" min="0" class="box" placeholder="enter minimum stock level" name="min_stock_level" value="5">
         </div>
         <div class="inputBox">
            <span>SKU (optional - auto-generated if empty)</span>
            <input type="text" class="box" maxlength="50" placeholder="enter SKU" name="sku">
         </div>
        <div class="inputBox">
            <span>Image 01 (required)</span>
            <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
        </div>
        <div class="inputBox">
            <span>Image 02 (required)</span>
            <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
        </div>
        <div class="inputBox">
            <span>Image 03 (required)</span>
            <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" class="box" required>
        </div>
         <div class="inputBox">
            <span>Product description (required)</span>
            <textarea name="details" placeholder="enter product details" class="box" required maxlength="500" cols="30" rows="10"></textarea>
         </div>
      </div>
      
      <input type="submit" value="add product" class="btn" name="add_product">
   </form>

</section>

<section class="show-products">

   <h1 class="heading">Products Added.</h1>

   <div class="box-container">

   <?php
      $select_products = $conn->prepare("SELECT * FROM `products` ORDER BY stock_status ASC, stock_quantity ASC");
      $select_products->execute();
      if($select_products->rowCount() > 0){
         while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
            // Stock status color coding
            $stock_class = '';
            $stock_icon = '';
            switch($fetch_products['stock_status']) {
               case 'in_stock':
                  $stock_class = 'stock-in';
                  $stock_icon = '✅';
                  break;
               case 'low_stock':
                  $stock_class = 'stock-low';
                  $stock_icon = '⚠️';
                  break;
               case 'out_of_stock':
                  $stock_class = 'stock-out';
                  $stock_icon = '❌';
                  break;
            }
   ?>
   <div class="box">
      <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" alt="">
      <div class="name"><?= $fetch_products['name']; ?></div>
      <div class="price">Nrs.<span><?= $fetch_products['price']; ?></span>/-</div>
      <div class="stock-info <?= $stock_class; ?>">
         <span class="stock-icon"><?= $stock_icon; ?></span>
         <span class="stock-text">Stock: <?= $fetch_products['stock_quantity']; ?></span>
         <span class="stock-status">(<?= ucfirst(str_replace('_', ' ', $fetch_products['stock_status'])); ?>)</span>
      </div>
      <div class="sku">SKU: <?= $fetch_products['sku']; ?></div>
      <div class="details"><span><?= $fetch_products['details']; ?></span></div>
      <div class="flex-btn">
         <a href="update_product.php?update=<?= $fetch_products['id']; ?>" class="option-btn">update</a>
         <a href="stock_management.php?product_id=<?= $fetch_products['id']; ?>" class="option-btn">stock</a>
         <a href="products.php?delete=<?= $fetch_products['id']; ?>" class="delete-btn" onclick="return confirm('delete this product?');">delete</a>
      </div>
   </div>
   <?php
         }
      }else{
         echo '<p class="empty">no products added yet!</p>';
      }
   ?>
   
   </div>

</section>

<script src="../js/admin_script.js"></script>
   
</body>
</html>
