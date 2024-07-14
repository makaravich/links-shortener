jQuery(document).ready(function ($) {
    (function (e) {

        // AJAX request to render form to adding links
        jQuery.ajax({
            type: "post",
            dataType: "json",
            url: LINKSH_AJAX.ajax_url,
            data: {
                action: "get_linksh_adding_form",
                nonce: LINKSH_AJAX.nonce
            },
            success: function (response) {
                $(response.data.formContent).insertBefore("body.post-type-" + LINKSH_AJAX.postType + " hr.wp-header-end");
            }
        });
    })();
});
