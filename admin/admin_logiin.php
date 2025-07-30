<?php
include '../components/connect.php';
session_start();

$message = [];

if(isset($_POST['submit'])){
   $name = $_POST['name'];
   $name = filter_var($name, FILTER_SANITIZE_STRING);
   $pass = sha1($_POST['pass']);
   $pass = filter_var($pass, FILTER_SANITIZE_STRING);
   
   try {
      $select_admin = $conn->prepare("SELECT * FROM `admins` WHERE name = ? AND password = ?");
      $select_admin->execute([$name, $pass]);
      $row = $select_admin->fetch(PDO::FETCH_ASSOC);
      
      if($select_admin->rowCount() > 0){
         $_SESSION['admin_id'] = $row['id'];
         header('location:dashboard.php');
         exit();
      } else {
         $message[] = 'Incorrect username or password!';
      }
   } catch(PDOException $e) {
      $message[] = 'Database connection failed!';
   }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Login</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <style>
   /* ============================================
      MODERN ADMIN LOGIN CSS - EMBEDDED
      ============================================ */
   
   * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
   }

   body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
      background-size: 400% 400%;
      animation: gradientShift 15s ease infinite;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
   }

   @keyframes gradientShift {
      0% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
      100% { background-position: 0% 50%; }
   }

   /* Floating Background Elements */
   body::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: float 20s linear infinite;
      z-index: 1;
   }

   @keyframes float {
      0% { transform: translate(0, 0) rotate(0deg); }
      100% { transform: translate(-50px, -50px) rotate(360deg); }
   }

   /* Message Notifications */
   .message {
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #ff6b6b, #ee5a52);
      color: white;
      padding: 1.5rem 2rem;
      border-radius: 15px;
      display: flex;
      align-items: center;
      gap: 1rem;
      box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      z-index: 1000;
      animation: slideIn 0.5s ease-out;
      max-width: 300px;
   }

   .message span {
      font-size: 1.4rem;
      font-weight: 500;
   }

   .message i {
      cursor: pointer;
      font-size: 1.6rem;
      transition: transform 0.3s ease;
      opacity: 0.8;
   }

   .message i:hover {
      transform: scale(1.2);
      opacity: 1;
   }

   @keyframes slideIn {
      from {
         transform: translateX(100%);
         opacity: 0;
      }
      to {
         transform: translateX(0);
         opacity: 1;
      }
   }

   /* Main login and register css */
   .form-container {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem;
      position: relative;
      z-index: 10;
   }

   .form-container form {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 25px;
      padding: 3rem 2.5rem;
      width: 100%;
      max-width: 450px;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
      position: relative;
      overflow: hidden;
      animation: slideUp 0.8s ease-out;
   }

   @keyframes slideUp {
      from {
         opacity: 0;
         transform: translateY(50px);
      }
      to {
         opacity: 1;
         transform: translateY(0);
      }
   }

   /* Animated Border */
   .form-container form::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c);
      background-size: 400% 400%;
      border-radius: 25px;
      padding: 2px;
      z-index: -1;
      animation: borderGlow 3s ease-in-out infinite;
   }

   @keyframes borderGlow {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
   }

   /* Form Header */
   .form-container form h3 {
      color: white;
      font-size: 2.8rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 1rem;
      text-transform: uppercase;
      letter-spacing: 3px;
      text-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
      position: relative;
   }

   .form-container form h3::after {
      content: '🔐';
      position: absolute;
      top: -50px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 3.5rem;
      animation: bounce 2s infinite;
   }

   @keyframes bounce {
      0%, 20%, 50%, 80%, 100% {
         transform: translateX(-50%) translateY(0);
      }
      40% {
         transform: translateX(-50%) translateY(-15px);
      }
      60% {
         transform: translateX(-50%) translateY(-8px);
      }
   }

   /* Default Credentials Box */
   .form-container form p {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 15px;
      padding: 1.5rem;
      margin: 1.5rem 0 2.5rem 0;
      color: rgba(255, 255, 255, 0.9);
      font-size: 1.3rem;
      text-align: center;
      backdrop-filter: blur(10px);
      line-height: 1.6;
   }

   .form-container form p span {
      color: #fff;
      font-weight: 700;
      background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
      padding: 0.4rem 1rem;
      border-radius: 20px;
      font-family: 'Courier New', monospace;
      border: 1px solid rgba(255, 255, 255, 0.3);
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
   }

   /* Input Fields */
   .form-container form .box {
      width: 100%;
      padding: 1.5rem 2rem;
      margin: 1.2rem 0;
      background: rgba(255, 255, 255, 0.1);
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 15px;
      color: white;
      font-size: 1.6rem;
      backdrop-filter: blur(10px);
      transition: all 0.3s ease;
      position: relative;
   }

   .form-container form .box::placeholder {
      color: rgba(255, 255, 255, 0.7);
      font-style: italic;
   }

   .form-container form .box:focus {
      outline: none;
      border-color: rgba(255, 255, 255, 0.5);
      background: rgba(255, 255, 255, 0.15);
      box-shadow: 0 0 25px rgba(255, 255, 255, 0.2);
      transform: translateY(-3px);
   }

   .form-container form .box:hover {
      border-color: rgba(255, 255, 255, 0.4);
      background: rgba(255, 255, 255, 0.12);
   }

   /* Input Icons */
   .form-container form .box[name="name"] {
      padding-left: 4rem;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' /%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 1.5rem center;
      background-size: 2rem;
   }

   .form-container form .box[name="pass"] {
      padding-left: 4rem;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' /%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: 1.5rem center;
      background-size: 2rem;
   }

   /* Submit Button */
   .form-container form .btn {
      width: 100%;
      padding: 1.6rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 15px;
      font-size: 1.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 2px;
      cursor: pointer;
      margin-top: 2rem;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
   }

   .form-container form .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.5s;
   }

   .form-container form .btn:hover::before {
      left: 100%;
   }

   .form-container form .btn:hover {
      transform: translateY(-4px);
      box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
      background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
   }

   .form-container form .btn:active {
      transform: translateY(-2px);
   }

   /* Loading State */
   .form-container form .btn.loading {
      opacity: 0.8;
      cursor: not-allowed;
      background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
   }

   /* Responsive Design */
   @media (max-width: 768px) {
      .form-container {
         padding: 1rem;
      }
      
      .form-container form {
         padding: 2rem 1.5rem;
         max-width: 100%;
         margin: 1rem;
      }
      
      .form-container form h3 {
         font-size: 2.2rem;
         letter-spacing: 2px;
      }
      
      .form-container form .box {
         padding: 1.3rem 1.8rem;
         font-size: 1.5rem;
         margin: 1rem 0;
      }
      
      .form-container form .box[name="name"],
      .form-container form .box[name="pass"] {
         padding-left: 3.5rem;
         background-size: 1.8rem;
         background-position: 1.2rem center;
      }
      
      .form-container form .btn {
         font-size: 1.6rem;
         padding: 1.4rem;
         letter-spacing: 1px;
      }
      
      .message {
         position: fixed;
         top: 10px;
         right: 10px;
         left: 10px;
         padding: 1.2rem;
         max-width: none;
      }
   }

   @media (max-width: 480px) {
      .form-container form h3 {
         font-size: 1.9rem;
      }
      
      .form-container form p {
         font-size: 1.2rem;
         padding: 1.2rem;
      }
      
      .form-container form .box {
         font-size: 1.4rem;
      }
   }

   /* Custom Scrollbar */
   ::-webkit-scrollbar {
      width: 8px;
   }

   ::-webkit-scrollbar-track {
      background: rgba(255, 255, 255, 0.1);
   }

   ::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 4px;
   }

   ::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #764ba2, #667eea);
   }
   </style>
