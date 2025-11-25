<?php
/**
 * Simple encryption for supplier contact information
 * Owner's supplier contacts are encrypted so developers can't see them
 */

// Encryption key - Owner should change this to their own secret key
// This key should be stored securely, not in version control
define('ENCRYPTION_KEY', 'CHANGE_THIS_TO_OWNER_SECRET_KEY_12345');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

function encryptContact($data) {
    if (empty($data)) return null;
    
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt($data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    
    // Combine IV and encrypted data
    return base64_encode($iv . $encrypted);
}

function decryptContact($encryptedData) {
    if (empty($encryptedData)) return null;
    
    $data = base64_decode($encryptedData);
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    
    return openssl_decrypt($encrypted, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
}

function maskContact($type, $value) {
    // Show masked version to non-owners
    if ($type === 'mobile') {
        // Show: 09XX-XXX-XX89
        if (strlen($value) >= 4) {
            return substr($value, 0, 4) . '-XXX-XX' . substr($value, -2);
        }
    } elseif ($type === 'email') {
        // Show: su***@example.com
        $parts = explode('@', $value);
        if (count($parts) === 2) {
            return substr($parts[0], 0, 2) . '***@' . $parts[1];
        }
    }
    return 'XXXXX';
}
?>
