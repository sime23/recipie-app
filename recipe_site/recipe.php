<?php
/**
 * recipe.php — Recipe Detail Page
 * ─────────────────────────────────────────────────────────────
 * Displays a single recipe fetched by its URL slug.
 * URL format: recipe.php?slug=authentic-spaghetti-carbonara
 *
 * Layout:
 *  1. Large hero image header
 *  2. Recipe meta (time, servings, difficulty, category)
 *  3. Two-column: Ingredients checklist | Numbered instructions
 *  4. Related recipes sidebar
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/php/recipes.php';
require_once __DIR__ . '/php/auth.php';
startSession();
$currentUser = getCurrentUser();
$loggedIn    = isLoggedIn();

// ── 1. Validate & sanitise the slug from the URL ──────────────
// htmlspecialchars + trim prevents XSS; the PDO query does the
// SQL injection prevention via a prepared statement.
$slug = trim(htmlspecialchars($_GET['slug'] ?? '', ENT_QUOTES, 'UTF-8'));

// If no slug provided, redirect home
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// ── 2. Fetch the recipe (returns null if not found) ───────────
$recipe = getRecipeBySlug($pdo, $slug);

// ── 3. 404 if recipe doesn't exist ───────────────────────────
if ($recipe === null) {
    http_response_code(404);
    // In production: include a custom 404 template
    die('<h1>404 — Recipe Not Found</h1><a href="index.php">Back to Home</a>');
}

// ── 4. Fetch supporting data ──────────────────────────────────
$categories = getCategories($pdo);
// Related recipes: same category, exclude current recipe
$related = getRecipesByCategory($pdo, $recipe['category_slug'], 4);
$related = array_filter($related, fn($r) => $r['slug'] !== $slug);

// ── 5. Computed values ────────────────────────────────────────
$totalTime    = (int)$recipe['prep_time'] + (int)$recipe['cook_time'];
$ingredients  = $recipe['ingredients'];   // already decoded to array by getRecipeBySlug()
$instructions = $recipe['instructions']; // same

$difficultyLabel = ['easy' => '🟢 Easy', 'medium' => '🟡 Medium', 'hard' => '🔴 Hard'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($recipe['title']) ?> — RecipeHub</title>
  <meta name="description" content="<?= htmlspecialchars(mb_substr($recipe['description'], 0, 160)) ?>">
  <!-- Open Graph for social sharing -->
  <meta property="og:title"       content="<?= htmlspecialchars($recipe['title']) ?>">
  <meta property="og:image"       content="<?= htmlspecialchars($recipe['image_url']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars(mb_substr($recipe['description'], 0, 160)) ?>">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="recipe-detail-page">

<!-- ═══════════════════════════════════════════════════════════
     SITE HEADER (same as index.php for consistency)
     ═══════════════════════════════════════════════════════════ -->
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
               class="nav-link <?= $cat['slug'] === $recipe['category_slug'] ? 'active' : '' ?>">
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

<!-- ═══════════════════════════════════════════════════════════
     BREADCRUMB
     ═══════════════════════════════════════════════════════════ -->
<nav class="breadcrumb container" aria-label="Breadcrumb">
  <a href="index.php">Home</a>
  <span aria-hidden="true"> › </span>
  <a href="category.php?slug=<?= htmlspecialchars($recipe['category_slug']) ?>">
    <?= htmlspecialchars($recipe['category_name']) ?>
  </a>
  <span aria-hidden="true"> › </span>
  <span aria-current="page"><?= htmlspecialchars($recipe['title']) ?></span>
</nav>

<!-- ═══════════════════════════════════════════════════════════
     RECIPE HERO — Large header image with gradient overlay
     ═══════════════════════════════════════════════════════════ -->
<div class="recipe-hero"
     style="background-image: url('<?= htmlspecialchars($recipe['image_url']) ?>');"
     role="img"
     aria-label="<?= htmlspecialchars($recipe['title']) ?>">
  <div class="recipe-hero-overlay"></div>
  <div class="recipe-hero-content container">
    <span class="hero-category-badge">
      <?= htmlspecialchars($recipe['category_icon']) ?>
      <?= htmlspecialchars($recipe['category_name']) ?>
    </span>
    <h1 class="recipe-hero-title">
      <?= htmlspecialchars($recipe['title']) ?>
    </h1>
    <?php if ($loggedIn): ?>
      <?php $faved = isFavorited($pdo, $currentUser['id'], (int)$recipe['id']); ?>
      <button class="fav-btn fav-btn--hero <?= $faved ? 'fav-btn--active' : '' ?>"
              id="fav-detail-<?= (int)$recipe['id'] ?>"
              data-recipe-id="<?= (int)$recipe['id'] ?>"
              onclick="toggleFav(this)"
              aria-label="<?= $faved ? 'Remove from favourites' : 'Add to favourites' ?>">
        <?= $faved ? '❤️' : '🤍' ?> <?= $faved ? 'Saved to Favourites' : 'Save to Favourites' ?>
      </button>
    <?php else: ?>
      <a href="login.php" class="fav-btn fav-btn--hero fav-btn--guest">🤍 Login to Save</a>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     RECIPE META BAR — Quick stats at a glance
     ═══════════════════════════════════════════════════════════ -->
<div class="recipe-meta-bar">
  <div class="container meta-bar-inner">

    <div class="meta-stat">
      <span class="meta-stat-label">Prep Time</span>
      <span class="meta-stat-value"><?= (int)$recipe['prep_time'] ?> min</span>
    </div>

    <div class="meta-divider" aria-hidden="true"></div>

    <div class="meta-stat">
      <span class="meta-stat-label">Cook Time</span>
      <span class="meta-stat-value"><?= (int)$recipe['cook_time'] ?> min</span>
    </div>

    <div class="meta-divider" aria-hidden="true"></div>

    <div class="meta-stat">
      <span class="meta-stat-label">Total Time</span>
      <span class="meta-stat-value"><?= $totalTime ?> min</span>
    </div>

    <div class="meta-divider" aria-hidden="true"></div>

    <div class="meta-stat">
      <span class="meta-stat-label">Servings</span>
      <span class="meta-stat-value"><?= (int)$recipe['servings'] ?></span>
    </div>

    <div class="meta-divider" aria-hidden="true"></div>

    <div class="meta-stat">
      <span class="meta-stat-label">Difficulty</span>
      <span class="meta-stat-value"><?= $difficultyLabel[$recipe['difficulty']] ?? 'Medium' ?></span>
    </div>

    <?php if (!empty($recipe['author_name'])): ?>
      <div class="meta-divider" aria-hidden="true"></div>
      <div class="meta-stat">
        <span class="meta-stat-label">By</span>
        <span class="meta-stat-value">Chef <?= htmlspecialchars($recipe['author_name']) ?></span>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MAIN RECIPE CONTENT
     Two-column layout: Ingredients (left) | Instructions (right)
     On mobile, stacks to single column.
     ═══════════════════════════════════════════════════════════ -->
<main class="recipe-main container" id="main-content">

  <!-- Description paragraph -->
  <p class="recipe-description">
    <?= htmlspecialchars($recipe['description']) ?>
  </p>

  <div class="recipe-body">

    <!-- ── LEFT COLUMN: Ingredients ──────────────────────── -->
    <aside class="ingredients-col" aria-label="Ingredients">
      <div class="ingredients-box">

        <h2 class="col-title">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
          </svg>
          Ingredients
        </h2>

        <p class="servings-note">For <?= (int)$recipe['servings'] ?> servings</p>

        <!--
          Checklist: clicking a checkbox strikes through the ingredient.
          This is handled in main.js — helps cooks track progress.
        -->
        <ul class="ingredient-list" role="list">
          <?php foreach ($ingredients as $i => $ingredient): ?>
            <li class="ingredient-item">
              <label class="ingredient-check" for="ing-<?= $i ?>">
                <input
                  type="checkbox"
                  id="ing-<?= $i ?>"
                  class="ingredient-checkbox"
                  aria-label="<?= htmlspecialchars($ingredient) ?>"
                >
                <span class="checkmark" aria-hidden="true"></span>
                <span class="ingredient-text">
                  <?= htmlspecialchars($ingredient) ?>
                </span>
              </label>
            </li>
          <?php endforeach; ?>
        </ul>

        <!-- Print button -->
        <button class="btn btn-outline print-btn" onclick="window.print()">
          🖨️ Print Recipe
        </button>

      </div>
    </aside>

    <!-- ── RIGHT COLUMN: Instructions ────────────────────── -->
    <section class="instructions-col" aria-label="Cooking instructions">

      <h2 class="col-title">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
        </svg>
        Instructions
      </h2>

      <!--
        Numbered steps using CSS counter.
        Clicking a step marks it as "done" (strikethrough + dimmed).
        JS handles the .done class toggle — see main.js.
      -->
      <ol class="instruction-list" role="list">
        <?php foreach ($instructions as $step => $instruction): ?>
          <li class="instruction-step" onclick="toggleStep(this)">
            <span class="step-number" aria-hidden="true"><?= $step + 1 ?></span>
            <div class="step-content">
              <p><?= htmlspecialchars($instruction) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>

    </section>

  </div><!-- /.recipe-body -->

</main><!-- /.recipe-main -->

<!-- ═══════════════════════════════════════════════════════════
     RELATED RECIPES
     Same category, different slug — up to 3 cards shown.
     ═══════════════════════════════════════════════════════════ -->
<?php if (!empty($related)): ?>
<section class="related-section">
  <div class="container">
    <h2 class="section-title">
      More <?= htmlspecialchars($recipe['category_name']) ?> Recipes
    </h2>
    <div class="recipe-grid recipe-grid--related">
      <?php foreach (array_slice($related, 0, 3) as $rel): ?>
        <article class="recipe-card">
          <a href="recipe.php?slug=<?= htmlspecialchars($rel['slug']) ?>" class="card-img-link" tabindex="-1">
            <div class="card-img-wrap">
              <img src="<?= htmlspecialchars($rel['image_url']) ?>"
                   alt="<?= htmlspecialchars($rel['title']) ?>"
                   class="card-img" loading="lazy" width="400" height="280">
              <span class="difficulty-badge difficulty-<?= htmlspecialchars($rel['difficulty']) ?>">
                <?= ucfirst(htmlspecialchars($rel['difficulty'])) ?>
              </span>
            </div>
          </a>
          <div class="card-body">
            <h3 class="card-title">
              <a href="recipe.php?slug=<?= htmlspecialchars($rel['slug']) ?>">
                <?= htmlspecialchars($rel['title']) ?>
              </a>
            </h3>
            <div class="card-meta">
              <span class="meta-item">
                ⏱ <?= (int)$rel['prep_time'] + (int)$rel['cook_time'] ?> min
              </span>
            </div>
            <a href="recipe.php?slug=<?= htmlspecialchars($rel['slug']) ?>" class="card-cta">
              View Recipe →
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FOOTER (shared) -->
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="logo-text">Recipe<strong>Hub</strong></span>
      <p>Curated recipes for every occasion.</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> RecipeHub. All rights reserved.</p>
  </div>
</footer>

<script src="js/main.js"></script>
</body>
</html>
