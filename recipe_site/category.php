<?php
/**
 * category.php — Category Landing Page
 * Displays all recipes in a given category.
 * URL: category.php?slug=dinner
 */

require_once __DIR__ . '/php/recipes.php';
require_once __DIR__ . '/php/auth.php';
startSession();
$currentUser = getCurrentUser();
$loggedIn    = isLoggedIn();

$slug       = trim(htmlspecialchars($_GET['slug'] ?? '', ENT_QUOTES, 'UTF-8'));
$categories = getCategories($pdo);

// Resolve the category details from slug
$currentCat = null;
foreach ($categories as $cat) {
  if ($cat['slug'] === $slug) { $currentCat = $cat; break; }
}

if (!$currentCat || empty($slug)) {
  header('Location: index.php');
  exit;
}

$recipes = getRecipesByCategory($pdo, $slug, 24);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($currentCat['name']) ?> Recipes — RecipeHub</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

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
            <a href="category.php?slug=<?= htmlspecialchars($cat['slug']) ?>"
               class="nav-link <?= $cat['slug'] === $slug ? 'active' : '' ?>">
              <?= htmlspecialchars($cat['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <form class="header-search" action="search_results.php" method="GET" role="search">
      <input type="search" name="q" placeholder="Search recipes…" class="search-input" aria-label="Search">
      <button type="submit" class="search-btn" aria-label="Submit search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>
    </form>
    <div class="header-auth-btns">
      <?php if ($loggedIn): ?>
        <a href="profile.php" class="btn btn-outline nav-profile-btn">👤 <?= htmlspecialchars($currentUser['username']) ?></a>
        <a href="php/auth.php?logout=1" class="nav-link logout-link">Logout</a>
      <?php else: ?>
        <a href="login.php"    class="btn btn-primary">Login</a>
        <a href="register.php" class="nav-link">Register</a>
      <?php endif; ?>
    </div>
    <button class="mobile-menu-btn" aria-label="Toggle menu" onclick="toggleMobileMenu()">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- Category banner -->
<div class="category-banner">
  <div class="container">
    <span class="category-banner-icon"><?= htmlspecialchars($currentCat['icon']) ?></span>
    <h1 class="category-banner-title"><?= htmlspecialchars($currentCat['name']) ?></h1>
    <p class="category-banner-count"><?= count($recipes) ?> Recipes</p>
  </div>
</div>

<main class="main-content container">
  <div class="recipe-grid">
    <?php if (empty($recipes)): ?>
      <p class="no-results">No recipes in this category yet.</p>
    <?php else: ?>
      <?php foreach ($recipes as $recipe): ?>
        <article class="recipe-card" data-category="<?= htmlspecialchars($recipe['category_slug'] ?? $slug) ?>">
          <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>" class="card-img-link" tabindex="-1">
            <div class="card-img-wrap">
              <img src="<?= htmlspecialchars($recipe['image_url']) ?>"
                   alt="<?= htmlspecialchars($recipe['title']) ?>"
                   class="card-img" loading="lazy" width="400" height="280">
              <span class="difficulty-badge difficulty-<?= htmlspecialchars($recipe['difficulty']) ?>">
                <?= ucfirst(htmlspecialchars($recipe['difficulty'])) ?>
              </span>
            </div>
          </a>
          <div class="card-body">
            <h3 class="card-title">
              <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>">
                <?= htmlspecialchars($recipe['title']) ?>
              </a>
            </h3>
            <p class="card-desc"><?= htmlspecialchars(mb_substr($recipe['description'], 0, 90)) ?>…</p>
            <div class="card-meta">
              <span class="meta-item">⏱ <?= (int)$recipe['prep_time'] + (int)$recipe['cook_time'] ?> min</span>
              <span class="meta-item">Serves <?= (int)$recipe['servings'] ?></span>
            </div>
            <div class="card-actions">
              <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>" class="card-cta">View Recipe →</a>
              <?php if ($loggedIn): ?>
                <?php $faved = isFavorited($pdo, $currentUser['id'], (int)$recipe['id']); ?>
                <button class="fav-btn <?= $faved ? 'fav-btn--active' : '' ?>"
                        data-recipe-id="<?= (int)$recipe['id'] ?>"
                        onclick="toggleFav(this)"
                        aria-label="<?= $faved ? 'Remove from favourites' : 'Add to favourites' ?>">
                  <?= $faved ? '❤️' : '🤍' ?>
                </button>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<footer class="site-footer">
  <div class="footer-bottom"><p>© <?= date('Y') ?> RecipeHub. All rights reserved.</p></div>
</footer>

<style>
/* Category banner styles — unique to this page */
.category-banner {
  background: var(--color-black-2);
  border-bottom: 3px solid var(--color-orange);
  padding: var(--space-xl) 0 var(--space-lg);
  text-align: center;
}
.category-banner-icon  { font-size: 3.5rem; display: block; margin-bottom: 0.5rem; }
.category-banner-title {
  font-family: var(--font-serif);
  font-size: clamp(2rem, 5vw, 3rem);
  color: var(--color-white);
}
.category-banner-count {
  font-size: 1rem;
  color: var(--color-orange);
  font-weight: 600;
  margin-top: 0.5rem;
}
</style>

<script src="js/main.js"></script>
</body>
</html>
