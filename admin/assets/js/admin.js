jQuery( document ).ready(function ($) {
	if ( ! $.fn.buttonset || ! $.fn.accordion || ! $.fn.tooltip ) {
		$( '.advads-jqueryui-error').show();
	}

	function advads_load_ad_type_parameter_metabox(ad_type) {
		jQuery( '#advanced-ad-type input' ).prop( 'disabled', true );
		$( '#advanced-ads-tinymce-wrapper' ).hide();
		$( '#advanced-ads-ad-parameters' ).html( '<span class="spinner advads-ad-parameters-spinner advads-spinner"></span>' );
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				'action': 'load_ad_parameters_metabox',
				'ad_type': ad_type,
				'ad_id': $( '#post_ID' ).val()
			},
			success: function (data, textStatus, XMLHttpRequest) {
				// toggle main content field
				if (data) {
					$( '#advanced-ads-ad-parameters' ).html( data ).trigger( 'paramloaded' );
					advads_maybe_textarea_to_tinymce( ad_type );
				}
			},
			error: function (MLHttpRequest, textStatus, errorThrown) {
				$( '#advanced-ads-ad-parameters' ).html( errorThrown );
			}
		}).always( function ( MLHttpRequest, textStatus, errorThrown ) {
			jQuery( '#advanced-ad-type input').prop( 'disabled', false );
		});;
	}
	;

	$( document ).on('change', '#advanced-ad-type input', function () {
		var ad_type = $( this ).val()
		advads_load_ad_type_parameter_metabox( ad_type );
	});

	// activate general buttons
	if ( $.fn.buttonset ) {
		$( '.advads-buttonset' ).buttonset();
	}
	// activate accordions
	if ( $.fn.accordion ) {
		$( ".advads-accordion" ).accordion({
		    active: false,
		    collapsible: true,
		});
	}

	$( document ).on('click', '.advads-conditions-terms-buttons .button', function (e) {
		$( this ).remove();
	});
	// display input field to search for terms
	$( document ).on('click', '.advads-conditions-terms-show-search', function (e) {
		e.preventDefault();
		// display input field
		$( this ).siblings( '.advads-conditions-terms-search' ).show().focus();
		// register autocomplete
		advads_register_terms_autocomplete( $( this ).siblings( '.advads-conditions-terms-search' ) );
		$( this ).next( 'br' ).show();
		$( this ).hide();
	});
	// function for autocomplete
	function advads_register_terms_autocomplete( self ){
	    self.autocomplete({
		    source: function(request, callback){
			    // var searchField  = request.term;
			    advads_term_search( self, callback );
		    },
		    minLength: 1,
		    select: function( event, ui ) {
			    // append new line with input fields
			    $( '<label class="button ui-state-active">' + ui.item.label + '<input type="hidden" name="' + self.data('inputName') + '" value="' + ui.item.value + '"></label>' ).appendTo( self.siblings( '.advads-conditions-terms-buttons' ) );

			    // show / hide other elements
			    // $( '#advads-display-conditions-individual-post' ).hide();
			    // $( '.advads-conditions-postids-list .show-search a' ).show();
		    },
		    close: function( event, ui ) {
				    self.val( '' );
		    }
	    });
	}
	// display input field to search for post, page, etc.
	$( document ).on( 'click', '.advads-conditions-postids-show-search', function (e) {
		e.preventDefault();
		// display input field
		$( '#advads-display-conditions-individual-post' ).show();
		$( '.advads-conditions-postids-search-line .description' ).hide();
		$( this ).hide();
	});
	// register autocomplete to display condition individual posts
	$(document).on("focus","#advads-display-conditions-individual-post",function(e) {
		var self = this;
	        if ( !$(this).data("autocomplete") ) { // If the autocomplete wasn't called yet:
		    $( this ).autocomplete({
			    source: function(request, callback){
				    var searchParam  = request.term;
				    advads_post_search( searchParam, callback );
			    },
			    minLength: 2,
			    select: function( event, ui ) {
				    // append new line with input fields
				    var newline = $( '<label class="button ui-state-active">' + ui.item.label + '</label>' );
				    $( '<input type="hidden" name="' + self.dataset.fieldName + '[value][]" value="' + ui.item.value + '"/>' ).appendTo( newline );
				    newline.insertBefore( $( '.advads-conditions-postids-search-line' ) );
			    },
			    close: function( event, ui ) {
				    $( '#advads-display-conditions-individual-post' ).val( '' );
			    },
		    })
		    .autocomplete().data("ui-autocomplete")._renderItem = function( ul, item ) {
			ul.addClass( "advads-conditions-postids-autocomplete-suggestions" );
			return $( "<li></li>" )
			  .append( "<span class='left'>" + item.label + "</span><span class='right'>" + item.info + "</span>" )
			  .appendTo( ul );
		    };
		};
	});
	
	// remove individual posts from the display conditions post list
	$( document ).on('click', '.advads-conditions-postid-buttons .button', function(e){
		$( this ).remove();
	});
	// display/hide error message if no option was selected
	// is also called on every click
	function advads_display_condition_option_not_selected(){
	    $( '.advads-conditions-not-selected' ).each(function(){
		if( $( this ).siblings('label.ui-state-active').length ){
		    $( this ).hide();
		} else {
		    $( this ).show();
		}
	    });
	}
	advads_display_condition_option_not_selected();
	// update error messages when an item is clicked
	$( document ).on( 'click', '.advads-conditions-terms-buttons .ui-button, .advads-conditions-single .ui-button', function(){
	    // needs a slight delay until the buttons are updated
	    window.setTimeout( advads_display_condition_option_not_selected, 200 );
	});
	// activate and toggle conditions connector option
	$('.advads-conditions-connector input').button();
	// dynamically change label
	$(document).on('click', '.advads-conditions-connector input', function(){
	    if( $( this ).is(':checked' ) ){
		$( this ).button( "option", "label", advadstxt.condition_or );
	    } else {
		$( this ).button( "option", "label", advadstxt.condition_and );
	    }
	});
	
		// display ad groups form
		$( '#advads-ad-group-list a.edit, #advads-ad-group-list a.row-title' ).click(function(e){
			e.preventDefault();
			var advadsgroupformrow = $( this ).parents( '.advads-group-row' ).next( '.advads-ad-group-form' );
			if(advadsgroupformrow.is( ':visible' )){
				advadsgroupformrow.hide();
			} else {
				advadsgroupformrow.show();
			}
		});
		// display ad groups usage
		$( '#advads-ad-group-list a.usage' ).click(function(e){
			e.preventDefault();
			var usagediv = $( this ).parents( '.advads-group-row' ).find( '.advads-usage' );
			if(usagediv.is( ':visible' )){
				usagediv.hide();
			} else {
				usagediv.show();
			}
		});
		// display ad groups usage
		$( '.advads-placements-table .usage-link' ).click(function(e){
			e.preventDefault();
			var usagediv = $( this ).next( '.advads-usage' );
			if(usagediv.is( ':visible' )){
				usagediv.hide();
			} else {
				usagediv.show();
			}
		});
		// menu tabs
		$( '#advads-tabs' ).find( 'a' ).click(function () {
			$( '#advads-tabs' ).find( 'a' ).removeClass( 'nav-tab-active' );
			$( '.advads-tab' ).removeClass( 'active' );

			var id = jQuery( this ).attr( 'id' ).replace( '-tab', '' );
			jQuery( '#' + id ).addClass( 'active' );
			jQuery( this ).addClass( 'nav-tab-active' );
		});

		// activate specific or first tab
		var active_tab = window.location.hash.replace( '#top#', '' );
		if (active_tab == '' || active_tab == '#_=_') {
			active_tab = $( '.advads-tab' ).attr( 'id' );
		}
		$( '#' + active_tab ).addClass( 'active' );
		$( '#' + active_tab + '-tab' ).addClass( 'nav-tab-active' );
		$( '.nav-tab-active' ).click();
		// set all tab urls
		advads_set_tab_hashes();

        /**
         * SETTINGS PAGE
         */

	// activate licenses
	$('.advads-license-activate').click(function(){

	    var button = $(this);
	    
	    if( ! this.dataset.addon ) { return }
	    
	    // hide button to prevent issues with activation when people click twice
	    button.hide();

	    var query = {
		action: 'advads-activate-license',
		addon: this.dataset.addon,
		pluginname: this.dataset.pluginname,
		optionslug: this.dataset.optionslug,
		security: $('#advads-licenses-ajax-referrer').val()
	    };

	    // show loader
	    $( '<span class="spinner advads-spinner"></span>' ).insertAfter( button );

	    // send and close message
	    $.post(ajaxurl, query, function (r) {
		// remove spinner
		$('span.spinner').remove();

		if( r === '1' ){
		    button.siblings('.advads-license-activate-error').remove();
		    button.fadeOut();
		    button.siblings('.advads-license-activate-active').fadeIn();
		    button.siblings('input').prop('disabled', true);
		} else {
		    button.next('.advads-license-activate-error').text( r );
		}
	    });
	});
	
	// deactivate licenses
	$('.advads-license-deactivate').click(function(){

	    var button = $(this);
	    
	    if( ! this.dataset.addon ) { return }
	    
	    // hide button to prevent issues with double clicking
	    button.hide();

	    var query = {
		action: 'advads-deactivate-license',
		addon: this.dataset.addon,
		pluginname: this.dataset.pluginname,
		optionslug: this.dataset.optionslug,
		security: $('#advads-licenses-ajax-referrer').val()
	    };

	    // show loader
	    $( '<span class="spinner advads-spinner"></span>' ).insertAfter( button );

	    // send and close message
	    $.post(ajaxurl, query, function (r) {
		// remove spinner
		$('span.spinner').remove();

		if( r === '1' ){
		    button.siblings('.advads-license-activate-error').hide();
		    button.siblings('.advads-license-activate-active').hide();
		    button.siblings('input').prop('disabled', false);
		    button.fadeOut();
		} else {
		    button.next('.advads-license-activate-error').show().text( r );
		    button.siblings('.advads-license-activate-active').hide();
		}
	    });
	});

	/**
         * PLACEMENTS
         */

	 // show image tooltips
	if ( $.fn.tooltip ) {
		$( ".advads-placements-new-form .advads-placement-type" ).tooltip({
			items: "span",
			content: function() {
				return $( this ).parents('.advads-placement-type').find( '.advads-placement-description' ).html();
			}
		});
	}

	/**
         * Image ad uploader
         */

	$('body').on('click', '.advads_image_upload', function(e) {

		e.preventDefault();

		var button = $(this);

		// If the media frame already exists, reopen it.
		if ( file_frame ) {
			// file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
			file_frame.open();
			return;
		}

		// Create the media frame.
		file_frame = wp.media.frames.file_frame = wp.media( {
			id: 'advads_type_image_wp_media',
			title: button.data( 'uploaderTitle' ),
			button: {
				text: button.data( 'uploaderButtonText' )
			},
			library: {
				type: 'image'
			},
			multiple: false // only allow one file to be selected
		} );

		// When an image is selected, run a callback.
		file_frame.on( 'select', function() {

			var selection = file_frame.state().get('selection');
			selection.each( function( attachment, index ) {
				attachment = attachment.toJSON();
				if ( 0 === index ) {
					// place first attachment in field
					$( '#advads-image-id' ).val( attachment.id );
					$( '#advanced-ads-ad-parameters-size input[name="advanced_ad[width]"]' ).val( attachment.width );
					$( '#advanced-ads-ad-parameters-size input[name="advanced_ad[height]"]' ).val( attachment.height );
					// update image preview
					var new_image = '<img width="'+ attachment.width +'" height="'+ attachment.height +
						'" title="'+ attachment.title +'" alt="'+ attachment.alt +'" src="'+ attachment.url +'"/>';
					$('#advads-image-preview').html( new_image );
					$('#advads-image-edit-link').attr( 'href', attachment.editLink );
				}
			});
		});

		// Finally, open the modal
		file_frame.open();
	});

	// WP 3.5+ uploader
	var file_frame;
	window.formfield = '';

	advads_ad_list_build_filters();

	// adblocker related code
	$( '#advanced-ads-use-adblocker' ).change( function() {
		advads_toggle_box( this, '#advads-adblocker-wrapper' );
	});
	// processing of the rebuild asset form and the FTP/SSH credentials form
	var $advads_adblocker_wrapper = $( '#advads-adblocker-wrapper' );
	$advads_adblocker_wrapper.find( 'input[type="submit"]' ).prop( 'disabled', false );
	$advads_adblocker_wrapper.on( 'submit', 'form', function( event ) {
		var $that = $( this ), // form itself
		data = $that.serializeArray();
		data.push( { name: 'action', value: 'advads-adblock-rebuild-assets' } );

		$that.prev( '.error' ).remove();
		$that.find( 'input[type="submit"]' ).prop( 'disabled', true ).after( '<span class="spinner advads-spinner"></span>' );

		$.ajax({
			type: $that.attr( 'method' ),
			url: ajaxurl,
			action: 'advads-adblock-rebuild-assets',
			data: data
		}).done( function( data ) {
			$advads_adblocker_wrapper.html( data );
		}).fail( function( jqXHR, textStatus, errorThrown ) {
			$that.before( '<div class="error"><p>' + textStatus  + ': ' + errorThrown + '</p></div>' );
			$that.find( 'input[type="submit"]' ).prop( 'disabled', false ).next( '.advads-spinner' ).remove();
		});

		event.preventDefault();
	});

	// process "reserve this space" checkbox
	$( '#advanced-ads-ad-parameters' ).on( 'change', '#advanced-ads-ad-parameters-size input[type=number]', function() {
		if ( $( '#advanced-ads-ad-parameters-size input[type=number]' ).filter( function() {
			return parseInt( this.value, 10 ) > 0;
		}).length === 2 ) {
			$( '#advads-wrapper-add-sizes' ).prop( 'disabled', false );
		} else {
			$( '#advads-wrapper-add-sizes' ).prop( 'disabled', true ).prop( 'checked', false );
		}
	});
	// process "reserve this space" checkbox - ad type changed
	$( '#advanced-ads-ad-parameters' ).on( 'paramloaded', function() {
		$( '#advanced-ads-ad-parameters-size input[type=number]:first' ).change();
	})
	// process "reserve this space" checkbox - on load
	$( '#advanced-ads-ad-parameters-size input[type=number]:first' ).change();
});

