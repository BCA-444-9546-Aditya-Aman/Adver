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
$website = isset($_POST['website']) ? trim($_POST['website']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$need = isset($_POST['need']) ? trim($_POST['need']) : '';

// Validation
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required.']);
    exit;
}
if (empty($business)) {
    echo json_encode(['success' => false, 'error' => 'Business name is required.']);
    exit;
}
if (empty($website)) {
    echo json_encode(['success' => false, 'error' => 'Website is required.']);
    exit;
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'A valid email is required.']);
    exit;
}
if (empty($need)) {
    echo json_encode(['success' => false, 'error' => 'Please select what matters most.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO seo_leads (name, business_name, website, email, phone, seo_need) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $business, $website, $email, $phone, $need]);
    echo json_encode(['success' => true]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
