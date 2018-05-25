<?php
/*
    Plugin Name: Advanced Custom Fields Extensions
    Description: Advanced Custom Fields add on. Create component, components field, hidden field and lastest post field
    Version: 1.0.0
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
function acf_extensions_load_textdomain() {
    load_plugin_textdomain('acf-component_field', false, dirname(plugin_basename(__FILE__)).'/lang/');
}

add_action('plugins_loaded', 'acf_extensions_load_textdomain');


/**
 * Load the main field class after core acf init
 *
 * @since  1.0.0
 * @return void
 */
function include_acf_extensions_plugin() {

	include_once('acf-component_field.php');
	include_once('acf-hidden_field.php');
	include_once('acf-latest_posts_field.php');

	acf_register_field_type( 'acf_field_component' );
	acf_register_field_type( 'acf_field_hidden' );
	acf_register_field_type( 'acf_field_latest_posts' );
}

add_action('acf/include_field_types', 'include_acf_extensions_plugin');


/**
 * Change acf-component status back to acf-diabled when deactivated
 *
 * @since  1.0.0
 * @return void
 */
function deactivate_acf_extensions_plugin() {

    // convert all the acf-component to acf-disabled
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

register_deactivation_hook(__FILE__, 'deactivate_acf_extensions_plugin');


/**
 * Loop through the inactive acf field group and tag them as acf component
 * don't touch the enable ones even its is_acf_component is true.
 * Because the means user manually enable it,
 * thus might not be a component anymore
 *
 * @since  1.0.7
 * @return void
 */
function activate_acf_extensions_plugin() {

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

register_activation_hook(__FILE__, 'activate_acf_extensions_plugin');
