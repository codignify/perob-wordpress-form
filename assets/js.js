jQuery(document).ready(function($) {
    if (perob_vars.use_ajax == 1) {
        var $perobform = $(perob_vars.form);
        $perobform.submit(function(e) {
            e.preventDefault();
            var data = $perobform.serialize();
            $.ajax({
                url: perob_vars.url,
                data: data,
                method: 'post',
                dataType: 'json',
                success: function (response) {
                    var mesg_cls = response.success == true ? 'success' : 'error';
                    $perobform.find('p.message').removeClass('success error').addClass(mesg_cls).html(response.message);
                }
            });
        });
    }
});
