<?php
/**
 * Plugin Name: Script Fetch Priority Low
 * Plugin URI: https://github.com/westonruter/script-fetchpriority-low
 * Description: Improves performance for the LCP metric by setting <code>fetchpriority=low</code> for on script modules (and modulepreload links) for the Interactivity API as well as on the <code>comment-reply</code> script. This implements <a href="https://core.trac.wordpress.org/ticket/61734">#61734</a>.
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
use WP_Scripts;

// Short-circuit functionality to facilitate benchmarking performance impact.
if ( isset( $_GET['disable_script_fetchpriority_low'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return;
}

/**
 * Checks whether the provided script module ID is for the Interactivity API.
 *
 * @param string $id Script module ID.
 * @return bool Whether the script module ID is for the Interactivity API.
 */
function is_interactivity_api_script_module( string $id ): bool {
	return (
		// These are usually not directly enqueued, so these will be emitted as modulepreload links which should get fetchpriority=low.
		in_array(
			$id,
			// TODO: It would be nice if wp_script_modules()->registered weren't private so that dependencies could be inspected.
			array(
				'@wordpress/interactivity',        // Dependency of all blocks using the Interactivity API.
				'@wordpress/interactivity-router', // Dependencies: @wordpress/interactivity and @wordpress/a11y.
				'@wordpress/a11y',                 // This is actually a dynamic import (currently), so it shouldn't show up as a modulepreload link.
			),
			true
		)
		||
		// Core blocks, which have handles in the format of `@wordpress/block-library/{blockName}/view-js-module` according to the logic in wp_default_script_modules().
		str_starts_with( $id, '@wordpress/block-library/' )
		||
		// For third-party blocks which support the Interactivity API and have a viewScriptModule (e.g. the embed block in the Web Stories plugin). The ID is generated via generate_block_asset_handle().
		str_ends_with( $id, '-view-script-module' )
	);
}

/**
 * Add fetchpriority=low to the comment-reply script.
 *
 * @param WP_Scripts $scripts Scripts.
 */
function add_fetchpriority_low_to_comment_reply_script( WP_Scripts $scripts ): void {
	$scripts->add_data( 'comment-reply', 'fetchpriority', 'low' );
}
add_action( 'wp_default_scripts', __NAMESPACE__ . '\add_fetchpriority_low_to_comment_reply_script' );

/**
 * Gets the fetchpriority for the value for the provided SCRIPT attributes for scripts and script modules.
 *
 * @param array{ id?: string, type?: string } $attributes Script attributes.
 * @return 'auto'|'low'|'high'|null The fetchpriority attribute value, or null if the script should not have a fetchpriority attribute..
 */
function get_fetchpriority_for_script_tag( array $attributes ): ?string {
	if ( ! isset( $attributes['id'] ) ) {
		return null;
	}

	// Script modules (for the Interactivity API).
	if (
		isset( $attributes['type'] )
		&&
		'module' === $attributes['type']
		&&
		1 === preg_match( '/^(?P<id>.+?)-js-module$/', $attributes['id'], $matches )
		&&
		is_interactivity_api_script_module( $matches['id'] )
	) {
		return 'low';
	}

	// Classic scripts (which have the fetchpriority data added).
	if (
		( ! isset( $attributes['type'] ) || 'text/javascript' === $attributes['type'] )
		&&
		1 === preg_match( '/^(?P<handle>.+?)-js$/', $attributes['id'], $matches )
	) {
		$dependency = wp_scripts()->query( $matches['handle'] );
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

	return null;
}

/**
 * Adds fetchpriority to SCRIPT tags.
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
 *
 * @see \WP_Script_Modules::print_a11y_script_module_html()
 */
function add_fetchpriority_low_to_interactivity_api_modulepreload_links(): void {
	foreach ( array( 'wp_head', 'wp_footer' ) as $position ) {
		$priority = has_action( $position, array( wp_script_modules(), 'print_script_module_preloads' ) );
		if ( is_int( $priority ) ) {
			break;
		}
	}
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
				if (
					is_string( $processor->get_attribute( 'id' ) )
					&&
					1 === preg_match( '/^(?P<id>.+?)-js-modulepreload$/', $processor->get_attribute( 'id' ), $matches )
					&&
					is_interactivity_api_script_module( $matches['id'] )
				) {
					$processor->set_attribute( 'fetchpriority', 'low' );
				}
			}
			echo $processor->get_updated_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		},
		$priority
	);
}

// Note that WP_Script_Modules::add_hooks() is called at after_setup_theme priority 10. Priority 1000 is used so that other plugins have the opportunity to override where script modules are printed.
add_action(
	'after_setup_theme',
	__NAMESPACE__ . '\add_fetchpriority_low_to_interactivity_api_modulepreload_links',
	1000
);
