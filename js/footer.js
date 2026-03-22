/**
 * VOYA — footer.js
 * Back-to-top: show/hide + smooth scroll
 */
(function () {
  "use strict";

  var backToTop = document.getElementById("vy-back-to-top");
  if (!backToTop) return;

  var SHOW_AFTER = 400;
  var ticking = false;

  function updateBtn() {
    ticking = false;
    if (window.scrollY > SHOW_AFTER) {
      backToTop.classList.add("is-visible");
    } else {
      backToTop.classList.remove("is-visible");
    }
  }

  window.addEventListener(
    "scroll",
    function () {
      if (!ticking) {
        requestAnimationFrame(updateBtn);
        ticking = true;
      }
    },
    { passive: true },
  );

  updateBtn(); // run once on load

  backToTop.addEventListener("click", function (e) {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
})();
