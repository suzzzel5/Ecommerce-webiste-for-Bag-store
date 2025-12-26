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
    } else {
        $atPos = strpos($email, '@');
        $localPart = substr($email, 0, $atPos);
        $domainPart = substr($email, $atPos + 1);

        // local part (before @) must contain at least one letter, so emails like 123@gmail.com are invalid
        if(!preg_match('/[A-Za-z]/', $localPart)){
            $errors[] = 'Email username must contain at least one letter!';
        } elseif(strlen($email) > 50){
            $errors[] = 'Email cannot exceed 50 characters!';
        } else {
            // Enforce a more realistic domain pattern and block abc@abc.abc style emails
            if(!preg_match('/^[A-Za-z0-9._%+-]+@([A-Za-z0-9-]+)\.([A-Za-z]{2,10})$/', $email, $m)){
                $errors[] = 'Please enter a valid email address (for example: name@example.com)!';
            } else {
                $domainName = strtolower($m[1]);
                $tld        = strtolower($m[2]);
                if($domainName === $tld){
                    $errors[] = 'Email domain and extension cannot be identical (e.g. abc@abc.abc is not allowed)!';
                }
            }
        }
    }

    // Phone validation
    if(empty($phone)){
        $errors[] = 'Phone number is required!';
    } elseif(!preg_match('/^9[0-9]{9}$/', $phone)){
        $errors[] = 'Phone number must be exactly 10 digits and start with 9!';
    } elseif(preg_match('/(\d)\1{4,}/', $phone)){
        $errors[] = 'Phone number cannot contain more than 4 identical digits in a row!';
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

            // Get the newly created user ID for the welcome email
            $new_user_id = $conn->lastInsertId();

            // Auto-login the user after registration
            $_SESSION['user_id'] = $new_user_id;
            
            // Sync guest cart and wishlist items to database
            include 'components/sync_guest_items.php';
            sync_guest_items_to_database($conn, $new_user_id);

            // Send welcome email (best-effort)
            $subject = 'Welcome to Nexus Bag, ' . htmlspecialchars($name) . '!';
            $body    = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">
                <h2 style="color:#2c3e50;">Welcome, ' . htmlspecialchars($name) . '!</h2>
                <p>Thanks for joining the <strong>Nexus Bag</strong> family.</p>
                <p>We&#39;re excited to have you with us &mdash; your next favorite bag might be just a few clicks away.</p>
                <p>Your unique customer ID is: <strong>' . htmlspecialchars($new_user_id, ENT_QUOTES, 'UTF-8') . '</strong></p>
                <p>You can now log in and start exploring our latest collections:</p>
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
      
      <input type="tel" name="phone" id="phone" required placeholder="enter your phone number" maxlength="10" class="box" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/script.js"></script>

<?php
// Prepare PHP messages for SweetAlert (if any)
$swal_messages = isset($message) && is_array($message) ? $message : [];
?>
<script>
// Client-side validation
document.addEventListener('DOMContentLoaded', function() {
    // SweetAlert for server-side registration messages
    const serverMessages = <?php echo json_encode($swal_messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    if (Array.isArray(serverMessages) && serverMessages.length > 0 && typeof Swal !== 'undefined') {
        const allText = serverMessages.join('\n');
        const isSuccess = serverMessages.some(msg => msg.toLowerCase().includes('registered successfully'));

        if (isSuccess) {
            Swal.fire({
                icon: 'success',
                title: 'Registration Successful',
                text: allText,
                confirmButtonColor: '#27ae60'
            }).then(() => {
                // Redirect to home after user acknowledges success (user is auto-logged in)
                window.location.href = 'home.php';
            });
        } else {
            // Show errors in a list
            const htmlList = '<ul style="text-align:left; margin:0; padding-left:1.2rem;">' +
                serverMessages.map(m => '<li>' + String(m).replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</li>').join('') +
                '</ul>';
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                html: htmlList,
                confirmButtonColor: '#e74c3c'
            });
        }
    }
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
        // Allow common email characters, require at least one letter before @, capture domain and TLD
        const emailRegex = /^(?=[^@]*[A-Za-z])[A-Za-z0-9._%+-]+@([A-Za-z0-9-]+)\.([A-Za-z]{2,10})$/;
        
        if (email === '') {
            emailError.textContent = 'Email is required!';
            emailInput.classList.add('error');
            return false;
        } else if (email.length > 50) {
            emailError.textContent = 'Email cannot exceed 50 characters!';
            emailInput.classList.add('error');
            return false;
        }

        const match = email.match(emailRegex);
        if (!match) {
            emailError.textContent = 'Please enter a valid email address (for example: name@example.com)!';
            emailInput.classList.add('error');
            return false;
        }

        const domainName = match[1].toLowerCase();
        const tld = match[2].toLowerCase();
        if (domainName === tld) {
            emailError.textContent = 'Email domain and extension cannot be identical (e.g. abc@abc.abc is not allowed)!';
            emailInput.classList.add('error');
            return false;
        }

        emailError.textContent = '';
        emailInput.classList.remove('error');
        return true;
    }

    function validatePhone() {
        const phone = phoneInput.value.trim();
        const phoneError = document.getElementById('phoneError');
        
        if (phone === '') {
            phoneError.textContent = 'Phone number is required!';
            phoneInput.classList.add('error');
            return false;
        } else if (!/^9\d{9}$/.test(phone)) {
            phoneError.textContent = 'Phone number must be exactly 10 digits and start with 9!';
            phoneInput.classList.add('error');
            return false;
        } else if (/(\d)\1{4,}/.test(phone)) {
            phoneError.textContent = 'Phone number cannot contain more than 4 identical digits in a row!';
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
