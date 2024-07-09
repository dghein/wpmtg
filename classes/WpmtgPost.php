<?php

namespace Wpmtg;

use Wpmtg\WpmtgApiHelper;

class WpmtgPost
{
    public function registerWpmtgPostType()
    {
        /**
         * Register the custom post-type for individual magic cards
         */
        register_post_type(
            'wpmtg_magiccard',
            array(
                'labels'      => array(
                    'name'          => __('Magic Cards', 'wpmtg'),
                    'singular_name' => __('Magic Card', 'wpmtg'),
                ),
                'public'      => true,
                'has_archive' => 'cards',
                'rewrite' => array(
                    'slug' => 'card/%wpmtg_card_setname%',
                    'with_front' => false
                ),
                'supports' => array('title', 'thumbnail', 'custom-fields'),
                'capability_type' => 'post',
                'capabilities' => array(
                    'create_posts' => 'do_not_allow', // Removes support for the "Add New" function
                ),
                'map_meta_cap' => true
            )
        );

        /**
         * Register card set taxonomy to categories individual cards
         * https://developer.wordpress.org/reference/functions/register_taxonomy/
         */
        register_taxonomy(
            'wpmtg_card_setname',
            'wpmtg_magiccard',
            array(
                'labels' => array(
                    'name' => 'Card Sets',
                    'singular_name' => 'Card Set',
                    'search_items' => 'Search Card Sets',
                    'popular_items' => 'Popular Card Sets',
                    'all_items' => 'All Card Sets',
                    'edit_item' => 'Edit Card Set',
                    'view_item' => 'View Card Set',
                    'update_item' => 'Update Card Set',
                    'add_new_item' => 'Add New Card Set'
                ),
                'rewrite' => array(
                    'slug' => 'set',
                    'with_front' => false
                ),
                'query_var' => 'wpmtg_card_setname',
                'show_in_nav_menus' => true
            )
        );
    }

    /**
     * Create the terms for Magic sets
     *
     * @return void
     */
    public function createCardSetTerms()
    {
        $allSets = WpmtgApiHelper::getCardSets();
        
        if ($allSets) {
            foreach ($allSets->data as $set) {
                wp_insert_term($set->name, 'wpmtg_card_setname');
            }
        }
    }

    /**
     * CUSTOM FIELDS: Set up metaboxes for Card post type
     * https://wptheming.com/2010/08/custom-metabox-for-post-type/
     * https://www.mugo.ca/Blog/Adding-complex-fields-to-WordPress-custom-post-types
     */
    public function magiccardCustomFields()
    {
        add_meta_box(
            'wpmtg_magiccard_custom_fields',
            __('Card Details'),
            [$this, 'magiccardRenderCustomFields'],
            'wpmtg_magiccard'
        );
    }

    /**
     * CUSTOM FIELDS: Render fields inside the "Card Details" metabox
     */
    public function magiccardRenderCustomFields()
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
     * Undocumented function
     *
     * @param [type] $post_link
     * @param [type] $post
     * @return void
     */
    public function magiccardPermalinkStructure($post_link, $post)
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

    /**
     * Disable Custom Fields Metabox
     * https://developer.wordpress.org/reference/functions/remove_meta_box/
     */
    public function hideCustomFieldsMetabox()
    {
        remove_meta_box('postcustom', 'wpmtg_magiccard', 'normal');
    }

    /**
     * Append custom field data from WPMTG Magic Card custom post type for front-end display in theme
     *
     * @param [type] $content
     * @return void
     */
    public function appendCardToPost($content)
    {
        global $post;

        if ($post->post_type === 'wpmtg_magiccard') {
            $postmeta = get_post_meta($post->ID);
            $postmeta['post_title'] = $post->title;

            $content = $this->appendCardInfoToPost($content, $postmeta);
        }

        return $content;
    }

    /**
     * Compile markup for front-end display of card information
     *
     * @param [type] $content
     * @param [type] $postmeta
     * @return void
     */
    private function appendCardInfoToPost($content, $postmeta)
    {
        $content .= wpautop($postmeta['card_text'][0]);

        $content .= '<ul class="wpmtg-magiccard__card-info">';
        $content .= isset($postmeta['artist']) ? '<li>Artist: ' . $postmeta['artist'][0] . '</li>' : '';
        $content .= isset($postmeta['colors']) ? '<li>Colors: ' . $postmeta['colors'][0] . '</li>' : '';
        $content .= isset($postmeta['mana_cost']) ? '<li>Mana Cost: ' . $postmeta['mana_cost'][0] . '</li>' : '';
        $content .= isset($postmeta['rarity']) ? '<li>Rarity: ' . $postmeta['rarity'][0] . '</li>' : '';
        $content .= isset($postmeta['type']) ? '<li>Type: ' . $postmeta['type'][0] . '</li>' : '';
        $content .= isset($postmeta['set_name']) ? '<li>Set: ' . $postmeta['set_name'][0] . '</li>' : '';
        $content .= isset($postmeta['released']) ? '<li>Released: ' . $postmeta['released'][0] . '</li>' : '';
        $content .= isset($postmeta['tcgplayer_purchase_uri']) ? '<li>Buy on TCGPlayer: ' . $postmeta['tcgplayer_purchase_uri'][0] . '</li>' : '';
        $content .= '</ul>';

        return $content;
    }
}