/**
 * store the action hash in settings form action
 * thanks for Yoast SEO for this idea
 */
function advads_set_tab_hashes() {
	// iterate through forms
	jQuery( '#advads-tabs' ).find( 'a' ).each(function () {
		var id = jQuery( this ).attr( 'id' ).replace( '-tab', '' );
		var optiontab = jQuery( '#' + id );

		var form = optiontab.children( '.advads-settings-tab-main-form' );
		if ( form.length ) {
			var currentUrl = form.attr( 'action' ).split( '#' )[ 0 ];
			form.attr( 'action', currentUrl + jQuery( this ).attr( 'href' ) );
		}
	});
}

/**
 * callback for term search autocomplete
 *
 * @param {type} search term
 * @param {type} callback
 * @returns {obj} json object with labels and values
 */
function advads_term_search(field, callback) {

	// return ['post', 'poster'];
	var query = {
		action: 'advads-terms-search',
	};
	
	query.search = field.val();
	query.tax = field.data('tagName');

	var querying = true;

	var results = {};
	jQuery.post(ajaxurl, query, function (r) {
		querying = false;
		var results = [];
		if(r){
			r.map(function(element, index){
				results[index] = {
					value: element.term_id,
					label: element.name
				};
			});
		}
		callback( results );
	}, 'json');
}

