/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/sass/admin.scss":
/*!************************************!*\
  !*** ./assets/src/sass/admin.scss ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/sass/gutenberg.scss":
/*!****************************************!*\
  !*** ./assets/src/sass/gutenberg.scss ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/sass/style.scss":
/*!************************************!*\
  !*** ./assets/src/sass/style.scss ***!
  \************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./assets/src/scripts/app.js":
/*!***********************************!*\
  !*** ./assets/src/scripts/app.js ***!
  \***********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _modules_app_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./modules/app.js */ "./assets/src/scripts/modules/app.js");
/* harmony import */ var _modules_frontend_admin_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./modules/frontend-admin.js */ "./assets/src/scripts/modules/frontend-admin.js");
/* harmony import */ var _modules_product_single_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./modules/product-single.js */ "./assets/src/scripts/modules/product-single.js");
/* harmony import */ var _modules_account_register_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./modules/account-register.js */ "./assets/src/scripts/modules/account-register.js");
/* harmony import */ var _modules_profile_public_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./modules/profile-public.js */ "./assets/src/scripts/modules/profile-public.js");
/* harmony import */ var _modules_contact_form_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./modules/contact-form.js */ "./assets/src/scripts/modules/contact-form.js");
// app.js

/**
 * Manage global libraries like jQuery or THREE from the webpack.mix.js file
 */

// Import custom modules






//import ChangePassword from './modules/change-password.js';

new _modules_app_js__WEBPACK_IMPORTED_MODULE_0__["default"]();

// Run frontend admin logic if dashboard OR registration page exists
if (document.querySelector('.exporter-dashboard') || document.querySelector('.awps-register-form')) {
  new _modules_frontend_admin_js__WEBPACK_IMPORTED_MODULE_1__["default"]();
}

// Run product single page logic ONLY if product container exists
if (document.querySelector('.awps-product-container')) {
  new _modules_product_single_js__WEBPACK_IMPORTED_MODULE_2__["default"]();
}

// Initialize contact form if present
if (document.querySelector('.awps-contact-form')) {
  new _modules_contact_form_js__WEBPACK_IMPORTED_MODULE_5__["default"]();
}

// Run registration form logic if registration form exists
if (document.querySelector('.awps-register-form')) {
  new _modules_account_register_js__WEBPACK_IMPORTED_MODULE_3__["default"]();
}

// Run public profile logic if profile page exists
if (document.querySelector('.exporter-profile')) {
  new _modules_profile_public_js__WEBPACK_IMPORTED_MODULE_4__["default"]();
}

// // Run change password logic if form exists
// if ( document.getElementById( 'change-password-form' ) ) {
//     new ChangePassword();
// }

// Run ContactForm logic if contact form exists
if (document.querySelector('.awps-contact-form')) {
  new _modules_contact_form_js__WEBPACK_IMPORTED_MODULE_5__["default"]();
}

// Basic back-to-top functionality
document.addEventListener('DOMContentLoaded', function () {
  var backToTop = document.querySelector('.back-to-top');
  window.addEventListener('scroll', function () {
    backToTop.style.display = window.pageYOffset > 300 ? 'flex' : 'none';
  });
  backToTop.addEventListener('click', function (e) {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });
});

/***/ }),

/***/ "./assets/src/scripts/modules/account-register.js":
/*!********************************************************!*\
  !*** ./assets/src/scripts/modules/account-register.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ AccountRegister)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");


/**
 * Account Registration Form Functionality
 * Handles state/city dropdowns and payment plan selection
 * 
 * @package awps
 */
var AccountRegister = /*#__PURE__*/function () {
  function AccountRegister() {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__["default"])(this, AccountRegister);
    this.form = document.querySelector('.awps-register-form');
    if (!this.form) return;
    this.stateEl = document.getElementById('state');
    this.cityEl = document.getElementById('city');
    this.paymentCards = document.querySelectorAll('.payment-plan-card');
    this.paymentRadios = document.querySelectorAll('input[name="payment_plan"]');

    // Get data from form data attributes
    this.citiesData = JSON.parse(this.form.dataset.cities || '{}');
    this.savedCity = this.form.dataset.savedCity || '';
    this.init();
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__["default"])(AccountRegister, [{
    key: "init",
    value: function init() {
      this.initStateCity();
      this.initPaymentPlans();
    }

    // ─────────────────────────────────────────────────────────────
    // State → City Dropdown Logic
    // ─────────────────────────────────────────────────────────────
  }, {
    key: "initStateCity",
    value: function initStateCity() {
      var _this = this;
      if (!this.stateEl || !this.cityEl || !this.citiesData) return;
      var updateCities = function updateCities() {
        var state = _this.stateEl.value;

        // Reset dropdown content
        _this.cityEl.innerHTML = '';

        // CASE 1: No State Selected → Disable City Dropdown
        if (!state) {
          _this.cityEl.disabled = true;
          var defaultOpt = document.createElement('option');
          defaultOpt.value = '';
          defaultOpt.textContent = '— Select State First —';
          _this.cityEl.appendChild(defaultOpt);
          return;
        }

        // CASE 2: State Selected → Enable City Dropdown
        _this.cityEl.disabled = false;

        // Add placeholder
        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Select City —';
        _this.cityEl.appendChild(placeholder);

        // Populate cities from the data
        var cities = _this.citiesData[state] || [];
        cities.forEach(function (cityName) {
          var opt = document.createElement('option');
          opt.value = cityName;
          opt.textContent = cityName;

          // Restore selection if it matches the saved city
          if (cityName === _this.savedCity) {
            opt.selected = true;
          }
          _this.cityEl.appendChild(opt);
        });
      };

      // Attach event listener
      this.stateEl.addEventListener('change', updateCities);

      // Run on page load
      updateCities();
    }

    // ─────────────────────────────────────────────────────────────
    // Payment Plan Selection Logic
    // ─────────────────────────────────────────────────────────────
  }, {
    key: "initPaymentPlans",
    value: function initPaymentPlans() {
      var _this2 = this;
      if (!this.paymentCards.length || !this.paymentRadios.length) return;
      var updateSelectedCard = function updateSelectedCard() {
        _this2.paymentCards.forEach(function (card) {
          var radio = card.querySelector('input[type="radio"]');
          if (radio.checked) {
            card.classList.add('selected');
          } else {
            card.classList.remove('selected');
          }
        });
      };

      // Add click listeners to all cards
      this.paymentCards.forEach(function (card) {
        card.addEventListener('click', function (e) {
          // Don't trigger if clicking directly on the radio
          if (e.target.tagName === 'INPUT') return;
          var radio = card.querySelector('input[type="radio"]');
          radio.checked = true;
          updateSelectedCard();
        });
      });

      // Add change listeners to radios
      this.paymentRadios.forEach(function (radio) {
        radio.addEventListener('change', updateSelectedCard);
      });

      // Run on page load to set initial state
      updateSelectedCard();
    }
  }]);
}();


/***/ }),

/***/ "./assets/src/scripts/modules/app.js":
/*!*******************************************!*\
  !*** ./assets/src/scripts/modules/app.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var _header_menu_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./header-menu.js */ "./assets/src/scripts/modules/header-menu.js");



var App = /*#__PURE__*/function () {
  function App() {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__["default"])(this, App);
    new _header_menu_js__WEBPACK_IMPORTED_MODULE_2__["default"]();
    this.el = document.querySelector('.el');
    this.listeners();
    this.init();
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__["default"])(App, [{
    key: "init",
    value: function init() {
      // eslint-disable-next-line no-console
      console.info('App Initialized');
    }
  }, {
    key: "listeners",
    value: function listeners() {
      if (this.el) {
        this.el.addEventListener('click', this.elClick);
      }
    }
  }, {
    key: "elClick",
    value: function elClick(e) {
      e.target.classList.add('text-light-grey');
      e.target.addEventListener('transitionend', function (event) {
        return 'color' === event.propertyName ? event.target.classList.remove('text-light-grey') : '';
      });
    }
  }]);
}();
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (App);

