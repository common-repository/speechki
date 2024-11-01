<?php
/*
* Plugin Name: Плагин сервиса Retell
* Description: Плагин для быстрого подключения сайта к сервису Retell
* Author:      Retell.cc
* Version:     0.3
*/

function speeckiKvark_addPalyerLocation($content)
{
    $selected_post_types = get_option('selected_post_type');
    $player_location = get_option('player_location');
    $script = '<script data-voiced="player">!function(e,n,i,t,o,c,r,s){if(void 0!==e[t])return c();r=n.createElement(i),s=n.getElementsByTagName(i)[0],r.id=t,r.src="https://widget.speechki.org/js/common.min.js",r.async=1,s.parentNode.insertBefore(r,s),r.onload=c}(window,document,"script","Speechki",0,function(){Speechki.init()});</script>';

    $post_types = array();
    foreach ($selected_post_types as $selected_post_type) {
        $post_types[] = $selected_post_type;
    }

    if(is_singular($post_types)) {

        if($player_location == 'before_content') {
            $content = $script.$content;
        } elseif($player_location == 'after_content') {
            $content .= $script;
        }

    }
    return $content;
}

$player_location = get_option('player_location');

if(!empty($player_location)) {
    add_filter('the_content', 'speeckiKvark_addPalyerLocation');
}

function speeckiKvark_addSpeechkiMetadata()
{
    $accept_code = get_option('accept_code');
  ?>
    <meta name="getalice-verification" content="<?=$accept_code?>" />
  <?php
}

function speeckiKvark_addSpeechkiPlayerShortcode()
{
     return '<script data-voiced="player">!function(e,n,i,t,o,c,r,s){if(void 0!==e[t])return c();r=n.createElement(i),s=n.getElementsByTagName(i)[0],r.id=t,r.src="https://widget.speechki.org/js/common.min.js",r.async=1,s.parentNode.insertBefore(r,s),r.onload=c}(window,document,"script","Speechki",0,function(){Speechki.init()});</script>';
}

add_shortcode('SpeechkiPlayer', 'speeckiKvark_addSpeechkiPlayerShortcode');


$accept_code = get_option('accept_code');

if(!empty($accept_code)) {
    add_action( 'wp_head', 'speeckiKvark_addSpeechkiMetadata' );
}

add_action( 'admin_enqueue_scripts', 'speeckiKvark_addSpeechkiAsets');

function speeckiKvark_addSpeechkiAsets() {
        wp_register_style('speechki-style', plugin_dir_url( __FILE__ ).'css/style.css');
        wp_enqueue_style('speechki-style');
}

add_action( 'admin_enqueue_scripts', 'speeckiKvark_addSpeechkiScripts' );
function speeckiKvark_addSpeechkiScripts($hook) {
	wp_enqueue_script( 'ajax-script', plugins_url( '/js/script.js', __FILE__ ), array('jquery') );
	wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ),
                        'nonce_for_getting_tokken' => wp_create_nonce('wp_ajax_speeckiKvark_getTemporaryToken'),
                        'nonce_for_saving_settings' => wp_create_nonce('speeckiKvark_SaveSettings'))
                        );
}


add_action( 'init', 'speeckiKvark_addSpeechkiFeed' );

function speeckiKvark_addSpeechkiFeed() {
    add_feed( 'speechki', 'speeckiKvark_speechki_feed_markup' );
}

