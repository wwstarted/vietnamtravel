/**
 * VOYA — single.js
 * Single post interactions
 *
 * 1. Reading Progress Bar
 * 2. Image Lightbox
 * 3. Share button popups
 * 4. Scroll fade-in (Intersection Observer)
 * 5. Table of Contents (TOC)
 * 6. Sidebar sticky offset
 *
 * Fix so với Wanderland:
 *  - Ionicons class → FA6 (vy-toc__arrow)
 *  - Sidebar sticky: dùng --header-height-desktop (bỏ --topbar-height)
 *  - TOC: dùng hidden attr thay vì style="display:none" để accessible hơn
 *  - Share: popup window.open thay vì href default
 *  - Lightbox: clean DOM — tạo 1 lần, reuse
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // ══════════════════════════════════════════════════════════
    // 1. READING PROGRESS BAR
    // ══════════════════════════════════════════════════════════
    var bar = document.createElement("div");
    bar.className = "vy-reading-progress";
    bar.setAttribute("aria-hidden", "true");
    document.body.appendChild(bar);

    var article = document.getElementById("vy-article");
    var ticking = false;

    function updateProgress() {
      ticking = false;
      if (!article) return;
      var top = article.getBoundingClientRect().top + window.scrollY;
      var height = article.offsetHeight;
      var scrolled = window.scrollY - top;
      var pct = Math.min(Math.max(scrolled / height, 0), 1);
      bar.style.width = pct * 100 + "%";
    }

    window.addEventListener(
      "scroll",
      function () {
        if (!ticking) {
          requestAnimationFrame(updateProgress);
          ticking = true;
        }
      },
      { passive: true },
    );

    updateProgress();

    // ══════════════════════════════════════════════════════════
    // 2. IMAGE LIGHTBOX
    // ══════════════════════════════════════════════════════════
    var contentImgs = document.querySelectorAll(".vy-single-entry img");

    if (contentImgs.length) {
      // Build lightbox DOM once
      var lb = document.createElement("div");
      lb.className = "vy-lightbox";
      lb.setAttribute("role", "dialog");
      lb.setAttribute("aria-modal", "true");
      lb.setAttribute("aria-label", "Image lightbox");
      lb.innerHTML =
        '<button class="vy-lightbox__close" aria-label="Đóng">' +
        '<i class="fa-solid fa-xmark" aria-hidden="true"></i>' +
        "</button>" +
        '<img class="vy-lightbox__img" src="" alt="">';
      document.body.appendChild(lb);

      var lbImg = lb.querySelector(".vy-lightbox__img");
      var lbClose = lb.querySelector(".vy-lightbox__close");

      function openLb(src, alt) {
        lbImg.src = src;
        lbImg.alt = alt || "";
        lb.classList.add("is-open");
        document.body.style.overflow = "hidden";
        lbClose.focus();
      }

      function closeLb() {
        lb.classList.remove("is-open");
        document.body.style.overflow = "";
        setTimeout(function () {
          lbImg.src = "";
        }, 300);
      }

      contentImgs.forEach(function (img) {
        img.style.cursor = "zoom-in";
        img.addEventListener("click", function () {
          openLb(img.src, img.alt);
        });
      });

      lbClose.addEventListener("click", closeLb);
      lb.addEventListener("click", function (e) {
        if (e.target === lb) closeLb();
      });

      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && lb.classList.contains("is-open")) closeLb();
      });
    }

    // ══════════════════════════════════════════════════════════
    // 3. SHARE BUTTON POPUPS
    // ══════════════════════════════════════════════════════════
    var shareBtns = document.querySelectorAll(".vy-share-btn");
    shareBtns.forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        var href = btn.getAttribute("href");
        if (href && href.startsWith("http")) {
          e.preventDefault();
          window.open(
            href,
            "_blank",
            "width=640,height=480,scrollbars=yes,noopener,noreferrer",
          );
        }
      });
    });

    // ══════════════════════════════════════════════════════════
    // 4. COMMENT FORM PLACEHOLDERS
    // ══════════════════════════════════════════════════════════
    var placeholders = {
      "#author": "Tên *",
      "#email": "Email *",
      "#url": "Website",
      "#comment": "Bình luận *",
    };
    Object.keys(placeholders).forEach(function (sel) {
      var el = document.querySelector(sel);
      if (el) el.setAttribute("placeholder", placeholders[sel]);
    });

    // ══════════════════════════════════════════════════════════
    // 5. SCROLL FADE-IN (Intersection Observer)
    // ══════════════════════════════════════════════════════════
    if ("IntersectionObserver" in window) {
      var targets = document.querySelectorAll(
        ".vy-single-entry p, " +
          ".vy-single-entry blockquote, " +
          ".vy-single-entry h2, " +
          ".vy-single-entry h3, " +
          ".vy-single-entry img, " +
          ".vy-author-box, " +
          ".vy-single-nav, " +
          ".vy-related-card",
      );

      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add("vy-fade-in");
              observer.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.08, rootMargin: "0px 0px -28px 0px" },
      );

      targets.forEach(function (el) {
        el.classList.add("vy-will-animate");
        observer.observe(el);
      });
    }

    // ══════════════════════════════════════════════════════════
    // 6. TABLE OF CONTENTS (TOC)
    // ══════════════════════════════════════════════════════════
    var tocEl = document.getElementById("vy-toc");
    var tocList = tocEl ? tocEl.querySelector(".vy-toc__list") : null;
    var tocToggle = tocEl ? tocEl.querySelector(".vy-toc__toggle") : null;
    var tocHead = tocEl ? tocEl.querySelector(".vy-toc__head") : null;
    var entry = document.querySelector(".vy-single-entry");

    if (tocEl && tocList && entry) {
      var headings = Array.from(entry.querySelectorAll("h2, h3")).filter(
        function (h) {
          return h.textContent.trim().length > 0;
        },
      );

      if (headings.length < 2) {
        // Không đủ heading — xoá TOC
        tocEl.remove();
      } else {
        // Build list
        headings.forEach(function (h, i) {
          if (!h.id) {
            var slug = h.textContent
              .trim()
              .toLowerCase()
              .replace(/[^\p{L}0-9\s-]/gu, "") // Unicode-aware slug
              .replace(/\s+/g, "-")
              .replace(/-+/g, "-")
              .substring(0, 60);
            h.id = "toc-" + i + "-" + slug;
          }

          var li = document.createElement("li");
          var a = document.createElement("a");
          if (h.tagName === "H3") li.classList.add("vy-toc-h3");

          a.href = "#" + h.id;
          a.textContent = h.textContent.trim();

          a.addEventListener("click", function (e) {
            e.preventDefault();
            var target = document.getElementById(h.id);
            if (!target) return;
            var headerH = parseInt(
              getComputedStyle(document.documentElement).getPropertyValue(
                "--header-height-desktop",
              ) || "80",
              10,
            );
            window.scrollTo({
              top:
                target.getBoundingClientRect().top +
                window.scrollY -
                headerH -
                20,
              behavior: "smooth",
            });
          });

          li.appendChild(a);
          tocList.appendChild(li);
        });

        // Inject after first <p>
        var firstP = entry.querySelector("p");
        if (firstP && firstP.nextSibling) {
          entry.insertBefore(tocEl, firstP.nextSibling);
        } else {
          entry.prepend(tocEl);
        }

        // Show TOC — remove hidden + inline style
        tocEl.removeAttribute("hidden");
        tocEl.style.display = "";

        // Toggle collapse
        function toggleToc() {
          var collapsed = tocEl.classList.toggle("is-collapsed");
          if (tocToggle)
            tocToggle.setAttribute(
              "aria-label",
              collapsed ? "Mở rộng mục lục" : "Thu gọn mục lục",
            );
          if (tocHead)
            tocHead.setAttribute("aria-expanded", collapsed ? "false" : "true");
        }

        if (tocHead) {
          tocHead.addEventListener("click", toggleToc);
          tocHead.addEventListener("keydown", function (e) {
            if (e.key === "Enter" || e.key === " ") {
              e.preventDefault();
              toggleToc();
            }
          });
        }

        // Active heading on scroll
        var tocLinks = Array.from(tocList.querySelectorAll("a"));

        function updateActiveToc() {
          var headerH =
            parseInt(
              getComputedStyle(document.documentElement).getPropertyValue(
                "--header-height-desktop",
              ) || "80",
              10,
            ) + 24;
          var current = "";

          headings.forEach(function (h) {
            if (window.scrollY >= h.offsetTop - headerH - 8) current = h.id;
          });

          tocLinks.forEach(function (link) {
            link
              .closest("li")
              .classList.toggle(
                "is-active",
                link.getAttribute("href") === "#" + current,
              );
          });
        }

        window.addEventListener("scroll", updateActiveToc, { passive: true });
        updateActiveToc();

        // Auto-collapse on mobile
        if (window.innerWidth <= 767) {
          tocEl.classList.add("is-collapsed");
          if (tocHead) tocHead.setAttribute("aria-expanded", "false");
        }
      }
    }

    // ══════════════════════════════════════════════════════════
    // 7. SIDEBAR STICKY OFFSET
    //    FIX: chỉ dùng --header-height-desktop (bỏ topbar)
    // ══════════════════════════════════════════════════════════
    var sidebar = document.getElementById("vy-sidebar");
    if (sidebar && window.innerWidth > 767) {
      var headerH = parseInt(
        getComputedStyle(document.documentElement).getPropertyValue(
          "--header-height-desktop",
        ) || "80",
        10,
      );
      sidebar.style.top = headerH + 24 + "px";
    }

    // Re-calc on resize
    window.addEventListener("resize", function () {
      if (!sidebar) return;
      if (window.innerWidth > 767) {
        var hH = parseInt(
          getComputedStyle(document.documentElement).getPropertyValue(
            "--header-height-desktop",
          ) || "80",
          10,
        );
        sidebar.style.top = hH + 24 + "px";
      } else {
        sidebar.style.top = "";
      }
    });

    // ══════════════════════════════════════════════════════════
    // 8. HERO LOADED ANIMATION (Ken Burns)
    // ══════════════════════════════════════════════════════════
    var heroEl = document.querySelector(".vy-single-hero");
    if (heroEl) {
      heroEl.classList.add("is-loaded");
    }
  }); // end DOMContentLoaded
})();
