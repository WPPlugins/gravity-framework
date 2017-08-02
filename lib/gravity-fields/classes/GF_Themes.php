<?php
/**
 * Saves data about available themes and processes requests for templates
 */
class GF_Themes {
	/** @type mixed[] Holds an array for each active theme */
	protected $themes = array();

	/** @type mixed[] Holds an array for each avalable theme */
	protected $available = array();

	/**
	 * Add hooks
	 */
	function __construct() {
		add_action( 'gf_extend', array( $this, 'set_theme' ), 100 );
	}

	/**
	 * After additional themes are added, choose the right one
	 */
	function set_theme() {
		if( $chosen = get_option( 'gf_theme' ) ) {
			foreach( $this->available as $theme ) {
				if( $theme[ 'id' ] == $chosen ) {
					$this->themes[] = $theme;

					do_action( 'gf_skin_' . $theme[ 'id' ] );

					break;
				}
			}
		}
	}

	/**
	 * Register a theme through a theme or plugin
	 *
	 * @param array $args - id, title, dir and url of the theme
	 */
	function register( $args ) {
		# Check for all required keys
		$keys = apply_filters( 'gf_theme_args', array( 'id', 'title', 'dir', 'url' ) );

		if( count( array_intersect_key( $args, array_flip( $keys ) ) ) < count( $keys ) ) {
			gf_die( 'Incorrect keys for gf_register_theme!' );
		}

		# Add the theme to the right place
		array_unshift( $this->available, $args );
	}

	/**
	 * Return the path to a template item. Checks all active themes and defaults to the plugin
	 *
	 * @param string $template Required file
	 * @return string Path to the file
	 */
	function path( $template, $location = null ) {
		$location = apply_filters( 'gf_template_location', $location, $template );
		$template = apply_filters( 'gf_template', $template, $location );

		# Default path
		$path = GF_DIR . 'templates/' . $template . '.php';

		# If there's a specific location, try a recursive call for it
		if( $location ) {
			$temp = $template . '-' . $location . '.php';

			foreach( $this->themes as $theme ) {
				if( file_exists( $theme[ 'dir' ] . $temp ) ) {
					$path = $theme[ 'dir' ] . $temp;
				}
			}

			if( file_exists( GF_DIR . 'templates/' . $temp ) ) {
				$path = GF_DIR . 'templates/' . $temp;
			}
		}
		
		# Go back to the default template
		foreach( $this->themes as $theme ) {
			if( file_exists( $theme[ 'dir' ] . $template . '.php' ) ) {
				$path = $theme[ 'dir' ] . $template . '.php';
			}
		}

		return apply_filters( 'gf_template_path', $path );
	}

	/**
	 * Get a list of available themes
	 * 
	 * @return mixed[] List of themes
	 */
	function get() {
		return apply_filters( 'gf_themes', $this->available );
	}
}

/**
 * Creates an instance of the class that's globally available
 */
global $gravityfields;
$gravityfields->themes = new GF_Themes();