<?php
/**
 * Authentication and Role-Based Access Control (RBAC)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user array or null
 */
function current_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'role'      => $_SESSION['user_role'] ?? 'student',
        'role_id'   => $_SESSION['user_role_id'] ?? 3,
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email'     => $_SESSION['user_email'] ?? '',
        'phone'     => $_SESSION['user_phone'] ?? '',
        'qualification' => $_SESSION['user_qualification'] ?? '',
        'experience' => $_SESSION['user_experience'] ?? '',
        'address' => $_SESSION['user_address'] ?? '',
        'avatar' => $_SESSION['user_avatar'] ?? '',
    ];
}

/**
 * Check if the user has a specific role
 */
function has_role($role) {
    if (!is_logged_in()) return false;
    $roles = is_array($role) ? $role : [$role];
    return in_array(strtolower($_SESSION['user_role'] ?? ''), array_map('strtolower', $roles));
}

/**
 * Require user to be logged in
 */
function require_login($redirectUrl = null) {
    if (!is_logged_in()) {
        $url = $redirectUrl ?? site_url('login.php');
        $_SESSION['flash_error'] = 'Please log in to access this page.';
        header("Location: " . $url);
        exit;
    }
}

/**
 * Enforce role access control. E.g. require_role(['admin', 'accountant'])
 */
function require_role($allowedRoles, $redirectUrl = null) {
    $account = require_active_account();
    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    if (!in_array(strtolower($account['role_name']), array_map('strtolower', $roles), true)) {
        $_SESSION['flash_error'] = 'Unauthorized access: You do not have permission for this section.';
        $url = $redirectUrl ?? site_url('index.php');
        header("Location: " . $url);
        exit;
    }
}

/**
 * Log in a user by populating session variables
 */
function login_user($user) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']      = $user['id'];
    $_SESSION['user_name']    = $user['full_name'];
    $_SESSION['user_email']   = $user['email'];
    $_SESSION['user_phone']   = $user['phone'] ?? '';
    $_SESSION['user_qualification'] = $user['qualification'] ?? '';
    $_SESSION['user_experience'] = $user['experience'] ?? '';
    $_SESSION['user_address'] = $user['address'] ?? '';
    $_SESSION['user_avatar'] = $user['avatar'] ?? '';
    $_SESSION['user_role']    = $user['role_name'] ?? 'student';
    $_SESSION['user_role_id'] = $user['role_id'] ?? 3;
}

/**
 * Keep the session identity in sync with the database after a role/status change.
 */
function sync_session_user(array $user) {
    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['user_name']     = $user['full_name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_phone']    = $user['phone'] ?? '';
    $_SESSION['user_qualification'] = $user['qualification'] ?? '';
    $_SESSION['user_experience'] = $user['experience'] ?? '';
    $_SESSION['user_address'] = $user['address'] ?? '';
    $_SESSION['user_avatar'] = $user['avatar'] ?? '';
    $_SESSION['user_role']     = $user['role_name'];
    $_SESSION['user_role_id']  = (int)$user['role_id'];
}

/**
 * Get the canonical destination for an authenticated role.
 */
function dashboard_for_role($role) {
    switch (strtolower((string)$role)) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'teacher':
            return 'teacher/dashboard.php';
        case 'accountant':
            return 'accountant/dashboard.php';
        default:
            return 'student/dashboard.php';
    }
}

/**
 * Require a live, active account from the database before loading a portal.
 */
function require_active_account() {
    global $db_connected, $pdo;

    require_login();

    if (!$db_connected || !$pdo) {
        logout_user();
        set_flash('error', 'Your account could not be verified because the database is unavailable.');
        header('Location: ' . site_url('login.php'));
        exit;
    }

    try {
        $stmt = $pdo->prepare('
            SELECT u.id, u.full_name, u.email, u.phone, u.qualification, u.experience, u.address, u.avatar, u.role_id, u.status, r.name AS role_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $account = $stmt->fetch();
    } catch (PDOException $e) {
        log_database_error('Verify authenticated account', $e);
        logout_user();
        session_start();
        set_flash('error', 'Your account could not be verified. Please sign in again.');
        header('Location: ' . site_url('login.php'));
        exit;
    }

    if (!$account || $account['status'] !== 'active') {
        $statusMessage = $account
            ? 'Your account is currently ' . $account['status'] . '. Please contact support.'
            : 'This account no longer exists.';
        logout_user();
        session_start();
        set_flash('error', $statusMessage);
        header('Location: ' . site_url('login.php'));
        exit;
    }

    sync_session_user($account);
    $account['role'] = $account['role_name'];
    return $account;
}

/**
 * Terminate user session
 */
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
