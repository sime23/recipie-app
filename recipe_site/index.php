<?php
/**
 * index.php — Homepage
 * ─────────────────────────────────────────────────────────────
 * Renders the homepage with:
 *  1. Full-width hero featuring the top 3 featured recipes
 *  2. Category filter bar (populated from DB)
 *  3. Responsive CSS Grid of recipe cards
 *  4. Search bar (GET request → search_results.php)
 *
 * All data is fetched via prepared PDO statements in recipes.php.
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/php/recipes.php';  // also requires db_connect.php
require_once __DIR__ . '/php/auth.php';
startSession();
$currentUser = getCurrentUser();
$loggedIn    = isLoggedIn();

// ── Fetch data from database ──────────────────────────────────
$featuredRecipes = getFeaturedRecipes($pdo, 3);   // hero section
$categories      = getCategories($pdo);            // nav + filter bar
$recipes         = getRecipes($pdo, 12, 0);        // main card grid

// ── Pagination setup ─────────────────────────────────────────
$totalRecipes = getTotalRecipeCount($pdo);
$perPage      = 12;
$totalPages   = (int) ceil($totalRecipes / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>RecipeHub — Discover Extraordinary Recipes</title>
  <meta name="description" content="Curated recipes from around the world. Find your next favourite dish.">
  <link rel="stylesheet" href="css/style.css">
  <!-- Google Fonts: Playfair Display (serif) + DM Sans (sans-serif) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     SITE HEADER / NAVIGATION
     ═══════════════════════════════════════════════════════════ -->
<header class="site-header">
  <div class="container header-inner">

    <!-- Logo -->
    <a href="index.php" class="logo">
      <span class="logo-icon">🔥</span>
      <span class="logo-text">Recipe<strong>Hub</strong></span>
    </a>

    <!-- Primary Navigation -->
    <nav class="primary-nav" aria-label="Main navigation">
      <ul>
        <li><a href="index.php" class="nav-link active">Home</a></li>
        <!-- Loop through categories fetched from DB -->
        <?php foreach ($categories as $cat): ?>
          <li>
            <a href="category.php?slug=<?= htmlspecialchars($cat['slug']) ?>"
               class="nav-link">
              <?= htmlspecialchars($cat['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <!-- Search Form (GET → search_results.php) -->
    <form class="header-search" action="search_results.php" method="GET" role="search">
      <input
        type="search"
        name="q"
        placeholder="Search recipes…"
        aria-label="Search recipes"
        class="search-input"
        minlength="2"
        maxlength="100"
      >
      <button type="submit" class="search-btn" aria-label="Submit search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>
    </form>

    <!-- Auth buttons -->
    <div class="header-auth-btns">
      <?php if ($loggedIn): ?>
        <a href="profile.php" class="btn btn-outline nav-profile-btn">👤 <?= htmlspecialchars($currentUser['username']) ?></a>
        <a href="php/auth.php?logout=1" class="nav-link logout-link">Logout</a>
      <?php else: ?>
        <a href="login.php"    class="btn btn-primary"  id="loginBtn">Login</a>
        <a href="register.php" class="nav-link"         id="registerBtn">Register</a>
      <?php endif; ?>
    </div>

    <!-- Mobile menu toggle -->
    <button class="mobile-menu-btn" aria-label="Toggle menu" onclick="toggleMobileMenu()">
      <span></span><span></span><span></span>
    </button>

  </div>
</header>

<!-- ═══════════════════════════════════════════════════════════
     HERO SECTION
     Featured recipes displayed as an auto-playing slider.
     ═══════════════════════════════════════════════════════════ -->
<section class="hero" aria-label="Featured recipes">

  <?php if (!empty($featuredRecipes)): ?>
    <div class="hero-slider" id="heroSlider">

      <?php foreach ($featuredRecipes as $i => $recipe): ?>
        <!-- Each slide is a full-bleed image with an overlay -->
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
             style="background-image: url('<?= htmlspecialchars($recipe['image_url']) ?>')">

          <div class="hero-overlay"></div>

          <div class="hero-content container">
            <span class="hero-category-badge">
              <?= htmlspecialchars($recipe['category_icon']) ?>
              <?= htmlspecialchars($recipe['category_name']) ?>
            </span>

            <h1 class="hero-title">
              <?= htmlspecialchars($recipe['title']) ?>
            </h1>

            <p class="hero-desc">
              <?= htmlspecialchars(mb_substr($recipe['description'], 0, 140)) ?>…
            </p>

            <!-- Recipe meta (prep time + difficulty) -->
            <div class="hero-meta">
              <span>⏱ <?= (int)$recipe['prep_time'] + (int)$recipe['cook_time'] ?> min</span>
              <span class="meta-dot">·</span>
              <span><?= ucfirst(htmlspecialchars($recipe['difficulty'])) ?></span>
            </div>

            <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>"
               class="btn btn-primary">
              View Recipe →
            </a>
          </div>

        </div>
      <?php endforeach; ?>

    </div><!-- /.hero-slider -->

    <!-- Slider dot navigation -->
    <div class="hero-dots" role="tablist" aria-label="Slide navigation">
      <?php for ($i = 0; $i < count($featuredRecipes); $i++): ?>
        <button class="hero-dot <?= $i === 0 ? 'active' : '' ?>"
                role="tab"
                aria-label="Slide <?= $i + 1 ?>"
                onclick="goToSlide(<?= $i ?>)">
        </button>
      <?php endfor; ?>
    </div>

  <?php endif; ?>

</section><!-- /.hero -->

<!-- ═══════════════════════════════════════════════════════════
     CATEGORY FILTER BAR
     Horizontal scrollable row of clickable category pills.
     Clicking a pill dynamically filters the card grid via JS.
     ═══════════════════════════════════════════════════════════ -->
<section class="category-bar-section">
  <div class="container">
    <div class="category-bar" role="tablist" aria-label="Filter by category">

      <button class="category-pill active" data-category="all" role="tab" aria-selected="true">
        🍽️ All Recipes
      </button>

      <?php foreach ($categories as $cat): ?>
        <button class="category-pill"
                data-category="<?= htmlspecialchars($cat['slug']) ?>"
                role="tab" aria-selected="false">
          <?= htmlspecialchars($cat['icon']) ?>
          <?= htmlspecialchars($cat['name']) ?>
          <span class="pill-count"><?= (int)$cat['recipe_count'] ?></span>
        </button>
      <?php endforeach; ?>

    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     RECIPE CARD GRID
     CSS Grid layout — 4 cols desktop, 2 cols tablet, 1 col mobile.
     Each card links to recipe.php?slug=…
     ═══════════════════════════════════════════════════════════ -->
<main class="main-content">
  <div class="container">

    <div class="section-header">
      <h2 class="section-title">Latest Recipes</h2>
      <p class="section-subtitle"><?= $totalRecipes ?> recipes and counting</p>
    </div>

    <!--
      .recipe-grid uses CSS Grid.
      data-category attribute on each card allows JS filtering
      without a page reload — better UX for the category pills above.
    -->
    <div class="recipe-grid" id="recipeGrid">

      <?php if (empty($recipes)): ?>
        <p class="no-results">No recipes found. Check back soon!</p>

      <?php else: ?>
        <?php foreach ($recipes as $recipe): ?>

          <article class="recipe-card"
                   data-category="<?= htmlspecialchars($recipe['category_slug']) ?>"
                   aria-label="<?= htmlspecialchars($recipe['title']) ?>">

            <!-- Thumbnail image with lazy loading -->
            <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>"
               class="card-img-link" tabindex="-1" aria-hidden="true">
              <div class="card-img-wrap">
                <img
                  src="<?= htmlspecialchars($recipe['image_url']) ?>"
                  alt="<?= htmlspecialchars($recipe['title']) ?>"
                  class="card-img"
                  loading="lazy"
                  width="400" height="280"
                >
                <!-- Difficulty badge overlaid on image -->
                <span class="difficulty-badge difficulty-<?= htmlspecialchars($recipe['difficulty']) ?>">
                  <?= ucfirst(htmlspecialchars($recipe['difficulty'])) ?>
                </span>
              </div>
            </a>

            <div class="card-body">

              <!-- Category tag -->
              <a href="category.php?slug=<?= htmlspecialchars($recipe['category_slug']) ?>"
                 class="card-category">
                <?= htmlspecialchars($recipe['category_icon']) ?>
                <?= htmlspecialchars($recipe['category_name']) ?>
              </a>

              <!-- Recipe title -->
              <h3 class="card-title">
                <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>">
                  <?= htmlspecialchars($recipe['title']) ?>
                </a>
              </h3>

              <!-- Short description (truncated) -->
              <p class="card-desc">
                <?= htmlspecialchars(mb_substr($recipe['description'], 0, 90)) ?>…
              </p>

              <!-- Time meta row -->
              <div class="card-meta">
                <span class="meta-item">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                  </svg>
                  <?= (int)$recipe['prep_time'] + (int)$recipe['cook_time'] ?> min
                </span>
                <span class="meta-item">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                  </svg>
                  Serves <?= (int)$recipe['servings'] ?>
                </span>
              </div>

              <!-- CTA + Favourite row -->
              <div class="card-actions">
                <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>"
                   class="card-cta">
                  View Recipe
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                  </svg>
                </a>
                <?php if ($loggedIn): ?>
                  <?php $faved = isFavorited($pdo, $currentUser['id'], (int)$recipe['id']); ?>
                  <button class="fav-btn <?= $faved ? 'fav-btn--active' : '' ?>"
                          id="fav-<?= (int)$recipe['id'] ?>"
                          data-recipe-id="<?= (int)$recipe['id'] ?>"
                          onclick="toggleFav(this)"
                          aria-label="<?= $faved ? 'Remove from favourites' : 'Add to favourites' ?>"
                          title="<?= $faved ? 'Remove from favourites' : 'Add to favourites' ?>">
                    <?= $faved ? '❤️' : '🤍' ?>
                  </button>
                <?php endif; ?>
              </div>

            </div><!-- /.card-body -->

          </article>

        <?php endforeach; ?>
      <?php endif; ?>

    </div><!-- /.recipe-grid -->

    <!-- ── Pagination ──────────────────────────────────────── -->
    <?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="Recipe pages">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <a href="?page=<?= $p ?>"
             class="page-btn <?= $p === 1 ? 'active' : '' ?>"
             aria-current="<?= $p === 1 ? 'page' : 'false' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>

  </div><!-- /.container -->
</main>

<!-- ═══════════════════════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════════════════════ -->
<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="logo-text">Recipe<strong>Hub</strong></span>
      <p>Curated recipes for every occasion.</p>
    </div>
    <div class="footer-links">
      <h4>Explore</h4>
      <ul>
        <?php foreach ($categories as $cat): ?>
          <li>
            <a href="category.php?slug=<?= htmlspecialchars($cat['slug']) ?>">
              <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="footer-links">
      <h4>RecipeHub</h4>
      <ul>
        <li><a href="#">About</a></li>
        <li><a href="#">Submit a Recipe</a></li>
        <li><a href="#">Newsletter</a></li>
        <li><a href="#">Privacy Policy</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> RecipeHub. All rights reserved.</p>
  </div>
</footer>

<script src="js/main.js"></script>
</body>
</html>
