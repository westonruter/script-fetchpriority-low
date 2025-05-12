<?php
/**
 * Plugin Name: Force Low-Priority Interactivity API Script Modules
 * Plugin URI: https://core.trac.wordpress.org/ticket/61734
 * Description: Improves Largest Contentful Paint by forcing the module scripts (from the Interactivity API) to be loaded with a low priority rather than the default high priority. This prevents network contention with loading the LCP element.
 * Requires at least: 6.5
 * Requires PHP: 7.2
 * Version: 0.1.1
 * Author: Weston Ruter
 * Author URI: https://weston.ruter.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Update URI: https://gist.github.com/westonruter/471111a891f43e0f48bc7e0ca478623d
 */

if ( isset( $_GET['disable_script_module_low_priority'] ) ) {
	return;
}

add_filter(
	'wp_script_attributes',
	static function ( $attributes ) {
		if (
			isset( $attributes['type'], $attributes['id'] ) &&
			'module' === $attributes['type']
			&&
			str_starts_with( $attributes['id'], '@wordpress/block-library/' )
		) {
			$attributes['fetchpriority'] = 'low';
		}
		return $attributes;
	}
);


add_action(
	'init',
	static function () {
		$position = wp_is_block_theme() ? 'wp_head' : 'wp_footer';
		$priority = has_action( $position, array( wp_script_modules(), 'print_script_module_preloads' ) );
		if ( false === $priority ) {
			return;
		}
		remove_action( $position, array( wp_script_modules(), 'print_script_module_preloads' ), $priority );
		add_action(
			$position,
			static function () {
				ob_start();
				wp_script_modules()->print_script_module_preloads();
				$buffer = ob_get_clean();
				if ( '' === $buffer ) {
					return;
				}
				$processor = new WP_HTML_Tag_Processor( $buffer );
				while ( $processor->next_tag( array( 'tag_name' => 'LINK' ) ) ) {
					$processor->set_attribute( 'fetchpriority', 'low' );
				}
				echo $processor->get_updated_html();
			},
			$priority
		);
	}
);
