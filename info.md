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
        wp_enqueue_script( 'kmz-favorite-script', plugins_url('/js/script.js', __FILE__), array( 'jquery' ), '1.0.0', true);
        wp_enqueue_style( 'kmz-favorite-style', plugins_url('/css/kmz-favorite-style.css', __FILE__), null, '1.0.0', 'screen' );
        wp_localize_script( 'kmz-favorite-script', 'kmzFavorites', [ 'url' => admin_url( 'admin-ajax.php' )] );
    }
}
```

Теперь после обновления страницы в исходном коде мы можем обнаружить нашу переменную `var kmzFavorites = {"url":"http:\/\/wordpress.loc\/wp-admin\/admin-ajax.php"};` - таким образом мы получили полный абсолютный путь к файлу *admin-ajax.php*.

Теперь для начала можем вывести необходимый нам объект `kmzFavorites` в консоль:

*wp-content/plugins/kmz-favorite-posts/js/script.js*

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

*wp-content/plugins/kmz-favorite-posts/js/script.js*

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

## Ограничиваем количество нажатий на ссылку, улучшаем безопасность

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

*wp-content/plugins/kmz-favorite-posts/js/script.js*

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

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
wp_localize_script( 'kmz-favorite-script', 'kmzFavorites', [ 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'kmz-favorites' )] );
```

Теперь после обновления страницы в исходном коде мы увидим такое содержимое объекта `kmzFavorites{}`:

```js
/* <![CDATA[ */
var kmzFavorites = {"url":"http:\/\/wordpress.loc\/wp-admin\/admin-ajax.php","nonce":"0350847b82"};
/* ]]> */
```


У нас появилось новое свойство `nonce:`, которое мы можем уже использовать в своём скрипте. Теперь вместо свойства `test:` объекта `data{}` мы можем создать свойство `security:`, которому передадим значение свойсва `nonce:` объекта `kmzFavorites{}`.

*wp-content/plugins/kmz-favorite-posts/js/script.js*

```
data: {
    security: kmzFavorites.nonce,
    action: 'kmz_add_favorite',
},
```

Теперь мы можем проверить совпадает сгенерированный нами код с тем, который мы указали в функции `wp_create_nonce()` - сделать это можно в функции `kmz_add_favorite()`, которую мы прицепили с хуком используя функцию `wp_verify_nonce()`:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_add_favorite(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    wp_die("AJAX request complete!");
}
```

Функция `wp_verify_nonce()` вернёт `false` если сгенированный код не соответствует строке которую мы передали в функцию `wp_create_nonce()`.

# Получаем ID текущего поста

Теперь нам нужно получить ID текущего поста. В WordPress есть переменная `$post` в которой храняться все данные текущего поста. Так как нам эта переменная нужна внутри функции, где локальная область видимости, нам нужно сделать её глобальной с помощью ключевого слова `global` - таким образом мы получим значение переменной из глобальной области видимости. Переменная `$post` - это объект, который содержит множество свойств, который относятся к текущему посту, нам же нужен только ID.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
wp_localize_script( 'kmz-favorite-script', 'kmzFavorites', [ 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'kmz-favorites' ), 'postId' => $post->ID] );
```

Проверим исходный код и содержимое объекта `kmzFavorites{}` - у нас долже появится новое свойство `postId:`. Теперь мы можем это свойство добавить в объект `data:` нашего AJAX запроса:

*wp-content/plugins/kmz-favorite-posts/js/script.js*

```js
data: {
    security: kmzFavorites.nonce,
    postId: kmzFavorites.postId,
    action: kmz_add'
}
```

