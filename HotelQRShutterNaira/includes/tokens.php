<?php
function safeGenerateStreamToken($adminId) {
    try {
        if (!function_exists('random_bytes')) {
            throw new Exception('random_bytes unavailable');
        }
        return generateStreamToken($adminId);
    } catch (Exception $e) {
        // Fallback for systems without random_bytes()
        return $adminId . '_' . md5(uniqid(mt_rand(), true));
    }
}
?>