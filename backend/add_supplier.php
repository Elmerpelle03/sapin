<?php
require '../../config/db.php';
require '../../config/session_admin.php';
require '../../config/encryption.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = $_POST['supplier_name'] ?? null;
    $company_name = $_POST['company_name'] ?? null;
    $contact_type = $_POST['contact_type'] ?? null;
    $mobile = $_POST['mobile'] ?? null;
    $email = $_POST['email'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    if (!$supplier_name || !$contact_type) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Validate contact info based on type
    if ($contact_type === 'mobile' && empty($mobile)) {
        echo json_encode(['success' => false, 'message' => 'Mobile number is required']);
        exit;
    }
    
    if ($contact_type === 'email' && empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    if ($contact_type === 'both' && (empty($mobile) || empty($email))) {
        echo json_encode(['success' => false, 'message' => 'Both mobile and email are required']);
        exit;
    }
    
    try {
        // Encrypt contact information
        $encrypted_mobile = !empty($mobile) ? encryptContact($mobile) : null;
        $encrypted_email = !empty($email) ? encryptContact($email) : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO suppliers 
            (supplier_name, company_name, contact_type, encrypted_mobile, encrypted_email, notes)
            VALUES (:supplier_name, :company_name, :contact_type, :encrypted_mobile, :encrypted_email, :notes)
        ");
        
        $stmt->execute([
            'supplier_name' => $supplier_name,
            'company_name' => $company_name,
            'contact_type' => $contact_type,
            'encrypted_mobile' => $encrypted_mobile,
            'encrypted_email' => $encrypted_email,
            'notes' => $notes
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => "Supplier '{$supplier_name}' added successfully. Contact info is encrypted and secure.",
            'supplier_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
