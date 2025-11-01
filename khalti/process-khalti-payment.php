<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../user_login.php');
    exit();
}

// Check if order data exists
if (!isset($_SESSION['khalti_order'])) {
    header('Location: ../checkout.php');
    exit();
}

$order_data = $_SESSION['khalti_order'];

// Generate unique order ID
$purchase_order_id = 'ORDER_' . time() . '_' . $_SESSION['user_id'];

// Store the order ID in session for later use
$_SESSION['khalti_order']['purchase_order_id'] = $purchase_order_id;

// Prepare Khalti payment data
$amount = $order_data['total_price'] * 100; // Convert to paisa
$purchase_order_name = "Order from " . $order_data['name'];
$name = $order_data['name'];
$email = $order_data['email'];
$phone = $order_data['number'];

$postFields = array(
    "return_url" => "http://localhost/projectdone/khalti/payment-response.php",
    "website_url" => "http://localhost/projectdone",
    "amount" => $amount,
    "purchase_order_id" => $purchase_order_id,
    "purchase_order_name" => $purchase_order_name,
    "customer_info" => array(
        "name" => $name,
        "email" => $email,
        "phone" => $phone
    )
);

$jsonData = json_encode($postFields);

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://a.khalti.com/api/v2/epayment/initiate/',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $jsonData,
    CURLOPT_HTTPHEADER => array(
        'Authorization: key live_secret_key_68791341fdd94846a146f0457ff7b455',
        'Content-Type: application/json',
    ),
));

$response = curl_exec($curl);

if (curl_errno($curl)) {
    $_SESSION['error'] = 'Payment gateway error: ' . curl_error($curl);
    header('Location: ../checkout.php');
    exit();
} else {
    $responseArray = json_decode($response, true);

    if (isset($responseArray['error'])) {
        $_SESSION['error'] = 'Payment error: ' . $responseArray['error'];
        header('Location: ../checkout.php');
        exit();
    } elseif (isset($responseArray['payment_url'])) {
        header('Location: ' . $responseArray['payment_url']);
        exit();
    } else {
        $_SESSION['error'] = 'Unexpected response from payment gateway';
        header('Location: ../checkout.php');
        exit();
    }
}


?>
