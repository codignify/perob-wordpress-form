jQuery(document).ready(function($) {
    function save_utm_data() {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i,
            utm_data = {};

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0].startsWith('utm_')) {
                utm_data[sParameterName[0]] = sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
        if (Object.keys(utm_data).length > 0) {
            localStorage.setItem('utm_data', JSON.stringify(utm_data));
        }
        return utm_data;
    };
    save_utm_data();
    if (perob_vars.use_ajax == 1) {
        var $perobform = $(perob_vars.form);
        $perobform.submit(function(e) {
            e.preventDefault();
            var data = $perobform.serialize();
            var utm_data = JSON.parse(localStorage.getItem('utm_data'));
            if (utm_data) {
                var queryString = Object.keys(utm_data).map(key => key + '=' + utm_data[key]).join('&');
                data += '&' + queryString;
            }
            $.ajax({
                url: perob_vars.url,
                data: data,
                method: 'post',
                dataType: 'json',
                success: function(response) {
                    var mesg_cls = response.success == true ? 'success' : 'error';
                    $perobform.find('.message').removeClass('success error').addClass(mesg_cls).html(response.message);
                }
            });
        });
    }
});
