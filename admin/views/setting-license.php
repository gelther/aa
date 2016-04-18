<?php
$errortext = false;
$expires = Advanced_Ads_Admin::get_instance()->get_license_expires( $options_slug );
$expired = false;
if( $expires ){
    $expires_time = strtotime( $expires );
    $days_left = ( $expires_time - time() ) / DAY_IN_SECONDS;
    if( $days_left <= 0 ){
	$plugin_url = isset( $plugin_url ) ? $plugin_url : ADVADS_URL;
	$errortext = sprintf(__( 'Your license expired. Please visit <a href="%s" target="_blank">the plugin page</a> to renew it.', 'advanced-ads' ), $plugin_url . '#utm_source=advanced-ads&utm_medium=link&utm_campaign=settings-licenses' );
	$expired = true;
    }
};
?><input type="text" class="regular-text" placeholder="<?php _e( 'License key', 'advanced-ads' ); ?>"
       name="<?php echo ADVADS_SLUG . '-licenses'; ?>[<?php echo $index; ?>]"
       value="<?php echo esc_attr_e($license_key); ?>"
       <?php if( $license_status !== false && $license_status == 'valid' && ! $expired ) : ?> disabled="disabled"<?php endif; ?>/><?php
if( $license_status !== false && $license_status == 'valid' && ! $expired ) :
    $show_active = true;
    ?><button type="button" class="button-secondary advads-license-deactivate"
		data-addon="<?php echo $index; ?>"
		data-pluginname="<?php echo $plugin_name; ?>"
		data-optionslug="<?php echo $options_slug; ?>"
		name="advads_license_activate"><?php _e('Deactivate License'); ?></button><?php
else :
    $show_active = false;
    if($license_key !== '') :
        ?><button type="button" class="button-primary advads-license-activate"
		data-addon="<?php echo $index; ?>"
		data-pluginname="<?php echo $plugin_name; ?>"
		data-optionslug="<?php echo $options_slug; ?>"
		name="advads_license_activate"><?php _e('Activate License'); ?></button><?php
    endif;
    if( '' === trim( $license_key ) ){
	$errortext = __('Please enter a valid license key', 'advanced-ads');
    } elseif( ! $expired ){
	$errortext = ( ! $license_status || $license_status == 'invalid') ? __('License key invalid', 'advanced-ads') : '';
    }
endif;
?><span class="advads-license-activate-error" <?php if( ! $errortext ) echo 'style="display: none;"'; ?>><?php echo $errortext; ?></span>
<span class="advads-license-activate-active" <?php if( ! $show_active ) echo 'style="display: none;"'; ?>><?php _e( 'active', 'advanced-ads' );
 if( isset( $days_left ) && 0 < $days_left && 91 > $days_left ) { echo '&nbsp;' . sprintf( __('(%d days left)', 'advanced-ads' ), $days_left ); }
?></span><?php
if($license_key === '') :
    ?><p class="description"><?php _e( '1. enter the key and save options; 2. click the activate button behind the field', 'advanced-ads' ); ?></p><?php
endif;