<?php
/*
    Plugin Name: Advanced Custom Fields: Component Field
    Description: Advanced Custom Fields add on. Make an entire acf field group reuseable, as a component field.
    Version: 2.0.0
    Author: Metabolism
    License: GPLv2 or later
    License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Load up the translation files
 *
 * @since  1.0.4
 * @return void
 */
function acf5_component_field_load_textdomain() {
    load_plugin_textdomain('acf-component_field', false, dirname(plugin_basename(__FILE__)).'/lang/');
}
add_action('plugins_loaded', 'acf5_component_field_load_textdomain');

/**
 * Load the main field class after core acf init
 *
 * @since  1.0.0
 * @return void
 */
function include_acf5_component_field_plugin() {
	include_once('acf-component_field-v5.php');
    $GLOBALS['acf_component_field'] = new acf_field_component_field();
}
add_action('acf/include_field_types', 'include_acf5_component_field_plugin');

/**
 * Change acf-component status back to acf-diabled when deactivated
 *
 * @since  1.0.0
 * @return void
 */
function deactivate_acf5_component_field_plugin() {

    // conver all the acf-comopnent to acf-disabled
    $args = array(
        'posts_per_page' => -1,
        'post_type'      => 'acf-field-group',
        'post_status'    => array('acf-component')
    );

    $field_groups = get_posts($args);

    foreach ($field_groups as $field_group) {
        $field_group->post_status = 'acf-disabled';
        wp_update_post($field_group);
    }
}
register_deactivation_hook(__FILE__, 'deactivate_acf5_component_field_plugin');

/**
 * Loop throught the inactive acf field group and tag them as acf component
 * don't touch the enable ones even its is_acf_component is true.
 * Because the means user manually enable it,
 * thus might not be a component anymore
 *
 * @since  1.0.7
 * @return void
 */
function activate_acf5_component_field_plugin() {

    $args = array(
        'posts_per_page' => -1,
        'post_type'      => 'acf-field-group',
        'post_status'    => array('acf-component', 'acf-disabled')
    );

    $field_groups = get_posts($args);

    foreach ($field_groups as $field_group) {
        $acf = acf_get_field_group($field_group);

        if (acf_maybe_get($acf, 'is_acf_component', 0) == 1) {
            wp_update_post(array(
                'ID' => $field_group->ID,
                'post_status' => 'acf-component'
            ));
        }
    }
}
register_activation_hook(__FILE__, 'activate_acf5_component_field_plugin');
