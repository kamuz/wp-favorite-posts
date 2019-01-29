jQuery(document).ready(function ($) {

    /**
     * Delete current post
     */
    $('#kmz_favorites_dashboard .dashicons.dashicons-no').click(function (e) {
        e.preventDefault();
        if (!confirm("Do you really want to delete this post?")) return;
        var postId = $(this).data('post'),
            parent = $(this).parent(),
            loader = parent.next(),
            li = $(this).closest('li');
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                security: kmzFavorites.nonce,
                postId: postId,
                action: 'kmz_del_favorite'
            },
            beforeSend: function(){
                parent.fadeOut(300, function() {
                    loader.fadeIn();
                });
            },
            success: function(res){
                loader.fadeOut(300, function(){
                    li.html(res);
                });
            },
            error: function(){
                alert("Error AJAX");
            }
        });
    });

    /**
     * Delete all posts
     */
    $('.kmz-favorites-del-all button').click(function(e){
        e.preventDefault();
        if (!confirm("Do you really want to delete this post?")) return;
        var current = $(this);
            loader = current.next();
            parent = current.parent();
            list = parent.prev();
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                security: kmzFavorites.nonce,
                action: 'kmz_del_all'
            },
            beforeSend: function(){
                current.fadeOut(300, function() {
                    loader.fadeIn();
                });
            },
            success: function(res){
                loader.fadeOut(300, function(){
                    if(res == 'List empty'){
                        parent.html(res);
                        list.fadeOut();
                    } else{
                        current.fadeIn();
                        alert(res);
                    }
                    parent.html(res);
                });
            },
            error: function(){
                alert("Error AJAX");
            }
        });
    });
});