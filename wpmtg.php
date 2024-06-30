<?php

/*
Plugin Name:  wpmtg
Description:  A Magic: The Gathering plugin
Plugin URI:   https://dustinsmodern.life
Author:       Dustin Hein
Version:      0.1.20
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/

// disable direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Register the autoloader for the plugin.
include_once __DIR__ . '/autoloader.php';

use Wpmtg\Wpmtg;

$wpmtg = new Wpmtg();

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

        // not likely for a single set... probably a more elegant way to go about this
        $has_even_more = $more_set_json->has_more;

        if ($has_even_more) {
            $next_page  = $more_set_json->next_page;
            $even_more_set_json = wpmtg_fetch_scryfall_cards($next_page);
            wpmtg_save_card_data($even_more_set_json);
        }
    }
}

/**
 * Does a Curl request to a Srcyfall endpoint
 */
function wpmtg_fetch_scryfall_cards($endpoint)
{
    // Make the request to the API endpoint
    $response = wp_remote_get($endpoint);

    // Check for any errors in the response
    if (is_wp_error($response)) {
        return null;
    }

    // Get the body of the response
    $body = wp_remote_retrieve_body($response);

    // Convert the body to a JSON object
    $set_json = json_decode($body);

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
        // error check for existing post of type `wpmtg_magiccard` of the same name
        // if duplicate exists, check set taxonomy. if that also matches, then
        // don't insert the post because it will be a duplicate of an existing post
        $post_exists = post_exists($card_data->name, '', '', 'wpmtg_magiccard');

        if ($post_exists) {
            // check for that post's taxonomy term
            $terms = wp_get_object_terms($post_exists, 'wpmtg_card_setname');

            if ($terms[0]->slug === $card_data->set) {
                // skip this iteration of foreach loop
                continue;
            }
        } else {
            $post_data = array(
                'post_author' => $user_ID,
                'post_date' => date('Y-m-d H:i:s'),
                'post_title' => $card_data->name,
                'post_status' => 'publish',
                'post_type' => 'wpmtg_magiccard',
                'supports' => array('thumbnail')
            );

            $new_post = wp_insert_post($post_data);

            // Define taxonomy term using 3-letter set abbreviation
            wp_set_object_terms($new_post, $card_data->set_name, 'wpmtg_card_setname');

            // get thumbnail images and then assign to the post
            $card_image = wpmtg_fetch_card_thumbnails($card_data, 'png');

            // check if double-sided
            !empty($card_data->card_faces) ? $double_sided = true : $double_sided = false;

            set_post_thumbnail($new_post, $card_image);
            $card_image_path = wp_get_attachment_url($card_image);

            // populate custom fields
            add_post_meta($new_post, 'artist', $card_data->artist);
            add_post_meta($new_post, 'card_image', $card_image_path);

            if (!$double_sided) {
                add_post_meta($new_post, 'card_text', $card_data->oracle_text);
            } else {
                $card_text = $card_data->card_faces[0]->oracle_text . '<br>' . $card_data->card_faces[1]->oracle_text;
                add_post_meta($new_post, 'card_text', $card_text);
            }

            if (isset($card_data->mana_cost)) {
                add_post_meta($new_post, 'mana_cost', $card_data->mana_cost);
            }

            add_post_meta($new_post, 'rarity', $card_data->rarity);
            add_post_meta($new_post, 'set', $card_data->set);
            add_post_meta($new_post, 'set_name', $card_data->set_name);
            add_post_meta($new_post, 'type', $card_data->type_line);
            add_post_meta($new_post, 'released', $card_data->released_at);

            // flavor text may or may not exist
            $flavor_text = isset($card_data->flavor_text);
            $flavor_text ? add_post_meta($new_post, 'flavor_text', $card_data->flavor_text) : '';

            // card color(s)
            if (!empty($card_data->colors)) {
                $colors = implode('', $card_data->colors);
                add_post_meta($new_post, 'colors', $colors);
            }

            // tcgplayer purchase uri
            $flavor_text = isset($card_data->purchase_uris->tcgplayer);
            $flavor_text ? add_post_meta($new_post, 'tcgplayer_purchase_uri', $card_data->purchase_uris->tcgplayer) : '';

            // Yoast SEO
            $meta_description = $card_data->name . ' from ' . $card_data->set_name . ', released for Magic: The Gathering ' . $card_data->released_at;
            update_post_meta($new_post, '_yoast_wpseo_metadesc', $meta_description);
        };
    }

    flush_rewrite_rules();
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

    $card_set = $card->set;

    $double_sided = false;

    // card faces means either double-sided card or story card.
    // having multiple card faces does not necessarily mean there are two sides or two separate images
    // note: double sided card comes with property: "layout": "modal_dfc"
    if (!empty($card->card_faces) && is_array($card->card_faces)) {
        if (isset($card->card_faces[0]->image_uris) && isset($card->card_faces[1]->image_uris)) {
            // front face card image
            $card_front = $card->card_faces[0]->image_uris->$thumbnail_size;
            // back face card image
            $card_back = $card->card_faces[1]->image_uris->$thumbnail_size;

            $double_sided = true;
            $card_remote_uri = [$card_front, $card_back];
        } else {
            $card_remote_uri = [$card->image_uris->$thumbnail_size];
        }
    } else {
        $card_remote_uri = [$card->image_uris->$thumbnail_size];
    }

    // retrieve card images from remote and write to local file system
    $i = 0; // counter because of sloppy coding :P

    foreach ($card_remote_uri as $card_face) {
        $ch = curl_init($card_face);

        // generate a nice-ish filename and start doing file system stuff
        // wpmtg base uploads folder /uploads/wpmtg/*CARD SET*/*THUMBNAIL SIZE*/
        $base_set_dirname = $upload_dir['basedir'] . '/' . 'wpmtg' . '/' . $card_set . '/' . $thumbnail_size;
        $image_pathinfo = pathinfo($card_face);
        $extension = preg_replace('/\?[0-9]+/', '', $image_pathinfo['extension']); // remove query string from end of filename
        if (!$extension) {
            $extension = 'png';
        }

        // name comes from somewhere else if double-sided
        if ($double_sided) {
            $card_name = $card->card_faces[$i]->name;
        } else {
            $card_name = $card_set . '_' . str_replace([' ', '\'', ',', '/'], ['_', '', '', ''], $card->name);
        }

        $card_nicename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $card_name);
        $card_nicername = $card_nicename . '.' . $extension;
        $card_image_path = $base_set_dirname . '/' . $card_nicername; // full path including filename

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

        $i++;
    }

    return $attach_id;
}