После чего можем вывести на экран весь массив `$_POST`.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_add_favorite(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    wp_die("AJAX request complete!");
}
```

Теперь когда мы с помощью AJAX получаем ID текущего поста, мы можем добавлять текущий пост в БД.

## Сохранение статьи в БД

`add_user_meta()` - добавляем определённые метаданные к указанному пользователю. На вход подаются несколько параметров, нас же интересуют первые 3 - ID пользователя, ключ мета поля и его значение.

`get_user_meta()` - позволяет получить определённые метаданные (наши статьи). Кроме того нам нужно будет проверять - не добавленна ли данная статья в избранное. На вход мы передаём ID пользователя, ключ метаполя.

`wp_get_current_user()` - получает данные о текущем пользователе, в том числе ID, который мы будем использовать.

Все мета данные хранятся в таблицы `wp_usermeta` хранятся все мета данные пользователя (данные профиля), при этом колонка `user_id` указывает на ID пользователя для которого записанны эти данные.

Для того чтобы тестировать то что мы напрограмируем, нужно создать ещё одного пользователя и авторизироваться в режиме инкогнито.

Мы создадим новый ключ для колонки `meta_key` например `kmz_favorites`, а в качестве значений будем записывать ID статьи, которую сохраняем в избранное.

* Сохраняем текущий ID страницы в переменнюу `$post_id` и приводим её к числу.
* Сохраняем данные текущем пользователе в переменную `$user` и функции `wp_current_user()`, которая возвращает объект с данными и чтобы в этом убедится мы можем посмотреть эти данные использовав функцию `var_dump()`.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_add_favorite(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    $post_id = (int)$_POST['postId'];
    $user = wp_get_current_user();
    echo '<pre>';
    var_dump($user);
    echo '</pre>';
    wp_die("AJAX request complete!");
}
```

Теперь мы можем добавлять данные в БД и для этого будем использовать функцию `add_user_meta()`. Данная функция в случае успешного добавления информации в БД возвращает ID добавленного поля, в ином случае возвращает `false`, поэтому мы можем использовать это в условии:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_add_favorite(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    $post_id = (int)$_POST['postId'];
    $user = wp_get_current_user();
    if(add_user_meta( $user->ID, 'kmz_favorites', $post_id )){
        wp_die('You post successfully added');
    }
    wp_die('Error of adding new post meta data to the database!');
}
```

Сейчас данные успешно добавляются в базу данных, но при этом если мы обновим страницу поста, то мы снова сможем добавить уже же статью в БД и при этом уже будет дублирование наших данных в БД. Перед тем как двигаться дальше, добавим несколько статей в изобранное для теста. Чтобы избежать дублирования данных, нам нужно написать отдельную функцию, которая будет проверять не добавлен ли текущая статья в избранное.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_is_favorites($post_id){
    $user = wp_get_current_user();
    $favorites = get_user_meta( $user->ID, 'kmz_favorites' );
    print_r($favorites);
    return true;
}
```

Теперь мы можем вызвать эту функцию чтобы посмотреть возвращаются ли нам данные и вставим её перед тем как добавлять данные в БД.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
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
    wp_die('Error of adding new post meta data to the database!');
}
```

Мы получили массив и теперь мы можем пройтись по этому массиву в цикле `foreach` и внутри сравниваем каждый элемент массива с ID нашей текущей статьи и если находим совпадение, тогда будем возвращать `true` то есть такая статья уже есть и нам нужно завершить дальшейшее выполнение функции, а если нет, тогда мы продолжим выполнение программного кода нашей функции и добавим текущую запись в избранное.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_add_favorite(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    $post_id = (int)$_POST['postId'];
    $user = wp_get_current_user();
    if(kmz_is_favorites($post_id)){
        wp_die();
    }
    if(add_user_meta( $user->ID, 'kmz_favorites', $post_id )){
        wp_die('You post successfully added');
    }
    wp_die('Error of adding new post meta data to the database!');
}

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
```

Теперь осталось сделать чтобы когда статья уже добаленна в избранное отображалась ссылка **Delete from Favorites**. За вывод кнопки у нас отвечает функция `kmz_favorites_content()`. Чтобы внутри использовать проверку с помощью функции `kmz_is_favorites()` нужно передать на вход ID поста, поэтому мы сначала сделаем глобальной переменную `global $post` и потому получим ID текущей страницы `$post->ID`.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
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
```

## Удаление из избранного

Для удаления из избранного мы уже будем использовать `delete_user_meta()`.

Наш существующий AJAX запрос в файле *wp-content/plugins/kmz-favorite-posts/js/script.js* работает только на добавление, соответственно чтобы нам реализовать удаление нам нужно просто его скопировать и поменять `action:`, но при этом мы будем дублировать код, что не является хорошей практикой. Чтобы этого избежать нужно в начале унифицировать ссылки, чтобы исходный код их был одинаковый. Чтобы отличить эти ссылки мы можем добавить новый атбрибут, назовём его к примеру `data-action` и назначим им разные значения этого атрибута, которое мы будем проверять и тем самым будем избегать дублирование кода:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
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
```

