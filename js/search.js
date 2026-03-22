/**
 * VOYA — search.js (v2)
 *
 * 1. Card entrance animation (reuse archive pattern)
 * 2. Keyword highlight trong kết quả
 * 3. Không submit form rỗng
 * 4. Sidebar sticky align-items (reuse archive.js pattern)
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    /* ══════════════════════════════════════════════════
           1. CARD ENTRANCE ANIMATION
        ══════════════════════════════════════════════════ */
    var listItems = document.querySelectorAll(
      "#vy-search-grid .vy-archive-list-item",
    );

    if (listItems.length && "IntersectionObserver" in window) {
      listItems.forEach(function (el, i) {
        el.style.opacity = "0";
        el.style.transform = "translateY(16px)";
        el.style.transition =
          "opacity 0.45s ease " +
          (i % 4) * 0.06 +
          "s, transform 0.45s ease " +
          (i % 4) * 0.06 +
          "s";
      });

      var obs = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.style.opacity = "1";
              entry.target.style.transform = "translateY(0)";
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.06, rootMargin: "0px 0px -16px 0px" },
      );

      listItems.forEach(function (el) {
        obs.observe(el);
      });
    }

    /* ══════════════════════════════════════════════════
           2. KEYWORD HIGHLIGHT
        ══════════════════════════════════════════════════ */
    var grid = document.getElementById("vy-search-grid");
    var keyword = (
      new URLSearchParams(window.location.search).get("s") || ""
    ).trim();

    if (grid && keyword.length >= 2) {
      var escaped = keyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      var regex = new RegExp("(" + escaped + ")", "gi");

      grid
        .querySelectorAll(
          ".vy-archive-list-item__title a, .vy-archive-list-item__desc",
        )
        .forEach(function (el) {
          walkTextNodes(el, regex);
        });
    }

    function walkTextNodes(el, regex) {
      var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
      var nodes = [];
      var node;
      while ((node = walker.nextNode())) nodes.push(node);

      nodes.forEach(function (textNode) {
        var text = textNode.nodeValue;
        if (!regex.test(text)) return;
        regex.lastIndex = 0;
        var wrapper = document.createElement("span");
        wrapper.innerHTML = text.replace(
          regex,
          "<mark class='vy-search-highlight'>$1</mark>",
        );
        textNode.parentNode.replaceChild(wrapper, textNode);
      });
    }

    /* ══════════════════════════════════════════════════
           3. PREVENT EMPTY FORM SUBMIT
        ══════════════════════════════════════════════════ */
    document
      .querySelectorAll(".vy-search-sidebar-form, .vy-search-inline-form")
      .forEach(function (form) {
        form.addEventListener("submit", function (e) {
          var input = form.querySelector("input[name='s']");
          if (input && !input.value.trim()) {
            e.preventDefault();
            input.focus();
          }
        });
      });

    /* ══════════════════════════════════════════════════
           4. SIDEBAR STICKY TOP — sync với header height
        ══════════════════════════════════════════════════ */
    var sidebarSticky = document.querySelector(
      ".vy-archive-sidebar .vy-archive-sidebar__sticky",
    );

    function setSidebarTop() {
      if (!sidebarSticky) return;
      if (window.innerWidth > 767) {
        var h = parseInt(
          getComputedStyle(document.documentElement).getPropertyValue(
            "--header-height-desktop",
          ) || "80",
          10,
        );
        sidebarSticky.style.top = h + 24 + "px";
      } else {
        sidebarSticky.style.top = "";
      }
    }

    setSidebarTop();

    var resizeTimer;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(setSidebarTop, 100);
    });
  }); /* end DOMContentLoaded */
})();
