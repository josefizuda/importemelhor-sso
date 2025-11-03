<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Disable error display
ini_set('display_errors', 0);
error_reporting(0);

require_once '../config.php';

// Clean any output that may have been generated
ob_clean();

header('Content-Type: application/json');

$auth = new Auth();

try {
    // Check authentication
    if (!isset($_COOKIE['sso_token'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $session = $auth->validateSession($_COOKIE['sso_token']);

    if (!$session) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Check admin permission
    $isAdmin = ($auth->isAdmin($session['user_id']));
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    // Check if file was uploaded
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No file uploaded or upload error: ' . $_FILES['image']['error']]);
        exit;
    }

    $file = $_FILES['image'];

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid file type. Only images are allowed']);
        exit;
    }

    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'File size exceeds 5MB limit']);
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'banner_' . time() . '_' . uniqid() . '.' . $extension;
    $upload_dir = __DIR__ . '/../public/uploads/banners/';
    $upload_path = $upload_dir . $filename;

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            http_response_code(500);
            $error_msg = error_get_last();
            echo json_encode([
                'error' => 'Failed to create upload directory',
                'details' => $error_msg ? $error_msg['message'] : 'Unknown error',
                'path' => $upload_dir
            ]);
            exit;
        }
        // Ensure directory has correct permissions
        chmod($upload_dir, 0777);
    }

    // Verify directory is writable
    if (!is_writable($upload_dir)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Upload directory is not writable',
            'path' => $upload_dir
        ]);
        exit;
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
        exit;
    }

    // Return the URL
    $file_url = '/public/uploads/banners/' . $filename;

    echo json_encode([
        'success' => true,
        'url' => $file_url,
        'filename' => $filename
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();
