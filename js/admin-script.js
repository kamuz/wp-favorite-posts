jQuery(document).ready(function ($) {
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
    })
});