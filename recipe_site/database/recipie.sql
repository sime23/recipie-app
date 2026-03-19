-- ============================================================
-- RECIPE WEBSITE DATABASE SCHEMA
-- Color Theme: Black (#121212), Orange (#FF6600), White (#FFFFFF)
-- Engine: MySQL 8.0+ | Charset: utf8mb4 for full Unicode support
-- ============================================================

-- Create and select the database
CREATE DATABASE IF NOT EXISTS recipe_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE recipe_db;

-- ============================================================
-- TABLE: categories
-- Stores recipe categories (e.g. Breakfast, Dinner, Desserts)
-- Created before recipes because recipes reference it via FK
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
  id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100)    NOT NULL,               -- e.g. "Breakfast"
  slug        VARCHAR(100)    NOT NULL,               -- URL-friendly: "breakfast"
  icon        VARCHAR(10)     DEFAULT '🍽️',           -- Emoji icon for UI
  created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: users
-- Stores registered user accounts
-- Passwords stored as bcrypt hashes — NEVER plain text
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  username      VARCHAR(60)     NOT NULL,
  email         VARCHAR(255)    NOT NULL,
  password_hash VARCHAR(255)    NOT NULL,              -- bcrypt hash
  avatar_url    VARCHAR(500)    DEFAULT NULL,
  role          ENUM('user','editor','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email    (email),
  UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: recipes
-- Core recipe data. category_id and author_id are foreign keys.
-- ingredients / instructions stored as JSON for flexible structure
-- ============================================================
CREATE TABLE IF NOT EXISTS recipes (
  id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  category_id     INT UNSIGNED    NOT NULL,
  author_id       INT UNSIGNED    DEFAULT NULL,        -- NULL = house recipe
  title           VARCHAR(255)    NOT NULL,
  slug            VARCHAR(255)    NOT NULL,            -- SEO-friendly URL segment
  description     TEXT            NOT NULL,            -- Short teaser paragraph
  ingredients     JSON            NOT NULL,            -- Array of ingredient strings
  instructions    JSON            NOT NULL,            -- Array of step strings
  prep_time       SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- Minutes
  cook_time       SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- Minutes
  servings        TINYINT UNSIGNED  NOT NULL DEFAULT 4,
  difficulty      ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  image_url       VARCHAR(500)    NOT NULL,
  featured        TINYINT(1)      NOT NULL DEFAULT 0,  -- 1 = show on homepage hero
  created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_recipes_slug (slug),

  -- Foreign key: recipe must belong to an existing category
  CONSTRAINT fk_recipes_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
    ON DELETE RESTRICT ON UPDATE CASCADE,

  -- Foreign key: author may be deleted (SET NULL keeps recipe)
  CONSTRAINT fk_recipes_author
    FOREIGN KEY (author_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,

  -- Full-text index enables fast MATCH() AGAINST() searches
  FULLTEXT KEY ft_recipes_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: recipe_tags  (many-to-many junction)
-- Lightweight tag system (e.g. "gluten-free", "quick", "vegan")
-- ============================================================
CREATE TABLE IF NOT EXISTS tags (
  id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name  VARCHAR(60)   NOT NULL,
  slug  VARCHAR(60)   NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recipe_tags (
  recipe_id INT UNSIGNED NOT NULL,
  tag_id    INT UNSIGNED NOT NULL,
  PRIMARY KEY (recipe_id, tag_id),
  CONSTRAINT fk_rt_recipe FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
  CONSTRAINT fk_rt_tag    FOREIGN KEY (tag_id)    REFERENCES tags(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA — Categories
-- ============================================================
INSERT INTO categories (name, slug, icon) VALUES
  ('Breakfast',  'breakfast',  '🍳'),
  ('Lunch',      'lunch',      '🥗'),
  ('Dinner',     'dinner',     '🍝'),
  ('Desserts',   'desserts',   '🍰'),
  ('Drinks',     'drinks',     '🍹'),
  ('Snacks',     'snacks',     '🥨');

-- ============================================================
-- SEED DATA — Demo Admin User  (password: "admin1234")
-- Hash generated with: password_hash('admin1234', PASSWORD_BCRYPT)
-- ============================================================
INSERT INTO users (username, email, password_hash, role) VALUES
  ('chef_admin', 'admin@recipehub.com',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt placeholder
   'admin');

-- ============================================================
-- SEED DATA — Sample Recipes
-- ============================================================
INSERT INTO recipes
  (category_id, author_id, title, slug, description,
   ingredients, instructions,
   prep_time, cook_time, servings, difficulty, image_url, featured)
VALUES
-- Recipe 1: Shakshuka
(1, 1,
 'Classic Shakshuka',
 'classic-shakshuka',
 'A bold North African dish of eggs poached in a spiced tomato and pepper sauce. Perfect for weekend brunch or a quick weeknight dinner.',
 JSON_ARRAY(
   '2 tbsp olive oil',
   '1 large onion, diced',
   '1 red bell pepper, sliced',
   '4 garlic cloves, minced',
   '1 tsp ground cumin',
   '1 tsp smoked paprika',
   '½ tsp chili flakes',
   '1 can (400g) crushed tomatoes',
   '6 large eggs',
   'Salt & black pepper to taste',
   'Fresh parsley & feta to serve'
 ),
 JSON_ARRAY(
   'Heat olive oil in a large skillet over medium heat.',
   'Sauté onion and bell pepper until softened, about 6 minutes.',
   'Add garlic, cumin, paprika, and chili flakes. Cook 1 minute until fragrant.',
   'Pour in crushed tomatoes. Season with salt and pepper. Simmer 10 minutes.',
   'Make 6 wells in the sauce and crack an egg into each.',
   'Cover and cook 5–7 minutes until whites are set but yolks remain runny.',
   'Garnish with parsley and crumbled feta. Serve with crusty bread.'
 ),
 10, 20, 4, 'easy',
 'https://images.unsplash.com/photo-1590412200988-a436970781fa?w=1200&q=80',
 1),

-- Recipe 2: Spaghetti Carbonara
(3, 1,
 'Authentic Spaghetti Carbonara',
 'authentic-spaghetti-carbonara',
 'The real Roman carbonara — no cream, ever. Just eggs, Pecorino, guanciale, and the magic of pasta water creating a silky, indulgent sauce.',
 JSON_ARRAY(
   '400g spaghetti',
   '200g guanciale (or pancetta), cubed',
   '4 large egg yolks + 1 whole egg',
   '100g Pecorino Romano, finely grated',
   '50g Parmigiano Reggiano, finely grated',
   '2 tsp coarsely cracked black pepper',
   'Salt for pasta water'
 ),
 JSON_ARRAY(
   'Bring a large pot of salted water to a boil. Cook spaghetti until al dente.',
   'Meanwhile, cook guanciale in a cold skillet over medium heat until crispy. Remove from heat.',
   'Whisk egg yolks, whole egg, and cheeses together in a bowl. Season with pepper.',
   'Reserve 200ml of pasta water before draining spaghetti.',
   'Add hot pasta to the guanciale pan off the heat. Toss vigorously.',
   'Add egg-cheese mixture, tossing constantly and adding pasta water tablespoon by tablespoon until a creamy sauce coats every strand.',
   'Serve immediately with extra Pecorino and black pepper.'
 ),
 10, 15, 4, 'medium',
 'https://images.unsplash.com/photo-1612874742237-6526221588e3?w=1200&q=80',
 1),

-- Recipe 3: Chocolate Lava Cake
(4, 1,
 'Dark Chocolate Lava Cakes',
 'dark-chocolate-lava-cakes',
 'Warm, fudgy chocolate cakes with a dramatically molten center. Just 6 ingredients and 12 minutes in the oven — your dinner guests will be speechless.',
 JSON_ARRAY(
   '170g dark chocolate (70%+), chopped',
   '115g unsalted butter, cubed',
   '2 large eggs',
   '2 large egg yolks',
   '60g powdered sugar, sifted',
   '2 tbsp all-purpose flour',
   'Pinch of sea salt',
   'Butter + cocoa powder for ramekins',
   'Vanilla ice cream, to serve'
 ),
 JSON_ARRAY(
   'Preheat oven to 220°C (425°F). Butter 4 ramekins and dust with cocoa powder.',
   'Melt chocolate and butter together in a double boiler. Stir until smooth. Cool slightly.',
   'Whisk eggs, yolks, and powdered sugar until pale and thick, about 2 minutes.',
   'Fold chocolate mixture into the egg mixture until combined.',
   'Sift in flour and salt. Fold gently until just incorporated.',
   'Divide batter among ramekins. (Can refrigerate up to 24 hrs at this point.)',
   'Bake 10–12 minutes until edges are set but center jiggles.',
   'Run a knife around the edge, invert onto a plate, and serve immediately with ice cream.'
 ),
 15, 12, 4, 'medium',
 'https://images.unsplash.com/photo-1624353365286-3f8d62daad51?w=1200&q=80',
 0),

-- Recipe 4: Mango Lassi
(5, 1,
 'Mango Lassi',
 'mango-lassi',
 'Thick, creamy, and vibrantly golden — this classic Indian yogurt drink is the ultimate warm-weather refresher. Three ingredients, two minutes.',
 JSON_ARRAY(
   '2 ripe Alphonso mangoes, peeled & cubed (or 400g frozen mango)',
   '300ml full-fat yogurt',
   '150ml whole milk (adjust for thickness)',
   '2 tbsp honey or sugar, to taste',
   '¼ tsp ground cardamom',
   'Ice cubes',
   'Pinch of saffron threads (optional garnish)'
 ),
 JSON_ARRAY(
   'Add mango, yogurt, milk, honey, and cardamom to a blender.',
   'Blend on high for 60 seconds until completely smooth.',
   'Taste and adjust sweetness. Add more milk if too thick.',
   'Pour over ice into tall glasses.',
   'Garnish with a pinch of saffron and serve immediately.'
 ),
 5, 0, 2, 'easy',
 'https://images.unsplash.com/photo-1527661591475-527312dd65f5?w=1200&q=80',
 0),

-- Recipe 5: Avocado Toast
(2, 1,
 'Perfect Avocado Toast',
 'perfect-avocado-toast',
 'Elevated avocado toast with whipped ricotta, chili flakes, and a jammy soft-boiled egg. Simple ingredients, extraordinary results.',
 JSON_ARRAY(
   '2 thick slices sourdough bread',
   '1 large ripe avocado',
   '4 tbsp ricotta cheese',
   '2 eggs',
   '1 tbsp lemon juice',
   '½ tsp chili flakes',
   'Flaky sea salt & black pepper',
   'Micro greens or arugula to top',
   'Extra virgin olive oil to drizzle'
 ),
 JSON_ARRAY(
   'Soft-boil eggs: lower into boiling water for exactly 6½ minutes, then ice bath for 2 minutes. Peel carefully.',
   'Toast sourdough slices until deep golden and crisp.',
   'Halve avocado, remove pit, and scoop flesh into a bowl. Add lemon juice, salt, and pepper. Mash coarsely.',
   'Spread whipped ricotta over toast first, then top with mashed avocado.',
   'Halve the soft-boiled eggs and place on top.',
   'Finish with chili flakes, flaky salt, micro greens, and a drizzle of olive oil.'
 ),
 10, 7, 2, 'easy',
 'https://images.unsplash.com/photo-1603046891744-1f40b0e28c3b?w=1200&q=80',
 0),

-- Recipe 6: Butter Chicken
(3, 1,
 'Butter Chicken (Murgh Makhani)',
 'butter-chicken-murgh-makhani',
 'The iconic Indian curry — tender chicken simmered in a velvety tomato-cream sauce perfumed with fenugreek and garam masala. Rich, aromatic, deeply satisfying.',
 JSON_ARRAY(
   '700g boneless chicken thighs, cubed',
   '200ml full-fat yogurt (marinade)',
   '1 tbsp garam masala (marinade)',
   '1 tsp turmeric (marinade)',
   '3 tbsp butter',
   '1 large onion, finely chopped',
   '4 garlic cloves + 1-inch ginger, grated',
   '2 cans (800g) crushed tomatoes',
   '1 tsp dried fenugreek leaves (kasuri methi)',
   '1 tsp sugar',
   '150ml heavy cream',
   'Salt to taste',
   'Naan or basmati rice to serve'
 ),
 JSON_ARRAY(
   'Marinate chicken in yogurt, garam masala, and turmeric for at least 1 hour (overnight preferred).',
   'Grill or broil marinated chicken until charred in spots, about 8 minutes. Set aside.',
   'In a heavy pot, melt butter over medium heat. Sauté onion until golden, 10 minutes.',
   'Add garlic and ginger. Cook 2 minutes.',
   'Add crushed tomatoes. Simmer 15 minutes until thickened.',
   'Blend the sauce until completely smooth. Return to pot.',
   'Add grilled chicken, fenugreek leaves, and sugar. Simmer 10 minutes.',
   'Stir in cream. Taste and season. Serve with naan and basmati rice.'
 ),
 20, 40, 6, 'medium',
 'https://images.unsplash.com/photo-1588166524941-3bf61a9c41db?w=1200&q=80',
 1);

-- Tags seed data
INSERT INTO tags (name, slug) VALUES
  ('Quick', 'quick'), ('Vegetarian', 'vegetarian'), ('Gluten-Free', 'gluten-free'),
  ('Spicy', 'spicy'), ('Comfort Food', 'comfort-food'), ('Date Night', 'date-night');

-- ============================================================
-- TABLE: user_favorites
-- Stores recipes that users have marked as favourite.
-- Composite PK prevents duplicate favourites.
-- Both FKs cascade on delete to keep data clean.
-- ============================================================
CREATE TABLE IF NOT EXISTS user_favorites (
  user_id    INT UNSIGNED NOT NULL,
  recipe_id  INT UNSIGNED NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (user_id, recipe_id),

  CONSTRAINT fk_fav_user
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  CONSTRAINT fk_fav_recipe
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
