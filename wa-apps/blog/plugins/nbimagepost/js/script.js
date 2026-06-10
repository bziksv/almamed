jQuery(function($){
    $('#post-image-upload').click(function() {
        $('#nb-post-image-upload-dialog').waDialog({
            disableButtonsOnSubmit: true,
            onSubmit: function() {
                return false;
            }
        });
        return false;
    });
});