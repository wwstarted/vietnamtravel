/**
 * VOYA — header.js  (v2)
 * Load với defer → DOM guaranteed ready
 *
 * Changes from v1:
 * - Removed transparent/hero state logic
 * - Sticky detection: IntersectionObserver on topbar (efficient, no scroll event)
 * - Search: full-screen modal overlay instead of expand bar
 * - Mobile menu: unchanged
 */
(function () {
  "use strict";

  /* ── DOM refs ─────────────────────────────────────────────── */
  var topbar = document.getElementById("vy-topbar");
  var header = document.getElementById("vy-header");
  var searchToggle = document.getElementById("vy-search-toggle");
  var searchModal = document.getElementById("vy-search-modal");
  var searchClose = document.getElementById("vy-search-close");
  var searchBackdrop = document.getElementById("vy-search-backdrop");
  var searchInput = document.getElementById("vy-search-input");
  var hamburger = document.getElementById("vy-hamburger");
  var mobileMenu = document.getElementById("vy-mobile-menu");
  var mobileClose = document.getElementById("vy-mobile-close");
  var mobileBackdrop = document.getElementById("vy-mobile-backdrop");

  if (!header) return;

  /* ── Remove no-transition class after first paint ──────────
     Prevents any flicker on initial load.                      */
  requestAnimationFrame(function () {
    header.classList.remove("vy-header-no-transition");
  });

  /* ── Sticky state via IntersectionObserver ─────────────────
     Watches the topbar element (in normal flow).
     When topbar exits viewport → header becomes sticky → add shadow.
     When topbar re-enters → remove shadow.
     Falls back to scroll listener if IntersectionObserver unavailable.
     ────────────────────────────────────────────────────────── */
  if (topbar) {
    if ("IntersectionObserver" in window) {
      var stickyObserver = new IntersectionObserver(
        function (entries) {
          // isIntersecting: true → topbar visible → not sticky
          // isIntersecting: false → topbar scrolled away → sticky
          var visible = entries[0].isIntersecting;
          header.classList.toggle("is-sticky", !visible);
        },
        {
          root: null,
          rootMargin: "0px",
          threshold: 0,
        },
      );
      stickyObserver.observe(topbar);
    } else {
      /* Fallback: scroll listener for older browsers */
      var TOPBAR_H = topbar.offsetHeight || 42;
      var rafActive = false;

      function checkSticky() {
        rafActive = false;
        header.classList.toggle("is-sticky", window.scrollY > TOPBAR_H);
      }

      window.addEventListener(
        "scroll",
        function () {
          if (!rafActive) {
            rafActive = true;
            requestAnimationFrame(checkSticky);
          }
        },
        { passive: true },
      );

      /* Sync on load (in case page was already scrolled) */
      checkSticky();
    }
  } else {
    /* No topbar in DOM (e.g. mobile where topbar is display:none):
       Use scroll position threshold instead                     */
    var rafActive2 = false;

    function checkStickyFallback() {
      rafActive2 = false;
      header.classList.toggle("is-sticky", window.scrollY > 10);
    }

    window.addEventListener(
      "scroll",
      function () {
        if (!rafActive2) {
          rafActive2 = true;
          requestAnimationFrame(checkStickyFallback);
        }
      },
      { passive: true },
    );

    checkStickyFallback();
  }

  /* ── Search Modal ─────────────────────────────────────────── */
  var searchOpen = false;

  function openSearch() {
    if (searchOpen) return;
    searchOpen = true;
    searchModal.classList.add("is-open");
    searchModal.setAttribute("aria-hidden", "false");
    if (searchToggle) searchToggle.setAttribute("aria-expanded", "true");

    /* Focus input after transition starts */
    setTimeout(function () {
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }, 120);
  }

  function closeSearch() {
    if (!searchOpen) return;
    searchOpen = false;
    searchModal.classList.remove("is-open");
    searchModal.setAttribute("aria-hidden", "true");
    if (searchToggle) {
      searchToggle.setAttribute("aria-expanded", "false");
      searchToggle.focus(); /* return focus to trigger */
    }
  }

  /* Toggle on search button */
  if (searchToggle) {
    searchToggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      searchOpen ? closeSearch() : openSearch();
    });
  }

  /* Close on X button */
  if (searchClose) {
    searchClose.addEventListener("click", function () {
      closeSearch();
    });
  }

  /* Close on backdrop click */
  if (searchBackdrop) {
    searchBackdrop.addEventListener("click", function () {
      closeSearch();
    });
  }

  /* ── Mobile Menu ──────────────────────────────────────────── */
  var menuOpen = false;
  var savedScroll = 0;

  function openMenu() {
    if (menuOpen) return;
    menuOpen = true;
    savedScroll = window.scrollY;

    mobileMenu.classList.add("is-open");
    mobileMenu.setAttribute("aria-hidden", "false");

    if (hamburger) {
      hamburger.classList.add("is-active");
      hamburger.setAttribute("aria-expanded", "true");
    }

    /* Lock body scroll */
    document.body.style.position = "fixed";
    document.body.style.top = "-" + savedScroll + "px";
    document.body.style.width = "100%";
    document.body.style.overflowY = "scroll";
  }

  function closeMenu() {
    if (!menuOpen) return;
    menuOpen = false;

    mobileMenu.classList.remove("is-open");
    mobileMenu.setAttribute("aria-hidden", "true");

    if (hamburger) {
      hamburger.classList.remove("is-active");
      hamburger.setAttribute("aria-expanded", "false");
    }

    /* Restore body scroll */
    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.width = "";
    document.body.style.overflowY = "";
    window.scrollTo(0, savedScroll);
  }

  if (hamburger) hamburger.addEventListener("click", openMenu);
  if (mobileClose) mobileClose.addEventListener("click", closeMenu);
  if (mobileBackdrop) mobileBackdrop.addEventListener("click", closeMenu);

  /* Accordion sub-menu */
  var toggleBtns = document.querySelectorAll(".vy-mobile-toggle");

  for (var i = 0; i < toggleBtns.length; i++) {
    toggleBtns[i].addEventListener(
      "click",
      (function (btn) {
        return function () {
          var li = btn.closest(".vy-mobile-item");
          if (!li) return;

          var wasOpen = li.classList.contains("is-open");
          var siblings = li.parentElement
            ? li.parentElement.querySelectorAll(
                ":scope > .vy-mobile-item.is-open",
              )
            : [];

          /* Close other open siblings */
          for (var s = 0; s < siblings.length; s++) {
            if (siblings[s] !== li) {
              siblings[s].classList.remove("is-open");
              var sibBtn = siblings[s].querySelector(".vy-mobile-toggle");
              if (sibBtn) sibBtn.setAttribute("aria-expanded", "false");
            }
          }

          li.classList.toggle("is-open", !wasOpen);
          btn.setAttribute("aria-expanded", String(!wasOpen));
        };
      })(toggleBtns[i]),
    );
  }

  /* ── Keyboard handlers ────────────────────────────────────── */
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (searchOpen) {
        closeSearch();
        return;
      }
      if (menuOpen) {
        closeMenu();
      }
    }
  });

  /* ── Resize: close mobile menu when returning to desktop ──── */
  var resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      if (window.innerWidth > 1199) {
        if (menuOpen) closeMenu();

        /* Reset all open accordion items */
        document
          .querySelectorAll(".vy-mobile-item.is-open")
          .forEach(function (li) {
            li.classList.remove("is-open");
          });
      }
    }, 150);
  });
})();
