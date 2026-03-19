/**
 * main.js — RecipeHub
 * ─────────────────────────────────────────────────────────────
 * Vanilla JS (no dependencies). Handles:
 *  1. Hero slider (auto-play + dot navigation)
 *  2. Category pill filter (client-side card filtering)
 *  3. Ingredient checklist persistence (localStorage)
 *  4. Instruction step toggle (mark as done)
 *  5. Mobile navigation menu
 *  6. Staggered card animation on load
 *  7. Scroll-triggered header shadow
 * ─────────────────────────────────────────────────────────────
 */

'use strict';

/* ════════════════════════════════════════════════════════════
   1. HERO SLIDER
   Auto-plays every 5 seconds. Dots and goToSlide() allow
   manual control. Uses CSS opacity transitions on .active class.
   ════════════════════════════════════════════════════════════ */
(function initHeroSlider() {
  const slides = document.querySelectorAll('.hero-slide');
  const dots   = document.querySelectorAll('.hero-dot');

  if (!slides.length) return;  // not on homepage — bail early

  let currentIndex = 0;
  let autoplayTimer = null;

  /**
   * Activate a specific slide by index.
   * Removes .active from all slides/dots, applies to the target.
   * @param {number} index
   */
  function activateSlide(index) {
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => { d.classList.remove('active'); d.setAttribute('aria-selected', 'false'); });

    slides[index].classList.add('active');
    dots[index].classList.add('active');
    dots[index].setAttribute('aria-selected', 'true');
    currentIndex = index;
  }

  /**
   * Advance to next slide. Wraps around to 0 after the last.
   */
  function nextSlide() {
    const next = (currentIndex + 1) % slides.length;
    activateSlide(next);
  }

  /** Start the auto-play interval (5 second cadence) */
  function startAutoplay() {
    autoplayTimer = setInterval(nextSlide, 5000);
  }

  /** Stop autoplay — called when user manually interacts */
  function stopAutoplay() {
    clearInterval(autoplayTimer);
  }

  // Expose goToSlide globally so onclick in PHP can call it
  window.goToSlide = function(index) {
    stopAutoplay();
    activateSlide(index);
    // Restart autoplay after manual interaction
    startAutoplay();
  };

  // Pause on hover (better UX — don't steal control from reader)
  const sliderEl = document.getElementById('heroSlider');
  if (sliderEl) {
    sliderEl.addEventListener('mouseenter', stopAutoplay);
    sliderEl.addEventListener('mouseleave', startAutoplay);
  }

  // Kick off
  activateSlide(0);
  startAutoplay();
})();


/* ════════════════════════════════════════════════════════════
   2. CATEGORY FILTER BAR
   Client-side filtering: hide/show recipe cards by category
   using the data-category attribute, no page reload needed.
   ════════════════════════════════════════════════════════════ */
(function initCategoryFilter() {
  const pills = document.querySelectorAll('.category-pill');
  const cards = document.querySelectorAll('.recipe-card[data-category]');

  if (!pills.length) return;

  pills.forEach(pill => {
    pill.addEventListener('click', function () {
      const chosen = this.dataset.category;  // 'all' | 'breakfast' | 'dinner' …

      // Update active pill styling
      pills.forEach(p => {
        p.classList.remove('active');
        p.setAttribute('aria-selected', 'false');
      });
      this.classList.add('active');
      this.setAttribute('aria-selected', 'true');

      // Show / hide cards based on category match
      cards.forEach((card, i) => {
        const match = chosen === 'all' || card.dataset.category === chosen;

        if (match) {
          card.style.display = '';  // Restore display (uses grid cell)
          // Re-trigger stagger animation so newly visible cards animate in
          card.style.animationDelay = `${i * 60}ms`;
          card.style.animation = 'none';
          // Force reflow then re-apply animation
          void card.offsetHeight;
          card.style.animation = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
})();


/* ════════════════════════════════════════════════════════════
   3. INGREDIENT CHECKLIST PERSISTENCE
   Saves checked state to localStorage keyed by recipe slug.
   State is restored on page load — persists across refreshes.
   ════════════════════════════════════════════════════════════ */
(function initIngredientChecklist() {
  const checkboxes = document.querySelectorAll('.ingredient-checkbox');
  if (!checkboxes.length) return;

  // Derive a unique key from the current page URL slug
  const slug    = window.location.search.replace('?slug=', '') || 'recipe';
  const storeKey = `checked_${slug}`;

  // ── Restore saved state from localStorage ────────────────
  function restoreChecked() {
    let saved = [];
    try {
      saved = JSON.parse(localStorage.getItem(storeKey)) || [];
    } catch(e) { /* ignore parse errors */ }

    checkboxes.forEach(cb => {
      if (saved.includes(cb.id)) {
        cb.checked = true;
      }
    });
  }

  // ── Save current checked state ────────────────────────────
  function saveChecked() {
    const checked = Array.from(checkboxes)
      .filter(cb => cb.checked)
      .map(cb => cb.id);
    try {
      localStorage.setItem(storeKey, JSON.stringify(checked));
    } catch(e) { /* storage full — fail silently */ }
  }

  // Bind change events
  checkboxes.forEach(cb => cb.addEventListener('change', saveChecked));

  // Restore on load
  restoreChecked();
})();


/* ════════════════════════════════════════════════════════════
   4. INSTRUCTION STEP TOGGLE
   Clicking a step marks it as done (strikethrough + dimmed).
   toggleStep() is also called from onclick in PHP template.
   ════════════════════════════════════════════════════════════ */

/**
 * Toggle the .done class on an instruction step.
 * Called via onclick attribute in recipe.php template.
 * @param {HTMLElement} el — the .instruction-step element
 */
window.toggleStep = function(el) {
  el.classList.toggle('done');
};


/* ════════════════════════════════════════════════════════════
   5. MOBILE NAVIGATION MENU
   Toggles .mobile-nav-open on <body> to show/hide the
   full-screen nav overlay (styled in style.css).
   ════════════════════════════════════════════════════════════ */
window.toggleMobileMenu = function() {
  document.body.classList.toggle('mobile-nav-open');

  // Update aria-expanded on the toggle button
  const btn = document.querySelector('.mobile-menu-btn');
  if (btn) {
    const isOpen = document.body.classList.contains('mobile-nav-open');
    btn.setAttribute('aria-expanded', isOpen);
  }
};

// Close mobile nav when clicking a nav link (UX: navigate without extra tap)
document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', () => {
    document.body.classList.remove('mobile-nav-open');
  });
});

// Close on Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && document.body.classList.contains('mobile-nav-open')) {
    document.body.classList.remove('mobile-nav-open');
  }
});