</head>
<body>

<!-- Display Messages -->
<?php
if(!empty($message)){
   foreach($message as $msg){
      echo '
      <div class="message">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}
?>

<section class="form-container">
   <form action="" method="POST">
      <h3>Admin Login</h3>
      <p>Default username = <span>admin</span> & password = <span>111</span></p>
      <input type="text" name="name" required placeholder="Enter your username" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      <input type="password" name="pass" required placeholder="Enter your password" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      <input type="submit" value="Login Now" class="btn" name="submit">
   </form>
</section>

<script>
// Auto-hide messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
   const messages = document.querySelectorAll('.message');
   messages.forEach(function(message) {
      setTimeout(function() {
         message.style.opacity = '0';
         message.style.transform = 'translateX(100%)';
         setTimeout(function() {
            message.remove();
         }, 300);
      }, 5000);
   });
});

// Add loading state to button
document.querySelector('.btn').addEventListener('click', function(e) {
   const form = this.closest('form');
   const username = form.querySelector('input[name="name"]').value;
   const password = form.querySelector('input[name="pass"]').value;
   
   if(username && password) {
      this.classList.add('loading');
      this.style.opacity = '0.8';
      this.value = 'Logging in...';
      
      // Prevent multiple clicks
      this.disabled = true;
      
      // Re-enable after 3 seconds in case of slow response
      setTimeout(() => {
         this.disabled = false;
         this.classList.remove('loading');
         this.style.opacity = '1';
         this.value = 'Login Now';
      }, 3000);
   }
});

// Add enter key support
document.addEventListener('keypress', function(e) {
   if (e.key === 'Enter') {
      document.querySelector('.btn').click();
   }
});

// Add smooth focus transitions
document.querySelectorAll('.box').forEach(input => {
   input.addEventListener('focus', function() {
      this.style.transform = 'translateY(-3px)';
   });
   
   input.addEventListener('blur', function() {
      this.style.transform = 'translateY(0)';
   });
});
</script>

</body>
</html>