<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
   exit();
}

// Get product details
$product_id = $_GET['product_id'] ?? 0;
if($product_id == 0) {
   header('location:products.php');
   exit();
}

$select_product = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
$select_product->execute([$product_id]);
if($select_product->rowCount() == 0) {
   header('location:products.php');
   exit();
}
$product = $select_product->fetch(PDO::FETCH_ASSOC);

// Handle stock operations
if(isset($_POST['restock'])) {
   $restock_quantity = (int)$_POST['restock_quantity'];
   $notes = trim($_POST['notes']);
   
   if($restock_quantity > 0) {
      $current_stock = $product['stock_quantity'];
      $new_stock = $current_stock + $restock_quantity;
      
      // Update stock status
      $stock_status = 'out_of_stock';
      if($new_stock > $product['min_stock_level']) {
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
       
       // Clear stock alerts if stock is now sufficient
       if($new_stock > $product['min_stock_level']) {
          $clear_alerts = $conn->prepare("UPDATE `stock_alerts` SET is_read = 1 WHERE product_id = ? AND (alert_type = 'low_stock' OR alert_type = 'out_of_stock')");
          $clear_alerts->execute([$product_id]);
       }
      
      $message[] = 'Stock updated successfully!';
      
      // Refresh product data
      $select_product->execute([$product_id]);
      $product = $select_product->fetch(PDO::FETCH_ASSOC);
   }
}

if(isset($_POST['adjust_stock'])) {
   $adjustment_quantity = (int)$_POST['adjustment_quantity'];
   $adjustment_type = $_POST['adjustment_type']; // 'add' or 'subtract'
   $notes = trim($_POST['notes']);
   
   $current_stock = $product['stock_quantity'];
   $quantity_change = ($adjustment_type == 'add') ? $adjustment_quantity : -$adjustment_quantity;
   $new_stock = $current_stock + $quantity_change;
   
   if($new_stock >= 0) {
      // Update stock status
      $stock_status = 'out_of_stock';
      if($new_stock > $product['min_stock_level']) {
         $stock_status = 'in_stock';
      } elseif($new_stock > 0) {
         $stock_status = 'low_stock';
      }
      
             // Update product stock
       $update_stock = $conn->prepare("UPDATE `products` SET stock_quantity = ?, stock_status = ? WHERE id = ?");
       $update_stock->execute([$new_stock, $stock_status, $product_id]);
       
       // Log stock history
       $insert_stock_history = $conn->prepare("INSERT INTO `stock_history`(product_id, action_type, quantity_change, previous_stock, new_stock, notes, admin_id) VALUES(?,?,?,?,?,?,?)");
       $insert_stock_history->execute([$product_id, 'adjustment', $quantity_change, $current_stock, $new_stock, $notes, $admin_id]);
       
       // Clear stock alerts if stock is now sufficient (for add operations)
       if($adjustment_type == 'add' && $new_stock > $product['min_stock_level']) {
          $clear_alerts = $conn->prepare("UPDATE `stock_alerts` SET is_read = 1 WHERE product_id = ? AND (alert_type = 'low_stock' OR alert_type = 'out_of_stock')");
          $clear_alerts->execute([$product_id]);
       }
      
      $message[] = 'Stock adjusted successfully!';
      
      // Refresh product data
      $select_product->execute([$product_id]);
      $product = $select_product->fetch(PDO::FETCH_ASSOC);
   } else {
      $message[] = 'Cannot reduce stock below 0!';
   }
}

// Get stock history
$select_stock_history = $conn->prepare("
   SELECT sh.*, a.name as admin_name 
   FROM `stock_history` sh 
   LEFT JOIN `admins` a ON sh.admin_id = a.id 
   WHERE sh.product_id = ? 
   ORDER BY sh.created_at DESC 
   LIMIT 20
");
$select_stock_history->execute([$product_id]);
$stock_history = $select_stock_history->fetchAll(PDO::FETCH_ASSOC);

// Get stock alerts
$select_alerts = $conn->prepare("
   SELECT * FROM `stock_alerts` 
   WHERE product_id = ? AND is_read = 0 
   ORDER BY created_at DESC
");
$select_alerts->execute([$product_id]);
$alerts = $select_alerts->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Stock Management - <?= $product['name']; ?></title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">

   <style>
      .stock-dashboard {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
         gap: 20px;
         margin-bottom: 30px;
      }
      
      .stock-card {
         background: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         text-align: center;
      }
      
      .stock-card h3 {
         margin-bottom: 10px;
         color: #333;
      }
      
      .stock-number {
         font-size: 2em;
         font-weight: bold;
         margin: 10px 0;
      }
      
      .stock-in { color: #28a745; }
      .stock-low { color: #ffc107; }
      .stock-out { color: #dc3545; }
      
      .stock-actions {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 20px;
         margin-bottom: 30px;
      }
      
      .action-form {
         background: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .stock-history {
         background: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .history-item {
         display: flex;
         justify-content: space-between;
         align-items: center;
         padding: 10px 0;
         border-bottom: 1px solid #eee;
      }
      
      .history-item:last-child {
         border-bottom: none;
      }
      
      .action-type {
         padding: 4px 8px;
         border-radius: 4px;
         font-size: 0.8em;
         font-weight: bold;
      }
      
      .action-restock { background: #d4edda; color: #155724; }
      .action-sale { background: #f8d7da; color: #721c24; }
      .action-adjustment { background: #fff3cd; color: #856404; }
      .action-return { background: #d1ecf1; color: #0c5460; }
      
      .product-info {
         background: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         margin-bottom: 20px;
      }
      
      .product-info img {
         width: 100px;
         height: 100px;
         object-fit: cover;
         border-radius: 10px;
         margin-right: 20px;
      }
      
      .product-details {
         display: flex;
         align-items: center;
      }
   </style>

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="stock-management">

   <div class="product-info">
      <div class="product-details">
         <img src="../uploaded_img/<?= $product['image_01']; ?>" alt="<?= $product['name']; ?>">
         <div>
            <h1><?= $product['name']; ?></h1>
            <p><strong>SKU:</strong> <?= $product['sku']; ?></p>
            <p><strong>Category:</strong> <?= $product['category']; ?></p>
            <p><strong>Price:</strong> Nrs. <?= $product['price']; ?>/-</p>
         </div>
      </div>
   </div>

   <div class="stock-dashboard">
      <div class="stock-card">
         <h3>Current Stock</h3>
         <div class="stock-number <?= $product['stock_status'] == 'in_stock' ? 'stock-in' : ($product['stock_status'] == 'low_stock' ? 'stock-low' : 'stock-out'); ?>">
            <?= $product['stock_quantity']; ?>
         </div>
         <p><?= ucfirst(str_replace('_', ' ', $product['stock_status'])); ?></p>
      </div>
      
      <div class="stock-card">
         <h3>Minimum Stock Level</h3>
         <div class="stock-number"><?= $product['min_stock_level']; ?></div>
         <p>Reorder Point</p>
      </div>
      
      <div class="stock-card">
         <h3>Last Restocked</h3>
         <div class="stock-number">
            <?= $product['last_restocked'] ? date('M d, Y', strtotime($product['last_restocked'])) : 'Never'; ?>
         </div>
         <p>Stock Update</p>
      </div>
      
      <div class="stock-card">
         <h3>Stock Alerts</h3>
         <div class="stock-number"><?= count($alerts); ?></div>
         <p>Unread Alerts</p>
      </div>
   </div>

   <div class="stock-actions">
      <div class="action-form">
         <h2>Restock Product</h2>
         <form action="" method="POST">
            <div class="inputBox">
               <span>Quantity to Add</span>
               <input type="number" name="restock_quantity" min="1" class="box" required>
            </div>
            <div class="inputBox">
               <span>Notes (optional)</span>
               <textarea name="notes" class="box" placeholder="e.g., New shipment received"></textarea>
            </div>
            <input type="submit" value="Restock" class="btn" name="restock">
         </form>
      </div>
      
      <div class="action-form">
         <h2>Adjust Stock</h2>
         <form action="" method="POST">
            <div class="inputBox">
               <span>Action</span>
               <select name="adjustment_type" class="box" required>
                  <option value="add">Add Stock</option>
                  <option value="subtract">Subtract Stock</option>
               </select>
            </div>
            <div class="inputBox">
               <span>Quantity</span>
               <input type="number" name="adjustment_quantity" min="1" class="box" required>
            </div>
            <div class="inputBox">
               <span>Notes (required)</span>
               <textarea name="notes" class="box" placeholder="Reason for adjustment" required></textarea>
            </div>
            <input type="submit" value="Adjust Stock" class="btn" name="adjust_stock">
         </form>
      </div>
   </div>

   <div class="stock-history">
      <h2>Stock History</h2>
      <?php if(empty($stock_history)): ?>
         <p class="empty">No stock history available.</p>
      <?php else: ?>
         <?php foreach($stock_history as $history): ?>
            <div class="history-item">
               <div>
                  <span class="action-type action-<?= $history['action_type']; ?>">
                     <?= ucfirst($history['action_type']); ?>
                  </span>
                  <strong><?= $history['quantity_change']; ?></strong> units
                  <?php if($history['notes']): ?>
                     - <?= $history['notes']; ?>
                  <?php endif; ?>
               </div>
               <div>
                  <small>
                     <?= $history['previous_stock']; ?> → <?= $history['new_stock']; ?>
                     <br>
                     <?= date('M d, Y H:i', strtotime($history['created_at'])); ?>
                     <?php if($history['admin_name']): ?>
                        by <?= $history['admin_name']; ?>
                     <?php endif; ?>
                  </small>
               </div>
            </div>
         <?php endforeach; ?>
      <?php endif; ?>
   </div>

</section>

<script src="../js/admin_script.js"></script>
   
</body>
</html>
