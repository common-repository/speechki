jQuery( document ).ready(function() {

    jQuery( "#ds_speechki_options" ).on( "click", "#get_api_key", function(e) {
        e.preventDefault();
        let login = jQuery('#ds_speechki_options #email').val(),
            pass = jQuery('#ds_speechki_options #pass').val(),
            error = false;

        if(login == '') {
            error = 'Заполните поле "Email"';
        } else {
            if(pass == '') {
                error = 'Заполните поле "Пароль"';
            }
        }

        if (error) {
            alert(error);
        } else {
            jQuery.ajax({
                dataType: 'json',
                url:ajax_object.ajax_url,
                data: {
                    '_ajax_nonce': ajax_object.nonce_for_getting_tokken,
                    'login': login,
                    'pass': pass,
                    'action': 'speeckiKvark_getTemporaryToken'
                },
                type:'POST',
                success:function(result){
                    if(result.error) {
                        alert(result.error_text);
                    } else {
                        jQuery('#ds_speechki_options').submit();
                    }
                }
            });
        }
    });

    jQuery( "#ds_speechki_save_options" ).on( "click", "#save_settings_api", function(e) {
        e.preventDefault();
        let post_types = jQuery('#ds_speechki_save_options #select_post_types').val(),
            // adapted_text_field = jQuery('#ds_speechki_save_options #adapted_text_field').val(),
            player_location = jQuery('#ds_speechki_save_options #player_location').val(),
            error = false;


        if(post_types == '') {
            error = 'Выберите тип поста';
        }

        if (error) {
            alert(error);
        } else {
            jQuery.ajax({
                dataType: 'json',
                url:ajax_object.ajax_url,
                data: {
                    '_ajax_nonce': ajax_object.nonce_for_saving_settings,
                    'post_types': post_types,
                    'player_location': player_location,
                    'action': 'speeckiKvark_SaveSettings'
                },
                type:'POST',
                success:function(result){
                    console.log(result);
                    if(result = 'ok') {
                        alert('Сохранено');
                    }
                }
            });
        }
    });

});
