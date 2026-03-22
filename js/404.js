/**
 * VOYA — 404.js
 * Full-screen 404 page interactions:
 *  1. Header: force transparent + disable sticky scroll
 *  2. Staggered content entrance animation
 *  3. Auto-focus search (delayed)
 *  4. Block form empty submit
 *
 * Fix so với Wanderland:
 *  - Bỏ .mkdf-page-header selector → dùng #vy-header (Voya)
 *  - Bỏ .mkdf-top-bar reference
 *  - header.js đã detect data-hero → transparent tự động
 *    nhưng 404 cần force vì body.error404 có overflow:hidden (no scroll)
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // ══════════════════════════════════════════════════════
    // 1. HEADER — force transparent, disable sticky
    //    header.js check DOM [data-hero] nhưng vì body
    //    overflow:hidden nên scroll event không fire →
    //    header sẽ không bao giờ đổi state → an toàn.
    //    Chỉ cần đảm bảo is-transparent được add.
    // ══════════════════════════════════════════════════════
    var header = document.getElementById("vy-header");

    if (header) {
      // Force transparent ngay lập tức (không chờ scroll)
      header.classList.add("is-transparent");
      header.classList.remove("is-sticky");

      // Lock: prevent header.js scroll handler thay đổi state
      // Vì body overflow:hidden nên scroll = 0 mãi mãi
      // → header.js sẽ tự giữ is-transparent. Không cần override.
    }

    // ══════════════════════════════════════════════════════
    // 2. STAGGERED CONTENT ENTRANCE ANIMATION
    // ══════════════════════════════════════════════════════
    var sequence = [
      ".vy-404-number",
      ".vy-404-tagline",
      ".vy-404-title",
      ".vy-404-search",
      ".vy-404-btns",
    ];

    sequence.forEach(function (sel, i) {
      var el = document.querySelector(sel);
      if (!el) return;

      // Set initial state
      el.style.opacity = "0";
      el.style.transform = "translateY(24px)";

      var delay = i * 0.13 + 0.18;
      var duration = "0.72s";
      var easing = "cubic-bezier(0.22, 1, 0.36, 1)";

      el.style.transition =
        "opacity " +
        duration +
        " " +
        easing +
        " " +
        delay +
        "s," +
        "transform " +
        duration +
        " " +
        easing +
        " " +
        delay +
        "s";

      // Double rAF để đảm bảo browser đã apply initial state
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          el.style.opacity = "1";
          el.style.transform = "translateY(0)";
        });
      });
    });

    // ══════════════════════════════════════════════════════
    // 3. AUTO-FOCUS SEARCH
    //    Delay để không conflict với entrance animation
    // ══════════════════════════════════════════════════════
    var input = document.querySelector(".vy-404-search__input");

    if (input) {
      setTimeout(function () {
        // Chỉ focus nếu user chưa interact với trang
        if (document.activeElement === document.body) {
          input.focus();
        }
      }, 900);
    }

    // ══════════════════════════════════════════════════════
    // 4. BLOCK EMPTY SEARCH SUBMIT
    // ══════════════════════════════════════════════════════
    var searchForm = document.querySelector(".vy-404-search");

    if (searchForm) {
      searchForm.addEventListener("submit", function (e) {
        var val = searchForm.querySelector("input[name='s']").value.trim();
        if (!val) {
          e.preventDefault();
          searchForm.querySelector("input[name='s']").focus();
          // Shake animation
          searchForm.classList.add("vy-shake");
          setTimeout(function () {
            searchForm.classList.remove("vy-shake");
          }, 500);
        }
      });
    }
  }); // end DOMContentLoaded
})();
