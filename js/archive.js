/**
 * VOYA — archive.js
 *
 * Load More: POST to admin-ajax.php → action=vy_load_posts
 * Data localized bởi PHP: VY_ARCHIVE.ajaxUrl, VY_ARCHIVE.nonce
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // ═══════════════════════════════════════════════════════
    // 1. FILTER SELECTS — CSP safe (data-filter-key)
    // ═══════════════════════════════════════════════════════
    document
      .querySelectorAll(".vy-filter-select[data-filter-key]")
      .forEach(function (sel) {
        sel.addEventListener("change", function () {
          var url = new URL(window.location.href);
          url.searchParams.delete("paged");
          if (sel.value) {
            url.searchParams.set(
              sel.getAttribute("data-filter-key"),
              sel.value,
            );
          } else {
            url.searchParams.delete(sel.getAttribute("data-filter-key"));
          }
          window.location.href = url.toString();
        });
      });

    // ═══════════════════════════════════════════════════════
    // 2. LOAD MORE — admin-ajax.php (chuẩn WP, reliable)
    // ═══════════════════════════════════════════════════════
    var btn = document.getElementById("vy-load-more-btn");
    var grid = document.getElementById("vy-archive-grid");
    var shownSpan = document.getElementById("vy-shown-count");
    var progBar = document.querySelector(".vy-load-more-progress__bar");

    if (!btn || !grid) return;

    // Kiểm tra VY_ARCHIVE được localize chưa
    if (typeof VY_ARCHIVE === "undefined" || !VY_ARCHIVE.ajaxUrl) {
      console.error(
        "[vy-archive] VY_ARCHIVE not localized. Check functions.php.",
      );
      return;
    }

    // Đọc config từ button data attributes
    var currentPage = parseInt(btn.dataset.page, 10) || 1;
    var maxPages = parseInt(btn.dataset.maxPages, 10) || 1;
    var perPage = parseInt(btn.dataset.perPage, 10) || 9;
    var total = parseInt(btn.dataset.total, 10) || 0;
    var cat = btn.dataset.cat || "0";
    var sort = btn.dataset.sort || "date_desc";
    var year = btn.dataset.year || "0";

    // Số bài đã hiện = hero(1) + grid cards
    var shownNow = 1 + grid.querySelectorAll(".vy-archive-card").length;

    // ── Helpers ──────────────────────────────────────────
    function updateUI(shown) {
      var pct =
        total > 0 ? Math.min(100, Math.round((shown / total) * 100)) : 100;
      if (progBar) progBar.style.width = pct + "%";
      if (shownSpan) shownSpan.textContent = shown;
    }

    function setLoading(on) {
      btn.classList.toggle("is-loading", on);
      btn.disabled = on;
    }

    function markDone() {
      btn.classList.add("is-done");
      btn.disabled = true;
      var textEl = btn.querySelector(".vy-load-more-btn__text");
      if (textEl) textEl.textContent = "Đã hiển thị tất cả bài viết";
    }

    // ── Click handler ─────────────────────────────────────
    btn.addEventListener("click", function () {
      if (btn.disabled) return;
      if (currentPage >= maxPages) {
        markDone();
        return;
      }

      var nextPage = currentPage + 1;
      setLoading(true);

      // Build FormData cho admin-ajax.php POST request
      var data = new FormData();
      data.append("action", "vy_load_posts"); // wp_ajax_vy_load_posts
      data.append("nonce", VY_ARCHIVE.nonce);
      data.append("paged", nextPage);
      data.append("per_page", perPage);
      data.append("cat", cat);
      data.append("sort", sort);
      data.append("year", year);

      fetch(VY_ARCHIVE.ajaxUrl, {
        method: "POST",
        body: data,
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then(function (res) {
          if (!res.ok) throw new Error("HTTP " + res.status);
          return res.json();
        })
        .then(function (json) {
          if (!json.success) {
            console.warn("[vy-archive] Server returned success:false", json);
            setLoading(false);
            return;
          }

          var html = json.data && json.data.html ? json.data.html.trim() : "";

          if (!html) {
            // Không còn bài
            markDone();
            setLoading(false);
            return;
          }

          // Parse và append cards
          var tmp = document.createElement("div");
          tmp.innerHTML = html;
          var cards = tmp.querySelectorAll(".vy-archive-card");

          if (cards.length === 0) {
            markDone();
            setLoading(false);
            return;
          }

          // Append với stagger animation
          cards.forEach(function (card, i) {
            card.classList.add("vy-card-hidden");
            card.style.transitionDelay = (i % 3) * 0.07 + "s";
            grid.appendChild(card);
          });

          currentPage = nextPage;
          shownNow += cards.length;
          updateUI(shownNow);
          initCardAnimations(grid.querySelectorAll(".vy-card-hidden"));

          if (currentPage >= maxPages) {
            markDone();
          }

          setLoading(false);
        })
        .catch(function (err) {
          console.error("[vy-archive] Load more error:", err);
          setLoading(false);
        });
    });

    // Init progress bar
    updateUI(shownNow);

    // ═══════════════════════════════════════════════════════
    // 3. CARD ENTRANCE ANIMATION
    // ═══════════════════════════════════════════════════════
    function initCardAnimations(cards) {
      if (!("IntersectionObserver" in window)) {
        cards.forEach(function (c) {
          c.classList.remove("vy-card-hidden");
          c.classList.add("vy-card-visible");
          c.style.transitionDelay = "";
        });
        return;
      }

      var obs = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.remove("vy-card-hidden");
              entry.target.classList.add("vy-card-visible");
              entry.target.style.transitionDelay = "";
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.06, rootMargin: "0px 0px -20px 0px" },
      );

      cards.forEach(function (card) {
        obs.observe(card);
      });
    }

    // Animate page 1 cards
    var page1Cards = grid.querySelectorAll(".vy-archive-card");
    page1Cards.forEach(function (c, i) {
      c.classList.add("vy-card-hidden");
      c.style.transitionDelay = (i % 3) * 0.07 + "s";
    });
    initCardAnimations(page1Cards);

    // ═══════════════════════════════════════════════════════
    // 4. FILTER BAR STICKY SHADOW
    // ═══════════════════════════════════════════════════════
    var filterBar = document.getElementById("vy-filter-bar");

    if (filterBar && "IntersectionObserver" in window) {
      var sentinel = document.createElement("div");
      sentinel.style.cssText =
        "height:1px;margin-bottom:-1px;pointer-events:none;";
      filterBar.parentElement.insertBefore(sentinel, filterBar);

      new IntersectionObserver(
        function (entries) {
          filterBar.classList.toggle("is-stuck", !entries[0].isIntersecting);
        },
        {
          threshold: 0,
          rootMargin:
            "-" +
            (parseInt(
              getComputedStyle(document.documentElement).getPropertyValue(
                "--header-height-desktop",
              ) || "80",
              10,
            ) +
              1) +
            "px 0px 0px 0px",
        },
      ).observe(sentinel);
    }

    // ═══════════════════════════════════════════════════════
    // 5. CATEGORY PILLS FADE MASK (mobile)
    // ═══════════════════════════════════════════════════════
    var pills = document.querySelector(".vy-filter-pills");

    if (pills) {
      function updateMask() {
        if (window.innerWidth > 1199) {
          pills.style.webkitMaskImage = "";
          pills.style.maskImage = "";
          return;
        }
        var overflows = pills.scrollWidth > pills.clientWidth;
        var atEnd =
          pills.scrollLeft + pills.clientWidth >= pills.scrollWidth - 4;
        var mask =
          overflows && !atEnd
            ? "linear-gradient(to right, #000 82%, transparent 100%)"
            : "";
        pills.style.webkitMaskImage = mask;
        pills.style.maskImage = mask;
      }

      pills.addEventListener("scroll", updateMask, { passive: true });
      window.addEventListener("resize", updateMask);
      updateMask();
    }
  });
})();
