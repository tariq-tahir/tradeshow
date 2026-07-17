/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/***/ ((module) => {

"use strict";
module.exports = window["jQuery"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*************************************!*\
  !*** ./assets/src/scripts/admin.js ***!
  \*************************************/
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
jQuery(document).ready(function ($) {
  var savedCity = awpsAdmin.savedCity || "";
  var ajaxUrl = awpsAdmin.ajaxUrl;
  var cities = awpsAdmin.cities || {};
  var countries = awpsAdmin.countries || [];
  var languages = awpsAdmin.languages || [];
  var paymentTerms = awpsAdmin.paymentTerms || [];
  var packagingOptions = awpsAdmin.packaging || [];

  // ==========================
  // UTILITY: Add a tag to a specific tag list container
  // ==========================
  function addTagToContainer($list, value) {
    if (!value) return;

    // Escape double quotes to safely use in attribute selector
    var escapedValue = value.replace(/"/g, '\\"');
    if ($list.find("[data-value=\"".concat(escapedValue, "\"]")).length > 0) {
      return; // Prevent duplicates
    }
    var $tag = $("<span class=\"tag\" data-value=\"".concat(value, "\">").concat(value, " <span class=\"remove-tag\">\xD7</span></span>"));
    $list.append($tag);

    // Update the hidden input in the same container
    var $hidden = $list.siblings('input[type="hidden"]');
    var tags = [];
    $list.find(".tag").each(function () {
      tags.push($(this).data("value"));
    });
    $hidden.val(tags.join(","));
  }

  // ==========================
  // MEDIA UPLOADER
  // ==========================
  $(document).on("click", ".media-upload-btn", function (e) {
    e.preventDefault();
    var wrap = $(this).closest(".media-upload-wrap");
    var field = wrap.data("field");
    var input = wrap.find('input[name="' + field + '"]');
    var preview = wrap.find(".preview");
    var removeBtn = wrap.find(".media-remove-btn");
    var frame = wp.media({
      title: "Select or Upload Image",
      button: {
        text: "Use this image"
      },
      multiple: false
    });
    frame.on("select", function () {
      var attachment = frame.state().get("selection").first().toJSON();
      input.val(attachment.url);
      preview.html('<img src="' + attachment.url + '" style="max-width:150px;height:auto;">');
      removeBtn.show();
    });
    frame.open();
  });
  $(document).on("click", ".media-remove-btn", function (e) {
    e.preventDefault();
    var wrap = $(this).closest(".media-upload-wrap");
    wrap.find('input[type="hidden"]').val("");
    wrap.find(".preview").empty();
    $(this).hide();
  });

  // ==========================
  // STATE → CITY DYNAMIC SELECT
  // ==========================
  function updateCities() {
    var state = $("#state").val();
    var citySelect = $("#city");
    citySelect.empty();
    if (state && cities[state]) {
      cities[state].forEach(function (city) {
        var isSelected = city === savedCity;
        var option = new Option(city, city, false, isSelected);
        citySelect.append(option);
      });
    } else {
      citySelect.append(new Option("— Select State First —", ""));
    }
  }
  $("#state").on("change", updateCities);
  updateCities(); // Initialize on load

  // ==========================
  // INITIALIZE TAG INPUTS
  // ==========================
  $(".custom-tags-input").each(function () {
    var $container = $(this);
    var $input = $container.find(".tag-input");
    var $list = $container.find(".tag-list");
    var $hidden = $container.find("input[type='hidden']");

    // Restore existing tags from hidden input
    var existing = ($hidden.val() || "").split(",").map(function (t) {
      return t.trim();
    }).filter(Boolean);
    existing.forEach(function (value) {
      addTagToContainer($list, value);
    });

    // Handle tag removal (delegated event)
    $list.on("click", ".remove-tag", function () {
      $(this).parent().remove();
      // Re-sync hidden input
      var tags = [];
      $list.find(".tag").each(function () {
        tags.push($(this).data("value"));
      });
      $hidden.val(tags.join(","));
    });

    // Handle manual entry via Enter or comma
    $input.on("keydown", function (e) {
      if (e.key === "Enter" || e.key === ",") {
        e.preventDefault();
        var val = $input.val().trim();
        if (val) {
          addTagToContainer($list, val);
          $input.val("");
        }
      }
    });
  });

  // ==========================
  // AUTOCOMPLETE SETUP HELPER
  // ==========================
  function setupAutocomplete(selector, source) {
    $(selector).autocomplete({
      source: source,
      select: function select(e, ui) {
        var $container = $(this).closest(".custom-tags-input");
        var $list = $container.find(".tag-list");
        addTagToContainer($list, ui.item.value);
        $(this).val("");
        return false;
      }
    });
  }

  // ==========================
  // STATIC AUTOCOMPLETE FIELDS
  // ==========================
  setupAutocomplete("#exports-to-input", countries);
  setupAutocomplete("#languages-input", languages);
  setupAutocomplete("#payment-terms-input", paymentTerms);
  setupAutocomplete("#packaging-input", packagingOptions);

  // ==========================
  // DYNAMIC (AJAX) AUTOCOMPLETE: Certifications
  // ==========================
  $("#certifications-input").autocomplete({
    source: function source(request, response) {
      $.post(ajaxUrl, {
        action: "get_certifications",
        term: request.term
      }).done(response).fail(function () {
        return response([]);
      });
    },
    select: function select(e, ui) {
      var $container = $(this).closest(".custom-tags-input");
      var $list = $container.find(".tag-list");
      addTagToContainer($list, ui.item.value);
      $(this).val("");
      return false;
    }
  });
});
})();

/******/ })()
;