/**
 * Disable Custom Fields Metabox
 */
function wpmtg_hide_custom_fields_metabox()
{
    remove_meta_box('postcustom', 'wpmtg_magiccard', 'normal');
}
add_action('add_meta_boxes', 'wpmtg_hide_custom_fields_metabox');

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
        ini_set('max_execution_time', '300'); //300 seconds
        wpmtg_get_cards_from_api($_POST['set']);
    }

    // the back-end form used to download card images
    echo '<div class="wrap">';
    echo '  <form action="" method="post">';
    echo '    <input type="text" name="set" maxlength="3">';
    echo '    <input type="submit" value="Import Set Data">';
    echo '  </form>';
    echo '</div>';
}

// /**
//  * Provide front-end template for wpmtg_matgiccard custom post type
//  *
//  * @param [type] $template
//  * @return void
//  */
// function wpmtg_register_card_post_type_template($template)
// {
//     global $post;

//     if ('wpmtg_magiccard' === $post->post_type) {
//         return plugin_dir_path(__FILE__) . 'single-wpmtg_magiccard.php';
//     }

//     return $template;
// }
// add_filter('single_template', 'wpmtg_register_card_post_type_template');

/**
 * Add wpmtg_card_setname taxonomy term to permalink structure
 * https://wisdmlabs.com/blog/add-taxonomy-term-custom-post-permalinks-wordpress/
 */
