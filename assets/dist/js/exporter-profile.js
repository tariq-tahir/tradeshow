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
/*!************************************************!*\
  !*** ./assets/src/scripts/exporter-profile.js ***!
  \************************************************/
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
jQuery(document).ready(function ($) {
  function bindMediaUpload(buttonSelector, inputSelector, previewSelector) {
    $(buttonSelector).on('click', function (e) {
      e.preventDefault();
      var button = $(this);
      var customUploader = wp.media({
        title: 'Select Image',
        button: {
          text: 'Use this image'
        },
        multiple: false
      }).on('select', function () {
        var attachment = customUploader.state().get('selection').first().toJSON();
        $(inputSelector).val(attachment.url);
        if (previewSelector) {
          $(previewSelector).attr('src', attachment.url).show();
        }
      }).open();
    });
  }

  // Logo uploader
  bindMediaUpload('#upload_logo_button', '#company_logo', '#logo_preview');

  // Banner uploader
  bindMediaUpload('#upload_banner_button', '#company_banner', '#banner_preview');
});
})();

/******/ })()
;