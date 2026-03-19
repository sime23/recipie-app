<?php
/**
 * profile.php — User Profile Page
 * ─────────────────────────────────────────────────────────────
 * Sidebar layout:
 *  - My Recipes (recipes authored by the user)
 *  - My Favourites (recipes the user has marked as favourite)
 * Also contains a "Create New Recipe" modal form.
 * ─────────────────────────────────────────────────────────────
 */
require_once __DIR__ . '/php/auth.php';
require_once __DIR__ . '/php/recipes.php';

startSession();
requireLogin('login.php');   // redirect if not logged in

$user = getCurrentUser();

// ── Handle create-recipe form submission ──────────────────────
$formError   = '';
$formSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_recipe') {
    $required = ['title', 'category_id', 'description', 'ingredients', 'instructions'];
    $missing  = false;
    foreach ($required as $field) {
        if (empty(trim($_POST[$field] ?? ''))) { $missing = true; break; }
    }

    if ($missing) {
        $formError = 'Please fill in all required fields.';
    } else {
        try {
            $newId = createRecipe($pdo, [
                'category_id' => (int) $_POST['category_id'],
                'author_id'   => $user['id'],
                'title'       => trim($_POST['title']),
                'description' => trim($_POST['description']),
                'ingredients' => trim($_POST['ingredients']),
                'instructions'=> trim($_POST['instructions']),
                'prep_time'   => (int) ($_POST['prep_time']  ?? 0),
                'cook_time'   => (int) ($_POST['cook_time']  ?? 0),
                'servings'    => (int) ($_POST['servings']   ?? 4),
                'difficulty'  => $_POST['difficulty']  ?? 'medium',
                'image_url'   => trim($_POST['image_url'] ?? ''),
            ]);
            $formSuccess = 'Recipe created successfully!';
        } catch (Exception $e) {
            $formError = 'Error creating recipe: ' . $e->getMessage();
        }
    }
}

