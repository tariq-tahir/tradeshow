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
/*!**************************************************!*\
  !*** ./assets/src/scripts/exporter-dashboard.js ***!
  \**************************************************/
/* provided dependency */ var jQuery = __webpack_require__(/*! jquery */ "jquery");
(function ($) {
  function bindMediaButtons() {
    // Upload
    $(document).on('click', '.awps-upload', function (e) {
      e.preventDefault();
      var $btn = $(this);
      var $target = $($btn.data('target')); // hidden input
      var $preview = $btn.siblings('.preview');
      var frame = wp.media({
        title: 'Select or Upload Image',
        button: {
          text: 'Use this image'
        },
        multiple: false
      });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        $target.val(attachment.url); // storing URL
        $preview.html('<img src="' + attachment.url + '" style="max-width:150px;height:auto;">');
      });
      frame.open();
    });

    // Remove
    $(document).on('click', '.awps-remove', function (e) {
      e.preventDefault();
      var $btn = $(this);
      var $target = $($btn.data('target'));
      var $preview = $btn.siblings('.preview');
      $target.val('');
      $preview.empty();
    });
  }
  $(bindMediaButtons);
})(jQuery);
})();

/******/ })()
;