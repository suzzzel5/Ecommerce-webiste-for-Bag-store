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
   <style>
      .dashboard-container {
         display: flex;
         min-height: 100vh;
         background: #f5f5f5;
      }
      
      .sidebar {
         width: 280px;
         background: #2c3e50;
         color: white;
         padding: 2rem 0;
         position: fixed;
         height: 100vh;
         overflow-y: auto;
      }
      
      .sidebar-header {
         text-align: center;
         padding: 0 2rem 2rem;
         border-bottom: 1px solid #34495e;
         margin-bottom: 2rem;
      }
      
      .sidebar-header h3 {
         color: #ecf0f1;
         font-size: 2.2rem;
         margin-bottom: 0.5rem;
      }
      
      .sidebar-header p {
         color: #bdc3c7;
         font-size: 1.4rem;
      }
      
      .sidebar-nav {
         padding: 0 2rem;
      }
      
      .nav-item {
         margin-bottom: 0.5rem;
      }
      
      .nav-link {
         display: flex;
         align-items: center;
         padding: 1.2rem 1.5rem;
         color: #bdc3c7;
         text-decoration: none;
         border-radius: 8px;
         transition: all 0.3s ease;
         font-size: 1.6rem;
      }
      
      .nav-link:hover {
         background: #34495e;
         color: #ecf0f1;
         transform: translateX(5px);
      }
      
      .nav-link.active {
         background: #3498db;
         color: white;
      }
      
      .nav-link i {
         margin-right: 1rem;
         width: 20px;
         text-align: center;
      }
      
      .main-content {
         flex: 1;
         margin-left: 280px;
         padding: 2rem;
      }
      
      .dashboard-header {
         background: white;
         padding: 2rem;
         border-radius: 12px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         margin-bottom: 2rem;
         text-align: center;
      }
      
      .dashboard-header h1 {
         font-size: 3rem;
         color: #2c3e50;
         margin-bottom: 1rem;
      }
      
      .stats-grid {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
         gap: 1.5rem;
         margin-bottom: 3rem;
      }
      
      .stat-card {
         background: white;
         padding: 1.5rem;
         border-radius: 10px;
         text-align: center;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
         border-left: 4px solid #3498db;
      }
      
      .stat-card h3 {
         font-size: 2.5rem;
         color: #2c3e50;
         margin-bottom: 0.5rem;
      }
      
      .stat-card p {
         color: #7f8c8d;
         font-size: 1.4rem;
         margin: 0;
      }
      
      .charts-section {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 2rem;
         margin-bottom: 3rem;
      }
      
      .chart-container {
         background: white;
         padding: 2rem;
         border-radius: 12px;
         box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      }
      
      .chart-container h3 {
         font-size: 2rem;
         color: #2c3e50;
         margin-bottom: 1.5rem;
         text-align: center;
      }
      
      @media (max-width: 768px) {
         .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
         }
         
         .sidebar.active {
            transform: translateX(0);
         }
         
         .main-content {
            margin-left: 0;
         }
         
         .charts-section {
            grid-template-columns: 1fr;
         }
         
         .stats-grid {
            grid-template-columns: repeat(2, 1fr);
         }
      }
   </style>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<div class="dashboard-container">
   <!-- Left Sidebar -->
   <div class="sidebar">
      <div class="sidebar-header">
         <h3>Admin Panel</h3>
         <p><?= htmlspecialchars($fetch_profile['name']) ?></p>
      </div>
      
      <nav class="sidebar-nav">
         <div class="nav-item">
            <a href="dashboard.php" class="nav-link active">
               <i class="fas fa-tachometer-alt"></i>
               Dashboard
            </a>
         </div>
         
         <div class="nav-item">
            <a href="products.php" class="nav-link">
               <i class="fas fa-box"></i>
               Products
            </a>
         </div>
         
         <div class="nav-item">
            <a href="placed_orders.php" class="nav-link">
               <i class="fas fa-shopping-cart"></i>
               Orders
            </a>
         </div>
         
         <div class="nav-item">
            <a href="users_accounts.php" class="nav-link">
               <i class="fas fa-users"></i>
               Users
            </a>
         </div>
         
         <div class="nav-item">
            <a href="admin_accounts.php" class="nav-link">
               <i class="fas fa-user-shield"></i>
               Admins
            </a>
         </div>
         
         <div class="nav-item">
            <a href="messages.php" class="nav-link">
               <i class="fas fa-envelope"></i>
               Messages
            </a>
         </div>
         
         <div class="nav-item">
            <a href="update_profile.php" class="nav-link">
               <i class="fas fa-user-edit"></i>
               Update Profile
            </a>
         </div>
         
         <div class="nav-item">
            <a href="admin_logout.php" class="nav-link">
               <i class="fas fa-sign-out-alt"></i>
               Logout
            </a>
         </div>
      </nav>
   </div>

   <!-- Main Content -->
   <div class="main-content">
      <div class="dashboard-header">
         <h1>Dashboard Overview</h1>
         <p>Welcome back, <?= htmlspecialchars($fetch_profile['name']) ?>!</p>
      </div>

      <!-- Statistics Cards -->
      <div class="stats-grid">
         <div class="stat-card">
            <h3>Nrs.<?= $total_pendings; ?>/-</h3>
            <p>Pending Orders</p>
         </div>
         
         <div class="stat-card">
            <h3>Nrs.<?= $total_completes; ?>/-</h3>
            <p>Completed Orders</p>
         </div>
         
         <div class="stat-card">
            <h3><?= $number_of_orders; ?></h3>
            <p>Total Orders</p>
         </div>
         
         <div class="stat-card">
            <h3><?= $number_of_products; ?></h3>
            <p>Products</p>
         </div>
         
         <div class="stat-card">
            <h3><?= $number_of_users; ?></h3>
            <p>Users</p>
         </div>
         
         <div class="stat-card">
            <h3><?= $number_of_messages; ?></h3>
            <p>Messages</p>
         </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-section">
         <div class="chart-container">
            <h3>Sales Overview</h3>
            <canvas id="salesChart"></canvas>
         </div>

         <div class="chart-container">
            <h3>Top Users by Purchase</h3>
            <canvas id="userChart"></canvas>
         </div>
      </div>
   </div>
</div>

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
         plugins: {
            legend: {
               position: 'bottom'
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
         scales: {
            y: {
               beginAtZero: true,
               ticks: {
                  callback: value => 'Nrs. ' + value
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