<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
}

if(isset($_POST['update'])){

   $errors = [];

   $pid = filter_var($_POST['pid'] ?? null, FILTER_VALIDATE_INT);
   if($pid === false || $pid <= 0){
      $errors[] = 'Invalid product ID!';
   }

   $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   if($name === ''){
      $errors[] = 'Product name is required!';
   } elseif(strlen($name) > 100){
      $errors[] = 'Product name cannot exceed 100 characters!';
   }

   $raw_price = isset($_POST['price']) ? (string)$_POST['price'] : '';
   $price = filter_var($raw_price, FILTER_VALIDATE_FLOAT);
   if($price === false || $price < 0){
      $errors[] = 'Please enter a valid product price!';
   } elseif($price > 9999999999){
      $errors[] = 'Product price is too large!';
   }

   $details = isset($_POST['details']) ? trim((string)$_POST['details']) : '';
   $details = filter_var($details, FILTER_SANITIZE_STRING);
   if($details === ''){
      $errors[] = 'Product details are required!';
   }

   // Discount Validation
   $discount_percentage = filter_var($_POST['discount_percentage'] ?? 0, FILTER_VALIDATE_INT);
   if($discount_percentage === false || $discount_percentage < 0 || $discount_percentage > 100){
      $errors[] = 'Discount percentage must be between 0 and 100!';
   }

   if(!empty($errors)){
      foreach($errors as $e){
         $message[] = $e;
      }
   } else {
      // Ensure product exists
      $exists = $conn->prepare("SELECT id FROM `products` WHERE id = ? LIMIT 1");
      $exists->execute([$pid]);
      if($exists->rowCount() === 0){
         $message[] = 'Product not found!';
      } else {
         // Prevent duplicate names (excluding this product)
         $dup = $conn->prepare("SELECT id FROM `products` WHERE name = ? AND id <> ? LIMIT 1");
         $dup->execute([$name, $pid]);
         if($dup->rowCount() > 0){
            $message[] = 'Another product with this name already exists!';
         } else {
            $update_product = $conn->prepare("UPDATE `products` SET name = ?, price = ?, details = ?, discount_percentage = ? WHERE id = ?");
            $update_product->execute([$name, $price, $details, $discount_percentage, $pid]);
            $message[] = 'Product updated successfully!';
         }
      }
   }

   $allowed_ext = ['jpg','jpeg','png','webp'];
   $max_image_size = 2000000; // 2MB

   $old_image_01 = isset($_POST['old_image_01']) ? (string)$_POST['old_image_01'] : '';
   if(isset($_FILES['image_01']) && (int)$_FILES['image_01']['error'] === UPLOAD_ERR_OK){
      if((int)$_FILES['image_01']['size'] > $max_image_size){
         $message[] = 'Image 01 size is too large (max 2MB)!';
      } else {
         $ext = strtolower(pathinfo((string)$_FILES['image_01']['name'], PATHINFO_EXTENSION));
         if(!in_array($ext, $allowed_ext, true)){
            $message[] = 'Image 01 must be jpg, jpeg, png, or webp!';
         } else {
            $new_image_01 = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $image_folder_01 = '../uploaded_img/'.$new_image_01;

            if(move_uploaded_file($_FILES['image_01']['tmp_name'], $image_folder_01)){
               $update_image_01 = $conn->prepare("UPDATE `products` SET image_01 = ? WHERE id = ?");
               if($update_image_01->execute([$new_image_01, $pid])){
                  $old_path = '../uploaded_img/'.$old_image_01;
                  if($old_image_01 && file_exists($old_path)){
                     @unlink($old_path);
                  }
                  $message[] = 'Image 01 updated successfully!';
               } else {
                  @unlink($image_folder_01);
                  $message[] = 'Could not update Image 01.';
               }
            } else {
               $message[] = 'Failed to upload Image 01.';
            }
         }
      }
   }

   $old_image_02 = isset($_POST['old_image_02']) ? (string)$_POST['old_image_02'] : '';
   if(isset($_FILES['image_02']) && (int)$_FILES['image_02']['error'] === UPLOAD_ERR_OK){
      if((int)$_FILES['image_02']['size'] > $max_image_size){
         $message[] = 'Image 02 size is too large (max 2MB)!';
      } else {
         $ext = strtolower(pathinfo((string)$_FILES['image_02']['name'], PATHINFO_EXTENSION));
         if(!in_array($ext, $allowed_ext, true)){
            $message[] = 'Image 02 must be jpg, jpeg, png, or webp!';
         } else {
            $new_image_02 = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $image_folder_02 = '../uploaded_img/'.$new_image_02;

            if(move_uploaded_file($_FILES['image_02']['tmp_name'], $image_folder_02)){
               $update_image_02 = $conn->prepare("UPDATE `products` SET image_02 = ? WHERE id = ?");
               if($update_image_02->execute([$new_image_02, $pid])){
                  $old_path = '../uploaded_img/'.$old_image_02;
                  if($old_image_02 && file_exists($old_path)){
                     @unlink($old_path);
                  }
                  $message[] = 'Image 02 updated successfully!';
               } else {
                  @unlink($image_folder_02);
                  $message[] = 'Could not update Image 02.';
               }
            } else {
               $message[] = 'Failed to upload Image 02.';
            }
         }
      }
   }

   $old_image_03 = isset($_POST['old_image_03']) ? (string)$_POST['old_image_03'] : '';
   if(isset($_FILES['image_03']) && (int)$_FILES['image_03']['error'] === UPLOAD_ERR_OK){
      if((int)$_FILES['image_03']['size'] > $max_image_size){
         $message[] = 'Image 03 size is too large (max 2MB)!';
      } else {
         $ext = strtolower(pathinfo((string)$_FILES['image_03']['name'], PATHINFO_EXTENSION));
         if(!in_array($ext, $allowed_ext, true)){
            $message[] = 'Image 03 must be jpg, jpeg, png, or webp!';
         } else {
            $new_image_03 = 'prod_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $image_folder_03 = '../uploaded_img/'.$new_image_03;

            if(move_uploaded_file($_FILES['image_03']['tmp_name'], $image_folder_03)){
               $update_image_03 = $conn->prepare("UPDATE `products` SET image_03 = ? WHERE id = ?");
               if($update_image_03->execute([$new_image_03, $pid])){
                  $old_path = '../uploaded_img/'.$old_image_03;
                  if($old_image_03 && file_exists($old_path)){
                     @unlink($old_path);
                  }
                  $message[] = 'Image 03 updated successfully!';
               } else {
                  @unlink($image_folder_03);
                  $message[] = 'Could not update Image 03.';
               }
            } else {
               $message[] = 'Failed to upload Image 03.';
            }
         }
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
   <title>Update Product</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="update-product">

   <h1 class="heading">Update Product</h1>

   <?php
      $update_id = filter_var($_GET['update'] ?? null, FILTER_VALIDATE_INT);
      if($update_id === false || $update_id <= 0){
         echo '<p class="empty">invalid product id!</p>';
      } else {
      $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
      $select_products->execute([$update_id]);
      if($update_id !== false && $select_products->rowCount() > 0){
         while($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)){ 
   ?>
   <form action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="pid" value="<?= $fetch_products['id']; ?>">
      <input type="hidden" name="old_image_01" value="<?= $fetch_products['image_01']; ?>">
      <input type="hidden" name="old_image_02" value="<?= $fetch_products['image_02']; ?>">
      <input type="hidden" name="old_image_03" value="<?= $fetch_products['image_03']; ?>">
      <div class="image-container">
         <div class="main-image">
            <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" alt="">
         </div>
         <div class="sub-image">
            <img src="../uploaded_img/<?= $fetch_products['image_01']; ?>" alt="">
            <img src="../uploaded_img/<?= $fetch_products['image_02']; ?>" alt="">
            <img src="../uploaded_img/<?= $fetch_products['image_03']; ?>" alt="">
         </div>
      </div>
      <span>Update Name</span>
      <input type="text" name="name" required class="box" maxlength="100" placeholder="enter product name" value="<?= $fetch_products['name']; ?>">
      <span>Update Price</span>
      <input type="number" name="price" required class="box" min="0" max="9999999999" placeholder="enter product price" onkeypress="if(this.value.length == 10) return false;" value="<?= $fetch_products['price']; ?>">
      <span>Discount Percentage (0-100)</span>
      <input type="number" name="discount_percentage" class="box" min="0" max="100" placeholder="enter discount percentage" value="<?= $fetch_products['discount_percentage']; ?>">
      <span>Update Details</span>
      <textarea name="details" class="box" required cols="30" rows="10"><?= $fetch_products['details']; ?></textarea>
      <span>Update image 01</span>
      <input type="file" name="image_01" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
      <span>Update image 02</span>
      <input type="file" name="image_02" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
      <span>Update image 03</span>
      <input type="file" name="image_03" accept="image/jpg, image/jpeg, image/png, image/webp" class="box">
      <div class="flex-btn">
         <input type="submit" name="update" class="btn" value="update">
         <a href="products.php" class="option-btn">Go Back.</a>
      </div>
   </form>
   
   <?php
         }
      }else{
         echo '<p class="empty">no product found!</p>';
      }
      }
   ?>
</section>
<script src="../js/admin_script.js"></script> 
</body>
</html>
