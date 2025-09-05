<?php
require_once '../config/database.php';
require_once '../classes/PDFGenerator.php';
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
                                // Get all tickets with 'no_resolu' status
                    $stmt = $conn->prepare("
                        SELECT t.*, u.email as user_email 
                        FROM tickets t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.status = 'no_resolu' AND t.deleted_at IS NULL
                        ORDER BY t.created_at DESC
                    ");
            $stmt->execute();
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $tickets,
                'count' => count($tickets)
            ]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['action'])) {
                throw new Exception('Action required');
            }
            
            switch ($data['action']) {
                case 'generate_single_report':
                    if (!isset($data['ticket_id'])) {
                        throw new Exception('Ticket ID required');
                    }
                    
                    // Get ticket details
                    $stmt = $conn->prepare("
                        SELECT t.*, u.email as user_email 
                        FROM tickets t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.id = ? AND t.status = 'no_resolu' AND t.deleted_at IS NULL
                    ");
                    $stmt->execute([$data['ticket_id']]);
                    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$ticket) {
                        throw new Exception('Ticket not found or not in no_resolu status');
                    }
                    
                    // Get attachments
                    $stmt = $conn->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
                    $stmt->execute([$data['ticket_id']]);
                    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Generate PDF
                    $pdf = new PDFGenerator();
                    $pdf->generateTicketReport($ticket, $attachments);
                    $pdf->output('ticket_' . $ticket['id'] . '_non_resolu.pdf');
                    break;
                    
                case 'generate_all_report':
                    // Get all no_resolu tickets
                    $stmt = $conn->prepare("
                        SELECT t.*, u.email as user_email 
                        FROM tickets t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.status = 'no_resolu' AND t.deleted_at IS NULL
                        ORDER BY t.created_at DESC
                    ");
                    $stmt->execute();
                    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($tickets)) {
                        throw new Exception('Aucun ticket non résolu trouvé');
                    }
                    
                    // Generate PDF
                    $pdf = new PDFGenerator();
                    $pdf->generateMultipleTicketsReport($tickets);
                    $pdf->output('rapport_tickets_non_resolus.pdf');
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