function speeckiKvark_speechki_feed_markup()
{

    header( 'Content-Type: application/rss+xml' );

    $selected_post_types = get_option('selected_post_type');

    $post_type = array();
    foreach ($selected_post_types as $selected_post_type) {
        $post_types[] = $selected_post_type;
    }

    $args = array(
      'numberposts' => -1,
      'post_type'   => $post_types,
      'post_status'    => 'publish'
    );

    $posts = get_posts($args);

    $rss = '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?><rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/">';

    $rss .= '<channel>';

        $rss .= '<title>' . get_bloginfo('name') . '</title>';
        $rss .= '<link>' . get_bloginfo('url') . '</link>';
        $rss .= '<description>' . get_bloginfo('description') . '</description>';

    //    $rss .= '<lastBuildDate>Mon, 06 Jan 2020 09:56:27 +0000</lastBuildDate>'; //!!!!!!!!!!!!!!!!!!

        $rss .= '<language>' . get_bloginfo('language') . '</language>';

        foreach ($posts as $post) {
            $rss .= '<item>';

                $rss .= '<title>' . $post->post_title . '</title>';
                $rss .= '<link>' . get_permalink($post->ID) . '</link>';

                // <comments>https://devstages.ru/pravilnyj-ajax-dlya-wordpress/#respond</comments> !!!!!!!!!!!!!
                $date = new DateTime($post->post_date);
                $rss .= '<pubDate>' . $date->format(DateTime::RFC822) . '</pubDate>';

                $rss .= '<guid isPermaLink="false">' . $post->guid .'</guid>';

                $content = apply_filters('the_content', $post->post_content);

                $rss .= '<content:encoded><![CDATA[' . $content . ']]></content:encoded>';

            $rss .= '</item>';
        }

    $rss .= '</channel>';
    $rss .= '</rss>';

    echo $rss;

}

function ds_speechki_settings_init()
{
    register_setting(
        'ds_speechki_options',
        'ds_speechki_options',
        'ds_speechki_options_validate'
    );

    add_settings_section(
        'ds_speechki_get_api',
        'Получение ключа API',
        'ds_speechki_get_api_desc',
        'ds_speechki'
    );

    add_settings_section(
        'ds_speechki_apisetting',
        'Настройка сервиса',
        'ds_speechki_apisetting_desc',
        'ds_speechki'
    );

    add_settings_field(
        'ds_speechki_email',
        'Email, указанный при регистрации',
        'ds_speechki_email_field',
        'ds_speechki',
        'ds_speechki_get_api'
    );
    add_settings_field(
        'ds_speechki_pass',
        'Пароль, указанный при регистрации',
        'ds_speechki_pass_field',
        'ds_speechki',
        'ds_speechki_get_api'
    );

    register_setting( 'ds_speechki', 'accept_code');

    // update_option('accept_code', '');

    register_setting( 'ds_speechki', 'token');
    register_setting( 'ds_speechki', 'selected_post_type');
    register_setting( 'ds_speechki', 'adapted_text_field');
    register_setting( 'ds_speechki', 'player_location');

}

function ds_speechki_accept_code_field()
{
    $options = get_option('ds_speechki_options');
    $authorbox = (isset($options['accept_code'])) ? $options['accept_code'] : '';
    $authorbox = esc_textarea($authorbox);
    echo '<input id="accept_code" type="hidden" name="ds_speechki_options[accept_code]" class="regular-text code" value="'.$authorbox.'">';
}

function ds_speechki_email_field()
{
    $options = get_option('ds_speechki_options');
    $authorbox = (isset($options['email'])) ? $options['email'] : '';
    $authorbox = esc_textarea($authorbox);
    echo '<input id="email" type="email" name="ds_speechki_options[email]" class="regular-text code" value="'.$authorbox.'">';
}

function ds_speechki_pass_field()
{
    $options = get_option('ds_speechki_options');
    $authorbox = (isset($options['pass'])) ? $options['pass'] : '';
    $authorbox = esc_textarea($authorbox);
    echo '<input id="pass" type="password" name="ds_speechki_options[pass]" class="regular-text code" value="'.$authorbox.'">';
}

function ds_speechki_options_validate($input)
{
    global $allowedposttags, $allowedrichhtml;

    if (isset($input['apikey'])) {
        $input['apikey'] = wp_kses_post($input['apikey']);
    }

    return $input;
}

add_action('admin_init', 'ds_speechki_settings_init');

function ds_speechki_get_api_desc() {
    echo "<p>Вам необходимо получть ключ API<p><p>Для этого перейдите на сайт и зарегистрируйтесь</p><p>После чего заполните поля авторизации и нажмите кнопку \"Сохранить и получить ключ\"</p>";
}

