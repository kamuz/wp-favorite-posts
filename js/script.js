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
            success: function(res){
                console.log(res);
            },
            error: function(){
                alert("Error AJAX");
            }
        });
    });
});