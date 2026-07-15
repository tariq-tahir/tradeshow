jQuery(document).ready(function ($) {
    const savedCity = awpsAdmin.savedCity || "";
    const ajaxUrl = awpsAdmin.ajaxUrl;
    const cities = awpsAdmin.cities || {};
    const countries = awpsAdmin.countries || [];
    const languages = awpsAdmin.languages || [];
    const paymentTerms = awpsAdmin.paymentTerms || [];
    const packagingOptions = awpsAdmin.packaging || [];

    // ==========================
    // UTILITY: Add a tag to a specific tag list container
    // ==========================
    function addTagToContainer($list, value) {
        if (!value) return;

        // Escape double quotes to safely use in attribute selector
        const escapedValue = value.replace(/"/g, '\\"');
        if ($list.find(`[data-value="${escapedValue}"]`).length > 0) {
            return; // Prevent duplicates
        }

        const $tag = $(`<span class="tag" data-value="${value}">${value} <span class="remove-tag">×</span></span>`);
        $list.append($tag);

        // Update the hidden input in the same container
        const $hidden = $list.siblings('input[type="hidden"]');
        const tags = [];
        $list.find(".tag").each(function () {
            tags.push($(this).data("value"));
        });
        $hidden.val(tags.join(","));
    }

    // ==========================
    // MEDIA UPLOADER
    // ==========================
    $(document).on("click", ".media-upload-btn", function (e) {
        e.preventDefault();
        const wrap = $(this).closest(".media-upload-wrap");
        const field = wrap.data("field");
        const input = wrap.find('input[name="' + field + '"]');
        const preview = wrap.find(".preview");
        const removeBtn = wrap.find(".media-remove-btn");

        const frame = wp.media({
            title: "Select or Upload Image",
            button: { text: "Use this image" },
            multiple: false,
        });

        frame.on("select", function () {
            const attachment = frame.state().get("selection").first().toJSON();
            input.val(attachment.url);
            preview.html(
                '<img src="' +
                    attachment.url +
                    '" style="max-width:150px;height:auto;">'
            );
            removeBtn.show();
        });

        frame.open();
    });

    $(document).on("click", ".media-remove-btn", function (e) {
        e.preventDefault();
        const wrap = $(this).closest(".media-upload-wrap");
        wrap.find('input[type="hidden"]').val("");
        wrap.find(".preview").empty();
        $(this).hide();
    });

    // ==========================
    // STATE → CITY DYNAMIC SELECT
    // ==========================
    function updateCities() {
        const state = $("#state").val();
        const citySelect = $("#city");
        citySelect.empty();

        if (state && cities[state]) {
            cities[state].forEach((city) => {
                const isSelected = city === savedCity;
                const option = new Option(city, city, false, isSelected);
                citySelect.append(option);
            });
        } else {
            citySelect.append(new Option("— Select State First —", ""));
        }
    }

    $("#state").on("change", updateCities);
    updateCities(); // Initialize on load

    // ==========================
    // INITIALIZE TAG INPUTS
    // ==========================
    $(".custom-tags-input").each(function () {
        const $container = $(this);
        const $input = $container.find(".tag-input");
        const $list = $container.find(".tag-list");
        const $hidden = $container.find("input[type='hidden']");

        // Restore existing tags from hidden input
        const existing = ($hidden.val() || "")
            .split(",")
            .map(t => t.trim())
            .filter(Boolean);

        existing.forEach(value => {
            addTagToContainer($list, value);
        });

        // Handle tag removal (delegated event)
        $list.on("click", ".remove-tag", function () {
            $(this).parent().remove();
            // Re-sync hidden input
            const tags = [];
            $list.find(".tag").each(function () {
                tags.push($(this).data("value"));
            });
            $hidden.val(tags.join(","));
        });

        // Handle manual entry via Enter or comma
        $input.on("keydown", function (e) {
            if (e.key === "Enter" || e.key === ",") {
                e.preventDefault();
                const val = $input.val().trim();
                if (val) {
                    addTagToContainer($list, val);
                    $input.val("");
                }
            }
        });
    });

    // ==========================
    // AUTOCOMPLETE SETUP HELPER
    // ==========================
    function setupAutocomplete(selector, source) {
        $(selector).autocomplete({
            source: source,
            select: function (e, ui) {
                const $container = $(this).closest(".custom-tags-input");
                const $list = $container.find(".tag-list");
                addTagToContainer($list, ui.item.value);
                $(this).val("");
                return false;
            }
        });
    }

    // ==========================
    // STATIC AUTOCOMPLETE FIELDS
    // ==========================
    setupAutocomplete("#exports-to-input", countries);
    setupAutocomplete("#languages-input", languages);
    setupAutocomplete("#payment-terms-input", paymentTerms);
    setupAutocomplete("#packaging-input", packagingOptions);

    // ==========================
    // DYNAMIC (AJAX) AUTOCOMPLETE: Certifications
    // ==========================
    $("#certifications-input").autocomplete({
        source: function (request, response) {
            $.post(ajaxUrl, {
                action: "get_certifications",
                term: request.term
            })
            .done(response)
            .fail(() => response([]));
        },
        select: function (e, ui) {
            const $container = $(this).closest(".custom-tags-input");
            const $list = $container.find(".tag-list");
            addTagToContainer($list, ui.item.value);
            $(this).val("");
            return false;
        }
    });
});