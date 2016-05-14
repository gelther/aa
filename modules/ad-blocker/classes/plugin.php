<?php
class Advanced_Ads_Ad_Blocker
{
	/**
	 * Singleton instance of the plugin
	 *
	 * @var     Advanced_Ads_Ad_Blocker
	 */
	protected static $instance;

	/**
	 * module options
	 *
	 * @since   1.0.0
	 * @var     array (if loaded)
	 */
	protected $options;

	/**
	 * upload directory
	 *
	 * @var     array (if loaded), false on failure
	 */
	protected $upload_dir;

	/**
	 * Initialize the module
	 *
	 * @since   1.0.0
	 */
	private function __construct() {
		$advads_options = Advanced_Ads::get_instance()->options();
		$options        = $this->options();
		if ( ! empty( $advads_options['use-adblocker'] ) &&
			! empty( $this->options['folder_name'] ) &&
			! empty( $this->options['module_can_work'] ) &&
			$this->get_upload_directory()
		) {
			add_action( 'plugins_loaded', array( $this, 'wp_plugins_loaded' ) );
		}
	}

	/**
	 * Return an instance of Advanced_Ads_Ad_Blocker
	 *
	 * @return Advanced_Ads_Ad_Blocker
	 * @since   1.0.0
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Add actions/filters/hooks and localisation after module have been loaded
	 *
	 * @since   1.0.0
	 */
	public function wp_plugins_loaded() {
		add_action( 'wp_enqueue_scripts', array( $this, 'edit_script_output' ), 101 );
	}

	/**
	 * Edit the script output (URL's) for all advanced-ads plugins
	 *
	 * @since   1.0.0
	 */
	public function edit_script_output() {
		global $wp_scripts, $wp_styles;

		// Get all plugin options
		$options = $this->options();

		// Check if the asset folder is set (check if this is installed yet)
		if ( isset( $options['folder_name'] ) && $options['folder_name'] != '' ) {
			// Loop through all script files and change the URL from which they are loaded
			foreach ( $wp_scripts->registered as $script ) {
				if ( strpos( $script->src, 'advanced-ads' ) !== false ) {
					$script->src = $this->clean_up_filename( $script->src );
				}
			}

			// Loop through all style files and change the URL from which they are loaded
			foreach ( $wp_styles->registered as $style ) {
				if ( strpos( $style->src, 'advanced-ads' ) !== false ) {
					$style->src = $this->clean_up_filename( $style->src );
				}
			}
		}
	}

	public function clean_up_filename( $file ) {
		$options    = $this->options();
		$upload_dir = $this->get_upload_directory();
		$url        = str_replace( WP_PLUGIN_URL, '', $file );
		if ( is_array( $options['lookup_table'][ $url ] ) && isset( $options['lookup_table'][ $url ]['path'] ) ) {
			return trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( $options['folder_name'] ) . $options['lookup_table'][ $url ]['path'];
		} elseif ( isset( $options['lookup_table'][ $url ] ) ) {
			return trailingslashit( $upload_dir['baseurl'] ) . trailingslashit( $options['folder_name'] ) . $options['lookup_table'][ $url ];
		}
		return $file;
	}

	/**
	 * Return module options
	 *
	 * @since   1.0.0
	 * @return array $options
	 */
	public function options() {
		if ( ! isset( $this->options ) ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				global $current_site;
				$this->options = get_blog_option( $current_site->blog_id, ADVADS_AB_SLUG, array() );
			} else {
				$this->options = get_option( ADVADS_AB_SLUG, array() );
			}

			if ( ! $this->options ) {
				$this->options = array();
			}
		}
		return $this->options;
	}

	/**
	 * Return path information on the currently configured uploads directory
	 *
	 * @return array , that have indices 'basedir' and 'baseurl', or false on failure
	 */
	public function get_upload_directory() {
		if ( ! isset( $this->upload_dir ) ) {
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				global $current_site;
				switch_to_blog( $current_site->blog_id );
				$upload_dir = wp_upload_dir();
				restore_current_blog();
			} else {
				$upload_dir = wp_upload_dir();
			}

			if ( $upload_dir['error'] ) {
				$this->upload_dir = false;
			} else {
				$this->upload_dir = $upload_dir;
			}
		}
		return $this->upload_dir;
	}
}
