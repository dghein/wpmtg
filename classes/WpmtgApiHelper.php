<?php

namespace Wpmtg;

/**
 * Used to request and handle data retrieved from Scryfall API.
 */
class WpmtgApiHelper
{
    /**
     * Get card data from Scryfall and save card information to posts
     *
     * @param [type] $set
     * @return void
     */
    public static function fetchScryfallDataAndSave($set)
    {
        if (!$set) {
            return false;
        }

        $set_json = self::fetchScryfallData('https://api.scryfall.com/cards/search?q=set=' . $set);

        // make posts for each card
        $newCards = self::saveCardData($set_json);

        // see if there are more results we want beyond the first
        $has_more = $set_json->has_more;

        if ($has_more) {
            $next_page  = $set_json->next_page;
            $more_set_json = self::fetchScryfallData($next_page);
            $newCards += self::saveCardData($more_set_json);

            // not likely for a single set... probably a more elegant way to go about this
            $has_even_more = $more_set_json->has_more;

            if ($has_even_more) {
                $next_page  = $more_set_json->next_page;
                $even_more_set_json = self::fetchScryfallData($next_page);
                $newCards += self::saveCardData($even_more_set_json);
            }
        }

        return $newCards;
    }

    /**
     * Get card data from API endpoint
     *
     * @param string $endpoint
     * @return object|null $set_json
     */
    public static function fetchScryfallData($endpoint)
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
    private static function saveCardData($set_data)
    {
        global $user_ID;

        $postsCreated = 0;

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

                if ($new_post) {
                    $postsCreated++;
                }

                // Create set taxonomy term if it doesn't already exist
                if(!term_exists($card_data->set, 'wpmtg_card_setname')) {
                    wp_insert_term($card_data->set_name, 'wpmtg_card_setname', array(
                        'slug' => $card_data->set
                    ));
                }

                // Define taxonomy term using 3-letter set abbreviation
                wp_set_object_terms($new_post, $card_data->set, 'wpmtg_card_setname');

                // get thumbnail images and then assign to the post
                $card_image = self::fetchCardThumbnails($card_data, 'normal');

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

        return $postsCreated;
    }

    /**
     * Transfers images from remote url to WP uploads directory and attach to post as featured image
     */
    private static function fetchCardThumbnails($card, $thumbnail_size)
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

            if (!$result) {
                echo "<pre>";
                var_dump(curl_error($ch));
                echo "</pre>";
            }
    
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
     * Undocumented function
     *
     * @param string $endpoint
     * @return void
     */
    public static function getCardSets()
    {
        $endpoint = 'https://api.scryfall.com/sets';
        $setData = self::fetchScryfallData($endpoint);

        return $setData;
    }

    public function doApiThings()
    {
        $newCards = $this->fetchScryfallDataAndSave($_REQUEST['set']);

        $data = '<div> ✔️ Successfully imported ' . $newCards . ' cards!</div>';
        echo $data;
        die;
    }
}
