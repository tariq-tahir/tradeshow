import $ from 'jquery';


// Universal Media Handler
document.addEventListener('click', function(e) {
    
    // 🔒 Ignore file input clicks directly
    if (e.target.tagName === 'INPUT' && e.target.type === 'file') {
        return;
    }

    const btn = e.target.closest('[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    
    // IDs for both inputs
    const galleryInput = document.getElementById('gallery-upload-input');
    const featuredInput = document.getElementById('featured-upload-input');

    // ==========================================
    // 1. HANDLE UPLOAD ACTIONS (Gallery & Featured)
    // ==========================================
    if (action === 'upload-featured' || action === 'upload-gallery') {
        
        // Pick the correct input based on action
        const activeInput = (action === 'upload-featured') ? featuredInput : galleryInput;
        
        activeInput.value = ''; // Reset to allow re-selection

        activeInput.onchange = function(event) {
          const rawFiles = Array.from(event.target.files);
            if (!rawFiles.length) return;

            // --- NEW: FILE SIZE VALIDATION ---
            const MAX_SIZE_MB = 2; // Set your limit here
            const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;
            
            // Filter out files that are too large
            const files = rawFiles.filter(file => {
                if (file.size > MAX_SIZE_BYTES) {
                    alert(`⚠️ File too large: "${file.name}"\nMaximum allowed size is ${MAX_SIZE_MB}MB.`);
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


            const galleryContainer = document.getElementById('gallery-preview');
            const featuredContainer = document.getElementById('featured-image-container');

            // --- GALLERY LOGIC ---
            if (action === 'upload-gallery') {
                files.forEach((file) => {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const item = document.createElement('div');
                        item.className = 'gallery-item';
                        item.style.cssText = "position:relative; display:inline-block; margin:5px;";
                        item.dataset.filename = file.name; 

                        item.innerHTML = `
                            <img src="${ev.target.result}" alt="${file.name}" 
                                 style="width:100px;height:100px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                            <button type="button" class="media-remove-btn" data-action="remove-gallery">×</button>
                            <input type="text" name="gallery_alt_new[]" placeholder="Alt text" style="width:100px; display:block; margin-top:5px; font-size:11px;">
                        `;
                        galleryContainer.appendChild(item);
                    };
                    reader.readAsDataURL(file);
                });
            }

            // --- FEATURED LOGIC ---
            else if (action === 'upload-featured') {
              const file = files[0];
              const reader = new FileReader();
              reader.onload = function(ev) {
                  // We only update the preview area
                  document.getElementById('featured-image-container').innerHTML = `
                      <div style="position:relative; display:inline-block;">
                          <img src="${ev.target.result}" style="max-width:200px;height:auto;border:1px solid #ddd;">
                          <button type="button" class="media-remove-btn" data-action="remove-featured" data-title="${file.name}">×</button>

                          <div style="margin-top:5px;">
                <input type="text" name="featured_image_alt" placeholder="Enter Alt Text..." style="width:100%; padding:4px; font-size:12px;">
            </div>
                      </div>
                  `;
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
        
        const item = btn.closest('.gallery-item');
        if (!item) return;

        const fileName = item.dataset.filename;
        const imageId = item.dataset.id; 

        if (confirm(`Are you sure you want to delete this image?`)) {
            // Sync the Gallery File Input
            const dt = new DataTransfer();
            const { files } = galleryInput;
            for (let i = 0; i < files.length; i++) {
                if (files[i].name !== fileName) dt.items.add(files[i]);
            }
            galleryInput.files = dt.files; 

            // Handle DB IDs
            if (imageId && !imageId.startsWith('new-')) {
                const removeInput = document.getElementById('remove_gallery_ids');
                if (removeInput) {
                    const existingValue = removeInput.value.trim();
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
          document.getElementById('featured-image-container').innerHTML = `
              <button type="button" class="media-upload-btn" data-action="upload-featured">Upload Image</button>
          `;

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
  document.querySelectorAll('.image-upload-wrap').forEach(wrap => {
    const input = wrap.querySelector('.image-input');
    const preview = wrap.querySelector('.preview');
    const removeBtn = wrap.querySelector('.remove-image');
    const hiddenRemove = wrap.querySelector('input[type="hidden"]');

    if (!input) return;

    input.addEventListener('change', e => {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = ev => {
        preview.innerHTML = `<img src="${ev.target.result}" style="max-width:200px;height:auto;">`;
        if (removeBtn) removeBtn.style.display = 'inline-block';
        if (hiddenRemove) hiddenRemove.value = '';
      };
      reader.readAsDataURL(file);
    });

    if (removeBtn) {
      removeBtn.addEventListener('click', () => {
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
  const updateCities = () => {
    const stateEl = document.getElementById('state');
    const citySelect = document.getElementById('city');
    if (!citySelect || !stateEl) return;

    const state = stateEl.value.trim();
    citySelect.innerHTML = '<option value="">— Select State First —</option>';

    if (state && citiesData[state]) {
      citiesData[state].forEach(city => {
        const option = document.createElement('option');
        option.value = city;
        option.textContent = city;
        citySelect.appendChild(option);
      });
    }

    if (savedCity) {
      for (let i = 0; i < citySelect.options.length; i++) {
        if (citySelect.options[i].value === savedCity) {
          citySelect.selectedIndex = i;
          break;
        }
      }
    }
  };

  const stateEl = document.getElementById('state');
  if (stateEl) {
    stateEl.addEventListener('change', updateCities);
    updateCities();
  }
}

// ---------------------------
// Generic Tag Widget (for autocomplete fields)
// ---------------------------
class TagWidget {
  constructor(opts) {
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

  getCurrentValues() {
    return Array.from(this.list.querySelectorAll('.tag')).map(el => {
      const dataValue = el.getAttribute('data-value');
      if (dataValue) return dataValue;
      const clone = el.cloneNode(true);
      clone.querySelectorAll('.remove-tag').forEach(btn => btn.remove());
      return clone.textContent.trim();
    });
  }

  updateHidden() {
    this.hidden.value = this.getCurrentValues().join(',');
  }

  addTag(valueOrObject) {
    let displayText, storageValue;

    if (typeof valueOrObject === 'object' && valueOrObject !== null) {
      // Handle { label, value } from AJAX
      displayText = String(valueOrObject.label || valueOrObject.value || '').trim();
      storageValue = String(valueOrObject.value || valueOrObject.label || '').trim();
    } else {
      // Fallback for string input (backward compatibility)
      displayText = storageValue = String(valueOrObject || '').trim();
    }

    if (!displayText || !storageValue) return;

    const currentStorageValues = Array.from(this.list.querySelectorAll('.tag')).map(el =>
      el.getAttribute('data-value')
    );

    if (currentStorageValues.includes(storageValue)) return;

    const tag = document.createElement('span');
    tag.className = 'tag';
    tag.setAttribute('data-value', storageValue); // ← stores ID for categories
    tag.innerHTML = `${displayText} <span class="remove-tag">×</span>`; // ← shows name

    tag.querySelector('.remove-tag').addEventListener('click', () => {
      tag.remove();
      this.updateHidden();
    });

    this.list.appendChild(tag);
    this.updateHidden();
  }

  initFromDOM() {
    const existingTags = this.list.querySelectorAll('.tag');
    if (existingTags.length > 0) {
      // Ensure every pre-existing tag has a proper data-value and remove listener
      existingTags.forEach(tag => {
        if (!tag.hasAttribute('data-value')) {
          const clone = tag.cloneNode(true);
          clone.querySelectorAll('.remove-tag').forEach(btn => btn.remove());
          tag.setAttribute('data-value', clone.textContent.trim());
        }

        // 🔹 CRITICAL: Attach remove listener if not already present
        const removeBtn = tag.querySelector('.remove-tag');
        if (removeBtn && !removeBtn._hasListener) {
          removeBtn._hasListener = true; // prevent duplicate listeners
          removeBtn.addEventListener('click', () => {
            tag.remove();
            this.updateHidden();
          });
        }
      });
      this.updateHidden();
    } else {
      const raw = (this.hidden.value || '').toString();
      const arr = raw
        .split(',')
        .map(t => t.trim())
        .filter(t => t.length > 0);
      this.list.innerHTML = '';
      this.hidden.value = '';
      arr.forEach(v => this.addTag(v));
    }
  }

  setupAutocomplete() {
    if (!$.ui?.autocomplete) return;

    if (this.sourceType === 'ajax' && this.ajaxAction) {
      const spinner = this.input.parentNode.querySelector('.awps-autocomplete-loader');

      $(this.input).autocomplete({
        source: (request, response) => {
          if (spinner) spinner.style.display = 'inline-block';

          $.ajax({
            url: window.ajaxurl || '/wp-admin/admin-ajax.php',
            dataType: 'json',
            data: {
              action: this.ajaxAction,
              term: request.term
            },
            success: data => response(data || []),
            error: () => response([]),
            complete: () => {
              if (spinner) spinner.style.display = 'none';
            }
          });
        },
        focus: () => false,
        select: (event, ui) => {
          // Pass the full item object so addTag can extract label + value
          this.addTag(ui.item);
          this.input.value = '';
          return false;
        }
      });
    } else if (this.sourceType === 'array' && Array.isArray(this.sourceArray)) {
      $(this.input).autocomplete({
        source: (request, response) => {
          const term = (request.term || '').toLowerCase();
          const results = this.sourceArray.filter(item =>
            String(item).toLowerCase().includes(term)
          );
          response(results);
        },
        focus: () => false,
        select: (event, ui) => {
          const val = (ui?.item && (ui.item.value || ui.item.label)) || ui.item || '';
          this.addTag(val);
          this.input.value = '';
          return false;
        }
      });
    }
  }

  setupManualEntry() {
    if (!this.allowManualEntry) return;
    this.input.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const v = this.input.value.trim();
        if (v) this.addTag(v);
        this.input.value = '';
      }
    });
  }
}

// ---------------------------
// Custom Tags (for FOB Ports - free text)
// ---------------------------
function initCustomTags() {
  document.querySelectorAll('.custom-tags-input').forEach(container => {
    const input = container.querySelector('.tag-input');
    const list = container.querySelector('.tag-list');
    const hidden = container.querySelector('input[type="hidden"]');

    if (!input || !list || !hidden) return;

    function getTagText(tagEl) {
      const clone = tagEl.cloneNode(true);
      clone.querySelectorAll('.remove-tag').forEach(btn => btn.remove());
      return clone.textContent.trim();
    }

    function syncHidden() {
      const tags = Array.from(list.querySelectorAll('.tag')).map(getTagText).filter(t => t);
      hidden.value = tags.join(',');
    }

    function addTag(text) {
      const cleanText = text.trim();
      if (!cleanText) return;
      const existing = Array.from(list.querySelectorAll('.tag')).map(getTagText);
      if (existing.includes(cleanText)) return;
      const tag = document.createElement('span');
      tag.className = 'tag';
      tag.innerHTML = `${cleanText} <span class="remove-tag">×</span>`;
      tag.querySelector('.remove-tag').addEventListener('click', () => {
        tag.remove();
        syncHidden();
      });
      list.appendChild(tag);
      syncHidden();
    }

    // Attach listeners to pre-rendered tags
    list.querySelectorAll('.tag').forEach(tag => {
      if (tag.dataset.initialized) return;
      const removeBtn = tag.querySelector('.remove-tag');
      if (removeBtn) {
        removeBtn.addEventListener('click', () => {
          tag.remove();
          syncHidden();
        });
      }
      tag.dataset.initialized = 'true';
    });

    syncHidden();

    input.addEventListener('keydown', e => {
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
  let cropper = null;
  let activeWrap = null;
  const modal = document.getElementById('cropper-modal');
  const cropperImage = document.getElementById('cropper-image');
  const btnCancel = document.getElementById('cropper-cancel');
  const btnSave = document.getElementById('cropper-save');

  if (!modal || !cropperImage) return;

  document.querySelectorAll('.image-upload-wrap .image-input').forEach(input => {
    input.addEventListener('change', e => {
      const file = e.target.files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = ev => {
        cropperImage.src = ev.target.result;
        modal.style.display = 'flex';
        activeWrap = input.closest('.image-upload-wrap');

        if (cropper) {
          try { cropper.destroy(); } catch {}
        }

        const ratio = activeWrap?.dataset?.field === 'company_logo' ? 1 : 4 / 1;
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

  btnCancel?.addEventListener('click', () => {
    if (cropper) try { cropper.destroy(); } catch {}
    modal.style.display = 'none';
  });

  btnSave?.addEventListener('click', () => {
    if (!cropper || !activeWrap) return;
    const width = activeWrap.dataset.field === 'company_logo' ? 300 : 1200;
    const height = activeWrap.dataset.field === 'company_logo' ? 300 : 300;
    const canvas = cropper.getCroppedCanvas({ width, height });
    canvas.toBlob(blob => {
      const preview = activeWrap.querySelector('.preview');
      preview.innerHTML = `<img src="${canvas.toDataURL()}" style="max-width:100%;height:auto;">`;

      const fileInput = activeWrap.querySelector('.image-input');
      const dt = new DataTransfer();
      dt.items.add(new File([blob], 'cropped.png', { type: 'image/png' }));
      fileInput.files = dt.files;

      const removeBtn = activeWrap.querySelector('.remove-image');
      if (removeBtn) removeBtn.style.display = 'inline-block';

      modal.style.display = 'none';
      try { cropper.destroy(); } catch {}
    }, 'image/png');
  });
}

// ---------------------------
// Main Class
// ---------------------------
export default class FrontendAdmin {
  constructor() {
    $(() => {
      const {
        stateCities,
        savedCity,
        countries,
        languages,
        incoterms,
        packaging,
        shipping,
        paymentTerms,
        samplePolicies,
        keyBenefits,
        qualityControl
      } = window.wpExporterData || {};

      // Always initialize
      initImageUploads();
      initCustomTags(); // for FOB Ports (free-text)
      initImageCropper();

      // 🔹 NEW: Form Validation Logic
      // This checks if the product categories hidden input is empty on submit
      // Inside your FrontendAdmin constructor, within the $(() => { ... }) block:

// --- ADD THIS VALIDATION BLOCK ---
      $(document).on('submit', '#awps-product-form', function(e) {

        const hiddenField = document.querySelector('#_certifications_hidden');
    const rawValue = hiddenField ? hiddenField.value : 'NOT FOUND';
    console.log('🔍 Certifications hidden field value:', rawValue);
    
    // Also log all hidden inputs with name="_certifications" to check for duplicates
    const allHiddenByName = document.querySelectorAll('input[name="_certifications"]');
    console.log('🔍 All inputs with name="_certifications":', allHiddenByName.length, allHiddenByName);


          const $hiddenInput = $('#product_categories');
          const categories = $hiddenInput.val() ? $hiddenInput.val().trim() : "";

          // Check if value is truly empty
          if (categories === "" || categories.length === 0) {
              e.preventDefault(); // Stop the form from submitting
              e.stopImmediatePropagation();
              
              // 1. Show the alert
              alert('⚠️ Please select at least one Product Category before saving.');

              // 2. Visual feedback (Highlighting the category box)
              const $container = $('.awps-tag-container').first(); // Grab the categories container
              $container.css({
                  'border': '2px solid #dc3232',
                  'padding': '10px',
                  'border-radius': '5px',
                  'background': '#fff5f5'
              });

              // 3. Scroll to the field
              $('html, body').animate({
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
  }
}














/**
 * AWPS INDEPENDENT IMAGE HANDLER (Final Version)
 * Specifically handles .awps-unique-remove and .awps-unique-input
 */
(function() {
    const slugify = (t) => t.toString().toLowerCase().trim().replace(/\s+/g, '-').replace(/[^\w\-]+/g, '').replace(/\-\-+/g, '-');

    // 1. REMOVE LOGIC (Safe Version)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.awps-iso-remove-btn');
        if (!btn) return; // Exit if the click wasn't on our remove button

        e.preventDefault();
        const wrap = btn.closest('.awps-iso-wrap');
        if (!wrap) return; // SAFETY CHECK: Stop if no wrapper found

        const fieldName = wrap.dataset.field;
        const previewBox = wrap.querySelector('.awps-iso-preview-box');
        const previewCont = wrap.querySelector('.awps-iso-preview-cont');
        const controls = wrap.querySelector('.awps-iso-controls');
        const hiddenRemove = wrap.querySelector('input[name="remove_' + fieldName + '"]');
        const fileInput = wrap.querySelector('.awps-iso-input');

        if (previewBox) previewBox.innerHTML = '';
        if (previewCont) previewCont.setAttribute('style', 'display: none !important');
        if (controls) controls.setAttribute('style', 'display: block !important');
        if (fileInput) fileInput.value = '';
        if (hiddenRemove) hiddenRemove.value = '1';
        
        console.log("AWPS ISO: Image removed safely.");
    }, true);

    // 2. UPLOAD & CROP LOGIC (Safe Version)
    document.addEventListener('change', function(e) {
        if (!e.target.classList.contains('awps-iso-input')) return;

        const input = e.target;
        const file = input.files[0];
        if (!file) return;

        const wrap = input.closest('.awps-iso-wrap');
        if (!wrap) return; // SAFETY CHECK: Stop if no wrapper found

        const fieldName = wrap.dataset.field;
        const modal = document.getElementById('cropper-modal');
        const cropperImg = document.getElementById('cropper-image');

        const reader = new FileReader();
        reader.onload = (ev) => {
            if (!cropperImg || !modal) return;
            cropperImg.src = ev.target.result;
            modal.style.display = 'flex';
            
            if (window.isoCropper) window.isoCropper.destroy();
            
            const ratio = fieldName === 'company_logo' ? 1 : 4 / 1;
            window.isoCropper = new Cropper(cropperImg, { aspectRatio: ratio, viewMode: 1 });

            const saveBtn = document.getElementById('cropper-save');
            const newBtn = saveBtn.cloneNode(true);
            saveBtn.parentNode.replaceChild(newBtn, saveBtn);

            newBtn.addEventListener('click', () => {
                const canvas = window.isoCropper.getCroppedCanvas({ width: 800, height: 800 });
                canvas.toBlob(blob => {
                    const dt = new DataTransfer();
                    const companyName = document.querySelector('input[name="company_name"]')?.value || 'company';
                    const fileName = `${slugify(companyName)}-${fieldName}.png`;
                    
                    dt.items.add(new File([blob], fileName, { type: 'image/png' }));
                    input.files = dt.files;

                    const pBox = wrap.querySelector('.awps-iso-preview-box');
                    const pCont = wrap.querySelector('.awps-iso-preview-cont');
                    const ctrl = wrap.querySelector('.awps-iso-controls');

                    if (pBox) {
                      // Determine the width based on the field name
                      // If it's the banner, use 400px, otherwise use 150px (for logo)
                      const imgWidth = (fieldName === 'banner_image') ? '400px' : '150px';
                        pBox.innerHTML = `
                            <img src="${canvas.toDataURL()}" style="max-width:${imgWidth}; width:100%; height:auto;">
                            <input type="text" name="${fieldName}_alt" placeholder="Alt text..." style="width:100%; font-size:12px; padding:4px;">
                            <button type="button" class="awps-iso-remove-btn">×</button>
                        `;
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