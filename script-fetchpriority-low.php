<?php
/**
 * Plugin Name: Script Fetch Priority Low
 * Plugin URI: https://github.com/westonruter/script-fetchpriority-low
 * Description: Improves performance for the LCP metric by setting <code>fetchpriority=low</code> for on script modules (and modulepreload links) for the Interactivity API as well as on the <code>comment-reply</code> script.
 * Requires at least: 6.5
 * Requires PHP: 7.2
 * Version: 0.1.0
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
if ( isset( $_GET['disable_script_fetchpriority_low'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return;
}

/**
 * Gets the fetchpriority for the value for the provided SCRIPT attributes.
 *
 * @param array{ id?: string, type?: string } $attributes Script attributes.
 * @return 'auto'|'low'|'high'|null The fetchpriority attribute value, or null if the script should not have a fetchpriority attribute..
 */
function get_fetchpriority_for_script_tag( array $attributes ): ?string {
	if ( ! isset( $attributes['id'] ) ) {
		return null;
	}

	if (
		isset( $attributes['type'] )
		&&
		'module' === $attributes['type']
		&&
		str_ends_with( $attributes['id'], '-js-module' )
		&&
		(
			str_starts_with( $attributes['id'], '@wordpress/block-library/' )
			||
			str_starts_with( $attributes['id'], '@wordpress/interactivity' )
		)
	) {
		return 'low';
	}

	if (
		( ! isset( $attributes['type'] ) || 'text/javascript' === $attributes['type'] )
		&&
		str_ends_with( $attributes['id'], '-js' )
	) {
		$handle = substr( $attributes['id'], 0, -3 );

		$dependency = wp_scripts()->query( $handle );
		if (
			$dependency
			&&
			isset( $dependency->extra['fetchpriority'] )
			&&
			in_array( $dependency->extra['fetchpriority'], array( 'auto', 'low', 'high' ), true )
		) {
			return $dependency->extra['fetchpriority'];
		}
	}

	return 'auto';
}

/**
 * Adds fetchpriority to SCRIPT tags.
 *
 * @todo This should also add fetchpriority=low to module scripts for non-core blocks.
 *
 * @param array<string, string>|mixed $attributes Script attributes.
 * @return array{ id?: string, type?: string, fetchpriority?: 'low'|'high' } Modified attributes.
 */
function filter_script_tag_attributes( $attributes ): array {
	if ( ! is_array( $attributes ) ) {
		$attributes = array();
	}

	/**
	 * Script attributes.
	 *
	 * @var array{ id?: string, type?: string } $attributes
	 */
	$fetchpriority = get_fetchpriority_for_script_tag( $attributes );

	if ( is_string( $fetchpriority ) && 'auto' !== $fetchpriority ) {
		$attributes['fetchpriority'] = $fetchpriority;
	}
	return $attributes;
}
add_filter( 'wp_script_attributes', __NAMESPACE__ . '\filter_script_tag_attributes' );

/**
 * Adds fetchpriority=low to the modulepreload LINK tags.
 */
function add_fetchpriority_low_to_interactivity_api_modulepreload_links(): void {
	$position = wp_is_block_theme() ? 'wp_head' : 'wp_footer';
	$priority = has_action( $position, array( wp_script_modules(), 'print_script_module_preloads' ) );
	if ( ! is_int( $priority ) ) {
		return;
	}
	remove_action( $position, array( wp_script_modules(), 'print_script_module_preloads' ), $priority );
	add_action(
		$position,
		static function (): void {
			ob_start();
			wp_script_modules()->print_script_module_preloads();
			$buffer = (string) ob_get_clean();
			if ( '' === $buffer ) {
				return;
			}
			$processor = new WP_HTML_Tag_Processor( $buffer );
			while ( $processor->next_tag( array( 'tag_name' => 'LINK' ) ) ) {
				$processor->set_attribute( 'fetchpriority', 'low' );
			}
			echo $processor->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		},
		$priority
	);
}

add_action( 'init', __NAMESPACE__ . '\add_fetchpriority_low_to_interactivity_api_modulepreload_links' );
