<?php

/**
 * Advanced Ads.
 *
 * @package   Advanced_Ads
 * @author    Thomas Maier <thomas.maier@webgilde.com>
 * @license   GPL-2.0+
 * @link      http://webgilde.com
 * @copyright 2013-2015 Thomas Maier, webgilde GmbH
 */

/**
 * This class is used to bundle all ajax callbacks
 *
 * @package Advanced_Ads_Ajax_Callbacks
 * @author  Thomas Maier <thomas.maier@webgilde.com>
 */
class Advanced_Ads_Ad_Ajax_Callbacks {

	public function __construct() {
		// NOTE: admin only!
		//add_action( 'wp_ajax_load_content_editor', array( $this, 'load_content_editor' ) );
		add_action( 'wp_ajax_load_ad_parameters_metabox', array( $this, 'load_ad_parameters_metabox' ) );
		add_action( 'wp_ajax_load_visitor_conditions_metabox', array( $this, 'load_visitor_condition' ) );
		add_action( 'wp_ajax_load_display_conditions_metabox', array( $this, 'load_display_condition' ) );
		add_action( 'wp_ajax_advads-terms-search', array( $this, 'search_terms' ) );
		add_action( 'wp_ajax_advads-close-notice', array( $this, 'close_notice' ) );
		add_action( 'wp_ajax_advads-subscribe-notice', array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_advads-activate-license', array( $this, 'activate_license' ) );
		add_action( 'wp_ajax_advads-deactivate-license', array( $this, 'deactivate_license' ) );
		add_action( 'wp_ajax_advads-adblock-rebuild-assets', array( $this, 'adblock_rebuild_assets' ) );
	}

	/**
	 * load content of the ad parameter metabox
	 *
	 * @since 1.0.0
	 */
	public function load_ad_parameters_metabox() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		$types = Advanced_Ads::get_instance()->ad_types;
		$type  = $_REQUEST['ad_type'];
		$ad_id = absint( $_REQUEST['ad_id'] );
		if ( empty( $ad_id ) ) { wp_die(); }

		$ad = new Advanced_Ads_Ad( $ad_id );

		if ( ! empty( $types[ $type ] ) && method_exists( $types[ $type ], 'render_parameters' ) ) {
			$types[ $type ]->render_parameters( $ad );

			include ADVADS_BASE_PATH . 'admin/views/ad-parameters-size.php';
		}

		wp_die();
	}

	/**
	 * load interface for single visitor condition
	 *
	 * @since 1.5.4
	 */
	public function load_visitor_condition() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		// get visitor condition types
		$visitor_conditions = Advanced_Ads_Visitor_Conditions::get_instance()->conditions;
		$condition          = array();
		$condition['type']  = isset( $_POST['type'] ) ? $_POST['type'] : '';
		$index              = isset( $_POST['index'] ) ? $_POST['index'] : 0;

		if ( isset( $visitor_conditions[ $condition['type'] ] ) ) {
			$metabox = $visitor_conditions[ $condition['type'] ]['metabox'];
		} else {
			die();
		}

		if ( method_exists( $metabox[0], $metabox[1] ) ) {
			call_user_func( array( $metabox[0], $metabox[1] ), $condition, $index );
		}

		die();
	}

	/**
	 * load interface for single display condition
	 *
	 * @since 1.7
	 */
	public function load_display_condition() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
			return;
		}

		// get display condition types
		$conditions = Advanced_Ads_Display_Conditions::get_instance()->conditions;
		$condition  = array();

		$condition['type'] = isset( $_POST['type'] ) ? $_POST['type'] : '';

		$index = isset( $_POST['index'] ) ? $_POST['index'] : 0;

		if ( isset( $conditions[ $condition['type'] ] ) ) {
			$metabox = $conditions[ $condition['type'] ]['metabox'];
		} else {
			die();
		}

		if ( method_exists( $metabox[0], $metabox[1] ) ) {
			call_user_func( array( $metabox[0], $metabox[1] ), $condition, $index );
		}

		die();
	}

		/**
		 * search terms belonging to a specific taxonomy
		 *
		 * @sinc 1.4.7
		 */
		public function search_terms() {
			if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_edit_ads' ) ) ) {
				return;
			}

			$args     = array();
			$taxonomy = $_POST['tax'];
			$args     = array( 'hide_empty' => false, 'number' => 20 );

			if ( ! isset( $_POST['search'] ) || $_POST['search'] === '' ) { wp_die(); }

			// if search is an id, search for the term id, else do a full text search
			if ( 0 !== absint( $_POST['search'] ) ) {
				$args['include'] = array( absint( $_POST['search'] ) );
			} else {
				$args['search'] = $_POST['search'];
			}

			$results = get_terms( $taxonomy, $args );
			// $results = _WP_Editors::wp_link_query( $args );
			echo wp_json_encode( $results );
			echo "\n";
			wp_die();
		}

		/**
		 * search terms belonging to a specific taxonomy
		 *
		 * @since 1.5.3
		 */
		public function close_notice() {
			if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
				return;
			}

			if ( ! isset( $_POST['notice'] ) || $_POST['notice'] === '' ) { die(); }

			Advanced_Ads_Admin_Notices::get_instance()->remove_from_queue( $_POST['notice'] );
			die();
		}

		/**
		 * subscribe to newsletter
		 *
		 * @since 1.5.3
		 */
		public function subscribe() {
			if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_see_interface' ) ) ) {
				return;
			}

			if ( ! isset( $_POST['notice'] ) || $_POST['notice'] === '' ) { die(); }

			echo Advanced_Ads_Admin_Notices::get_instance()->subscribe( $_POST['notice'] );
			die();
		}

	/**
	 * activate license of an add-on
	 *
	 * @since 1.5.7
	 */
	public function activate_license() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		// check nonce
		check_ajax_referer( 'advads_ajax_license_nonce', 'security' );

		if ( ! isset( $_POST['addon'] ) || $_POST['addon'] === '' ) { die(); }

		echo Advanced_Ads_Admin::get_instance()->activate_license( $_POST['addon'], $_POST['pluginname'], $_POST['optionslug'] );

		die();
	}

	/**
	 * deactivate license of an add-on
	 *
	 * @since 1.6.11
	 */
	public function deactivate_license() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		// check nonce
		check_ajax_referer( 'advads_ajax_license_nonce', 'security' );

		if ( ! isset( $_POST['addon'] ) || $_POST['addon'] === '' ) { die(); }

		echo Advanced_Ads_Admin::get_instance()->deactivate_license( $_POST['addon'], $_POST['pluginname'], $_POST['optionslug'] );

		die();
	}

	/**
	 * rebuild assets for ad-blocker module
	 */
	public function adblock_rebuild_assets() {
		if ( ! current_user_can( Advanced_Ads_Plugin::user_cap( 'advanced_ads_manage_options' ) ) ) {
			return;
		}

		Advanced_Ads_Ad_Blocker_Admin::get_instance()->add_asset_rebuild_form();
		exit();
	}

}