/* ════════════════════════════════════════════════════════════
   6. STAGGERED CARD ANIMATION
   On DOMContentLoaded, apply incremental animation-delay to
   recipe cards so they cascade in rather than all appearing
   at once. Simple approach — no IntersectionObserver needed
   for the number of cards on a typical page.
   ════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
  const cards = document.querySelectorAll('.recipe-card');
  cards.forEach((card, i) => {
    // Each card delayed by 60ms × its position in the grid
    card.style.animationDelay = `${i * 60}ms`;
  });
});


/* ════════════════════════════════════════════════════════════
   7. SCROLL-TRIGGERED HEADER SHADOW
   Adds a subtle shadow to the sticky header once the user
   scrolls down, providing visual separation from content.
   ════════════════════════════════════════════════════════════ */
(function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const THRESHOLD = 20;  // px scrolled before shadow appears

  function handleScroll() {
    if (window.scrollY > THRESHOLD) {
      header.style.boxShadow = '0 4px 30px rgba(0,0,0,0.6)';
    } else {
      header.style.boxShadow = '';
    }
  }

  // Passive listener doesn't block scroll rendering (performance)
  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();  // Run once in case page loaded mid-scroll
})();


/* ════════════════════════════════════════════════════════════
   8. SMOOTH SEARCH INPUT EXPERIENCE
   Debounce search so it doesn't fire on every keystroke
   (useful if we later upgrade to live AJAX search).
   ════════════════════════════════════════════════════════════ */
(function initSearch() {
  const input = document.querySelector('.search-input');
  if (!input) return;

  /**
   * Debounce utility — delays fn execution until
   * `delay` ms have passed since the last call.
   */
  function debounce(fn, delay) {
    let timer;
    return function(...args) {
      clearTimeout(timer);
      timer = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  // Placeholder: hook for future live-search feature
  // Currently the form submits on Enter/button click.
  input.addEventListener('input', debounce(function(e) {
    const val = e.target.value.trim();
    // Future: if (val.length >= 2) fetchLiveResults(val);
    void val;
  }, 300));
})();


/* ════════════════════════════════════════════════════════════
   9. FAVOURITE TOGGLE (AJAX)
   Sends a POST to php/favorite_action.php and flips the
   button state (🤍 ↔ ❤️) without a page reload.
   Called from onclick="toggleFav(this)" in PHP templates.
   ════════════════════════════════════════════════════════════ */

/**
 * Toggle favourite state for a recipe card.
 * @param {HTMLButtonElement} btn — the .fav-btn element clicked
 */
window.toggleFav = function(btn) {
  const recipeId = btn.dataset.recipeId;
  if (!recipeId) return;

  // Prevent double-clicks while request is in-flight
  btn.disabled = true;
  btn.style.opacity = '0.6';

  const formData = new FormData();
  formData.append('recipe_id', recipeId);

  // Resolve the correct path to the AJAX endpoint.
  // Works whether script is in root (/php/) or profile.php level.
  const base = document.querySelector('base')?.href || window.location.origin;
  // Detect if we're in a php/ subdirectory already
  const path = window.location.pathname.includes('/php/')
    ? 'favorite_action.php'
    : 'php/favorite_action.php';

  fetch(path, { method: 'POST', body: formData })
    .then(r => {
      if (r.status === 401) {
        // Not logged in — redirect to login page
        window.location.href = 'login.php';
        return null;
      }
      return r.json();
    })
    .then(data => {
      if (!data) return;
      if (data.error) {
        alert(data.error);
        btn.disabled = false;
        btn.style.opacity = '';
        return;
      }

      // Update button appearance based on new state
      if (data.favorited) {
        btn.classList.add('fav-btn--active');
        // If hero button (shows text), update the label
        if (btn.classList.contains('fav-btn--hero')) {
          btn.textContent = '❤️ Saved to Favourites';
        } else {
          btn.textContent = '❤️';
        }
        btn.setAttribute('aria-label', 'Remove from favourites');
        btn.setAttribute('title', 'Remove from favourites');
      } else {
        btn.classList.remove('fav-btn--active');
        if (btn.classList.contains('fav-btn--hero')) {
          btn.textContent = '🤍 Save to Favourites';
        } else {
          btn.textContent = '🤍';
        }
        btn.setAttribute('aria-label', 'Add to favourites');
        btn.setAttribute('title', 'Add to favourites');
      }

      btn.disabled = false;
      btn.style.opacity = '';
    })
    .catch(err => {
      console.error('Favourite toggle failed:', err);
      btn.disabled = false;
      btn.style.opacity = '';
    });
};
