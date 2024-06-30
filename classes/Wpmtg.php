<?php

namespace Wpmtg;

use Wpmtg\WpmtgPost;

class Wpmtg
{
    private $PostTypeHelper;

    public function __construct()
    {
        $this->PostTypeHelper = new WpmtgPost();

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
     * Add actions.
     *
     * All actions should be added through this method.
     *
     * For Actions that will be used in the admin we add "/admin" in the name of the action.
     * @example :
     *  - add_action('wp-plugin-boilerplate/admin/NAME_ACTION', [CLASS_CONTAIN_METHODE_ACTION, 'METHODE_NAME']);
     *  - add_action('wp-plugin-boilerplate/NAME_ACTION', [CLASS_CONTAIN_METHODE_ACTION, 'METHODE_NAME']);
     *  - add_action('wp-plugin-boilerplate/something/NAME_ACTION', [CLASS_CONTAIN_METHODE_ACTION, 'METHODE_NAME']);
     *
     * @see : https://developer.wordpress.org/reference/functions/add_action/
     *
     * @return void
     */
    private function addActions()
    {
        add_action('init', [$this->PostTypeHelper, 'registerWpmtgPostType']);
    }
}
