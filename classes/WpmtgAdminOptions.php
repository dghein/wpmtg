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
        echo '<p>Import full card sets to WordPress by searching for a set below and selecting it from the list.</p>';

        // Card import form — submitted via AJAX in js/admin.js (not a traditional POST).
        echo '<div class="wrap">';
        echo '  <form action="" method="post" id="frmImport">';
        echo '    <fieldset id="frmImportFieldset">';
        echo '      <legend>Import By:</legend>';
        echo '      <input type="hidden" name="action" value="import_wpmtg_card_set">';

        // Radio toggles which input group is visible (handled by js/admin.js).
        echo '      <input type="radio" name="import_method" value="setcode" class="card-import-form__import-methods" id="importMethodSetCode" data-toggle="importFormSetCodeField" checked>';
        echo '      <label for="importMethodSetCode">Set Code</label>';

        echo '      <input type="radio" name="import_method" value="setdate" class="card-import-form__import-methods" id="importMethodSetDate" data-toggle="importFormFieldSetDate">';
        echo '      <label for="importMethodSetDate">Date</label>';
        echo '    </fieldset>';

        // Set-code import: React UI writes the chosen code into the hidden input on submit.
        // IDs here must match src/admin-set-selector/index.jsx constants.
        echo '    <div class="card-import-form__input" id="importFormSetCodeField">';
        echo '      <input type="hidden" name="set" id="importFormFieldSetCode">';
        echo '      <div id="wpmtg-set-selector-root"></div>';
        echo '    </div>';

        // Date import: plain date picker (unchanged from original form).
        echo '    <input type="date" name="date" class="card-import-form__input" id="importFormFieldSetDate">';

        // submit
        echo '    <input type="submit" value="Import Cards" class="button-primary" id="importFormSubmitButton">';
        echo '  </form>';
        echo '</div>';

        // List sets already imported to this site (stored as taxonomy terms).
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