/**
 * callback for post search autocomplete
 *
 * @param {type} query
 * @param {type} callback
 * @returns {obj} json object with labels and values
 */
function advads_post_search(query, callback) {

	// return ['post', 'poster'];
	var query = {
		action: 'wp-link-ajax',
		_ajax_linking_nonce: jQuery( '#_ajax_linking_nonce' ).val()
	};

	query.search = jQuery( '#advads-display-conditions-individual-post' ).val();

	var querying = true;

	var results = {};
	jQuery.post(ajaxurl, query, function (r) {
		querying = false;
		var results = [];
		if(r){
			r.map(function(element, index){
				results[index] = {
					label: element.title,
					value: element.ID,
					info: element.info
				};
			});
		}
		callback( results );
	}, 'json');
}

/**
 * toggle content elements (hide/show)
 *
 * @param selector jquery selector
 */
function advads_toggle(selector) {
	jQuery( selector ).slideToggle();
}

/**
 * toggle content elements with a checkbox (hide/show)
 *
 * @param selector jquery selector
 */
function advads_toggle_box(e, selector) {
	if (jQuery( e ).is( ':checked' )) {
		jQuery( selector ).slideDown();
	} else {
		jQuery( selector ).slideUp();
	}
}

/**
 * disable content of one box when selecting another
 *  only grey/disable it, don’t hide it
 *
 * @param selector jquery selector
 */