Теперь получим значение атрибута `data` с помощью метода jQuery `data()` и выведем его в консоль:

*wp-content/plugins/kmz-favorite-posts/js/kmz-favorite-script.js*

```js
jQuery(document).ready( function($) {
    $('p.favorite-links > a').click(function(e){
        e.preventDefault();
        var action = $(this).data('action');
        console.log(action);
        $.ajax({
        //..
```

Таким образом по клику на кнопки **Add to Favorites** и **Remove from Favorites** мы будем получать строку либо `add` либо `del` в консоли. Теперь мы можем изменить наш экшен, чтобы он был динамическим:

*wp-content/plugins/kmz-favorite-posts/js/script.js*

```js
data: {
    security: kmzFavorites.nonce,
    action: 'kmz_' + action + '_favorite',
    postId: kmzFavorites.postId,
},
```

Теперь нам осталось сдублировать нашу функцию, которая обрабатывает AJAX запрос. Когда мы используем функцию `kmz_is_favorites()`, то мы уже идём от обратного и завершаем выполнение скрипта в том случае, если этой записи нет в БД. Как уже говорилось ранее для удаления мета данных мы будем использовать функцию `delete_user_meta()`, которой  на вход мы передаём ID пользователя, ключ и значение поля. Значение поля не объязательное и если мы укажем только первые два параметра, тогда будут удаленны все мета данные с определённым ключём для даннного пользователя - это нам пригодится в том случае, когда пользователь пожелает удалит все статьи из избранного.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
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
```

## Добавление виджета в консоли админ-панели и вывод избранных статей в виде ссылок

Для того чтобы добавить виджет в консоль существует специальная функция `wp_add_dashboard_widget()`, которая должна вызываться во время события `wp_dashboard_setup`, то есть когда происходит инициализация консоли. На вход передаются 5 параметров, 3 из которых объязательные - ID виджета (уникальный идентификатор), название виджета (отображаемый заголовок виджета) и функция обратного вызова, которая выводить сам контент виджета.

Для начала повесим хук на событие `wp_dashboard_setup` и опишем нашу функцию, где вызовем `wp_add_dashboard_widget()`, а также добавим колбэк функцию, которая просто будет выводить текстовый статический контент:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_favorites_dashboard_widget(){
    wp_add_dashboard_widget( 'kmz_favorites_dashboard', 'Your list of favorite posts', 'kmz_show_dashboard_widget' );
}
add_action('wp_dashboard_setup', 'kmz_favorites_dashboard_widget' );

function kmz_show_dashboard_widget(){
    echo "This this temp content for admin widget";
}
```

Этого уже достаточно, чтобы увидеть наш виджет на стартовой странице админ-панели.

* Получим текущего пользователя с помощью `wp_get_current_user()`
* Получаем все ID статей, которые были добавленны в избранное с помощью `get_user_meta()`
* Выводим данные на экран
* Проверяем если у пользователя нет статей в изобранном, тогда выводим сообщение

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_show_dashboard_widget(){
    $user = wp_get_current_user();
    $favorites = get_user_meta( $user->ID, 'kmz_favorites' );
    if(!$favorites){
        echo "You don't have favorite posts yet!";
    }
    else{
        echo '<pre>';
        var_dump($favorites);
        echo '</pre>';
        return;
    }
}
```

Если вместо `return` использовать функцию `exit()` то у нас не будут выводится остальные виджеты, то есть у нас прекратится выполнение функции `wp_add_dashboard_widget()`.

Сейчас мы можем распечатать список статей с помощью функции `wp_get_posts()` для этого:

* Берём наш массив и делаем его строкой, в которой ID постов будут разделённые запятой используя функцию `implode()`
* Передаём полученный набор ID в качестве параметра c ключём `include` функции `get_posts()`
* Используя массив `foreach` выводим заголовки статей:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_show_dashboard_widget(){
   $user = wp_get_current_user();
    $favorites = get_user_meta( $user->ID, 'kmz_favorites' );
    if(!$favorites){
        echo "You don't have favorite posts yet!";
    }
    else{
        $str = implode( ',', $favorites);
        // echo $str;
        $kmz_posts = get_posts( ['include' => $str] );
        // var_dump($kmz_posts);
        echo "<ul>";
        foreach ($kmz_posts as $kmz_post) {
            echo '<li><a href="'. $kmz_post->guid . '">' . $kmz_post->post_title . '</a></li>';
        }
        echo "</ul>";
    }
}
```

