<?php
class GF_Datastore_Termsmeta implements GF_Datastore {
	protected $term_id;

	protected static $cache = array();
	
	/* @type string[] List of unique field keys */
	static protected $field_keys = array();

	/**
	 * Adds the appropriate actions that are needed
	 */
	public static function add_actions() {
		add_action( 'gf_install', array( 'GF_Datastore_Termsmeta', 'install' ) );
		add_action( 'gf_uninstall', array( 'GF_Datastore_Termsmeta', 'uninstall' ) );
		add_filter('terms_clauses', array('GF_Datastore_Termsmeta', 'meta_query_terms_clauses'), 10, 3);
	}

	public static function invalidate_cache($term_id, $meta_key) {
		$cache_key = "{$term_id}__{$meta_key}";
		if(isset(self::$cache[$cache_key])) {
			unset(self::$cache[$cache_key]);
		}		
	}

	public static function get_term_meta($term_id, $meta_key, $single = false) {
		global $wpdb;

		$cache_key = "{$term_id}__{$meta_key}";

		if(isset(self::$cache[$cache_key])) {
			if(
				( $single && count(self::$cache[$cache_key]) == 1 )
				|| ( !$single && count(self::$cache[$cache_key]) > 1 )
			) {
				return self::$cache[$cache_key];
			}
		}

		$sql = $wpdb->prepare(
			"SELECT meta_value
				FROM {$wpdb->prefix}termsmeta
				WHERE term_id=%d AND meta_key=%s",
			$term_id,
			$meta_key
		);

		$value = $single  ? $wpdb->get_var( $sql )
						  : $wpdb->get_col( $sql );

		$value = maybe_unserialize( $value );

		if(is_array($value)) {
			$clean = array();

			foreach($value as $v) {
				$clean[] = maybe_unserialize($v);
			}

			$value = $clean;
		}

		self::$cache[$cache_key] = $single ? array($value) : $value;

		return $value;
	}

	public static function add_term_meta($term_id, $meta_key, $meta_value) {
		global $wpdb;

		self::invalidate_cache($term_id, $meta_key);

		$meta_value = is_array( $meta_value) ? maybe_serialize($meta_value) : $meta_value;

		$wpdb->insert(
			$wpdb->prefix . 'termsmeta',
			array(
				'term_id'    => $term_id,
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value
			),
			array(
				'%d',
				'%s',
				'%s'
			)
		);

		return $wpdb->insert_id;
	}

	public static function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') {
		global $wpdb;

		self::invalidate_cache($term_id, $meta_key);

		$meta_value = maybe_serialize($meta_value);
		$prev_value = maybe_serialize($prev_value);

		$sql = $wpdb->prepare(
			"SELECT COUNT(*)
			FROM {$wpdb->prefix}termsmeta
			WHERE meta_key=%s AND term_id=%d",
			$meta_key,
			$term_id
		);

		if($prev_value) {
			$sql .= $wpdb->prepare(
				" AND meta_value=%s",
				$prev_value
			);
		}

		$current = $wpdb->get_var( $sql );

