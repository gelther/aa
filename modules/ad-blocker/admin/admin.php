<?php
class Advanced_Ads_Ad_Blocker_Admin
{
	/**
	 * Singleton instance of the plugin
	 *
	 * @var     Advanced_Ads_Ad_Blocker_Admin
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
	 * pattern to search assets using preg_match. The string ends with .css/.js/.png/.gif
	 *
	 * @var     string
	 */
	protected $search_file_pattern = '/(css|js|png|gif)$/';

	/**
	 * pattern to exclide directories from search. The string does not contain 'vendor/composer' or '/admin/'
	 *
	 * @var     string
	 */
	protected $exclude_dir_pattern = '/(vendor\/composer|\/admin\/)/';

	/**
	 * Array, containing path information on the currently configured uploads directory
	 *
	 * @var     array
	 */
	protected $upload_dir;

	/**
	 * Error messages for user
	 *
	 * @var     WP_Error
	 */
	protected $error_messages;

	/**
	 * path where the rebuild_form is located
	 *
	 * @var     string
	 */
	private $form_url = 'admin.php?page=advanced-ads-settings#top#general';

	/**
	 * Initialize the module
	 *
	 * @since   1.0.0
	 */
	public function __construct() {
		// Get the most recent options values
		$this->options = Advanced_Ads_Ad_Blocker::get_instance()->options();

		$this->upload_dir = Advanced_Ads_Ad_Blocker::get_instance()->get_upload_directory();

		// add module settings to Advanced Ads settings page
		add_action( 'advanced-ads-settings-init', array( $this, 'settings_init' ), 9, 1 );
		// add rebuild asset form
		add_filter( 'advanced-ads-settings-tab-after-form', array( $this, 'add_asset_rebuild_form_wrap' ) );

		add_filter( 'pre_update_option_' . ADVADS_AB_SLUG, array( $this, 'sanitize_settings' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'process_auto_update' ) );

		$this->error_messages = new WP_Error();
	}

	/**
	 * Return an instance of Advanced_Ads_Ad_Blocker
	 *
	 * @since   1.0.0
	 * @return Advanced_Ads_Ad_Blocker_Admin
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Render the ad-blocker rebuild assets form wrapper with rebuild assets form inside
	 *
	 * @param str $_setting_tab_id - id of the tab
	 */
	public function add_asset_rebuild_form_wrap( $_setting_tab_id ) {
		if ( $_setting_tab_id == 'general' ) {
			$advads_options = Advanced_Ads::get_instance()->options();
			$use_adblocker  = isset( $advads_options['use-adblocker'] );
			?>
			<div id="advads-adblocker-wrapper" <?php if ( ! $use_adblocker ) { echo 'style="display:none"'; } ?>>
				<?php
					// not ajax yet, print the form
					$button_attrs = array( 'disabled' => 'disabled', 'autocomplete' => 'off' );
					include ADVADS_AB_BASE_PATH . 'admin/views/rebuild_form.php';
				?>
			</div>
			<?php
		}
	}

	/**
	 * Render the ad-blocker rebuild assets form
	 */
	public function add_asset_rebuild_form() {
		$success = false;
		$message = '';
		// we already have similar check in the ad_ajax_callback.php, but this check is necessary if JS is disabled
		if ( isset( $_POST['advads_ab_form_submit'] ) ) { //new submission
			if ( false === ( $output = $this->process_form( $this->form_url ) ) ) {
				//we are displaying credentials form - no need for further processing
				return;
			} elseif ( is_wp_error( $output ) ) {
				$success = false;
				$message = $output->get_error_message();
			} else {
				$success = true;
				$message = __( 'The asset folder was rebuilt successfully', 'advanced-ads' );
			}

			include ADVADS_AB_BASE_PATH . 'admin/views/rebuild_form.php';
		}
	}

	/**
	 * Perform processing of the rebuild_form, sent by user
	 *
	 * @param  str  $form_url - URL of the page to display request form
	 * @return true           on success, false in case of wrong credentials, WP_Error in case of error
	 **/
	function process_form( $form_url ) {
		check_ajax_referer( 'advads_ab_form_nonce', 'security' );

		//fields that should be preserved across screens (when ftp/ssh login screen appears)
		$preserved_form_fields = array( 'advads_ab_form_submit', 'advads_ab_assign_new_folder', 'security', '_wp_http_referer' );
		//leave this empty to perform test for 'direct' writing
		$method                = '';
		//target folder
		$context               = $this->upload_dir['basedir'];
		//page url with nonce value
		$form_url              = wp_nonce_url( $form_url, 'advads_ab_form_nonce', 'security' );

		if ( ! $this->filesystem_init( $form_url, $method, $context, $preserved_form_fields ) ) {
			return false; //stop further processing when request form is displaying
		}
		// at this point we do not need ftp/ssh credentials anymore
		$form_post_fields = array_intersect_key( $_POST, array_flip( array( 'advads_ab_assign_new_folder' ) ) );

		$this->create_dummy_plugin( $form_post_fields );

		if ( $error_messages = $this->error_messages->get_error_messages() ) {
			foreach ( $error_messages as $error_message ) {
				Advanced_Ads::log( __METHOD__ . ': ' . $error_message );
			}

			return $this->error_messages; // WP_Error object
		}

		return true;
	}

