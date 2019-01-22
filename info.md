# Плагин добавления записей в избранное

## Добавляем кнопку для добавление в избранное

Добавим ссылку **Добавить в избранное** с помощью фильтра. При этом мы должны сделать проверку, чтобы ссылка добавлялась только для материалов (content post type) с типом статьи и чтобы она отображалась только для авторизированных пользователей.

*wp-content/plugins/kamuz-favorite-posts/kamuz-favorite-posts.php*

```php
function kamuz_favorites_content( $content ) {
    if( !is_single() || !is_user_logged_in() ) {
        return $content;
    }
    else {
        return '<p class="favorite-links add-to-favorite"><a href="#">Add to Favorite</a></p>' . $content;
    }
}
add_filter( 'the_content', 'kamuz_favorites_content' );
```

* `!is_single()` - проверяем не является ли данная страница статьёй
* `!is_user_logged_in()` - проверяем не залогиненный ли пользователь