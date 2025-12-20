<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
};

if(isset($_POST['add_product'])){

   $errors = [];

   $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   if($name === ''){
      $errors[] = 'Product name is required!';
   }elseif(strlen($name) > 100){
      $errors[] = 'Product name cannot exceed 100 characters!';
   }elseif(strlen($name) < 3){
      $errors[] = 'Product name must be at least 3 characters long!';
   }elseif(is_numeric($name)){
      $errors[] = 'Product name cannot be just numbers!';
   }

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

   // Price validation (numeric)
   $raw_price = isset($_POST['price']) ? (string)$_POST['price'] : '';
   $price = filter_var($raw_price, FILTER_VALIDATE_FLOAT);
   if($price === false || $price < 0 || !is_numeric($raw_price)){
      $errors[] = 'Please enter a valid product price!';
   } elseif($price > 9999999999){
      $errors[] = 'Product price is too large!';
   }

   $details = isset($_POST['details']) ? trim((string)$_POST['details']) : '';
   $details = filter_var($details, FILTER_SANITIZE_STRING);
   if($details === ''){
      $errors[] = 'Product description is required!';
   } elseif(strlen($details) > 500){
      $errors[] = 'Product description cannot exceed 500 characters!';
   } elseif(strlen($details) < 10){
      $errors[] = 'Product description must be at least 10 characters long!';
   } elseif(is_numeric($details)){
      $errors[] = 'Product description cannot be just numbers!';
   }

   // Stock management fields
   $stock_quantity = filter_var($_POST['stock_quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
   if($stock_quantity === false){
      $errors[] = 'Stock quantity must be a valid non-negative number!';
   }

   $min_stock_level = filter_var($_POST['min_stock_level'] ?? 5, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
   if($min_stock_level === false){
      $errors[] = 'Minimum stock level must be a valid non-negative number!';
   }

   $sku = isset($_POST['sku']) ? trim((string)$_POST['sku']) : '';
   $sku = filter_var($sku, FILTER_SANITIZE_STRING);
   if(strlen($sku) > 50){
      $errors[] = 'SKU cannot exceed 50 characters!';
   } elseif($sku !== ''){
      // Check if SKU already exists
      $check_sku = $conn->prepare("SELECT id FROM `products` WHERE sku = ?");
      $check_sku->execute([$sku]);
      if($check_sku->rowCount() > 0){
         $errors[] = 'SKU already exists! Please use a unique SKU.';
      }
   }

   // Determine stock status
   $stock_status = 'out_of_stock';
   if($stock_quantity !== false && $min_stock_level !== false){
      if($stock_quantity > $min_stock_level) {
         $stock_status = 'in_stock';
      } elseif($stock_quantity > 0) {
         $stock_status = 'low_stock';
      }
   }

   // Validate images
   $allowed_ext = ['jpg','jpeg','png','webp'];
   $max_image_size = 2000000; // 2MB

   $uploads = [
      'image_01' => null,
      'image_02' => null,
      'image_03' => null,
   ];

   foreach (['image_01','image_02','image_03'] as $k) {
      if(!isset($_FILES[$k]) || (int)$_FILES[$k]['error'] !== UPLOAD_ERR_OK){
         $errors[] = strtoupper(str_replace('_',' ', $k)) . ' is required!';
         continue;
      }
      if((int)$_FILES[$k]['size'] > $max_image_size){
         $errors[] = strtoupper(str_replace('_',' ', $k)) . ' size is too large (max 2MB)!';
         continue;
      }
      $original = (string)$_FILES[$k]['name'];
      $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
      if(!in_array($ext, $allowed_ext, true)){
         $errors[] = strtoupper(str_replace('_',' ', $k)) . ' must be jpg, jpeg, png, or webp!';
         continue;
      }
      // Strict image content check
      if(getimagesize($_FILES[$k]['tmp_name']) === false){
         $errors[] = strtoupper(str_replace('_',' ', $k)) . ' is not a valid image file!';
         continue;
      }
      $new_name = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
      $uploads[$k] = [
         'name' => $new_name,
         'tmp'  => $_FILES[$k]['tmp_name'],
         'path' => '../uploaded_img/' . $new_name,
      ];
   }

   if(!empty($errors)){
      foreach($errors as $e){
         $message[] = $e;
      }
   }else{
      // Generate SKU if not provided
      if($sku === '') {
         $sku = 'SKU-' . strtoupper(substr($category, 0, 3)) . '-' . date('Ymd') . '-' . rand(1000, 9999);
      }

      // Prevent duplicate names
      $select_products = $conn->prepare("SELECT id FROM `products` WHERE name = ? LIMIT 1");
      $select_products->execute([$name]);

      if($select_products->rowCount() > 0){
         $message[] = 'Product name already exists!';
      }else{
         try {
            $conn->beginTransaction();

            $insert_products = $conn->prepare("INSERT INTO `products`(name, category, details, price, stock_quantity, min_stock_level, stock_status, sku, image_01, image_02, image_03) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $insert_products->execute([
               $name,
               $category,
               $details,
               $price,
               $stock_quantity,
               $min_stock_level,
               $stock_status,
               $sku,
               $uploads['image_01']['name'],
               $uploads['image_02']['name'],
               $uploads['image_03']['name'],
            ]);

            $product_id = (int)$conn->lastInsertId();

            // Move files
            foreach ($uploads as $u) {
               if(!$u) { throw new Exception('Image upload data missing.'); }
               if(!move_uploaded_file($u['tmp'], $u['path'])){
                  throw new Exception('Failed to upload product images.');
               }
            }

            // Log stock history for new product
            $insert_stock_history = $conn->prepare("INSERT INTO `stock_history`(product_id, action_type, quantity_change, previous_stock, new_stock, notes, admin_id) VALUES(?,?,?,?,?,?,?)");
            $insert_stock_history->execute([$product_id, 'restock', $stock_quantity, 0, $stock_quantity, 'Initial stock', $admin_id]);

            $conn->commit();
            $message[] = 'New product added!';
         } catch (Exception $ex) {
            if($conn->inTransaction()){
               $conn->rollBack();
            }
            error_log('Add product error: ' . $ex->getMessage());
            $message[] = 'Could not add product. Please try again.';
         }
      }
   }

};

if(isset($_GET['delete'])){

   $delete_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
   if($delete_id === false || $delete_id <= 0){
      header('location:products.php');
      exit();
   }

   $delete_product_image = $conn->prepare("SELECT image_01, image_02, image_03 FROM `products` WHERE id = ?");
   $delete_product_image->execute([$delete_id]);
   $fetch_delete_image = $delete_product_image->fetch(PDO::FETCH_ASSOC);

   if(!$fetch_delete_image){
      header('location:products.php');
      exit();
   }

   foreach (['image_01','image_02','image_03'] as $k) {
      $p = '../uploaded_img/' . (string)$fetch_delete_image[$k];
      if($fetch_delete_image[$k] && file_exists($p)){
         @unlink($p);
      }
   }

   // Delete related stock history and alerts first
   $delete_stock_history = $conn->prepare("DELETE FROM `stock_history` WHERE product_id = ?");
   $delete_stock_history->execute([$delete_id]);
   $delete_stock_alerts = $conn->prepare("DELETE FROM `stock_alerts` WHERE product_id = ?");
   $delete_stock_alerts->execute([$delete_id]);

   $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
   $delete_product->execute([$delete_id]);
   $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE pid = ?");
   $delete_cart->execute([$delete_id]);
   $delete_wishlist = $conn->prepare("DELETE FROM `wishlist` WHERE pid = ?");
   $delete_wishlist->execute([$delete_id]);
   header('location:products.php');
   exit();
}

// Stock management functions
if(isset($_POST['restock'])) {
   $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
   $restock_quantity = filter_var($_POST['restock_quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
   $notes = isset($_POST['notes']) ? trim((string)$_POST['notes']) : '';
   $notes = filter_var($notes, FILTER_SANITIZE_STRING);

   if($product_id === false || $product_id <= 0){
      $message[] = 'Invalid product selected!';
   } elseif($restock_quantity === false){
      $message[] = 'Restock quantity must be at least 1!';
   } else {
      // Get current stock
      $get_current_stock = $conn->prepare("SELECT stock_quantity, min_stock_level FROM `products` WHERE id = ?");
      $get_current_stock->execute([$product_id]);
      $current_stock_data = $get_current_stock->fetch(PDO::FETCH_ASSOC);

      if(!$current_stock_data){
         $message[] = 'Product not found!';
      } else {
         $current_stock = (int)$current_stock_data['stock_quantity'];
         $min_stock = (int)$current_stock_data['min_stock_level'];
         $new_stock = $current_stock + (int)$restock_quantity;

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
   }
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

   <form action="" method="post" style="display: flex; gap: 10px; max-width: 800px; margin: 0 auto 30px auto; flex-wrap: wrap;">
      <input type="text" name="search_box" placeholder="Search products..." maxlength="100" class="box" style="flex: 1; margin: 0; min-width: 200px;" value="<?= isset($_POST['search_box']) ? htmlspecialchars($_POST['search_box']) : '' ?>">
      
      <select name="filter_type" class="box" style="flex: 0 0 200px; margin: 0; cursor: pointer;">
         <option value="">All Filters</option>
         <optgroup label="Category">
            <option value="cat_Luggage" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'cat_Luggage') ? 'selected' : '' ?>>Luggage</option>
            <option value="cat_Side Bag" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'cat_Side Bag') ? 'selected' : '' ?>>Side Bag</option>
            <option value="cat_Cap" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'cat_Cap') ? 'selected' : '' ?>>Cap</option>
            <option value="cat_Bags" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'cat_Bags') ? 'selected' : '' ?>>Bags</option>
            <option value="cat_Others" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'cat_Others') ? 'selected' : '' ?>>Others</option>
         </optgroup>
         <optgroup label="Stock Status">
            <option value="stat_in_stock" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'stat_in_stock') ? 'selected' : '' ?>>In Stock</option>
            <option value="stat_low_stock" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'stat_low_stock') ? 'selected' : '' ?>>Low Stock</option>
            <option value="stat_out_of_stock" <?= (isset($_POST['filter_type']) && $_POST['filter_type'] == 'stat_out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
         </optgroup>
      </select>

      <button type="submit" class="fas fa-search" name="search_btn" style="background: var(--main-color, #2980b9); color: white; font-size: 20px; padding: 10px 20px; cursor: pointer; border-radius: 5px; border: none;"></button>
      <a href="products.php" class="option-btn" style="margin: 0; display: flex; align-items: center;">Reset</a>
   </form>

   <div class="box-container">

   <?php
      if(isset($_POST['search_btn'])){
         $search_box = $_POST['search_box'] ?? '';
         $search_box = filter_var($search_box, FILTER_SANITIZE_STRING);
         $filter_type = $_POST['filter_type'] ?? '';

         $query = "SELECT * FROM `products` WHERE (name LIKE ? OR sku LIKE ?)";
         $params[] = "%{$search_box}%";
         $params[] = "%{$search_box}%";

         if(!empty($filter_type)){
            if(strpos($filter_type, 'cat_') === 0){
               $query .= " AND category = ?";
               $params[] = substr($filter_type, 4);
            } elseif(strpos($filter_type, 'stat_') === 0){
               $query .= " AND stock_status = ?";
               $params[] = substr($filter_type, 5);
            }
         }
         
         $query .= " ORDER BY stock_status ASC, stock_quantity ASC";
         $select_products = $conn->prepare($query);
         $select_products->execute($params);
      }else{
         $select_products = $conn->prepare("SELECT * FROM `products` ORDER BY stock_status ASC, stock_quantity ASC");
         $select_products->execute();
      }

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