function wpmtg_magiccard_permalink_structure($post_link, $post)
{
    if (false !== strpos($post_link, '%wpmtg_card_setname%')) {
        $projectscategory_type_term = get_the_terms($post->ID, 'wpmtg_card_setname');
        if (!empty($projectscategory_type_term)) {
            $post_link = str_replace('%wpmtg_card_setname%', array_pop($projectscategory_type_term)->slug, $post_link);
        } else {
            $post_link = str_replace('%wpmtg_card_setname%', 'uncategorized', $post_link);
        }
    }

    return $post_link;
}
add_filter('post_type_link', 'wpmtg_magiccard_permalink_structure', 10, 4);

/**
 * CUSTOM FIELDS: Set up metaboxes for Card post type
 * https://wptheming.com/2010/08/custom-metabox-for-post-type/
 * https://www.mugo.ca/Blog/Adding-complex-fields-to-WordPress-custom-post-types
 */
function wpmtg_magiccard_custom_fields()
{
    add_meta_box(
        'wpmtg_magiccard_custom_fields',
        __('Card Details'),
        'wpmtg_magiccard_render_custom_fields',
        'wpmtg_magiccard'
    );
}
add_action('add_meta_boxes', 'wpmtg_magiccard_custom_fields');

/**
 * CUSTOM FIELDS: Render fields inside the "Card Details" metabox
 */
function wpmtg_magiccard_render_custom_fields()
{
    global $post;

    // Nonce field to validate form request came from current site
    wp_nonce_field(basename(__FILE__), 'wpmtg_magiccard_custom_fields');

    // Get the card information, which should already be populated by the card import script
    $artist = get_post_meta($post->ID, 'artist', true);
    $card_image = get_post_meta($post->ID, 'card_image', true);
    $card_text = get_post_meta($post->ID, 'card_text', true);
    $colors = get_post_meta($post->ID, 'colors', true);
    $mana_cost = get_post_meta($post->ID, 'mana_cost', true);
    $rarity = get_post_meta($post->ID, 'rarity', true);
    $released = get_post_meta($post->ID, 'released', true);
    $set_name = get_post_meta($post->ID, 'set_name', true);
    $tcgplayer_purchase_uri = get_post_meta($post->ID, 'tcgplayer_purchase_uri', true);
    $type = get_post_meta($post->ID, 'type', true);

    echo '<label for="txtAreaCardText">' . __('Card Text') . '</label>';
    echo '<textarea id="txtAreaCardText" name="card_text" class="widefat" rows="6" readonly>' . esc_textarea($card_text) . '</textarea>';

    echo '<label for="txtSetName">' . __('Set Name') . '</label>';
    echo '<input type="text" id="txtSetName" name="set_name" value="' . esc_textarea($set_name)  . '" class="widefat" readonly>';

    echo '<label for="txtType">' . __('Card Type') . '</label>';
    echo '<input type="text" id="txtType" name="type" value="' . esc_textarea($type)  . '" class="widefat" readonly>';

    echo '<label for="txtColors">' . __('Card Colors') . '</label>';
    echo '<input type="text" id="txtColors" name="Colors" value="' . esc_textarea($colors)  . '" class="widefat" readonly>';

    echo '<label for="txtManaCost">' . __('Mana Cost') . '</label>';
    echo '<input type="text" id="txtManaCost" name="ManaCost" value="' . esc_textarea($mana_cost)  . '" class="widefat" readonly>';

    echo '<label for="txtRarity">' . __('Rarity') . '</label>';
    echo '<input type="text" id="txtRarity" name="rarity" value="' . esc_textarea($rarity)  . '" class="widefat" readonly>';

    echo '<label for="txtArtist">' . __('Artist') . '</label>';
    echo '<input type="text" id="txtArtist" name="artist" value="' . esc_textarea($artist)  . '" class="widefat" readonly>';

    echo '<label for="txtReleased">' . __('Released') . '</label>';
    echo '<input type="text" id="txtReleased" name="released" value="' . esc_textarea($released)  . '" class="widefat" readonly>';

    echo '<label for="txtTCGPlayerPurchaseURI">' . __('TCGPlayer Link') . '</label>';
    echo '<input type="text" id="txtTCGPlayerPurchaseURI" name="tcgplayer_purchase_uri" value="' . esc_textarea($tcgplayer_purchase_uri)  . '" class="widefat" readonly>';

    echo '<label for="txtCardImageUrl">' . __('Card Image URL') . '</label>';
    echo '<input type="text" id="txtCardImage" name="card_image" value="' . esc_textarea($card_image)  . '" class="widefat" readonly>';
}

