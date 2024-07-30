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
     * Registers custom post type and associated taxonomy
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
    }

    public function enqueueAdminScripts()
    {
        wp_register_script('wpmtg-admin-js', PLUGIN_PATH . '/js/admin.js', array(), '1.0');
        wp_enqueue_script('wpmtg-admin-js');

        $localizedVars = array('pluginPath' => PLUGIN_PATH);
        wp_localize_script('wpmtg-admin-js', 'localizedVars', $localizedVars);
    }

    /**
     * Add actions for WordPress hooks.
     * All actions should be added through this method.
     *
     * For Actions that will be used in the admin we add "/admin" in the name of the action.
     * @example :
     *  - add_action('wp-plugin-boilerplate/admin/NAME_ACTION', [CLASS_CONTAIN_METHOD_ACTION, 'METHODE_NAME']);
     *  - add_action('wp-plugin-boilerplate/NAME_ACTION', [CLASS_CONTAIN_METHOD_ACTION, 'METHODE_NAME']);
     *  - add_action('wp-plugin-boilerplate/something/NAME_ACTION', [CLASS_CONTAIN_METHOD_ACTION, 'METHODE_NAME']);
     *
     * @see : https://developer.wordpress.org/reference/functions/add_action/
     *
     * @return void
     */
    private function addActions()
    {
        add_action('add_meta_boxes', [$this->PostTypeHelper, 'hideCustomFieldsMetabox']);
        add_action('add_meta_boxes', [$this->PostTypeHelper, 'magiccardCustomFields']);
        add_action('init', [$this->PostTypeHelper, 'registerWpmtgPostType']);
        add_filter('post_type_link', [$this->PostTypeHelper, 'magiccardPermalinkStructure'], 10, 4);
        add_filter('the_content', [$this->PostTypeHelper, 'appendCardToPost']);
        add_action('admin_menu', [$this->AdminHelper, 'createAdminOptionsPage']);

        // sorting & filtering posts in wp admin
        add_action('restrict_manage_posts', [$this->PostTypeHelper, 'filterPostsBySet']);
        add_filter('manage_wpmtg_magiccard_posts_columns', [$this->PostTypeHelper, 'set_custom_edit_mycpt_columns']);
        add_action('manage_wpmtg_magiccard_posts_custom_column', [$this->PostTypeHelper, 'custom_mycpt_column'], 10, 2);
        add_filter('manage_edit-wpmtg_magiccard_sortable_columns', [$this->PostTypeHelper, 'set_custom_mycpt_sortable_columns']);

        // admin scripts + styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);

        //ajax stuff
        add_action('wp_ajax_import_wpmtg_card_set', [$this->ApiHelper, 'doApiThings']);
        add_action('wp_ajax_nopriv_import_wpmtg_card_set', [$this->ApiHelper, 'doApiThings']);
    }
}
