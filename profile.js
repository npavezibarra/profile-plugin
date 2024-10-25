jQuery(document).ready(function($) {
    $('#mis-preferencias-form').on('submit', function(e) {
        e.preventDefault();
        var political_preference = $('#political_preference').val();
        var is_public = $('#is_public').is(':checked') ? 'yes' : 'no';

        $.ajax({
            url: ajaxurl, // Definido previamente con wp_localize_script
            method: 'POST',
            data: {
                action: 'guardar_preferencias',
                political_preference: political_preference,
                is_public: is_public
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data); // Preferencias guardadas
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });
});