	/**
	 * Initialize Filesystem object
	 *
	 * @param  str   $form_url - URL of the page to display request form
	 * @param  str   $method   - connection method
	 * @param  str   $context  - destination folder
	 * @param  array $fields   - fileds of $_POST array that should be preserved between screens
	 * @return bool            - false on failure, stored text on success
	 *
	 **/
	function filesystem_init( $form_url, $method, $context, $fields = null ) {
		global $wp_filesystem;

		// first attempt to get credentials
		if ( false === ( $creds = request_filesystem_credentials( $form_url, $method, false, $context, $fields ) ) ) {
			// we don't have credentials, so the request for them is displaying, no need for further processing
			return false;
		}

		// now we got some credentials - try to use them
		if ( ! WP_Filesystem( $creds ) ) {
			// incorrect connection data - ask for credentials again, now with error message
			request_filesystem_credentials( $form_url, $method, true, $context, $fields );
			return false;
		}

		return true; //filesystem object successfully initiated
	}

	/**
	 * Add settings to settings page
	 *
	 * @since   1.0.0
	 * @param string $hook settings page hook
	 */
	public function settings_init( $hook ) {
		//register_setting( ADVADS_AB_SLUG, ADVADS_AB_SLUG, array( $this, 'sanitize_settings' ) );

		add_settings_field(
			'use-adblocker',
			__( 'Ad blocker fix', 'advanced-ads' ),
			array( $this, 'render_settings_use_adblocker' ),
			$hook,
			'advanced_ads_setting_section'
		);
	}

	/**
	 * Catch the update options before it's submitted to the database
	 *
	 * @param  array $new_options new values that need to be saved
	 * @param  array $old_options old values saved in database
	 * @return array              - options to save
	 */
	public function sanitize_settings( $new_options, $old_options ) {
		if ( is_array( $new_options ) ) {
			$this->options = array_merge( $this->options, $new_options );
			// Error, disable the ad-blocker script
			if ( ! isset( $new_options['module_can_work'] ) ) {
				unset( $this->options['module_can_work'] );
			}
		}

		return $this->options;
	}