При таком подходе мы можем выводить не только информацию в виде ссылок, а и другие данные.

Но можно использовать альтернативный вариант с использованием цикла и функций `get_the_title()` и `get_the_permalink()`, которым на вход нужно передать ID поста:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_show_dashboard_widget(){
    $user = wp_get_current_user();
    $favorites = get_user_meta( $user->ID, 'kmz_favorites' );
    if(!$favorites){
        echo "You don't have favorite posts yet!";
    }
    else{
        $data = [];
        foreach($favorites as $favorite){
            $data[$favorite] = [
                'title' => get_the_title( $favorite ),
                'link' => get_the_permalink( $favorite )
            ];
        }
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}
```

На выходе мы получим примерно такой вывод:

```plain
Array
(
    [4] => Array
        (
            [title] => Hello, man
            [link] => http://wordpress.loc/?p=4
        )

    [6] => Array
        (
            [title] => How are you
            [link] => http://wordpress.loc/?p=6
        )

)
```

Теперь это всё можно вывести в виде списка ссылок:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_show_dashboard_widget(){
    $user = wp_get_current_user();
    $favorites = get_user_meta( $user->ID, 'kmz_favorites' );
    if(!$favorites){
        echo "You don't have favorite posts yet!";
    }
    else{
        echo '<ul>';
        foreach($favorites as $favorite){
            echo '<li><a href="' . get_the_permalink( $favorite ) . '" target="_blank">' . get_the_title($favorite) . '</a></li>';
        }
        echo '</ul>';
    }
}
```

## Удаление из избранного в виджете

Теперь нам нужно добавить элемент `span` внутри которого будет ссылка по клику на которую будет выполняться AJAX запрос на сервер. Внутри ссылки мы добавим атрибут `data-post` внутри которого у нас будет выводиться ID статьи, который у нас находится в переменной `$favorite`. Иконку для удаления мы возьмём из [стандарных иконок](https://developer.wordpress.org/resource/dashicons/#no), который идут вместе с WordPress.

Также нам понадобится наше изображение лоадер, который мы просто скопируем с уже имеющеющего кода, при этом не забываем также скопировать переменную `$img_loader_src`.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
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
    }
}
```

Кроме этого тот файл стилей, который мы подключали ранее будет работать только в пользовательской части сайта, а в админке он будет не доступен. Для подключения пользовательского файла стилей нужно уже использовать другой экшн `admin_enqueue_scripts`, который удобен тем, что мы можем подключать стили или скрипты к какой-то определённой странице и в нашем случае мы хотим подключить только для главной страницы админки.

Создадим нашу функцию и передадим параметр `$hook`, который затем распечатаем, чтобы посмотреть что он нам возвратит.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_favorite_admin_scripts($hook) {
    var_dump($hook);
}
add_action( 'admin_enqueue_scripts', 'kmz_favorite_admin_scripts' );
```

При обновлении страницы вывелась какая строка, а потом всё перекрыла админка, но если перейти в исходный код, то мы увидим:

```plain
string(9) "index.php"
```

Если мы перейдём на другую страницу в админке, то этот параметр уже будет возвращать другое значение. То есть в параметре `$hook` у нас находится адрес скрипта, таким образом в условии мы можем провить какое именно значение находится внутри данного параметра и если это `index.php`, тогда мы будем подключать наши скрипты и стили, а если что-то другое, то мы завершим выполнение данной функции. Давайте попробует пока что просто выводить `alert()` если мы на главной странице админки:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_favorite_admin_scripts($hook) {
    if($hook != 'index.php'){
        return;
    }
    else{
        echo '<script>alert("Hello, Admin");</script>';
    }
}
```

Теперь по аналогии как мы делали ранее подключаем файл стилей и скриптов:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_favorite_admin_scripts($hook) {
    if($hook != 'index.php'){
        return;
    }
    else{
        wp_enqueue_script( 'admin-script', plugins_url('/js/admin-script.js', __FILE__), array( 'jquery' ), '1.0.0', true);
        wp_enqueue_style( 'kmz-favorite-admin-style', plugins_url('/css/admin-style.css', __FILE__), null, '1.0.0', 'screen' );
    }
}
```

