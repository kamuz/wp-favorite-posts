<?php

/**
 * Plugin Name: KMZ Favorite Posts
 * Description: Display and manage favorite posts
 * Version: 1.0.0
 * Author: Vladimir Kamuz
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Author URI: https://wpdev.pp.ua/
 * Plugin URI: https://github.com/kamuz/wp-favorite-posts
 * Text Domain: kmz-favorite-posts
 * Domain Path: /languages
*/

/**
 * Add button before content single post for logged users
 */
function kmz_favorites_content( $content ) {
    global $post;
    $img_loader_src = plugins_url( '/img/ajax-loader.gif', __FILE__ );
    if ( !is_single() || !is_user_logged_in() ) {
        return $content;
    }
    elseif (kmz_is_favorites($post->ID)){
        return '<p class="remove-favorite"><a href="#">Remove from favorites</a> <img src="' . $img_loader_src . '" alt="loader" class="loader-gif hidden"> </p>' . $content;
    }
    else {
        return '<p class="favorite-links add-to-favorite"><a href="#">Add to Favorite</a> <img src="' . $img_loader_src . '" alt="loader" class="hidden"> </p>' . $content;
    }
}
add_filter( 'the_content', 'kmz_favorites_content' );

/**
 * Add CSS and JavaScript
 */
function kmz_favorite_css_js() {
    global $post;
    if( is_single() || is_user_logged_in() ){
        wp_enqueue_style( 'kmz-favorite-style', plugins_url('/css/style.css', __FILE__), null, '1.0.0', 'screen' );
        wp_enqueue_script( 'kmz-favorite-script', plugins_url('/js/script.js', __FILE__), array( 'jquery' ), '1.0.0', true);
        wp_localize_script( 'kmz-favorite-script', 'kmzFavorites', [ 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'kmz-favorites' ), 'postId' => $post->ID] );
    }
}
add_action( 'wp_enqueue_scripts', 'kmz_favorite_css_js' );

/**
 * AJAX request to add post to favorite
 */
function kmz_add_favorite(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    $post_id = (int)$_POST['postId'];
    $user = wp_get_current_user();
    if(kmz_is_favorites($post_id)){
        wp_die('Current post is already added');
    }
    if(add_user_meta( $user->ID, 'kmz_favorites', $post_id )){
        wp_die('You post successfully added');
    }
    wp_die('AJAX request completed!');
}
add_action( 'wp_ajax_kmz_add_favorite', 'kmz_add_favorite' );

/**
 * Check in current post is added
 */

function kmz_is_favorites($post_id){
    $user = wp_get_current_user();
    $favorites = get_user_meta( $user->ID, 'kmz_favorites' );
    foreach($favorites as $favorite){
        if($favorite == $post_id){
            return true;
        }
    }
    return false;
}