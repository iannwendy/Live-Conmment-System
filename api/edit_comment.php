<?php
// Include database configuration
require_once 'config.php';

// Set header to JSON
header('Content-Type: application/json');

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate input data
$commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

// Validate input
if ($commentId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid comment ID'
    ]);
    exit;
}

if (empty($content)) {
    echo json_encode([
        'success' => false,
        'message' => 'Comment content is required'
    ]);
    exit;
}

// Prevent too long inputs
if (strlen($content) > 1000) {
    echo json_encode([
        'success' => false,
        'message' => 'Comment is too long (maximum 1000 characters)'
    ]);
    exit;
}

try {
    // Get username from session
    $username = $_SESSION['username'];
    
    // First check if the comment exists and belongs to the current user
    $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = :comment_id AND username = :username");
    $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Comment not found or you do not have permission to edit it'
        ]);
        exit;
    }
    
    // Prepare SQL statement to update the comment
    $stmt = $pdo->prepare("UPDATE comments SET content = :content WHERE id = :comment_id AND username = :username");
    
    // Bind parameters
    $stmt->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt->bindParam(':comment_id', $commentId, PDO::PARAM_INT);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    
    // Execute the statement
    $stmt->execute();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Comment updated successfully'
    ]);
    
} catch(PDOException $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>