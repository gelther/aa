<div id="advanced-ads-ad-parameters-size">
    <p><?php _e( 'size:', 'advanced-ads' ); ?></p>
    <label><?php _e( 'width', 'advanced-ads' ); ?><input type="number" size="4" maxlength="4" value="<?php echo isset($ad->width) ? $ad->width : 0; ?>" name="advanced_ad[width]">px</label>
    <label><?php _e( 'height', 'advanced-ads' ); ?><input type="number" size="4" maxlength="4" value="<?php echo isset($ad->height) ? $ad->height : 0; ?>" name="advanced_ad[height]">px</label>
    <?php $add_wrapper_sizes = ! empty( $ad->output['add_wrapper_sizes'] ); ?>
    <label><input type="checkbox" id="advads-wrapper-add-sizes" name="advanced_ad[output][add_wrapper_sizes]" value="true" <?php checked($add_wrapper_sizes); ?>><?php _e( 'reserve this space', 'advanced-ads' ); ?></label>
</div>
