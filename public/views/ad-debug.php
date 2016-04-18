<div class="<?php echo $prefix; ?>addebug" style="<?php echo $style; ?>">
<strong>ad debug output</strong>
<?php if( $content === array() ) : ?>
<p style="color: green;">no errors found</p>
<?php else : ?>
<p><?php echo implode( '</p><p>', $content ); ?></p>
<p><a style="color: green;" href="<?php echo ADVADS_URL; ?>manual/ad-debug-mode/#utm_source=advanced-ads&utm_medium=link&utm_campaign=ad-debug-mode" target="_blank">Find solutions in the manual</a></p>
<?php endif; ?>
</div>