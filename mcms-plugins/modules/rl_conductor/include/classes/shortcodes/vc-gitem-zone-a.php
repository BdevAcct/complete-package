<?php
if ( ! defined( 'BASED_TREE_URI' ) ) {
	die( '-1' );
}

require_once vc_path_dir( 'SHORTCODES_DIR', 'vc-gitem-zone.php' );

class MCMSBakeryShortCode_VC_Gitem_Zone_A extends MCMSBakeryShortCode_VC_Gitem_Zone {
	public $zone_name = 'a';

	protected function getFileName() {
		return 'vc_gitem_zone';
	}
}