// import HeaderMenu from './header-menu.js';

// class App {
// 	constructor() {
// 		this.el = document.querySelector('.el');
// 		this.headerMenu = new HeaderMenu();

// 		this.listeners();
// 		this.init();
// 	}

// 	init() {
// 		// eslint-disable-next-line no-console
// 		console.info('App Initialized');
// 	}

// 	listeners() {
// 		if (this.el) {
// 			this.el.addEventListener('click', this.elClick);
// 		}
// 	}

// 	elClick(e) {
// 		e.target.classList.add('text-light-grey');
// 		e.target.addEventListener('transitionend', (event) => {
// 			if (event.propertyName === 'color') {
// 				event.target.classList.remove('text-light-grey');
// 			}
// 		});
// 	}
// }

// export default App;

/***/ }),

/***/ "./assets/src/scripts/modules/contact-form.js":
/*!****************************************************!*\
  !*** ./assets/src/scripts/modules/contact-form.js ***!
  \****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ContactForm)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");


/**
 * Contact Form Functionality
 * Handles submission timestamp, basic validation feedback, and UX enhancements
 */
var ContactForm = /*#__PURE__*/function () {
  function ContactForm() {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__["default"])(this, ContactForm);
    this.form = document.getElementById('awps-contact-form');
    if (!this.form) return;
    this.submitBtn = document.getElementById('contact-submit-btn');
    this.loadTimestamp = Date.now();
    this.init();
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__["default"])(ContactForm, [{
    key: "init",
    value: function init() {
      var _this = this;
      this.form.addEventListener('submit', function (e) {
        return _this.handleSubmit(e);
      });
    }
  }, {
    key: "handleSubmit",
    value: function handleSubmit(e) {
      // Set elapsed time for rate-limiting backend check
      var elapsedSeconds = Math.floor((Date.now() - this.loadTimestamp) / 1000);
      var timeInput = document.getElementById('submit_time');
      if (timeInput) {
        timeInput.value = elapsedSeconds;
      }

      // Basic client-side validation feedback (optional enhancement)
      var requiredFields = this.form.querySelectorAll('[required]');
      var isValid = true;
      requiredFields.forEach(function (field) {
        if (!field.value.trim()) {
          isValid = false;
          field.classList.add('error');

          // Remove error state on input
          field.addEventListener('input', function handler() {
            field.classList.remove('error');
            field.removeEventListener('input', handler);
          }, {
            once: true
          });
        }
      });
      if (!isValid) {
        e.preventDefault();
        this.showValidationMessage(__('Please fill in all required fields.', 'awps'));
        return;
      }

      // Show loading state
      this.setLoadingState(true);
    }
  }, {
    key: "setLoadingState",
    value: function setLoadingState(loading) {
      if (!this.submitBtn) return;
      if (loading) {
        this.submitBtn.disabled = true;
        this.submitBtn.dataset.originalText = this.submitBtn.innerHTML;
        this.submitBtn.innerHTML = '<span class="spinner"></span> ' + __('Sending...', 'awps');
      } else {
        this.submitBtn.disabled = false;
        this.submitBtn.innerHTML = this.submitBtn.dataset.originalText || __('Send Message', 'awps');
      }
    }
  }, {
    key: "showValidationMessage",
    value: function showValidationMessage(message) {
      // Remove existing messages
      var existing = this.form.querySelector('.validation-message');
      if (existing) existing.remove();

      // Create and insert message
      var msg = document.createElement('div');
      msg.className = 'validation-message error';
      msg.setAttribute('role', 'alert');
      msg.textContent = message;
      this.form.insertBefore(msg, this.form.firstChild);

      // Auto-remove after 5 seconds
      setTimeout(function () {
        return msg.remove();
      }, 5000);
    }
  }]);
}();


/***/ }),

/***/ "./assets/src/scripts/modules/frontend-admin.js":
/*!******************************************************!*\
  !*** ./assets/src/scripts/modules/frontend-admin.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FrontendAdmin)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/typeof */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! jquery */ "jquery");
/* harmony import */ var jquery__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(jquery__WEBPACK_IMPORTED_MODULE_3__);





// Universal Media Handler
document.addEventListener('click', function (e) {
  // 🔒 Ignore file input clicks directly
  if (e.target.tagName === 'INPUT' && e.target.type === 'file') {
    return;
  }
  var btn = e.target.closest('[data-action]');
  if (!btn) return;
  var action = btn.dataset.action;

  // IDs for both inputs
  var galleryInput = document.getElementById('gallery-upload-input');
  var featuredInput = document.getElementById('featured-upload-input');

  // ==========================================
  // 1. HANDLE UPLOAD ACTIONS (Gallery & Featured)
  // ==========================================
  if (action === 'upload-featured' || action === 'upload-gallery') {
    // Pick the correct input based on action
    var activeInput = action === 'upload-featured' ? featuredInput : galleryInput;
    activeInput.value = ''; // Reset to allow re-selection

    activeInput.onchange = function (event) {
      var rawFiles = Array.from(event.target.files);
      if (!rawFiles.length) return;

      // --- NEW: FILE SIZE VALIDATION ---
      var MAX_SIZE_MB = 2; // Set your limit here
      var MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

      // Filter out files that are too large
      var files = rawFiles.filter(function (file) {
        if (file.size > MAX_SIZE_BYTES) {
          alert("\u26A0\uFE0F File too large: \"".concat(file.name, "\"\nMaximum allowed size is ").concat(MAX_SIZE_MB, "MB."));
          return false;
        }
        return true;
      });

      // If no files passed the size check, stop here
      if (files.length === 0) {
        activeInput.value = '';
        return;
      }
      // ---------------------------------

      var galleryContainer = document.getElementById('gallery-preview');
      var featuredContainer = document.getElementById('featured-image-container');

      // --- GALLERY LOGIC ---
      if (action === 'upload-gallery') {
        files.forEach(function (file) {
          var reader = new FileReader();
          reader.onload = function (ev) {
            var item = document.createElement('div');
            item.className = 'gallery-item';
            item.style.cssText = "position:relative; display:inline-block; margin:5px;";
            item.dataset.filename = file.name;
            item.innerHTML = "\n                            <img src=\"".concat(ev.target.result, "\" alt=\"").concat(file.name, "\" \n                                 style=\"width:100px;height:100px;object-fit:cover;border-radius:4px;border:1px solid #ddd;\">\n                            <button type=\"button\" class=\"media-remove-btn\" data-action=\"remove-gallery\">\xD7</button>\n                            <input type=\"text\" name=\"gallery_alt_new[]\" placeholder=\"Alt text\" style=\"width:100px; display:block; margin-top:5px; font-size:11px;\">\n                        ");
            galleryContainer.appendChild(item);
          };
          reader.readAsDataURL(file);
        });
      }

      // --- FEATURED LOGIC ---
      else if (action === 'upload-featured') {
        var file = files[0];
        var reader = new FileReader();
        reader.onload = function (ev) {
          // We only update the preview area
          document.getElementById('featured-image-container').innerHTML = "\n                      <div style=\"position:relative; display:inline-block;\">\n                          <img src=\"".concat(ev.target.result, "\" style=\"max-width:200px;height:auto;border:1px solid #ddd;\">\n                          <button type=\"button\" class=\"media-remove-btn\" data-action=\"remove-featured\" data-title=\"").concat(file.name, "\">\xD7</button>\n\n                          <div style=\"margin-top:5px;\">\n                <input type=\"text\" name=\"featured_image_alt\" placeholder=\"Enter Alt Text...\" style=\"width:100%; padding:4px; font-size:12px;\">\n            </div>\n                      </div>\n                  ");
          // Update the hidden status values without deleting the inputs
          document.getElementById('remove_featured_image').value = "0";
          document.getElementById('featured_image_id').value = "new_upload";
        };
        reader.readAsDataURL(file);
      }
    };
    activeInput.click();
  }

  // ==========================================
  // 2. HANDLE REMOVE GALLERY IMAGE
  // ==========================================
  if (action === 'remove-gallery') {
    e.preventDefault();
    e.stopImmediatePropagation();
    var item = btn.closest('.gallery-item');
    if (!item) return;
    var fileName = item.dataset.filename;
    var imageId = item.dataset.id;
    if (confirm("Are you sure you want to delete this image?")) {
      // Sync the Gallery File Input
      var dt = new DataTransfer();
      var files = galleryInput.files;
      for (var i = 0; i < files.length; i++) {
        if (files[i].name !== fileName) dt.items.add(files[i]);
      }
      galleryInput.files = dt.files;

      // Handle DB IDs
      if (imageId && !imageId.startsWith('new-')) {
        var removeInput = document.getElementById('remove_gallery_ids');
        if (removeInput) {
          var existingValue = removeInput.value.trim();
          removeInput.value = existingValue ? existingValue + ',' + imageId : imageId;
        }
      }
      item.remove();
    }
  }

  // ==========================================
  // 3. HANDLE REMOVE FEATURED IMAGE
  // ==========================================
  if (action === 'remove-featured') {
    e.preventDefault();
    e.stopImmediatePropagation();
    if (confirm('Remove featured image?')) {
      // Set the flag for the PHP handler
      document.getElementById('remove_featured_image').value = '1';

      // Clear the preview UI
      document.getElementById('featured-image-container').innerHTML = "\n              <button type=\"button\" class=\"media-upload-btn\" data-action=\"upload-featured\">Upload Image</button>\n          ";

      // Note: We keep the value in #featured_image_id so the PHP knows 
      // which ID was there to delete it from media, but the UI is cleared.
      featuredInput.value = '';
    }
  }
});

