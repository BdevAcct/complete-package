<?php
/**
 * @package MCMSSEO\Admin\Views
 */

if ( ! defined( 'MCMSSEO_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

echo '<h2>', esc_html__( 'Sitewide meta settings', 'mandarincms-seo' ), '</h2>';

$yform->toggle_switch( 'noindex-subpages-mcmsseo', $index_switch_values, __( 'Subpages of archives', 'mandarincms-seo' ) );
echo '<p>', __( 'If you want to prevent /page/2/ and further of any archive to show up in the search results, set this to "noindex".', 'mandarincms-seo' ), '</p>';

$yform->light_switch( 'usemetakeywords', __( 'Use meta keywords tag?', 'mandarincms-seo' ) );
echo '<p>', __( 'I don\'t know why you\'d want to use meta keywords, but if you want to, enable this.', 'mandarincms-seo' ), '</p>';

/* translators: %s expands to <code>noodp</code> */
$yform->light_switch( 'noodp', sprintf( __( 'Force %s meta robots tag sitewide', 'mandarincms-seo' ), '<code>noodp</code>' ) );
/* translators: %s expands to <code>noodp</code> */
echo '<p>', sprintf( __( 'Prevents search engines from using the DMOZ description in the search results for all pages on this site. Note: If you set a custom description for a page or post, it will have the %s tag regardless of this setting.', 'mandarincms-seo' ), '<code>noodp</code>' ), '</p>';
