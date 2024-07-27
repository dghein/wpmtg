<?php

namespace Wpmtg;

use Wpmtg\WpmtgApiHelper;

class WpmtgAdminOptions
{
    /**
     * Undocumented function
     *
     * @return void
     */
    public function createAdminOptionsPage()
    {
        add_menu_page('WPMTG', 'WPMTG', 'manage_options', 'wpmtg', [$this, 'adminOptionsPage']);
    }

    /**
     * Content for options page content with lots of useful things
     */
    public function adminOptionsPage()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        ini_set('max_execution_time', '300'); //300 seconds


        // the back-end form used to download card images
        echo '<div class="wrap">';
        echo '  <form action="" method="post" id="frmImport">';
        echo '    <input type="hidden" name="action" value="import_wpmtg_card_set">';
        echo '    <input type="text" name="set">';
        echo '    <input type="submit" value="Import Set Data">';
        echo '  </form>';
        echo '</div>';
    }
}
