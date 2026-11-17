<?php
/**
 * Global Utility Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * HTML Escaping shorthand for XSS prevention
 */
function e($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency amounts nicely (e.g., 30,000 RWF)
 */
function format_money($amount, $currency = 'RWF') {
    return number_format((float)$amount, 0, '.', ',') . ' ' . $currency;
}

/**
 * Official support team contacts (single source of truth).
 * Update these in one place to change them across the whole platform.
 */
function support_whatsapp_number() {
    return '250798532388'; // 0798532388 in international format
}

function support_phone_display() {
    return '+250 798 532 388';
}

function support_phone_tel() {
    return '+' . support_whatsapp_number();
}

function support_email() {
    return 'contact@digitalsmart.rw';
}

/**
 * Build a WhatsApp chat link for the official support team.
 * Optionally pre-fills the first message.
 */
function support_whatsapp_url($message = '') {
    $url = 'https://wa.me/' . support_whatsapp_number();
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

/** Log database details for maintainers without exposing them to users. */
function log_database_error($context, Throwable $exception) {
    error_log('[Digital Smart Class] ' . $context . ': ' . $exception->getMessage());
}

/**
 * Record a security-sensitive or administrative action without exposing it to users.
 */
function audit_log($action, $details = null, $userId = null) {
    global $db_connected, $pdo;
    if (!$db_connected || !$pdo) {
        return false;
    }

    try {
        $stmt = $pdo->prepare('
            INSERT INTO audit_logs (user_id, action, details, ip_address)
            VALUES (:user_id, :action, :details, :ip_address)
        ');
        $stmt->execute([
            'user_id' => $userId ?: ($_SESSION['user_id'] ?? null),
            'action' => substr((string)$action, 0, 100),
            'details' => $details !== null ? substr((string)$details, 0, 65535) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
        return true;
    } catch (PDOException $e) {
        log_database_error('Write audit log', $e);
        return false;
    }
}

/**
 * Set a session flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get and clear a session flash message
 */
function get_flash($type) {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Render flash alert banner if present
 */
function render_flash_messages() {
    $types = ['success' => 'alert-success', 'error' => 'alert-danger', 'info' => 'alert-info'];
    $html = '';
    foreach ($types as $type => $cssClass) {
        $msg = get_flash($type);
        if ($msg) {
            $html .= '<div class="alert ' . $cssClass . '">' . e($msg) . '</div>';
        }
    }
    return $html;
}

/**
 * CSRF Protection Helpers
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