	/**
	 * Creates dummy plugin and return new options, that need to be stored in database
	 *
	 * @param  array $form_post_fields options, POST data sent by user
	 * @return array $new_options      - options, that need to be stored in database
	 */
	public function create_dummy_plugin( $form_post_fields = array() ) {
		global $wp_filesystem;

		$need_assign_new_name = isset( $form_post_fields['advads_ab_assign_new_folder'] );

		if ( ! $this->upload_dir ) {
			$message = __( 'There is no writable upload folder', 'advanced-ads' );
			$this->error_messages->add( 'create_dummy_1', $message );
			return false;
		}

		$new_options                    = $new_options_error = array();
		// $new_options_error does not have the 'module_can_work' key - ad-blocker script will be inactive and the asset folder will be rebuilt next time
		$new_options['module_can_work'] = true;

		if ( ! empty( $this->options['folder_name'] ) ) {
			$new_options['folder_name'] = $new_options_error['folder_name'] = $this->options['folder_name'];

			$old_folder_normalized = $this->normalize_path( trailingslashit( $this->upload_dir['basedir'] ) . $this->options['folder_name'] );

			if ( $wp_filesystem->exists( $old_folder_normalized ) ) {

				if ( $need_assign_new_name ) {
					$new_folder_name       = $this->generate_unique_name( array( $new_options['folder_name'] ) );
					$new_folder_normalized = $this->normalize_path( trailingslashit( $this->upload_dir['basedir'] ) . $new_folder_name );

					if ( ! $wp_filesystem->move( $old_folder_normalized, $new_folder_normalized ) ) {
						$message = sprintf( __( 'Unable to rename "%s" directory', 'advanced-ads' ), $old_folder_normalized );
						$this->error_messages->add( 'create_dummy_2', $message );
						return false;
					}
					$new_options['folder_name'] = $new_options_error['folder_name'] = $new_folder_name;

				}

				$is_rebuild_needed = $need_assign_new_name ? count( $this->get_assets( true ) ) : count( $this->get_assets() );

				// we have an error while the method is being executed
				update_option( ADVADS_AB_SLUG, $new_options_error );

				if ( $is_rebuild_needed ) {
					$lookup_table = $this->copy_assets( $new_options['folder_name'], $need_assign_new_name );
					if ( ! $lookup_table ) {
						$message = sprintf( __( 'Unable to copy assets to the "%s" directory', 'advanced-ads' ), $new_options['folder_name'] );
						$this->error_messages->add( 'create_dummy_3', $message );
						return false;
					}
					$new_options['lookup_table'] = $lookup_table;
				}

			} else {
				// we have an error while the method is being executed
				update_option( ADVADS_AB_SLUG, $new_options_error );
				// old folder does not exist, let's create it
				$lookup_table = $this->copy_assets( $new_options['folder_name'] );
				if ( ! $lookup_table ) {
					$message = sprintf( __( 'Unable to copy assets to the "%s" directory', 'advanced-ads' ), $new_options['folder_name'] );
					$this->error_messages->add( 'create_dummy_4', $message );
					return false;
				}
				$new_options['lookup_table'] = $lookup_table;
			}
		} else {
			// It seems this is the first time this plugin was ran, let's create everything we need in order to
			// have this plugin function normally.
			$new_folder_name            = $this->generate_unique_name();
			// Create a unique folder name
			$new_options['folder_name'] = $new_options_error['folder_name'] = $new_folder_name;
			// we have an error while the  method is being executed
			update_option( ADVADS_AB_SLUG, $new_options_error );
			// Copy the assets
			$lookup_table = $this->copy_assets( $new_options['folder_name'] );
			if ( ! $lookup_table ) {
				$message = sprintf( __( 'Unable to copy assets to the "%s" directory', 'advanced-ads' ), $new_options['folder_name'] );
				$this->error_messages->add( 'create_dummy_5', $message );
				return false;
			}
			$new_options['lookup_table'] = $lookup_table;
		}
		// successful result, save options and rewrite previous error options
		update_option( ADVADS_AB_SLUG, $new_options );
		Advanced_Ads_Admin_Notices::get_instance()->remove_from_queue( 'assets_expired' );
	}

