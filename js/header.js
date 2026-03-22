/**
 * VOYA — header.js
 * Load với defer → DOM guaranteed ready
 * Fix: detect hero bằng DOM element thay vì body class
 */
(function () {
  "use strict";

  var header = document.getElementById("vy-header");
  var searchToggle = document.getElementById("vy-search-toggle");
  var searchBar = document.getElementById("vy-search-bar");
  var searchClose = document.getElementById("vy-search-close");
  var searchInput = document.getElementById("vy-search-input");
  var hamburger = document.getElementById("vy-hamburger");
  var mobileMenu = document.getElementById("vy-mobile-menu");
  var mobileClose = document.getElementById("vy-mobile-close");
  var mobileBackdrop = document.getElementById("vy-mobile-backdrop");

  if (!header) return;

  // ── Hero detection: kiểm tra DOM element thực tế ─────────────
  // Chỉ transparent khi trang CÓ hero section thực sự
  var heroEl = document.querySelector(
    ".vy-hero, .vy-home-hero, .vy-hero-banner, [data-hero]",
  );
  var IS_HERO = heroEl !== null;

  var STICKY_OFFSET = 80;
  var searchOpen = false;
  var menuOpen = false;
  var savedScroll = 0;

  // ── Scroll ────────────────────────────────────────────────────
  // is-transparent đã được PHP add server-side → không cần add lại ở đây
  // JS chỉ cần quản lý scroll state (sticky/transparent khi scroll)

  var lastY = window.scrollY;
  var rafPending = false;

  function handleScroll() {
    rafPending = false;
    if (lastY > STICKY_OFFSET) {
      // Đã scroll xuống → sticky, bỏ transparent
      header.classList.add("is-sticky");
      header.classList.remove("is-transparent");
    } else {
      // Về đầu trang → bỏ sticky, restore transparent nếu là hero page
      header.classList.remove("is-sticky");
      if (IS_HERO) header.classList.add("is-transparent");
    }
  }

  window.addEventListener(
    "scroll",
    function () {
      lastY = window.scrollY;
      if (!rafPending) {
        rafPending = true;
        requestAnimationFrame(handleScroll);
      }
    },
    { passive: true },
  );

  // Chạy ngay 1 lần để đồng bộ nếu user load trang ở giữa (đã scroll sẵn)
  if (lastY > STICKY_OFFSET) handleScroll();

  // Xoá no-transition sau 1 rAF — transition chỉ active sau khi paint xong
  // Tránh browser animate từ white → transparent ngay lúc load
  requestAnimationFrame(function () {
    header.classList.remove("vy-header-no-transition");
  });

  // ── Search ────────────────────────────────────────────────────
  function openSearch() {
    if (!searchBar || searchOpen) return;
    searchOpen = true;
    searchBar.classList.add("is-open");
    searchBar.setAttribute("aria-hidden", "false");
    header.classList.add("search-is-open");
    if (searchToggle) searchToggle.setAttribute("aria-expanded", "true");
    setTimeout(function () {
      if (searchInput) searchInput.focus();
    }, 100);
  }

  function closeSearch() {
    if (!searchBar || !searchOpen) return;
    searchOpen = false;
    searchBar.classList.remove("is-open");
    searchBar.setAttribute("aria-hidden", "true");
    header.classList.remove("search-is-open");
    if (searchToggle) searchToggle.setAttribute("aria-expanded", "false");
  }

  if (searchToggle) {
    searchToggle.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      searchOpen ? closeSearch() : openSearch();
    });
  }

  if (searchClose) {
    searchClose.addEventListener("click", function (e) {
      e.stopPropagation();
      closeSearch();
    });
  }

  document.addEventListener("click", function (e) {
    if (!searchOpen) return;
    if (
      !(searchBar && searchBar.contains(e.target)) &&
      !(searchToggle && searchToggle.contains(e.target))
    ) {
      closeSearch();
    }
  });

  // ── Mobile Menu ───────────────────────────────────────────────
  function openMenu() {
    if (!mobileMenu || menuOpen) return;
    menuOpen = true;
    savedScroll = window.scrollY;
    mobileMenu.classList.add("is-open");
    mobileMenu.setAttribute("aria-hidden", "false");
    if (hamburger) {
      hamburger.classList.add("is-active");
      hamburger.setAttribute("aria-expanded", "true");
    }
    document.body.style.position = "fixed";
    document.body.style.top = "-" + savedScroll + "px";
    document.body.style.width = "100%";
    document.body.style.overflowY = "scroll";
  }

  function closeMenu() {
    if (!mobileMenu || !menuOpen) return;
    menuOpen = false;
    mobileMenu.classList.remove("is-open");
    mobileMenu.setAttribute("aria-hidden", "true");
    if (hamburger) {
      hamburger.classList.remove("is-active");
      hamburger.setAttribute("aria-expanded", "false");
    }
    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.width = "";
    document.body.style.overflowY = "";
    window.scrollTo(0, savedScroll);
  }

  if (hamburger) hamburger.addEventListener("click", openMenu);
  if (mobileClose) mobileClose.addEventListener("click", closeMenu);
  if (mobileBackdrop) mobileBackdrop.addEventListener("click", closeMenu);

  // Mobile accordion
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
          for (var s = 0; s < siblings.length; s++) {
            if (siblings[s] !== li) siblings[s].classList.remove("is-open");
          }
          li.classList.toggle("is-open", !wasOpen);
          btn.setAttribute("aria-expanded", String(!wasOpen));
        };
      })(toggleBtns[i]),
    );
  }

  // ── Keyboard ──────────────────────────────────────────────────
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

  // ── Resize ────────────────────────────────────────────────────
  var resizeTimer;
  window.addEventListener("resize", function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      if (window.innerWidth > 1199) {
        if (menuOpen) closeMenu();
        document
          .querySelectorAll(".vy-mobile-item.is-open")
          .forEach(function (li) {
            li.classList.remove("is-open");
          });
      }
    }, 150);
  });
})();
