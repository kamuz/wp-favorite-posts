jQuery(document).ready( function($) {
    $('p.favorite-links > a').click(function(e){
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: kmzFavorites.url,
            data: {
                test: 'Test data',
                action: 'kmz_add_favorite',
            },
            beforeSend: function(){
                $('p.favorite-links > img').fadeIn();
            },
            success: function(res){
                console.log(res);
                $('p.favorite-links > img').fadeOut(300, function(){
                    $('p.favorite-links > a').hide();
                    $('p.favorite-links').html(res);
                });
            },
            error: function(){
                alert("Error AJAX");
            }
        });
    });
});