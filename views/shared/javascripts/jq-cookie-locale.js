jQuery(function($) {
    // header selection doesn't play well with English/Spanish homepage buttons, so splitting out into separate call.
    $('#header_bar li:last a').click(function(e) {
        e.preventDefault();

        var map_to_slug =  $('#map_to_slug').val();
        var cookie_info = cookie_information();

        if(Cookies.get('multi-loc') == null || Cookies.get('multi-loc') == 'en') {
            Cookies.set('multi-loc', cookie_info.alt_loc, cookie_info.cookie_settings);

            $("html").attr('lang', cookie_info.alt_loc);
        } else {
            Cookies.set('multi-loc', cookie_info.default_loc, cookie_info.cookie_settings);
        }

        navigate(map_to_slug);
    });

    $('#english, #spanish').click(function(e) {
        e.preventDefault();

        var target_browse_page = $(this).attr('id');
        var map_to_slug = $('#map_to_slug');

        if(target_browse_page === 'english') {
            map_to_slug.val('/explore');
        } else {
            map_to_slug.val('/explora');
        }

        var cookie_info = cookie_information();
        var slug_value = map_to_slug.val();

        if((target_browse_page === 'spanish' && Cookies.get('multi-loc') == 'en') || Cookies.get('multi-loc') == null) {
            Cookies.set('multi-loc', cookie_info.alt_loc, cookie_info.cookie_settings);
            $("html").attr('lang', cookie_info.alt_loc);

        } else if((target_browse_page === 'english' && Cookies.get('multi-loc') == 'en') || (target_browse_page === 'spanish' && Cookies.get('multi-loc') == 'es')) {
            // Do nothing, cookie already set correctly
        } else {
            Cookies.set('multi-loc', cookie_info.default_loc, cookie_info.cookie_settings);
            $("html").attr('lang', cookie_info.default_loc);
        }

        navigate(slug_value);
    });

    function cookie_information() {
        var cookie_settings =  {
            expires: 365,
            path: '/',
            domain: document.location.hostname
        };

        return {
            alt_loc: $('#alt_loc').val(),
            default_loc: $('#default_loc').val(),
            cookie_settings: cookie_settings
        }
    }

    function navigate(slug_value) {
        if (slug_value) {
            location.href = slug_value;
        } else {
            location.reload(true);
        }
    }

    /**
     * Translate search box text in header
     */
    var placeholder_text = $('#query');
    var search_button = $('form.navbar-form button');

    if($('href').attr('lang') == 'es'|| (Cookies.get('multi-loc') != null && Cookies.get('multi-loc') == 'es')) {
        placeholder_text.attr('placeholder', 'Busca en la colección');
        search_button.text('Búsqueda');
    } else {
        placeholder_text.attr('placeholder', 'Search the collection');
        search_button.text('Search');
    }
});