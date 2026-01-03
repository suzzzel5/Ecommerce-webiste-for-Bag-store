<?php

include '../components/connect.php';

session_start();

$admin_id = $_SESSION['admin_id'];

if(!isset($admin_id)){
   header('location:admin_login.php');
};

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_message = $conn->prepare("DELETE FROM `messages` WHERE id = ?");
   $delete_message->execute([$delete_id]);
   header('location:messages.php');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Messages</title>

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   <link rel="stylesheet" href="../css/admin_style.css">

   <style>
      /* Modern Messages Page Styles */
      .contacts {
         padding: 3rem 2rem;
         background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
         min-height: 100vh;
      }

      .contacts .heading {
         font-size: 3.5rem;
         color: white;
         margin-bottom: 3rem;
         text-align: center;
         text-transform: uppercase;
         font-weight: 600;
         text-shadow: 0 2px 4px rgba(0,0,0,0.3);
         letter-spacing: 2px;
      }

      .contacts .box-container {
         display: grid;
         grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
         gap: 2rem;
         max-width: 1400px;
         margin: 0 auto;
      }

      .contacts .box-container .box {
         background: white;
         border-radius: 20px;
         padding: 2.5rem;
         box-shadow: 0 15px 35px rgba(0,0,0,0.1);
         border: none;
         position: relative;
         overflow: hidden;
         transition: all 0.3s ease;
         animation: slideInUp 0.6s ease-out;
      }

      .contacts .box-container .box:hover {
         transform: translateY(-5px);
         box-shadow: 0 20px 40px rgba(0,0,0,0.15);
      }

      .contacts .box-container .box::before {
         content: '';
         position: absolute;
         top: 0;
         left: 0;
         right: 0;
         height: 4px;
         background: linear-gradient(45deg, #667eea, #764ba2);
      }

      .contacts .box-container .box .message-header {
         display: flex;
         align-items: center;
         justify-content: space-between;
         margin-bottom: 2rem;
         padding-bottom: 1rem;
         border-bottom: 2px solid #f8f9fa;
      }

      .contacts .box-container .box .message-header .user-info {
         display: flex;
         align-items: center;
         gap: 1rem;
      }

      .contacts .box-container .box .message-header .user-avatar {
         width: 50px;
         height: 50px;
         background: linear-gradient(45deg, #667eea, #764ba2);
         border-radius: 50%;
         display: flex;
         align-items: center;
         justify-content: center;
         color: white;
         font-size: 1.8rem;
         font-weight: 600;
      }

      .contacts .box-container .box .message-header .user-details h4 {
         font-size: 1.8rem;
         color: #2c3e50;
         font-weight: 600;
         margin-bottom: 0.5rem;
      }

      .contacts .box-container .box .message-header .user-details .email {
         font-size: 1.4rem;
         color: #6c757d;
      }

      .contacts .box-container .box .message-content {
         background: #f8f9fa;
         padding: 1.5rem;
         border-radius: 12px;
         margin: 1.5rem 0;
         border-left: 4px solid #667eea;
      }

      .contacts .box-container .box .message-content p {
         font-size: 1.6rem;
         color: #495057;
         line-height: 1.6;
         margin: 0;
      }

      .contacts .box-container .box .message-meta {
         display: grid;
         grid-template-columns: 1fr 1fr;
         gap: 1rem;
         margin: 1.5rem 0;
      }

      .contacts .box-container .box .message-meta .meta-item {
         background: white;
         padding: 1rem;
         border-radius: 8px;
         border: 2px solid #e9ecef;
         text-align: center;
      }

      .contacts .box-container .box .message-meta .meta-item .label {
         font-size: 1.2rem;
         color: #6c757d;
         font-weight: 500;
         margin-bottom: 0.5rem;
         text-transform: uppercase;
         letter-spacing: 0.5px;
      }

      .contacts .box-container .box .message-meta .meta-item .value {
         font-size: 1.4rem;
         color: #2c3e50;
         font-weight: 600;
      }

      .contacts .box-container .box .delete-btn {
         background: linear-gradient(45deg, #e74c3c, #c0392b);
         color: white;
         padding: 1rem 2rem;
         border-radius: 10px;
         font-size: 1.4rem;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 1px;
         transition: all 0.3s ease;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 0.5rem;
         margin-top: 1.5rem;
         border: none;
         cursor: pointer;
         width: 100%;
      }

      .contacts .box-container .box .delete-btn:hover {
         transform: translateY(-2px);
         box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
         background: linear-gradient(45deg, #c0392b, #e74c3c);
      }

      .contacts .box-container .box .delete-btn i {
         font-size: 1.6rem;
      }

      .contacts .empty {
         background: white;
         padding: 4rem 2rem;
         border-radius: 20px;
         text-align: center;
         color: #6c757d;
         font-size: 2rem;
         box-shadow: 0 15px 35px rgba(0,0,0,0.1);
         margin: 2rem auto;
         max-width: 500px;
         animation: slideInUp 0.6s ease-out;
      }

      .contacts .empty i {
         font-size: 4rem;
         color: #dee2e6;
         margin-bottom: 1rem;
         display: block;
      }

      /* Animation */
      @keyframes slideInUp {
         from {
            opacity: 0;
            transform: translateY(30px);
         }
         to {
            opacity: 1;
            transform: translateY(0);
         }
      }

      /* Responsive Design */
      @media (max-width: 768px) {
         .contacts .box-container {
            grid-template-columns: 1fr;
            padding: 0 1rem;
         }
         
         .contacts .heading {
            font-size: 2.8rem;
         }
         
         .contacts .box-container .box {
            padding: 2rem;
         }
         
         .contacts .box-container .box .message-meta {
            grid-template-columns: 1fr;
         }
      }

      /* Message counter badge */
      .message-counter {
         position: fixed;
         top: 2rem;
         right: 2rem;
         background: linear-gradient(45deg, #e74c3c, #c0392b);
         color: white;
         padding: 1rem 1.5rem;
         border-radius: 25px;
         font-size: 1.4rem;
         font-weight: 600;
         box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);
         z-index: 1000;
         animation: pulse 2s infinite;
      }

      @keyframes pulse {
         0% { transform: scale(1); }
         50% { transform: scale(1.05); }
         100% { transform: scale(1); }
      }
   </style>

</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="contacts">

   <h1 class="heading">Customer Messages</h1>

   <?php
      $select_messages = $conn->prepare("SELECT * FROM `messages` ORDER BY id DESC");
      $select_messages->execute();
      $message_count = $select_messages->rowCount();
   ?>
   
   <div class="message-counter">
      <i class="fas fa-envelope"></i> <?= $message_count ?> Messages
   </div>

   <div class="box-container">

      <?php
         if($message_count > 0){
            while($fetch_message = $select_messages->fetch(PDO::FETCH_ASSOC)){
      ?>
      <div class="box">
         <div class="message-header">
            <div class="user-info">
               <div class="user-avatar">
                  <?= strtoupper(substr($fetch_message['name'], 0, 1)); ?>
               </div>
               <div class="user-details">
                  <h4><?= $fetch_message['name']; ?></h4>
                  <div class="email"><?= $fetch_message['email']; ?></div>
               </div>
            </div>
         </div>
         
         <div class="message-meta">
            <div class="meta-item">
               <div class="label">User ID</div>
               <div class="value">#<?= $fetch_message['user_id']; ?></div>
            </div>
            <div class="meta-item">
               <div class="label">Phone</div>
               <div class="value"><?= $fetch_message['number']; ?></div>
            </div>
         </div>
         
         <div class="message-content">
            <p><?= $fetch_message['message']; ?></p>
         </div>
         
         <a href="messages.php?delete=<?= $fetch_message['id']; ?>" 
            onclick="return confirm('Are you sure you want to delete this message?');" 
            class="delete-btn">
            <i class="fas fa-trash"></i> Delete Message
         </a>
      </div>
      <?php
            }
         }else{
            echo '<div class="empty">
                     <i class="fas fa-inbox"></i>
                     <p>No messages received yet!</p>
                  </div>';
         }
      ?>

   </div>

</section>

<script src="../js/admin_script.js"></script>

<script>
   // Add animation delay to each message box
   document.addEventListener('DOMContentLoaded', function() {
      const messageBoxes = document.querySelectorAll('.contacts .box-container .box');
      messageBoxes.forEach((box, index) => {
         box.style.animationDelay = `${index * 0.1}s`;
      });
   });
</script>
   
</body>
</html>
