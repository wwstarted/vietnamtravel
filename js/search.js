/**
 * VOYA — search.js
 * Search results page interactions:
 *  1. Card entrance animation (tái dụng từ archive)
 *  2. Auto-focus inline search form
 *  3. Highlight search keyword trong kết quả
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // ══════════════════════════════════════════════════════
    // 1. CARD ENTRANCE ANIMATION
    // ══════════════════════════════════════════════════════
    if ("IntersectionObserver" in window) {
      var grid = document.getElementById("vy-search-grid");
      var cards = grid ? grid.querySelectorAll(".vy-archive-card") : [];

      if (cards.length > 0) {
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

        cards.forEach(function (card, i) {
          card.classList.add("vy-card-hidden");
          card.style.transitionDelay = (i % 3) * 0.07 + "s";
          obs.observe(card);
        });
      }
    }

    // ══════════════════════════════════════════════════════
    // 2. FOCUS INLINE SEARCH FORM
    // ══════════════════════════════════════════════════════
    // Chỉ focus nếu không có kết quả (empty state)
    // hoặc user ở đầu trang — không cướp focus khi scroll
    var inlineInput = document.getElementById("vy-search-inline-input");
    if (inlineInput && window.scrollY < 100) {
      // Đặt cursor ở cuối text hiện có
      var val = inlineInput.value;
      inlineInput.value = "";
      inlineInput.value = val;
    }

    // ══════════════════════════════════════════════════════
    // 3. HIGHLIGHT KEYWORD TRONG KẾT QUẢ
    //    Wrap keyword trong title/excerpt bằng <mark>
    // ══════════════════════════════════════════════════════
    var grid2 = document.getElementById("vy-search-grid");
    var urlParams = new URLSearchParams(window.location.search);
    var keyword = (urlParams.get("s") || "").trim();

    if (grid2 && keyword.length >= 2) {
      // Escape special regex chars
      var escaped = keyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
      var regex = new RegExp("(" + escaped + ")", "gi");

      var targets = grid2.querySelectorAll(
        ".vy-archive-card__title a, .vy-archive-card__excerpt",
      );

      targets.forEach(function (el) {
        // Chỉ highlight text nodes — không đụng vào child elements
        highlightTextNodes(el, regex);
      });
    }

    function highlightTextNodes(el, regex) {
      // Walk text nodes only
      var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);

      var nodes = [];
      var node;
      while ((node = walker.nextNode())) {
        nodes.push(node);
      }

      nodes.forEach(function (textNode) {
        var text = textNode.nodeValue;
        if (!regex.test(text)) return;
        regex.lastIndex = 0; // reset

        var wrapper = document.createElement("span");
        wrapper.innerHTML = text.replace(
          regex,
          "<mark class='vy-search-highlight'>$1</mark>",
        );
        textNode.parentNode.replaceChild(wrapper, textNode);
      });
    }

    // ══════════════════════════════════════════════════════
    // 4. SUBMIT FORM KHI NHẤN ENTER
    //    (Browser tự handle nhưng ensure không submit rỗng)
    // ══════════════════════════════════════════════════════
    var form = document.querySelector(".vy-search-inline-form");
    if (form) {
      form.addEventListener("submit", function (e) {
        var val = form.querySelector("input[name='s']").value.trim();
        if (!val) {
          e.preventDefault(); // không submit form rỗng
          form.querySelector("input[name='s']").focus();
        }
      });
    }
  }); // end DOMContentLoaded
})();
