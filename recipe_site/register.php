<?php
/**
 * register.php — New User Registration Page
 */
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/recipes.php';

startSession();

if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = trim($_POST['password']  ?? '');
    $password2 = trim($_POST['password2'] ?? '');

    if (empty($username) || empty($email) || empty($password) || empty($password2)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        try {
            registerUser($pdo, $username, $email, $password);
            header('Location: profile.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$categories = getCategories($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — RecipeHub</title>
  <meta name="description" content="Join RecipeHub to share your recipes and save your favourites.">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-page">

<!-- HEADER -->
<header class="site-header">
  <div class="container header-inner">
    <a href="index.php" class="logo">
      <span class="logo-icon">🔥</span>
      <span class="logo-text">Recipe<strong>Hub</strong></span>
    </a>
    <nav class="primary-nav" aria-label="Main navigation">
      <ul>
        <li><a href="index.php" class="nav-link">Home</a></li>
        <?php foreach ($categories as $cat): ?>
          <li>
            <a href="category.php?slug=<?= htmlspecialchars($cat['slug']) ?>" class="nav-link">
              <?= htmlspecialchars($cat['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="header-auth-btns">
      <a href="login.php" class="btn btn-outline">Login</a>
    </div>
  </div>
</header>

<!-- AUTH CONTENT -->
<main class="auth-main">
  <div class="auth-card">

    <div class="auth-header">
      <span class="auth-icon">🍴</span>
      <h1 class="auth-title">Join RecipeHub</h1>
      <p class="auth-subtitle">Create your free account and start cooking</p>
    </div>

    <?php if ($error): ?>
      <div class="auth-alert auth-alert--error" role="alert">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form class="auth-form" method="POST" action="register.php" novalidate>

      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          class="form-input"
          placeholder="e.g. chef_gordon"
          value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
          required
          minlength="3"
          maxlength="60"
          autocomplete="username"
        >
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input
          type="email"
          id="email"
          name="email"
          class="form-input"
          placeholder="you@example.com"
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
          required
          autocomplete="email"
        >
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-with-toggle">
          <input
            type="password"
            id="password"
            name="password"
            class="form-input"
            placeholder="At least 6 characters"
            required
            minlength="6"
            autocomplete="new-password"
          >
          <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')" aria-label="Show/hide password">
            👁
          </button>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password2">Confirm Password</label>
        <div class="input-with-toggle">
          <input
            type="password"
            id="password2"
            name="password2"
            class="form-input"
            placeholder="Repeat your password"
            required
            autocomplete="new-password"
          >
          <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password2')" aria-label="Show/hide password">
            👁
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        Create Account →
      </button>

    </form>

    <p class="auth-switch">
      Already have an account? <a href="login.php">Sign in</a>
    </p>

  </div>
</main>

<script>
function togglePasswordVisibility(id) {
  const input = document.getElementById(id);
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
