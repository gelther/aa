<?php

class Advanced_Ads_AdSense_Data {

	private static $instance;

	private $options;

	private $resizing;

	private function __construct() {

        $options = get_option(GADSENSE_OPT_NAME, array());

		// AdSense publisher id
		if ( ! isset($options['adsense-id']) ) {
            // check if there is still an old setting
            // 'gadsense_options' was renamed
            $old_options = get_option( 'gadsense_options', array() );
            if ( isset($old_options['adsense_id']) ) {
                $options['adsense-id'] = $old_options['adsense_id'];
                $options['limit-per-page'] = $old_options['limit_ads_per_page'];

                // remove old options
                delete_option('gadsense_options');
            } else {
                $options['adsense-id'] = '';
                $options['limit-per-page'] = true;
            }

            update_option(GADSENSE_OPT_NAME, $options);
		}

            if ( !isset($options['limit-per-page']) ) {
                $options['limit-per-page'] = '';
            }

            if ( !isset($options['page-level-enabled']) ) {
                $options['page-level-enabled'] = false;
            }

		$this->options = $options;

		// Resizing method for responsive ads
		$this->resizing = array(
			'auto' => __( 'Auto', 'advanced-ads' ),
		);
	}

        /**
	 * GETTERS
	 */
	public function get_options() {
		return $this->options;
	}
	public function get_adsense_id() {
		return trim($this->options['adsense-id']);
	}

	public function get_limit_per_page() {
		return $this->options['limit-per-page'];
	}

	public function get_responsive_sizing() {
		$resizing = $this->resizing;
		$this->resizing = apply_filters( 'advanced-ads-gadsense-responsive-sizing', $resizing );
		return $this->resizing;
	}

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

}
