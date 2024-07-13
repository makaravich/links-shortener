jQuery(document).ready(function ($) {
    (function (e) {

        data = {

        }
        jQuery.ajax({
            type: "post",
            //dataType: "json",
            url: LINKSH_AJAX.ajax_url,
            data: {
                action: "get_linksh_adding_form",
                nonce: LINKSH_AJAX.nonce
            },
            success: function (response) {
                console.log(response);

                $(response.data.formContent).insertBefore("body.post-type-links_shrt hr.wp-header-end");
            }
        });

        //$("<div>***</div>").insertBefore("body.post-type-links_shrt hr.wp-header-end");


    })();
});
