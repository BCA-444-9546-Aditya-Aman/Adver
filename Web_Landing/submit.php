<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../db_connect.php';

// Retrieve and sanitize inputs
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$service = isset($_POST['service']) ? trim($_POST['service']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required.']);
    exit;
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'A valid email is required.']);
    exit;
}
if (empty($service)) {
    echo json_encode(['success' => false, 'error' => 'Please select a service.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO web_leads (name, email, phone, service, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $service, $message]);
    echo json_encode(['success' => true]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
