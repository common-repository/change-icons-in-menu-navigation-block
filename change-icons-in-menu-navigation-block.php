<?php
/**
 * Plugin Name: Change Icons in Menu Navigation Block
 * Description: Allows changing the open and close menu icons in the navigation block.
 * Version: 1.0.1
 * Author: Flavia Bernárdez Rodríguez
 * Author URI: https://flabernardez.com
 * License: GPL v3 or later
 * Text Domain: change-icons-in-menu-navigation-block
 * Domain Path: /languages
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function fla_cimnb_add_admin_menu() {
	add_options_page(
		esc_html__('Change Icons in Menu Navigation Block', 'change-icons-in-menu-navigation-block'),
		esc_html__('Change Menu Icons', 'change-icons-in-menu-navigation-block'),
		'manage_options',
		'change-icons-in-menu-navigation-block',
		'fla_cimnb_options_page'
	);
}
add_action('admin_menu', 'fla_cimnb_add_admin_menu');

function fla_cimnb_settings_init() {
	register_setting('fla_cimnb', 'fla_cimnb_settings');

	add_settings_section(
		'fla_cimnb_section',
		esc_html__('Customize Menu Icons', 'change-icons-in-menu-navigation-block'),
		'fla_cimnb_settings_section_callback',
		'fla_cimnb'
	);

	add_settings_field(
		'fla_cimnb_open_icon',
		esc_html__('Open Menu Icon SVG Code', 'change-icons-in-menu-navigation-block'),
		'fla_cimnb_open_icon_render',
		'fla_cimnb',
		'fla_cimnb_section'
	);
	add_settings_field(
		'fla_cimnb_close_icon',
		esc_html__('Close Menu Icon SVG Code', 'change-icons-in-menu-navigation-block'),
		'fla_cimnb_close_icon_render',
		'fla_cimnb',
		'fla_cimnb_section'
	);
}
add_action('admin_init', 'fla_cimnb_settings_init');

function fla_cimnb_open_icon_render() {
	$options = get_option('fla_cimnb_settings');
	?>
    <textarea cols='40' rows='5' name='fla_cimnb_settings[fla_cimnb_open_icon]'><?php echo esc_textarea($options['fla_cimnb_open_icon']); ?></textarea>
	<?php
}

function fla_cimnb_close_icon_render() {
	$options = get_option('fla_cimnb_settings');
	?>
    <textarea cols='40' rows='5' name='fla_cimnb_settings[fla_cimnb_close_icon]'><?php echo esc_textarea($options['fla_cimnb_close_icon']); ?></textarea>
	<?php
}

function fla_cimnb_settings_section_callback() {
	echo esc_html__('Paste the SVG code for the open and close menu icons.', 'change-icons-in-menu-navigation-block');
}

function fla_cimnb_options_page() {
	?>
    <div class="wrap">
        <h2><?php echo esc_html__('Change Icons in Menu Navigation Block', 'change-icons-in-menu-navigation-block'); ?></h2>
		<?php settings_errors(); ?>
        <form action='options.php' method='post'>
			<?php
			settings_fields('fla_cimnb');
			do_settings_sections('fla_cimnb');
			submit_button(esc_html__('Save Changes', 'change-icons-in-menu-navigation-block'));
			?>
        </form>
    </div>
	<?php
}

function fla_cimnb_sanitize_svg($svg) {

	$svg = preg_replace('/<script.*?>([\s\S]*?)<\/script>/i', '', $svg);
    $svg = preg_replace('/\s*on\w+="[^"]*"/i', '', $svg);
	$svg = preg_replace("/\s*on\w+='[^']*'/i", '', $svg);

	return $svg;
}

function fla_cimnb_pre_update_option_settings($value, $old_value, $option) {
	foreach (['fla_cimnb_open_icon', 'fla_cimnb_close_icon'] as $field) {
		if (!empty($value[$field])) {

			$sanitized_svg = fla_cimnb_sanitize_svg($value[$field]);

			if (empty($sanitized_svg) && !empty(trim($value[$field]))) {
				add_settings_error('fla_cimnb_settings', "{$field}_invalid", 'The SVG code provided is invalid or contains elements or attributes not allowed.');
				$value[$field] = $old_value[$field];
			} else {
				$value[$field] = $sanitized_svg;
			}
		}
	}

	return $value;
}
add_filter('pre_update_option_fla_cimnb_settings', 'fla_cimnb_pre_update_option_settings', 10, 3);


function fla_cimnb_custom_render_block_core_navigation($block_content, $block) {
	if ($block['blockName'] === 'core/navigation' && !is_admin() && !wp_is_json_request()) {
		$options = get_option('fla_cimnb_settings');

		$block_content = preg_replace_callback(
			'/<svg(.*?)<\/svg>/s',
			function($matches) use ($options) {
				static $svg_counter = 0;
				$svg_counter++;

				if ($svg_counter === 1 && !empty($options['fla_cimnb_open_icon'])) {
					return $options['fla_cimnb_open_icon'];
				} elseif ($svg_counter === 2 && !empty($options['fla_cimnb_close_icon'])) {
					return $options['fla_cimnb_close_icon'];
				}

				return $matches[0];
			},
			$block_content
		);
	}

	return $block_content;
}
add_filter('render_block', 'fla_cimnb_custom_render_block_core_navigation', 10, 2);