// ---------------------------
// Image Upload (preview + remove)
// ---------------------------
function initImageUploads() {
  document.querySelectorAll('.image-upload-wrap').forEach(function (wrap) {
    var input = wrap.querySelector('.image-input');
    var preview = wrap.querySelector('.preview');
    var removeBtn = wrap.querySelector('.remove-image');
    var hiddenRemove = wrap.querySelector('input[type="hidden"]');
    if (!input) return;
    input.addEventListener('change', function (e) {
      var file = e.target.files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (ev) {
        preview.innerHTML = "<img src=\"".concat(ev.target.result, "\" style=\"max-width:200px;height:auto;\">");
        if (removeBtn) removeBtn.style.display = 'inline-block';
        if (hiddenRemove) hiddenRemove.value = '';
      };
      reader.readAsDataURL(file);
    });
    if (removeBtn) {
      removeBtn.addEventListener('click', function () {
        preview.innerHTML = '';
        if (input) input.value = '';
        if (hiddenRemove) hiddenRemove.value = '1';
        removeBtn.style.display = 'none';
      });
    }
  });
}

// ---------------------------
// State -> City Logic
// ---------------------------
function initStateCity(citiesData, savedCity) {
  var updateCities = function updateCities() {
    var stateEl = document.getElementById('state');
    var citySelect = document.getElementById('city');
    if (!citySelect || !stateEl) return;
    var state = stateEl.value.trim();
    citySelect.innerHTML = '<option value="">— Select State First —</option>';
    if (state && citiesData[state]) {
      citiesData[state].forEach(function (city) {
        var option = document.createElement('option');
        option.value = city;
        option.textContent = city;
        citySelect.appendChild(option);
      });
    }
    if (savedCity) {
      for (var i = 0; i < citySelect.options.length; i++) {
        if (citySelect.options[i].value === savedCity) {
          citySelect.selectedIndex = i;
          break;
        }
      }
    }
  };
  var stateEl = document.getElementById('state');
  if (stateEl) {
    stateEl.addEventListener('change', updateCities);
    updateCities();
  }
}

