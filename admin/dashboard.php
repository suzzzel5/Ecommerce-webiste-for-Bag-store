<?php

include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
   exit;
}

// Fetch admin info
$fetch_profile = [];
$select_profile = $conn->prepare("SELECT * FROM `admins` WHERE id = ?");
$select_profile->execute([$admin_id]);
if($select_profile->rowCount() > 0){
   $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
}

// Fetch dashboard data
$total_pendings = 0;
$total_completes = 0;

$select_pendings = $conn->prepare("SELECT total_price FROM `orders` WHERE payment_status = ?");
$select_pendings->execute(['pending']);
foreach ($select_pendings->fetchAll(PDO::FETCH_ASSOC) as $row) {
   $total_pendings += $row['total_price'];
}

$select_completes = $conn->prepare("SELECT total_price FROM `orders` WHERE payment_status = ?");
$select_completes->execute(['completed']);
foreach ($select_completes->fetchAll(PDO::FETCH_ASSOC) as $row) {
   $total_completes += $row['total_price'];
}

$select_orders = $conn->query("SELECT * FROM `orders`");
$number_of_orders = $select_orders->rowCount();

$select_products = $conn->query("SELECT * FROM `products`");
$number_of_products = $select_products->rowCount();

$select_users = $conn->query("SELECT * FROM `users`");
$number_of_users = $select_users->rowCount();

$select_admins = $conn->query("SELECT * FROM `admins`");
$number_of_admins = $select_admins->rowCount();

$select_messages = $conn->query("SELECT * FROM `messages`");
$number_of_messages = $select_messages->rowCount();

