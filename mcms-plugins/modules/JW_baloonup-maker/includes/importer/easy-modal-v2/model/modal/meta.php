<?php

// Exit if accessed directly
if ( ! defined( 'BASED_TREE_URI' ) ) {
	exit;
}

class EModal_Model_Modal_Meta extends EModal_Model {
	protected $_class_name = 'EModal_Model_Modal_Meta';
	protected $_table_name = 'em_modal_metas';
	protected $_pk = 'modal_id';
	protected $_default_fields = array(
		'id'       => null,
		'modal_id' => null,
		'display'  => array(),
		'close'    => array(),
	);

	public function __construct( $id = null ) {
		global $mcmsdb;
		$table_name = $mcmsdb->prefix . $this->_table_name;
		$class_name = strtolower( $this->_class_name );

		$this->_default_fields['modal_id'] = $id;

		$this->_data = apply_filters( "{$class_name}_fields", $this->_default_fields );

		if ( $id && is_numeric( $id ) ) {
			$row = $mcmsdb->get_row( "SELECT * FROM $table_name WHERE modal_id = $id ORDER BY id DESC LIMIT 1", ARRAY_A );
			if ( $row[ $this->_pk ] ) {
				$this->process_load( $row );
			}
		} else {
			$this->set_fields( apply_filters( "{$class_name}_defaults", array() ) );
		}

		return $this;
	}

	public function save() {
		global $mcmsdb;
		$table_name = $mcmsdb->prefix . $this->_table_name;

		$rows = $mcmsdb->get_col( "SELECT id FROM $table_name WHERE modal_id = $this->modal_id ORDER BY id DESC" );
		if ( count( $rows ) ) {
			$this->id = $rows[0];
			$mcmsdb->update( $table_name, $this->serialized_values(), array( 'id' => $this->id ) );
		} else {
			$mcmsdb->insert( $table_name, $this->serialized_values() );
			$this->id = $mcmsdb->insert_id;
		}
	}

}