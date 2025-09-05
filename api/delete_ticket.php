<?php
require_once '../config/database.php';
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ticket_id'])) {
        throw new Exception('Missing ticket ID');
    }

    $db = new Database();
    $conn = $db->connect();
    
    // Get ticket details before deletion for historique
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$data['ticket_id']]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket not found or already deleted');
    }
    
    // Soft delete the ticket
    $stmt = $conn->prepare("UPDATE tickets SET deleted_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$data['ticket_id']]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Log the deletion in historique
        $stmt = $conn->prepare("INSERT INTO historique (ticket_id, user_id, action, old_value, details) VALUES (?, ?, 'deleted', ?, ?)");
        $stmt->execute([
            $data['ticket_id'],
            $_SESSION['user_id'],
            json_encode($ticket),
            "Ticket supprimé: {$ticket['title']}"
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete ticket');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
