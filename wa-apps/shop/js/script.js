
if (typeof ($.History) != "undefined") {
    $.History.bind(function () {
            var hash;
            hash = window.location.hash;
            var action = hash.replace(/^[^\/]+\//, '').replace(/\/.*$/, '');
            var id = hash.replace(/^([^\/]+\/){1,2}/, '').replace(/\/$/, '');
            var $content = $('#nb-popup-form');

            var id_form = "";
            if(id){
                id_form = '&id_form='+id;
            }
            if (action) {
                $content.load('?plugin=nbpopupform'+ id_form +'&action=' + action, function (responseText, textStatus, XMLHttpRequest) {
                    if (!$content.find('>div.block.double-padded').length) {
                        $content.wrapInner('<div class="block double-padded"></div>');
                    }
                });
            }
        });
    };


;
