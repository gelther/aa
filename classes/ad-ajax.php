<?php

/**
 * Provide public ajax interface.
 *
 * @since 1.5.0
 */
class Advanced_Ads_Ajax {

	private function __construct()
	{
		add_action( 'wp_ajax_advads_ad_select', array( $this, 'advads_ajax_ad_select' ) );
		add_action( 'wp_ajax_nopriv_advads_ad_select', array( $this, 'advads_ajax_ad_select' ) );
	}

	private static $instance;

	public static function get_instance()
	{
		if ( ! isset(self::$instance) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Simple wp ajax interface for ad selection.
	 *
	 * Provides a single ad given ID and selection method.
	 */
	public function advads_ajax_ad_select() {
		// set proper header
		header( 'Content-Type: application/json; charset: utf-8' );

		// allow modules / add ons to test (this is rather late but should happen before anything important is called)
		do_action( 'advanced-ads-ajax-ad-select-init' );

		// init handlers
		$selector = Advanced_Ads_Select::get_instance();
		$methods = $selector->get_methods();
		$method = isset( $_REQUEST['ad_method'] ) ? (string) $_REQUEST['ad_method'] : null;
		$id = isset( $_REQUEST['ad_id'] ) ? (string) $_REQUEST['ad_id'] : null;
		$arguments = isset( $_REQUEST['ad_args'] ) ? $_REQUEST['ad_args'] : array();
		if (is_string($arguments)) {
			$arguments = stripslashes($arguments);
			$arguments = json_decode($arguments, true);
		}
		$adIds = isset( $_REQUEST['ad_ids'] ) ? $_REQUEST['ad_ids'] : null;
		if ( is_string( $adIds ) ) {
			$adIds = json_decode( $adIds, true );
		}

		$response = array();
		if ( isset( $methods[ $method ] ) && isset( $id ) ) {
			$advads = Advanced_Ads::get_instance();
			if (is_array($adIds)) { // ads loaded previously and passed by query
				$advads->current_ads += $adIds;
			}
			$l = count( $advads->current_ads );

			// build content
			$content = $selector->get_ad_by_method( $id, $method, $arguments );
			$adIds = array_slice( $advads->current_ads, $l ); // ads loaded by this request

			$response = array( 'status' => 'success', 'item' => $content, 'id' => $id, 'method' => $method, 'ads' => $adIds );
		} else {
			// report error
			$response = array( 'status' => 'error', 'message' => 'No valid ID or METHOD found.' );
		}

		echo json_encode( $response );
		die();
	}
}
