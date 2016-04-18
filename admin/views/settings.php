<?php
/**
 * the view for the settings page
 */

// array with setting tabs for frontend
$setting_tabs = apply_filters('advanced-ads-setting-tabs', array(
	'general' => array(
		'page' => $this->plugin_screen_hook_suffix,
		'group' => ADVADS_SLUG,
		'tabid' => 'general',
		'title' => __( 'General', 'advanced-ads' )
	)
));
?><div class="wrap">
	<?php screen_icon(); ?>
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<?php settings_errors(); ?>
	<h2 class="nav-tab-wrapper" id="advads-tabs">
		<?php foreach ( $setting_tabs as $_setting_tab_id => $_setting_tab ) : ?>
			<a class="nav-tab" id="<?php echo $_setting_tab_id; ?>-tab"
				href="#top#<?php echo $_setting_tab_id; ?>"><?php echo $_setting_tab['title']; ?></a>
		<?php endforeach; ?>
	</h2>
		<?php foreach ( $setting_tabs as $_setting_tab_id => $_setting_tab ) : ?>
			<div id="<?php echo $_setting_tab_id; ?>" class="advads-tab">
				<form class="advads-settings-tab-main-form" method="post" action="options.php">
					<?php
					if ( isset( $_setting_tab['group'] ) ) {
						settings_fields( $_setting_tab['group'] );
					}
					do_settings_sections( $_setting_tab['page'] );

					do_action( 'advanced-ads-settings-form', $_setting_tab_id, $_setting_tab );
					submit_button( __( 'Save settings on this page', 'advanced-ads' ) );
					?>
				</form>
				<?php do_action( 'advanced-ads-settings-tab-after-form', $_setting_tab_id, $_setting_tab ); ?>
			</div>
		<?php endforeach; ?>
		<?php
			do_action( 'advanced-ads-additional-settings-form' );
		?>
	<ul>
		<li><a href="/wp-admin/admin.php?page=advanced-ads-debug"><?php _e( 'Debug Page', 'advanced-ads' ); ?></a></li>
		<li><a href="/wp-admin/admin.php?page=advanced-ads-intro"><?php _e( 'Welcome Page', 'advanced-ads' ); ?></a></li>
		<li><a href="http://wordpress.org/plugins/advanced-ads/" title="<?php _e( 'Advanced Ads on WordPress.org', 'advanced-ads' ); ?>"><?php _e( 'Advanced Ads on wp.org', 'advanced-ads' ); ?></a></li>
		<li><a href="http://webgilde.com" title="<?php _e( 'the company behind Advanced Ads', 'advanced-ads' ); ?>"><?php echo 'webgilde GmbH'; ?></a></li>
	</ul>

</div>