		if($current) {
			$where = array(
				'term_id'    => $term_id,
				'meta_key'   => $meta_key
			);

			if($prev_value) {
				$where['meta_value'] = $prev_value;
			}

			return $wpdb->update(
				$wpdb->prefix . 'termsmeta',
				array(
					'meta_value' => $meta_value
				),
				$where,
				array(
					'%s'
				),
				array(
					'%d',
					'%s',
					'%s'
				)
			);
		} else {
			return self::add_term_meta($term_id, $meta_key, $meta_value);
		}
	} 

	public static function delete_term_meta($term_id, $meta_key = null, $meta_value = '') {
		global $wpdb;

		if( $meta_key ) {
			self::invalidate_cache($term_id, $meta_key);			
		}

		$sql = sprintf(
			"DELETE FROM {$wpdb->prefix}termsmeta WHERE term_id=%d",
			$term_id
		);

		if( $meta_key ) {
			$sql .= " AND meta_key='" . mysql_real_escape_string( $meta_key ) . "'";
		}

		if($meta_value) {
			$sql .= sprintf( " AND meta_value='%s'", mysql_real_escape_string($meta_value) );
		}

		return $wpdb->query( $sql );
	}

	public static function meta_query_terms_clauses($pieces, $taxonomies, $args) {
		global $wpdb;

		$relation = 'AND';
		$meta_queries = array();

		if( isset( $args['meta_query'] ) && is_array( $args['meta_query' ] ) ) {
			foreach( $args['meta_query'] as $key => $query ) {
				if( is_array( $query ) && isset( $query['key'] ) && isset( $query['value'] ) ) {
					$meta_queries[] = $query;
				} elseif( $key == 'relation' ) {
					$relation = $query;
				}
			}
		}

		$additional_wheres = array();
		
		foreach( $meta_queries as $i => $query ) {
			$pieces['join'] .= "\nINNER JOIN {$wpdb->prefix}termsmeta as mq_{$i} ON (mq_{$i}.term_id = t.term_id AND mq_{$i}.meta_key='" . mysql_real_escape_string($query['key']) . "')";

			$where = "mq_{$i}.meta_value";

			if( isset( $query['compare

				'] ) ) {
				switch($query['compare']) {
					case '=':
					case '!=':
					case '>':
					case '>=':
					case '<':
					case '<=':
					case 'LIKE':
					case 'NOT LIKE':
						$where .= " " . $query['compare'] . " '" . mysql_real_escape_string($query['value']) . "'";
						break;

					case 'IN':
					case 'NOT IN':
					case 'BETWEEN':
					case 'NOT BETWEEN':
						$full = "(";
						foreach($query['value'] as $i => $v) {
							$full .= "'" . mysql_real_escape_string($v) . "'"; 

							if( $i + 1 != count( $query['value'] ) ) {
								$full .= ',';
							}
						}
						$full .= ")";

						$where .= " " . $query['compare'] . " $full";
						break;
				}
			} else {
				$where .= "='" . mysql_real_escape_string($query['value']) . "'";
			}

			$additional_wheres[] = $where;
		}

		if( ! empty( $additional_wheres ) ) {
			$pieces['where'] .= " AND (" . implode(" $relation ", $additional_wheres) . ")";			
		}

		if( isset( $args['meta_key'] ) && ( isset( $args['meta_value'] ) || strpos($args['orderby'], 'meta_value') === 0 ) ) {
			$pieces['join'] .= "\nINNER JOIN {$wpdb->prefix}termsmeta as meta_query ON (meta_query.term_id = t.term_id AND meta_query.meta_key='" . mysql_real_escape_string($args['meta_key']) . "')";

			if( isset( $args['meta_value'] ) ) {
				$pieces['where'] .= " AND meta_query.meta_value='" . mysql_real_escape_string($args['meta_value']) . "'";
			}

			if( isset( $args['orderby'] ) && $args['orderby'] == 'meta_value' ) {
				$pieces['orderby'] = 'ORDER BY meta_query.meta_value';
			}

			if( isset( $args['orderby'] ) && $args['orderby'] == 'meta_value_num' ) {
				$pieces['orderby'] = 'CAST(meta_query.meta_value AS SIGNED)';
			}
		}

		return $pieces;
	}

	public function set_term($term_id) {
		$this->term_id = $term_id;
		return $this;
	}

	/**
	 * Set an ID of the object
	 * 
	 * @param int $id The identifier. same as set_term
	 */
	public function set_id( $id ) {
		$this->set_term( $id );
	}

	function get_value($key) {
		if( isset($this->term_id) ) {
			return GF_Datastore_Termsmeta::get_term_meta($this->term_id, $key, true);
		} else {
			return '';
		}
	}

	function get_multiple($key) {
		return $this->get_value($key);
	}

	function save_value($key, $value) {
		return GF_Datastore_Termsmeta::update_term_meta($this->term_id, $key, $value);
	}

	function save_multiple($key, $values = array()) {
		return $this->save_value($key, $values);
	}

	function delete_value($key) {
		return GF_Datastore_Termsmeta::delete_term_meta($this->term_id, $key);
	}	

	/**
	 * Check if a field with the same key has been registered
	 *
	 * @param string $key The key of the field
	 */
	function check_field_id( $key ) {
		if( isset( GF_Datastore_Termsmeta::$field_keys[ $key ] ) ) {
			GF_Exceptions::add( sprintf( __( 'Error: Trying to register a terms meta field with the %s key twice!', 'gf' ), $key ), 'unavailable_field_key' );
			return false;
		} else {
			GF_Datastore_Termsmeta::$field_keys[ $key ] = 1;
			return true;
		}
	}

	function install(){
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		# Create the table and add indexes
		$sql = array(
			"CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}termsmeta` (
			  meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  term_id bigint(20) unsigned NOT NULL,
			  meta_key varchar(255) DEFAULT NULL,
			  meta_value longtext,
			  PRIMARY KEY  (meta_id)
			) CHARSET=utf8 AUTO_INCREMENT=1",
			"ALTER TABLE  {$wpdb->prefix}termsmeta ADD KEY  term_id ( term_id )",
			"ALTER TABLE  {$wpdb->prefix}termsmeta ADD KEY  meta_key ( meta_key )"
		);

		foreach( $sql as $query ) {
			 dbDelta( $query );
		}
	}

	function uninstall() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}termsmeta" );
	}
}

# Register functions to be easily available for outside usage
if(!function_exists('get_term_meta')) {
	function get_term_meta($term_id, $meta_key, $single = false) {
		return GF_Datastore_Termsmeta::get_term_meta($term_id, $meta_key, $single);
	}
}

if(!function_exists('add_term_meta')) {
	function add_term_meta($term_id, $meta_key, $meta_value) {
		return GF_Datastore_Termsmeta::add_term_meta($term_id, $meta_key, $meta_value);
	}
}

if(!function_exists('update_term_meta')) {
	function update_term_meta($term_id, $meta_key, $meta_value, $prev_value = '') {
		return GF_Datastore_Termsmeta::update_term_meta($term_id, $meta_key, $meta_value, $prev_value);
	}
}

if(!function_exists('delete_term_meta')) {
	function delete_term_meta($term_id, $meta_key, $meta_value = '') {
		return GF_Datastore_Termsmeta::delete_term_meta($term_id, $meta_key, $meta_value);
	}
}

/**
 * Termsmeta has a few additional, static hooks.
 */
GF_Datastore_Termsmeta::add_actions();