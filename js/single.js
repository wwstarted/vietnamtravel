/**
 * VOYA — single.js (v2)
 *
 * Thay đổi so với v1:
 *  - Bỏ hero Ken Burns (không còn hero full-viewport)
 *  - Sidebar sticky: do CSS xử lý, JS chỉ recalc top offset
 *  - TOC: giữ nguyên logic, inject sau .vy-single-excerpt
 *  - Giữ: reading progress, lightbox, share popup, fade-in
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    /* ══════════════════════════════════════════════════
           1. READING PROGRESS BAR
        ══════════════════════════════════════════════════ */
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
      var pct = Math.min(Math.max((window.scrollY - top) / height, 0), 1);
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

    /* ══════════════════════════════════════════════════
           2. IMAGE LIGHTBOX
        ══════════════════════════════════════════════════ */
    var contentImgs = document.querySelectorAll(".vy-single-entry img");

    if (contentImgs.length) {
      var lb = document.createElement("div");
      lb.className = "vy-lightbox";
      lb.setAttribute("role", "dialog");
      lb.setAttribute("aria-modal", "true");
      lb.setAttribute("aria-label", "Image lightbox");
      lb.innerHTML =
        '<button class="vy-lightbox__close" aria-label="Đóng">' +
        '<i class="fa-solid fa-xmark" aria-hidden="true"></i></button>' +
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

    /* ══════════════════════════════════════════════════
           3. SHARE BUTTON POPUPS
        ══════════════════════════════════════════════════ */
    document.querySelectorAll(".vy-share-btn").forEach(function (btn) {
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

    /* ══════════════════════════════════════════════════
           4. COMMENT FORM PLACEHOLDERS
        ══════════════════════════════════════════════════ */
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

    /* ══════════════════════════════════════════════════
           5. SCROLL FADE-IN
        ══════════════════════════════════════════════════ */
    if ("IntersectionObserver" in window) {
      var targets = document.querySelectorAll(
        ".vy-single-entry p, .vy-single-entry blockquote, " +
          ".vy-single-entry h2, .vy-single-entry h3, " +
          ".vy-single-entry img, " +
          ".vy-author-box, .vy-single-nav, " +
          ".vy-sidebar-post-item",
      );

      var fadeObs = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add("vy-fade-in");
              fadeObs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.08, rootMargin: "0px 0px -28px 0px" },
      );

      targets.forEach(function (el) {
        el.classList.add("vy-will-animate");
        fadeObs.observe(el);
      });
    }

    /* ══════════════════════════════════════════════════
           6. TABLE OF CONTENTS (TOC)
           Inject AFTER .vy-single-excerpt (thay vì sau <p> đầu)
        ══════════════════════════════════════════════════ */
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
        tocEl.remove();
      } else {
        /* Build TOC items */
        headings.forEach(function (h, i) {
          if (!h.id) {
            var slug = h.textContent
              .trim()
              .toLowerCase()
              .replace(/[^\p{L}0-9\s-]/gu, "")
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

        /* Inject: sau .vy-single-excerpt, nếu không có thì đầu entry */
        var excerpt = document.querySelector(".vy-single-excerpt");
        if (excerpt && excerpt.parentNode === article) {
          /* TOC nằm ngoài article → move vào trước entry */
          entry.insertBefore(tocEl, entry.firstChild);
        } else {
          /* Default: sau p đầu tiên trong entry */
          var firstP = entry.querySelector("p");
          if (firstP && firstP.nextSibling) {
            entry.insertBefore(tocEl, firstP.nextSibling);
          } else {
            entry.prepend(tocEl);
          }
        }

        /* Show */
        tocEl.removeAttribute("hidden");
        tocEl.style.display = "";

        /* Toggle collapse */
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

        /* Active heading on scroll */
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

        /* Auto-collapse mobile */
        if (window.innerWidth <= 767) {
          tocEl.classList.add("is-collapsed");
          if (tocHead) tocHead.setAttribute("aria-expanded", "false");
        }
      }
    }

    /* ══════════════════════════════════════════════════
           7. SIDEBAR STICKY — CSS xử lý chính
           JS chỉ set top offset đúng theo header height
        ══════════════════════════════════════════════════ */
    var sidebarSticky = document.querySelector(".vy-single-sidebar__sticky");

    function setSidebarTop() {
      if (!sidebarSticky) return;
      if (window.innerWidth > 767) {
        var headerH = parseInt(
          getComputedStyle(document.documentElement).getPropertyValue(
            "--header-height-desktop",
          ) || "80",
          10,
        );
        sidebarSticky.style.top = headerH + 24 + "px";
      } else {
        sidebarSticky.style.top = "";
      }
    }

    setSidebarTop();

    var resizeTimer;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(setSidebarTop, 120);
    });
  }); /* end DOMContentLoaded */
})();
