<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
}

if(isset($_POST['update_payment'])){
   $order_id = $_POST['order_id'];
   $payment_status = $_POST['payment_status'];
   $payment_status = filter_var($payment_status, FILTER_SANITIZE_STRING);
   $update_payment = $conn->prepare("UPDATE `orders` SET payment_status = ? WHERE id = ?");
   $update_payment->execute([$payment_status, $order_id]);
   $message[] = 'payment status updated!';
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_id = filter_var($delete_id, FILTER_SANITIZE_NUMBER_INT);
   // Soft delete: hide order from admin, keep it visible for user
   $archive_order = $conn->prepare("UPDATE `orders` SET deleted_by_admin = 1 WHERE id = ?");
   $archive_order->execute([$delete_id]);
   header('location:placed_orders.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Placed Orders - Admin Dashboard</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">

   <style>
      .orders-hero {
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         padding: 4rem 2rem;
         text-align: center;
         color: white;
         position: relative;
         overflow: hidden;
      }

      .orders-hero::before {
         content: '';
         position: absolute;
         top: 0;
         left: 0;
         right: 0;
         bottom: 0;
         background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
         opacity: 0.3;
      }

      .hero-content {
         position: relative;
         z-index: 2;
      }

      .hero-title {
         font-size: 4.8rem;
         font-weight: 800;
         margin-bottom: 1rem;
         text-shadow: 0 2px 4px rgba(0,0,0,0.3);
      }

      .hero-subtitle {
         font-size: 1.8rem;
         opacity: 0.9;
         margin-bottom: 2rem;
      }

      .stats-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
         gap: 2rem;
         margin-top: 3rem;
      }

      .stat-card {
         background: rgba(255,255,255,0.1);
         backdrop-filter: blur(10px);
         border-radius: 20px;
         padding: 2rem;
         border: 1px solid rgba(255,255,255,0.2);
      }

      .stat-number {
         font-size: 3.6rem;
         font-weight: 700;
         color: #ffd700;
         display: block;
         margin-bottom: 0.5rem;
      }

      .stat-label {
         font-size: 1.4rem;
         opacity: 0.8;
         text-transform: uppercase;
         letter-spacing: 1px;
      }

      .orders-section {
         padding: 4rem 2rem;
         background: #f8f9fa;
         min-height: 100vh;
      }

      .orders-container {
         max-width: 1400px;
         margin: 0 auto;
      }

      .section-header {
         text-align: center;
         margin-bottom: 4rem;
      }

      .section-title {
         font-size: 3.6rem;
         font-weight: 700;
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         -webkit-background-clip: text;
         -webkit-text-fill-color: transparent;
         background-clip: text;
         margin-bottom: 1rem;
      }

      .section-subtitle {
         font-size: 1.8rem;
         color: #6c757d;
         max-width: 600px;
         margin: 0 auto;
         line-height: 1.6;
      }

      .orders-table-container {
         background: white;
         border-radius: 20px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
         overflow: hidden;
         margin-top: 2rem;
      }

      .orders-table {
         width: 100%;
         border-collapse: collapse;
         font-size: 1.4rem;
      }

             .orders-table thead {
          background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
          color: white;
       }

      .orders-table th {
         padding: 1.5rem 1rem;
         text-align: left;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 0.5px;
         font-size: 1.2rem;
      }

      .orders-table td {
         padding: 1.5rem 1rem;
         border-bottom: 1px solid #f8f9fa;
         vertical-align: middle;
      }

      .orders-table tbody tr {
         transition: all 0.3s ease;
      }

      .orders-table tbody tr:hover {
         background: #f8f9fa;
         transform: scale(1.01);
      }

      .orders-table tbody tr:last-child td {
         border-bottom: none;
      }

      .order-id {
         font-weight: 700;
         color: #2c3e50;
         font-size: 1.3rem;
      }

      .customer-name {
         font-weight: 600;
         color: #2c3e50;
      }

      .customer-phone {
         color: #6c757d;
         font-family: monospace;
      }

      .customer-address {
         max-width: 200px;
         color: #6c757d;
         line-height: 1.4;
      }

      .total-products {
         text-align: center;
         font-weight: 600;
         color: #2c3e50;
      }

      .total-price {
         font-weight: 700;
         color: #e74c3c;
         text-align: right;
      }

      .payment-method {
         text-transform: capitalize;
         color: #6c757d;
         font-weight: 500;
      }

      .status-badge {
         padding: 0.5rem 1rem;
         border-radius: 20px;
         font-size: 1.1rem;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 0.5px;
         text-align: center;
         display: inline-block;
         min-width: 100px;
      }

      .status-badge.pending {
         background: #fff3cd;
         color: #856404;
      }

      .status-badge.completed {
         background: #d4edda;
         color: #155724;
      }

      .order-date {
         color: #6c757d;
         font-size: 1.2rem;
      }

      .table-actions {
         display: flex;
         gap: 0.8rem;
         align-items: center;
         justify-content: center;
      }

      .status-select {
         padding: 0.8rem 1rem;
         border: 2px solid #e9ecef;
         border-radius: 10px;
         font-size: 1.2rem;
         background: white;
         color: #2c3e50;
         cursor: pointer;
         transition: all 0.3s ease;
         min-width: 120px;
      }

      .status-select:focus {
         outline: none;
         border-color: #667eea;
         box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      }

      .update-btn {
         padding: 0.8rem 1.5rem;
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         color: white;
         border: none;
         border-radius: 10px;
         font-size: 1.2rem;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
         text-transform: uppercase;
         letter-spacing: 0.5px;
      }

      .update-btn:hover {
         transform: translateY(-2px);
         box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
      }

      .delete-btn {
         padding: 0.8rem 1.5rem;
         background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
         color: white;
         border: none;
         border-radius: 10px;
         font-size: 1.2rem;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.3s ease;
         text-decoration: none;
         text-transform: uppercase;
         letter-spacing: 0.5px;
         display: inline-block;
      }

      .delete-btn:hover {
         transform: translateY(-2px);
         box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
      }

      .empty-state {
         text-align: center;
         padding: 6rem 2rem;
         background: white;
         border-radius: 20px;
         box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      }

      .empty-state i {
         font-size: 8rem;
         color: #dee2e6;
         margin-bottom: 2rem;
      }

      .empty-state h3 {
         font-size: 2.4rem;
         color: #6c757d;
         margin-bottom: 1rem;
      }

      .empty-state p {
         font-size: 1.6rem;
         color: #adb5bd;
         line-height: 1.6;
      }

      @media (max-width: 768px) {
         .hero-title {
            font-size: 3.2rem;
         }
         
         .section-title {
            font-size: 2.8rem;
         }
         
         .orders-table-container {
            overflow-x: auto;
         }
         
         .orders-table {
            min-width: 800px;
         }
         
         .table-actions {
            flex-direction: column;
            gap: 0.5rem;
         }
         
         .stats-grid {
            grid-template-columns: repeat(2, 1fr);
         }
      }
   </style>

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="orders-hero">
   <div class="hero-content">
      <h1 class="hero-title">Order Management</h1>
      <p class="hero-subtitle">Track and manage all customer orders efficiently</p>
      
      <?php
         // Do not count orders that admin has "deleted" (archived)
         $total_orders = $conn->prepare("SELECT COUNT(*) as total FROM `orders` WHERE deleted_by_admin = 0");
         $total_orders->execute();
         $total_count = $total_orders->fetch(PDO::FETCH_ASSOC)['total'];
         
         $pending_orders = $conn->prepare("SELECT COUNT(*) as pending FROM `orders` WHERE payment_status = 'pending' AND deleted_by_admin = 0");
         $pending_orders->execute();
         $pending_count = $pending_orders->fetch(PDO::FETCH_ASSOC)['pending'];
         
         $completed_orders = $conn->prepare("SELECT COUNT(*) as completed FROM `orders` WHERE payment_status = 'completed' AND deleted_by_admin = 0");
         $completed_orders->execute();
         $completed_count = $completed_orders->fetch(PDO::FETCH_ASSOC)['completed'];

         // Count archived (soft-deleted) orders for admin overview
         $archived_orders = $conn->prepare("SELECT COUNT(*) as archived FROM `orders` WHERE deleted_by_admin = 1");
         $archived_orders->execute();
         $archived_count = $archived_orders->fetch(PDO::FETCH_ASSOC)['archived'];
      ?>
      
      <div class="stats-grid">
         <div class="stat-card">
            <span class="stat-number"><?= $total_count; ?></span>
            <span class="stat-label">Total Orders</span>
         </div>
         <div class="stat-card">
            <span class="stat-number"><?= $pending_count; ?></span>
            <span class="stat-label">Pending</span>
         </div>
         <div class="stat-card">
            <span class="stat-number"><?= $completed_count; ?></span>
            <span class="stat-label">Completed</span>
         </div>
         <div class="stat-card">
            <span class="stat-number"><?= $archived_count; ?></span>
            <span class="stat-label">Archived</span>
         </div>
      </div>
   </div>
</div>

<section class="orders-section">
   <div class="orders-container">
      <div class="section-header">
         <h2 class="section-title">Placed Orders</h2>
         <p class="section-subtitle"></p>
      </div>

      <div class="orders-table-container">
         <?php
            // Show the most recently placed orders (newest users) at the top.
            // Exclude orders that admin has marked as deleted.
            $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE deleted_by_admin = 0 ORDER BY id DESC");
            $select_orders->execute();
            if($select_orders->rowCount() > 0){
         ?>
         <table class="orders-table">
            <thead>
               <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Phone</th>
                  <th>Address</th>
                  <th>Products</th>
                  <th>Total Amount</th>
                  <th>Payment Method</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
               </tr>
            </thead>
            <tbody>
               <?php while($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)){ ?>
               <tr>
                  <td>
                     <span class="order-id">#<?= $fetch_orders['id']; ?></span>
                  </td>
                  <td>
                     <span class="customer-name"><?= $fetch_orders['name']; ?></span>
                  </td>
                  <td>
                     <span class="customer-phone"><?= $fetch_orders['number']; ?></span>
                  </td>
                  <td>
                     <span class="customer-address"><?= $fetch_orders['address']; ?></span>
                  </td>
                  <td>
                     <span class="total-products"><?= $fetch_orders['total_products']; ?></span>
                  </td>
                  <td>
                     <span class="total-price">Nrs. <?= number_format($fetch_orders['total_price']); ?>/-</span>
                  </td>
                  <td>
                     <span class="payment-method"><?= ucfirst($fetch_orders['method']); ?></span>
                  </td>
                  <td>
                     <span class="status-badge <?= $fetch_orders['payment_status']; ?>"><?= ucfirst($fetch_orders['payment_status']); ?></span>
                  </td>
                  <td>
                     <span class="order-date"><?= date('M d, Y', strtotime($fetch_orders['placed_on'])); ?></span>
                  </td>
                  <td>
                     <div class="table-actions">
                        <form action="" method="post" style="display: flex; gap: 0.5rem; align-items: center;">
                           <input type="hidden" name="order_id" value="<?= $fetch_orders['id']; ?>">
                           <select name="payment_status" class="status-select">
                              <option value="<?= $fetch_orders['payment_status']; ?>" selected disabled>Current</option>
                              <option value="pending">Pending</option>
                              <option value="completed">Completed</option>
                           </select>
                           <input type="submit" value="Update" class="update-btn" name="update_payment">
                        </form>
                        <a href="placed_orders.php?delete=<?= $fetch_orders['id']; ?>" class="delete-btn" data-confirm="order-delete">
                           <i class="fas fa-trash"></i>
                        </a>
                     </div>
                  </td>
               </tr>
               <?php } ?>
            </tbody>
         </table>
         <?php } else { ?>
         <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>No Orders Yet</h3>
            <p>There are no orders placed by customers at the moment. Orders will appear here once customers start shopping.</p>
         </div>
         <?php } ?>

         <?php
            // Archived orders (soft-deleted by admin)
            $archived_orders_list = $conn->prepare("SELECT * FROM `orders` WHERE deleted_by_admin = 1 ORDER BY id DESC");
            $archived_orders_list->execute();
            if($archived_orders_list->rowCount() > 0){
         ?>
         <div class="orders-table-container" style="margin-top: 3rem;">
            <h3 class="section-title" style="font-size: 2.4rem; margin-bottom: 1.5rem;">Archived Orders</h3>
            <table class="orders-table">
               <thead>
                  <tr>
                     <th>Order ID</th>
                     <th>Customer</th>
                     <th>Phone</th>
                     <th>Address</th>
                     <th>Products</th>
                     <th>Total Amount</th>
                     <th>Payment Method</th>
                     <th>Status</th>
                     <th>Date</th>
                  </tr>
               </thead>
               <tbody>
                  <?php while($archived = $archived_orders_list->fetch(PDO::FETCH_ASSOC)){ ?>
                  <tr>
                     <td>
                        <span class="order-id">#<?= $archived['id']; ?></span>
                     </td>
                     <td>
                        <span class="customer-name"><?= $archived['name']; ?></span>
                     </td>
                     <td>
                        <span class="customer-phone"><?= $archived['number']; ?></span>
                     </td>
                     <td>
                        <span class="customer-address"><?= $archived['address']; ?></span>
                     </td>
                     <td>
                        <span class="total-products"><?= $archived['total_products']; ?></span>
                     </td>
                     <td>
                        <span class="total-price">Nrs. <?= number_format($archived['total_price']); ?>/-</span>
                     </td>
                     <td>
                        <span class="payment-method"><?= ucfirst($archived['method']); ?></span>
                     </td>
                     <td>
                        <span class="status-badge <?= $archived['payment_status']; ?>"><?= ucfirst($archived['payment_status']); ?></span>
                     </td>
                     <td>
                        <span class="order-date"><?= date('M d, Y', strtotime($archived['placed_on'])); ?></span>
                     </td>
                  </tr>
                  <?php } ?>
               </tbody>
            </table>
         </div>
         <?php } ?>
      </div>
   </div>
</section>

<script>
// SweetAlert confirm for deleting (archiving) an order in admin placed orders
document.addEventListener('DOMContentLoaded', function () {
   if (typeof Swal === 'undefined') return;
   var deleteLinks = document.querySelectorAll('.delete-btn[data-confirm="order-delete"]');
   deleteLinks.forEach(function (link) {
      link.addEventListener('click', function (e) {
         e.preventDefault();
         var href = this.getAttribute('href');
         Swal.fire({
            title: 'Archive this order?',
            text: 'This will hide the order from admin view but keep it for the customer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive',
            cancelButtonText: 'Cancel'
         }).then(function (result) {
            if (result.isConfirmed) {
               window.location.href = href;
            }
         });
      });
   });
});
</script>

<script src="../js/admin_script.js"></script>
   
</body>
</html>

