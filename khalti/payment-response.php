<?php
session_start();
$pidx = $_GET['pidx'] ?? null;

if ($pidx) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://a.khalti.com/api/v2/epayment/lookup/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode(['pidx' => $pidx]),
        CURLOPT_HTTPHEADER => array(
            'Authorization: key live_secret_key_68791341fdd94846a146f0457ff7b455',
            'Content-Type: application/json',
        ),
    ));
 
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Check for cURL errors
    if (curl_errno($curl)) {
        $_SESSION['error'] = 'Payment verification failed: ' . curl_error($curl);
        header("Location: ../checkout.php");
        exit();
    }

    // Check if response is empty
    if (empty($response)) {
        $_SESSION['error'] = 'Empty response from payment verification.';
        header("Location: ../checkout.php");
        exit();
    }

    $responseArray = json_decode($response, true);

    // Check if JSON decode failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['error'] = 'Invalid response from payment verification.';
        header("Location: ../checkout.php");
        exit();
    }

    if ($responseArray && isset($responseArray['status'])) {
        switch ($responseArray['status']) {
            case 'Completed':
                // Process successful payment
                if (isset($_SESSION['khalti_order'])) {
                    try {
                        include '../components/connect.php';
                        
                        if (!$conn) {
                            throw new Exception('Database connection failed');
                        }
                        
                        $conn->beginTransaction();
                        
                        $order_data = $_SESSION['khalti_order'];
                        
                        // Insert order into database
                        $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on, payment_status) VALUES(?,?,?,?,?,?,?,?,NOW(),'paid')");
                        if (!$insert_order) {
                            throw new Exception('Failed to prepare order insert statement');
                        }
                        
                        $insert_order->execute([
                            $order_data['user_id'], 
                            $order_data['name'], 
                            $order_data['number'], 
                            $order_data['email'], 
                            'khalti', 
                            $order_data['address'], 
                            $order_data['total_products'], 
                            $order_data['total_price']
                        ]);
                        
                        // Update stock levels and log stock history
                        $update_cart = $conn->prepare("SELECT c.*, p.stock_quantity, p.min_stock_level FROM `cart` c JOIN `products` p ON c.pid = p.id WHERE c.user_id = ?");
                        if (!$update_cart) {
                            throw new Exception('Failed to prepare cart update statement');
                        }
                        
                        $update_cart->execute([$order_data['user_id']]);
                        
                        while($cart_item = $update_cart->fetch(PDO::FETCH_ASSOC)) {
                            $new_stock = $cart_item['stock_quantity'] - $cart_item['quantity'];
                            
                            // Update stock status
                            $stock_status = 'out_of_stock';
                            if($new_stock > $cart_item['min_stock_level']) {
                                $stock_status = 'in_stock';
                            } elseif($new_stock > 0) {
                                $stock_status = 'low_stock';
                            }
                            
                            // Update product stock
                            $update_product_stock = $conn->prepare("UPDATE `products` SET stock_quantity = ?, stock_status = ? WHERE id = ?");
                            if (!$update_product_stock) {
                                throw new Exception('Failed to prepare stock update statement');
                            }
                            $update_product_stock->execute([$new_stock, $stock_status, $cart_item['pid']]);
                            
                            // Log stock history for sale
                            $insert_stock_history = $conn->prepare("INSERT INTO `stock_history`(product_id, action_type, quantity_change, previous_stock, new_stock, notes, admin_id) VALUES(?,?,?,?,?,?,?)");
                            if ($insert_stock_history) {
                                $insert_stock_history->execute([$cart_item['pid'], 'sale', -$cart_item['quantity'], $cart_item['stock_quantity'], $new_stock, 'Order placed by user via Khalti', null]);
                            }
                            
                            // Create stock alerts if needed
                            if($new_stock <= $cart_item['min_stock_level'] && $new_stock > 0) {
                                $check_alert = $conn->prepare("SELECT id FROM `stock_alerts` WHERE product_id = ? AND alert_type = 'low_stock' AND is_read = 0");
                                if ($check_alert) {
                                    $check_alert->execute([$cart_item['pid']]);
                                    if($check_alert->rowCount() == 0) {
                                        $insert_alert = $conn->prepare("INSERT INTO `stock_alerts`(product_id, alert_type, message) VALUES(?,?,?)");
                                        if ($insert_alert) {
                                            $insert_alert->execute([$cart_item['pid'], 'low_stock', 'Product is running low on stock. Current stock: ' . $new_stock]);
                                        }
                                    }
                                }
                            } elseif($new_stock == 0) {
                                $check_alert = $conn->prepare("SELECT id FROM `stock_alerts` WHERE product_id = ? AND alert_type = 'out_of_stock' AND is_read = 0");
                                if ($check_alert) {
                                    $check_alert->execute([$cart_item['pid']]);
                                    if($check_alert->rowCount() == 0) {
                                        $insert_alert = $conn->prepare("INSERT INTO `stock_alerts`(product_id, alert_type, message) VALUES(?,?,?)");
                                        if ($insert_alert) {
                                            $insert_alert->execute([$cart_item['pid'], 'out_of_stock', 'Product is out of stock!']);
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Clear cart
                        $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
                        if ($delete_cart) {
                            $delete_cart->execute([$order_data['user_id']]);
                        }
                        
                        // Commit transaction
                        $conn->commit();

                        // Email receipt to customer
                        require_once __DIR__ . '/../components/mailer.php';
                        $orderId  = $order_data['purchase_order_id'] ?? '';
                        $subject  = 'Order Confirmation ' . ($orderId ? ('#' . $orderId) : '');
                        $body     = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#333;">'
                                   . '<h2 style="color:#2c3e50;">Thank you for your order, ' . htmlspecialchars($order_data['name']) . '!</h2>'
                                   . '<p>Your payment via <strong>Khalti</strong> was successful.</p>'
                                   . ($orderId ? ('<p><strong>Order ID:</strong> ' . htmlspecialchars($orderId) . '</p>') : '')
                                   . '<p><strong>Total:</strong> NPR ' . htmlspecialchars(number_format((float)$order_data['total_price'], 2)) . '</p>'
                                   . '<p><strong>Delivery address:</strong> ' . htmlspecialchars($order_data['address']) . '</p>'
                                   . '<p>We will notify you when your items are dispatched.</p>'
                                   . '<hr style="border:none;border-top:1px solid #eee;margin:20px 0;"/>'
                                   . '<p style="font-size:12px;color:#777;">If you have questions, reply to this email.</p>'
                                   . '</div>';
                        send_mail($order_data['email'], $subject, $body);
                        
                        // Clear session data
                        unset($_SESSION['khalti_order']);
                        if (isset($_SESSION['cart_count'])) {
                            unset($_SESSION['cart_count']);
                        }
                        
                        $_SESSION['success'] = 'Payment successful! Your order has been placed.';
                        
                    } catch (Exception $e) {
                        if (isset($conn)) {
                            $conn->rollback();
                        }
                        $_SESSION['error'] = 'An error occurred while processing your order. Please contact support.';
                        error_log("Khalti order processing error: " . $e->getMessage());
                    }
                } else {
                    $_SESSION['error'] = 'Order data not found. Please try again.';
                }
                
                header("Location: ../orders.php");
                exit();

            case 'Expired':
            case 'User canceled':
                $_SESSION['error'] = 'Payment was cancelled or expired. Please try again.';
                header("Location: ../checkout.php");
                exit();
   
                
            default:
                $_SESSION['error'] = 'Payment failed. Please try again.';
                header("Location: ../checkout.php");
                exit();
     
        }
    } else {
        $_SESSION['error'] = 'Invalid payment response format.';
        header("Location: ../checkout.php");
        exit();
    }
} else {
    $_SESSION['error'] = 'Invalid payment response.';
    header("Location: ../checkout.php");
    exit();
}
?>