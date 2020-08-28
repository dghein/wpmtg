<?php

/*
Plugin Name:  wpmtg
Description:  A Magic: The Gathering plugin
Plugin URI:   https://dustinsmodern.life
Author:       Dustin Hein
Version:      0.1.1
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/

// disable direct file access
if (!defined('ABSPATH')) {
    exit;
}

register_activation_hook(__FILE__, 'wpmtg_activate');
register_deactivation_hook(__FILE__, 'wpmtg_deactivate');

/**
 * Plugin Deactivation
 * - Blows database table(s) away
 */
function wpmtg_deactivate()
{
    // (https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/)
}

/**
 * Plugin Activation
 * Registers custom post type and associated taxonomy
 * Calls function to hit Scryfall API to populate posts
 */
function wpmtg_activate()
{
    // Regiastered on 'init' hook but also called here as per this example:
    // https://stackoverflow.com/questions/50810282/create-custom-post-type-when-plugin-is-activated-and-remove-once-deactivated-wo
    wpmtg_register_card_post_type();

    // get card data from Scryfall
    wpmtg_get_cards_from_api();
}

/**
 * Get card data from Scryfall and save card information to posts
 */
function wpmtg_get_cards_from_api($set = 'lea')
{
    $set_json = wpmtg_fetch_scryfall_cards('https://api.scryfall.com/cards/search?q=set=' . $set);

    // make posts for each card
    wpmtg_save_card_data($set_json);

    // see if there are more results we want beyond the first
    $has_more = $set_json->has_more;

    if ($has_more) {
        $next_page  = $set_json->next_page;
        $more_set_json = wpmtg_fetch_scryfall_cards($next_page);
        wpmtg_save_card_data($more_set_json);
    }
}

/**
 * Does a Curl request to a Srcyfall endpoint
 */
function wpmtg_fetch_scryfall_cards($endpoint)
{
    // initialize curl and specify API endpoint
    $ch = curl_init();

    // set curl options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string instead of outputting it
    curl_setopt($ch, CURLOPT_URL, $endpoint); // Set the url

    // execute & close
    $result = curl_exec($ch);
    curl_close($ch);

    // convert to json object
    $set_json = json_decode($result);

    return $set_json;
}

/**
 * Takes JSON data retrieved from Scryfall and inserts into posts
 * This happens when the plugin is activated and may be called multiple times
 */
function wpmtg_save_card_data($set_data)
{
    global $user_ID;

    // make a post for each card
    foreach ($set_data->data as $card_data) {
        $post_data = array(
            'post_author' => $user_ID,
            'post_date' => date('Y-m-d H:i:s'),
            'post_title' => $card_data->name,
            'post_content' => $card_data->oracle_text,
            'post_status' => 'publish',
            'post_type' => 'wpmtg_magiccard',
            'supports' => array('thumbnail')
        );

        $new_post = wp_insert_post($post_data);
        wp_set_object_terms($new_post, $card_data->set, 'wpmtg_card_setname');

        // get thumbnail image and then assign to post
        $thumbnail = wpmtg_fetch_card_thumbnails($card_data, 'card_image_sm');
        set_post_thumbnail($new_post, $thumbnail);
    }

    flush_rewrite_rules();
}

/**
 * Make menu item(s) in the WP Admin
 */
function wpmtg_create_admin_menu_item()
{
    add_menu_page('WPMTG', 'WPMTG', 'manage_options', 'wpmtg', 'wpmtg_options_page');
}
add_action('admin_menu', 'wpmtg_create_admin_menu_item');

/**
 * Options page content with lots of useful things
 * Current List of Useful Things:
 * - Button to fetch card images and store locally in uploads directory
 */
function wpmtg_options_page()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // check to see if we're downloading cards, and if we are, download them
    if (isset($_POST['set']) && !empty($_POST['set'])) {
        wpmtg_get_cards_from_api($_POST['set']);
    }

    // the back-end form used to download card images
    echo '<div class="wrap">';
    echo '  <form action="" method="post">';
    echo '    <input type="text" name="set">';
    echo '    <input type="submit" value="Import Set Data">';
    echo '  </form>';
    echo '</div>';
}

/**
 * Transfers images from remote url to uploads directory
 */
function wpmtg_fetch_card_thumbnails($card, $thumbnail_size)
{
    // figure out where images are going to be stored
    $upload_dir = wp_upload_dir();

    // wpmtg base uploads folder /uploads/wpmtg
    $base_dirname = $upload_dir['basedir'] . '/' . "wpmtg";

    // make directory if it doesn't exist
    if (!file_exists($base_dirname)) {
        wp_mkdir_p($base_dirname);
    }

    // retrieve card iamges from remote and write to local file system
    $card_remote_uri = $card->image_uris->normal;
    $card_set = $card->set;

    $ch = curl_init($card_remote_uri);

    // generate a nice-ish filename and start doing file system stuff
    // wpmtg base uploads folder /uploads/wpmtg/*CARD SET*/*THUMBNAIL SIZE*/
    $base_set_dirname = $upload_dir['basedir'] . '/' . 'wpmtg' . '/' . $card_set . '/' . $thumbnail_size;
    $card_nicename = $card_set . '_' . str_replace([' ', '\'', ','], ['_', '', ''], $card->name)  . '.jpg'; // card slug
    $card_nicename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $card_nicename);
    $card_image_path = $base_set_dirname . '/' . $card_nicename; // full path including filename

        // make directory if it doesn't exist
    if (!file_exists($base_set_dirname)) {
        wp_mkdir_p($base_set_dirname);
    }

    $fp = fopen($card_image_path, 'wb');

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FILE, $fp);

    $result = curl_exec($ch);

    $wp_filetype = wp_check_filetype($card_image_path, null);

    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($card_image_path),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $card_image_path);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $card_image_path);
    $attachment = wp_update_attachment_metadata($attach_id, $attach_data);

    curl_close($ch);
    fclose($fp);
    return $attach_id;
}

function wpmtg_register_card_post_type()
{
    register_post_type(
        'wpmtg_magiccard',
        array(
            'labels'      => array(
                'name'          => __('Cards', 'wpmtg'),
                'singular_name' => __('Card', 'wpmtg'),
            ),
                'public'      => true,
                'has_archive' => 'cards',
                'rewrite' => array(
                    'slug' => 'card/%wpmtg_card_setname%',
                    'with_front' => false
                ),
                'supports' => array('title', 'editor', 'thumbnail')
        )
    );

    register_taxonomy(
        'wpmtg_card_setname',
        'wpmtg_magiccard',
        array(
            'rewrite' => array(
                'slug' => 'set',
                'with_front' => false
            )
        )
    );
}
add_action('init', 'wpmtg_register_card_post_type');

/**
 * Add wpmtg_card_setname taxonomy term to permalink structure
 * https://wisdmlabs.com/blog/add-taxonomy-term-custom-post-permalinks-wordpress/
 */
function wpmtg_magiccard_permalink_structure($post_link, $post)
{
    if (false !== strpos($post_link, '%wpmtg_card_setname%')) {
        $projectscategory_type_term = get_the_terms($post->ID, 'wpmtg_card_setname');
        if (!empty($projectscategory_type_term)) {
            $post_link = str_replace('%wpmtg_card_setname%', array_pop($projectscategory_type_term)->
            slug, $post_link);
        } else {
            $post_link = str_replace('%wpmtg_card_setname%', 'uncategorized', $post_link);
        }
    }
    return $post_link;
}
add_filter('post_type_link', 'wpmtg_magiccard_permalink_structure', 10, 4);