// ── Fetch data for active tab ─────────────────────────────────
$activeTab     = $_GET['tab'] ?? 'my-recipes';
$categories    = getCategories($pdo);
$myRecipes     = getUserRecipes($pdo, $user['id']);
$myFavourites  = getUserFavorites($pdo, $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — RecipeHub</title>
  <meta name="description" content="Manage your recipes and favourites on RecipeHub.">
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body class="profile-page">

<!-- ═══════════════════════════════════════════════════════════
     SITE HEADER
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
            <a href="category.php?slug=<?= htmlspecialchars($cat['slug']) ?>" class="nav-link">
              <?= htmlspecialchars($cat['name']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="header-auth-btns">
      <a href="profile.php" class="btn btn-outline nav-profile-btn active">👤 My Profile</a>
      <a href="php/auth.php?logout=1" class="nav-link logout-link">Logout</a>
    </div>
  </div>
</header>

<!-- ═══════════════════════════════════════════════════════════
     PROFILE HERO BANNER
     ═══════════════════════════════════════════════════════════ -->
<div class="profile-hero">
  <div class="container profile-hero-inner">
    <div class="profile-avatar">
      <?= strtoupper(substr($user['username'], 0, 1)) ?>
    </div>
    <div class="profile-hero-info">
      <h1 class="profile-username"><?= htmlspecialchars($user['username']) ?></h1>
      <p class="profile-stats">
        <span>📋 <?= count($myRecipes) ?> recipe<?= count($myRecipes) !== 1 ? 's' : '' ?></span>
        <span class="stat-dot">·</span>
        <span>❤️ <?= count($myFavourites) ?> favourite<?= count($myFavourites) !== 1 ? 's' : '' ?></span>
      </p>
    </div>
    <button class="btn btn-primary create-recipe-trigger" onclick="openCreateModal()">
      + Create New Recipe
    </button>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     PROFILE LAYOUT (Sidebar + Content)
     ═══════════════════════════════════════════════════════════ -->
<div class="container profile-layout">

  <!-- Sidebar -->
  <aside class="profile-sidebar">
    <nav class="sidebar-nav" aria-label="Profile navigation">
      <a href="profile.php?tab=my-recipes"
         class="sidebar-nav-btn <?= $activeTab === 'my-recipes'   ? 'active' : '' ?>">
        <span class="sidebar-icon">📋</span>
        <span class="sidebar-label">My Recipes</span>
        <span class="sidebar-count"><?= count($myRecipes) ?></span>
      </a>
      <a href="profile.php?tab=favourites"
         class="sidebar-nav-btn <?= $activeTab === 'favourites' ? 'active' : '' ?>">
        <span class="sidebar-icon">❤️</span>
        <span class="sidebar-label">My Favourites</span>
        <span class="sidebar-count"><?= count($myFavourites) ?></span>
      </a>
    </nav>
  </aside>

  <!-- Main content area -->
  <main class="profile-content">

    <?php if ($formSuccess): ?>
      <div class="profile-alert profile-alert--success">✅ <?= htmlspecialchars($formSuccess) ?></div>
    <?php endif; ?>
    <?php if ($formError): ?>
      <div class="profile-alert profile-alert--error">⚠️ <?= htmlspecialchars($formError) ?></div>
    <?php endif; ?>

    <!-- ── MY RECIPES TAB ── -->
    <?php if ($activeTab === 'my-recipes'): ?>
      <div class="profile-tab-header">
        <h2 class="profile-tab-title">📋 My Recipes</h2>
        <button class="btn btn-primary btn-sm" onclick="openCreateModal()">+ New Recipe</button>
      </div>

      <?php if (empty($myRecipes)): ?>
        <div class="profile-empty">
          <span class="empty-icon">🍳</span>
          <h3>No recipes yet</h3>
          <p>Share your first recipe with the RecipeHub community!</p>
          <button class="btn btn-primary" onclick="openCreateModal()">Create Your First Recipe</button>
        </div>
      <?php else: ?>
        <div class="recipe-grid">
          <?php foreach ($myRecipes as $recipe): ?>
            <article class="recipe-card" data-category="<?= htmlspecialchars($recipe['category_slug']) ?>">
              <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>" class="card-img-link" tabindex="-1">
                <div class="card-img-wrap">
                  <img
                    src="<?= htmlspecialchars($recipe['image_url'] ?: 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=800&q=80') ?>"
                    alt="<?= htmlspecialchars($recipe['title']) ?>"
                    class="card-img" loading="lazy" width="400" height="280"
                  >
                  <span class="difficulty-badge difficulty-<?= htmlspecialchars($recipe['difficulty']) ?>">
                    <?= ucfirst(htmlspecialchars($recipe['difficulty'])) ?>
                  </span>
                  <span class="my-recipe-badge">My Recipe</span>
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
                <p class="card-desc"><?= htmlspecialchars(mb_substr($recipe['description'], 0, 90)) ?>…</p>
                <div class="card-meta">
                  <span class="meta-item">⏱ <?= (int)$recipe['prep_time'] + (int)$recipe['cook_time'] ?> min</span>
                  <span class="meta-item">🍽️ Serves <?= (int)$recipe['servings'] ?></span>
                </div>
                <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>" class="card-cta">
                  View Recipe
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                  </svg>
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    <!-- ── MY FAVOURITES TAB ── -->
    <?php elseif ($activeTab === 'favourites'): ?>
      <div class="profile-tab-header">
        <h2 class="profile-tab-title">❤️ My Favourites</h2>
      </div>

      <?php if (empty($myFavourites)): ?>
        <div class="profile-empty">
          <span class="empty-icon">🤍</span>
          <h3>No favourites yet</h3>
          <p>Browse recipes and click the ❤️ heart button to save your favourites here.</p>
          <a href="index.php" class="btn btn-primary">Browse Recipes</a>
        </div>
      <?php else: ?>
        <div class="recipe-grid">
          <?php foreach ($myFavourites as $recipe): ?>
            <article class="recipe-card" data-category="<?= htmlspecialchars($recipe['category_slug']) ?>">
              <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>" class="card-img-link" tabindex="-1">
                <div class="card-img-wrap">
                  <img
                    src="<?= htmlspecialchars($recipe['image_url']) ?>"
                    alt="<?= htmlspecialchars($recipe['title']) ?>"
                    class="card-img" loading="lazy" width="400" height="280"
                  >
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
                <p class="card-desc"><?= htmlspecialchars(mb_substr($recipe['description'], 0, 90)) ?>…</p>
                <div class="card-meta">
                  <span class="meta-item">⏱ <?= (int)$recipe['prep_time'] + (int)$recipe['cook_time'] ?> min</span>
                  <span class="meta-item">🍽️ Serves <?= (int)$recipe['servings'] ?></span>
                </div>
                <div class="card-actions">
                  <a href="recipe.php?slug=<?= htmlspecialchars($recipe['slug']) ?>" class="card-cta">
                    View Recipe
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                      <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                  </a>
                  <button class="fav-btn fav-btn--active"
                          data-recipe-id="<?= (int)$recipe['id'] ?>"
                          onclick="toggleFav(this)"
                          aria-label="Remove from favourites"
                          title="Remove from favourites">
                    ❤️
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

  </main>
</div><!-- /.profile-layout -->

<!-- ═══════════════════════════════════════════════════════════
     CREATE RECIPE MODAL
     ═══════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="createModal" role="dialog" aria-modal="true" aria-label="Create new recipe">
  <div class="modal-box">
    <div class="modal-header">
      <h2 class="modal-title">🍴 Create New Recipe</h2>
      <button class="modal-close" onclick="closeCreateModal()" aria-label="Close">✕</button>
    </div>

    <form class="create-recipe-form" method="POST" action="profile.php?tab=my-recipes">
      <input type="hidden" name="action" value="create_recipe">

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="cr-title">Recipe Title <span class="required">*</span></label>
          <input type="text" id="cr-title" name="title" class="form-input" placeholder="e.g. Classic Shakshuka" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="cr-category">Category <span class="required">*</span></label>
          <select id="cr-category" name="category_id" class="form-input form-select" required>
            <option value="">— Select a category —</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>">
                <?= htmlspecialchars($cat['icon']) ?> <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="cr-description">Description <span class="required">*</span></label>
        <textarea id="cr-description" name="description" class="form-input form-textarea" rows="2" placeholder="Short description of your recipe…" required></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="cr-ingredients">
          Ingredients <span class="required">*</span>
          <span class="form-hint">One per line or comma-separated</span>
        </label>
        <textarea id="cr-ingredients" name="ingredients" class="form-input form-textarea" rows="5"
                  placeholder="2 tbsp olive oil&#10;1 onion, diced&#10;4 garlic cloves" required></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="cr-instructions">
          Instructions <span class="required">*</span>
          <span class="form-hint">One step per line</span>
        </label>
        <textarea id="cr-instructions" name="instructions" class="form-input form-textarea" rows="5"
                  placeholder="Step 1: Heat the pan over medium heat&#10;Step 2: Add olive oil…" required></textarea>
      </div>

      <div class="form-grid-3">
        <div class="form-group">
          <label class="form-label" for="cr-prep">Prep Time (min)</label>
          <input type="number" id="cr-prep" name="prep_time" class="form-input" min="0" value="10">
        </div>
        <div class="form-group">
          <label class="form-label" for="cr-cook">Cook Time (min)</label>
          <input type="number" id="cr-cook" name="cook_time" class="form-input" min="0" value="20">
        </div>
        <div class="form-group">
          <label class="form-label" for="cr-servings">Servings</label>
          <input type="number" id="cr-servings" name="servings" class="form-input" min="1" value="4">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="cr-difficulty">Difficulty</label>
          <select id="cr-difficulty" name="difficulty" class="form-input form-select">
            <option value="easy">🟢 Easy</option>
            <option value="medium" selected>🟡 Medium</option>
            <option value="hard">🔴 Hard</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="cr-image">Image URL</label>
          <input type="url" id="cr-image" name="image_url" class="form-input" placeholder="https://…">
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Publish Recipe →</button>
      </div>
    </form>
  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> RecipeHub. All rights reserved.</p>
  </div>
</footer>

<script src="js/main.js"></script>
<script>
// ── Modal open/close ──────────────────────────────────────────
function openCreateModal() {
  document.getElementById('createModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeCreateModal() {
  document.getElementById('createModal').classList.remove('open');
  document.body.style.overflow = '';
}
// Close on overlay click
document.getElementById('createModal').addEventListener('click', function(e) {
  if (e.target === this) closeCreateModal();
});

// ── Favourite toggle (for removing from favourites tab) ───────
function toggleFav(btn) {
  const recipeId = btn.dataset.recipeId;
  btn.disabled = true;

  const formData = new FormData();
  formData.append('recipe_id', recipeId);

  fetch('php/favorite_action.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.error) { alert(data.error); btn.disabled = false; return; }
      if (!data.favorited) {
        // Remove the card from the favourites view
        const card = btn.closest('.recipe-card');
        if (card) card.remove();
      }
    })
    .catch(() => { btn.disabled = false; });
}

// Auto-open modal if there was a form error
<?php if ($formError && ($activeTab === 'my-recipes' || isset($_POST['action']))): ?>
  openCreateModal();
<?php endif; ?>
</script>

</body>
</html>
