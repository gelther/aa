<div class="advads-ad-block-check">
    <img src="<?php echo ADVADS_BASE_URL . 'admin/assets/img/advertisement.png' ?>" width="1" height="1"/>
    <div class="message error" style="display: none;"><p><?php _e( 'Please disable your <strong>AdBlocker</strong> to prevent problems with your ad setup.', 'advanced-ads' ); ?></p></div>
</div>
<script>
jQuery(document).ready(function(){
	if( ! jQuery('.advads-ad-block-check img').is(':visible') ){
		jQuery('.advads-ad-block-check .message').show();
	}
});
</script>