// ---------------------------
// Generic Tag Widget (for autocomplete fields)
// ---------------------------
var TagWidget = /*#__PURE__*/function () {
  function TagWidget(opts) {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__["default"])(this, TagWidget);
    this.input = document.querySelector(opts.inputSelector);
    this.list = document.querySelector(opts.listSelector);
    this.hidden = document.querySelector(opts.hiddenSelector);
    this.sourceType = opts.sourceType;
    this.ajaxAction = opts.ajaxAction;
    this.sourceArray = opts.sourceArray || [];
    this.allowManualEntry = opts.allowManualEntry;
    if (!this.input || !this.list || !this.hidden) return;
    this.initFromDOM();
    this.setupAutocomplete();
    this.setupManualEntry();
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__["default"])(TagWidget, [{
    key: "getCurrentValues",
    value: function getCurrentValues() {
      return Array.from(this.list.querySelectorAll('.tag')).map(function (el) {
        var dataValue = el.getAttribute('data-value');
        if (dataValue) return dataValue;
        var clone = el.cloneNode(true);
        clone.querySelectorAll('.remove-tag').forEach(function (btn) {
          return btn.remove();
        });
        return clone.textContent.trim();
      });
    }
  }, {
    key: "updateHidden",
    value: function updateHidden() {
      this.hidden.value = this.getCurrentValues().join(',');
    }
  }, {
    key: "addTag",
    value: function addTag(valueOrObject) {
      var _this = this;
      var displayText, storageValue;
      if ((0,_babel_runtime_helpers_typeof__WEBPACK_IMPORTED_MODULE_0__["default"])(valueOrObject) === 'object' && valueOrObject !== null) {
        // Handle { label, value } from AJAX
        displayText = String(valueOrObject.label || valueOrObject.value || '').trim();
        storageValue = String(valueOrObject.value || valueOrObject.label || '').trim();
      } else {
        // Fallback for string input (backward compatibility)
        displayText = storageValue = String(valueOrObject || '').trim();
      }
      if (!displayText || !storageValue) return;
      var currentStorageValues = Array.from(this.list.querySelectorAll('.tag')).map(function (el) {
        return el.getAttribute('data-value');
      });
      if (currentStorageValues.includes(storageValue)) return;
      var tag = document.createElement('span');
      tag.className = 'tag';
      tag.setAttribute('data-value', storageValue); // ← stores ID for categories
      tag.innerHTML = "".concat(displayText, " <span class=\"remove-tag\">\xD7</span>"); // ← shows name

      tag.querySelector('.remove-tag').addEventListener('click', function () {
        tag.remove();
        _this.updateHidden();
      });
      this.list.appendChild(tag);
      this.updateHidden();
    }
  }, {
    key: "initFromDOM",
    value: function initFromDOM() {
      var _this2 = this;
      var existingTags = this.list.querySelectorAll('.tag');
      if (existingTags.length > 0) {
        // Ensure every pre-existing tag has a proper data-value and remove listener
        existingTags.forEach(function (tag) {
          if (!tag.hasAttribute('data-value')) {
            var clone = tag.cloneNode(true);
            clone.querySelectorAll('.remove-tag').forEach(function (btn) {
              return btn.remove();
            });
            tag.setAttribute('data-value', clone.textContent.trim());
          }

          // 🔹 CRITICAL: Attach remove listener if not already present
          var removeBtn = tag.querySelector('.remove-tag');
          if (removeBtn && !removeBtn._hasListener) {
            removeBtn._hasListener = true; // prevent duplicate listeners
            removeBtn.addEventListener('click', function () {
              tag.remove();
              _this2.updateHidden();
            });
          }
        });
        this.updateHidden();
      } else {
        var raw = (this.hidden.value || '').toString();
        var arr = raw.split(',').map(function (t) {
          return t.trim();
        }).filter(function (t) {
          return t.length > 0;
        });
        this.list.innerHTML = '';
        this.hidden.value = '';
        arr.forEach(function (v) {
          return _this2.addTag(v);
        });
      }
    }
  }, {
    key: "setupAutocomplete",
    value: function setupAutocomplete() {
      var _$$ui,
        _this3 = this;
      if (!((_$$ui = (jquery__WEBPACK_IMPORTED_MODULE_3___default().ui)) !== null && _$$ui !== void 0 && _$$ui.autocomplete)) return;
      if (this.sourceType === 'ajax' && this.ajaxAction) {
        var spinner = this.input.parentNode.querySelector('.awps-autocomplete-loader');
        jquery__WEBPACK_IMPORTED_MODULE_3___default()(this.input).autocomplete({
          source: function source(request, response) {
            if (spinner) spinner.style.display = 'inline-block';
            jquery__WEBPACK_IMPORTED_MODULE_3___default().ajax({
              url: window.ajaxurl || '/wp-admin/admin-ajax.php',
              dataType: 'json',
              data: {
                action: _this3.ajaxAction,
                term: request.term
              },
              success: function success(data) {
                return response(data || []);
              },
              error: function error() {
                return response([]);
              },
              complete: function complete() {
                if (spinner) spinner.style.display = 'none';
              }
            });
          },
          focus: function focus() {
            return false;
          },
          select: function select(event, ui) {
            // Pass the full item object so addTag can extract label + value
            _this3.addTag(ui.item);
            _this3.input.value = '';
            return false;
          }
        });
      } else if (this.sourceType === 'array' && Array.isArray(this.sourceArray)) {
        jquery__WEBPACK_IMPORTED_MODULE_3___default()(this.input).autocomplete({
          source: function source(request, response) {
            var term = (request.term || '').toLowerCase();
            var results = _this3.sourceArray.filter(function (item) {
              return String(item).toLowerCase().includes(term);
            });
            response(results);
          },
          focus: function focus() {
            return false;
          },
          select: function select(event, ui) {
            var val = (ui === null || ui === void 0 ? void 0 : ui.item) && (ui.item.value || ui.item.label) || ui.item || '';
            _this3.addTag(val);
            _this3.input.value = '';
            return false;
          }
        });
      }
    }
  }, {
    key: "setupManualEntry",
    value: function setupManualEntry() {
      var _this4 = this;
      if (!this.allowManualEntry) return;
      this.input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
          e.preventDefault();
          var v = _this4.input.value.trim();
          if (v) _this4.addTag(v);
          _this4.input.value = '';
        }
      });
    }
  }]);
}(); // ---------------------------
// Custom Tags (for FOB Ports - free text)
// ---------------------------
function initCustomTags() {
  document.querySelectorAll('.custom-tags-input').forEach(function (container) {
    var input = container.querySelector('.tag-input');
    var list = container.querySelector('.tag-list');
    var hidden = container.querySelector('input[type="hidden"]');
    if (!input || !list || !hidden) return;
    function getTagText(tagEl) {
      var clone = tagEl.cloneNode(true);
      clone.querySelectorAll('.remove-tag').forEach(function (btn) {
        return btn.remove();
      });
      return clone.textContent.trim();
    }
    function syncHidden() {
      var tags = Array.from(list.querySelectorAll('.tag')).map(getTagText).filter(function (t) {
        return t;
      });
      hidden.value = tags.join(',');
    }
    function addTag(text) {
      var cleanText = text.trim();
      if (!cleanText) return;
      var existing = Array.from(list.querySelectorAll('.tag')).map(getTagText);
      if (existing.includes(cleanText)) return;
      var tag = document.createElement('span');
      tag.className = 'tag';
      tag.innerHTML = "".concat(cleanText, " <span class=\"remove-tag\">\xD7</span>");
      tag.querySelector('.remove-tag').addEventListener('click', function () {
        tag.remove();
        syncHidden();
      });
      list.appendChild(tag);
      syncHidden();
    }

    // Attach listeners to pre-rendered tags
    list.querySelectorAll('.tag').forEach(function (tag) {
      if (tag.dataset.initialized) return;
      var removeBtn = tag.querySelector('.remove-tag');
      if (removeBtn) {
        removeBtn.addEventListener('click', function () {
          tag.remove();
          syncHidden();
        });
      }
      tag.dataset.initialized = 'true';
    });
    syncHidden();
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        addTag(input.value);
        input.value = '';
      }
    });
  });
}

// ---------------------------
// Image Cropper
// ---------------------------
function initImageCropper() {
  var cropper = null;
  var activeWrap = null;
  var modal = document.getElementById('cropper-modal');
  var cropperImage = document.getElementById('cropper-image');
  var btnCancel = document.getElementById('cropper-cancel');
  var btnSave = document.getElementById('cropper-save');
  if (!modal || !cropperImage) return;
  document.querySelectorAll('.image-upload-wrap .image-input').forEach(function (input) {
    input.addEventListener('change', function (e) {
      var _e$target$files;
      var file = (_e$target$files = e.target.files) === null || _e$target$files === void 0 ? void 0 : _e$target$files[0];
      if (!file) return;
      var reader = new FileReader();
      reader.onload = function (ev) {
        var _activeWrap;
        cropperImage.src = ev.target.result;
        modal.style.display = 'flex';
        activeWrap = input.closest('.image-upload-wrap');
        if (cropper) {
          try {
            cropper.destroy();
          } catch (_unused) {}
        }
        var ratio = ((_activeWrap = activeWrap) === null || _activeWrap === void 0 || (_activeWrap = _activeWrap.dataset) === null || _activeWrap === void 0 ? void 0 : _activeWrap.field) === 'company_logo' ? 1 : 4 / 1;
        if (typeof Cropper === 'undefined') {
          console.error('Cropper.js not loaded');
          return;
        }
        cropper = new Cropper(cropperImage, {
          aspectRatio: ratio,
          viewMode: 1,
          autoCropArea: 1
        });
      };
      reader.readAsDataURL(file);
    });
  });
  btnCancel === null || btnCancel === void 0 || btnCancel.addEventListener('click', function () {
    if (cropper) try {
      cropper.destroy();
    } catch (_unused2) {}
    modal.style.display = 'none';
  });
  btnSave === null || btnSave === void 0 || btnSave.addEventListener('click', function () {
    if (!cropper || !activeWrap) return;
    var width = activeWrap.dataset.field === 'company_logo' ? 300 : 1200;
    var height = activeWrap.dataset.field === 'company_logo' ? 300 : 300;
    var canvas = cropper.getCroppedCanvas({
      width: width,
      height: height
    });
    canvas.toBlob(function (blob) {
      var preview = activeWrap.querySelector('.preview');
      preview.innerHTML = "<img src=\"".concat(canvas.toDataURL(), "\" style=\"max-width:100%;height:auto;\">");
      var fileInput = activeWrap.querySelector('.image-input');
      var dt = new DataTransfer();
      dt.items.add(new File([blob], 'cropped.png', {
        type: 'image/png'
      }));
      fileInput.files = dt.files;
      var removeBtn = activeWrap.querySelector('.remove-image');
      if (removeBtn) removeBtn.style.display = 'inline-block';
      modal.style.display = 'none';
      try {
        cropper.destroy();
      } catch (_unused3) {}
    }, 'image/png');
  });
}