Создадаём соответствующие файлы в пользовательской папке с нашим плагином и можем добавить туда какой-то код, чтобы проверить что они подключились корректно и мы ничего не напутали с путями.

*wp-content/plugins/kmz-favorite-posts/css/admin-style.css*

```css
#kmz_favorites_dashboard .dashicons.dashicons-no{
    color: #82878c;
    outline: none;
}
#kmz_favorites_dashboard .dashicons.dashicons-no:hover{
    color: red;
}
#kmz_favorites_dashboard .loader-gif{
    position: relative;
    top: 3px;
    left: 3px;
}
```

*wp-content/plugins/kmz-favorite-posts/js/admin-script.js*

```js
console.log("Test Admin Widget JS");
```

Ранее мы получали ID поста через глобальную переменную `global $post` - сейчас же мы мы будем брать его из атрибута `data-post`.

Нам также потребуются данные для локализации (безопасность AJAX) - мы можем просто скопировать данный фрагмент кода и лишнее удалим. К примеру нам уже не нужно указывать параметр `url`, который ссылался на файл `wp-admin/admin-ajax.php` и если мы перейдём исходный код админки, то мы увидим что у нас уже определенна переменная `var ajaxurl = '/wp-admin/admin-ajax.php'`, которая и ведёт на обработчик, а пользовательской части этого по умолчанию нет. В итоге у нас получилось следующее:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_favorite_admin_scripts($hook) {
    if($hook != 'index.php'){
        return;
    }
    else{
        wp_enqueue_script( 'admin-script', plugins_url('/js/admin-script.js', __FILE__), array( 'jquery' ), '1.0.0', true);
        wp_enqueue_style( 'kmz-favorite-admin-style', plugins_url('/css/kmz-favorite-admin-style.css', __FILE__), null, '1.0.0', 'screen' );
        wp_localize_script( 'admin-script', 'kmzFavorites', [ 'nonce' => wp_create_nonce( 'kmz-favorites' ) ] );
    }
}
```

Обновим страницу и перейдём в исходный код чтобы найти переменную `var kmzFavorites`, которая должна иметь примерно следующий вид - `var kmzFavorites = {"nonce":"4a2d730896"};`.

Напишем в скрипте базовый код, который будет обрабатывать событие клика по нашей кнопке:

*wp-content/plugins/kmz-favorite-posts/js/admin-script.js*

```js
jQuery(document).ready(function($){
    $('#kmz_favorites_dashboard .dashicons.dashicons-no').click(function(e){
        e.preventDefault();
        if(!confirm("Do you really want to delete this post?")) return;
        console.log("Hello, Admin AJAX");
    })
});
```

С помощью `confirm()` мы просто уточим у пользователя действительно ли он хочет удалить этот пост при этом используем обратное отрицание, если вернётся `false` то есть клик по кнопке **Canсel**, то мы завершаем выполнение скрипта.

Теперь нам потребуется:

* ID поста, который содержится в атрибуте `data-post` и в этом нам поможет метод jQuery `data()`
* Элемент `span`, то есть родитель того элемента по которому был произведён клик, для того чтобы скрыть сам крестик для этой цели будем использовать метод `parent()`, который вернёт родительский элемент
* Лоадер для того чтобы его показать и для этого мы используем метода `next()`, который вернёт следующий элемент
* Родитель элемента `span` - элемент `li` и для того чтобы удалить определённую статью с списка изобранного, мы применим метод `closest()` который вернёт ближайший родитель с указанным селектором.

То есть нам потребуется в общем счёте 4 переменных чтобы было удобно в дальнейшем работать с кодом - создадим их и выведем в консоль, чтобы быть уверенем что возвращаются требуемые нам данные по клику.

*wp-content/plugins/kmz-favorite-posts/js/admin-script.js*

```js
jQuery(document).ready(function($){
    $('#kmz_favorites_dashboard .dashicons.dashicons-no').click(function(e){
        e.preventDefault();
        if(!confirm("Do you really want to delete this post?")) return;
        var postId = $(this).data('post'),
            parent = $(this).parent(),
            loader = parent.next(),
            li = $(this).closest('li');
        console.log(postId);
        console.log(parent);
        console.log(loader);
        console.log(li);
    })
});
```

Чтобы не писать AJAX запрос с самого начала мы скопируем ранее созданные и подправим его под наши нужды.

* Нам нужно изменить параметр `url` - ранее мы локализовали данные и мы обращались к соответсвующему свойству `url` объекта `kmzFavorites`, сейчас же для админской части у нас уже доступна переменная `ajaxurl` которую мы можем использовать. Чтобы проверить перейдите в исходный код и найдите переменную `ajaxurl`.
* ID поста для свойства `postId` теперь у нас содержится в недавно нами созданной переменной `postId`
* `action` у нас теперь будет только один, поэтому ставим `kmz_del`
* В функции `beforeSend:` мы должны скрыть крестик и показать лоадер
* В функции `success:` мы скрываем лоадер и в элемент `li` выводим ответ

*wp-content/plugins/kmz-favorite-posts/js/admin-script.js*

```js
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
```

Наш AJAX запрос будет обрабатываться тот же функцией что мы создали ранее `kmz_del_favorite()`, так что повторно ничего писать уже не нужно.

## Очистить список избранного

Мы создадим кнопку ниже списка избранных постов в виджете в консоли WordPress по клику на которую будет происходить удаление всех избранных записей.

Добавим кнопку **Delete All** и изображение лоадер в функцию:

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
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
```

