<?php
/**
 * favorite_action.php
 * ─────────────────────────────────────────────────────────────
 * AJAX endpoint — toggle a recipe favourite for the logged-in user.
 *
 * POST params:
 *   recipe_id  (int)  — the recipe to favourite/unfavourite
 *
 * Response (JSON):
 *   { "favorited": true|false }  on success
 *   { "error": "..." }           on failure
 * ─────────────────────────────────────────────────────────────
 */
header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/recipes.php';   // brings in $pdo

startSession();

// ── Auth guard ────────────────────────────────────────────────
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to favourite a recipe.']);
    exit;
}

// ── Validate input ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

$recipeId = (int) ($_POST['recipe_id'] ?? 0);
if ($recipeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid recipe ID.']);
    exit;
}

// ── Toggle favourite ──────────────────────────────────────────
$user      = getCurrentUser();
$favorited = toggleFavorite($pdo, $user['id'], $recipeId);

echo json_encode(['favorited' => $favorited]);
