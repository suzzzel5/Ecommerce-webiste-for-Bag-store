<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
   exit();
}

// Mark alert as read
if(isset($_GET['mark_read'])) {
   $alert_id = $_GET['mark_read'];
   $update_alert = $conn->prepare("UPDATE `stock_alerts` SET is_read = 1 WHERE id = ?");
   $update_alert->execute([$alert_id]);
   header('location:stock_alerts.php');
   exit();
}

// Mark all alerts as read
if(isset($_GET['mark_all_read'])) {
   $update_all_alerts = $conn->prepare("UPDATE `stock_alerts` SET is_read = 1 WHERE is_read = 0");
   $update_all_alerts->execute();
   header('location:stock_alerts.php');
   exit();
}

// Clear resolved alerts (where stock is now sufficient)
if(isset($_GET['clear_resolved'])) {
   $clear_resolved = $conn->prepare("
      UPDATE `stock_alerts` sa 
      JOIN `products` p ON sa.product_id = p.id 
      SET sa.is_read = 1 
      WHERE p.stock_quantity > p.min_stock_level 
      AND sa.alert_type IN ('low_stock', 'out_of_stock')
   ");
   $clear_resolved->execute();
   header('location:stock_alerts.php');
   exit();
}

// Get all stock alerts with product details (only for products that are actually low/out of stock)
$select_alerts = $conn->prepare("
   SELECT sa.*, p.name as product_name, p.sku, p.stock_quantity, p.min_stock_level, p.image_01
   FROM `stock_alerts` sa
   JOIN `products` p ON sa.product_id = p.id
   WHERE (sa.alert_type = 'low_stock' AND p.stock_quantity <= p.min_stock_level AND p.stock_quantity > 0)
      OR (sa.alert_type = 'out_of_stock' AND p.stock_quantity = 0)
   ORDER BY sa.created_at DESC
");
$select_alerts->execute();
$alerts = $select_alerts->fetchAll(PDO::FETCH_ASSOC);

// Get active alert statistics (only for products that are currently low/out of stock)
$select_active_stats = $conn->prepare("
   SELECT 
      COUNT(*) as total_alerts,
      SUM(CASE WHEN sa.is_read = 0 THEN 1 ELSE 0 END) as unread_alerts,
      SUM(CASE WHEN sa.alert_type = 'low_stock' THEN 1 ELSE 0 END) as low_stock_count,
      SUM(CASE WHEN sa.alert_type = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock_count
   FROM `stock_alerts` sa
   JOIN `products` p ON sa.product_id = p.id
   WHERE (sa.alert_type = 'low_stock' AND p.stock_quantity <= p.min_stock_level AND p.stock_quantity > 0)
      OR (sa.alert_type = 'out_of_stock' AND p.stock_quantity = 0)
");
$select_active_stats->execute();
$active_stats = $select_active_stats->fetch(PDO::FETCH_ASSOC);

// Get total historical alerts
$select_total_stats = $conn->prepare("
   SELECT 
      COUNT(*) as total_historical_alerts,
      SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as total_unread_alerts
   FROM `stock_alerts`
");
$select_total_stats->execute();
$total_stats = $select_total_stats->fetch(PDO::FETCH_ASSOC);

// Get low stock products
$select_low_stock = $conn->prepare("
   SELECT * FROM `products` 
   WHERE stock_quantity <= min_stock_level 
   ORDER BY stock_quantity ASC
");
$select_low_stock->execute();
$low_stock_products = $select_low_stock->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Stock Alerts</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">

   <style>
      .alerts-dashboard {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
         gap: 20px;
         margin-bottom: 30px;
      }
      
      .alert-card {
         background: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         text-align: center;
      }
      
      .alert-card h3 {
         margin-bottom: 10px;
         color: #333;
      }
      
      .alert-number {
         font-size: 2em;
         font-weight: bold;
         margin: 10px 0;
      }
      
      .total-alerts { color: #007bff; }
      .unread-alerts { color: #dc3545; }
      .low-stock { color: #ffc107; }
      .out-of-stock { color: #dc3545; }
      
      .alerts-container {
         display: grid;
         grid-template-columns: 2fr 1fr;
         gap: 20px;
      }
      
      .alerts-list {
         background: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .alert-item {
         display: flex;
         align-items: center;
         padding: 15px;
         border-bottom: 1px solid #eee;
         transition: background-color 0.3s;
      }
      
      .alert-item:hover {
         background-color: #f8f9fa;
      }
      
      .alert-item:last-child {
         border-bottom: none;
      }
      
      .alert-item.unread {
         background-color: #fff3cd;
         border-left: 4px solid #ffc107;
      }
      
      .alert-icon {
         font-size: 1.5em;
         margin-right: 15px;
         width: 40px;
         text-align: center;
      }
      
      .alert-low { color: #ffc107; }
      .alert-out { color: #dc3545; }
      
      .alert-content {
         flex: 1;
      }
      
      .alert-title {
         font-weight: bold;
         margin-bottom: 5px;
      }
      
      .alert-message {
         color: #666;
         font-size: 0.9em;
      }
      
      .alert-time {
         color: #999;
         font-size: 0.8em;
      }
      
      .alert-actions {
         display: flex;
         gap: 10px;
      }
      
      .low-stock-products {
         background: #fff;
         padding: 20px;
         border-radius: 10px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .product-item {
         display: flex;
         align-items: center;
         padding: 10px 0;
         border-bottom: 1px solid #eee;
      }
      
      .product-item:last-child {
         border-bottom: none;
      }
      
      .product-item img {
         width: 50px;
         height: 50px;
         object-fit: cover;
         border-radius: 5px;
         margin-right: 15px;
      }
      
      .product-info {
         flex: 1;
      }
      
      .product-name {
         font-weight: bold;
         margin-bottom: 5px;
      }
      
      .stock-info {
         font-size: 0.9em;
         color: #666;
      }
      
      .stock-critical {
         color: #dc3545;
         font-weight: bold;
      }
      
      .stock-warning {
         color: #ffc107;
         font-weight: bold;
      }
      
      .header-actions {
         display: flex;
         justify-content: space-between;
         align-items: center;
         margin-bottom: 20px;
      }
   </style>

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="stock-alerts">

   <div class="header-actions">
      <h1 class="heading">Stock Alerts</h1>
      <div>
         <a href="stock_alerts.php?clear_resolved=1" class="btn" onclick="return confirm('Clear all resolved alerts?')">
            <i class="fas fa-broom"></i> Clear Resolved
         </a>
         <a href="stock_alerts.php?mark_all_read=1" class="btn" onclick="return confirm('Mark all alerts as read?')">
            <i class="fas fa-check-double"></i> Mark All Read
         </a>
      </div>
   </div>

       <div class="alerts-dashboard">
       <div class="alert-card">
          <h3>Active Alerts</h3>
          <div class="alert-number total-alerts"><?= $active_stats['total_alerts']; ?></div>
          <p>Current Issues</p>
       </div>
       
       <div class="alert-card">
          <h3>Unread Alerts</h3>
          <div class="alert-number unread-alerts"><?= $active_stats['unread_alerts']; ?></div>
          <p>Require Attention</p>
       </div>
       
       <div class="alert-card">
          <h3>Low Stock</h3>
          <div class="alert-number low-stock"><?= $active_stats['low_stock_count']; ?></div>
          <p>Products</p>
       </div>
       
       <div class="alert-card">
          <h3>Out of Stock</h3>
          <div class="alert-number out-of-stock"><?= $active_stats['out_of_stock_count']; ?></div>
          <p>Products</p>
       </div>
    </div>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center;">
       <small style="color: #666;">
           Historical Data: <?= $total_stats['total_historical_alerts']; ?> total alerts created | 
          <?= $total_stats['total_unread_alerts']; ?> total unread alerts
       </small>
    </div>
    
   <div class="alerts-container">
             <div class="alerts-list">
          <h2>Recent Alerts</h2>
          <?php if(empty($alerts)): ?>
             <p class="empty"> No active stock alerts found. All products have sufficient stock!</p>
          <?php else: ?>
            <?php foreach($alerts as $alert): ?>
               <div class="alert-item <?= $alert['is_read'] ? '' : 'unread'; ?>">
                  <div class="alert-icon alert-<?= $alert['alert_type'] == 'low_stock' ? 'low' : 'out'; ?>">
                     <?= $alert['alert_type'] == 'low_stock' ? '⚠️' : '❌'; ?>
                  </div>
                  
                  <div class="alert-content">
                     <div class="alert-title">
                        <?= $alert['product_name']; ?> (<?= $alert['sku']; ?>)
                     </div>
                     <div class="alert-message">
                        <?= $alert['message']; ?>
                     </div>
                     <div class="alert-time">
                        <?= date('M d, Y H:i', strtotime($alert['created_at'])); ?>
                     </div>
                  </div>
                  
                  <div class="alert-actions">
                     <?php if(!$alert['is_read']): ?>
                        <a href="stock_alerts.php?mark_read=<?= $alert['id']; ?>" class="option-btn" title="Mark as Read">
                           <i class="fas fa-check"></i>
                        </a>
                     <?php endif; ?>
                     <a href="stock_management.php?product_id=<?= $alert['product_id']; ?>" class="option-btn" title="Manage Stock">
                        <i class="fas fa-cog"></i>
                     </a>
                  </div>
               </div>
            <?php endforeach; ?>
         <?php endif; ?>
      </div>
      
             <div class="low-stock-products">
          <h2>Low Stock Products</h2>
          <?php if(empty($low_stock_products)): ?>
             <p class="empty">All products have sufficient stock!</p>
          <?php else: ?>
            <?php foreach($low_stock_products as $product): ?>
               <div class="product-item">
                  <img src="../uploaded_img/<?= $product['image_01']; ?>" alt="<?= $product['name']; ?>">
                  
                  <div class="product-info">
                     <div class="product-name"><?= $product['name']; ?></div>
                     <div class="stock-info">
                        Stock: 
                        <span class="<?= $product['stock_quantity'] == 0 ? 'stock-critical' : 'stock-warning'; ?>">
                           <?= $product['stock_quantity']; ?>
                        </span>
                        / <?= $product['min_stock_level']; ?>
                     </div>
                  </div>
                  
                  <a href="stock_management.php?product_id=<?= $product['id']; ?>" class="option-btn">
                     Manage
                  </a>
               </div>
            <?php endforeach; ?>
         <?php endif; ?>
      </div>
   </div>

</section>

<script src="../js/admin_script.js"></script>
   
</body>
</html>
