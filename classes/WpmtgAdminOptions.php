<?php

namespace Wpmtg;

class WpmtgAdminOptions
{
    /**
     * Create an admin menu item pointing to a the plugin's 'Card Importer' tool
     *
     * @return void
     */
    public function createAdminOptionsPage()
    {
        add_submenu_page('edit.php?post_type=wpmtg_magiccard', 'Import Cards', 'Import Cards', 'manage_options', 'wpmtg-card-importer', [$this, 'adminOptionsPage']);
    }

    /**
     * Content for options page content with lots of useful things
     */
    public function adminOptionsPage()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        ini_set('max_execution_time', 500); // because big request

        echo '<h1>WPMTG Card Importer</h1>';
        echo '<p>Import full card sets to WordPress by entering a set code in the field below. <a href="https://www.scryfall.com/sets" target="_blank">Click here for a full list of set codes</a>.</p>';

        // the back-end form used to download card images
        echo '<div class="wrap">';
        echo '  <form action="" method="post" id="frmImport">';
        echo '    <fieldset id="frmImportFieldset">';
        echo '      <legend>Import By:</legend>';
        echo '      <input type="hidden" name="action" value="import_wpmtg_card_set">';

        // option to import by set-code
        echo '      <input type="radio" name="import_method" value="setcode" class="card-import-form__import-methods" id="importMethodSetCode" data-toggle="importFormFieldSetCode" checked>';
        echo '      <label for="importMethodSetCode">Set Code</label>';

        // option to import by date
        echo '      <input type="radio" name="import_method" value="setdate" class="card-import-form__import-methods" id="importMethodSetDate" data-toggle="importFormFieldSetDate">';
        echo '      <label for="importMethodSetDate">Date</label>';
        echo '    </fieldset>';
        
        // input fields
        echo '    <input type="text" name="set" class="card-import-form__input" id="importFormFieldSetCode">';
        echo '    <input type="date" name="date" class="card-import-form__input" id="importFormFieldSetDate">';

        // submit
        echo '    <input type="submit" value="Import Cards" class="button-primary" id="importFormSubmitButton">';
        echo '  </form>';
        echo '</div>';

        // display a list of card sets already imported to the site
        $terms = get_terms(array(
            'taxonomy' => 'wpmtg_card_setname',
            'hide_empty' => false,
        ));

        echo '<div>';
        echo '  <h2>Your Card Sets</h2>';

        if (!empty($terms) && !is_wp_error($terms)) {
            echo '<ul class="wpmtg-card-setname-list">';

            foreach ($terms as $term) {
                $term_link = get_term_link($term);
                echo '<li><a href="' . esc_url($term_link) . '">' . esc_html($term->name) . '</a></li>';
            }

            echo '</ul>';
        } else {
            echo '<p>No card sets found.</p>';
        }
        echo '</div>';
    }
}
