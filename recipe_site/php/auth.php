<?php
/**
 * auth.php
 * ─────────────────────────────────────────────────────────────
 * Session-based authentication helpers for RecipeHub.
 *
 * Functions:
 *  - startSession()       → safe session_start()
 *  - isLoggedIn()         → bool
 *  - getCurrentUser()     → array|null
 *  - loginUser()          → bool
 *  - registerUser()       → true or throws
 *  - logoutUser()         → void (destroys session)
 * ─────────────────────────────────────────────────────────────
 */

// ── Start session safely ──────────────────────────────────────
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// ── Check if a user is logged in ─────────────────────────────
function isLoggedIn(): bool
{
    startSession();
    return !empty($_SESSION['user_id']);
}

// ── Get the current logged-in user data ──────────────────────
function getCurrentUser(): ?array
{
    startSession();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'       => (int) $_SESSION['user_id'],
        'username' => $_SESSION['user_username'] ?? '',
        'email'    => $_SESSION['user_email']    ?? '',
        'role'     => $_SESSION['user_role']     ?? 'user',
    ];
}

// ── Require login — redirect to login.php if not authenticated ─
function requireLogin(string $redirectTo = 'login.php'): void
{
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}

// ── Log in a user ─────────────────────────────────────────────
// Returns true on success, false if credentials are wrong.
function loginUser(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare(
        "SELECT id, username, email, password_hash, role FROM users WHERE email = :email LIMIT 1"
    );
    $stmt->execute([':email' => trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    // Upgrade hash if bcrypt cost has changed
    if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT)) {
        $newHash = password_hash($password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password_hash = :h WHERE id = :id");
        $upd->execute([':h' => $newHash, ':id' => $user['id']]);
    }

    startSession();
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];

    return true;
}

// ── Register a new user ───────────────────────────────────────
// Returns true on success; throws Exception with a user-friendly message on failure.
function registerUser(PDO $pdo, string $username, string $email, string $password): bool
{
    $username = trim($username);
    $email    = strtolower(trim($email));

    // Basic validation
    if (strlen($username) < 3 || strlen($username) > 60) {
        throw new Exception('Username must be between 3 and 60 characters.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address.');
    }
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters.');
    }

    // Check for duplicates
    $check = $pdo->prepare("SELECT id FROM users WHERE email = :e OR username = :u LIMIT 1");
    $check->execute([':e' => $email, ':u' => $username]);
    if ($check->fetch()) {
        throw new Exception('That email address or username is already registered.');
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :h)"
    );
    $stmt->execute([':u' => $username, ':e' => $email, ':h' => $hash]);

    $userId = (int) $pdo->lastInsertId();

    // Auto-login
    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id']       = $userId;
    $_SESSION['user_username'] = $username;
    $_SESSION['user_email']    = $email;
    $_SESSION['user_role']     = 'user';

    return true;
}

// ── Log out the current user ──────────────────────────────────
function logoutUser(): void
{
    startSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ── Handle logout via GET param (?logout=1) ───────────────────
if (isset($_GET['logout'])) {
    logoutUser();
    header('Location: ../index.php');
    exit;
}
