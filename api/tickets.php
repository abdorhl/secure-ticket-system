<?php
require_once '../config/database.php';
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    if (empty($_POST['title']) || empty($_POST['description'])) {
        throw new Exception('Missing required fields');
    }

    $db = new Database();
    $conn = $db->connect();
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Insert ticket
        $stmt = $conn->prepare("
            INSERT INTO tickets (user_id, title, description, priority, problem_type, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'open', NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['priority'] ?? 'medium',
            $_POST['problem_type'] ?? 'software'
        ]);
        
        $ticketId = $conn->lastInsertId();
        
        // Handle multiple screenshots with security validation
        if (isset($_FILES['screenshots'])) {
            $uploadDir = '../uploads/screenshots/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $screenshots = $_FILES['screenshots'];
            $totalFiles = count($screenshots['name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            for ($i = 0; $i < $totalFiles; $i++) {
                if ($screenshots['error'][$i] === UPLOAD_ERR_OK) {
                    $fileSize = $screenshots['size'][$i];
                    $fileType = $screenshots['type'][$i];
                    $fileName = $screenshots['name'][$i];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Security validations
                    if ($fileSize > $maxFileSize) {
                        throw new Exception("File too large: " . $fileName);
                    }
                    
                    if (!in_array($fileType, $allowedTypes)) {
                        throw new Exception("Invalid file type: " . $fileName);
                    }
                    
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        throw new Exception("Invalid file extension: " . $fileName);
                    }
                    
                    // Verify file is actually an image
                    $imageInfo = getimagesize($screenshots['tmp_name'][$i]);
                    if ($imageInfo === false) {
                        throw new Exception("File is not a valid image: " . $fileName);
                    }
                    
                    // Generate secure filename
                    $secureFileName = $ticketId . '_' . uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $fileExtension;
                    $filePath = $uploadDir . $secureFileName;
                    
                    if (move_uploaded_file($screenshots['tmp_name'][$i], $filePath)) {
                        // Save screenshot reference in database
                        $stmt = $conn->prepare("
                            INSERT INTO ticket_attachments (ticket_id, file_path, original_name, file_size, mime_type, created_at) 
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $ticketId, 
                            'uploads/screenshots/' . $secureFileName,
                            $fileName,
                            $fileSize,
                            $fileType
                        ]);
                        
                        // Log file upload
                        error_log("File uploaded: " . $secureFileName . " for ticket: " . $ticketId);
                    } else {
                        throw new Exception("Failed to upload file: " . $fileName);
                    }
                }
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Ticket created successfully',
            'ticketId' => $ticketId
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>