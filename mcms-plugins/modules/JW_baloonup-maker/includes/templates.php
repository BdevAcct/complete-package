<?php
/**
 * Template Loader for Modules.
 *
 * @package   PopMake_Template_Loader
 * @author    Daniel Iser
 * @author    Gary Jones
 */

// Exit if accessed directly
if ( ! defined( 'BASED_TREE_URI' ) ) {
	exit;
}

/**
 * Returns the path to the BaloonUp Maker templates directory
 */
function balooncreate_get_templates_dir() {
	//$deprecated = ! pum_is_v1_4_compatible() ? 'deprecated/' : '';
	return BaloonUp_Maker::$DIR . 'templates';
}

/**
 * Returns the URL to the BaloonUp Maker templates directory
 */
function balooncreate_get_templates_url() {
	return BaloonUp_Maker::$URL . 'templates';
}

/**
 * Retrieves a template part
 */
function balooncreate_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) ) {
		$templates[] = $slug . '-' . $name . '.php';
	}
	$templates[] = $slug . '.php';

	// Allow template parts to be filtered
	$templates = apply_filters( 'balooncreate_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return balooncreate_locate_template( $templates, $load, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 */
function balooncreate_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) ) {
			continue;
		}

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// try locating this template file by looping through the template paths
		foreach ( balooncreate_get_myskin_template_paths() as $template_path ) {

			if ( file_exists( $template_path . $template_name ) ) {
				$located = $template_path . $template_name;
				break;
			}
		}

		if ( $located ) {
			break;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) ) {
		load_template( $located, $require_once );
	}

	return $located;
}

/**
 * Returns a list of paths to check for template locations
 */
function balooncreate_get_myskin_template_paths() {

	$template_dir = balooncreate_get_myskin_template_dir_name();

	$file_paths = array(
		1   => trailingslashit( get_stylesheet_directory() ) . $template_dir,
		10  => trailingslashit( get_template_directory() ) . $template_dir,
		100 => balooncreate_get_templates_dir()
	);

	$file_paths = apply_filters( 'balooncreate_template_paths', $file_paths );

	// sort the file paths based on priority
	ksort( $file_paths, SORT_NUMERIC );

	return array_map( 'trailingslashit', $file_paths );
}

/**
 * Returns the template directory name.
 *
 * mySkins can filter this by using the balooncreate_templates_dir filter.
 */
function balooncreate_get_myskin_template_dir_name() {
	return trailingslashit( apply_filters( 'balooncreate_templates_dir', 'balooncreate_templates' ) );
}
