<?php

namespace Wpmtg;

use Wpmtg\WpmtgApiHelper;

class WpmtgPost
{
    /**
     * Register the custom post-type for individual magic cards
     */
    public function registerWpmtgPostType()
    {
        // MTG logo admin menu-item icon
        $menuIcon = 'data:image/svg+xml;base64,' . base64_encode('<svg enable-background="new 0 0 600 600" version="1.1" viewBox="0 0 600 600" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
<path d="m601 237v364h-600v-600h600v236m-304.29-217.65c-41.528 0.72055-81.158 9.7956-118.45 28.063-122.42 59.974-184.06 195.26-149.21 326.97 4.7107 17.802 11.199 34.936 19.984 52.403 5.2651-4.6661 10.58-8.4899 14.798-13.28 12.955-14.711 18.526-33.186 24.933-51.169 0.44118-1.2383-0.22996-2.8996-0.47-4.3499-2.3601-14.26-6.0183-28.439-6.8859-42.787-3.4012-56.25 11.582-107.5 47.546-151.02 56.542-68.416 129.78-95.09 216.83-78.479 61.71 11.775 109.29 46.601 142.54 99.912 27.292 43.75 37.468 91.649 30.805 142.88-0.98633 7.5853-0.72882 14.576 2.0073 21.873 3.662 9.7659 6.6922 19.834 9.1257 29.98 3.8931 16.232 15.868 25.369 28.317 34.867 20.958-48.408 28.272-98.139 21.762-149.87-6.3757-50.664-24.862-96.548-56.57-136.48-57.662-72.613-133.37-108.89-227.07-109.52m-60.718 277.18c4.7815 9.1036 10.085 17.976 14.248 27.354 13.279 29.914 21.656 61.388 27.485 93.467 3.128 17.215 10.649 34.331 3.256 52.286 8.0168 4.0024 15.555 7.7661 22.586 11.276 4.8287-6.65 8.9782-13.292 14.039-19.145 11.228-12.986 25.8-21.976 39.802-31.522 4.2912-2.9257 4.9133-5.4837 3.433-10.084-6.2023-19.275-12.388-38.565-17.992-58.018-4.2743-14.836-6.1356-30.122-2.6622-45.383 1.5228-6.691 4.5899-13.091 7.4503-19.4 2.7489-6.0621 3.9612-11.635-1.4233-16.888-0.93869-0.91571-1.5764-2.1384-2.3617-3.2132-9.7464-13.34-18.435-13.853-29.622-1.906-2.2937 2.4495-5.0305 4.4842-7.6722 6.8062-0.9856-1.1315-1.4518-1.5909-1.8314-2.1133-24.287-33.421-46.246-68.228-62.83-106.2-1.5816-3.6209-3.3909-5.1694-7.6028-5.0612-9.6466 0.24802-19.312-0.18266-28.969-0.39278-18.981-0.41302-37.961-0.88615-56.943-1.2518-1.3841-0.026642-3.4404 0.14275-4.0661 1.0192-4.3815 6.1372-8.4687 12.485-12.908 19.152 4.5349 1.6539 8.2725 2.9984 11.997 4.3799 5.2797 1.9586 10.597 3.8274 15.806 5.9612 3.2658 1.3379 4.8202 3.8481 4.0222 7.5623-1.4265 6.6396-2.1979 13.479-4.2529 19.912-3.6846 11.534-7.9861 22.88-12.312 34.196-1.4156 3.7026-0.99315 5.8098 2.856 7.3904 3.9598 1.626 7.6575 3.8792 11.526 5.7403 4.1647 2.0034 8.2541 4.3203 12.63 5.6902 6.421 2.01 7.4174 1.3493 10.514-4.7716 2.4716-4.8847 4.51-10.005 7.2114-14.752 2.6448-4.6482 6.4922-5.4036 9.787-2.1134 12.365 12.348 25.032 24.45 34.801 40.019m108.29 220.38c-35.617 6.6329-70.894 5.6639-105.73-4.7934-31.711-9.5188-59.835-25.419-84.543-47.452-2.1291-1.8985-4.951-4.3336-7.4279-4.3076-12.605 0.13174-25.293 0.26755-37.776 1.8187-10.659 1.3246-20.714 5.3766-29.222 12.495 117.41 150.09 344.44 140.38 449.74-9.3951-19.801-7.1785-40.049-9.2536-60.902-5.7933-12.233 2.0298-23.05 6.2444-32.454 14.887-6.6636 6.124-14.706 10.858-22.49 15.631-21.165 12.977-44.14 21.487-69.19 26.91m156.4-170.02c-2.3952-5.8356-4.5883-11.764-7.2188-17.492-15.385-33.498-30.496-67.13-46.472-100.35-8.9533-18.616-9.2088-37.77-7.0919-57.505 0.15204-1.4173-0.77182-3.3798-1.8431-4.4248-4.9021-4.7816-10.721-3.4137-16.43-1.902-21.01 5.5627-41.958 4.0368-62.688-1.157-14.468-3.625-15.327-3.6447-22.664 9.5954-0.16092 0.29039-0.43698 0.60889-0.41376 0.89432 0.1402 1.7248-0.29819 4.2938 0.65076 5.0196 4.4423 3.3977 9.206 6.3969 13.984 9.3287 5.2185 3.2022 10.749 5.9213 15.818 9.3335 3.4814 2.3433 5.4312 5.9346 3.5532 10.325-2.9831 6.9733-5.5056 14.26-9.4155 20.689-4.1245 6.7818-9.7015 12.672-14.541 19.029-3.8554 5.0647-4.2679 9.2127-0.27994 14.045 4.1016 4.9706 8.8129 9.5193 13.71 13.726 3.8078 3.2712 8.3603 3.5845 12.99 0.91748 7.5125-4.3277 8.3185-3.943 12.913 3.2927 19.449 30.632 31.862 64.572 45.784 97.774 4.6504 11.091 6.4911 23.201-0.24924 34.177-3.8368 6.2477-9.4876 11.466-14.731 16.741-5.1204 5.152-10.761 9.787-16.175 14.651 4.8322 6.2205 8.5908 11.059 12.805 16.484 41.119-21.507 83.624-27.965 127.69-10.369 3.7212-6.5929 6.9406-12.297 9.993-17.705-5.2637-4.1439-10.192-7.588-14.613-11.592-10.338-9.3615-19.107-19.691-22.783-33.798-3.4275-13.151-7.9647-26.012-12.277-39.733m-333.6-56.531c-5.4131-2.5386-10.809-5.1151-16.243-7.607-8.3042-3.8077-9.2516-3.9362-13.012 4.2752-7.3158 15.975-14.362 32.105-20.689 48.492-8.4861 21.979-15.607 44.496-24.423 66.334-5.9164 14.657-15.623 27.043-28.834 36.264-2.1218 1.4809-4.334 2.8323-6.5866 4.2971 3.8322 6.0266 7.4503 11.717 11.174 17.572 14.053-10.514 29.737-16.007 46.527-17.307 12.867-0.99616 25.883 0.005096 38.831-0.081726 1.9836-0.013245 5.0986-0.53491 5.7325-1.8014 2.1574-4.3101-0.67215-11.1-4.5861-13.915-3.7653-2.7075-7.6722-5.4689-10.641-8.953-2.3362-2.7416-5.0746-6.8651-4.637-9.9496 1.9794-13.952 3.7048-28.176 7.9839-41.512 6.9897-21.784 15.931-42.939 23.967-64.39 2.4268-6.4774 1.6525-8.3915-4.565-11.718z" fill="#FEFEFE" opacity="0"/>
<path d="m297.18 19.337c93.22 0.64636 168.93 36.924 226.59 109.54 31.708 39.929 50.195 85.813 56.57 136.48 6.5104 51.735-0.80402 101.47-21.762 149.87-12.449-9.4978-24.423-18.635-28.317-34.867-2.4335-10.146-5.4637-20.214-9.1257-29.98-2.7361-7.2965-2.9936-14.288-2.0073-21.873 6.6624-51.236-3.5135-99.135-30.805-142.88-33.256-53.311-80.832-88.137-142.54-99.912-87.052-16.61-160.29 10.064-216.83 78.479-35.964 43.516-50.947 94.765-47.546 151.02 0.86758 14.349 4.5258 28.528 6.8859 42.787 0.24004 1.4503 0.91118 3.1115 0.47 4.3499-6.4067 17.983-11.978 36.458-24.933 51.169-4.2181 4.7899-9.5331 8.6137-14.798 13.28-8.7848-17.467-15.273-34.601-19.984-52.403-34.853-131.71 26.787-267 149.21-326.97 37.289-18.267 76.918-27.342 118.92-28.079z" fill="#FEFEFE"/>
<path d="m235.8 296.22c-9.5842-15.258-22.251-27.359-34.616-39.707-3.2948-3.2902-7.1422-2.5349-9.787 2.1134-2.7014 4.7477-4.7398 9.8677-7.2114 14.752-3.0971 6.1209-4.0935 6.7817-10.514 4.7716-4.3759-1.3699-8.4653-3.6868-12.63-5.6902-3.8689-1.8611-7.5666-4.1143-11.526-5.7403-3.8492-1.5806-4.2716-3.6878-2.856-7.3904 4.3263-11.316 8.6278-22.662 12.312-34.196 2.055-6.433 2.8264-13.272 4.2529-19.912 0.79799-3.7141-0.75635-6.2244-4.0222-7.5623-5.2085-2.1338-10.526-4.0027-15.806-5.9612-3.724-1.3815-7.4617-2.7259-11.997-4.3799 4.4394-6.6677 8.5266-13.015 12.908-19.152 0.6257-0.87643 2.6819-1.0458 4.0661-1.0192 18.982 0.36563 37.962 0.83876 56.943 1.2518 9.6566 0.21011 19.322 0.64079 28.969 0.39278 4.2119-0.10829 6.0213 1.4402 7.6028 5.0612 16.584 37.968 38.543 72.775 62.83 106.2 0.37958 0.52234 0.84576 0.98175 1.8314 2.1133 2.6418-2.3219 5.3785-4.3566 7.6722-6.8062 11.188-11.947 19.876-11.434 29.622 1.906 0.78522 1.0747 1.423 2.2975 2.3617 3.2132 5.3845 5.253 4.1722 10.826 1.4233 16.888-2.8604 6.3081-5.9275 12.709-7.4503 19.4-3.4734 15.261-1.6121 30.547 2.6622 45.383 5.6047 19.453 11.79 38.744 17.992 58.018 1.4804 4.6005 0.85828 7.1585-3.433 10.084-14.001 9.5459-28.573 18.536-39.802 31.522-5.0612 5.8535-9.2107 12.495-14.039 19.145-7.0304-3.5099-14.569-7.2736-22.586-11.276 7.3932-17.955-0.12793-35.071-3.256-52.286-5.829-32.079-14.205-63.553-27.485-93.467-4.1631-9.3782-9.4666-18.25-14.433-27.665z" fill="#FEFEFE"/>
<path d="m344.69 516.83c24.635-5.3337 47.611-13.844 68.776-26.821 7.784-4.7726 15.827-9.5068 22.49-15.631 9.4043-8.6428 20.222-12.857 32.454-14.887 20.853-3.4602 41.101-1.3851 60.902 5.7931-105.3 149.77-332.33 159.49-449.74 9.3954 8.5079-7.1181 18.563-11.17 29.222-12.495 12.483-1.5511 25.17-1.687 37.776-1.8187 2.477-0.02591 5.2988 2.4091 7.4279 4.3076 24.709 22.033 52.832 37.933 84.543 47.452 34.838 10.457 70.115 11.426 106.15 4.7039z" fill="#FEFEFE"/>
<path d="m500.81 347.26c4.1805 13.355 8.7178 26.216 12.145 39.367 3.6769 14.107 12.446 24.437 22.783 33.798 4.4213 4.0038 9.3494 7.4478 14.613 11.592-3.0525 5.4081-6.2719 11.112-9.993 17.705-44.063-17.595-86.569-11.137-127.69 10.369-4.2144-5.4253-7.9731-10.264-12.805-16.484 5.4143-4.8638 11.055-9.4988 16.175-14.651 5.243-5.2753 10.894-10.494 14.731-16.741 6.7403-10.976 4.8996-23.087 0.24924-34.177-13.922-33.202-26.335-67.143-45.784-97.774-4.5941-7.2357-5.4001-7.6204-12.913-3.2927-4.6297 2.667-9.1822 2.3537-12.99-0.91748-4.8968-4.2068-9.6082-8.7556-13.71-13.726-3.9879-4.8327-3.5755-8.9807 0.27994-14.045 4.8397-6.3578 10.417-12.248 14.541-19.029 3.9099-6.4288 6.4324-13.715 9.4155-20.689 1.878-4.39-0.071747-7.9813-3.5532-10.325-5.0695-3.4121-10.6-6.1312-15.818-9.3335-4.7776-2.9317-9.5413-5.931-13.984-9.3287-0.94894-0.72582-0.51056-3.2948-0.65076-5.0196-0.023224-0.28543 0.25284-0.60393 0.41376-0.89432 7.3376-13.24 8.1961-13.22 22.664-9.5954 20.73 5.1938 41.678 6.7197 62.688 1.157 5.7095-1.5117 11.528-2.8796 16.43 1.902 1.0713 1.045 1.9952 3.0075 1.8431 4.4248-2.1169 19.735-1.8614 38.889 7.0919 57.505 15.975 33.216 31.087 66.848 46.472 100.35 2.6305 5.7275 4.8236 11.656 7.3506 17.858z" fill="#FEFEFE"/>
<path d="m167.4 290.56c5.8979 3.1259 6.6722 5.04 4.2454 11.517-8.0366 21.451-16.978 42.606-23.967 64.39-4.2791 13.336-6.0045 27.56-7.9839 41.512-0.43762 3.0844 2.3008 7.208 4.637 9.9496 2.9689 3.4841 6.8758 6.2455 10.641 8.953 3.9139 2.8144 6.7435 9.6044 4.5861 13.915-0.63393 1.2665-3.7489 1.7881-5.7325 1.8014-12.948 0.086822-25.965-0.91443-38.831 0.081726-16.79 1.3-32.474 6.7935-46.527 17.307-3.7236-5.8558-7.3418-11.546-11.174-17.572 2.2526-1.4648 4.4648-2.8162 6.5866-4.2971 13.211-9.2206 22.917-21.607 28.834-36.264 8.8154-21.838 15.937-44.355 24.423-66.334 6.3269-16.387 13.373-32.518 20.689-48.492 3.7605-8.2114 4.708-8.0829 13.012-4.2752 5.4345 2.4919 10.83 5.0684 16.563 7.808z" fill="#FEFEFE"/>
</svg>');

        // Regiser the magic card post-type
        register_post_type(
            'wpmtg_magiccard',
            array(
                'labels'      => array(
                    'name'          => __('Magic Cards', 'wpmtg'),
                    'singular_name' => __('Magic Card', 'wpmtg'),
                ),
                'public'      => true,
                'has_archive' => 'cards',
                'menu_icon' => $menuIcon,
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

        // Register card set taxonomy to categories individual cards
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
     * Create taxonomy terms for Magic card sets
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
     * Setup metaboxes to hold custom fields magic card post-type
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
     * Filter permalink to include card set-code/slug in URL
     *
     * @param [type] $postLink
     * @param [type] $post
     * @return void
     */
    public function magiccardPermalinkStructure($postLink, $post)
    {
        if (false !== strpos($postLink, '%wpmtg_card_setname%')) {
            $categoryTerm = get_the_terms($post->ID, 'wpmtg_card_setname');
            if (!empty($categoryTerm)) {
                $postLink = str_replace('%wpmtg_card_setname%', array_pop($categoryTerm)->slug, $postLink);
            } else {
                $postLink = str_replace('%wpmtg_card_setname%', 'uncategorized', $postLink);
            }
        }

        return $postLink;
    }

    /**
     * Remove custom fields metabox from magic card edit screen
     * https://developer.wordpress.org/reference/functions/remove_meta_box/
     */
    public function hideCustomFieldsMetabox()
    {
        remove_meta_box('postcustom', 'wpmtg_magiccard', 'normal');
    }

    /**
     * Get card data and pass to front-end
     *
     * @param string $content
     * @return string
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
     * Build markup and add to the content for front-end display of card info
     *
     * @param string $content
     * @param array $postmeta
     * @return string $content
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
        $content .= isset($postmeta['tcgplayer_purchase_uri']) ? '<li><a href="' . $postmeta['tcgplayer_purchase_uri'][0] . '" target="_blank">Buy on TCGPlayer</a></li>' : '';
        $content .= '</ul>';

        return $content;
    }

    /**
     * Build a select dropdown to filter cards by set in WP Admin
     *
     * @param [type] $post_type
     * @return void
     */
    public function filterPostsBySet($post_type)
    {
        if ('wpmtg_magiccard' !== $post_type) {
            return;
        }

        $taxonomy_name = 'wpmtg_card_setname';

        // get all terms
        $sets = get_terms(
            array(
                'taxonomy' => $taxonomy_name,
                'hide_empty' => false
            )
        );

        // selected taxonomy from URL
        $selected = isset($_GET[$taxonomy_name]) && $_GET[$taxonomy_name] ? $_GET[$taxonomy_name] : '';

        if ($sets) {
            echo '<select name="' . $taxonomy_name . '">';
            echo '<option value="">All sets</option>';

            foreach ($sets as $set) {
                echo '<option value="' . $set->slug . '"' . selected($selected, $set->slug) . '>' . $set->name . '</option>';
            }

            echo '</select>';
        }
    }

    /**
     * Set-up sortable column in WP Admin
     * https://www.ractoon.com/articles/wordpress-sortable-admin-columns-for-custom-posts
     *
     * @param array $columns
     * @return void
     */
    public function setupSortableAdminColumns($columns)
    {
        $date = $columns['date'];
        unset($columns['date']);

        $columns['custom_taxonomy'] = __('Set', 'wpmtg_card_setname');

        // put date at the end
        $columns['date'] = $date;

        return $columns;
    }

    /**
     * Populate sortable column in WP Admin with set name
     *
     * @param [type] $column
     * @param [type] $post_id
     * @return void
     */
    public function cardSetnameColumn($column, $post_id)
    {
        $terms = get_the_term_list($post_id, 'wpmtg_card_setname', '', ', ', '');
        echo is_string($terms) ? $terms : '—';
    }

    /**
     * Make custom taxonomy column sortable
     *
     * @param [type] $columns
     * @return void
     */
    function cardSetnameSortableColumn($columns)
    {
        $columns['custom_taxonomy'] = 'wpmtg_card_setname';

        return $columns;
    }
}
