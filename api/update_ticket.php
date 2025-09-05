<?php
require_once '../config/database.php';
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ticket_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    $validStatuses = ['open', 'in_progress', 'resolved', 'closed', 'no_resolu'];
    if (!in_array($data['status'], $validStatuses)) {
        throw new Exception('Invalid status');
    }

    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("UPDATE tickets SET status = ? WHERE id = ?");
    $result = $stmt->execute([$data['status'], $data['ticket_id']]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Ticket updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update ticket');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