// ---------------------------
// Main Class
// ---------------------------
var FrontendAdmin = /*#__PURE__*/(0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_2__["default"])(function FrontendAdmin() {
  (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_1__["default"])(this, FrontendAdmin);
  jquery__WEBPACK_IMPORTED_MODULE_3___default()(function () {
    var _ref = window.wpExporterData || {},
      stateCities = _ref.stateCities,
      savedCity = _ref.savedCity,
      countries = _ref.countries,
      languages = _ref.languages,
      incoterms = _ref.incoterms,
      packaging = _ref.packaging,
      shipping = _ref.shipping,
      paymentTerms = _ref.paymentTerms,
      samplePolicies = _ref.samplePolicies,
      keyBenefits = _ref.keyBenefits,
      qualityControl = _ref.qualityControl;

    // Always initialize
    initImageUploads();
    initCustomTags(); // for FOB Ports (free-text)
    initImageCropper();

    // 🔹 NEW: Form Validation Logic
    // This checks if the product categories hidden input is empty on submit
    // Inside your FrontendAdmin constructor, within the $(() => { ... }) block:

    // --- ADD THIS VALIDATION BLOCK ---
    jquery__WEBPACK_IMPORTED_MODULE_3___default()(document).on('submit', '#awps-product-form', function (e) {
      var hiddenField = document.querySelector('#_certifications_hidden');
      var rawValue = hiddenField ? hiddenField.value : 'NOT FOUND';
      console.log('🔍 Certifications hidden field value:', rawValue);

      // Also log all hidden inputs with name="_certifications" to check for duplicates
      var allHiddenByName = document.querySelectorAll('input[name="_certifications"]');
      console.log('🔍 All inputs with name="_certifications":', allHiddenByName.length, allHiddenByName);
      var $hiddenInput = jquery__WEBPACK_IMPORTED_MODULE_3___default()('#product_categories');
      var categories = $hiddenInput.val() ? $hiddenInput.val().trim() : "";

      // Check if value is truly empty
      if (categories === "" || categories.length === 0) {
        e.preventDefault(); // Stop the form from submitting
        e.stopImmediatePropagation();

        // 1. Show the alert
        alert('⚠️ Please select at least one Product Category before saving.');

        // 2. Visual feedback (Highlighting the category box)
        var $container = jquery__WEBPACK_IMPORTED_MODULE_3___default()('.awps-tag-container').first(); // Grab the categories container
        $container.css({
          'border': '2px solid #dc3232',
          'padding': '10px',
          'border-radius': '5px',
          'background': '#fff5f5'
        });

        // 3. Scroll to the field
        jquery__WEBPACK_IMPORTED_MODULE_3___default()('html, body').animate({
          scrollTop: $container.offset().top - 100
        }, 400);
        return false;
      }
    });
    // ---------------------------------

    // State-City (Profile tab)
    if (stateCities && typeof savedCity !== 'undefined') {
      initStateCity(stateCities, savedCity);
    }
    if (document.getElementById('exports-to-input')) {
      new TagWidget({
        inputSelector: '#exports-to-input',
        listSelector: '#exports-to-list',
        hiddenSelector: "input[name='exports_to']",
        sourceType: 'array',
        sourceArray: countries || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('languages-input')) {
      new TagWidget({
        inputSelector: '#languages-input',
        listSelector: '#languages-list',
        hiddenSelector: "input[name='languages']",
        sourceType: 'array',
        sourceArray: languages || [],
        allowManualEntry: true
      });
    }

    // Payment Terms & Packaging on Profile tab (if present)
    if (document.getElementById('payment_terms_input')) {
      new TagWidget({
        inputSelector: '#payment_terms_input',
        listSelector: '#payment_terms_list',
        hiddenSelector: "input[name='payment_terms']",
        sourceType: 'array',
        sourceArray: paymentTerms || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('packaging_details_input')) {
      new TagWidget({
        inputSelector: '#packaging_details_input',
        listSelector: '#packaging_details_list',
        hiddenSelector: "input[name='packaging']",
        sourceType: 'array',
        sourceArray: packaging || [],
        allowManualEntry: true
      });
    }

    // 🔹 Product Edit Tab Fields (underscore-prefixed)
    if (document.getElementById('_incoterms_input')) {
      new TagWidget({
        inputSelector: '#_incoterms_input',
        listSelector: '#_incoterms_list',
        hiddenSelector: "input[name='_incoterms']",
        sourceType: 'array',
        sourceArray: incoterms || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('_packaging_input')) {
      new TagWidget({
        inputSelector: '#_packaging_input',
        listSelector: '#_packaging_list',
        hiddenSelector: "input[name='_packaging']",
        sourceType: 'array',
        sourceArray: packaging || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('_shipping_input')) {
      new TagWidget({
        inputSelector: '#_shipping_input',
        listSelector: '#_shipping_list',
        hiddenSelector: "input[name='_shipping']",
        sourceType: 'array',
        sourceArray: shipping || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('_payment_terms_input')) {
      new TagWidget({
        inputSelector: '#_payment_terms_input',
        listSelector: '#_payment_terms_list',
        hiddenSelector: "input[name='_payment_terms']",
        sourceType: 'array',
        sourceArray: paymentTerms || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('_sample_policies_input')) {
      new TagWidget({
        inputSelector: '#_sample_policies_input',
        listSelector: '#_sample_policies_list',
        hiddenSelector: "input[name='_sample_policies']",
        sourceType: 'array',
        sourceArray: samplePolicies || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('_key_benefits_input')) {
      new TagWidget({
        inputSelector: '#_key_benefits_input',
        listSelector: '#_key_benefits_list',
        hiddenSelector: "input[name='_key_benefits']",
        sourceType: 'array',
        sourceArray: keyBenefits || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('_quality_control_input')) {
      new TagWidget({
        inputSelector: '#_quality_control_input',
        listSelector: '#_quality_control_list',
        hiddenSelector: "input[name='_quality_control']",
        sourceType: 'array',
        sourceArray: qualityControl || [],
        allowManualEntry: true
      });
    }
    if (document.getElementById('_certifications_input')) {
      new TagWidget({
        inputSelector: '#_certifications_input',
        listSelector: '#_certifications_list',
        hiddenSelector: '#_certifications_hidden',
        sourceType: 'ajax',
        ajaxAction: 'awps_search_certifications_by_id',
        allowManualEntry: false
      });
    }

    // 🔹 NEW: Product Categories (on Add/Edit Product form)
    if (document.getElementById('product_categories_input')) {
      new TagWidget({
        inputSelector: '#product_categories_input',
        listSelector: '#product_categories_list',
        hiddenSelector: '#product_categories',
        sourceType: 'ajax',
        ajaxAction: 'awps_search_product_categories',
        allowManualEntry: false
      });
    }
  });
});
/**
 * AWPS INDEPENDENT IMAGE HANDLER (Final Version)
 * Specifically handles .awps-unique-remove and .awps-unique-input
 */

(function () {
  var slugify = function slugify(t) {
    return t.toString().toLowerCase().trim().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g, '-');
  };

  // 1. REMOVE LOGIC (Safe Version)
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.awps-iso-remove-btn');
    if (!btn) return; // Exit if the click wasn't on our remove button

    e.preventDefault();
    var wrap = btn.closest('.awps-iso-wrap');
    if (!wrap) return; // SAFETY CHECK: Stop if no wrapper found

    var fieldName = wrap.dataset.field;
    var previewBox = wrap.querySelector('.awps-iso-preview-box');
    var previewCont = wrap.querySelector('.awps-iso-preview-cont');
    var controls = wrap.querySelector('.awps-iso-controls');
    var hiddenRemove = wrap.querySelector('input[name="remove_' + fieldName + '"]');
    var fileInput = wrap.querySelector('.awps-iso-input');
    if (previewBox) previewBox.innerHTML = '';
    if (previewCont) previewCont.setAttribute('style', 'display: none !important');
    if (controls) controls.setAttribute('style', 'display: block !important');
    if (fileInput) fileInput.value = '';
    if (hiddenRemove) hiddenRemove.value = '1';
    console.log("AWPS ISO: Image removed safely.");
  }, true);

  // 2. UPLOAD & CROP LOGIC (Safe Version)
  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('awps-iso-input')) return;
    var input = e.target;
    var file = input.files[0];
    if (!file) return;
    var wrap = input.closest('.awps-iso-wrap');
    if (!wrap) return; // SAFETY CHECK: Stop if no wrapper found

    var fieldName = wrap.dataset.field;
    var modal = document.getElementById('cropper-modal');
    var cropperImg = document.getElementById('cropper-image');
    var reader = new FileReader();
    reader.onload = function (ev) {
      if (!cropperImg || !modal) return;
      cropperImg.src = ev.target.result;
      modal.style.display = 'flex';
      if (window.isoCropper) window.isoCropper.destroy();
      var ratio = fieldName === 'company_logo' ? 1 : 4 / 1;
      window.isoCropper = new Cropper(cropperImg, {
        aspectRatio: ratio,
        viewMode: 1
      });
      var saveBtn = document.getElementById('cropper-save');
      var newBtn = saveBtn.cloneNode(true);
      saveBtn.parentNode.replaceChild(newBtn, saveBtn);
      newBtn.addEventListener('click', function () {
        var canvas = window.isoCropper.getCroppedCanvas({
          width: 800,
          height: 800
        });
        canvas.toBlob(function (blob) {
          var _document$querySelect;
          var dt = new DataTransfer();
          var companyName = ((_document$querySelect = document.querySelector('input[name="company_name"]')) === null || _document$querySelect === void 0 ? void 0 : _document$querySelect.value) || 'company';
          var fileName = "".concat(slugify(companyName), "-").concat(fieldName, ".png");
          dt.items.add(new File([blob], fileName, {
            type: 'image/png'
          }));
          input.files = dt.files;
          var pBox = wrap.querySelector('.awps-iso-preview-box');
          var pCont = wrap.querySelector('.awps-iso-preview-cont');
          var ctrl = wrap.querySelector('.awps-iso-controls');
          if (pBox) {
            // Determine the width based on the field name
            // If it's the banner, use 400px, otherwise use 150px (for logo)
            var imgWidth = fieldName === 'banner_image' ? '400px' : '150px';
            pBox.innerHTML = "\n                            <img src=\"".concat(canvas.toDataURL(), "\" style=\"max-width:").concat(imgWidth, "; width:100%; height:auto;\">\n                            <input type=\"text\" name=\"").concat(fieldName, "_alt\" placeholder=\"Alt text...\" style=\"width:100%; font-size:12px; padding:4px;\">\n                            <button type=\"button\" class=\"awps-iso-remove-btn\">\xD7</button>\n                        ");
          }
          if (pCont) pCont.style.display = 'block';
          if (ctrl) ctrl.style.display = 'none';
          modal.style.display = 'none';
          window.isoCropper.destroy();
        });
      });
    };
    reader.readAsDataURL(file);
  });
})();

/***/ }),

/***/ "./assets/src/scripts/modules/header-menu.js":
/*!***************************************************!*\
  !*** ./assets/src/scripts/modules/header-menu.js ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ HeaderMenu)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");


/**
 * Header Menu Module
 * Handles mobile menu toggle, dropdown interactions, and responsive behavior
 */
var HeaderMenu = /*#__PURE__*/function () {
  function HeaderMenu() {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__["default"])(this, HeaderMenu);
    this.cacheDOM();
    this.bindEvents();
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__["default"])(HeaderMenu, [{
    key: "cacheDOM",
    value: function cacheDOM() {
      this.toggle = document.querySelector('.mobile-menu-toggle');
      this.menu = document.querySelector('.main-menu');
      this.submenuLinks = document.querySelectorAll('.main-menu .menu-item-has-children > a');
      this.welcomeUser = document.querySelector('.awps-welcome');
      this.loginDropdown = document.querySelector('.awps-login-dropdown');
    }
  }, {
    key: "bindEvents",
    value: function bindEvents() {
      var _this = this;
      // Mobile menu toggle
      if (this.toggle && this.menu) {
        this.toggle.addEventListener('click', function (e) {
          e.preventDefault();
          _this.toggle.classList.toggle('active');
          _this.menu.classList.toggle('show');
        });
      }

      // Submenu toggle on mobile
      if (this.submenuLinks.length > 0) {
        this.submenuLinks.forEach(function (link) {
          link.addEventListener('click', function (e) {
            if (window.innerWidth <= 767) {
              e.preventDefault();
              var submenu = link.nextElementSibling;
              if (submenu && submenu.classList.contains('dropdown-menu')) {
                submenu.classList.toggle('show');

                // Close other open submenus
                _this.submenuLinks.forEach(function (otherLink) {
                  var otherSubmenu = otherLink.nextElementSibling;
                  if (otherSubmenu && otherSubmenu !== submenu) {
                    otherSubmenu.classList.remove('show');
                  }
                });
              }
            }
          });
        });
      }

      // User dropdown toggle on mobile
      if (this.welcomeUser && this.loginDropdown) {
        this.welcomeUser.addEventListener('click', function (e) {
          if (window.innerWidth <= 767) {
            e.preventDefault();
            _this.loginDropdown.classList.toggle('show');
          }
        });
      }

      // Close all menus when clicking outside
      document.addEventListener('click', function (e) {
        _this.handleClickOutside(e);
      });

      // Handle window resize
      window.addEventListener('resize', function () {
        if (window.innerWidth > 767) {
          // Reset mobile states on desktop
          if (_this.menu) _this.menu.classList.remove('show');
          if (_this.toggle) _this.toggle.classList.remove('active');
          document.querySelectorAll('.dropdown-menu').forEach(function (submenu) {
            submenu.classList.remove('show');
          });
          if (_this.loginDropdown) _this.loginDropdown.classList.remove('show');
        }
      });
    }
  }, {
    key: "handleClickOutside",
    value: function handleClickOutside(e) {
      // Close mobile menu
      if (this.toggle && this.menu) {
        if (!this.toggle.contains(e.target) && !this.menu.contains(e.target) && this.menu.classList.contains('show')) {
          this.menu.classList.remove('show');
          this.toggle.classList.remove('active');
        }
      }

      // Close user dropdown
      if (this.loginDropdown) {
        if (!this.loginDropdown.contains(e.target) && this.loginDropdown.classList.contains('show')) {
          this.loginDropdown.classList.remove('show');
        }
      }

      // Close submenus when clicking a link (mobile only)
      if (window.innerWidth <= 767) {
        var clickedLink = e.target.closest('.dropdown-menu a');
        if (clickedLink) {
          document.querySelectorAll('.dropdown-menu').forEach(function (submenu) {
            submenu.classList.remove('show');
          });
          if (this.menu) this.menu.classList.remove('show');
          if (this.toggle) this.toggle.classList.remove('active');
        }
      }
    }
  }]);
}();


/***/ }),

/***/ "./assets/src/scripts/modules/product-single.js":
/*!******************************************************!*\
  !*** ./assets/src/scripts/modules/product-single.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ProductSingle)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");


/**
 * Product Single Page Functionality
 * Handles lightbox gallery, inquiry form AJAX, and share actions
 * 
 * NOTE: Uses Promise chains instead of async/await to avoid regenerator-runtime dependency
 */
var ProductSingle = /*#__PURE__*/function () {
  function ProductSingle() {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__["default"])(this, ProductSingle);
    this.lightbox = null;
    this.lightboxImg = null;
    this.currentIndex = 0;
    this.imageIds = [];
    this.imageUrls = {};
    this.init();
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__["default"])(ProductSingle, [{
    key: "init",
    value: function init() {
      // Initialize only if we're on a product page
      if (!document.querySelector('.awps-product-container')) return;
      this.cacheElements();
      this.parseImageData();
      this.bindEvents();
    }
  }, {
    key: "cacheElements",
    value: function cacheElements() {
      this.lightbox = document.getElementById('awps-lightbox');
      this.lightboxImg = document.getElementById('lightbox-image');
      this.closeBtn = document.getElementById('lightbox-close');
      this.prevBtn = document.getElementById('lightbox-prev');
      this.nextBtn = document.getElementById('lightbox-next');
      this.form = document.getElementById('awps-inquiry-form');
      this.responseDiv = document.getElementById('inquiry-response');
      this.shareCopyBtn = document.querySelector('.share-copy');
    }
  }, {
    key: "parseImageData",
    value: function parseImageData() {
      // Read image data from data attributes on the container
      var container = document.querySelector('.awps-product-container');
      if (!container) return;
      try {
        this.imageUrls = JSON.parse(container.dataset.imageUrls || '{}');
        this.imageIds = JSON.parse(container.dataset.imageIds || '[]');
      } catch (e) {
        console.error('Failed to parse product image ', e);
      }
    }
  }, {
    key: "bindEvents",
    value: function bindEvents() {
      var _this = this;
      // Lightbox: thumbnail clicks
      document.querySelectorAll('.gallery-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function (e) {
          e.preventDefault();
          _this.openLightbox(thumb.dataset.id);
        });
      });

      // Lightbox: controls
      if (this.closeBtn) {
        this.closeBtn.addEventListener('click', function () {
          return _this.closeLightbox();
        });
      }
      if (this.prevBtn) {
        this.prevBtn.addEventListener('click', function () {
          return _this.showImage(_this.currentIndex - 1);
        });
      }
      if (this.nextBtn) {
        this.nextBtn.addEventListener('click', function () {
          return _this.showImage(_this.currentIndex + 1);
        });
      }

      // Lightbox: keyboard navigation
      document.addEventListener('keydown', function (e) {
        return _this.handleKeydown(e);
      });

      // Lightbox: close on backdrop click
      if (this.lightbox) {
        this.lightbox.addEventListener('click', function (e) {
          if (e.target === _this.lightbox) _this.closeLightbox();
        });
      }

      // Inquiry form AJAX
      if (this.form) {
        this.form.addEventListener('submit', function (e) {
          return _this.handleInquirySubmit(e);
        });
      }

      // Share: copy link
      if (this.shareCopyBtn) {
        this.shareCopyBtn.addEventListener('click', function (e) {
          e.preventDefault();
          _this.copyToClipboard(_this.shareCopyBtn.dataset.url);
        });
      }
    }

    // ─────────────────────────────────────────────────────────────
    // Lightbox Methods
    // ─────────────────────────────────────────────────────────────
  }, {
    key: "openLightbox",
    value: function openLightbox(id) {
      var index = this.imageIds.indexOf(parseInt(id));
      if (index === -1 || !this.lightbox || !this.lightboxImg) return;
      this.currentIndex = index;
      var url = this.imageUrls[id];
      if (url) {
        this.lightboxImg.src = url;
        this.lightbox.style.display = 'block';
        // Trigger reflow for transition
        void this.lightbox.offsetWidth;
        this.lightbox.style.opacity = '1';
        document.body.style.overflow = 'hidden';
      }
    }
  }, {
    key: "closeLightbox",
    value: function closeLightbox() {
      var _this2 = this;
      if (!this.lightbox) return;
      this.lightbox.style.opacity = '0';
      setTimeout(function () {
        _this2.lightbox.style.display = 'none';
        document.body.style.overflow = '';
      }, 300);
    }
  }, {
    key: "showImage",
    value: function showImage(index) {
      if (!this.imageIds.length) return;

      // Wrap around
      if (index < 0) index = this.imageIds.length - 1;
      if (index >= this.imageIds.length) index = 0;
      this.currentIndex = index;
      var id = this.imageIds[index];
      var url = this.imageUrls[id];
      if (url && this.lightboxImg) {
        this.lightboxImg.src = url;
      }
    }
  }, {
    key: "handleKeydown",
    value: function handleKeydown(e) {
      if (!this.lightbox || this.lightbox.style.display !== 'block') return;
      if (e.key === 'Escape') {
        this.closeLightbox();
      } else if (e.key === 'ArrowLeft') {
        this.showImage(this.currentIndex - 1);
      } else if (e.key === 'ArrowRight') {
        this.showImage(this.currentIndex + 1);
      }
    }

    // ─────────────────────────────────────────────────────────────
    // Inquiry Form AJAX (Promise-based, NO async/await)
    // ─────────────────────────────────────────────────────────────
  }, {
    key: "handleInquirySubmit",
    value: function handleInquirySubmit(e) {
      var _this3 = this;
      e.preventDefault();
      var submitBtn = this.form.querySelector('button[type="submit"]');
      var originalBtnText = submitBtn.innerHTML;

      // ✅ SAFETY CHECK: Ensure ajaxUrl exists
      var ajaxUrl = this.form.dataset.ajaxUrl || '/wp-admin/admin-ajax.php';

      // Disable button and show loading state
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
      var formData = new FormData(this.form);

      // Add action and security fields explicitly (in case form doesn't have them)
      formData.append('action', 'awps_send_inquiry');
      // If you have a nonce field in the form, it will be included automatically
      // Otherwise, you may need to add it via JS (see Step 3)

      console.log('📤 Sending inquiry to:', ajaxUrl);
      console.log('FormData:', Object.fromEntries(formData));
      fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).then(function (response) {
        console.log('📥 Response status:', response.status);

        // ✅ Check if response is actually JSON before parsing
        var contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
          throw new Error('Expected JSON response, got: ' + contentType);
        }
        return response.json();
      }).then(function (data) {
        var _data$data;
        console.log('📥 Response data:', data);
        _this3.showResponse(data.success, ((_data$data = data.data) === null || _data$data === void 0 ? void 0 : _data$data.message) || 'Submission failed. Please try again.');
        if (data.success) {
          _this3.form.reset();
        }
      })["catch"](function (error) {
        console.error('❌ AJAX error:', error);
        _this3.showResponse(false, 'Network error. Please check your connection.');
      })["finally"](function () {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      });
    }
  }, {
    key: "showResponse",
    value: function showResponse(success, message) {
      var _this4 = this;
      if (!this.responseDiv) return;
      this.responseDiv.style.display = 'block';
      this.responseDiv.className = "notice ".concat(success ? 'success' : 'error');
      this.responseDiv.innerHTML = "\n            <div style=\"background:".concat(success ? '#d4edda' : '#f8d7da', ";\n                        color:").concat(success ? '#155724' : '#721c24', ";\n                        padding:12px;\n                        border-radius:4px;\n                        margin-top:1rem;\">\n                ").concat(message, "\n            </div>\n        ");

      // Auto-hide success messages after 5 seconds
      if (success) {
        setTimeout(function () {
          _this4.responseDiv.style.display = 'none';
        }, 5000);
      }
    }

    // ─────────────────────────────────────────────────────────────
    // Utility Methods
    // ─────────────────────────────────────────────────────────────
  }, {
    key: "copyToClipboard",
    value: function copyToClipboard(text) {
      var _this5 = this;
      if (!text) return;
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () {
          _this5.showCopyFeedback('Copied!');
        })["catch"](function () {
          _this5.fallbackCopy(text);
        });
      } else {
        this.fallbackCopy(text);
      }
    }
  }, {
    key: "fallbackCopy",
    value: function fallbackCopy(text) {
      // Fallback for older browsers
      var textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      try {
        document.execCommand('copy');
        this.showCopyFeedback('Copied!');
      } catch (err) {
        this.showCopyFeedback('Failed to copy');
      }
      document.body.removeChild(textarea);
    }
  }, {
    key: "showCopyFeedback",
    value: function showCopyFeedback(message) {
      var _this6 = this;
      if (!this.shareCopyBtn) return;
      var originalText = this.shareCopyBtn.innerHTML;
      this.shareCopyBtn.innerHTML = "<i class=\"fas fa-check\"></i> ".concat(message);
      setTimeout(function () {
        _this6.shareCopyBtn.innerHTML = originalText;
      }, 2000);
    }
  }]);
}();


