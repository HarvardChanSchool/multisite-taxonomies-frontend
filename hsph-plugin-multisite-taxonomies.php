<?php
/**
 * HSPH Plugin Multisite Taxoinomies Frontend
 *
 * @package multitaxo
 * @subpackage multisite-taxonomies-frontend
 */

/**
 * Plugin Name: HSPH Plugin Multisite Taxoinomies Frontend
 * Plugin URI:  http://www.hsph.harvard.edu/information-technology/
 * Description: Frontend display for the multisite taxonomies plugin
 * Version:     1.0.0
 * Author:      HSPH Webteam
 * Author URI:  http://www.hsph.harvard.edu/
 * Text Domain: multitaxo
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Plugin init.
require_once( plugin_dir_path( __FILE__ ) . 'inc/class-multisite-taxonomy-archive-pages.php' );
$multisite_taxonomy_archive_pages = new Multisite_Taxonomy_Archive_Pages();

$multisite_taxonomy_archive_pages->init();
