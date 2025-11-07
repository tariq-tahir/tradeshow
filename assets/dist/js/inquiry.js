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
/*!***************************************!*\
  !*** ./assets/src/scripts/inquiry.js ***!
  \***************************************/
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
jQuery(function ($) {
  // Toggle the form
  $(document).on('click', '.awps-inquiry-toggle', function () {
    $(this).closest('.awps-inquiry-wrap').find('.awps-inquiry-form').slideToggle();
  });

  // Cancel
  $(document).on('click', '.awps-inquiry-cancel', function () {
    $(this).closest('.awps-inquiry-form').slideUp();
  });

  // AJAX submit (with file upload support)
  $(document).on('submit', '.awps-inquiry-post-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var fd = new FormData(this); // file inputs included

    // show spinner / disable
    var $btn = $form.find('button[type="submit"]');
    $btn.prop('disabled', true).text('Sending...');
    $.ajax({
      url: typeof awpsInquiry !== 'undefined' ? awpsInquiry.ajax_url : '/wp-admin/admin-ajax.php',
      method: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json'
    }).done(function (res) {
      var $result = $form.siblings('.awps-inquiry-result');
      if (res.success) {
        $result.html('<div class="notice success">' + res.data.message + '</div>').show();
        $form[0].reset();
        $form.slideUp();
      } else {
        $result.html('<div class="notice error">' + (res.data && res.data.message ? res.data.message : 'Submission failed') + '</div>').show();
      }
    }).fail(function (xhr, status, err) {
      // helpful debug: show response text when available
      var msg = 'An error occurred. Please try again.';
      try {
        if (xhr && xhr.responseText) {
          // try to parse JSON error
          var j = JSON.parse(xhr.responseText);
          if (j && j.data && j.data.message) msg = j.data.message;
        }
      } catch (e) {}
      alert('❌ ' + msg);
      console.error('Inquiry AJAX failed', status, err, xhr && xhr.responseText);
    }).always(function () {
      $btn.prop('disabled', false).text('📨 Request Quote');
    });
  });
});
})();

/******/ })()
;