/***/ }),

/***/ "./assets/src/scripts/modules/profile-public.js":
/*!******************************************************!*\
  !*** ./assets/src/scripts/modules/profile-public.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ ProfilePublic)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/classCallCheck */ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js");
/* harmony import */ var _babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @babel/runtime/helpers/createClass */ "./node_modules/@babel/runtime/helpers/esm/createClass.js");


/**
 * Public Supplier Profile Functionality
 * Handles inquiry form AJAX and share actions
 * 
 * @package awps
 * 
 * NOTE: Uses Promise chains instead of async/await to avoid regenerator-runtime dependency
 */
var ProfilePublic = /*#__PURE__*/function () {
  function ProfilePublic() {
    (0,_babel_runtime_helpers_classCallCheck__WEBPACK_IMPORTED_MODULE_0__["default"])(this, ProfilePublic);
    this.form = document.getElementById('exporter-inquiry-form');
    this.shareCopyBtn = document.querySelector('.share-copy');
    if (!this.form && !this.shareCopyBtn) return;
    this.init();
  }
  return (0,_babel_runtime_helpers_createClass__WEBPACK_IMPORTED_MODULE_1__["default"])(ProfilePublic, [{
    key: "init",
    value: function init() {
      if (this.form) this.bindInquiryForm();
      if (this.shareCopyBtn) this.bindCopyLink();
    }
  }, {
    key: "bindInquiryForm",
    value: function bindInquiryForm() {
      var _this = this;
      this.form.addEventListener('submit', function (e) {
        e.preventDefault();
        var submitBtn = _this.form.querySelector('button[type="submit"]');
        var originalBtnText = submitBtn.innerHTML;
        var resultDiv = document.getElementById('exporter-inquiry-result');

        // Disable button and show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
        if (resultDiv) {
          resultDiv.style.display = 'none';
          resultDiv.innerHTML = '';
        }
        var formData = new FormData(_this.form);

        // Use Promise chain instead of async/await
        fetch(_this.form.dataset.ajaxUrl, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).then(function (response) {
          return response.json();
        }).then(function (data) {
          if (resultDiv) {
            var _data$data, _data$data2;
            resultDiv.style.display = 'block';
            resultDiv.className = "exporter-inquiry-result notice ".concat(data.success ? 'success' : 'error');
            resultDiv.innerHTML = data.success ? "<i class=\"fa-solid fa-circle-check\"></i> ".concat(((_data$data = data.data) === null || _data$data === void 0 ? void 0 : _data$data.message) || 'Inquiry sent successfully!') : "<i class=\"fa-solid fa-circle-exclamation\"></i> ".concat(((_data$data2 = data.data) === null || _data$data2 === void 0 ? void 0 : _data$data2.message) || 'Failed to send inquiry. Please try again.');
            if (data.success) _this.form.reset();
          }
        })["catch"](function (error) {
          console.error('Inquiry AJAX error:', error);
          if (resultDiv) {
            resultDiv.style.display = 'block';
            resultDiv.className = 'exporter-inquiry-result notice error';
            resultDiv.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Network error. Please check your connection.';
          }
        })["finally"](function () {
          // Restore button state
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        });
      });
    }
  }, {
    key: "bindCopyLink",
    value: function bindCopyLink() {
      var _this2 = this;
      this.shareCopyBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var url = _this2.shareCopyBtn.dataset.url;
        if (!url) return;

        // Use Promise for clipboard API
        var copyPromise = navigator.clipboard ? navigator.clipboard.writeText(url) : Promise.resolve().then(function () {
          // Fallback for older browsers
          var textarea = document.createElement('textarea');
          textarea.value = url;
          textarea.style.position = 'fixed';
          textarea.style.left = '-9999px';
          document.body.appendChild(textarea);
          textarea.select();
          document.execCommand('copy');
          document.body.removeChild(textarea);
        });
        copyPromise.then(function () {
          // Show feedback
          var originalText = _this2.shareCopyBtn.innerHTML;
          _this2.shareCopyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
          setTimeout(function () {
            _this2.shareCopyBtn.innerHTML = originalText;
          }, 2000);
        })["catch"](function (err) {
          console.error('Copy failed:', err);
          alert('Could not copy link. Please copy manually.');
        });
      });
    }
  }]);
}();


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/classCallCheck.js":
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js ***!
  \*******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _classCallCheck)
