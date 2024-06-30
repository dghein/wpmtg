<?php

namespace Wpmtg;

class WpmtgPost
{
    public function registerWpmtgPostType()
    {
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
                'supports' => array('title', 'editor', 'thumbnail', 'custom-fields')
            )
        );
    
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
}
