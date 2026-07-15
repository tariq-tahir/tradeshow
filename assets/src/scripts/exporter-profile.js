jQuery(document).ready(function ($) {
  function bindMediaUpload(buttonSelector, inputSelector, previewSelector) {
    $(buttonSelector).on('click', function (e) {
      e.preventDefault();

      const button = $(this);
      const customUploader = wp.media({
        title: 'Select Image',
        button: { text: 'Use this image' },
        multiple: false
      }).on('select', function () {
        const attachment = customUploader.state().get('selection').first().toJSON();
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
