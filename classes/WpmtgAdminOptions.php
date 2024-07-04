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

        // check to see if we're downloading cards, and if we are, download them
        if (isset($_POST['set']) && !empty($_POST['set'])) {
            ini_set('max_execution_time', '300'); //300 seconds
            WpmtgApiHelper::fetchScryfallDataAndSave($_POST['set']);
        }

        // the back-end form used to download card images
        echo '<div class="wrap">';
        echo '  <form action="" method="post">';
        echo '    <input type="text" name="set" maxlength="3">';
        echo '    <input type="submit" value="Import Set Data">';
        echo '  </form>';
        echo '</div>';
    }
}
