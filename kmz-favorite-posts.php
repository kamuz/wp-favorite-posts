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
        return '<p class="favorite-links remove-favorite"><a href="#" data-action="del">Remove from favorites</a> <img src="' . $img_loader_src . '" alt="loader" class="loader-gif hidden" data-action="del"> </p>' . $content;
    }
    else {
        return '<p class="favorite-links add-to-favorite"><a href="#" data-action="add">Add to Favorite</a> <img src="' . $img_loader_src . '" alt="loader" class="hidden"> </p>' . $content;
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
 * Function for AJAX request for add post to favorite
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
 * Function for AJAX request for delete post from favorite
 */
function kmz_del_favorite(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    $post_id = (int)$_POST['postId'];
    $user = wp_get_current_user();
    if(!kmz_is_favorites($post_id)){
        wp_die();
    }
    if(delete_user_meta( $user->ID, 'kmz_favorites', $post_id )){
        wp_die('Removed');
    }
    wp_die('Error of removing post meta data from the database!');
}
add_action( 'wp_ajax_kmz_del_favorite', 'kmz_del_favorite' );

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

/**
 * Add dashboard widget
 */
function kmz_favorites_dashboard_widget(){
    wp_add_dashboard_widget( 'kmz_favorites_dashboard', 'Your list of favorite posts', 'kmz_show_dashboard_widget' );
}
add_action('wp_dashboard_setup', 'kmz_favorites_dashboard_widget' );

function kmz_show_dashboard_widget(){
    $user = wp_get_current_user();
    $favorites = get_user_meta( $user->ID, 'kmz_favorites' );
    if(!$favorites){
        echo "You don't have favorite posts yet!";
    }
    else{
        $img_loader_src = plugins_url( '/img/ajax-loader.gif', __FILE__ );
        echo '<ul>';
        foreach($favorites as $favorite){
            echo '<li><a href="' . get_the_permalink( $favorite ) . '" target="_blank">' . get_the_title($favorite) . '</a><span><a href="#" data-post="' . $favorite . '" class="dashicons dashicons-no"></a></span><img src="' . $img_loader_src . '" alt="loader" class="loader-gif hidden"> </li>';
        }
        echo '</ul>';
        echo '<div class="kmz-favorites-del-all"><button class="button button-primary">Delete All</button><img src="' . $img_loader_src . '" alt="loader" class="loader-gif hidden"></div>';
    }
}

/**
 * Add files of JS scripts and CSS styles to admin section
 */
function kmz_favorite_admin_scripts($hook) {
    if($hook != 'index.php'){
        return;
    }
    else{
        wp_enqueue_script( 'kmz-favorite-admin-script', plugins_url('/js/admin-script.js', __FILE__), array( 'jquery' ), '1.0.0', true);
        wp_enqueue_style( 'kmz-favorite-admin-style', plugins_url('/css/admin-style.css', __FILE__), null, '1.0.0', 'screen' );
        wp_localize_script( 'kmz-favorite-admin-script', 'kmzFavorites', [ 'nonce' => wp_create_nonce( 'kmz-favorites' ) ] );
    }
}
add_action( 'admin_enqueue_scripts', 'kmz_favorite_admin_scripts' );

/**
 * Delete all favorite posts from admin widget
 */
function kmz_del_all(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }

    $user = wp_get_current_user();

    if(delete_metadata('user', $user->ID, 'kmz_favorites')){
        wp_die('List empty');
    }
    else{
        wp_die('Error of deleting');
    }
}

add_action( 'wp_ajax_kmz_del_all', 'kmz_del_all' );