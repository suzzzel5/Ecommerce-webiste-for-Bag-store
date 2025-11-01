<?php

include 'components/connect.php';
include 'components/mailer.php';

session_start();

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
};

if(isset($_POST['submit'])){

    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);
    $name = trim($name);
    $email = $_POST['email'];
    $email = filter_var($email, FILTER_SANITIZE_STRING);
    $email = trim($email);
    $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
    $phone = filter_var($phone, FILTER_SANITIZE_STRING);
    $phone = trim($phone);
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $address = filter_var($address, FILTER_SANITIZE_STRING);
    $address = trim($address);
    $pass = $_POST['pass'];
    $cpass = $_POST['cpass'];

   // Server-side validation
    $errors = [];

   // Name validation
    if(empty($name)){
        $errors[] = 'Name is required!';
    } elseif(strlen($name) < 3){
        $errors[] = 'Name must be at least 3 characters long!';
    } elseif(strlen($name) > 20){
        $errors[] = 'Name cannot exceed 20 characters!';
    } elseif(!preg_match("/^[a-zA-Z\s]+$/", $name)){
        $errors[] = 'Name can only contain letters and spaces!';
    }

    // Email validation
    if(empty($email)){
        $errors[] = 'Email is required!';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = 'Please enter a valid email address!';
    } elseif(strlen($email) > 50){
        $errors[] = 'Email cannot exceed 50 characters!';
    }

    // Phone validation
    if(empty($phone)){
        $errors[] = 'Phone number is required!';
    } else {
        $digitsOnly = preg_replace('/\D+/', '', $phone);
        if(strlen($digitsOnly) < 7 || strlen($digitsOnly) > 15){
            $errors[] = 'Phone number must contain between 7 and 15 digits!';
        } elseif(!preg_match('/^[0-9+()\-\s]+$/', $phone)){
            $errors[] = 'Phone number contains invalid characters!';
        }
    }

    // Address validation
    if(empty($address)){
        $errors[] = 'Address is required!';
    } elseif(strlen($address) < 5){
        $errors[] = 'Address must be at least 5 characters long!';
    } elseif(strlen($address) > 255){
        $errors[] = 'Address cannot exceed 255 characters!';
    }

    // Password validation
    if(empty($pass)){
        $errors[] = 'Password is required!';
    } elseif(strlen($pass) < 6){
        $errors[] = 'Password must be at least 6 characters long!';
    } elseif(strlen($pass) > 20){
        $errors[] = 'Password cannot exceed 20 characters!';
    } elseif(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/", $pass)){
        $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number!';
    }

    // Confirm password validation
    if(empty($cpass)){
        $errors[] = 'Please confirm your password!';
    } elseif($pass !== $cpass){
        $errors[] = 'Passwords do not match!';
    }

    // If no validation errors, proceed with registration
    if(empty($errors)){
        // Check if email already exists
        $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
        $select_user->execute([$email]);
        $row = $select_user->fetch(PDO::FETCH_ASSOC);

        if($select_user->rowCount() > 0){
            $message[] = 'Email already exists!';
        } else {
            // Hash password
            $hashed_pass = sha1($pass);
            
            // Insert user
            $insert_user = $conn->prepare("INSERT INTO `users`(name, email, password, phone, address) VALUES(?,?,?,?,?)");
            $insert_user->execute([$name, $email, $hashed_pass, $phone, $address]);

            // Send welcome email (non-blocking best-effort)
            $subject = 'Welcome to Nexus Bag, ' . htmlspecialchars($name) . '!';
            $body    = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">
                <h2 style="color:#2c3e50;">Welcome, ' . htmlspecialchars($name) . '!</h2>
                <p>Thanks for registering at <strong>Nexus Bag</strong>.</p>
                <p>You can now log in and start shopping:</p>
                <p><a href="' . htmlspecialchars((isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] : '') . '/projectdone/user_login.php') . '" style="background:#27ae60;color:#fff;padding:10px 16px;text-decoration:none;border-radius:4px;display:inline-block;">Login Now</a></p>
                <hr style="border:none;border-top:1px solid #eee;margin:20px 0;"/>
                <p style="font-size:12px;color:#777;">If you did not create this account, please ignore this email.</p>
            </div>';
            $sent = send_mail($email, $subject, $body);
            if ($sent) {
                $message[] = 'Registered successfully. A welcome email was sent to ' . htmlspecialchars($email) . '.';
            } else {
                $message[] = 'Registered successfully, but we could not send the welcome email.';
            }
        }
    } else {
        // Display validation errors
        foreach($errors as $error){
            $message[] = $error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register</title>
   
   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="css/style.css">

</head>
<body>
   
<?php include 'components/user_header.php'; ?>

<section class="form-container">

   <form action="" method="post" id="registerForm" novalidate>
      <h3>Register Now.</h3>
      <input type="text" name="name" id="name" required placeholder="enter your username" maxlength="20" class="box">
      <div class="error-message" id="nameError"></div>
      
      <input type="email" name="email" id="email" required placeholder="enter your email" maxlength="50" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      <div class="error-message" id="emailError"></div>
      
      <input type="tel" name="phone" id="phone" required placeholder="enter your phone number" maxlength="20" class="box" oninput="this.value = this.value.replace(/[^0-9+()\-\s]/g, '')">
      <div class="error-message" id="phoneError"></div>
      
      <input type="text" name="address" id="address" required placeholder="enter your address" maxlength="255" class="box">
      <div class="error-message" id="addressError"></div>
      
      <input type="password" name="pass" id="pass" required placeholder="enter your password" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      <div class="error-message" id="passError"></div>
      
      <input type="password" name="cpass" id="cpass" required placeholder="confirm your password" maxlength="20" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      <div class="error-message" id="cpassError"></div>
      
      <input type="submit" value="register now" class="btn" name="submit">
      <p>Already have an account?</p>
      <a href="user_login.php" class="option-btn">Login Now.</a>
   </form>

</section>

<script src="js/script.js"></script>

<script>
// Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    const addressInput = document.getElementById('address');
    const passInput = document.getElementById('pass');
    const cpassInput = document.getElementById('cpass');

    // Real-time validation functions
    function validateName() {
        const name = nameInput.value.trim();
        const nameError = document.getElementById('nameError');
        
        if (name === '') {
            nameError.textContent = 'Name is required!';
            nameInput.classList.add('error');
            return false;
        } else if (name.length < 3) {
            nameError.textContent = 'Name must be at least 3 characters long!';
            nameInput.classList.add('error');
            return false;
        } else if (name.length > 20) {
            nameError.textContent = 'Name cannot exceed 20 characters!';
            nameInput.classList.add('error');
            return false;
        } else if (!/^[a-zA-Z\s]+$/.test(name)) {
            nameError.textContent = 'Name can only contain letters and spaces!';
            nameInput.classList.add('error');
            return false;
        } else {
            nameError.textContent = '';
            nameInput.classList.remove('error');
            return true;
        }
    }

    function validateEmail() {
        const email = emailInput.value.trim();
        const emailError = document.getElementById('emailError');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email === '') {
            emailError.textContent = 'Email is required!';
            emailInput.classList.add('error');
            return false;
        } else if (!emailRegex.test(email)) {
            emailError.textContent = 'Please enter a valid email address!';
            emailInput.classList.add('error');
            return false;
        } else if (email.length > 50) {
            emailError.textContent = 'Email cannot exceed 50 characters!';
            emailInput.classList.add('error');
            return false;
        } else {
            emailError.textContent = '';
            emailInput.classList.remove('error');
            return true;
        }
    }

    function validatePhone() {
        const phone = phoneInput.value.trim();
        const phoneError = document.getElementById('phoneError');
        const digitsOnly = phone.replace(/\D+/g, '');
        
        if (phone === '') {
            phoneError.textContent = 'Phone number is required!';
            phoneInput.classList.add('error');
            return false;
        } else if (digitsOnly.length < 7 || digitsOnly.length > 15) {
            phoneError.textContent = 'Phone number must contain between 7 and 15 digits!';
            phoneInput.classList.add('error');
            return false;
        } else if (!/^[0-9+()\-\s]+$/.test(phone)) {
            phoneError.textContent = 'Phone number contains invalid characters!';
            phoneInput.classList.add('error');
            return false;
        } else {
            phoneError.textContent = '';
            phoneInput.classList.remove('error');
            return true;
        }
    }

    function validateAddress() {
        const address = addressInput.value.trim();
        const addressError = document.getElementById('addressError');
        
        if (address === '') {
            addressError.textContent = 'Address is required!';
            addressInput.classList.add('error');
            return false;
        } else if (address.length < 5) {
            addressError.textContent = 'Address must be at least 5 characters long!';
            addressInput.classList.add('error');
            return false;
        } else if (address.length > 255) {
            addressError.textContent = 'Address cannot exceed 255 characters!';
            addressInput.classList.add('error');
            return false;
        } else {
            addressError.textContent = '';
            addressInput.classList.remove('error');
            return true;
        }
    }

    function validatePassword() {
        const password = passInput.value;
        const passError = document.getElementById('passError');
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/;
        
        if (password === '') {
            passError.textContent = 'Password is required!';
            passInput.classList.add('error');
            return false;
        } else if (password.length < 6) {
            passError.textContent = 'Password must be at least 6 characters long!';
            passInput.classList.add('error');
            return false;
        } else if (password.length > 20) {
            passError.textContent = 'Password cannot exceed 20 characters!';
            passInput.classList.add('error');
            return false;
        } else if (!passwordRegex.test(password)) {
            passError.textContent = 'Password must contain at least one uppercase letter, one lowercase letter, and one number!';
            passInput.classList.add('error');
            return false;
        } else {
            passError.textContent = '';
            passInput.classList.remove('error');
            return true;
        }
    }

    function validateConfirmPassword() {
        const password = passInput.value;
        const confirmPassword = cpassInput.value;
        const cpassError = document.getElementById('cpassError');
        
        if (confirmPassword === '') {
            cpassError.textContent = 'Please confirm your password!';
            cpassInput.classList.add('error');
            return false;
        } else if (password !== confirmPassword) {
            cpassError.textContent = 'Passwords do not match!';
            cpassInput.classList.add('error');
            return false;
        } else {
            cpassError.textContent = '';
            cpassInput.classList.remove('error');
            return true;
        }
    }

    // Add event listeners for real-time validation
    nameInput.addEventListener('blur', validateName);
    nameInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            validateName();
        }
    });

    emailInput.addEventListener('blur', validateEmail);
    emailInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            validateEmail();
        }
    });

    phoneInput.addEventListener('blur', validatePhone);
    phoneInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            validatePhone();
        }
    });

    addressInput.addEventListener('blur', validateAddress);
    addressInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            validateAddress();
        }
    });

    passInput.addEventListener('blur', validatePassword);
    passInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            validatePassword();
        }
        // Also validate confirm password when password changes
        if (cpassInput.value) {
            validateConfirmPassword();
        }
    });

    cpassInput.addEventListener('blur', validateConfirmPassword);
    cpassInput.addEventListener('input', function() {
        if (this.classList.contains('error')) {
            validateConfirmPassword();
        }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        const isNameValid = validateName();
        const isEmailValid = validateEmail();
        const isPhoneValid = validatePhone();
        const isAddressValid = validateAddress();
        const isPasswordValid = validatePassword();
        const isConfirmPasswordValid = validateConfirmPassword();

        if (!isNameValid || !isEmailValid || !isPhoneValid || !isAddressValid || !isPasswordValid || !isConfirmPasswordValid) {
            e.preventDefault();
            return false;
        }
    });

    // Password strength indicator
    passInput.addEventListener('input', function() {
        const password = this.value;
        const strength = getPasswordStrength(password);
        updatePasswordStrengthIndicator(strength);
    });

    function getPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 6) strength++;
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        return strength;
    }

    function updatePasswordStrengthIndicator(strength) {
        const passError = document.getElementById('passError');
        let message = '';
        let color = '';
        
        if (strength < 3) {
            message = 'Weak password';
            color = '#e74c3c';
        } else if (strength < 5) {
            message = 'Medium password';
            color = '#f39c12';
        } else {
            message = 'Strong password';
            color = '#27ae60';
        }
        
        if (passInput.value && !passInput.classList.contains('error')) {
            passError.textContent = message;
            passError.style.color = color;
        }
    }
});
</script>

<style>
/* Error styling */
.error-message {
    color: #e74c3c;
    font-size: 1.2rem;
    margin-top: 0.5rem;
    margin-bottom: 1rem;
    min-height: 1.8rem;
}

.box.error {
    border-color: #e74c3c !important;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2) !important;
}

.box:focus.error {
    border-color: #e74c3c !important;
    box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2) !important;
}
</style>

</body>
</html>
