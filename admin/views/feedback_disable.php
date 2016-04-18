<tr id="advanced-ads-feedback" class="active"><td colspan="3">
<form action="mailto:support@wpadvancedads.com">
<p><?php _e( 'Thank you for helping to improve Advanced Ads.', 'advanced-ads' ); ?></p>
<p><?php _e( 'Your feedback will motivates me to work harder towards a professional ad management solution.', 'advanced-ads' ); ?></p>
<p><?php _e( 'Why did you decide to disable Advanced Ads?', 'advanced-ads' ); ?></p>
<ul>
<li><input type="checkbox" name="advanced_ads_disable_reason[]"/><?php _e( 'I stopped showing ads on my site', 'advanced-ads' ); ?></li>
<li><input type="checkbox" name="advanced_ads_disable_reason[]"/><?php printf(__( 'I miss a feature or <a href="%s">add-on</a>', 'advanced-ads' ), ADVADS_URL . '/add-ons/#utm_source=advanced-ads&utm_medium=link&utm_campaign=disabled' ); ?></li>
<li><input type="checkbox" name="advanced_ads_disable_reason[]"/><?php _e( 'I have a technical problem', 'advanced-ads' ); ?></li>
<li><input type="checkbox" name="advanced_ads_disable_reason[]"/><?php _e( 'other reason', 'advanced-ads' ); ?></li>
</ul>
<textarea name="advanced_ads_disable_reason_text" placeholder="<?php _e( 'Please specify, if possible', 'advanced-ads' ); ?>"></textarea>
<p><?php _e( 'What would be a reason to return to Advanced Ads?', 'advanced-ads' ); ?></p>
<textarea name="advanced_ads_return[]"></textarea>
<?php if( $email ) : ?>
<input type="submit" name="advanced_ads_disable_submit" value="submit as <?php echo $email; ?>"/>
<?php endif; ?>
<input type="submit" name="advanced_ads_disable_submit" value="submit anonymously"/>
</form>
</td></tr>