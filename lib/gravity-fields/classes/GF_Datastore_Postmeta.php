<?php
class GF_Datastore_Postmeta implements GF_Datastore {
	protected $post_id;

	/* @type string[] List of unique field keys */
	static $field_keys = array();

	public function set_post($post_id) {
		if(!intval($post_id)) {
			gf_die('Invalid post ID!');
		}

		$this->post_id = $post_id;
	}

	/**
	 * Set an ID of the post
	 * 
	 * @param int $id The ID of the post
	 */
	function set_id( $id ) {
		$this->set_post( $id );
	}

	function get_value($key) {
		if(!$this->post_id) {
			return false;
		}

		return get_post_meta($this->post_id, $key, true);
	}

	function get_multiple($key) {
		return $this->get_value($key);
	}

	function save_value($key, $value) {
		if(!$this->post_id) {
			return false;
		}

		update_post_meta($this->post_id, $key, $value);
		return true;
	}

	function save_multiple($key, $values = array()) {
		return $this->save_value($key, $values);
	}

	function delete_value($key) {
		return delete_post_meta($this->post_id, $key);
	}

	/**
	 * Check if a field with the same key has been registered
	 *
	 * @param string $key The key of the field
	 */
	function check_field_id( $key ) {
		if( isset( GF_Datastore_Postmeta::$field_keys[ $key ] ) ) {
			GF_Exceptions::add( sprintf( __( 'Error: Trying to register a post meta field with the %s key twice!', 'gf' ), $key ), 'unavailable_field_key' );
			return false;
		} else {
			GF_Datastore_Postmeta::$field_keys[ $key ] = 1;
			return true;
		}
	}
}