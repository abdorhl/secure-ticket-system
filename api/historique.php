<?php
require_once '../config/database.php';
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    $db = new Database();
    $conn = $db->connect();

    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get historique with ticket and user information
            $stmt = $conn->prepare("
                SELECT 
                    h.*,
                    t.title as ticket_title,
                    t.status as ticket_status,
                    t.priority as ticket_priority,
                    u.email as user_email,
                    u.role as user_role
                FROM historique h
                LEFT JOIN tickets t ON h.ticket_id = t.id
                LEFT JOIN users u ON h.user_id = u.id
                ORDER BY h.created_at DESC
                LIMIT 1000
            ");
            $stmt->execute();
            $historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $historique,
                'count' => count($historique)
            ]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['action'])) {
                throw new Exception('Action required');
            }
            
            switch ($data['action']) {
                case 'get_deleted_tickets':
                    // Get all deleted tickets with user information
                    $stmt = $conn->prepare("
                        SELECT 
                            t.*,
                            u.email as user_email,
                            u.role as user_role,
                            h.created_at as deleted_at,
                            h.details as deletion_reason
                        FROM tickets t
                        LEFT JOIN users u ON t.user_id = u.id
                        LEFT JOIN historique h ON t.id = h.ticket_id AND h.action = 'deleted'
                        WHERE t.deleted_at IS NOT NULL
                        ORDER BY t.deleted_at DESC
                    ");
                    $stmt->execute();
                    $deletedTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $deletedTickets,
                        'count' => count($deletedTickets)
                    ]);
                    break;
                    
                case 'restore_ticket':
                    if (!isset($data['ticket_id'])) {
                        throw new Exception('Ticket ID required');
                    }
                    
                    // Restore the ticket
                    $stmt = $conn->prepare("UPDATE tickets SET deleted_at = NULL WHERE id = ?");
                    $result = $stmt->execute([$data['ticket_id']]);
                    
                    if ($result && $stmt->rowCount() > 0) {
                        // Log the restoration
                        $stmt = $conn->prepare("INSERT INTO historique (ticket_id, user_id, action, details) VALUES (?, ?, 'updated', 'Ticket restauré')");
                        $stmt->execute([$data['ticket_id'], $_SESSION['user_id']]);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Ticket restored successfully'
                        ]);
                    } else {
                        throw new Exception('Failed to restore ticket');
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
