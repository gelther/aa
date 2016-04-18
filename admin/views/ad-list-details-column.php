<fieldset class="inline-edit-col-left">
    <div class="inline-edit-col">
	    <?php if ( ! empty( $type ) ) : ?>
			<p><strong class="advads-ad-type"><?php echo $type; ?></strong></p>
	    <?php endif;
		if ( ! empty( $size) ) : ?>
			<p class="advads-ad-size"><?php echo $size; ?></p><?php
		endif;
		do_action( 'advanced-ads-ad-list-details-column-after', $ad ); ?>
	</div>
</fieldset>