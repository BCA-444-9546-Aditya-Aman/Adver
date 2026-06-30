<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../db_connect.php';

// Retrieve and sanitize inputs
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$business = isset($_POST['business']) ? trim($_POST['business']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$business_type = isset($_POST['business_type']) ? trim($_POST['business_type']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required.']);
    exit;
}
if (empty($business)) {
    echo json_encode(['success' => false, 'error' => 'Business name is required.']);
    exit;
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'A valid email is required.']);
    exit;
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Phone number is required.']);
    exit;
}
try {
    $stmt = $pdo->prepare("INSERT INTO automation_leads (name, business_name, email, phone, business_type, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $business, $email, $phone, $business_type, $message]);
    echo json_encode(['success' => true]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
