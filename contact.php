<?php

// Start session first
session_start();

// Include database connection
include 'components/connect.php';

// Validate database connection
if(!isset($conn) || !$conn){
   die('Database connection failed. Please contact the administrator.');
}

// Handle user session (optional for contact form)
$user_id = '';
if(isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])){
   // Validate user_id is numeric and positive
   if(is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT)){
      $user_id = (int)$_SESSION['user_id'];
      
      // Verify user exists in database (optional validation)
      $verify_user = $conn->prepare("SELECT id FROM `users` WHERE id = ? LIMIT 1");
      $verify_user->execute([$user_id]);
      if($verify_user->rowCount() === 0){
         $user_id = ''; // Reset if user doesn't exist
      }
   }
}

// Initialize message array and form variables
$message = [];
$name = '';
$email = '';
$number = '';
$msg = '';

// Rate limiting to prevent spam (max 3 messages per 10 minutes)
if(!isset($_SESSION['contact_submissions'])){
   $_SESSION['contact_submissions'] = [];
}

// Clean old submissions (older than 10 minutes)
$current_time = time();
$_SESSION['contact_submissions'] = array_filter($_SESSION['contact_submissions'], function($timestamp) use ($current_time){
   return ($current_time - $timestamp) < 600; // 10 minutes = 600 seconds
});

// Strict validation for form submission
if(isset($_POST['send'])){
   
   // Check rate limit
   if(count($_SESSION['contact_submissions']) >= 3){
      $message[] = 'You have submitted too many messages recently. Please wait a few minutes before trying again.';
   } else {
   
   $errors = [];
   
   // Validate and sanitize name
   $name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
   if(empty($name)){
      $errors[] = 'Name is required!';
   } elseif(strlen($name) < 2){
      $errors[] = 'Name must be at least 2 characters long!';
   } elseif(strlen($name) > 50){
      $errors[] = 'Name cannot exceed 50 characters!';
   } elseif(!preg_match("/^[a-zA-Z\s'-]+$/u", $name)){
      $errors[] = 'Name can only contain letters, spaces, hyphens, and apostrophes!';
   } elseif(is_numeric($name)){
      $errors[] = 'Name cannot be just numbers!';
   }
   // Final sanitization
   $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
   
   // Validate and sanitize email
   $email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
   if(empty($email)){
      $errors[] = 'Email is required!';
   } elseif(strlen($email) > 100){
      $errors[] = 'Email cannot exceed 100 characters!';
   } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
      $errors[] = 'Please enter a valid email address!';
   } else {
      // Additional email validation
      $atPos = strpos($email, '@');
      if($atPos === false || $atPos === 0 || $atPos === strlen($email) - 1){
         $errors[] = 'Invalid email format!';
      } else {
         $localPart = substr($email, 0, $atPos);
         $domainPart = substr($email, $atPos + 1);
         
         // Validate local part contains at least one letter
         if(!preg_match('/[A-Za-z]/', $localPart)){
            $errors[] = 'Email username must contain at least one letter!';
         }
         
         // Validate domain format
         if(!preg_match('/^([A-Za-z0-9-]+)\.([A-Za-z]{2,10})$/', $domainPart)){
            $errors[] = 'Please enter a valid email address (e.g., name@example.com)!';
         }
      }
   }
   // Final sanitization
   $email = filter_var($email, FILTER_SANITIZE_EMAIL);
   
   // Validate and sanitize phone number
   $number = isset($_POST['number']) ? trim((string)$_POST['number']) : '';
   if(empty($number)){
      $errors[] = 'Phone number is required!';
   } elseif(!is_numeric($number)){
      $errors[] = 'Phone number must contain only digits!';
   } elseif(strlen($number) < 7 || strlen($number) > 15){
      $errors[] = 'Phone number must be between 7 and 15 digits!';
   } elseif(!preg_match('/^[0-9]{7,15}$/', $number)){
      $errors[] = 'Invalid phone number format!';
   }
   // Final sanitization - keep only digits
   $number = preg_replace('/[^0-9]/', '', $number);
   
   // Validate and sanitize message
   $msg = isset($_POST['msg']) ? trim((string)$_POST['msg']) : '';
   if(empty($msg)){
      $errors[] = 'Message is required!';
   } elseif(strlen($msg) < 10){
      $errors[] = 'Message must be at least 10 characters long!';
   } elseif(strlen($msg) > 1000){
      $errors[] = 'Message cannot exceed 1000 characters!';
   }
   // Final sanitization - allow basic formatting but prevent XSS
   $msg = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
   
   // If no errors, proceed with database operations
   if(empty($errors)){
      try {
         // Check for duplicate message (exact match)
         $select_message = $conn->prepare("SELECT id FROM `messages` WHERE name = ? AND email = ? AND number = ? AND message = ? LIMIT 1");
         $select_message->execute([$name, $email, $number, $msg]);
         
         if($select_message->rowCount() > 0){
            $message[] = 'You have already sent this exact message!';
         } else {
            // Insert message with error handling
            $insert_message = $conn->prepare("INSERT INTO `messages`(user_id, name, email, number, message) VALUES(?,?,?,?,?)");
            $insert_result = $insert_message->execute([$user_id, $name, $email, $number, $msg]);
            
            if($insert_result){
               $message[] = 'Message sent successfully! We will get back to you soon.';
               // Track submission for rate limiting
               $_SESSION['contact_submissions'][] = $current_time;
               // Clear form data after successful submission
               $name = '';
               $email = '';
               $number = '';
               $msg = '';
            } else {
               $message[] = 'Failed to send message. Please try again later.';
            }
         }
      } catch(PDOException $e){
         // Log error (in production, log to file instead of exposing)
         error_log("Contact form error: " . $e->getMessage());
         $message[] = 'An error occurred while sending your message. Please try again later.';
      }
   } else {
      // Add validation errors to message array
      $message = array_merge($message, $errors);
   }
   
   } // End rate limiting check
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Contact</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="contact">

   <form action="" method="post" id="contactForm">
      <h3>Get in touch.</h3>
      <input type="text" 
             name="name" 
             id="name"
             placeholder="enter your name" 
             required 
             maxlength="50" 
             minlength="2"
             pattern="[a-zA-Z\s'-]+"
             title="Name must contain only letters, spaces, hyphens, and apostrophes"
             value="<?= isset($name) ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : ''; ?>" 
             class="box">
      
      <input type="email" 
             name="email" 
             id="email"
             placeholder="enter your email" 
             required 
             maxlength="100" 
             value="<?= isset($email) ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8') : ''; ?>" 
             class="box"
             oninput="this.value = this.value.replace(/\s/g, '')">
      
      <input type="tel" 
             name="number" 
             id="number"
             placeholder="enter your phone number" 
             required 
             minlength="7"
             maxlength="15"
             pattern="[0-9]{7,15}"
             title="Phone number must be 7-15 digits"
             value="<?= isset($number) ? htmlspecialchars($number, ENT_QUOTES, 'UTF-8') : ''; ?>" 
             class="box"
             oninput="this.value = this.value.replace(/[^0-9]/g, '')">
      
      <textarea name="msg" 
                id="msg"
                class="box" 
                placeholder="enter your message" 
                cols="30" 
                rows="10"
                required
                minlength="10"
                maxlength="1000"><?= isset($msg) ? htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
      
      <input type="submit" value="send message" name="send" class="btn">
   </form>

</section>

<script src="js/script.js"></script>

</body>
</html>
