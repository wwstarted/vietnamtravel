/**
 * VOYA — archive.js (v2)
 *
 * Chức năng:
 * 1. Sidebar toggle trên mobile (show/hide)
 * 2. Filter category links → active state (UI feedback ngay lập tức)
 * 3. Card entrance animation khi scroll vào viewport
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    /* ═══════════════════════════════════════════════════════
           1. SIDEBAR MOBILE TOGGLE
           Tạo nút toggle trên mobile để show/hide sidebar
        ═══════════════════════════════════════════════════════ */
    var sidebar = document.querySelector(".vy-archive-sidebar");
    var layout = document.querySelector(".vy-archive-layout");

    if (sidebar && window.innerWidth <= 767) {
      /* Tạo nút toggle */
      var toggleBtn = document.createElement("button");
      toggleBtn.className = "vy-archive-sidebar-toggle";
      toggleBtn.innerHTML =
        '<i class="fa-solid fa-filter" aria-hidden="true"></i> ' +
        "<span>Lọc bài viết</span>" +
        '<i class="fa-solid fa-chevron-down vy-sidebar-arrow" aria-hidden="true"></i>';
      toggleBtn.setAttribute("aria-expanded", "false");
      toggleBtn.setAttribute("aria-controls", "vy-sidebar-content");
      toggleBtn.type = "button";

      /* Wrap sidebar content */
      var sidebarSticky = sidebar.querySelector(".vy-archive-sidebar__sticky");
      if (sidebarSticky) {
        sidebarSticky.id = "vy-sidebar-content";
        sidebarSticky.style.display = "none";

        sidebar.insertBefore(toggleBtn, sidebarSticky);

        toggleBtn.addEventListener("click", function () {
          var expanded = toggleBtn.getAttribute("aria-expanded") === "true";
          toggleBtn.setAttribute("aria-expanded", !expanded);
          sidebarSticky.style.display = expanded ? "none" : "flex";
          toggleBtn.querySelector(".vy-sidebar-arrow").style.transform =
            expanded ? "rotate(0deg)" : "rotate(180deg)";
        });
      }
    }

    /* ═══════════════════════════════════════════════════════
           2. CATEGORY FILTER — active state feedback
           Khi click link filter, thêm is-loading class để feedback
        ═══════════════════════════════════════════════════════ */
    var filterLinks = document.querySelectorAll(
      ".vy-filter-cat-link:not(.vy-filter-cat-link--reset)",
    );

    filterLinks.forEach(function (link) {
      link.addEventListener("click", function () {
        /* Tất cả links → remove active */
        filterLinks.forEach(function (l) {
          l.classList.remove("is-active");
          l.querySelector(".vy-filter-cat-radio i").className =
            "fa-regular fa-circle";
        });
        /* Link được click → active */
        link.classList.add("is-active");
        var icon = link.querySelector(".vy-filter-cat-radio i");
        if (icon) icon.className = "fa-solid fa-circle-dot";
      });
    });

    /* ═══════════════════════════════════════════════════════
           3. CARD ENTRANCE ANIMATION (IntersectionObserver)
        ═══════════════════════════════════════════════════════ */
    var listItems = document.querySelectorAll(
      ".vy-archive-list-item, .vy-archive-topblogs__item",
    );

    if (!("IntersectionObserver" in window)) {
      /* Fallback: show all immediately */
      listItems.forEach(function (el) {
        el.style.opacity = "1";
      });
      return;
    }

    /* Init: set hidden */
    listItems.forEach(function (el, i) {
      el.style.opacity = "0";
      el.style.transform = "translateY(18px)";
      el.style.transition =
        "opacity 0.48s ease " +
        (i % 4) * 0.06 +
        "s, transform 0.48s ease " +
        (i % 4) * 0.06 +
        "s";
    });

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.06, rootMargin: "0px 0px -16px 0px" },
    );

    listItems.forEach(function (el) {
      observer.observe(el);
    });
  }); /* end DOMContentLoaded */
})();