/* harmony export */ });
function _classCallCheck(a, n) {
  if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function");
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/createClass.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/createClass.js ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _createClass)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperties(e, r) {
  for (var t = 0; t < r.length; t++) {
    var o = r[t];
    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__["default"])(o.key), o);
  }
}
function _createClass(e, r, t) {
  return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", {
    writable: !1
  }), e;
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js":
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPrimitive)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");

function toPrimitive(t, r) {
  if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js":
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js ***!
  \******************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPropertyKey)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPrimitive.js */ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js");


function toPropertyKey(t) {
  var i = (0,_toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__["default"])(t, "string");
  return "symbol" == (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i) ? i : i + "";
}


/***/ }),

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js":
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}


/***/ }),

/***/ "jquery":
/*!*************************!*\
  !*** external "jQuery" ***!
  \*************************/
/***/ ((module) => {

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
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/js/app": 0,
/******/ 			"css/style": 0,
/******/ 			"css/gutenberg": 0,
/******/ 			"css/admin": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkawps"] = self["webpackChunkawps"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["css/style","css/gutenberg","css/admin"], () => (__webpack_require__("./assets/src/scripts/app.js")))
/******/ 	__webpack_require__.O(undefined, ["css/style","css/gutenberg","css/admin"], () => (__webpack_require__("./assets/src/sass/style.scss")))
/******/ 	__webpack_require__.O(undefined, ["css/style","css/gutenberg","css/admin"], () => (__webpack_require__("./assets/src/sass/admin.scss")))
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["css/style","css/gutenberg","css/admin"], () => (__webpack_require__("./assets/src/sass/gutenberg.scss")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;