<?php

/**
 * Advanced Ads.
 *
 * @package   Advanced_Ads_Admin
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013-2015 Thomas Maier, webgilde GmbH
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package Advanced_Ads_Admin
 * @author  Thomas Maier <thomas.maier@webgilde.com>
 */
class Advanced_Ads_Admin {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Instance of admin notice class.
	 *
	 * @since    1.5.2
	 * @var      object
	 */
	protected $notices = null;

	/**
	 * Slug of the settings page
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	public $plugin_screen_hook_suffix = null;

	/**
	 * Slug of the ad group page
	 *
	 * @since    1.0.0
	 * @var      string
	 */
	protected $ad_group_hook_suffix = null;

	/**
	 * general plugin slug
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	protected $plugin_slug = '';

	/**
	 * post type slug
	 *
	 * @since   1.0.0
	 * @var     string
	 */
	protected $post_type = '';

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			new Advanced_Ads_Ad_Ajax_Callbacks;
		} else {
			add_action( 'plugins_loaded', array( $this, 'wp_plugins_loaded' ) );
		}
		// add shortcode creator to TinyMCE
		Advanced_Ads_Shortcode_Creator::get_instance();
		// registering custom columns needs to work with and without DOING_AJAX
		add_filter( 'manage_advanced_ads_posts_columns', array($this, 'ad_list_columns_head') ); // extra column
		add_filter( 'manage_advanced_ads_posts_custom_column', array($this, 'ad_list_columns_content'), 10, 2 ); // extra column
		add_filter( 'manage_advanced_ads_posts_custom_column', array($this, 'ad_list_columns_timing'), 10, 2 ); // extra column
		add_action( 'restrict_manage_posts', array( $this, 'ad_list_add_filters') );
	}

	public function wp_plugins_loaded() {
		/*
         * Call $plugin_slug from public plugin class.
         *
         */
		$plugin = Advanced_Ads::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();
		$this->post_type = constant( 'Advanced_Ads::POST_TYPE_SLUG' );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 9 );

		// Add menu items
		add_action( 'admin_menu', array($this, 'add_plugin_admin_menu') );
		add_action( 'admin_head', array( $this, 'highlight_menu_item' ) );

		// on post/ad edit screen
		add_action( 'edit_form_top', array($this, 'edit_form_above_title') );
		add_action( 'edit_form_after_title', array($this, 'edit_form_below_title') );
		add_action( 'admin_init', array($this, 'add_meta_boxes') );
		add_action( 'post_submitbox_misc_actions', array($this, 'add_submit_box_meta') );

		// save ads post type
		add_action( 'save_post', array($this, 'save_ad') );
		// delete ads post type
		add_action( 'delete_post', array($this, 'delete_ad') );

		// ad updated messages
		add_filter( 'post_updated_messages', array($this, 'ad_update_messages') );
		add_filter( 'bulk_post_updated_messages', array($this, 'ad_bulk_update_messages'), 10, 2 );

		// handling (ad) lists
		add_filter( 'request', array($this, 'ad_list_request') ); // order ads by title, not ID

		// settings handling
		add_action( 'admin_init', array($this, 'settings_init') );
		// update placements
		add_action( 'admin_init', array('Advanced_Ads_Placements', 'update_placements') );
		// check for add-on updates
		add_action( 'admin_init', array($this, 'add_on_updater'), 1 );
		
		// check for update logic
		add_action( 'admin_notices', array($this, 'admin_notices') );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( '__DIR__' ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array($this, 'add_action_links') );

		// add meta box for post types edit pages
		add_action( 'add_meta_boxes', array( $this, 'add_post_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_post_meta_box' ) );

		// register dashboard widget
		add_action( 'wp_dashboard_setup', array($this, 'add_dashboard_widget') );

		// set 1 column layout on overview page as user and page option
		add_filter( 'screen_layout_columns', array('Advanced_Ads_Overview_Widgets_Callbacks', 'one_column_overview_page') );
		add_filter( 'get_user_option_screen_layout_toplevel_page_advanced', array( 'Advanced_Ads_Overview_Widgets_Callbacks', 'one_column_overview_page_user') );
		
		// add links to plugin page
		add_filter( 'plugin_action_links_' . ADVADS_BASE, array( $this, 'add_plugin_links' ) );
		// display information when user is going to disable the plugin
		// add_filter( 'after_plugin_row_' . ADVADS_BASE, array( $this, 'display_deactivation_message' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 */
	public function enqueue_admin_styles() {
		wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), ADVADS_VERSION );
		if( self::screen_belongs_to_advanced_ads() ){
			// jQuery ui smoothness style 1.11.4
			wp_enqueue_style( $this->plugin_slug . '-jquery-ui-styles', plugins_url( 'assets/jquery-ui/jquery-ui.min.css', __FILE__ ), array(), '1.11.4' );
		}
		//wp_enqueue_style( 'jquery-style', '//code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css' );
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		// global js script
		wp_enqueue_script( $this->plugin_slug . '-admin-global-script', plugins_url( 'assets/js/admin-global.js', __FILE__ ), array('jquery'), ADVADS_VERSION );

		if( self::screen_belongs_to_advanced_ads() ){
		    wp_register_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array('jquery', 'jquery-ui-autocomplete'), ADVADS_VERSION );
		    // jquery ui
		    wp_enqueue_script( 'jquery-ui-accordion' );
		    wp_enqueue_script( 'jquery-ui-button' );
		    wp_enqueue_script( 'jquery-ui-tooltip' );

		    // just register this script for later inclusion on ad group list page
		    wp_register_script( 'inline-edit-group-ads', plugins_url( 'assets/js/inline-edit-group-ads.js', __FILE__ ), array('jquery'), ADVADS_VERSION );
		    
		    // register admin.js translations
		    $translation_array = array(
			    'condition_or' => __( 'or', 'advanced-ads' ),
			    'condition_and' => __( 'and', 'advanced-ads' ),
		    );
		    wp_localize_script( $this->plugin_slug . '-admin-script', 'advadstxt', $translation_array );
		    
		    wp_enqueue_script( $this->plugin_slug . '-admin-script' );
		}

		//call media manager for image upload only on ad edit pages
		$screen = get_current_screen();
		if( isset( $screen->id ) && Advanced_Ads::POST_TYPE_SLUG === $screen->id ) {
			// the 'wp_enqueue_media' function can be executed only once and should be called with the 'post' parameter
			// in this case, the '_wpMediaViewsL10n' js object inside html will contain id of the post, that is necessary to view oEmbed priview inside tinyMCE editor.
			// since other plugins can call the 'wp_enqueue_media' function without the 'post' parameter, Advanced Ads should call it earlier.
			global $post;
			wp_enqueue_media( array( 'post' => $post ) );
		}

	}

	/**
	 * check if the current screen belongs to Advanced Ads
	 *
	 * @since 1.6.6
	 * @return bool true if screen belongs to Advanced Ads
	 */
	static function screen_belongs_to_advanced_ads(){

		if( ! function_exists( 'get_current_screen' ) ){
		    return false;
		}
		
		$screen = get_current_screen();
		//echo $screen->id;
		if( !isset( $screen->id ) ) {
			return false;
		}

		$advads_pages = apply_filters( 'advanced-ads-dashboard-screens', array(
			'advanced-ads_page_advanced-ads-groups', // ad groups
			'edit-advanced_ads', // ads overview
			'advanced_ads', // ad edit page
			'advanced-ads_page_advanced-ads-placements', // placements
			'advanced-ads_page_advanced-ads-settings', // settings
			'toplevel_page_advanced-ads', // overview
			'admin_page_advanced-ads-debug', // debug
			'advanced-ads_page_advanced-ads-support', // support
			'admin_page_advanced-ads-intro', // intro
		));

		if( in_array( $screen->id, $advads_pages )){
			return true;
		}

		return false;
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		// add main menu item with overview page
		add_menu_page(
			__( 'Overview', 'advanced-ads' ), 'Advanced Ads', Advanced_Ads_Plugin::user_cap( 'advanced_ads_see_interface'), $this->plugin_slug, array($this, 'display_overview_page'), 'dashicons-chart-line', '58.74'
		);

		add_submenu_page(
			$this->plugin_slug, __( 'Ads', 'advanced-ads' ), __( 'Ads', 'advanced-ads' ), Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads'), 'edit.php?post_type=' . Advanced_Ads::POST_TYPE_SLUG
		);

		$this->ad_group_hook_suffix = add_submenu_page(
			$this->plugin_slug, __( 'Ad Groups', 'advanced-ads' ), __( 'Groups', 'advanced-ads' ), Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads'), $this->plugin_slug . '-groups', array($this, 'ad_group_admin_page')
		);

		// add placements page
		add_submenu_page(
			$this->plugin_slug, __( 'Ad Placements', 'advanced-ads' ), __( 'Placements', 'advanced-ads' ), Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_placements'), $this->plugin_slug . '-placements', array($this, 'display_placements_page')
		);
		// add settings page
		$this->plugin_screen_hook_suffix = add_submenu_page(
			$this->plugin_slug, __( 'Advanced Ads Settings', 'advanced-ads' ), __( 'Settings', 'advanced-ads' ), Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options'), $this->plugin_slug . '-settings', array($this, 'display_plugin_settings_page')
		);
		add_submenu_page(
			'options.php', __( 'Advanced Ads Debugging', 'advanced-ads' ), __( 'Debug', 'advanced-ads' ), Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options'), $this->plugin_slug . '-debug', array($this, 'display_plugin_debug_page')
		);
		// intro page
		add_submenu_page(
			'options.php', __( 'Advanced Ads Intro', 'advanced-ads' ), __( 'Advanced Ads Intro', 'advanced-ads' ), Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options'), $this->plugin_slug . '-intro', array($this, 'display_plugin_intro_page')
		);
		// add support page
		add_submenu_page(
			$this->plugin_slug, __( 'Support', 'advanced-ads' ), __( 'Support', 'advanced-ads' ), Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options'), $this->plugin_slug . '-support', array($this, 'display_support_page')
		);

		// allows extensions to insert sub menu pages
		do_action( 'advanced-ads-submenu-pages', $this->plugin_slug );
	}

	/**
	 * Highlights the 'Advanced Ads->Ads' item in the menu when an ad edit page is open
	 * @see the 'parent_file' and the 'submenu_file' filters for reference
	 */
	public function highlight_menu_item() {
		global $parent_file, $submenu_file, $post_type;

		if ( $post_type === $this->post_type ) {
			$parent_file = $this->plugin_slug;
			$submenu_file = 'edit.php?post_type=' . $this->post_type;			
		}
	}

	/**
	 * Render the overview page
	 *
	 * @since    1.2.2
	 */
	public function display_overview_page() {

		$screen = get_current_screen();

		// set up overview widgets
		Advanced_Ads_Overview_Widgets_Callbacks::setup_overview_widgets( $screen );

		// convert from vertical order to horizontal
		$screen->add_option( 'layout_columns', 1 );

		include ADVADS_BASE_PATH . 'admin/views/overview.php';
	}

	/**
	 * Render the settings page
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_settings_page() {
		include ADVADS_BASE_PATH . 'admin/views/settings.php';
	}

	/**
	 * Render the placements page
	 *
	 * @since    1.1.0
	 */
	public function display_placements_page() {
		$placement_types = Advanced_Ads_Placements::get_placement_types();
		$placements = Advanced_Ads::get_ad_placements_array(); // -TODO use model
		$items = Advanced_Ads_Placements::items_for_select();
		// load ads and groups for select field

		// display view
		include ADVADS_BASE_PATH . 'admin/views/placements.php';
	}

	/**
	 * Render the debug page
	 *
	 * @since    1.0.1
	 */
	public function display_plugin_debug_page() {
		// load array with ads by condition
		$plugin = Advanced_Ads::get_instance();
		$plugin_options = $plugin->options();
		$ad_placements = Advanced_Ads::get_ad_placements_array(); // -TODO use model

		include ADVADS_BASE_PATH . 'admin/views/debug.php';
	}

	/**
	 * Render intro page
	 *
	 * @since    1.6.8.2
	 */
	public function display_plugin_intro_page() {
		// load array with ads by condition

		// remove intro message from queue
		Advanced_Ads_Admin_Notices::get_instance()->remove_from_queue('nl_intro');

		include ADVADS_BASE_PATH . 'admin/views/intro.php';
	}

	/**
	 * Render the support page
	 *
	 * @since    1.6.8.1
	 */
	public function display_support_page() {
		// process email

		$mail_sent = false;
		$sent_errors = array();
		global $current_user;
		$user = wp_get_current_user();

		$email = $user->user_email !== '' ? $user->user_email : '';
		$name = $user->first_name !== '' ? $user->first_name . ' ' . $user->last_name : $user->user_login;
		$message = '';

		if( isset( $_POST['advads_support']['email'] ) ){

			$email = trim( $_POST['advads_support']['email'] );
			$name = trim( $_POST['advads_support']['name'] );
			$message = trim( $_POST['advads_support']['message'] );
			if( '' === $message ){
				$sent_errors[] = __('Please enter a message', 'advanced-ads');
			}
			if( is_email( $email ) ){
				$headers = 'From: '. $name .' <' . $email . '>' . "\r\n";
				$content = $message;
				$content .= "\r\n\r\n Name: " . $name;
				$content .= "\r\n URL: " . home_url();

				$mail_sent = wp_mail( 'support@wpadvancedads.com', 'Support for ' . home_url(), $content, $headers );
				if( ! $mail_sent ){
				    $sent_errors[] = sprintf(__('Email could NOT be sent. Please contact us directly at %s.', 'advanced-ads'), '<a href="mailto:support@wpadvancedads.com">support@wpadvancedads.com</a>');
				}
			} else {
			    $sent_errors[] = __('Please enter a valid email address', 'advanced-ads');
			}
		}

		include ADVADS_BASE_PATH . 'admin/views/support.php';
	}

	/**
	 * Render the ad group page
	 *
	 * @since    1.0.0
	 */
	public function ad_group_admin_page() {

		$taxonomy = Advanced_Ads::AD_GROUP_TAXONOMY;
		$post_type = Advanced_Ads::POST_TYPE_SLUG;
		$tax = get_taxonomy( $taxonomy );

		$action = $this->current_action();

		// handle new and updated groups
		if ( 'editedgroup' == $action ) {
			$group_id = (int) $_POST['group_id'];
			check_admin_referer( 'update-group_' . $group_id );

			if ( ! current_user_can( $tax->cap->edit_terms ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this feature.', 'advanced-ads' ) ); }

			// handle new groups
			if ( 0 == $group_id ) {
				$ret = wp_insert_term( $_POST['name'], $taxonomy, $_POST );
				if ( $ret && ! is_wp_error( $ret ) ) {
					$forced_message = 1; }
				else {
					$forced_message = 4; }
				// handle group updates
			} else {
				$tag = get_term( $group_id, $taxonomy );
				if ( ! $tag ) {
					wp_die( __( 'You attempted to edit an ad group that doesn&#8217;t exist. Perhaps it was deleted?', 'advanced-ads' ) ); }

				$ret = wp_update_term( $group_id, $taxonomy, $_POST );
				if ( $ret && ! is_wp_error( $ret ) ) {
					$forced_message = 3; }
				else {
					$forced_message = 5; }
			}
			// deleting items
		} elseif ( $action == 'delete' ){
			$group_id = (int) $_REQUEST['group_id'];
			check_admin_referer( 'delete-tag_' . $group_id );

			if ( ! current_user_can( $tax->cap->delete_terms ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this feature.', 'advanced-ads' ) ); }

			wp_delete_term( $group_id, $taxonomy );

			$forced_message = 2;
		}

		// handle views
		switch ( $action ) {
			case 'edit' :
				$title = $tax->labels->edit_item;
				if ( isset($_REQUEST['group_id']) ) {
					$group_id = absint( $_REQUEST['group_id'] );
					$tag = get_term( $group_id, $taxonomy, OBJECT, 'edit' );
				} else {
					$group_id = 0;
					$tag = false;
				}

				include ADVADS_BASE_PATH . 'admin/views/ad-group-edit.php';
				break;

			default :
				$title = $tax->labels->name;
				$wp_list_table = _get_list_table( 'WP_Terms_List_Table' );

				// load template
				include ADVADS_BASE_PATH . 'admin/views/ad-group.php';
		}
	}

	/**
	 * returns a link to the ad group list page
	 *
	 * @since 1.0.0
	 * @param arr $args additional arguments, e.g. action or group_id
	 * @return string admin url
	 */
	static function group_page_url($args = array()) {
		$plugin = Advanced_Ads::get_instance();

		$defaultargs = array(
			// 'post_type' => constant("Advanced_Ads::POST_TYPE_SLUG"),
			'page' => 'advanced-ads-groups',
		);
		$args = $args + $defaultargs;

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links($links) {

		return array_merge(
			array(
			'settings' => '<a href="' . admin_url( 'edit.php?post_type=advanced_ads&page=advanced-ads-settings' ) . '">' . __( 'Settings', 'advanced-ads' ) . '</a>'
				), $links
		);
	}

	/**
	 * add information above the ad title
	 *
	 * @since 1.5.6
	 * @param obj $post
	 */
	public function edit_form_above_title($post){
		if ( ! isset($post->post_type) || $post->post_type != $this->post_type ) {
			return;
		}
		$ad = new Advanced_Ads_Ad( $post->ID );

		include ADVADS_BASE_PATH . 'admin/views/ad-info-top.php';
	}

	/**
	 * add information about the ad below the ad title
	 *
	 * @since 1.1.0
	 * @param obj $post
	 */
	public function edit_form_below_title($post){
		if ( ! isset($post->post_type) || $post->post_type != $this->post_type ) {
			return;
		}
		$ad = new Advanced_Ads_Ad( $post->ID );

		include ADVADS_BASE_PATH . 'admin/views/ad-info.php';
	}

	/**
	 * Add meta boxes
	 *
	 * @since    1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'ad-main-box', __( 'Ad Type', 'advanced-ads' ), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
		);
		add_meta_box(
			'ad-parameters-box', __( 'Ad Parameters', 'advanced-ads' ), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
		);
		add_meta_box(
			'ad-output-box', __( 'Layout / Output', 'advanced-ads' ), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
		);
		add_meta_box(
			'ad-display-box', __( 'Display Conditions', 'advanced-ads' ), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
		);
		add_meta_box(
			'ad-visitor-box', __( 'Visitor Conditions', 'advanced-ads' ), array($this, 'markup_meta_boxes'), Advanced_Ads::POST_TYPE_SLUG, 'normal', 'high'
		);
	}

	/**
	 * add meta values below submit box
	 *
	 * @since 1.3.15
	 */
	public function add_submit_box_meta(){
		global $post, $wp_locale;

		if ( $post->post_type !== Advanced_Ads::POST_TYPE_SLUG ) { return; }

		$ad = new Advanced_Ads_Ad( $post->ID );

		// get time set for ad or current timestamp (both GMT)
		$utc_ts = $ad->expiry_date ? $ad->expiry_date : time();
		$utc_time = date_create( '@' . $utc_ts );
        $tz_option = get_option( 'timezone_string' );
        $exp_time = clone( $utc_time );
        
        if ( $tz_option ) {
            $exp_time->setTimezone( self::get_wp_timezone() );
        } else {
            $tz_name = self::timezone_get_name( self::get_wp_timezone() );
            $tz_offset = substr( $tz_name, 3 );
            $off_time = date_create( $utc_time->format( 'Y-m-d\TH:i:s' ) . $tz_offset );
            $offset_in_sec = date_offset_get( $off_time );
            $exp_time = date_create( '@' . ( $utc_ts + $offset_in_sec ) );
        }
        
		list( $curr_year, $curr_month, $curr_day, $curr_hour, $curr_minute ) = explode( '-', $exp_time->format( 'Y-m-d-H-i' ) );
		$enabled = 1 - empty($ad->expiry_date);

		include ADVADS_BASE_PATH . 'admin/views/ad-submitbox-meta.php';
	}

	/**
	 * load templates for all meta boxes
	 *
	 * @since 1.0.0
	 * @param obj $post
	 * @param array $box
	 * @todo move ad initialization to main function and just global it
	 */
	public function markup_meta_boxes($post, $box) {
		$ad = new Advanced_Ads_Ad( $post->ID );

		switch ( $box['id'] ) {
			case 'ad-main-box':
				$view = 'ad-main-metabox.php';
				break;
			case 'ad-parameters-box':
				$view = 'ad-parameters-metabox.php';
				break;
			case 'ad-output-box':
				$view = 'ad-output-metabox.php';
				break;
			case 'ad-display-box':
				$view = 'ad-display-metabox.php';
				break;
			case 'ad-visitor-box':
				$view = 'ad-visitor-metabox.php';
				break;
		}

		if ( ! isset( $view ) ) {
			return;
		}
		include ADVADS_BASE_PATH . 'admin/views/' . $view;
	}

	/**
	 * prepare the ad post type to be saved
	 *
	 * @since 1.0.0
	 * @param int $post_id id of the post
	 * @todo handling this more dynamic based on ad type
	 */
	public function save_ad($post_id) {

		// only use for ads, no other post type
		if ( ! isset($_POST['post_type']) || $this->post_type != $_POST['post_type'] || ! isset($_POST['advanced_ad']['type']) ) {
			return;
		}

		// don’t do this on revisions
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// get ad object
		$ad = new Advanced_Ads_Ad( $post_id );
		if ( ! $ad instanceof Advanced_Ads_Ad ) {
			return;
		}

		// filter to allow change of submitted ad settings
		$_POST['advanced_ad'] = apply_filters( 'advanced-ads-ad-settings-pre-save', $_POST['advanced_ad'] );

		$ad->type = $_POST['advanced_ad']['type'];
		if ( isset($_POST['advanced_ad']['output']) ) {
			$ad->set_option( 'output', $_POST['advanced_ad']['output'] );
		} else {
			$ad->set_option( 'output', array() );
		}
		/**
		 * deprecated since introduction of "visitors" in 1.5.4
		 */
		if ( isset($_POST['advanced_ad']['visitor']) ) {
			$ad->set_option( 'visitor', $_POST['advanced_ad']['visitor'] );
		} else {
			$ad->set_option( 'visitor', array() );
		}
		// visitor conditions
		if ( isset($_POST['advanced_ad']['visitors']) ) {
			$ad->set_option( 'visitors', $_POST['advanced_ad']['visitors'] );
		} else {
			$ad->set_option( 'visitors', array() );
		}
		$ad->url = 0;
		if ( isset($_POST['advanced_ad']['url']) ) {
			$ad->url = esc_url( $_POST['advanced_ad']['url'] );
		}
		// save size
		$ad->width = 0;
		if ( isset($_POST['advanced_ad']['width']) ) {
			$ad->width = absint( $_POST['advanced_ad']['width'] );
		}
		$ad->height = 0;
		if ( isset($_POST['advanced_ad']['height']) ) {
			$ad->height = absint( $_POST['advanced_ad']['height'] );
		}

		if ( ! empty($_POST['advanced_ad']['description']) ) {
			$ad->description = esc_textarea( $_POST['advanced_ad']['description'] ); }
		else { $ad->description = ''; }

		if ( ! empty($_POST['advanced_ad']['content']) ) {
			$ad->content = $_POST['advanced_ad']['content']; }
		else { $ad->content = ''; }

		if ( ! empty($_POST['advanced_ad']['conditions']) ){
			$ad->conditions = $_POST['advanced_ad']['conditions'];
		} else {
			$ad->conditions = array();
		}
		// prepare expiry date
		if ( isset($_POST['advanced_ad']['expiry_date']['enabled']) ) {
			$year   = absint( $_POST['advanced_ad']['expiry_date']['year'] );
			$month  = absint( $_POST['advanced_ad']['expiry_date']['month'] );
			$day    = absint( $_POST['advanced_ad']['expiry_date']['day'] );
			$hour   = absint( $_POST['advanced_ad']['expiry_date']['hour'] );
			$minute = absint( $_POST['advanced_ad']['expiry_date']['minute'] );

			$expiration_date = sprintf( "%04d-%02d-%02d %02d:%02d:%02d", $year, $month, $day, $hour, $minute, '00' );
			$valid_date = wp_checkdate( $month, $day, $year, $expiration_date );

			if ( !$valid_date ) {
				$ad->expiry_date = 0;
			} else {
				$_gmDate = date_create( $expiration_date, self::get_wp_timezone() );
                $_gmDate->setTimezone( new DateTimeZone( 'UTC' ) );
				$gmDate = $_gmDate->format( 'Y-m-d-H-i' );
				list( $year, $month, $day, $hour, $minute ) = explode( '-', $gmDate );
				$ad->expiry_date = gmmktime($hour, $minute, 0, $month, $day, $year);
			}
		} else {
			$ad->expiry_date = 0;
		}

		$image_id = ( isset( $_POST['advanced_ad']['output']['image_id'] ) ) ? absint( $_POST['advanced_ad']['output']['image_id'] ) : 0;
		if ( $image_id ) {
			$all_posts_id = get_post_meta( $image_id, '_advanced-ads_parent_id' );

			if ( ! in_array ( $post_id, $all_posts_id ) ) {
				add_post_meta( $image_id, '_advanced-ads_parent_id', $post_id, false  );
			}
		}

		$ad->save();
	}

	/**
	 * prepare the ad post type to be removed
	 *
	 * @param int $post_id id of the post
	 */
	public function delete_ad( $post_id ) {
		global $wpdb;

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $post_id > 0 ) {
			$post_type = get_post_type( $post_id );
			if ( $post_type == $this->post_type ) {
				$wpdb->query(
					$wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %d", '_advanced-ads_parent_id', $post_id )
				);
			}
		}
	}

		/**
		 * edit ad update messages
		 *
		 * @since 1.4.7
		 * @param arr $messages existing post update messages
		 * @return arr $messages
		 *
		 * @see wp-admin/edit-form-advanced.php
		 */
		public function ad_update_messages($messages = array()){
			$post = get_post();

			// added to hide error message caused by third party code that uses post_updated_messages filter wrong
			if( ! is_array( $messages )){
			    return $messages;
			}

			$messages[Advanced_Ads::POST_TYPE_SLUG] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Ad updated.', 'advanced-ads' ),
		4  => __( 'Ad updated.', 'advanced-ads' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Ad restored to revision from %s', 'advanced-ads' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Ad published.', 'advanced-ads' ),
		7  => __( 'Ad saved.', 'advanced-ads' ),
		8  => __( 'Ad submitted.', 'advanced-ads' ),
		9  => sprintf(
			__( 'Ad scheduled for: <strong>%1$s</strong>.', 'advanced-ads' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i', 'advanced-ads' ), strtotime( $post->post_date ) )
		),
		10 => __( 'Ad draft updated.', 'advanced-ads' )
			);
			return $messages;
		}

		/**
		 * edit ad bulk update messages
		 *
		 * @since 1.4.7
		 * @param arr $messages existing bulk update messages
		 * @param arr $counts numbers of updated ads
		 * @return arr $messages
		 *
		 * @see wp-admin/edit.php
		 */
		public function ad_bulk_update_messages(array $messages, array $counts){
			$post = get_post();

			$messages[Advanced_Ads::POST_TYPE_SLUG] = array(
				'updated'   => _n( '%s ad updated.', '%s ads updated.', $counts['updated'] ),
				'locked'    => _n( '%s ad not updated, somebody is editing it.', '%s ads not updated, somebody is editing them.', $counts['locked'] ),
				'deleted'   => _n( '%s ad permanently deleted.', '%s ads permanently deleted.', $counts['deleted'] ),
				'trashed'   => _n( '%s ad moved to the Trash.', '%s ads moved to the Trash.', $counts['trashed'] ),
				'untrashed' => _n( '%s ad restored from the Trash.', '%s ads restored from the Trash.', $counts['untrashed'] ),
			);

			return $messages;
		}

		/**
	 * get action from the params
	 *
	 * @since 1.0.0
	 */
		public function current_action() {
			if ( isset($_REQUEST['action']) && -1 != $_REQUEST['action'] ) {
				return $_REQUEST['action'];
			}

			return false;
		}

		/**
	 * initialize settings
	 *
	 * @since 1.0.1
	 */
		public function settings_init(){

			// get settings page hook
			$hook = $this->plugin_screen_hook_suffix;

			// register settings
			register_setting( ADVADS_SLUG, ADVADS_SLUG, array($this, 'sanitize_settings') );

			// general settings section
			add_settings_section(
				'advanced_ads_setting_section',
				__( 'General', 'advanced-ads' ),
				array($this, 'render_settings_section_callback'),
				$hook
			);

			// licenses section only for main blog
			if( is_main_site( get_current_blog_id() ) ){
			    // register license settings
			    register_setting( ADVADS_SLUG . '-licenses', ADVADS_SLUG . '-licenses', array( $this, 'sanitize_license_keys' ) );

			    add_settings_section(
				    'advanced_ads_settings_license_section',
				    __( 'Licenses', 'advanced-ads' ),
				    array($this, 'render_settings_licenses_section_callback'),
				    'advanced-ads-settings-license-page'
			    );

			    add_filter( 'advanced-ads-setting-tabs', array( $this, 'license_tab') );
			}

			// add setting fields to disable ads
			add_settings_field(
				'disable-ads',
				__( 'Disable ads', 'advanced-ads' ),
				array($this, 'render_settings_disable_ads'),
				$hook,
				'advanced_ads_setting_section'
			);
			// add setting fields for user role
			add_settings_field(
				'hide-for-user-role',
				__( 'Hide ads for logged in users', 'advanced-ads' ),
				array($this, 'render_settings_hide_for_users'),
				$hook,
				'advanced_ads_setting_section'
			);
			// add setting fields for advanced js
			add_settings_field(
				'activate-advanced-js',
				__( 'Use advanced JavaScript', 'advanced-ads' ),
				array($this, 'render_settings_advanced_js'),
				$hook,
				'advanced_ads_setting_section'
			);
			// add setting fields for content injection protection
			add_settings_field(
				'content-injection-everywhere',
				__( 'Unlimited ad injection', 'advanced-ads' ),
				array($this, 'render_settings_content_injection_everywhere'),
				$hook,
				'advanced_ads_setting_section'
			);
			// add setting fields for content injection priority
			add_settings_field(
				'content-injection-priority',
				__( 'Priority of content injection filter', 'advanced-ads' ),
				array($this, 'render_settings_content_injection_priority'),
				$hook,
				'advanced_ads_setting_section'
			);
			// add setting fields for content injection priority
			add_settings_field(
				'block-bots',
				__( 'Hide ads from bots', 'advanced-ads' ),
				array($this, 'render_settings_block_bots'),
				$hook,
				'advanced_ads_setting_section'
			);
			// opt out from internal notices
			add_settings_field(
				'disable-notices',
				__( 'Disable notices', 'advanced-ads' ),
				array($this, 'render_settings_disabled_notices'),
				$hook,
				'advanced_ads_setting_section'
			);
			// opt out from internal notices
			add_settings_field(
				'front-prefix',
				__( 'ID prefix', 'advanced-ads' ),
				array($this, 'render_settings_front_prefix'),
				$hook,
				'advanced_ads_setting_section'
			);
			// remove id from widgets
			add_settings_field(
				'remove-widget-id',
				__( 'Remove Widget ID', 'advanced-ads' ),
				array($this, 'render_settings_remove_widget_id'),
				$hook,
				'advanced_ads_setting_section'
			);
			// allow editors to manage ads
			add_settings_field(
				'editors-manage-ads',
				__( 'Allow editors to manage ads', 'advanced-ads' ),
				array($this, 'render_settings_editors_manage_ads'),
				$hook,
				'advanced_ads_setting_section'
			);

			// hook for additional settings from add-ons
			do_action( 'advanced-ads-settings-init', $hook );
		}

		/**
		 * add license tab
		 *
		 * arr $tabs setting tabs
		 */
		public function license_tab( array $tabs ){

			$tabs['licenses'] = array(
				'page' => 'advanced-ads-settings-license-page',
				'group' => ADVADS_SLUG . '-licenses',
				'tabid' => 'licenses',
				'title' => __( 'Licenses', 'advanced-ads' )
			);

			return $tabs;
		}

		/**
	 * render settings section
	 *
	 * @since 1.1.1
	 */
		public function render_settings_section_callback(){
			// for whatever purpose there might come
		}

		/**
	 * render licenses settings section
	 *
	 * @since 1.5.1
	 */
		public function render_settings_licenses_section_callback(){
			echo '<p>'. __( 'Enter license keys for our powerful <a href="'.ADVADS_URL.'add-ons/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-licenses" target="_blank">add-ons</a>.', 'advanced-ads' ) .'</p>';
			// nonce field
			echo '<input type="hidden" id="advads-licenses-ajax-referrer" value="' . wp_create_nonce( "advads_ajax_license_nonce" ) . '"/>';
		}

		/**
	 * options to disable ads
	 *
	 * @since 1.3.11
	 */
		public function render_settings_disable_ads(){
			$options = Advanced_Ads::get_instance()->options();

			// set the variables
			$disable_all = isset($options['disabled-ads']['all']) ? 1 : 0;
			$disable_404 = isset($options['disabled-ads']['404']) ? 1 : 0;
			$disable_archives = isset($options['disabled-ads']['archives']) ? 1 : 0;
			$disable_secondary = isset($options['disabled-ads']['secondary']) ? 1 : 0;
			$disable_feed = ( ! isset( $options['disabled-ads']['feed'] ) || $options['disabled-ads']['feed'] ) ? 1 : 0;

			// load the template
			include ADVADS_BASE_PATH . 'admin/views/settings-disable-ads.php';
		}

		/**
	 * render setting to hide ads from logged in users
	 *
	 * @since 1.1.1
	 */
		public function render_settings_hide_for_users(){
			$options = Advanced_Ads::get_instance()->options();
			$current_capability_role = isset($options['hide-for-user-role']) ? $options['hide-for-user-role'] : 0;

			$capability_roles = array(
			'' => __( '(display to all)', 'advanced-ads' ),
			'read' => __( 'Subscriber', 'advanced-ads' ),
			'delete_posts' => __( 'Contributor', 'advanced-ads' ),
			'edit_posts' => __( 'Author', 'advanced-ads' ),
			'edit_pages' => __( 'Editor', 'advanced-ads' ),
			'activate_plugins' => __( 'Admin', 'advanced-ads' ),
			);
			echo '<select name="'.ADVADS_SLUG.'[hide-for-user-role]">';
			foreach ( $capability_roles as $_capability => $_role ) {
				echo '<option value="'.$_capability.'" '.selected( $_capability, $current_capability_role, false ).'>'.$_role.'</option>';
			}
			echo '</select>';

			echo '<p class="description">'. __( 'Choose the lowest role a user must have in order to not see any ads.', 'advanced-ads' ) .'</p>';
		}

		/**
	 * render setting to display advanced js file
	 *
	 * @since 1.2.3
	 */
		public function render_settings_advanced_js(){
			$options = Advanced_Ads::get_instance()->options();
			$checked = ( ! empty($options['advanced-js'])) ? 1 : 0;

			// display notice if js file was overridden
			if( ! $checked && apply_filters( 'advanced-ads-activate-advanced-js', $checked ) ){
				echo '<p>' . __( '<strong>notice: </strong>the file is currently enabled by an add-on that needs it.', 'advanced-ads' ) . '</p>';
			}
			echo '<input id="advanced-ads-advanced-js" type="checkbox" value="1" name="'.ADVADS_SLUG.'[advanced-js]" '.checked( $checked, 1, false ).'>';
			echo '<p class="description">'. sprintf( __( 'Enable advanced JavaScript functions (<a href="%s" target="_blank">here</a>). Some features and add-ons might override this setting if they need features from this file.', 'advanced-ads' ), ADVADS_URL . 'javascript-functions/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings' ) .'</p>';
		}

	/**
	 * render setting for content injection protection
	 *
	 * @since 1.4.1
	 */
	public function render_settings_content_injection_everywhere(){
		$options = Advanced_Ads::get_instance()->options();
		$everywhere = ( isset($options['content-injection-everywhere']) ) ? true : false;

		echo '<input id="advanced-ads-injection-everywhere" type="checkbox" value="true" name="'.ADVADS_SLUG.'[content-injection-everywhere]" '.checked( $everywhere, true, false ).'>';
		echo '<p class="description">'. __( 'Some plugins and themes trigger ad injection where it shouldn’t happen. Therefore, Advanced Ads ignores injected placements on non-singular pages and outside the loop. However, this can cause problems with some themes. You can enable this option if you don’t see ads or want to enable ad injections on archive pages AT YOUR OWN RISK.', 'advanced-ads' ) .'</p>';

	}

		/**
	 * render setting for content injection priority
	 *
	 * @since 1.4.1
	 */
		public function render_settings_content_injection_priority(){
			$options = Advanced_Ads::get_instance()->options();
			$priority = ( isset($options['content-injection-priority'])) ? intval( $options['content-injection-priority'] ) : 100;

			echo '<input id="advanced-ads-content-injection-priority" type="number" value="'.$priority.'" name="'.ADVADS_SLUG.'[content-injection-priority]" size="3"/>';
			echo '<p class="description">';
			if ( $priority < 11 ) {
				echo '<span class="advads-error-message">' . __( 'Please check your post content. A priority of 10 and below might cause issues (wpautop function might run twice).', 'advanced-ads' ) . '</span><br />';
			}
			_e( 'Play with this value in order to change the priority of the injected ads compared to other auto injected elements in the post content.', 'advanced-ads' );
			echo '</p>';
		}

		/**
	 * render setting for blocking bots
	 *
	 * @since 1.4.9
	 */
		public function render_settings_block_bots(){
			$options = Advanced_Ads::get_instance()->options();
			$checked = ( ! empty($options['block-bots'])) ? 1 : 0;

			echo '<input id="advanced-ads-block-bots" type="checkbox" value="1" name="'.ADVADS_SLUG.'[block-bots]" '.checked( $checked, 1, false ).'>';
			echo '<p class="description">'. sprintf( __( 'Hide ads from crawlers, bots and empty user agents. Also prevents counting impressions for bots when using the <a href="%s" target="_blank">Tracking Add-On</a>.', 'advanced-ads' ), ADVADS_URL . 'add-ons/tracking/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings' ) .'<br/>'
						. __( 'Disabling this option only makes sense if your ads contain content you want to display to bots (like search engines) or your site is cached and bots could create a cached version without the ads.', 'advanced-ads' ) . '</p>';
		}

		/**
	 * render setting to disable notices
	 *
	 * @since 1.5.3
	 */
		public function render_settings_disabled_notices(){
			$options = Advanced_Ads::get_instance()->options();
			$checked = ( ! empty($options['disable-notices'])) ? 1 : 0;

			echo '<input id="advanced-ads-disabled-notices" type="checkbox" value="1" name="'.ADVADS_SLUG.'[disable-notices]" '.checked( $checked, 1, false ).'>';
			echo '<p class="description">'. __( 'Disable internal notices like tips, tutorials, email newsletters and update notices. Disabling notices is recommended if you run multiple blogs with Advanced Ads already.', 'advanced-ads' ) . '</p>';
		}

		/**
		* render setting for frontend prefix
		*
		* @since 1.6.8
		*/
		public function render_settings_front_prefix(){
			$options = Advanced_Ads::get_instance()->options();

			$prefix = Advanced_Ads_Plugin::get_instance()->get_frontend_prefix();
			$old_prefix = ( isset($options['id-prefix'])) ? esc_attr( $options['id-prefix'] ) : '';

			echo '<input id="advanced-ads-front-prefix" type="text" value="' .$prefix .'" name="'.ADVADS_SLUG.'[front-prefix]" />';
			// deprecated
			echo '<input type="hidden" value="' .$old_prefix .'" name="'.ADVADS_SLUG.'[id-prefix]" />';
			echo '<p class="description">'. __( 'Prefix of class or id attributes in the frontend. Change it if you don’t want <strong>ad blockers</strong> to mark these blocks as ads.<br/>You might need to <strong>rewrite css rules afterwards</strong>.', 'advanced-ads' ) .'</p>';
		}

		/**
		* render setting to remove the id from advanced ads widgets
		*
		* @since 1.6.8.2
		*/
		public function render_settings_remove_widget_id(){
			$options = Advanced_Ads::get_instance()->options();

			// is true by default if no options where previously set
			if( ! isset($options['remove-widget-id']) && $options !== array() ){
			    $remove = false;
			} elseif( $options === array() ){
			    $remove = true;
			} else {
			    $remove = true;
			}

			echo '<input id="advanced-ads-remove-widget-id" type="checkbox" ' . checked( $remove, true, false ) . ' name="'.ADVADS_SLUG.'[remove-widget-id]" />';
			echo '<p class="description">' . __( 'Remove the ID attribute from widgets in order to not make them an easy target of ad blockers.', 'advanced-ads' );

			if ( class_exists( 'q2w3_fixed_widget', false ) ) {
				echo '<br />' . __( 'If checked, the Advanced Ads Widget will not work with the fixed option of the <strong>Q2W3 Fixed Widget</strong> plugin.', 'advanced-ads' );
			}

			echo '</p>';
		}
		
		/**
		 * render setting to allow editors to manage ads
		 * 
		 * @since 1.6.14
		 */
		public function render_settings_editors_manage_ads(){
			$options = Advanced_Ads::get_instance()->options();

			// is false by default if no options where previously set
			if( isset($options['editors-manage-ads']) && $options['editors-manage-ads'] ){
			    $allow = true;
			} else {
			    $allow = false;
			}

			echo '<input id="advanced-ads-editors-manage-ads" type="checkbox" ' . checked( $allow, true, false ) . ' name="'.ADVADS_SLUG.'[editors-manage-ads]" />';
			echo '<p class="description">'. __( 'Allow editors to also manage and publish ads.', 'advanced-ads' ) . 
				' ' . sprintf(__( 'You can assign different ad-related roles on a user basis with <a href="%s" target="_blank">Advanced Ads Pro</a>.', 'advanced-ads' ), ADVADS_URL . 'add-ons/advanced-ads-pro/#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings') . '</p>';
		    
		}
		
		/**
		 * sanitize plugin settings
		 *
		 * @since 1.5.1
		 * @param array $options all the options
		 */
		public function sanitize_settings($options){

			// sanitize whatever option one wants to sanitize

			if ( isset( $options['front-prefix'] ) ) {
				$options['front-prefix'] = sanitize_html_class( $options['front-prefix'], Advanced_Ads_Plugin::DEFAULT_FRONTEND_PREFIX );
			}

			$options = apply_filters( 'advanced-ads-sanitize-settings', $options );
			
			// check if editors can edit ads now and set the rights
			// else, remove that right
			$editor_role = get_role( 'editor' );
			if( null == $editor_role ){
			    return $options;
			}
			if( isset($options['editors-manage-ads']) && $options['editors-manage-ads'] ){
				$editor_role->add_cap( 'advanced_ads_see_interface' );
				$editor_role->add_cap( 'advanced_ads_edit_ads' );
				$editor_role->add_cap( 'advanced_ads_manage_placements' );
				$editor_role->add_cap( 'advanced_ads_place_ads' );
			} else {
				$editor_role->remove_cap( 'advanced_ads_see_interface' ); 
				$editor_role->remove_cap( 'advanced_ads_edit_ads' ); 
				$editor_role->remove_cap( 'advanced_ads_manage_placements' ); 
				$editor_role->remove_cap( 'advanced_ads_place_ads' ); 
			}

			// we need 3 states: ! isset, 1, 0
			$options['disabled-ads']['feed'] = isset( $options['disabled-ads']['feed'] ) ? 1 : 0;

			return $options;
		}

		/**
		 * sanitize add-on license keys array
		 *  most important is to not remove a key even if the add-on is temporarily disabled
		 *  if the key was newly added, check if it was already activated online
		 *
		 * @since 1.6.1
		 * @param array $options all the options (license keys)
		 */
		public function sanitize_license_keys( $options ){

			// get existing license keys
			$licenses = get_option( ADVADS_SLUG . '-licenses', array() );
			
			// trim all keys
			$options = array_map('trim', $options);

			// merge existing with new license key to prevent accidental removal
			if( is_array( $options ) && is_array( $licenses ) ){
			    $all_licenses = array_merge( $licenses, $options );
			} elseif( is_array( $options ) ){
			    $all_licenses = $options;
			} else {
			    $all_licenses = $licenses;
			}
			
			// get infos from all add-ons
			$add_ons = apply_filters( 'advanced-ads-add-ons', array() );
			
			// check new licenses if they were already activated before
			$new_licenses = array_diff_assoc( $all_licenses, $licenses );
			foreach( $new_licenses as $_slug => $_key ){
			    if( isset( $add_ons[ $_slug ] ) ){
				    $this->check_license( $_key, $add_ons[ $_slug ][ 'name' ], $add_ons[ $_slug ][ 'options_slug' ] );
			    }
			}
			
			return $all_licenses;
		}

	/**
	 * add heading for extra column of ads list
	 * remove the date column
	 *
	 * @since 1.3.3
	 * @param arr $columns
	 */
	public function ad_list_columns_head( $columns ){

		$new_columns = array();
		foreach( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( $key == 'title' ){
				$new_columns[ 'ad_details' ] = __( 'Ad Details', 'advanced-ads' );
				$new_columns[ 'ad_timing' ] = __( 'Ad Planning', 'advanced-ads' );
			}
		}
		
		// white-listed columns
		$whitelist = apply_filters( 'advanced-ads-ad-list-allowed-columns', array(
		    'cb', // checkbox
		    'title',
		    'ad_details',
		    'ad_timing',
		    'taxonomy-advanced_ads_groups',
		) );
		
		// remove non-white-listed columns
		foreach( $new_columns as $_key => $_value ){
			if( ! in_array( $_key, $whitelist ) ){
				unset( $new_columns[ $_key ] );
			}
		}

		return $new_columns;
	}

		/**
	 * order ads by title on ads list
	 *
	 * @since 1.3.18
	 * @param arr $vars array with request vars
	 */
	public function ad_list_request($vars){

		// order ads by title on ads list
		if ( is_admin() && empty( $vars['orderby'] ) && $this->post_type == $vars['post_type'] ) {
			$vars = array_merge( $vars, array(
				'orderby' => 'title',
				'order' => 'ASC'
			) );
		}

		return $vars;
	}

	/**
	 * display ad details in ads list
	 *
	 * @since 1.3.3
	 * @param string $column_name name of the column
	 * @param int $ad_id id of the ad
	 */
	public function  ad_list_columns_content($column_name, $ad_id) {
		if ( $column_name == 'ad_details' ) {
			$ad = new Advanced_Ads_Ad( $ad_id );

			// load ad type title
			$types = Advanced_Ads::get_instance()->ad_types;
			$type = ( ! empty($types[$ad->type]->title)) ? $types[$ad->type]->title : 0;

			// load ad size
			$size = 0;
			if ( ! empty($ad->width) || ! empty($ad->height) ) {
				$size = sprintf( '%d x %d', $ad->width, $ad->height );
			}

			$size = apply_filters( 'advanced-ads-list-ad-size', $size, $ad );

			include ADVADS_BASE_PATH . 'admin/views/ad-list-details-column.php';
		}
	}
	
	/**
	 * display ad details in ads list
	 *
	 * @since 1.6.11
	 * @param string $column_name name of the column
	 * @param int $ad_id id of the ad
	 */
	public function  ad_list_columns_timing($column_name, $ad_id) {
	    
		if ( $column_name == 'ad_timing' ) {
			$ad = new Advanced_Ads_Ad( $ad_id );
			
			$expiry = false;
			$post_future = false;
			$post_start = get_the_date('U', $ad->id );
			$html_classes = 'advads-filter-timing';
			$expiry_date_format = get_option( 'date_format' ). ', ' . get_option( 'time_format' );

			if( isset( $ad->expiry_date ) && $ad->expiry_date ){
				$html_classes .= ' advads-filter-any-exp-date';

				$expiry = $ad->expiry_date;
				if( $ad->expiry_date < time() ){
					$html_classes .= ' advads-filter-expired';
				}
			}
			if( $post_start > time() ){
				$post_future = $post_start;
				$html_classes .= ' advads-filter-future';
			}

			include ADVADS_BASE_PATH . 'admin/views/ad-list-timing-column.php';
		}
	}

	/**
	 * adds filter dropdowns before the 'Filter' button on the ad list table
	 */
	function ad_list_add_filters() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) || $screen->id !== 'edit-advanced_ads' ) {
			return;
		}

		include ADVADS_BASE_PATH . 'admin/views/ad-list-filters.php';
	}

	/**
	 * add a meta box to post type edit screens with ad settings
	 *
	 * @since 1.3.10
	 * @param string $post_type current post type
	 */
		public function add_post_meta_box($post_type = ''){
			// don’t display for non admins
			if( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads') ) ) {
				return;
			}
			
			// get public post types
			$public_post_types = get_post_types( array('public' => true, 'publicly_queryable' => true), 'names', 'or' );

			//limit meta box to public post types
			if ( in_array( $post_type, $public_post_types ) ) {
				add_meta_box(
					'advads-ad-settings',
					__( 'Ad Settings', 'advanced-ads' ),
					array( $this, 'render_post_meta_box' ),
					$post_type,
					'advanced',
					'low'
				);
			}
		}

		/**
	 * render meta box for ad settings on a per post basis
	 *
	 * @since 1.3.10
	 * @param WP_Post $post The post object.
	*/
		public function render_post_meta_box( $post ) {

			// nonce field to check when we save the values
			wp_nonce_field( 'advads_post_meta_box', 'advads_post_meta_box_nonce' );

			// retrieve an existing value from the database.
			$values = get_post_meta( $post->ID, '_advads_ad_settings', true );

			// load the view
			include ADVADS_BASE_PATH . 'admin/views/post-ad-settings-metabox.php';
		}

		/**
	 * save the ad meta when the post is saved.
	 *
	 * @since 1.3.10
	 * @param int $post_id The ID of the post being saved.
	*/
		public function save_post_meta_box( $post_id ) {

			if( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads') ) ) {
			    return;
			}

			// check nonce
			if ( ! isset( $_POST['advads_post_meta_box_nonce'] ) ) {
				return $post_id; }

			$nonce = $_POST['advads_post_meta_box_nonce'];

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, 'advads_post_meta_box' ) ) {
				return $post_id; }

			// don’t save on autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id; }

			// check the user's permissions.
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return $post_id; }
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return $post_id; }
			}

			// Sanitize the user input.
			$_data['disable_ads'] = isset($_POST['advanced_ads']['disable_ads']) ? absint( $_POST['advanced_ads']['disable_ads'] ) : 0;

			// Update the meta field.
			update_post_meta( $post_id, '_advads_ad_settings', $_data );
		}

		/**
	 * add dashboard widget with ad stats and additional information
	 *
	 * @since 1.3.12
	 */
		public function add_dashboard_widget(){
			// display dashboard widget only to authors and higher roles
			if( ! current_user_can('publish_posts') ) {
			        return;
			}
			add_meta_box( 'advads_dashboard_widget', __( 'Ads Dashboard', 'advanced-ads' ), array($this, 'dashboard_widget_function'), 'dashboard', 'side', 'high' );
		}

		/**
	 * display widget functions
	 */
		public static function dashboard_widget_function($post, $callback_args){
			// load ad optimization feed
			$feeds = array(
			array(
				'link'         => 'http://webgilde.com/en/ad-optimization/',
				'url'          => 'http://webgilde.com/en/ad-optimization/feed/',
				'title'        => __( 'From the ad optimization universe', 'advanced-ads' ),
				'items'        => 2,
				'show_summary' => 0,
				'show_author'  => 0,
				'show_date'    => 0,
			),
			array(
				'link'         => ADVADS_URL,
				'url'          => ADVADS_URL . 'feed/',
				'title'        => __( 'Advanced Ads Tutorials', 'advanced-ads' ),
				'items'        => 2,
				'show_summary' => 0,
				'show_author'  => 0,
				'show_date'    => 0,
			),
			);

			// get number of ads
			$recent_ads = Advanced_Ads::get_instance()->get_model()->get_ads();
			echo '<p>';
			printf(__( '%d ads – <a href="%s">manage</a> - <a href="%s">new</a>', 'advanced-ads' ),
				count( $recent_ads ),
				'edit.php?post_type='. Advanced_Ads::POST_TYPE_SLUG,
			'post-new.php?post_type='. Advanced_Ads::POST_TYPE_SLUG);
			echo '</p>';

			// get and display plugin version
			$advads_plugin_data = get_plugin_data( ADVADS_BASE_PATH . 'advanced-ads.php' );
			if ( isset($advads_plugin_data['Version']) ){
				$version = $advads_plugin_data['Version'];
				echo '<p><a href="'.ADVADS_URL.'#utm_source=advanced-ads&utm_medium=link&utm_campaign=dashboard" target="_blank" title="'.
					__( 'plugin manual and homepage', 'advanced-ads' ).'">Advanced Ads</a> '. $version .'</p>';
			}

			$notice_options = Advanced_Ads_Admin_Notices::get_instance()->options();
			$_notice = 'nl_first_steps';
			if ( ! isset($notice_options['closed'][ $_notice ] ) ) {
				?><div class="advads-admin-notice">
				    <p><button type="button" class="button-primary advads-notices-button-subscribe" data-notice="<?php echo $_notice ?>"><?php _e('Get the tutorial via email', 'advanced-ads'); ?></button></p>
				</div><?php
			}

			$_notice = 'nl_adsense';
			if ( ! isset($notice_options['closed'][ $_notice ] ) ) {
				?><div class="advads-admin-notice">
				    <p><button type="button" class="button-primary advads-notices-button-subscribe" data-notice="<?php echo $_notice ?>"><?php _e('Get AdSense tips via email', 'advanced-ads'); ?></button></p>
				</div><?php
			}

			// rss feed
			// $this->dashboard_widget_function_output('advads_dashboard_widget', $feed);
			self::dashboard_cached_rss_widget( 'advads_dashboard_widget', array('Advanced_Ads_Admin', 'dashboard_widget_function_output'), array('advads' => $feeds) );
		}

		/**
	 * checks to see if there are feed urls in transient cache; if not, load them
	 * built using a lot of https://developer.wordpress.org/reference/functions/wp_dashboard_cached_rss_widget/
	 *
	 * @since 1.3.12
	 * @param string $widget_id
	 * @param callback $callback
	 * @param array $check_urls RSS feeds
	 * @return bool False on failure. True on success.
	 */
		static function dashboard_cached_rss_widget( $widget_id, $callback, $feeds = array() ) {
			if ( empty($feeds) ) {
				return;
			}

			$cache_key = 'dash_' . md5( $widget_id );
			/*if ( false !== ( $output = get_transient( $cache_key ) ) ) {
            echo $output;
            return true;
			}*/

			if ( $callback && is_callable( $callback ) ) {
				ob_start();
				call_user_func_array( $callback, $feeds );
				set_transient( $cache_key, ob_get_flush(), 12 * HOUR_IN_SECONDS ); // Default lifetime in cache of 12 hours (same as the feeds)
			}

			return true;
		}

		/**
	 * create the rss output of the widget
	 *
	 * @param string $widget_id Widget ID.
	 * @param array  $feeds     Array of RSS feeds.
	 */
		static function dashboard_widget_function_output( $feeds ) {
			foreach ( $feeds as $_feed ){
				echo '<div class="rss-widget">';
				echo '<h4>'.$_feed['title'].'</h4>';
				wp_widget_rss_output( $_feed['url'], $_feed );
				echo '</div>';
			}
		}
        
    /**
     *  get DateTimeZone object for the WP installation
     */
    public static function get_wp_timezone() {
        $_time_zone = get_option( 'timezone_string' );
        $time_zone = new DateTimeZone( 'UTC' );
        if ( $_time_zone ) {
            $time_zone = new DateTimeZone( $_time_zone );
        } else {
            $gmt_offset = floatval( get_option( 'gmt_offset' ) );
            $sign = ( 0 > $gmt_offset )? '-' : '+';
            $int = floor( abs( $gmt_offset ) );
            $frac = abs( $gmt_offset ) - $int;
            
            $gmt = '';
            if ( $gmt_offset ) {
                $gmt .= $sign . zeroise( $int, 2 ) . ':' . zeroise( 60 * $frac, 2 );
                $time_zone = date_create( '2017-10-01T12:00:00' . $gmt )->getTimezone();
            }
            
        }
        return $time_zone;
    }
    
    /**
     *  get literal expression of timezone
     */
    public static function timezone_get_name( $DTZ ) {
        if ( $DTZ instanceof DateTimeZone ) {
            $TZ = timezone_name_get( $DTZ );
            if ( 'UTC' == $TZ ) {
                return 'UTC+0';
            }
            if ( false === strpos( $TZ, '/' ) ) {
                $TZ = 'UTC' . $TZ;
            } else {
                $TZ = sprintf( __( 'time of %s', 'advanced-ads' ), $TZ );
            }
            return $TZ;
        }
        return 'UTC+0';
    }

	/**
	 * initiate the admin notices class
	 *
	 * @since 1.5.3
	 */
	public function admin_notices(){
		// display ad block warning to everyone who can edit ads
		if( current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads') ) ) {
			if ( $this->screen_belongs_to_advanced_ads() ){
				include ADVADS_BASE_PATH . 'admin/views/notices/adblock.php';
				include ADVADS_BASE_PATH . 'admin/views/notices/jqueryui_error.php';
			}
		}
		
		if( current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads') ) ) {
			$this->notices = Advanced_Ads_Admin_Notices::get_instance();
		}
	}

	/**
	 * save license key
	 *
	 * @since 1.2.0
	 * @param string $addon string with addon identifier
	 */
	public function activate_license( $addon = '', $plugin_name = '', $options_slug = '' ) {

		if ( '' === $addon || '' === $plugin_name || '' === $options_slug ) {
			return __( 'Error while trying to register the license. Please contact support.', 'advanced-ads' );
		}

		$licenses = $this->get_licenses();
		$license_key = isset($licenses[$addon]) ? $licenses[$addon] : '';
		if ( '' == $license_key ) {
			return __( 'Please enter and save a valid license key first.', 'advanced-ads' );
		}
		
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license_key,
			'item_name' => urlencode( $plugin_name ),
			'url'       => home_url()
		);
		// Call the custom API.
		$response = wp_remote_post( ADVADS_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );
		
		if ( is_wp_error( $response ) ) {
			return wp_remote_retrieve_body( $response );
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		// save license status
		update_option($options_slug . '-license-status', $license_data->license, false);

		// display activation problem
		if( !empty( $license_data->error )) {
		    // user friendly texts for errors
		    $errors = array(
			'license_not_activable' => __( 'This is the bundle license key.', 'advanced-ads' ),
			'item_name_mismatch' => __( 'This is not the correct key for this add-on.', 'advanced-ads' )
		    );
		    $error = isset( $errors[ $license_data->error ] ) ? $errors[ $license_data->error ] : $license_data->error;
		    return sprintf( __('License is invalid. Reason: %s'), $error);
		} else {
		    // save license value time
		    update_option($options_slug . '-license-expires', $license_data->expires, false);
		}

		return 1;
	}
	
	/**
	 * check if a specific license key was already activated for the current page
	 * 
	 * @since 1.6.17
	 * @return bool true if already activated
	 */
	public function check_license( $license_key = '', $plugin_name = '', $options_slug = '' ){
	    
		$api_params = array(
			'edd_action' => 'check_license',
			'license' => $license_key,
			'item_name' => urlencode( $plugin_name )
		);
		$response = wp_remote_get( add_query_arg( $api_params, ADVADS_URL ), array( 'timeout' => 15, 'sslverify' => false ) );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		// if this license is still valid
		if( $license_data->license == 'valid' ) {
			update_option($options_slug . '-license-expires', $license_data->expires, false);
			update_option($options_slug . '-license-status', $license_data->license, false);
		}
	}	
	
	/**
	 * deactivate license key
	 *
	 * @since 1.6.11
	 * @param string $addon string with addon identifier
	 */
	public function deactivate_license( $addon = '', $plugin_name = '', $options_slug = '' ) {

		if ( '' === $addon || '' === $plugin_name || '' === $options_slug ) {
			return __( 'Error while trying to disable the license. Please contact support.', 'advanced-ads' );
		}

		$licenses = $this->get_licenses();
		$license_key = isset($licenses[$addon]) ? $licenses[$addon] : '';

		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license_key,
			'item_name'  => urlencode( $plugin_name )
		);
		// Send the remote request
		$response = wp_remote_post( ADVADS_URL, array( 
		    'body' => $api_params, 
		    'timeout' => 15,
		    'sslverify' => false,
		) );
		
		if ( is_wp_error( $response ) ) {
			return wp_remote_retrieve_body( $response );
		}

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		// save license status

		// remove data
		if( 'deactivated' === $license_data->license ) {
		    delete_option( $options_slug . '-license-status' );
		    delete_option( $options_slug . '-license-expires' );
		} else {
		    return __( 'License couldn’t be deactivated. Please try again later or contact support.', 'advanced-ads' );
		}

		return 1;
	}
	
	/**
	 * get license keys for all add-ons
	 * 
	 * @since 1.6.15
	 * @return arr $licenses licenses
	 */
	public function get_licenses(){
	    
	    $licenses = array();
	    
	    if( is_multisite() ){
		    // if multisite, get option from main blog
		    global $current_site;
		    $licenses = get_blog_option( $current_site->blog_id, ADVADS_SLUG . '-licenses', array() );
		    
	    } else {
		    $licenses = get_option( ADVADS_SLUG . '-licenses', array() );
	    }
	    
	    return $licenses;
	}
	
	/**
	 * get license status of an add-on
	 * 
	 * @since 1.6.15
	 * @param  str $slug slug of the add-on
	 * @return str $status license status, e.g. "valid" or "invalid"
	 */
	public function get_license_status( $slug = '' ){
	    
	    $status = false;
	    
	    if( is_multisite() ){
		    // if multisite, get option from main blog
		    global $current_site;
		    $status = get_blog_option( $current_site->blog_id, $slug . '-license-status', false);
	    } else {
		    $status = get_option( $slug . '-license-status', false);
	    }
	    
	    return $status;
	}
	
	/**
	 * get license expired value of an add-on
	 * 
	 * @since 1.6.15
	 * @param  str $slug slug of the add-on
	 * @return str $date expiry date of an add-on
	 */
	public function get_license_expires( $slug = '' ){
	    
	    $date = false;
	    
	    if( is_multisite() ){
		    // if multisite, get option from main blog
		    global $current_site;
		    $date = get_blog_option( $current_site->blog_id, $slug . '-license-expires', false);
	    } else {
		    $date = get_option( $slug . '-license-expires', false);
	    }
	    
	    return $date;
	}
	
	
	/*
         * add-on updater
	 *
	 * @since 1.5.7
         */
        public function add_on_updater(){
	    
		// ignore, if not main blog
		if( is_multisite() && ! is_main_site() ){
		    return;
		}

		/**
		 * list of registered add ons
		 * contains:
		 *	    name
		 *	    version
		 *	    path
		 *	    options_slug
		 *	    short option slug (=key)
		 */
		$add_ons = apply_filters( 'advanced-ads-add-ons', array() );

		if( $add_ons === array() ) {
		    return;
		}

		foreach( $add_ons as $_add_on_key => $_add_on ){

			// check if a license expired over time
			$expiry_date = $this->get_license_expires( $_add_on['options_slug'] );
			$now = time();
			if( $expiry_date && strtotime( $expiry_date ) < $now ){
				// remove license status
				delete_option( $_add_on['options_slug'] . '-license-status' );
				continue;
			}

			// check status
			if( $this->get_license_status( $_add_on['options_slug'] ) !== 'valid' ) {
				continue;
			}

			// retrieve our license key from the DB
			$licenses = get_option(ADVADS_SLUG . '-licenses', array());
			$license_key = isset($licenses[$_add_on_key]) ? $licenses[$_add_on_key] : '';

			// setup the updater
			if( $license_key ){
				new EDD_SL_Plugin_Updater( ADVADS_URL, $_add_on['path'], array(
					'version' 	=> $_add_on['version'],
					'license' 	=> $license_key,
					'item_name' => $_add_on['name'],
					'author' 	=> 'Thomas Maier'
				    )
				);
			}
		}
        }
	
	/**
	 * add links to the plugins list
	 *
	 * @since 1.6.14
	 * @param arr $links array of links for the plugins, adapted when the current plugin is found.
	 * @param str $file  the filename for the current plugin, which the filter loops through.
	 * @return array $links
	 */
	function add_plugin_links( $links ) {
		// add link to support page
		$support_link = '<a href="' . esc_url( admin_url( 'admin.php?page=advanced-ads-support' ) ) . '">' . __( 'Support', 'advanced-ads' ) . '</a>';
		array_unshift( $links, $support_link );

		// add link to add-ons
		$extend_link = '<a href="' . ADVADS_URL . 'add-ons/#utm_source=advanced-ads&utm_medium=link&utm_campaign=plugin-page" target="_blank">' . __( 'Add-Ons', 'advanced-ads' ) . '</a>';
		array_unshift( $links, $extend_link );
		
		return $links;
	}
	
	/**
	 * display message when someone is going to disable the plugin
	 * 
	 * @since 1.6.14
	 */
	function display_deactivation_message(){
	    
		// get email address
		$current_user = wp_get_current_user();
		if ( !($current_user instanceof WP_User) ){
		    $email = '';
		} else {
		    $email = trim( $current_user->user_email );
		}
		
		include ADVADS_BASE_PATH . 'admin/views/feedback_disable.php';
	}
}
