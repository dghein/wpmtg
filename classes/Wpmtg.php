<?php

namespace Wpmtg;

use Wpmtg\WpmtgPost;
use Wpmtg\WpmtgAdminOptions;
use Wpmtg\WpmtgApiHelper;

class Wpmtg
{
    private $AdminHelper;
    private $ApiHelper;
    private $PostTypeHelper;

    public function __construct()
    {
        $this->PostTypeHelper = new WpmtgPost();
        $this->AdminHelper = new WpmtgAdminOptions();
        $this->ApiHelper = new WpmtgApiHelper();

        $this->addActions();
    }

    public function registerActivationHooks()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Plugin activation
     * Registers custom post-type
     *
     * @return void
     */
    public function activate()
    {
        $this->PostTypeHelper->registerWpmtgPostType();
    }

    /**
     * Plugin deactivation
     * https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
     *
     * @return void
     */
    public function deactivate()
    {
        // nothing so far but will eventually be cleanup file, database, etc.
    }

    public function enqueueAdminScripts()
    {
        wp_register_script('wpmtg-admin-js', PLUGIN_PATH . '/js/admin.js', array(), '1.0');
        wp_enqueue_script('wpmtg-admin-js');

        // make some plugin variables and constants available in javascript
        $localizedVars = array('pluginPath' => PLUGIN_PATH);
        wp_localize_script('wpmtg-admin-js', 'localizedVars', $localizedVars);
    }

    /**
     * Add actions for WordPress hooks
     * https://developer.wordpress.org/reference/functions/add_action/
     *
     * @return void
     */
    private function addActions()
    {
        // wpmtg_magiccard post-type registration
        add_action('init', [$this->PostTypeHelper, 'registerWpmtgPostType']);

        // admin scripts + styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);

        // appends magic card information to post on the front-end
        add_filter('the_content', [$this->PostTypeHelper, 'appendCardToPost']);

        // custom fields
        add_action('add_meta_boxes', [$this->PostTypeHelper, 'hideCustomFieldsMetabox']);
        add_action('add_meta_boxes', [$this->PostTypeHelper, 'magiccardCustomFields']);

        // filter permalink structure (used to include set code in URL)
        add_filter('post_type_link', [$this->PostTypeHelper, 'magiccardPermalinkStructure'], 10, 4);

        // wp admin options page
        add_action('admin_menu', [$this->AdminHelper, 'createAdminOptionsPage']);

        // sorting & filtering cards in wp admin
        add_action('restrict_manage_posts', [$this->PostTypeHelper, 'filterPostsBySet']);
        add_filter('manage_wpmtg_magiccard_posts_columns', [$this->PostTypeHelper, 'setupSortableAdminColumns']);
        add_action('manage_wpmtg_magiccard_posts_custom_column', [$this->PostTypeHelper, 'cardSetnameColumn'], 10, 2);
        add_filter('manage_edit-wpmtg_magiccard_sortable_columns', [$this->PostTypeHelper, 'cardSetnameSortableColumn']);

        // ajax/scryfall api stuff
        add_action('wp_ajax_import_wpmtg_card_set', [$this->ApiHelper, 'doApiThings']);
        add_action('wp_ajax_nopriv_import_wpmtg_card_set', [$this->ApiHelper, 'doApiThings']);
    }
}