/**
 * CUSTOM FIELDS: Save the metabox data
 */
function wpmtg_save_magiccard_meta($post_id, $post)
{

    if (!current_user_can('edit_post', $post_id)) {
        return $post_id;
    }

    // Verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times.
    if (!isset($_POST['set_name']) || !wp_verify_nonce($_POST['wpmtg_magiccard_custom_fields'], basename(__FILE__))) {
        return $post_id;
    }

    // Now that we're authenticated, time to save the data.
    // This sanitizes the data from the field and saves it into an array $events_meta.
    // NOTE: 08/29/2020 I'm disabling this because all  of the fields are read only.
    // In the future, we'll want to add some code here to be able to
    // save changes to custom fields that are added later down the road
    //$card_meta['set_name'] = esc_textarea($_POST['set_name']);

    // Cycle through the $card_meta array.
    // Note, in this example we just have one item, but this is helpful if you have multiple.
    foreach ($card_meta as $key => $value) :
        // Don't store custom data twice
        if ('revision' === $post->post_type) {
            return;
        }

        if (get_post_meta($post_id, $key, false)) {
            // If the custom field already has a value, update it.
            update_post_meta($post_id, $key, $value);
        } else {
            // If the custom field doesn't have a value, add it.
            add_post_meta($post_id, $key, $value);
        }

        if (!$value) {
            // Delete the meta key if there's no value
            delete_post_meta($post_id, $key);
        }
    endforeach;
}
add_action('save_post', 'wpmtg_save_magiccard_meta', 1, 2);

/**
 * Append custom field data from WPMTG Magic Card custom post type for front-end display in theme
 */
function wpmtg_append_card_to_post($content)
{
    global $post;

    if ($post->post_type === 'wpmtg_magiccard') {
        $postmeta = get_post_meta($post->ID);
        $postmeta['post_title'] = $post->title;
    
        $content = wpmtg_append_card_info_to_post($content, $postmeta);
    }

    return $content;
}
add_action('the_content', 'wpmtg_append_card_to_post');

function wpmtg_append_card_info_to_post($content, $postmeta)
{
    $content .= '<div class="wpmtg-magiccard__card-image">';
    $content .= '      <img src="' . $postmeta['card_image'][0] . '" alt="' . $postmeta['post_title'][0] . ' card image" class="wpmtg-magiccard__card-image__image">';
    $content .= '</div>';

    $content .= wpautop($postmeta['card_text'][0]);

    $content .= '<ul class="wpmtg-magiccard__card-info">';
    $content .= '    <li>Artist: ' . $postmeta['artist'][0] . '</li>';
    $content .= '    <li>Colors: ' . $postmeta['colors'][0] . '</li>';
    $content .= '    <li>Mana Cost: ' . $postmeta['mana_cost'][0] . '</li>';
    $content .= '    <li>Rarity: ' . $postmeta['rarity'][0] . '</li>';
    $content .= '    <li>Type: ' . $postmeta['type'][0] . '</li>';
    $content .= '    <li>Set: ' . $postmeta['set_name'][0] . '</li>';
    $content .= '    <li>Released: ' . $postmeta['released'][0] . '</li>';
    $content .= '    <li>Buy on TCGPlayer: ' . $postmeta['tcgplayer_purchase_uri'][0] . '</li>';
    $content .= '</ul>';

    return $content;
}
