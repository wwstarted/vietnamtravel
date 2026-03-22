/**
 * VOYA — page-cms.js
 * CMS Page interactions:
 *  1. Hero Ken Burns
 *  2. Image Slider — infinite loop, 3-up→2-up→1-up, touch, keyboard
 *  3. Content scroll fade-in
 *
 * Redesigned từ Wanderland page-cms.js:
 *  - Prefix wl→vy
 *  - Thêm dots indicator
 *  - Thêm IntersectionObserver content fade-in
 *  - Cải thiện clone/infinite logic
 *  - Keyboard a11y tốt hơn
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    // ══════════════════════════════════════════════════════
    // 1. HERO KEN BURNS
    // ══════════════════════════════════════════════════════
    var heroEl = document.querySelector(".vy-cms-hero");
    if (heroEl) {
      // Trigger scale animation
      setTimeout(function () {
        heroEl.classList.add("is-loaded");
      }, 100);
    }

    // ══════════════════════════════════════════════════════
    // 2. IMAGE SLIDER
    //    Infinite loop: clone N slides at front & back
    //    Responsive: 3-up desktop | 2-up tablet | 1-up mobile
    // ══════════════════════════════════════════════════════
    var slider = document.getElementById("vy-cms-slider");
    var track = document.getElementById("vy-cms-slider-track");
    var btnPrev = document.getElementById("vy-cms-prev");
    var btnNext = document.getElementById("vy-cms-next");
    var dotsWrap = document.getElementById("vy-cms-dots");

    if (!slider || !track || !btnPrev || !btnNext) goto_content_anim();

    var slides = Array.from(track.querySelectorAll(".vy-cms-slide"));
    var total = slides.length;
    var GAP = 16; // px — matches CSS gap
    var currentIdx = 0;
    var isAnimating = false;
    var autoTimer = null;
    var cloneCount = 0;

    // Less than 2 slides → hide controls
    if (total < 2) {
      btnPrev.style.display = "none";
      btnNext.style.display = "none";
      if (dotsWrap) dotsWrap.style.display = "none";
      goto_content_anim();
    } else {
      initSlider();
    }

    function getVisible() {
      var w = window.innerWidth;
      if (w <= 480) return 1;
      if (w <= 1199) return 2;
      return 3;
    }

    function getSlideWidth() {
      var visible = getVisible();
      var totalGaps = GAP * (visible - 1);
      var trackW = slider.offsetWidth;
      return (trackW - totalGaps) / visible;
    }

    // ── Build infinite clones ─────────────────────────────
    function buildClones() {
      // Remove previous clones
      track.querySelectorAll(".vy-cms-slide--clone").forEach(function (c) {
        c.parentNode.removeChild(c);
      });

      cloneCount = Math.min(total, getVisible() + 1);

      // Prepend: clones of last N slides (reversed)
      for (var i = cloneCount - 1; i >= 0; i--) {
        var cl =
          slides[(total - 1 - (i % total) + total) % total].cloneNode(true);
        cl.classList.add("vy-cms-slide--clone");
        cl.setAttribute("aria-hidden", "true");
        track.insertBefore(cl, track.firstChild);
      }

      // Append: clones of first N slides
      for (var j = 0; j < cloneCount; j++) {
        var cl2 = slides[j % total].cloneNode(true);
        cl2.classList.add("vy-cms-slide--clone");
        cl2.setAttribute("aria-hidden", "true");
        track.appendChild(cl2);
      }
    }

    // ── Set slide widths ──────────────────────────────────
    function setWidths() {
      var sw = getSlideWidth();
      track
        .querySelectorAll(".vy-cms-slide, .vy-cms-slide--clone")
        .forEach(function (s) {
          s.style.flex = "0 0 " + sw + "px";
          s.style.minWidth = sw + "px";
        });
    }

    // ── Get translateX for a logical index ───────────────
    function getOffset(idx) {
      var sw = getSlideWidth();
      return -((cloneCount + idx) * (sw + GAP));
    }

    // ── Move track to index ───────────────────────────────
    function moveTo(idx, animate) {
      if (animate === undefined) animate = true;
      track.style.transition = animate
        ? "transform 0.58s cubic-bezier(0.4, 0, 0.2, 1)"
        : "none";
      track.style.transform = "translateX(" + getOffset(idx) + "px)";
      updateDots();
    }

    // ── After transition: jump from clone to real ─────────
    track.addEventListener("transitionend", function () {
      isAnimating = false;
      if (currentIdx < 0) {
        currentIdx = total - 1;
        moveTo(currentIdx, false);
      } else if (currentIdx >= total) {
        currentIdx = 0;
        moveTo(currentIdx, false);
      }
      updateDots();
    });

    // ── Navigation ───────────────────────────────────────
    function goNext() {
      if (isAnimating) return;
      isAnimating = true;
      currentIdx++;
      moveTo(currentIdx);
      resetAuto();
    }

    function goPrev() {
      if (isAnimating) return;
      isAnimating = true;
      currentIdx--;
      moveTo(currentIdx);
      resetAuto();
    }

    function goTo(idx) {
      if (isAnimating || idx === currentIdx) return;
      isAnimating = true;
      currentIdx = idx;
      moveTo(currentIdx);
      resetAuto();
    }

    btnNext.addEventListener("click", goNext);
    btnPrev.addEventListener("click", goPrev);

    // ── Dots ─────────────────────────────────────────────
    function buildDots() {
      if (!dotsWrap) return;
      dotsWrap.innerHTML = "";
      for (var d = 0; d < total; d++) {
        var dot = document.createElement("button");
        dot.className = "vy-cms-dot" + (d === 0 ? " is-active" : "");
        dot.setAttribute("type", "button");
        dot.setAttribute("role", "tab");
        dot.setAttribute("aria-selected", d === 0 ? "true" : "false");
        dot.setAttribute("aria-label", "Slide " + (d + 1));
        dot.setAttribute("data-idx", d);
        dotsWrap.appendChild(dot);
      }
    }

    function updateDots() {
      if (!dotsWrap) return;
      var real = ((currentIdx % total) + total) % total;
      dotsWrap.querySelectorAll(".vy-cms-dot").forEach(function (dot, i) {
        var active = i === real;
        dot.classList.toggle("is-active", active);
        dot.setAttribute("aria-selected", active ? "true" : "false");
      });
    }

    dotsWrap &&
      dotsWrap.addEventListener("click", function (e) {
        var dot = e.target.closest(".vy-cms-dot");
        if (!dot) return;
        goTo(parseInt(dot.getAttribute("data-idx"), 10));
      });

    // ── Autoplay ─────────────────────────────────────────
    function startAuto() {
      autoTimer = setInterval(goNext, 5000);
    }

    function stopAuto() {
      clearInterval(autoTimer);
    }

    function resetAuto() {
      stopAuto();
      startAuto();
    }

    // Pause on hover/focus
    slider.addEventListener("mouseenter", stopAuto);
    slider.addEventListener("mouseleave", startAuto);
    slider.addEventListener("focusin", stopAuto);
    slider.addEventListener("focusout", function (e) {
      if (!slider.contains(e.relatedTarget)) startAuto();
    });
    document.addEventListener("visibilitychange", function () {
      document.hidden ? stopAuto() : startAuto();
    });

    // ── Touch / swipe ─────────────────────────────────────
    var touchStartX = 0,
      touchStartY = 0,
      swiping = false;

    slider.addEventListener(
      "touchstart",
      function (e) {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
        swiping = true;
      },
      { passive: true },
    );

    slider.addEventListener(
      "touchmove",
      function (e) {
        if (!swiping) return;
        if (
          Math.abs(e.touches[0].clientY - touchStartY) >
          Math.abs(e.touches[0].clientX - touchStartX)
        ) {
          swiping = false;
        }
      },
      { passive: true },
    );

    slider.addEventListener(
      "touchend",
      function (e) {
        if (!swiping) return;
        swiping = false;
        var dx = e.changedTouches[0].clientX - touchStartX;
        if (Math.abs(dx) > 44) {
          dx < 0 ? goNext() : goPrev();
        }
      },
      { passive: true },
    );

    // ── Keyboard ─────────────────────────────────────────
    slider.addEventListener("keydown", function (e) {
      if (e.key === "ArrowLeft") {
        e.preventDefault();
        goPrev();
      }
      if (e.key === "ArrowRight") {
        e.preventDefault();
        goNext();
      }
    });

    // ── Resize ───────────────────────────────────────────
    var resizeTimer;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () {
        buildClones();
        setWidths();
        moveTo(currentIdx, false);
      }, 150);
    });

    // ── Init ─────────────────────────────────────────────
    function initSlider() {
      buildClones();
      buildDots();
      setWidths();
      moveTo(0, false);
      startAuto();
    }

    // ══════════════════════════════════════════════════════
    // 3. CONTENT SCROLL FADE-IN
    // ══════════════════════════════════════════════════════
    goto_content_anim();

    function goto_content_anim() {
      if (!("IntersectionObserver" in window)) return;

      var targets = document.querySelectorAll(
        ".vy-cms-content p, " +
          ".vy-cms-content h2, " +
          ".vy-cms-content h3, " +
          ".vy-cms-content h4, " +
          ".vy-cms-content blockquote, " +
          ".vy-cms-content img, " +
          ".vy-cms-content figure, " +
          ".vy-cms-content table, " +
          ".vy-cms-content ul, " +
          ".vy-cms-content ol, " +
          ".vy-cms-content hr",
      );

      var obs = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add("vy-fade-in");
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.06, rootMargin: "0px 0px -24px 0px" },
      );

      targets.forEach(function (el, i) {
        el.classList.add("vy-will-animate");
        el.style.transitionDelay = Math.min(i * 0.04, 0.3) + "s";
        obs.observe(el);
      });
    }
  }); // end DOMContentLoaded
})();
