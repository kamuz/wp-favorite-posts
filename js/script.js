jQuery(document).ready( function($) {
    /**
     * Add/remove post to/from favorites
     */
    $('p.favorite-links > a').click(function(e){
        e.preventDefault();
        // Get action name from data-action attribute
        var action = $(this).data('action');
        // Setup AJAX
        $.ajax({
            type: 'POST',
            url: kmzFavorites.url,
            data: {
                security: kmzFavorites.nonce,
                action: 'kmz_' + action + '_favorite',
                postId: kmzFavorites.postId,
            },
            // Show loader image
            beforeSend: function(){
                $('p.favorite-links > img').fadeIn();
            },
            // Hide loader image and link and show result
            success: function(res){
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