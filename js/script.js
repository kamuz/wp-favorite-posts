jQuery(document).ready( function($) {
    $('p.favorite-links > a').click(function(e){
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: kmzFavorites.url,
            data: {
                security: kmzFavorites.nonce,
                action: 'kmz_add_favorite',
                postId: kmzFavorites.postId,
            },
            // Show loader image
            beforeSend: function(){
                $('p.favorite-links > img').fadeIn();
            },
            // Hide loader image and link and show result
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