<?php

include '../components/connect.php';
session_start();

$admin_id = $_SESSION['admin_id'] ?? null;

if(!$admin_id){
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
   $total_pendings += (float)$row['total_price'];
}

$select_completes = $conn->prepare("SELECT total_price FROM `orders` WHERE payment_status = ?");
$select_completes->execute(['completed']);
foreach ($select_completes->fetchAll(PDO::FETCH_ASSOC) as $row) {
   $total_completes += (float)$row['total_price'];
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
   $user_totals[] = (float)$user['total_spent'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Dashboard</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/admin_style.css">
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="dashboard">
   <h1 class="heading">Dashboard</h1>

   <div class="dashboard-wrapper">
      <aside class="left-panel">
         <div class="box">
            <h3>Welcome!</h3>
            <p><?= htmlspecialchars($fetch_profile['name'] ?? '') ?></p>
            <a href="update_profile.php" class="btn">Update Profile</a>
         </div>

         <div class="box">
            <h3>Nrs.<?= number_format($total_pendings, 2) ?></h3>
            <p>Total Pendings</p>
            <a href="placed_orders.php" class="btn">See Orders</a>
         </div>

         <div class="box">
            <h3>Nrs.<?= number_format($total_completes, 2) ?></h3>
            <p>Completed Orders</p>
            <a href="placed_orders.php" class="btn">See Orders</a>
         </div>

         <div class="box">
            <h3><?= $number_of_orders; ?></h3>
            <p>Total Orders</p>
            <a href="placed_orders.php" class="btn">See Orders</a>
         </div>

         <div class="box">
            <h3><?= $number_of_products; ?></h3>
            <p>Products Added</p>
            <a href="products.php" class="btn">See Products</a>
         </div>

         <div class="box">
            <h3><?= $number_of_users; ?></h3>
            <p>Normal Users</p>
            <a href="users_accounts.php" class="btn">See Users</a>
         </div>

         <div class="box">
            <h3><?= $number_of_admins; ?></h3>
            <p>Admin Users</p>
            <a href="admin_accounts.php" class="btn">See Admins</a>
         </div>

         <div class="box">
            <h3><?= $number_of_messages; ?></h3>
            <p>New Messages</p>
            <a href="messages.php" class="btn">See Messages</a>
         </div>
      </aside>

      <main class="middle-content">
         <div class="top-charts">
            <div class="chart-container">
               <h3>Sales Overview</h3>
               <div class="chart-wrapper">
                  <canvas id="salesChart"></canvas>
               </div>
            </div>

            <div class="chart-container">
               <h3>Top Users by Purchase</h3>
               <div class="chart-wrapper">
                  <canvas id="userChart"></canvas>
               </div>
            </div>
         </div>
      </main>
   </div>
</section>

<script>
   const salesCtx = document.getElementById('salesChart').getContext('2d');
   new Chart(salesCtx, {
      type: 'doughnut',
      data: {
         labels: ['Pending Sales', 'Completed Sales'],
         datasets: [{
            label: 'Sales Overview',
            data: [<?= (float)$total_pendings ?>, <?= (float)$total_completes ?>],
            backgroundColor: ['#f39c12', '#2ecc71'],
            borderColor: ['#e67e22', '#27ae60'],
            borderWidth: 1
         }]
      },
      options: {
         responsive: true,
         maintainAspectRatio: false,
         plugins: {
            legend: { position: 'bottom' }
         }
      }
   });
</script>
<script>
   const userCtx = document.getElementById('userChart').getContext('2d');
   new Chart(userCtx, {
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
               ticks: { callback: value => 'Nrs. ' + value }
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