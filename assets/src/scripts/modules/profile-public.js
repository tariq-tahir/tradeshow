/**
 * Public Supplier Profile Functionality
 * Handles inquiry form AJAX and share actions
 * 
 * @package awps
 * 
 * NOTE: Uses Promise chains instead of async/await to avoid regenerator-runtime dependency
 */

export default class ProfilePublic {
    constructor() {
        this.form = document.getElementById('exporter-inquiry-form');
        this.shareCopyBtn = document.querySelector('.share-copy');
        
        if (!this.form && !this.shareCopyBtn) return;
        
        this.init();
    }

    init() {
        if (this.form) this.bindInquiryForm();
        if (this.shareCopyBtn) this.bindCopyLink();
    }

    bindInquiryForm() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const submitBtn = this.form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            const resultDiv = document.getElementById('exporter-inquiry-result');
            
            // Disable button and show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            if (resultDiv) {
                resultDiv.style.display = 'none';
                resultDiv.innerHTML = '';
            }

            const formData = new FormData(this.form);
            
            // Use Promise chain instead of async/await
            fetch(this.form.dataset.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (resultDiv) {
                    resultDiv.style.display = 'block';
                    resultDiv.className = `exporter-inquiry-result notice ${data.success ? 'success' : 'error'}`;
                    resultDiv.innerHTML = data.success 
                        ? `<i class="fa-solid fa-circle-check"></i> ${data.data?.message || 'Inquiry sent successfully!'}`
                        : `<i class="fa-solid fa-circle-exclamation"></i> ${data.data?.message || 'Failed to send inquiry. Please try again.'}`;
                    
                    if (data.success) this.form.reset();
                }
            })
            .catch(error => {
                console.error('Inquiry AJAX error:', error);
                if (resultDiv) {
                    resultDiv.style.display = 'block';
                    resultDiv.className = 'exporter-inquiry-result notice error';
                    resultDiv.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Network error. Please check your connection.';
                }
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    }

    bindCopyLink() {
        this.shareCopyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const url = this.shareCopyBtn.dataset.url;
            if (!url) return;

            // Use Promise for clipboard API
            const copyPromise = navigator.clipboard 
                ? navigator.clipboard.writeText(url)
                : Promise.resolve().then(() => {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = url;
                    textarea.style.position = 'fixed';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                });

            copyPromise
                .then(() => {
                    // Show feedback
                    const originalText = this.shareCopyBtn.innerHTML;
                    this.shareCopyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                    setTimeout(() => {
                        this.shareCopyBtn.innerHTML = originalText;
                    }, 2000);
                })
                .catch(err => {
                    console.error('Copy failed:', err);
                    alert('Could not copy link. Please copy manually.');
                });
        });
    }
}