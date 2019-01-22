# Плагин добавления записей в избранное

## Добавляем кнопку для добавление в избранное

Добавим ссылку **Добавить в избранное** с помощью фильтра. При этом мы должны сделать проверку, чтобы ссылка добавлялась только для материалов (content post type) с типом статьи и чтобы она отображалась только для авторизированных пользователей.

*wp-content/plugins/kamuz-favorite-posts/kamuz-favorite-posts.php*

```php
function kmz_favorites_content( $content ) {
    if( !is_single() || !is_user_logged_in() ) {
        return $content;
    }
    else {
        return '<p class="favorite-links add-to-favorite"><a href="#">Add to Favorite</a></p>' . $content;
    }
}
add_filter( 'the_content', 'kmz_favorites_content' );
```

* `!is_single()` - проверяем не является ли данная страница статьёй
* `!is_user_logged_in()` - проверяем не залогиненный ли пользователь

## Подключение CSS и JavaScript

```php
function kmz_favorite_css_js() {
    if( is_single() || is_user_logged_in() ){
        wp_enqueue_style( 'kmz-favorite-style', plugins_url('/css/style.css', __FILE__), null, '1.0.0', 'screen' );
        wp_enqueue_script( 'kmz-favorite-script', plugins_url('/js/script.js', __FILE__), array( 'jquery' ), '1.0.0', true);
    }
}
add_action( 'wp_enqueue_scripts', 'kmz_favorite_css_js' );
```

Добавим немного стилей:

*wp-content/plugins/kmz-favorite-posts/css/style.css*

```css
.single p.favorite-links a{
    text-decoration: none;
    box-shadow: none;
    border-bottom: 2px dotted indianred;
}
```

Проверим JavaScript:

*wp-content/plugins/kmz-favorite-posts/js/script.js*

```js
jQuery(document).ready(function($){
    $('p.favorite-links > a').click(function(e){
        e.preventDefault();
        console.log("Clicked! You are the best WordPress Developer...");
    });
});
```