// Get top users by completed order spending
$users_data = [];
$select_users_spending = $conn->prepare("
   SELECT users.name AS username, SUM(orders.total_price) AS total_spent
   FROM orders
   JOIN users ON orders.user_id = users.id
   WHERE orders.payment_status = 'completed'
   GROUP BY orders.user_id
   ORDER BY total_spent DESC
   LIMIT 10
");
$select_users_spending->execute();
$users_data = $select_users_spending->fetchAll(PDO::FETCH_ASSOC);

// Prepare for chart
$user_names = [];
$user_totals = [];

foreach ($users_data as $user) {
   $user_names[] = $user['username'];
   $user_totals[] = $user['total_spent'];
}

// Get stock alerts data
$select_stock_alerts = $conn->prepare("
   SELECT sa.*, p.name as product_name, p.sku, p.stock_quantity, p.min_stock_level, p.image_01
   FROM `stock_alerts` sa
   JOIN `products` p ON sa.product_id = p.id
   WHERE (sa.alert_type = 'low_stock' AND p.stock_quantity <= p.min_stock_level AND p.stock_quantity > 0)
      OR (sa.alert_type = 'out_of_stock' AND p.stock_quantity = 0)
   ORDER BY sa.created_at DESC
   LIMIT 5
");
$select_stock_alerts->execute();
$stock_alerts = $select_stock_alerts->fetchAll(PDO::FETCH_ASSOC);

// Get stock alert statistics
$select_stock_stats = $conn->prepare("
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
$select_stock_stats->execute();
$stock_stats = $select_stock_stats->fetch(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Admin Dashboard</title>
   <link rel="stylesheet" href="../css/admin_style.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="dashboard">
   <h1 class="heading"></h1>

   <div class="dashboard-wrapper">
      
      <!-- LEFT PANEL WITH VERTICAL NAVIGATION -->
      <div class="left-panel">
         <div class="admin-profile">
            <div class="profile-info">
               <i class="fas fa-user-circle"></i>
               <h3><?= htmlspecialchars($fetch_profile['name']) ?></h3>
               <p>Administrator</p>
            </div>
            <a href="update_profile.php" class="btn">Update Profile</a>
         </div>

         <nav class="dashboard-nav">
            <ul>
               <li>
                  <a href="dashboard.php" class="active">
                     <i class="fas fa-tachometer-alt"></i>
                     <span>Dashboard</span>
                  </a>
               </li>
               <li>
                  <a href="placed_orders.php">
                     <i class="fas fa-shopping-cart"></i>
                     <span>Orders</span>
                     <span class="badge"><?= $number_of_orders ?></span>
                  </a>
               </li>
               <li>
                  <a href="products.php">
                     <i class="fas fa-box"></i>
                     <span>Products</span>
                     <span class="badge"><?= $number_of_products ?></span>
                  </a>
               </li>
               <li>
                  <a href="users_accounts.php">
                     <i class="fas fa-users"></i>
                     <span>Users</span>
                     <span class="badge"><?= $number_of_users ?></span>
                  </a>
               </li>
               <li>
                  <a href="admin_accounts.php">
                     <i class="fas fa-user-shield"></i>
                     <span>Admins</span>
                     <span class="badge"><?= $number_of_admins ?></span>
                  </a>
               </li>
               <li>
                  <a href="messages.php">
                     <i class="fas fa-envelope"></i>
                     <span>Messages</span>
                     <span class="badge"><?= $number_of_messages ?></span>
                  </a>
               </li>
               <li>
                  <a href="stock_alerts.php">
                     <i class="fas fa-exclamation-triangle"></i>
                     <span>Stock Alerts</span>
                     <span class="badge alert"><?= $stock_stats['unread_alerts'] ?? 0 ?></span>
                  </a>
               </li>
            </ul>
         </nav>   

         <div class="quick-stats">
            <div class="stat-item">
               <div class="stat-icon pending">
                  <i class="fas fa-clock"></i>
               </div>
               <div class="stat-details">
                  <h4>Pending</h4>
                  <p>Nrs.<?= $total_pendings; ?>/-</p>
               </div>
            </div>
            
            <div class="stat-item">
               <div class="stat-icon completed">
                  <i class="fas fa-check-circle"></i>
               </div>
               <div class="stat-details">
                  <h4>Completed</h4>
                  <p>Nrs.<?= $total_completes; ?>/-</p>
               </div>
            </div>
         </div>

         <!-- Stock Alerts Section -->
         <div class="stock-alerts-section">
            <div class="section-header">
               <h3><i class="fas fa-exclamation-triangle"></i> Stock Alerts</h3>
               <a href="stock_alerts.php" class="view-all">View All</a>
            </div>
            
            <?php if($stock_stats['total_alerts'] > 0): ?>
               <div class="alerts-summary">
                  <div class="alert-stat">
                     <span class="count"><?= $stock_stats['unread_alerts'] ?? 0 ?></span>
                     <span class="label">Unread</span>
                  </div>
                  <div class="alert-stat">
                     <span class="count low"><?= $stock_stats['low_stock_count'] ?? 0 ?></span>
                     <span class="label">Low Stock</span>
                  </div>
                  <div class="alert-stat">
                     <span class="count out"><?= $stock_stats['out_of_stock_count'] ?? 0 ?></span>
                     <span class="label">Out of Stock</span>
                  </div>
               </div>

               <div class="recent-alerts">
                  <?php foreach($stock_alerts as $alert): ?>
                     <div class="alert-item <?= $alert['is_read'] ? 'read' : 'unread' ?>">
                        <div class="alert-icon">
                           <i class="fas fa-<?= $alert['alert_type'] == 'out_of_stock' ? 'times-circle' : 'exclamation-circle' ?>"></i>
                        </div>
                        <div class="alert-content">
                           <h4><?= htmlspecialchars($alert['product_name']) ?></h4>
                           <p class="alert-message">
                              <?= $alert['alert_type'] == 'out_of_stock' ? 'Out of stock' : 'Low stock (' . $alert['stock_quantity'] . ' remaining)' ?>
                           </p>
                           <small class="alert-time"><?= date('M j, Y', strtotime($alert['created_at'])) ?></small>
                        </div>
                     </div>
                  <?php endforeach; ?>
               </div>
            <?php else: ?>
               <div class="no-alerts">
                  <i class="fas fa-check-circle"></i>
                  <p>No stock alerts</p>
                  <small>All products are well stocked</small>
               </div>
            <?php endif; ?>
         </div>
      </div>

      <!-- MIDDLE SECTION WITH CHARTS -->
      <div class="middle-section">
         <div class="charts-container">
            <div class="chart-card">
               <h3><i class="fas fa-chart-pie"></i> Sales Overview</h3>
               <div class="chart-wrapper">
                  <canvas id="salesChart"></canvas>
               </div>
            </div>

            <div class="chart-card">
               <h3><i class="fas fa-chart-bar"></i> Top Users by Purchase</h3>
               <div class="chart-wrapper">
                  <canvas id="userChart"></canvas>
               </div>
            </div>
         </div>
      </div>

   </div>
</section>

<script>
   const ctx = document.getElementById('salesChart').getContext('2d');
   const salesChart = new Chart(ctx, {
      type: 'doughnut',
      data: {
         labels: ['Pending Sales', 'Completed Sales'],
         datasets: [{
            label: 'Sales Overview',
            data: [<?= $total_pendings ?>, <?= $total_completes ?>],
            backgroundColor: ['#f39c12', '#2ecc71'],
            borderColor: ['#e67e22', '#27ae60'],
            borderWidth: 1
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         plugins: {
            legend: {
               position: 'bottom',
               labels: {
                  font: {
                     size: 12
                  }
               }
            }
         }
      }
   });
</script>
<script>
   const userCtx = document.getElementById('userChart').getContext('2d');

   const userChart = new Chart(userCtx, {
      type: 'bar',
      data: {
         labels: <?= json_encode($user_names); ?>,
         datasets: [{
            label: 'Total Purchase (Nrs.)',
            data: <?= json_encode($user_totals); ?>,
            backgroundColor: '#3498db',
            borderColor: '#2980b9',
            borderWidth: 1,
            borderRadius: 8,
            hoverBackgroundColor: '#2980b9'
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         scales: {
            y: {
               beginAtZero: true,
               ticks: {
                  callback: value => 'Nrs. ' + value,
                  font: {
                     size: 11
                  }
               }
            },
            x: {
               ticks: {
                  font: {
                     size: 10
                  }
               }
            }
         },
         plugins: {
            legend: { display: false },
            tooltip: {
               callbacks: {
                  label: context => 'Nrs. ' + context.parsed.y
               }
            }
         }
      }
   });
</script>

<script src="../js/admin_script.js"></script>

</body>
</html>
