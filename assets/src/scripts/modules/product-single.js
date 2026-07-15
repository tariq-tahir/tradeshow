/**
 * Product Single Page Functionality
 * Handles lightbox gallery, inquiry form AJAX, and share actions
 * 
 * NOTE: Uses Promise chains instead of async/await to avoid regenerator-runtime dependency
 */

export default class ProductSingle {
    constructor() {
        this.lightbox = null;
        this.lightboxImg = null;
        this.currentIndex = 0;
        this.imageIds = [];
        this.imageUrls = {};
        
        this.init();
    }

    init() {
        // Initialize only if we're on a product page
        if (!document.querySelector('.awps-product-container')) return;
        
        this.cacheElements();
        this.parseImageData();
        this.bindEvents();
    }

    cacheElements() {
        this.lightbox = document.getElementById('awps-lightbox');
        this.lightboxImg = document.getElementById('lightbox-image');
        this.closeBtn = document.getElementById('lightbox-close');
        this.prevBtn = document.getElementById('lightbox-prev');
        this.nextBtn = document.getElementById('lightbox-next');
        this.form = document.getElementById('awps-inquiry-form');
        this.responseDiv = document.getElementById('inquiry-response');
        this.shareCopyBtn = document.querySelector('.share-copy');
    }

    parseImageData() {
        // Read image data from data attributes on the container
        const container = document.querySelector('.awps-product-container');
        if (!container) return;
        
        try {
            this.imageUrls = JSON.parse(container.dataset.imageUrls || '{}');
            this.imageIds = JSON.parse(container.dataset.imageIds || '[]');
        } catch (e) {
            console.error('Failed to parse product image ', e);
        }
    }

    bindEvents() {
        // Lightbox: thumbnail clicks
        document.querySelectorAll('.gallery-thumb').forEach(thumb => {
            thumb.addEventListener('click', (e) => {
                e.preventDefault();
                this.openLightbox(thumb.dataset.id);
            });
        });

        // Lightbox: controls
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.closeLightbox());
        }
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.showImage(this.currentIndex - 1));
        }
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.showImage(this.currentIndex + 1));
        }

        // Lightbox: keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeydown(e));

        // Lightbox: close on backdrop click
        if (this.lightbox) {
            this.lightbox.addEventListener('click', (e) => {
                if (e.target === this.lightbox) this.closeLightbox();
            });
        }

        // Inquiry form AJAX
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleInquirySubmit(e));
        }

        // Share: copy link
        if (this.shareCopyBtn) {
            this.shareCopyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.copyToClipboard(this.shareCopyBtn.dataset.url);
            });
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Lightbox Methods
    // ─────────────────────────────────────────────────────────────

    openLightbox(id) {
        const index = this.imageIds.indexOf(parseInt(id));
        if (index === -1 || !this.lightbox || !this.lightboxImg) return;
        
        this.currentIndex = index;
        const url = this.imageUrls[id];
        
        if (url) {
            this.lightboxImg.src = url;
            this.lightbox.style.display = 'block';
            // Trigger reflow for transition
            void this.lightbox.offsetWidth;
            this.lightbox.style.opacity = '1';
            document.body.style.overflow = 'hidden';
        }
    }

    closeLightbox() {
        if (!this.lightbox) return;
        
        this.lightbox.style.opacity = '0';
        setTimeout(() => {
            this.lightbox.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }

    showImage(index) {
        if (!this.imageIds.length) return;
        
        // Wrap around
        if (index < 0) index = this.imageIds.length - 1;
        if (index >= this.imageIds.length) index = 0;
        
        this.currentIndex = index;
        const id = this.imageIds[index];
        const url = this.imageUrls[id];
        
        if (url && this.lightboxImg) {
            this.lightboxImg.src = url;
        }
    }

    handleKeydown(e) {
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

    handleInquirySubmit(e) {
        e.preventDefault();
        
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // ✅ SAFETY CHECK: Ensure ajaxUrl exists
        const ajaxUrl = this.form.dataset.ajaxUrl || '/wp-admin/admin-ajax.php';
        
        // Disable button and show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        const formData = new FormData(this.form);
        
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
        })
        .then(response => {
            console.log('📥 Response status:', response.status);
            
            // ✅ Check if response is actually JSON before parsing
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Expected JSON response, got: ' + contentType);
            }
            
            return response.json();
        })
        .then(data => {
            console.log('📥 Response data:', data);
            this.showResponse(data.success, data.data?.message || 'Submission failed. Please try again.');
            if (data.success) {
                this.form.reset();
            }
        })
        .catch(error => {
            console.error('❌ AJAX error:', error);
            this.showResponse(false, 'Network error. Please check your connection.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    }

    showResponse(success, message) {
        if (!this.responseDiv) return;
        
        this.responseDiv.style.display = 'block';
        this.responseDiv.className = `notice ${success ? 'success' : 'error'}`;
        this.responseDiv.innerHTML = `
            <div style="background:${success ? '#d4edda' : '#f8d7da'};
                        color:${success ? '#155724' : '#721c24'};
                        padding:12px;
                        border-radius:4px;
                        margin-top:1rem;">
                ${message}
            </div>
        `;
        
        // Auto-hide success messages after 5 seconds
        if (success) {
            setTimeout(() => {
                this.responseDiv.style.display = 'none';
            }, 5000);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Utility Methods
    // ─────────────────────────────────────────────────────────────

    copyToClipboard(text) {
        if (!text) return;
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text)
                .then(() => {
                    this.showCopyFeedback('Copied!');
                })
                .catch(() => {
                    this.fallbackCopy(text);
                });
        } else {
            this.fallbackCopy(text);
        }
    }

    fallbackCopy(text) {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
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

    showCopyFeedback(message) {
        if (!this.shareCopyBtn) return;
        
        const originalText = this.shareCopyBtn.innerHTML;
        this.shareCopyBtn.innerHTML = `<i class="fas fa-check"></i> ${message}`;
        
        setTimeout(() => {
            this.shareCopyBtn.innerHTML = originalText;
        }, 2000);
    }
}