function advads_toggle_box_enable(e, selector) {
	if (jQuery( e ).is( ':checked' )) {
		jQuery( selector ).find( 'input' ).removeAttr( 'disabled', '' );
	} else {
		jQuery( selector ).find( 'input' ).attr( 'disabled', 'disabled' );
	}
}

/**
 * validate placement form on submit
 */
function advads_validate_placement_form(){
	// check if placement type was selected
	if( ! jQuery('.advads-placement-type input:checked').length){
		jQuery('.advads-placement-type-error').show();
		return false;
	} else {
		jQuery('.advads-placement-type-error').hide();
	}
	// check if placement name was entered
	if( jQuery('.advads-new-placement-name').val() == '' ){
		jQuery('.advads-placement-name-error').show();
		return false;
	} else {
		jQuery('.advads-placement-name-error').hide();
	}
	return true;
}

/**
 * replace textarea with TinyMCE editor for Rich Content ad type
 */
function advads_maybe_textarea_to_tinymce( ad_type ) {
	var textarea            = jQuery( '#advads-ad-content-plain' ),
		textarea_html       = textarea.val(),
		tinymce_id          = 'advanced-ads-tinymce',
		tinymce_id_ws       = jQuery( '#' + tinymce_id ),
		tinymce_wrapper_div = jQuery ( '#advanced-ads-tinymce-wrapper' );

	if ( ad_type !== 'content' ) {
		tinymce_id_ws.prop('name', tinymce_id );
		tinymce_wrapper_div.hide();
		return false;
	}

	if ( typeof tinyMCE === 'object' && tinyMCE.get( tinymce_id ) !== null ) {
		// visual mode
		if ( textarea_html ) {
			// see BeforeSetContent in the wp-includes\js\tinymce\plugins\wordpress\plugin.js
			var wp = window.wp,
			hasWpautop = ( wp && wp.editor && wp.editor.autop && tinyMCE.get( tinymce_id ).getParam( 'wpautop', true ) );
			if ( hasWpautop ) {
				textarea_html = wp.editor.autop( textarea_html );
			}
			tinyMCE.get( tinymce_id ).setContent( textarea_html );
		}
		textarea.remove();
		tinymce_id_ws.prop('name', textarea.prop( 'name' ) );
		tinymce_wrapper_div.show();
	} else if ( tinymce_id_ws.length ) {
		// text mode
		tinymce_id_ws.val( textarea_html );
		textarea.remove();
		tinymce_id_ws.prop('name', textarea.prop( 'name' ) );
		tinymce_wrapper_div.show();
	}
}

 /**
 * adds <option> tags to <select> dropdowns before the 'Filter' button on the ad list table
 * @param {string} jQuery wrapped set where to find value/text for <option>
 * @param {string} jQuery wrapped set with <select> tag
 */
