/*
 * global js functions for Advanced Ads
 */
jQuery( document ).ready(function () {

	/**
	 * ADMIN NOTICES
	 */
	// close button
	jQuery(document).on('click', '.advads-admin-notice button.notice-dismiss', function(){
	    var messagebox = jQuery(this).parents('.advads-admin-notice');
	    if( messagebox.attr('data-notice') === undefined) return;

	    var query = {
		action: 'advads-close-notice',
		notice: messagebox.attr('data-notice')
	    };
	    // send query
	    jQuery.post(ajaxurl, query, function (r) {
		// messagebox.fadeOut();
	    });

	});
	// autoresponder button
	jQuery('.advads-notices-button-subscribe').click(function(){
	    if(this.dataset.notice === undefined) return;
	    var messagebox = jQuery(this).parents('.advads-admin-notice');
	    messagebox.find('p').append( '<span class="spinner advads-spinner"></span>' );

	    var query = {
		action: 'advads-subscribe-notice',
		notice: this.dataset.notice
	    };
	    // send and close message
	    jQuery.post(ajaxurl, query, function (r) {
		if(r === '1'){
		    messagebox.fadeOut();
		} else {
		    messagebox.find('p').html(r);
		    // don’t change class on intro page
		    if( ! jQuery('.admin_page_advanced-ads-intro').length ){
			    messagebox.removeClass('updated').addClass('error');
		    }
		}
	    });

	});

});