<?php

namespace Wpmtg;

use Wpmtg\WpmtgPost;
use Wpmtg\WpmtgAdminOptions;

class Wpmtg
{
    private $AdminHelper;
    private $PostTypeHelper;

    public function __construct()
    {
        $this->PostTypeHelper = new WpmtgPost();
        $this->AdminHelper = new WpmtgAdminOptions();

        $this->addActions();
    }

    private function registerActivationHooks()
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
    private function activate()
    {
        // TODO: get all of the card sets from API and register as taxonomies
    }

    /**
     * Plugin deactivation
     * https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
     *
     * @return void
     */
    private function deactivate()
    {
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
    }
}
