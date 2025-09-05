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
            // Get only non-admin users
            $stmt = $conn->prepare("SELECT id, email, role, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $users
            ]);
            break;

        case 'POST':
            if (empty($_POST['email']) || empty($_POST['password'])) {
                throw new Exception('Missing required fields');
            }

            // Force role to be 'user'
            $stmt = $conn->prepare("INSERT INTO users (email, password, role, created_at) VALUES (?, ?, 'user', NOW())");
            $result = $stmt->execute([
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_DEFAULT)
            ]);

            if (!$result) {
                throw new Exception('Failed to create user');
            }

            echo json_encode([
                'success' => true,
                'message' => 'User created successfully'
            ]);
            break;

        case 'DELETE':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['id'])) {
                throw new Exception('User ID is required');
            }

            // Only allow deletion of non-admin users
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
            $result = $stmt->execute([$data['id']]);

            if (!$result) {
                throw new Exception('Failed to delete user');
            }

            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
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