function copyToClipboard(text) {
    // Modern browsers (HTTPS)
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('.share-copy');
            btn.textContent = 'Copied!';
            btn.classList.add('copied');
            setTimeout(() => {
                btn.textContent = 'Copy Link';
                btn.classList.remove('copied');
            }, 2000);
        }).catch(err => {
            fallbackCopy(text);
        });
    } else {
        // Fallback for HTTP or older browsers
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const input = document.createElement('input');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    input.setSelectionRange(0, 99999); // For mobile

    try {
        document.execCommand('copy');
        const btn = document.querySelector('.share-copy');
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Copy Link';
            btn.classList.remove('copied');
        }, 2000);
    } catch (err) {
        alert('Failed to copy. Please copy manually: ' + text);
    }

    document.body.removeChild(input);
}


document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('exporter-inquiry-form');
    const resultDiv = document.getElementById('exporter-inquiry-result');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        resultDiv.style.display = 'none';

        // Create FormData
        const formData = new FormData(form);

        // Send AJAX
        fetch('<?= admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = '📨 Send Inquiry';

            if (data.success) {
                resultDiv.innerHTML = '<div class="notice success">' + data.data.message + '</div>';
                resultDiv.style.display = 'block';
                form.reset();
            } else {
                resultDiv.innerHTML = '<div class="notice error">❌ ' + (data.data?.message || 'Submission failed') + '</div>';
                resultDiv.style.display = 'block';
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.textContent = '📨 Send Inquiry';
            resultDiv.innerHTML = '<div class="notice error">❌ Network error. Please try again.</div>';
            resultDiv.style.display = 'block';
            console.error('Error:', error);
        });
    });
});