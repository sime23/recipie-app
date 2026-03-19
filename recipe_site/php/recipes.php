<?php
/**
 * recipes.php
 * ─────────────────────────────────────────────────────────────
 * Data-access layer for the recipe website.
 * All SQL queries use PDO prepared statements — zero raw
 * user input ever reaches the database engine directly.
 *
 * Functions:
 *  - getRecipes()        → paginated homepage list
 *  - getRecipeBySlug()   → single recipe detail page
 *  - getFeaturedRecipes()→ hero carousel
 *  - searchRecipes()     → LIKE-based keyword search
 *  - getCategories()     → sidebar / nav categories
 *  - getRecipesByCategory() → filtered list
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/db_connect.php';  // brings in $pdo

// ════════════════════════════════════════════════════════════
// FUNCTION: getRecipes
// Returns a paginated list of all recipes with their category.
// ════════════════════════════════════════════════════════════
function getRecipes(PDO $pdo, int $limit = 12, int $offset = 0): array
{
    /*
     * JOIN explanation:
     *  recipes LEFT JOIN categories → we always get the recipe row
     *  even if category_id has no match (defensive coding).
     * LIMIT + OFFSET → supports pagination (page 1, 2, 3…).
     * Prepared statement uses :limit / :offset placeholders —
     * the PDO driver replaces them safely, escaping any special chars.
     */
    $sql = "
        SELECT
            r.id,
            r.title,
            r.slug,
            r.description,
            r.prep_time,
            r.cook_time,
            r.servings,
            r.difficulty,
            r.image_url,
            r.featured,
            r.created_at,
            c.name  AS category_name,
            c.slug  AS category_slug,
            c.icon  AS category_icon
        FROM   recipes    r
        LEFT JOIN categories c ON c.id = r.category_id
        ORDER BY r.featured DESC, r.created_at DESC
        LIMIT  :limit
        OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    // bindValue with explicit type prevents integer-as-string bugs in LIMIT/OFFSET
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();  // returns [] if no rows, never false
}

// ════════════════════════════════════════════════════════════
// FUNCTION: getRecipeBySlug
// Fetches a single recipe for the detail page.
// Using the slug (not ID) keeps URLs human-readable & SEO-friendly.
// ════════════════════════════════════════════════════════════
function getRecipeBySlug(PDO $pdo, string $slug): ?array
{
    $sql = "
        SELECT
            r.*,
            c.name AS category_name,
            c.slug AS category_slug,
            c.icon AS category_icon,
            u.username AS author_name
        FROM   recipes    r
        LEFT JOIN categories c ON c.id = r.category_id
        LEFT JOIN users      u ON u.id = r.author_id
        WHERE  r.slug = :slug
        LIMIT  1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':slug' => $slug]);  // named placeholder → safe

    $row = $stmt->fetch();

    if (!$row) {
        return null;  // caller should 404
    }

    // Decode JSON columns into PHP arrays for easy template iteration
    $row['ingredients']  = json_decode($row['ingredients'],  true) ?? [];
    $row['instructions'] = json_decode($row['instructions'], true) ?? [];

    return $row;
}

