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
