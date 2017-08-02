<?php
class GF_Datastore_Usermeta implements GF_Datastore {
	protected $user;

	/* @type string[] List of unique field keys */
	static protected $field_keys = array();

	public function set_user( WP_User $user ) {
		$this->user = $user;
	}

	/**
	 * Set a user by ID
	 *
	 * @param int $id The ID of the user
	 */
	public function set_id( $id ) {
		$user = new stdClass();
		$user->ID = $id;
		$this->user = $user;
	}

	function get_value($key) {
		if( ! $this->user ) {
			return false;
		}

		return get_user_meta( $this->user->ID, $key, true );
	}

	function get_multiple( $key ) {
		return $this->get_value( $key );
	}

	function save_value( $key, $value ) {
		if( ! $this->user ) {
			return false;
		}

		update_user_meta( $this->user->ID, $key, $value );

		return true;
	}

	function save_multiple( $key, $values = array() ) {
		return $this->save_value( $key, $values );
	}

	function delete_value( $key ) {
		return delete_user_meta( $this->user->ID, $key );
	}

	/**
	 * Check if a field with the same key has been registered
	 *
	 * @param string $key The key of the field
	 */
	function check_field_id( $key ) {
		if( isset( GF_Datastore_Usermeta::$field_keys[ $key ] ) ) {
			GF_Exceptions::add( sprintf( __( 'Error: Trying to register a user meta field with the %s key twice!', 'gf' ), $key ), 'unavailable_field_key' );
			return false;
		} else {
			GF_Datastore_Usermeta::$field_keys[ $key ] = 1;
			return true;
		}
	}
}