// ════════════════════════════════════════════════════════════
// FUNCTION: getFeaturedRecipes
// Pulls only rows marked featured = 1 for the homepage hero.
// ════════════════════════════════════════════════════════════
function getFeaturedRecipes(PDO $pdo, int $limit = 3): array
{
    $sql = "
        SELECT r.title, r.slug, r.description, r.image_url,
               r.prep_time, r.cook_time, r.difficulty,
               c.name AS category_name, c.icon AS category_icon
        FROM   recipes r
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE  r.featured = 1
        ORDER BY r.created_at DESC
        LIMIT  :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: searchRecipes
// Keyword search using LIKE on title + description.
//
// Security note:
//  The search term is bound as a parameter — PDO wraps it
//  in quotes and escapes % _ \ characters automatically,
//  so users cannot inject SQL or break the LIKE pattern.
//
// For production with millions of rows, swap to FULLTEXT
// MATCH()…AGAINST() — also shown commented below.
// ════════════════════════════════════════════════════════════
function searchRecipes(PDO $pdo, string $term, int $limit = 20): array
{
    // Sanitise for LIKE: escape literal %, _, and \ characters
    $safeTerm = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($term));
    $likeTerm = '%' . $safeTerm . '%';

    /*
     * LIKE query — works on any MySQL setup, no special indexes needed.
     * The :term placeholder is bound once; PDO reuses it for both columns.
     */
    $sql = "
        SELECT
            r.id, r.title, r.slug, r.description,
            r.image_url, r.prep_time, r.cook_time, r.difficulty,
            c.name AS category_name, c.icon AS category_icon
        FROM   recipes r
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE  r.title       LIKE :term
            OR r.description LIKE :term2
        ORDER BY
            CASE WHEN r.title LIKE :term3 THEN 0 ELSE 1 END,  -- title matches first
            r.created_at DESC
        LIMIT  :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':term',  $likeTerm, PDO::PARAM_STR);
    $stmt->bindValue(':term2', $likeTerm, PDO::PARAM_STR);
    $stmt->bindValue(':term3', $likeTerm, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit,    PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: getCategories
// Used to render the navigation and sidebar category list.
// ════════════════════════════════════════════════════════════
function getCategories(PDO $pdo): array
{
    /*
     * COUNT(r.id) → how many recipes are in each category.
     * GROUP BY c.id groups the joined rows back per category.
     * HAVING COUNT > 0 hides empty categories from the nav.
     */
    $sql = "
        SELECT c.id, c.name, c.slug, c.icon,
               COUNT(r.id) AS recipe_count
        FROM   categories c
        LEFT JOIN recipes r ON r.category_id = c.id
        GROUP BY c.id, c.name, c.slug, c.icon
        HAVING recipe_count > 0
        ORDER BY c.name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: getRecipesByCategory
// Filters recipe list by category slug for the category page.
// ════════════════════════════════════════════════════════════
function getRecipesByCategory(PDO $pdo, string $categorySlug, int $limit = 12): array
{
    $sql = "
        SELECT
            r.id, r.title, r.slug, r.description,
            r.image_url, r.prep_time, r.cook_time, r.difficulty, r.servings,
            c.name AS category_name, c.icon AS category_icon
        FROM   recipes    r
        INNER JOIN categories c ON c.id = r.category_id
        WHERE  c.slug = :slug
        ORDER BY r.created_at DESC
        LIMIT  :limit
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':slug',  $categorySlug, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit,        PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: getTotalRecipeCount
// For pagination: how many pages do we need?
// ════════════════════════════════════════════════════════════
function getTotalRecipeCount(PDO $pdo): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM recipes");
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: getUserRecipes
// Returns all recipes created by a specific user.
// ════════════════════════════════════════════════════════════
function getUserRecipes(PDO $pdo, int $userId): array
{
    $sql = "
        SELECT
            r.id, r.title, r.slug, r.description,
            r.image_url, r.prep_time, r.cook_time, r.difficulty, r.servings,
            r.created_at,
            c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon
        FROM   recipes r
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE  r.author_id = :uid
        ORDER BY r.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: getUserFavorites
// Returns all recipes that a user has favourited.
// ════════════════════════════════════════════════════════════
function getUserFavorites(PDO $pdo, int $userId): array
{
    $sql = "
        SELECT
            r.id, r.title, r.slug, r.description,
            r.image_url, r.prep_time, r.cook_time, r.difficulty, r.servings,
            c.name AS category_name, c.slug AS category_slug, c.icon AS category_icon,
            uf.created_at AS favorited_at
        FROM   user_favorites uf
        JOIN   recipes     r ON r.id = uf.recipe_id
        LEFT JOIN categories c ON c.id = r.category_id
        WHERE  uf.user_id = :uid
        ORDER BY uf.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: isFavorited
// Returns true if the given user has favourited the recipe.
// ════════════════════════════════════════════════════════════
function isFavorited(PDO $pdo, int $userId, int $recipeId): bool
{
    $stmt = $pdo->prepare(
        "SELECT 1 FROM user_favorites WHERE user_id = :uid AND recipe_id = :rid LIMIT 1"
    );
    $stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
    return (bool) $stmt->fetchColumn();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: toggleFavorite
// Adds the recipe to favourites if not already there; removes if it is.
// Returns true if the recipe is now favourited, false if removed.
// ════════════════════════════════════════════════════════════
function toggleFavorite(PDO $pdo, int $userId, int $recipeId): bool
{
    if (isFavorited($pdo, $userId, $recipeId)) {
        $stmt = $pdo->prepare(
            "DELETE FROM user_favorites WHERE user_id = :uid AND recipe_id = :rid"
        );
        $stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
        return false; // now un-favourited
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO user_favorites (user_id, recipe_id) VALUES (:uid, :rid)"
        );
        $stmt->execute([':uid' => $userId, ':rid' => $recipeId]);
        return true; // now favourited
    }
}

// ════════════════════════════════════════════════════════════
// FUNCTION: createRecipe
// Inserts a new recipe submitted by a logged-in user.
// Returns the new recipe's ID.
// ════════════════════════════════════════════════════════════
function createRecipe(PDO $pdo, array $data): int
{
    // Build a URL-safe slug from the title
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9\-]+/', '-', $data['title'])));
    $slug = trim($slug, '-');

    // Ensure slug uniqueness by appending a random suffix if needed
    $check = $pdo->prepare("SELECT id FROM recipes WHERE slug = :s LIMIT 1");
    $check->execute([':s' => $slug]);
    if ($check->fetch()) {
        $slug .= '-' . substr(uniqid(), -5);
    }

    // Parse comma/newline-separated ingredients and instructions into JSON arrays
    $ingredients  = array_values(array_filter(array_map('trim',
        preg_split('/[\r\n,]+/', $data['ingredients']))));
    $instructions = array_values(array_filter(array_map('trim',
        preg_split('/[\r\n]+/', $data['instructions']))));

    $sql = "
        INSERT INTO recipes
          (category_id, author_id, title, slug, description,
           ingredients, instructions,
           prep_time, cook_time, servings, difficulty, image_url, featured)
        VALUES
          (:cat, :author, :title, :slug, :desc,
           :ing, :inst,
           :prep, :cook, :serv, :diff, :img, 0)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cat'    => (int)   $data['category_id'],
        ':author' => (int)   $data['author_id'],
        ':title'  =>         $data['title'],
        ':slug'   =>         $slug,
        ':desc'   =>         $data['description'],
        ':ing'    =>         json_encode($ingredients),
        ':inst'   =>         json_encode($instructions),
        ':prep'   => (int)   ($data['prep_time']  ?? 0),
        ':cook'   => (int)   ($data['cook_time']  ?? 0),
        ':serv'   => (int)   ($data['servings']   ?? 4),
        ':diff'   =>         ($data['difficulty'] ?? 'medium'),
        ':img'    =>         ($data['image_url']  ?? ''),
    ]);

    return (int) $pdo->lastInsertId();
}

// ════════════════════════════════════════════════════════════
// FUNCTION: deleteRecipe
// Deletes a recipe, but ONLY if the author_id matches the
// provided user_id. Also unlinks the image if it is stored
// locally in uploads/recipes/.
// Returns true on success, false if the recipe doesn't exist
// or does not belong to the user.
// ════════════════════════════════════════════════════════════
function deleteRecipe(PDO $pdo, int $recipeId, int $userId): bool
{
    // First, verify ownership and get the image URL so we can delete the file
    $stmt = $pdo->prepare("SELECT id, image_url FROM recipes WHERE id = :rid AND author_id = :uid LIMIT 1");
    $stmt->execute([':rid' => $recipeId, ':uid' => $userId]);
    $recipe = $stmt->fetch();

    if (!$recipe) {
        return false; // Recipe not found or user is not the author
    }

    // Delete the database record (ON DELETE CASCADE handles favourites/tags if set up)
    $delStmt = $pdo->prepare("DELETE FROM recipes WHERE id = :id");
    $success = $delStmt->execute([':id' => $recipe['id']]);

    // If successful, delete the local image file (if it exists)
    if ($success && !empty($recipe['image_url'])) {
        $imgPath = __DIR__ . '/../' . ltrim($recipe['image_url'], '/');
        // Only delete if it's actually in our uploads folder
        if (strpos($recipe['image_url'], 'uploads/recipes/') !== false && file_exists($imgPath)) {
            unlink($imgPath);
        }
    }

    return $success;
}
