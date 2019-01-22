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
function kamuz_favorites_content( $content ) {
    if( !is_single() || !is_user_logged_in() ) {
        return $content;
    }
    else {
        return '<p class="favorite-links add-to-favorite"><a href="#">Add to Favorite</a></p>' . $content;
    }
}
add_filter( 'the_content', 'kamuz_favorites_content' );