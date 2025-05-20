<?php
/**
 * Plugin Name: Script Fetch Priority Low
 * Plugin URI: https://github.com/westonruter/script-fetchpriority-low
 * Description: Improves performance for the LCP metric by setting <code>fetchpriority=low</code> for on script modules (and modulepreload links) for the Interactivity API as well as on the comment-reply script.
 * Requires at least: 6.5
 * Requires PHP: 7.2
 * Version: 0.1.2
 * Author: Weston Ruter
 * Author URI: https://weston.ruter.net/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Update URI: https://github.com/westonruter/script-fetchpriority-low
 * GitHub Plugin URI: https://github.com/westonruter/script-fetchpriority-low
 * Primary Branch: main
 *
 * @package ScriptFetchpriorityLow
 */

namespace ScriptFetchpriorityLow;

use WP_HTML_Tag_Processor;

// Short-circuit functionality to facilitate benchmarking performance impact.
if ( isset( $_GET['disable_script_fetchpriority_low'] ) ) {
	return;
}

// Add fetchpriority=low to the SCRIPT tags for Interactivity API view modules from the block library.
// TODO: This should also add fetchpriority=low to module scripts for non-core blocks.
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

// Add fetchpriority=low to the script modulepreload LINK tags.
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
