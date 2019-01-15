<?php
if ( ! defined( 'BASED_TREE_URI' ) ) {
	die( '-1' );
}

if ( 'vc_edit_form' === vc_post_param( 'action' ) ) {
	VcShortcodeAutoloader::getInstance()
	                     ->includeClass( 'MCMSBakeryShortCode_VC_Progress_Bar' );

	add_filter( 'vc_edit_form_fields_attributes_vc_progress_bar', array(
		'MCMSBakeryShortCode_VC_Progress_Bar',
		'convertAttributesToNewProgressBar',
	) );
}
