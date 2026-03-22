/**
 * VOYA — home.js
 * 1. Hero Slider     — autoplay, touch, keyboard, progress bar
 * 2. Featured Slider — 3→2→1 responsive, header arrows
 * 3. Social Share    — data-share popup (bỏ inline onclick)
 *
 * Fix so với version trước:
 *  - Back-to-top REMOVED: đã có trong footer.js, không cần duplicate
 *  - Social share: dùng data-share attr thay vì inline onclick (CSP safe)
 *  - slides[0].classList.add('is-active') di chuyển VÀO sau triggerEnter
 *    để tránh flash trắng trước khi animation chạy
 */

(function () {
  "use strict";

  /* ═══════════════════════════════════════════════════════════
     1. HERO SLIDER
  ═══════════════════════════════════════════════════════════ */

  document.addEventListener("DOMContentLoaded", function () {
    var slider = document.querySelector(".vy-hero");
    if (!slider) return;

    var slides = Array.from(slider.querySelectorAll(".vy-hero__slide"));
    var dots = Array.from(slider.querySelectorAll(".vy-hero__dot"));
    var btnPrev = slider.querySelector(".vy-hero__arrow--prev");
    var btnNext = slider.querySelector(".vy-hero__arrow--next");
    var counterCur = slider.querySelector(".vy-hero__counter-cur");
    var progressBar = slider.querySelector(".vy-hero__progress-bar");

    var TOTAL = slides.length;
    var AUTOPLAY_MS = 6000;
    var prefersRM = window.matchMedia(
      "(prefers-reduced-motion: reduce)",
    ).matches;

    if (TOTAL <= 1) {
      hideEl(btnPrev);
      hideEl(btnNext);
      hideEl(slider.querySelector(".vy-hero__dots"));
      hideEl(slider.querySelector(".vy-hero__progress"));
      hideEl(slider.querySelector(".vy-hero__controls"));
      triggerEnter(getContent(slides[0]));
      return;
    }

    var current = 0;
    var isAnimating = false;
    var autoTimer = null;

    function getContent(slide) {
      return slide ? slide.querySelector(".vy-hero__content") : null;
    }

    function triggerEnter(el) {
      if (!el) return;
      if (prefersRM) {
        el.style.opacity = "1";
        el.style.transform = "none";
        return;
      }
      el.classList.remove("is-visible", "is-exit");
      el.style.opacity = "0";
      el.style.transform = "translateY(32px)";
      void el.offsetWidth;
      el.style.opacity = "";
      el.style.transform = "";
      setTimeout(function () {
        el.classList.add("is-visible");
      }, 360);
    }

    function triggerExit(el, cb) {
      if (!el || prefersRM) {
        if (cb) cb();
        return;
      }
      el.classList.remove("is-visible");
      el.classList.add("is-exit");
      setTimeout(function () {
        el.classList.remove("is-exit");
        if (cb) cb();
      }, 320);
    }

    function go(next) {
      if (isAnimating || next === current) return;
      isAnimating = true;

      var prev = current;
      current = next;
      var prevSlide = slides[prev];
      var nextSlide = slides[next];

      triggerExit(getContent(prevSlide), function () {
        prevSlide.classList.add("is-leaving");
        prevSlide.setAttribute("aria-hidden", "true");
        setTabIndex(prevSlide, "-1");

        nextSlide.classList.add("is-active");
        nextSlide.setAttribute("aria-hidden", "false");
        setTabIndex(nextSlide, "0");

        dots.forEach(function (d, i) {
          var active = i === next;
          d.classList.toggle("is-active", active);
          d.setAttribute("aria-selected", active ? "true" : "false");
        });

        if (counterCur) counterCur.textContent = pad(next + 1);
        triggerEnter(getContent(nextSlide));

        var fallback = setTimeout(cleanup, 960);
        prevSlide.addEventListener("transitionend", function handler(e) {
          if (e.propertyName !== "opacity") return;
          clearTimeout(fallback);
          cleanup();
          prevSlide.removeEventListener("transitionend", handler);
        });

        function cleanup() {
          prevSlide.classList.remove("is-active", "is-leaving");
          isAnimating = false;
        }
      });
    }

    function goNext() {
      go((current + 1) % TOTAL);
    }
    function goPrev() {
      go((current - 1 + TOTAL) % TOTAL);
    }

    function setTabIndex(slide, val) {
      slide.querySelectorAll("a, button").forEach(function (el) {
        el.setAttribute("tabindex", val);
      });
    }

    /* Progress bar */
    function resetProgress() {
      if (!progressBar) return;
      progressBar.style.transition = "none";
      progressBar.style.width = "0%";
      void progressBar.offsetWidth;
    }
    function startProgress() {
      if (!progressBar || prefersRM) return;
      resetProgress();
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          progressBar.style.transition = "width " + AUTOPLAY_MS + "ms linear";
          progressBar.style.width = "100%";
        });
      });
    }

    /* Autoplay */
    function startAuto() {
      stopAuto();
      startProgress();
      autoTimer = setInterval(function () {
        goNext();
        startProgress();
      }, AUTOPLAY_MS);
    }
    function stopAuto() {
      clearInterval(autoTimer);
      autoTimer = null;
      resetProgress();
    }

    /* Controls */
    if (btnPrev)
      btnPrev.addEventListener("click", function () {
        goPrev();
        startAuto();
      });
    if (btnNext)
      btnNext.addEventListener("click", function () {
        goNext();
        startAuto();
      });

    dots.forEach(function (dot) {
      dot.addEventListener("click", function () {
        var i = parseInt(dot.dataset.index, 10);
        if (!isNaN(i) && i !== current) {
          go(i);
          startAuto();
        }
      });
    });

    /* Keyboard */
    slider.setAttribute("tabindex", "0");
    slider.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") {
        e.preventDefault();
        goPrev();
        startAuto();
      }
      if (e.key === "ArrowRight") {
        e.preventDefault();
        goNext();
        startAuto();
      }
    });

    /* Pause on hover/focus/hidden */
    slider.addEventListener("mouseenter", stopAuto);
    slider.addEventListener("mouseleave", startAuto);
    slider.addEventListener("focusin", stopAuto);
    slider.addEventListener("focusout", function (e) {
      if (!slider.contains(e.relatedTarget)) startAuto();
    });
    document.addEventListener("visibilitychange", function () {
      document.hidden ? stopAuto() : startAuto();
    });

    /* Touch */
    (function () {
      var startX = 0,
        startY = 0,
        dragging = false;
      var THRESH = 50;
      slider.addEventListener(
        "touchstart",
        function (e) {
          startX = e.touches[0].clientX;
          startY = e.touches[0].clientY;
          dragging = true;
        },
        { passive: true },
      );
      slider.addEventListener(
        "touchmove",
        function (e) {
          if (!dragging) return;
          if (
            Math.abs(e.touches[0].clientY - startY) >
            Math.abs(e.touches[0].clientX - startX)
          )
            dragging = false;
        },
        { passive: true },
      );
      slider.addEventListener(
        "touchend",
        function (e) {
          if (!dragging) return;
          dragging = false;
          var dx = e.changedTouches[0].clientX - startX;
          var dy = e.changedTouches[0].clientY - startY;
          if (Math.abs(dx) > THRESH && Math.abs(dx) > Math.abs(dy)) {
            dx < 0 ? goNext() : goPrev();
            startAuto();
          }
        },
        { passive: true },
      );
    })();

    /* Helpers */
    function pad(n) {
      return String(n).padStart(2, "0");
    }
    function hideEl(el) {
      if (el) el.style.display = "none";
    }

    /* Init */
    slides[0].classList.add("is-active");
    slides[0].setAttribute("aria-hidden", "false");
    slides.forEach(function (s, i) {
      if (i !== 0) setTabIndex(s, "-1");
    });
    triggerEnter(getContent(slides[0]));
    if (!prefersRM) startAuto();
  }); // end DOMContentLoaded — Hero

  /* ═══════════════════════════════════════════════════════════
     2. FEATURED POSTS SLIDER
  ═══════════════════════════════════════════════════════════ */

  document.addEventListener("DOMContentLoaded", function () {
    var wrap = document.querySelector(".vy-featured__slider-wrap");
    if (!wrap) return;

    var viewport = wrap.querySelector(".vy-featured__viewport");
    var track = wrap.querySelector(".vy-featured__track");
    var cards = Array.from(
      track ? track.querySelectorAll(".vy-featured__card") : [],
    );
    var btnPrev = document.querySelector(".vy-featured__arrow--prev");
    var btnNext = document.querySelector(".vy-featured__arrow--next");

    var GAP = 24;
    var total = cards.length;
    var current = 0;

    if (!viewport || !track || total === 0) return;

    function getVisible() {
      var w = window.innerWidth;
      if (w <= 767) return 1;
      if (w <= 1199) return 2;
      return 3;
    }

    function cardWidth() {
      var visible = getVisible();
      var totalGaps = GAP * (visible - 1);
      return (viewport.offsetWidth - totalGaps) / visible;
    }

    function setWidths() {
      var w = cardWidth();
      cards.forEach(function (c) {
        c.style.width = w + "px";
      });
    }

    function moveTo(idx) {
      var visible = getVisible();
      var maxIdx = Math.max(0, total - visible);
      current = Math.max(0, Math.min(idx, maxIdx));
      var offset = current * (cardWidth() + GAP);
      track.style.transform = "translateX(-" + offset + "px)";
      if (btnPrev) btnPrev.disabled = current === 0;
      if (btnNext) btnNext.disabled = current >= maxIdx;
    }

    if (btnPrev)
      btnPrev.addEventListener("click", function () {
        moveTo(current - 1);
      });
    if (btnNext)
      btnNext.addEventListener("click", function () {
        moveTo(current + 1);
      });

    /* Keyboard */
    wrap.setAttribute("tabindex", "0");
    wrap.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") {
        e.preventDefault();
        moveTo(current - 1);
      }
      if (e.key === "ArrowRight") {
        e.preventDefault();
        moveTo(current + 1);
      }
    });

    /* Touch */
    var touchX = 0,
      touchY = 0,
      swiping = false;
    viewport.addEventListener(
      "touchstart",
      function (e) {
        touchX = e.touches[0].clientX;
        touchY = e.touches[0].clientY;
        swiping = true;
      },
      { passive: true },
    );
    viewport.addEventListener(
      "touchmove",
      function (e) {
        if (!swiping) return;
        if (
          Math.abs(e.touches[0].clientY - touchY) >
          Math.abs(e.touches[0].clientX - touchX)
        )
          swiping = false;
      },
      { passive: true },
    );
    viewport.addEventListener(
      "touchend",
      function (e) {
        if (!swiping) return;
        swiping = false;
        var dx = e.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 48)
          dx < 0 ? moveTo(current + 1) : moveTo(current - 1);
      },
      { passive: true },
    );

    /* Resize */
    var resizeTimer;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        setWidths();
        moveTo(current);
      }, 120);
    });

    setWidths();
    moveTo(0);
  }); // end DOMContentLoaded — Featured

  /* ═══════════════════════════════════════════════════════════
     3. SOCIAL SHARE — data-share popup (CSP-safe, no onclick)
  ═══════════════════════════════════════════════════════════ */

  document.addEventListener("DOMContentLoaded", function () {
    var shareLinks = document.querySelectorAll(
      ".vy-explore__social-link[data-share]",
    );

    shareLinks.forEach(function (link) {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        var url = link.getAttribute("data-share");
        if (!url) return;
        window.open(url, "_blank", "width=620,height=400,noopener,noreferrer");
      });
    });
  });
})();