function advads_ad_list_add_items_to_dropdowns( $input, $select ) {
	var all_unique_rows = [];

	$input.each( function() {
		var one_row = jQuery( this ).text();

		if ( jQuery.inArray( one_row, all_unique_rows ) === -1 ) {
			all_unique_rows.push( one_row );
			$select.append(
				jQuery("<option/>", {
					value: one_row,
					text: one_row
				})
			);
		}
	});
}

 /**
 * adds filter dropdowns before the 'Filter' button on the ad list table
 */
function advads_ad_list_build_filters() {
	var $filter_type     = jQuery( '#advads-filter-type' ),
		$filter_size     = jQuery( '#advads-filter-size' ),
		$filter_group    = jQuery( '#advads-filter-group' ),
		$filter_date     = jQuery( '#advads-filter-date' ),
		$ad_planning_col = jQuery( '.advads-filter-timing' );

	advads_ad_list_add_items_to_dropdowns( jQuery( '.advads-ad-type' ), $filter_type );
	advads_ad_list_add_items_to_dropdowns( jQuery( '.advads-ad-size' ), $filter_size );
	advads_ad_list_add_items_to_dropdowns( jQuery( '.taxonomy-advanced_ads_groups a' ), $filter_group );
	// if such classes exist on the page - show related <option>s
	jQuery.each( ['advads-filter-future', 'advads-filter-any-exp-date', 'advads-filter-expired' ], function( i, v ) {
		if ( $ad_planning_col.hasClass( v ) ) {
			$filter_date.children( 'option[value="' + v + '"] ' ).show();
		}
	});

	jQuery( "#advads-filter-type, #advads-filter-size, #advads-filter-group, #advads-filter-date"  ).change( function() {
		var $the_list_tr = jQuery( '#the-list tr' ).removeClass( 'advads-hidden' );

		if ( $filter_type.val() ) {
			$the_list_tr.filter( function() {
				return jQuery( this ).find( '.advads-ad-type' ).text() !== $filter_type.val();
			}).addClass( 'advads-hidden' );
		}
		if ( $filter_size.val() ) {
			$the_list_tr.not( '.advads-hidden' ).filter ( function() {
				return jQuery( this ).find( '.advads-ad-size' ).text() !== $filter_size.val();
			}).addClass( 'advads-hidden' );
		}
		if ( $filter_date.val() ) {
			$the_list_tr.not( '.advads-hidden' ).filter( function() {
				return jQuery( this ).find( '.' + $filter_date.val() ).length === 0;
			}).addClass( 'advads-hidden' );
		}
		if ( $filter_group.val() ) {
			$the_list_tr.not( '.advads-hidden' ).filter( function() {
				var ret = false;
				//iterate through each group within current tr
				jQuery( this ).find( '.taxonomy-advanced_ads_groups a' ).each( function() {
					if ( jQuery( this ).text() === $filter_group.val() ) {
						ret = true;
						return false; //break the loop
					}
				});
				return ret === false;
			}).addClass( 'advads-hidden' );
		}
		// create stripped table, because css nth-child does not counts hidden rows
		$the_list_tr.not( '.advads-hidden' ).filter( ':odd' ).addClass( 'advads-ad-list-odd' ).removeClass( 'advads-ad-list-even' ).end()
		.filter( ':even' ).addClass( 'advads-ad-list-even' ).removeClass( 'advads-ad-list-odd' );
	});
}
