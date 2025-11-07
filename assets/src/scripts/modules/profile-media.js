export default class MediaUpload {
  constructor() {
    this.init();
    
  }

  init() {
    jQuery(document).ready(($) => {
      // Upload
      $(document).on("click", ".media-upload-btn", function (e) {
        e.preventDefault();
        const wrap = $(this).closest(".media-upload-wrap");
        const input = wrap.find("input[type=hidden]");
        const preview = wrap.find(".preview");
        const removeBtn = wrap.find(".media-remove-btn");

        let frame = wp.media({
          title: "Select or Upload Image",
          button: { text: "Use this image" },
          multiple: false,
        });

        frame.on("select", function () {
          const attachment = frame.state().get("selection").first().toJSON();
          input.val(attachment.url);
          preview.html('<img src="' + attachment.url + '" style="max-width:150px;height:auto;">');
          removeBtn.show();
        });

        frame.open();
      });

      // Remove
      $(document).on("click", ".media-remove-btn", function (e) {
        e.preventDefault();

        const wrap = $(this).closest(".media-upload-wrap");
        wrap.find("input[type=hidden]").val("");
        wrap.find(".preview").html("");
        $(this).hide();
      });
    });
  }
}
