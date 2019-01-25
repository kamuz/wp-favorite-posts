# Плагин добавления записей в избранное

## Добавляем кнопку для добавление в избранное

Добавим ссылку **Добавить в избранное** с помощью фильтра. При этом мы должны сделать проверку, чтобы ссылка добавлялась только для материалов (content post type) с типом статьи и чтобы она отображалась только для авторизированных пользователей.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

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

Нам нужно загружать CSS и JavaScript файлы только для страниц блога и только для авторизированных пользователей, поэтому внутри функции мы сделаем проверку.

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

## Отправляем AJAX запрос

Порядок того что мы делаем в нашем скрипте:

* Отменяем дефолтное поведение по клику на нашу кнопку с помощью метода `preventDefault()`
* Используем метод `ajax()` и передаём необходимые параметры для AJAX запроса:
    * `type:` - данные будут передаваться методом `POST`
    * `url:` - файл, куда будут отправленны данные. Пока что мы запишем этот адрес в ручную `wp-admin/admin-ajax.php`, а потом мы это поменяем
    * `data:` - данные, которые мы будем отправлять. Пока что мы просто отправим данные для теста. Кроме этого нам нужно указать ещё один параметр `{action}`, который и свяжет наш AJAX запрос с нужным нам хуком и нужной функцией соотвественно.
    * `success:` - ответ, который прийдёт в случае успешной отправки AJAX запроса. Создаём функцию, в которую передадим результат AJAX запроса, который мы внутрий функции пока что просто выведем в консоль.
    * `error:` - выводим сообщение, если во время запроса произвойдёт какая-то ошибка

*wp-content/plugins/kmz-favorite-posts/js/script.js*

```js
jQuery(document).ready( function($) {
    $('p.favorite-links > a').click(function(e){
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: '/wp-admin/admin-ajax.php',
            data: {
                test: 'Test data',
                action: 'kmz_add_favorite'
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
```

Теперь мы должны принять наш запрос в файле *wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*. Мы будем принимать запрос только от авторизированных пользователей, поэтому будем применять `wp_ajax_{action}`. Вместо `{action}` мы указываем то же значение что мы указывали в параметре `action:` в нашем скрипте. Таким образом, когда мы будем отправлять AJAX запрос, он отправляется в файле *wp-admin/admin-ajax.php*, в котором используется передаваемый `action:`, после чего разыскивается соотвественный хук с динамическим экшеном `wp_ajax_kmz_add_favorite`, после чего запрос попадает в функцию, которую мы повесили на наш хук - можно её назвать точно также как и хук, главное чтобы имя этой функции было уникальным.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_add_favorite(){
    if(isset($_POST)){
        print_r($_POST);
    }
    wp_die('AJAX request completed!');
}
add_action( 'wp_ajax_kmz_add_favorite', 'kmz_add_favorite' );
```

Мы отправляем данные методом `POST`, поэтому как минимум в данном массиве должны находится наши данные, поэтому мы проверяем если POST данные существуют, то мы пока что их просто распечатаем. После этого мы должны завершить работу скрипта, для этого мы будем используем функцию `wp_die()`, которая фактически является обвёрткой стандарной функции PHP `die()`;

Если вы всё написали правильно, то в итоге должны получить примерно такой результат в консоли после клика на кнопку **Add to Favorite**:

```
Array
(
    [test] => Test data
    [action] => kmz_add
)
AJAX request complete!
```

## Формируем динамический путь к `wp-admin/admin-ajax.php`

Теперь нам нужно `url:` который мы прописали вручную прописать динамически. Зачем это вообще изменять, тем более что в большинстве случаев такой код будет работать. Дело в том что иногда мы может установить сайт не в корень сервера, а например в подпаку, но наш текущий путь начинается от корня и мы можем получить ошибку, потому что будем обращатся к не существующему файлу. Для того чтобы исправить эту проблему нам нужно использовать функцию `wp_localize_script()`, которая позволяет передать необходимые данные для нашего скрипта. Данную функцию нужно вызывать внутри функции, которая использует хук `wp_enqueue_scripts`.

Первый параметр - это идентификатор скрипта, для которого мы передаём наши данные, второй параметр - это имя объекта в котором мы будем хранить наши данные, третий параметр - это сами данные, которые передаются в виде массива, где ключ это свойства передаваемого объекта, а значение - это сами данные и в данном случае это будет у нас путь к файлу *wp-admin/admin-ajax.php*. В `url` мы передадим путь к админке используя функцию `admin_url()`, на вход которой передадим путь к нашему файлу `admin-ajax.php`.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_favorite_css_js() {
    if( !is_single() || !is_user_logged_in() ) {
        return $content;
    }
    else{
        wp_enqueue_script( 'kmz-favorite-script', plugins_url('/js/kmz-favorite-script.js', __FILE__), array( 'jquery' ), '1.0.0', true);
        wp_enqueue_style( 'kmz-favorite-style', plugins_url('/css/kmz-favorite-style.css', __FILE__), null, '1.0.0', 'screen' );
        wp_localize_script( 'kmz-favorite-script', 'kmzFavorites', [ 'url' => admin_url( 'admin-ajax.php' )] );
    }
}
```