	/**
	 * Copy all assets (JS/CSS) to the magic directory
	 *
	 * @since   1.0.0
	 * @param  string     $folder_name          destination folder
	 * @param  bool       $need_assign_new_name true if we need to assign new random names to assets
	 * @return bool/array                       - bool false on failure, array lookup table on success
	 */
	public function copy_assets( $folder_name, $need_assign_new_name = false ) {
		global $wp_filesystem;

		// Are we completely rebuilding the assets folder?
		$normalized_asset_path = $this->normalize_path( trailingslashit( $this->upload_dir['basedir'] ) . $folder_name );
		$asset_path            = trailingslashit( $this->upload_dir['basedir'] ) . $folder_name ;

		// already saved associations (original name => replaced name)
		$rand_asset_names = array();

		if ( $need_assign_new_name ) {
			// Check if there is a previous asset folder
			if ( $wp_filesystem->exists( $normalized_asset_path ) ) {
				// Remove the old directory and its contents
				if ( ! $wp_filesystem->rmdir( trailingslashit( $normalized_asset_path ), true ) ) {
					$message = sprintf( __( 'We do not have direct write access to the "%s" directory', 'advanced-ads' ), $normalized_asset_path );
					$this->error_messages->add( 'copy_assets_1', $message );
					return false;
				}
			}
		} elseif ( isset( $this->options['lookup_table'] ) ) {
			foreach ( $this->options['lookup_table'] as $orig_path => $replaced_info ) {
				$replaced_path = is_array( $replaced_info ) ? $replaced_info['path']  : $replaced_info;

				$orig_path_components     = preg_split( '/\//', $orig_path, -1, PREG_SPLIT_NO_EMPTY );
				$replaced_path_components = preg_split( '/\//', $replaced_path, -1, PREG_SPLIT_NO_EMPTY );

				foreach ( $orig_path_components as $k=> $orig_path_part ) {
					$rand_asset_names[ $orig_path_part ] = $replaced_path_components[ $k ];
				}
			}
		}

		if ( ! $wp_filesystem->exists( $normalized_asset_path ) ) {
			if ( ! $wp_filesystem->mkdir( $normalized_asset_path ) ) {
				$message = sprintf( __( 'We do not have direct write access to the "%s" directory', 'advanced-ads' ), $this->upload_dir['basedir'] );
				$this->error_messages->add( 'copy_assets_2', $message );
				return false;
			}
		}

		// lookup_table contains associations between the original path of the asset and it path within our magic folder
		// i.e: [advanced-ads-layer/admin/assets/css/admin.css] => array( path => /12/34/56/78/1347107783.css, size => 99 )
		if ( $need_assign_new_name ) {
			$assets       = $this->get_assets( true );
			$lookup_table = array();
		} else {
			$assets       = $this->get_assets( false );
			$lookup_table = isset( $this->options['lookup_table'] ) ? $this->options['lookup_table'] : array();
		}

		/* Do not rename assets and folders. If, for example, some library uses in file.css something like this:
		'background: url(/img/image.png)', you should add 'img') to this array */
		$not_rename_assets = array( 'public', 'assets', 'js', 'css', 'fancybox', 'advanced.js', 'jquery.fancybox-1.3.4.css' );

		// Loop through all the found assets
		foreach ( $assets as $file => $filesize ) {
			if ( ! file_exists( $file ) ) {
				continue;
			}

			$first_cleanup                = str_replace( WP_PLUGIN_DIR, '', $file );
			$first_cleanup_dir            = dirname( $first_cleanup );
			$first_cleanup_filename       = basename( $first_cleanup );
			$first_cleanup_file_extension = pathinfo( $first_cleanup, PATHINFO_EXTENSION );
			$path_components              = preg_split( '/\//', $first_cleanup_dir, -1, PREG_SPLIT_NO_EMPTY );
			$path_components_new          = array();

			foreach ( $path_components as $k => $dir ) {
				if ( in_array( $dir, $not_rename_assets ) ) {
					$path_components_new[ $k ] = $dir;
				} elseif ( array_key_exists( $dir, $rand_asset_names ) ) {
					$path_components_new[ $k ] = $rand_asset_names[ $dir ];
				} else {
					$new_rand_folder_name      = $this->generate_unique_name( $rand_asset_names );
					$path_components_new[ $k ] = $new_rand_folder_name;
					$rand_asset_names[ $dir ]  = $new_rand_folder_name;
				}
			}

			$new_dir_full = trailingslashit( $asset_path ) . trailingslashit( implode( '/', $path_components_new ) );
			$new_dir      = trailingslashit( implode( '/', $path_components_new ) );


			if ( ! in_array( $first_cleanup_filename, $not_rename_assets ) && ( $first_cleanup_file_extension == 'js' || $first_cleanup_file_extension == 'css' ) ) {
				if ( array_key_exists( $first_cleanup_filename, $rand_asset_names ) ) {
					$new_abs_file = $new_dir_full . $rand_asset_names[ $first_cleanup_filename ];
					$new_rel_file = $new_dir . $rand_asset_names[ $first_cleanup_filename ];
				} else {
					$new_filename                                = $this->generate_unique_name( $rand_asset_names ) . '.' . $first_cleanup_file_extension;
					$rand_asset_names[ $first_cleanup_filename ] = $new_filename;
					$new_abs_file                                = $new_dir_full . $new_filename;
					$new_rel_file                                = $new_dir . $new_filename;
				}
			} else {
				$new_abs_file = $new_dir_full . $first_cleanup_filename;
				$new_rel_file = $new_dir . $first_cleanup_filename;
			}

			if ( ! file_exists( $new_dir_full ) ) {
				// Create the path if it doesn't exist (prevents the copy() function from failing)
				if ( ! wp_mkdir_p( $new_dir_full ) ) {
					$message = sprintf( __( 'Unable to create "%s" directory. Is its parent directory writable by the server?', 'advanced-ads' ), $asset_path );
					$this->error_messages->add( 'copy_assets_4', $message );
					return false;
				}
			}

			$file         = $this->normalize_path( $file );
			$new_abs_file = $this->normalize_path( $new_abs_file );

			// Copy the file to our new magic directory
			if ( ! $wp_filesystem->copy( $file, $new_abs_file, true, FS_CHMOD_FILE ) ) {
				$message = sprintf( __( 'Unable to copy files to %s', 'advanced-ads' ), $normalized_asset_path );
				$this->error_messages->add( 'copy_assets_5', $message );
				return false;
			}

			$lookup_table[ $first_cleanup ] = array(
				'path' => $new_rel_file,
				'size' => $filesize,
			);
		}

		return $lookup_table;
	}

