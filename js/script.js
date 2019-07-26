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
                postId: kmzFavorites.postId
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
                    if(action == 'del'){
                        $('.kmz-widget-favorite-posts').find('.favorite-post-' + kmzFavorites.postId).remove();
                    }
                    if(action == 'add'){
                    //<li class=\"favorite-post-' + kmzFavorites.postId + '\"><a href=\" + kmzFavorites.url + \" target="_blank">' . get_the_title($favorite) . '</a><span><a href="#" data-post="' . $favorite . '" class="dashicons dashicons-no"></a></span><img src="' . $img_loader_src . '" alt="loader" class="loader-gif hidden"> </li>'
                        $('.kmz-widget-favorite-posts').prepend('<li class="favorite-post-' + kmzFavorites.postId + '" ><a href="' + kmzFavorites.postUrl + '">' + kmzFavorites.postTitle + '</a></li>');
                        console.log(kmzFavorites);
                    }
                });
            },
            error: function(){
                alert("Error AJAX");
            }
        });
    });
});