<?php
/**
 * login.php — User Login Page
 */
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/recipes.php'; // for $pdo + getCategories()

startSession();

// Already logged in → go to profile
if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (loginUser($pdo, $email, $password)) {
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Invalid email address or password. Please try again.';
    }
}

$categories = getCategories($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — RecipeHub</title>
  <meta name="description" content="Log in to RecipeHub to create recipes and save your favourites.">
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
      <a href="register.php" class="btn btn-outline">Register</a>
    </div>
  </div>
</header>

<!-- AUTH CONTENT -->
<main class="auth-main">
  <div class="auth-card">

    <div class="auth-header">
      <span class="auth-icon">👤</span>
      <h1 class="auth-title">Welcome Back</h1>
      <p class="auth-subtitle">Sign in to your RecipeHub account</p>
    </div>

    <?php if ($error): ?>
      <div class="auth-alert auth-alert--error" role="alert">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form class="auth-form" method="POST" action="login.php" novalidate>

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
            placeholder="Enter your password"
            required
            autocomplete="current-password"
          >
          <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')" aria-label="Show/hide password">
            👁
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full">
        Sign In →
      </button>

    </form>

    <p class="auth-switch">
      Don't have an account? <a href="register.php">Create one free</a>
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