function ds_speechki_apisetting_desc() {
    echo "<p>Ваш API ключ</p>";
}

function ds_speechki_admin_menu_setup()
{
    add_submenu_page(
        'options-general.php',
        'Настройки плагина Retell',
        'Retell',
        'manage_options',
        'ds_speechki',
        'ds_speechki_admin_page_screen'
    );
}

add_action('admin_menu', 'ds_speechki_admin_menu_setup');

function ds_speechki_admin_page_screen()
{
    $start_form_class = '';
    $settings_form_class = 'speechki_hide';
    if(get_option('accept_code') != '') {
        $start_form_class = 'speechki_hide';
        $settings_form_class = '';
    }

    global $submenu;
    $page_data = array();
    foreach ($submenu['options-general.php'] as $i => $menu_item) {
        if ($submenu['options-general.php'][$i][2] == 'ds_speechki') {
            $page_data = $submenu['options-general.php'][$i];
        }
    }
    ?>
    <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php echo $page_data[3]; ?></h2>
        <form id="ds_speechki_options" class="<?=$start_form_class;?>" action="options.php" method="post">
            <p>Перейдите на сайт <a target="_blank" href="https://console.retell.cc/">console.retell.cc</a> и зарегистрируйтесь.</p>
            <p>Введите ваш логин и пароль ниже.</p>
            <p>Нажмите на кнопку «Авторизоваться»</p>
            <?php
            settings_fields('ds_speechki_options');
            do_settings_fields('ds_speechki', 'ds_speechki_get_api');
            ?>
            <button id="get_api_key" class="button button-primary">Авторизоваться</button>
        </form>

        <?php
            $args = array(
               'public'   => true,
               // '_builtin' => false
            );

            $output = 'objects';
            // $output = 'names';
            $operator = 'and';

            $post_types = get_post_types( $args, $output, $operator );

            $selected_post_types = get_option('selected_post_type');
            $adapted_text_field = get_option('adapted_text_field');
            $player_location = get_option('player_location');

            $selected = '';
        ?>

        <form id="ds_speechki_save_options" class="<?=$settings_form_class;?>" action="" method="post">
            <p>1. Выберите тип страниц, которые будут озвучиваться Retell</p>

            <select id="select_post_types" size="4" multiple name="select_post_types">
                <?php
                    foreach ($post_types as $key => $post_type) {
                        if(in_array($post_type->name, $selected_post_types)) {
                            $selected = 'selected';
                        } else {
                            $selected = '';
                        }
                        if ($key != 'attachment') {
                            echo '<option '. $selected .' value="'. $post_type->name .'">'. $post_type->label .'</option>';
                        }
                    }
                ?>
            </select>

            <p>2. Выберите место размещения плеера Retell</p>
            <select name="player_location" id="player_location">
                <option <?php if($player_location == 'before_content') echo 'selected' ?> value="before_content">Разместить перед описанием поста</option>
                <option <?php if($player_location == 'after_content') echo 'selected' ?> value="after_content">Разместить после описания поста</option>
                <option <?php if($player_location == 'shortcode') echo 'selected' ?> value="shortcode">Шорткод</option>
            </select>
            <p class="under-player-location">Либо выберите опцию "Шорткод" и воспользуйтесь шорткодом [RetellPlayer], установив плеер в удобное для вас место.</p>

            <p>Консоль управления: <a target="_blank" href="https://console.retell.cc/">console.retell.cc</a></p>
            <button id="save_settings_api" class="button button-primary">Сохранить</button>
        </form>
    </div>
    <?php
}

function addSpeechkiSettingsLink($links)
{
    $settings_link = '<a href="options-general.php?page=ds_speechki">Настройки</a>';
    array_unshift( $links, $settings_link );
    return $links;
}



$plugin_file = plugin_basename(__FILE__);
add_filter( "plugin_action_links_$plugin_file", 'addSpeechkiSettingsLink' );

include_once (plugin_dir_path( __FILE__ ).'API/API.php');
