/**
 * WANDERLAND — Admin: Home Sections Options Page
 * File: admin/js/home-options.js
 *
 * Features:
 *  - Search posts via WP REST API (/wp/v2/posts)
 *  - Add post to selected list
 *  - Remove post from selected list
 *  - Drag-to-sort (HTML5 native DnD — no library needed)
 *  - Update order badges + hidden inputs after sort
 *  - Counter badge update
 *  - Auto-dismiss saved notice
 */

(function ($) {
  "use strict";

  // ── Config from PHP ────────────────────────────────────────────
  const CFG = window.WL_HOME_OPTIONS || {};
  const REST_URL = CFG.restUrl || "/wp-json/wp/v2/posts";
  const NONCE = CFG.nonce || "";
  const I18N = CFG.i18n || {};

  // ── DOM refs ───────────────────────────────────────────────────
  const $form = $("#wl-home-form");
  const $searchInput = $("#wl-post-search");
  const $searchRes = $("#wl-search-results");
  const $spinner = $("#wl-search-spinner");
  const $list = $("#wl-sortable-list");
  const $emptyState = $("#wl-empty-state");
  const $counter = $("#wl-post-count");
  const $saveBtn = $("#wl-save-btn");

  const sectionKey = $form.data("section") || "hero_slider";
  const optionName = "wl_section_" + sectionKey;
  const maxPosts = parseInt($form.data("max"), 10) || 8;

  // ── State ──────────────────────────────────────────────────────
  let searchTimer = null;
  let dragSrcEl = null; // element being dragged

  // ── INIT ───────────────────────────────────────────────────────
  function init() {
    bindSearch();
    bindRemove();
    bindDragSort();
    updateOrderBadges();
    updateCounter();
    autoDismissNotice();
  }

  // ═══════════════════════════════════════════════════════════════
  //  SEARCH
  // ═══════════════════════════════════════════════════════════════

  function bindSearch() {
    $searchInput.on("input", function () {
      const q = $.trim($(this).val());
      clearTimeout(searchTimer);

      if (q.length < 2) {
        showSearchPlaceholder();
        return;
      }

      $spinner.addClass("is-visible");
      searchTimer = setTimeout(function () {
        searchPosts(q);
      }, 350);
    });

    // Close results when clicking outside
    $(document).on("click.wlSearch", function (e) {
      if (!$(e.target).closest("#wl-post-search, #wl-search-results").length) {
        showSearchPlaceholder();
      }
    });
  }

  function searchPosts(query) {
    $.ajax({
      url: REST_URL,
      method: "GET",
      beforeSend: function (xhr) {
        xhr.setRequestHeader("X-WP-Nonce", NONCE);
      },
      data: {
        search: query,
        per_page: 10,
        status: "publish",
        _fields: "id,title,date,_links,categories",
        _embed: "wp:featuredmedia,wp:term",
      },
      success: function (posts) {
        $spinner.removeClass("is-visible");
        renderSearchResults(posts);
      },
      error: function () {
        $spinner.removeClass("is-visible");
        $searchRes.html(
          '<p class="wl-no-results">' +
            (I18N.noResults || "Không tìm thấy.") +
            "</p>",
        );
      },
    });
  }

  function renderSearchResults(posts) {
    if (!posts || posts.length === 0) {
      $searchRes.html(
        '<p class="wl-no-results">' +
          (I18N.noResults || "Không tìm thấy bài viết.") +
          "</p>",
      );
      return;
    }

    // Get currently added IDs
    const addedIds = getAddedIds();
    let html = "";

    posts.forEach(function (post) {
      const isAdded = addedIds.indexOf(post.id) !== -1;
      const thumbUrl = getEmbedThumb(post);
      const catName = getEmbedCat(post);
      const postDate = formatDate(post.date);
      const title = $("<div>").text(post.title.rendered).html(); // escape

      html +=
        '<button type="button" class="wl-result-item' +
        (isAdded ? " is-added" : "") +
        '"' +
        ' data-post-id="' +
        post.id +
        '"' +
        ' data-title="' +
        escapeAttr(post.title.rendered) +
        '"' +
        ' data-date="' +
        escapeAttr(postDate) +
        '"' +
        ' data-cat="' +
        escapeAttr(catName) +
        '"' +
        ' data-thumb="' +
        escapeAttr(thumbUrl) +
        '"' +
        ' aria-label="' +
        escapeAttr("Thêm: " + post.title.rendered) +
        '"' +
        ' role="option"' +
        (isAdded ? ' aria-disabled="true"' : "") +
        ">";

      // Thumb
      html += '<div class="wl-result-thumb">';
      if (thumbUrl) {
        html +=
          '<img src="' + escapeAttr(thumbUrl) + '" alt="" loading="lazy">';
      } else {
        html += '<span class="dashicons dashicons-format-image"></span>';
      }
      html += "</div>";

      // Info
      html += '<div class="wl-result-info">';
      html += '<span class="wl-result-title">' + title + "</span>";
      html += '<div class="wl-result-meta">';
      if (catName) {
        html +=
          '<span class="wl-result-cat">' +
          $("<div>").text(catName).html() +
          "</span>";
        html += '<span class="wl-meta-dot">·</span>';
      }
      html +=
        '<span class="wl-result-date">' +
        $("<div>").text(postDate).html() +
        "</span>";
      html += "</div></div>";

      // Add icon
      html += '<span class="wl-result-add">';
      html += isAdded
        ? '<span class="dashicons dashicons-yes" title="Đã thêm"></span>'
        : '<span class="dashicons dashicons-plus-alt2"></span>';
      html += "</span>";

      html += "</button>";
    });

    $searchRes.html(html);

    // Bind click on result items
    $searchRes.find(".wl-result-item:not(.is-added)").on("click", function () {
      addPost({
        id: parseInt($(this).data("post-id"), 10),
        title: $(this).data("title"),
        date: $(this).data("date"),
        cat: $(this).data("cat"),
        thumb: $(this).data("thumb"),
      });

      // Mark as added in results
      $(this).addClass("is-added").attr("aria-disabled", "true").off("click");
      $(this)
        .find(".wl-result-add")
        .html('<span class="dashicons dashicons-yes" title="Đã thêm"></span>');
    });
  }

  function showSearchPlaceholder() {
    $searchRes.html(
      '<div class="wl-search-placeholder">' +
        '<span class="dashicons dashicons-search"></span>' +
        "<p>" +
        (I18N.searchPlaceholder
          ? "Nhập từ khoá để tìm bài viết"
          : "Search posts") +
        "</p>" +
        "</div>",
    );
    $spinner.removeClass("is-visible");
  }

  // ═══════════════════════════════════════════════════════════════
  //  ADD POST
  // ═══════════════════════════════════════════════════════════════

  function addPost(post) {
    // Max check
    if (getAddedIds().length >= maxPosts) {
      showToast(I18N.maxReached || "Đã đạt giới hạn.", "warning");
      return;
    }

    // Already added check
    if (getAddedIds().indexOf(post.id) !== -1) {
      showToast(
        I18N.alreadyAdded || "Bài viết này đã có trong danh sách.",
        "warning",
      );
      return;
    }

    // Build item HTML
    const thumbHtml = post.thumb
      ? '<img src="' + escapeAttr(post.thumb) + '" alt="" loading="lazy">'
      : '<span class="dashicons dashicons-format-image"></span>';

    const catHtml = post.cat
      ? '<span class="wl-post-cat">' +
        $("<div>").text(post.cat).html() +
        '</span><span class="wl-meta-dot">·</span>'
      : "";

    const itemHtml =
      '<div class="wl-post-item" data-post-id="' +
      post.id +
      '" draggable="true" role="listitem">' +
      '<input type="hidden" name="' +
      optionName +
      '[]" value="' +
      post.id +
      '">' +
      '<span class="wl-drag-handle" aria-hidden="true" title="Kéo để sắp xếp">' +
      '<svg width="10" height="16" viewBox="0 0 10 16" fill="none">' +
      '<circle cx="3" cy="2" r="1.5" fill="currentColor"/><circle cx="7" cy="2" r="1.5" fill="currentColor"/>' +
      '<circle cx="3" cy="6" r="1.5" fill="currentColor"/><circle cx="7" cy="6" r="1.5" fill="currentColor"/>' +
      '<circle cx="3" cy="10" r="1.5" fill="currentColor"/><circle cx="7" cy="10" r="1.5" fill="currentColor"/>' +
      '<circle cx="3" cy="14" r="1.5" fill="currentColor"/><circle cx="7" cy="14" r="1.5" fill="currentColor"/>' +
      "</svg></span>" +
      '<div class="wl-post-thumb">' +
      thumbHtml +
      "</div>" +
      '<div class="wl-post-info">' +
      '<span class="wl-post-title">' +
      $("<div>").text(post.title).html() +
      "</span>" +
      '<div class="wl-post-meta">' +
      catHtml +
      '<span class="wl-post-date">' +
      $("<div>").text(post.date).html() +
      "</span></div>" +
      "</div>" +
      '<span class="wl-post-order"></span>' +
      '<button type="button" class="wl-remove-btn" aria-label="Xoá bài viết này" data-post-id="' +
      post.id +
      '">' +
      '<span class="dashicons dashicons-no-alt"></span></button>' +
      "</div>";

    // Remove empty state if showing
    $emptyState.hide();

    // Append and animate in
    const $item = $(itemHtml);
    $item.css({ opacity: 0, transform: "translateY(-8px)" });
    $list.append($item);

    requestAnimationFrame(function () {
      $item.css({
        transition: "opacity 0.2s ease, transform 0.2s ease",
        opacity: 1,
        transform: "translateY(0)",
      });
    });

    // Bind DnD and remove on new item
    bindDragItem($item[0]);
    $item.find(".wl-remove-btn").on("click", handleRemove);

    updateOrderBadges();
    updateCounter();
  }

  // ═══════════════════════════════════════════════════════════════
  //  REMOVE POST
  // ═══════════════════════════════════════════════════════════════

  function bindRemove() {
    $list.on("click", ".wl-remove-btn", handleRemove);
  }

  function handleRemove() {
    const $item = $(this).closest(".wl-post-item");
    const postId = parseInt($(this).data("post-id"), 10);

    // Animate out
    $item.css({ transition: "opacity 0.18s ease, transform 0.18s ease" });
    $item.css({ opacity: 0, transform: "translateX(8px)" });

    setTimeout(function () {
      $item.remove();
      updateOrderBadges();
      updateCounter();

      // Show empty state if none left
      if ($list.find(".wl-post-item").length === 0) {
        $emptyState.show();
      }

      // Re-enable in search results
      $searchRes
        .find('.wl-result-item[data-post-id="' + postId + '"]')
        .removeClass("is-added")
        .attr("aria-disabled", null)
        .find(".wl-result-add")
        .html('<span class="dashicons dashicons-plus-alt2"></span>');

      // Re-bind click on that item
      $searchRes
        .find('.wl-result-item[data-post-id="' + postId + '"]')
        .on("click", function () {
          addPost({
            id: parseInt($(this).data("post-id"), 10),
            title: $(this).data("title"),
            date: $(this).data("date"),
            cat: $(this).data("cat"),
            thumb: $(this).data("thumb"),
          });
          $(this).addClass("is-added").off("click");
          $(this)
            .find(".wl-result-add")
            .html('<span class="dashicons dashicons-yes"></span>');
        });
    }, 200);
  }

  // ═══════════════════════════════════════════════════════════════
  //  DRAG TO SORT (HTML5 native DnD)
  // ═══════════════════════════════════════════════════════════════

  function bindDragSort() {
    // Bind on existing items
    $list.find(".wl-post-item").each(function () {
      bindDragItem(this);
    });
  }

  function bindDragItem(el) {
    el.addEventListener("dragstart", onDragStart);
    el.addEventListener("dragend", onDragEnd);
    el.addEventListener("dragover", onDragOver);
    el.addEventListener("dragleave", onDragLeave);
    el.addEventListener("drop", onDrop);

    // Drag must start from handle
    const handle = el.querySelector(".wl-drag-handle");
    if (handle) {
      handle.addEventListener("mousedown", function () {
        el.setAttribute("draggable", "true");
      });
    }
  }

  function onDragStart(e) {
    dragSrcEl = this;
    this.classList.add("is-dragging");
    e.dataTransfer.effectAllowed = "move";
    e.dataTransfer.setData("text/plain", this.dataset.postId);
  }

  function onDragEnd() {
    this.classList.remove("is-dragging");
    // Remove all drag-over highlights
    $list.find(".wl-post-item").removeClass("is-drag-over");
    dragSrcEl = null;
    updateOrderBadges();
  }

  function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";

    if (this !== dragSrcEl) {
      // Clear all, then add to this
      $list.find(".wl-post-item").removeClass("is-drag-over");
      this.classList.add("is-drag-over");
    }
    return false;
  }

  function onDragLeave() {
    this.classList.remove("is-drag-over");
  }

  function onDrop(e) {
    e.preventDefault();
    e.stopPropagation();

    if (dragSrcEl && this !== dragSrcEl) {
      const listEl = $list[0];
      const items = Array.from(listEl.querySelectorAll(".wl-post-item"));
      const srcIdx = items.indexOf(dragSrcEl);
      const dstIdx = items.indexOf(this);

      if (srcIdx < dstIdx) {
        listEl.insertBefore(dragSrcEl, this.nextSibling);
      } else {
        listEl.insertBefore(dragSrcEl, this);
      }
    }

    this.classList.remove("is-drag-over");
    updateOrderBadges();
    return false;
  }

  // ═══════════════════════════════════════════════════════════════
  //  HELPERS
  // ═══════════════════════════════════════════════════════════════

  function updateOrderBadges() {
    $list.find(".wl-post-item").each(function (i) {
      $(this)
        .find(".wl-post-order")
        .text(i + 1);
    });
  }

  function updateCounter() {
    const count = $list.find(".wl-post-item").length;
    $counter.text(count);
    // Visual warning when approaching max
    if (count >= maxPosts) {
      $counter.css("color", "#d63638");
    } else {
      $counter.css("color", "");
    }
  }

  function getAddedIds() {
    return $list
      .find(".wl-post-item")
      .map(function () {
        return parseInt($(this).data("post-id"), 10);
      })
      .get();
  }

  // Extract featured image URL from REST _embed
  function getEmbedThumb(post) {
    try {
      const media = post._embedded && post._embedded["wp:featuredmedia"];
      if (media && media[0] && media[0].media_details) {
        const sizes = media[0].media_details.sizes;
        if (sizes.thumbnail) return sizes.thumbnail.source_url;
        if (sizes.medium) return sizes.medium.source_url;
        return media[0].source_url;
      }
    } catch (e) {}
    return "";
  }

  // Extract first category from REST _embed
  function getEmbedCat(post) {
    try {
      const terms = post._embedded && post._embedded["wp:term"];
      if (terms) {
        const cats = terms.find(function (group) {
          return group[0] && group[0].taxonomy === "category";
        });
        if (cats && cats[0]) return cats[0].name;
      }
    } catch (e) {}
    return "";
  }

  function formatDate(dateStr) {
    try {
      const d = new Date(dateStr);
      return d.toLocaleDateString("vi-VN", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });
    } catch (e) {
      return dateStr ? dateStr.substring(0, 10) : "";
    }
  }

  function escapeAttr(str) {
    return String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  function showToast(msg, type) {
    const $toast = $(
      '<div style="position:fixed;bottom:32px;right:32px;z-index:9999;' +
        "padding:12px 20px;border-radius:6px;font-size:13px;font-weight:500;" +
        "background:" +
        (type === "warning" ? "#fff8ed" : "#f0fdf4") +
        ";" +
        "border:1px solid " +
        (type === "warning" ? "#fcd34d" : "#86efac") +
        ";" +
        "color:" +
        (type === "warning" ? "#92400e" : "#166534") +
        ";" +
        "box-shadow:0 4px 12px rgba(0,0,0,0.12);" +
        'transition:opacity 0.3s ease;opacity:0;">' +
        msg +
        "</div>",
    );
    $("body").append($toast);
    requestAnimationFrame(function () {
      $toast.css("opacity", "1");
      setTimeout(function () {
        $toast.css("opacity", "0");
        setTimeout(function () {
          $toast.remove();
        }, 350);
      }, 2500);
    });
  }

  function autoDismissNotice() {
    const $notice = $("#wl-saved-notice");
    if ($notice.length) {
      setTimeout(function () {
        $notice.css({ transition: "opacity 0.4s ease", opacity: "0" });
        setTimeout(function () {
          $notice.slideUp(300);
        }, 400);
      }, 3000);
    }
  }

  // ── Save button feedback ───────────────────────────────────────
  $saveBtn.on("click", function () {
    $(this)
      .addClass("is-saving")
      .find(".dashicons")
      .removeClass("dashicons-saved")
      .addClass("dashicons-update");
  });

  // ── Init ───────────────────────────────────────────────────────
  $(document).ready(init);
})(jQuery);
