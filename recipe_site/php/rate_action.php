<?php
/**
 * rate_action.php
 * Handles AJAX requests to rate a recipe.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/recipes.php';

startSession();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to rate recipes.']);
    exit;
}

$recipeId = (int) ($_POST['recipe_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);

if ($recipeId <= 0 || $rating < 0 || $rating > 5) {
    echo json_encode(['error' => 'Invalid recipe ID or rating. Rating must be between 1 and 5.']);
    exit;
}

$user = getCurrentUser();

try {
    $newAvg = rateRecipe($pdo, $user['id'], $recipeId, $rating);
    echo json_encode([
        'success' => true,
        'new_average' => $newAvg
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save rating: ' . $e->getMessage()]);
}
