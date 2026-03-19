<?php
/**
 * search_results.php
 * ─────────────────────────────────────────────────────────────
 * Handles GET ?q= search queries.
 * Calls searchRecipes() which uses PDO LIKE prepared statements.
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/php/recipes.php';

// ── Sanitise the search term ──────────────────────────────────
// htmlspecialchars prevents XSS when we reflect the query back in the UI.
// The actual SQL safety is handled by PDO prepared statements in recipes.php.
$rawQuery    = $_GET['q'] ?? '';
$searchTerm  = trim(htmlspecialchars($rawQuery, ENT_QUOTES, 'UTF-8'));
$hasQuery    = strlen($searchTerm) >= 2;

// ── Run search if query is long enough ────────────────────────
$results    = $hasQuery ? searchRecipes($pdo, $searchTerm, 20) : [];
$categories = getCategories($pdo);
$count      = count($results);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Search: "<?= $searchTerm ?>" — RecipeHub</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

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
    <form class="header-search" action="search_results.php" method="GET" role="search">
      <input type="search" name="q"
             value="<?= $searchTerm ?>"
             placeholder="Search recipes…"
             class="search-input" aria-label="Search"
             autofocus>
      <button type="submit" class="search-btn" aria-label="Submit search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>
    </form>
    <button class="mobile-menu-btn" aria-label="Toggle menu" onclick="toggleMobileMenu()">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<main class="main-content container">

  <!-- Search results header -->
  <div class="search-header">
    <?php if ($hasQuery): ?>
      <h1 class="search-title">
        <?php if ($count > 0): ?>
          <?= $count ?> result<?= $count !== 1 ? 's' : '' ?> for
          "<span class="search-term"><?= $searchTerm ?></span>"
        <?php else: ?>
          No results for "<span class="search-term"><?= $searchTerm ?></span>"
        <?php endif; ?>
      </h1>
    <?php else: ?>
      <h1 class="search-title">Search Recipes</h1>
      <p>Enter at least 2 characters to search.</p>
    <?php endif; ?>
  </div>

  <!-- Results grid -->
  <?php if (!empty($results)): ?>
    <div class="recipe-grid">
      <?php foreach ($results as $recipe): ?>
        <article class="recipe-card">
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
            <a href="category.php?slug=<?= htmlspecialchars($recipe['category_slug']) ?>" class="card-category">
              <?= htmlspecialchars($recipe['category_icon']) ?>
              <?= htmlspecialchars($recipe['category_name']) ?>
            </a>
            <h3 class="card-title">
              <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>">
                <?= htmlspecialchars($recipe['title']) ?>
              </a>
            </h3>
            <p class="card-desc">
              <?= htmlspecialchars(mb_substr($recipe['description'], 0, 90)) ?>…
            </p>
            <div class="card-meta">
              <span class="meta-item">⏱ <?= (int)$recipe['prep_time'] + (int)$recipe['cook_time'] ?> min</span>
            </div>
            <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>" class="card-cta">
              View Recipe →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

  <?php elseif ($hasQuery): ?>
    <!-- No results state -->
    <div class="no-results-box">
      <p class="no-results-emoji">🔍</p>
      <h2>Nothing found</h2>
      <p>Try a different keyword, or browse by category:</p>
      <div class="no-results-cats">
        <?php foreach ($categories as $cat): ?>
          <a href="category.php?slug=<?= htmlspecialchars($cat['slug']) ?>" class="category-pill">
            <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <a href="index.php" class="btn btn-primary" style="margin-top:2rem">← Back to All Recipes</a>
    </div>
  <?php endif; ?>

</main>

<footer class="site-footer">
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> RecipeHub. All rights reserved.</p>
  </div>
</footer>

<script src="js/main.js"></script>
</body>
</html>