В файле скриптов мы обрабатываем клик по кнопке и создаём необходимые переменные:

*wp-content/plugins/kmz-favorite-posts/js/admin-script.js*

```js
$('.kmz-favorites-del-all button').click(function(e){
    e.preventDefault();
    if (!confirm("Do you really want to delete this post?")) return;
    var current = $(this);
        loader = current.next();
        parent = current.parent();
        list = parent.prev();
    console.log(loader);
    console.log(parent);
    console.log(list);
});
```

Скопируем и изменим созданный ранее AJAX запрос

* Меняем `action:` на `kmz_del_all`
* `postId` нам уже не нужен - мы его убираем
* Перед отправкой запроса в функции `beforeSend()` мы скрываем кнопку и отображаем лоадер
* В случае успешного выполнениния AJAX запроса мы скрываем лоадер и выведем ответ с помощью метода `html()`.

*wp-content/plugins/kmz-favorite-posts/js/admin-script.js*

```js
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
                parent.html(res);
            });
        },
        error: function(){
            alert("Error AJAX");
        }
    });
});
```

Добавляем функцию `kmz_del_all()` в котором пока что просто будем выводить POST данные и завершать выполнение скрипта

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
function kmz_del_all(){
    if(!wp_verify_nonce( $_POST['security'], 'kmz-favorites' )){
        wp_die("Security error!");
    }
    print_r($_POST);
    wp_die();
}

add_action( 'wp_ajax_kmz_del_all', 'kmz_del_all' );
```

Если результат выводится, тогда двигаемся дальше. А дальше нам нужно получить текущего пользователя и удалить все метаданные. Ранее мы использовали `delete_user_meta()` которая удаляла одну запись, теперь мы будем использовать `delete_metadata()`, которой необходимо передать:

* `meta_type` - тип объекта с котором мы будем работать, в нашем случае это `user`.
* `object_id` - ID объекта, в нашем случае это ID пользователя, который у нас имеется в переменной `$user`
* `meta_key` - название ключа мета данных

Функция `delete_metadata()` возвращает либо `true` в случае успеха, либо `false` в случае неудачи, поэтому мы можем использовать данную функцию в условии.

*wp-content/plugins/kmz-favorite-posts/kmz-favorite-posts.php*

```php
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
```

Теперь в скрипте нам нужно изменить функцию `success`:

* Если результат запроса возвращает `List empty` тогда выводить этого сообщение и плавно скрываем список постов
* Иначе выводим во всплывающем окне сообщение в случае неудачи и снова отображаем кнопку **Delete All**.

*wp-content/plugins/kmz-favorite-posts/js/admin-script.js*

```js
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
```