Теперь после обновления страницы в исходном коде мы можем обнаружить нашу переменную `var kmzFavorites = {"url":"http:\/\/wordpress.loc\/wp-admin\/admin-ajax.php"};` - таким образом мы получили полный абсолютный путь к файлу *admin-ajax.php*.

Теперь для начала можем вывести необходимый нам объект `kmzFavorites` в консоль:

*wp-content/plugins/kmz-favorite-posts/js/kmz-favorite-script.js*

```js
jQuery(document).ready( function($) {
    $('p.favorite-links > a').click(function(e){
        e.preventDefault();
        console.log(kmzFavorites);
        //..
    });
});
```

Соответсвенно, если нам нужно получить только значение свойства `url` мы должны написать так - `kmzFavorites.url`.

*wp-content/plugins/kmz-favorite-posts/js/kmz-favorite-script.js*

```js
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
```

Таким образом мы сформировали динамический путь.

## Ограничиваем количество нажатий на ссылку, улучшаем безопасность и получаем ID текущего поста

По клику на кнопку у нас будет появляться прелоадер, который будет отображаться в процессе отправки AJAX запроса на сервер, после того как прийдёт ответ от сервера мы будем скрывать этот прелоадер и вместо ссылки будем выводить текст, например **Added to Favorite**.

Для начала находим и перемещаем в папку *img/* gif изображение прелоадера. Далее в функции, которая выводит ссылку добавляем изображение:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_favorites_content( $content ) {
    if( !is_single() || !is_user_logged_in() ) {
        return $content;
    }
    else {
        $img_loader_src = plugins_url( '/img/ajax-loader.gif', __FILE__ );
        return '<p class="favorite-links add-to-favorite"><a href="#">Add to Favorite</a> <img src="' . $img_loader_src . '" alt="loader" class="hidden"> </p>' . $content;
    }
}
```

Проверяем что изображение успешно выводится и скрываем его в CSS:

*wp-content/plugins/kmz-favorite-posts/css/kmz-favorite-style.css*

```css
.hidden{
    display: none;
}
```

Теперь нам нужно показывать это изображение-прелоадер, только в тот момент, когда пользователь кликает по ссылке **Add to Favorite**. Для этого в файл со скриптами, где мы пишем код для AJAX запроса добавить новый параметр `beforeSend:` в котором мы напишем функцию, которая будет отображать картинку-прелоадер. После чего мы немного подправим параметр `success:`, где в функции мы будем скрывать прелоадер и ссылку и пока что отображать результат AJAX запроса с помощью функции `html()`, который хранится в переменной `res`.

*wp-content/plugins/kmz-favorite-posts/js/kmz-favorite-script.js*

```js
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
            beforeSend: function(){
                $('p.favorite-links > img').fadeIn();
            },
            success: function(res){
                console.log(res);
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
```

Теперь нам нужно проверить подлинность AJAX запроса и для этого мы воспользуемся функцией `wp_create_nonce()`, которая создаёт специальный проверочный код и функцию `wp_verify_nonce()`, которая проверяет этот проверочный код.

Функцию `wp_create_nonce()` мы будем передавать в качестве элемента массива третьего параметра функции `wp_localize_script()`. На вход функции `wp_create_nonce()` нам нужно передать секретную строку, на основании которой и генеруется этот проверочный код.

*wp-content/plugins/kmz-favorite-posts/functions.php*

```php
wp_localize_script( 'kmz-favorite-script', 'kmzFavorites', [ 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'kmz-favorites' )] );
```

Теперь после обновления страницы в исходном коде мы увидим такое содержимое объекта `kmzFavorites{}`:

```js
/* <![CDATA[ */
var kmzFavorites = {"url":"http:\/\/wordpress.loc\/wp-admin\/admin-ajax.php","nonce":"0350847b82"};
/* ]]> */
```
