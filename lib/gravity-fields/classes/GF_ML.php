<?php
/**
 * The base class which provides an universal API
 */
class GF_ML {
	/** @type GF_ML_Adapter Holds an instance of the active adapter */
	private static $adapter;

	/**
	 * Check if there is a multilingual plugin that's active.
	 * If support is enabled, but there are no plugins, a notice will be generated
	 */
	static function check() {
		# If there is an adapter set, it's all okay
		if( isset( self::$adapter ) ) {
			return true;
		}

		# Check if the multilingual GF functionality is available
		$enabled = get_option( 'gf_multilingual' );

		# The option might not be set but the theme might allow/deny it
		if( defined( 'GF_ENABLE_MULTILINGUAL' ) ) {
			$enabled = GF_ENABLE_MULTILINGUAL;
		}

		# Stop if not enabled
		if( ! $enabled ) {
			return false;
		}

		# Now that it's allowed, check adapters
		$available_adapters = array( 'GF_Qtranslate' );
		$available_adapters = apply_filters( 'gf_ml_adapters', $available_adapters);

		foreach($available_adapters as $adapter) {
			if( call_user_func( array( $adapter, 'check' ) ) ) {
				self::$adapter = new $adapter;
				break;
			}
		}

		# After checking adapters
		if( isset( self::$adapter ) ) {
			return true;
		} else {
			$message = __( 'There is no multilingual plugin that GF supports being installed or activated! Such a plugin is required for multilingual functionality the active theme or a plugin. Please don\'t edit anything before installing a plugin!', 'gf' );
			$message = apply_filters( 'gf_no_ml_plugin', $message );
			
			GF_Notices::add( $message, true );
			
			return false;
		}
	}

	/**
	 * Retrieves all active languages
	 * 
	 * @return mixed[] A miltidimentional array of languages
	 */
	static function get() {
		# Make sure that $adapter is loaded
		self::check();

		return self::$adapter ? self::$adapter->get() : array();
	}

	/**
	 * Split a string into multiple languages and return a specific one.
	 * Routes the actions through the active adapter.
	 * 
	 * @param string $value The value to be splitted
	 * @param string $language The language in which the value is needed
	 * @return mixed The extracted value in the right language or the default one
	 */
	static function split( $value, $language = null ) {
		# Make sure that $adapter is loaded
		self::check();

		return self::$adapter ? apply_filters( 'gf_ml_split', self::$adapter->split( $value, $language ), $value, $language ) : $value;
	}

	/**
	 * Joins multiple values into a single string.
	 * Routes the action through the active adapter.
	 * 
	 * @param mixed[] An array with values, whose keys are lang codes
	 * @return string The joined string
	 */
	static function join( $values ) {
		# Make sure that $adapter is loaded
		self::check();

		return self::$adapter ? apply_filters( 'gf_ml_join', self::$adapter->join( $values ), $values ) : $values;
	}
}