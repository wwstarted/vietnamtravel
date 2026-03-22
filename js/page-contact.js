/**
 * VOYA — page-contact.js
 * Contact form: real-time validation + UX enhancements
 *
 * Redesigned từ Wanderland:
 *  - Prefix wl → vy
 *  - FA6 spinner (vy-cf-submit__spinner) thay SVG rotate
 *  - Smooth scroll to form notice sau submit
 *  - Textarea char counter (thêm mới)
 */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("vy-contact-form");
    var submit = document.getElementById("vy-cf-submit");

    if (!form || !submit) return;

    var inputs = Array.from(
      form.querySelectorAll(".vy-cf-input, .vy-cf-textarea"),
    );

    // ══════════════════════════════════════════════════════
    // 1. REAL-TIME VALIDATION
    // ══════════════════════════════════════════════════════
    inputs.forEach(function (el) {
      // Validate on blur
      el.addEventListener("blur", function () {
        validateField(el);
      });

      // Re-validate while typing (chỉ khi đã có lỗi)
      el.addEventListener("input", function () {
        if (el.classList.contains("is-invalid")) {
          validateField(el);
        }
        // Filled state
        el.classList.toggle("is-filled", el.value.trim() !== "");
      });

      // Init filled state
      el.classList.toggle("is-filled", el.value.trim() !== "");
    });

    function validateField(el) {
      var val = el.value.trim();
      var valid = true;

      if (el.required && !val) {
        valid = false;
      } else if (el.type === "email" && val) {
        valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
      }

      el.classList.toggle("is-invalid", !valid);
      el.classList.toggle("is-valid", valid && val !== "");
      return valid;
    }

    function validateAll() {
      var allValid = true;
      inputs.forEach(function (el) {
        if (!validateField(el)) allValid = false;
      });
      return allValid;
    }

    // ══════════════════════════════════════════════════════
    // 2. FORM SUBMIT — validate + loading state
    // ══════════════════════════════════════════════════════
    form.addEventListener("submit", function (e) {
      if (!validateAll()) {
        e.preventDefault();
        // Focus first invalid field
        var first = form.querySelector(".is-invalid");
        if (first) {
          first.focus();
          first.scrollIntoView({ behavior: "smooth", block: "center" });
        }
        return;
      }

      // Show loading
      submit.classList.add("is-loading");
      submit.disabled = true;
    });

    // ══════════════════════════════════════════════════════
    // 3. TEXTAREA AUTO-GROW
    // ══════════════════════════════════════════════════════
    var textarea = form.querySelector(".vy-cf-textarea");

    if (textarea) {
      function autoGrow() {
        textarea.style.height = "auto";
        textarea.style.height = Math.max(160, textarea.scrollHeight) + "px";
      }

      textarea.addEventListener("input", autoGrow);
      autoGrow(); // init
    }

    // ══════════════════════════════════════════════════════
    // 4. SMOOTH SCROLL TO NOTICE (sau PHP submit)
    //    Nếu trang load lại với notice thì scroll xuống form
    // ══════════════════════════════════════════════════════
    var notice = document.querySelector(".vy-form-notice");
    if (notice) {
      var headerH = parseInt(
        getComputedStyle(document.documentElement).getPropertyValue(
          "--header-height-desktop",
        ) || "80",
        10,
      );
      var top =
        notice.getBoundingClientRect().top + window.scrollY - headerH - 24;
      window.scrollTo({ top: Math.max(0, top), behavior: "smooth" });
    }

    // ══════════════════════════════════════════════════════
    // 5. TEXTAREA CHAR COUNTER (thêm mới)
    //    Hiện "X ký tự" dưới textarea
    // ══════════════════════════════════════════════════════
    if (textarea) {
      var counter = document.createElement("span");
      counter.className = "vy-cf-char-count";
      counter.style.cssText =
        "display:block;text-align:right;font-size:10px;color:var(--color-secondary);" +
        "margin-top:4px;letter-spacing:0.05em;font-family:var(--font-body);";
      textarea.parentElement.appendChild(counter);

      function updateCount() {
        var len = textarea.value.length;
        counter.textContent = len + " ký tự";
        counter.style.color =
          len > 500 ? "var(--color-accent)" : "var(--color-secondary)";
      }

      textarea.addEventListener("input", updateCount);
      updateCount();
    }
  }); // end DOMContentLoaded
})();
