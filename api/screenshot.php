<?php
require_once '../config/database.php';
requireAuth();

header('Content-Type: application/json');

try {
    if (!isset($_FILES['screenshot']) || !isset($_POST['description'])) {
        throw new Exception('Champs requis manquants');
    }

    $uploadDir = '../uploads/screenshots/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $file = $_FILES['screenshot'];
    $fileName = uniqid() . '_' . time() . '.png';
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Échec de l\'enregistrement de la capture d\'écran');
    }

    // Enregistrer dans la base de données
    $db = new Database();
    $conn = $db->connect();
    
    $stmt = $conn->prepare("INSERT INTO screenshots (user_id, file_path, description, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([
        $_SESSION['user_id'],
        'uploads/screenshots/' . $fileName,
        $_POST['description']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Capture d\'écran sauvegardée avec succès',
        'file_path' => 'uploads/screenshots/' . $fileName
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>