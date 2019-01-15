<?php
/**
 * @package MCMSSEO\Admin
 */

$type = 'warnings';
$dashicon = 'flag';

$i18n_title = __( 'Notifications', 'mandarincms-seo' );
$i18n_issues = '';
$i18n_no_issues = __( 'No notifications found.', 'mandarincms-seo' );

$active_total = count( $alerts_data['warnings']['active'] );
$total = $alerts_data['metrics']['warnings'];

$active = $alerts_data['warnings']['active'];
$dismissed = $alerts_data['warnings']['dismissed'];

include 'partial-alerts-template.php';