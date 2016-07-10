<?php
/**
 * Debug functions
 *
 * Functions to debug library CRM
 *
 * @author   closemarketing
 * @category Functions
 * @package  Gravityforms CRM
 * @version  1.0.0
 */

function debug_message($message) {
        if (WP_DEBUG==true) {
        //Debug Mode
        echo '  <table class="widefat">
                <thead>
                <tr class="form-invalid">
                    <th class="row-title">'.__('Message Debug Mode','gravityformscrm').'</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                <td><pre>';
        print_r($message);
        echo '</pre></td></tr></table>';
    }
}