	/**
	 * This function recursively searches for assets
	 *
	 * @param  string $dir The directory to search in
	 * @return array       with pairs: abs_filename => size
	 */
	public function recursive_search_assets( $dir ) {
		$assets = array();

		$tree = glob( rtrim( $dir, '/' ) . '/*' );
		if ( is_array( $tree ) ) {
			foreach ( $tree as $file ) {
				if ( is_dir( $file ) && ! preg_match( $this->exclude_dir_pattern, $file ) ) {
					$assets = array_merge( $assets, $this->recursive_search_assets( $file ) );
				} elseif ( is_file( $file )  && preg_match( $this->search_file_pattern, $file ) ) {
					$assets[ $file ] = @filesize( $file );
				}
			}
		}

		return $assets;
	}

	/**
	 * Returns new or modified assets and their sizes
	 *
	 * @param  bool  $all true for all assets, false for modified or new assets
	 * @return array
	 */
	public function get_assets( $all = false ) {
		$new_files_info = $this->recursive_search_assets( trailingslashit( WP_PLUGIN_DIR ) . 'advanced-ads*' );

		if ( ! isset( $this->options['lookup_table'] ) || ! isset( $this->upload_dir['basedir'] ) || ! isset( $this->options['folder_name'] ) ) {
			return $new_files_info;
		}

		$asset_path = trailingslashit( trailingslashit( $this->upload_dir['basedir'] ) . $this->options['folder_name'] ) ;
		$new_files  = array();

		foreach ( $new_files_info as $abs_file => $size ) {
			$rel_file = str_replace( WP_PLUGIN_DIR, '', $abs_file );

			if ( ! isset( $this->options['lookup_table'][ $rel_file ]['size'] ) ||
				$this->options['lookup_table'][ $rel_file ]['size'] !== $size ||
				! file_exists( $asset_path . $this->options['lookup_table'][ $rel_file ]['path'] )
			) {
				$new_files[ $abs_file ] = $size;
			}
		}

		return $new_files;
	}

	/**
	 * Automatically updates assets
	 */
	public function process_auto_update() {
		$advads_options = Advanced_Ads::get_instance()->options();
		if ( ! isset( $advads_options['use-adblocker'] ) ) {
			return;
		}

		//if  module is working without errors and there are new assets
		if ( ! empty( $this->options['module_can_work'] ) && count( $this->get_assets() ) ) {
			if ( get_filesystem_method() !== 'direct' ) {
				// we can not update assets automatically. The user should visit the setting page and update assets manually
				// disable module and show notice
				unset( $this->options['module_can_work'] );
				update_option( ADVADS_AB_SLUG, $this->options );
				return;
			}

			$context = $this->upload_dir['basedir'];
			if ( ! $context || ! $this->filesystem_init( '', 'direct', $context ) ) {
				// disable module and show notice
				unset( $this->options['module_can_work'] );
				update_option( ADVADS_AB_SLUG, $this->options );
				return;
			}

			$this->create_dummy_plugin();

			// write errors to the log
			if ( $error_messages = $this->error_messages->get_error_messages() ) {
				foreach ( $error_messages as $error_message ) {
					Advanced_Ads::log( __METHOD__ . ': ' . $error_message );
				}
			}
		}
	}

	/**
	 * Replaces the 'direct' absolute path with the Filesystem API path. Useful only when the 'direct' method is not used.
	 * Check https://codex.wordpress.org/Filesystem_API for info
	 *
	 * @param  string existing path
	 * @return string normalized path
	 */
	public function normalize_path( $path ) {
		global $wp_filesystem;
		return str_replace( ABSPATH, $wp_filesystem->abspath(), $path );
	}

	/**
	 * Renders the settings page for this module
	 *
	 * @since   1.0.0
	 */
	public function render_settings(){}

	/**
	 * render setting to enable/disable adblocker
	 */
	public function render_settings_use_adblocker() {
		$advads_options = Advanced_Ads::get_instance()->options();
		$checked        = ( ! empty( $advads_options['use-adblocker'] ) ) ? 1 : 0;

		echo '<input id="advanced-ads-use-adblocker" type="checkbox" value="1" name="' . ADVADS_SLUG . '[use-adblocker]" ' . checked( $checked, 1, false ) . '>';
		echo '<p class="description">' . __( 'Prevents ad block software from breaking your website when blocking asset files (.js, .css).', 'advanced-ads' ) . '</p>';
	}

	/**
	 * Generate unique name
	 *
	 * @param  array  $haystack array to check, that the returned string does not exist in this array
	 * @return string           unique name
	 */
	function generate_unique_name( $haystack = false ) {
		if ( $haystack ) {
			while ( true ) {
				$needle =  mt_rand( 0, 999 );
				if ( ! in_array( $needle, $haystack ) ) {
					return  $needle;
				}
			}
		}
		return mt_rand( 0, 999 );
	}

}
