<?php

/**
 * Upgrade logic from older data to new one
 * 
 * the version number itself is changed in /admin/includes/class-notices.php::register_version_notices()
 *
 * @since 1.7
 */
class Advanced_Ads_Upgrades {
    
	public function __construct(){
	    
		$internal_options = Advanced_Ads_Plugin::get_instance()->internal_options();
		
		// don’t upgrade if no previous version existed
		if( empty( $internal_options['version'] ) ) {
		    return;
		}
		
		if ( version_compare( $internal_options['version'], '1.7' ) == -1 ) {
			// run with wp_loaded action, because WP_Query is needed and some plugins inject data that is not yet initialized
			add_action( 'wp_loaded', array( $this, 'upgrade_1_7') );
		}
		
		// update version notices – if this doesn’t happen here, the upgrade might run multiple times and destroy updated data
		Advanced_Ads_Admin_Notices::get_instance()->register_version_notices();
	}
    
	/**
	 * upgrade data to version 1.7
	 * rewrite existing display conditions
	 */
	public function upgrade_1_7(){
	    
		// get all ads, regardless of the publish status
		$args['post_status'] = 'any';
		$args['suppress_filters'] = true; // needed to remove issue with a broken plugin from the repository
		$ads = Advanced_Ads::get_instance()->get_model()->get_ads( $args );
		
		// iterate through ads
		// error_log(print_r($ads, true));
		error_log(print_r('–– STARTING ADVANCED ADS data upgrade to version 1.7 ––', true));
		foreach( $ads as $_ad ){
		    // ad options
		    $option_key = Advanced_Ads_Ad::$options_meta_field;
		    if( !isset( $_ad->ID ) || ! $option_key ){
			continue;
		    }
		    $options = get_post_meta( $_ad->ID, $option_key, true );
		    // rewrite display conditions
		    if( ! isset( $options['conditions'] ) ){
			continue;
		    }
		    
		    error_log(print_r('AD ID: ' . $_ad->ID, true));
		    error_log(print_r('OLD CONDITIONS', true));
		    error_log(print_r($options['conditions'], true));
		    
		    $old_conditions = $options['conditions'];
		    
		    // check if conditions are disabled
		    if( ! isset( $old_conditions['enabled'] ) || ! $old_conditions['enabled'] ){
			$new_conditions = '';
		    } else {
			$new_conditions = array();
			
			// rewrite general conditions
			$old_general_conditions = array(
			    'is_front_page',
			    'is_singular',
			    'is_archive',
			    'is_search',
			    'is_404',
			    'is_attachment',
			    'is_main_query'
			);
			$general = array();
			foreach( $old_general_conditions as $_general_condition ){
			    if( isset( $old_conditions[ $_general_condition ] ) && $old_conditions[ $_general_condition ] ) { 
				$general[] = $_general_condition;
			    }
			}
			// move general conditions into display conditions
			// only, if the number of conditions in the previous setting is lower, because only that means there is an active limitation
			// not sure if allowing an empty array is logical, but some users might have set this up to hide an ad
			if( count( $general ) < count( $old_general_conditions ) ){
			    $new_conditions[] = array(
				'type' => 'general',
				'value' => $general
			    );
			}
			
			// rewrite post types condition
			if( isset( $old_conditions[ 'posttypes' ]['include'] ) 
				&& ( !isset ( $old_conditions[ 'posttypes' ]['all'] ) 
				|| ! $old_conditions[ 'posttypes' ]['all'] ) ) {
				if ( is_string( $old_conditions[ 'posttypes' ]['include']) ) {
				    $old_conditions[ 'posttypes' ]['include'] = explode( ',', $old_conditions[ 'posttypes' ]['include'] );
				}
				$new_conditions[] = array(
				    'type' => 'posttypes',
				    'value' => $old_conditions[ 'posttypes' ]['include']
				);
			}
			
			/**
			 * rewrite category ids and category archive ids
			 * 
			 * the problem is that before there was no connection between term ids and taxonomy, now, each taxonomy has its own condition
			 */
			// check, if there are even such options set
			if( ( isset( $old_conditions[ 'categoryids' ] ) 
				&& ( !isset ( $old_conditions[ 'categoryids' ]['all'] ) 
				|| ! $old_conditions[ 'categoryids' ]['all'] ) )
				|| ( isset( $old_conditions[ 'categoryarchiveids' ] ) 
				&& ( !isset ( $old_conditions[ 'categoryarchiveids' ]['all'] ) 
				|| ! $old_conditions[ 'categoryarchiveids' ]['all'] ) )) 
			    { 
			    
				// get all taxonomies
				$taxonomies = get_taxonomies( array('public' => true, 'publicly_queryable' => true), 'objects', 'or' );
				$taxonomy_terms = array();
				foreach ( $taxonomies as $_tax ) {
				    if( $_tax->name === 'advanced_ads_groups' ){
					continue;
				    }
				    // get all terms
				    $terms = get_terms( $_tax->name, array('hide_empty' => false, 'number' => 0, 'fields' => 'ids' ) );
				    if ( is_wp_error( $terms ) || ! count( $terms ) ){
					continue;
				    } else {
					$taxonomy_terms[ $_tax->name ] = $terms;
				    }
				    
				    // get terms that are in all terms and in active terms
				    if( isset( $old_conditions[ 'categoryids' ] ) 
					&& ( !isset ( $old_conditions[ 'categoryids' ]['all'] ) 
					|| ! $old_conditions[ 'categoryids' ]['all'] ) )
				    {
					// honor "include" option first
					if( isset ( $old_conditions[ 'categoryids' ]['include'] ) && count( $old_conditions[ 'categoryids' ]['include'] ) 
						&& $same_values = array_intersect($terms, $old_conditions[ 'categoryids' ]['include']) ){
						    $new_conditions[] = array(
							'type' => 'taxonomy_' . $_tax->name ,
							'operator' => 'is',
							'value' => $same_values
						    );
					} elseif ( isset ( $old_conditions[ 'categoryids' ]['exclude'] ) && count( $old_conditions[ 'categoryids' ]['exclude'] ) 
						&& $same_values = array_intersect($terms, $old_conditions[ 'categoryids' ]['exclude']) ){
						 $new_conditions[] = array(
						    'type' => 'taxonomy_' . $_tax->name ,
						    'operator' => 'is_not',
						    'value' => $same_values
						);
					}
				    }
				    
				    // get terms that are in all terms and in active terms
				    if( isset( $old_conditions[ 'categoryarchiveids' ] ) 
					&& ( !isset ( $old_conditions[ 'categoryarchiveids' ]['all'] ) 
					|| ! $old_conditions[ 'categoryarchiveids' ]['all'] ) )
				    {
					// honor "include" option first
					if( isset ( $old_conditions[ 'categoryarchiveids' ]['include'] ) && count( $old_conditions[ 'categoryarchiveids' ]['include'] ) 
						&& $same_values = array_intersect($terms, $old_conditions[ 'categoryarchiveids' ]['include']) ){
						    $new_conditions[] = array(
							'type' => 'archive_' . $_tax->name ,
							'operator' => 'is',
							'value' => $same_values
						    );
					} elseif ( isset ( $old_conditions[ 'categoryarchiveids' ]['exclude'] ) && count( $old_conditions[ 'categoryarchiveids' ]['exclude'] ) 
						&& $same_values = array_intersect($terms, $old_conditions[ 'categoryarchiveids' ]['exclude']) ){
						 $new_conditions[] = array(
						    'type' => 'archive_' . $_tax->name ,
						    'operator' => 'is_not',
						    'value' => $same_values
						);
					}
				    }
				}
			}
			
			// rewrite single post ids
			if( isset ( $old_conditions[ 'postids' ]['ids'] )
				&& isset ( $old_conditions[ 'postids' ]['method'] )
				&& $old_conditions[ 'postids' ]['method']
				&& ( !isset ( $old_conditions[ 'postids' ]['all'] ) 
				|| ! $old_conditions[ 'postids' ]['all'] ) ) { 
			    $operator = ( $old_conditions[ 'postids' ]['method'] === 'exclude' ) ? 'is_not' : 'is';
			    if ( is_string( $old_conditions[ 'postids' ]['ids']) ) {
				    $old_conditions[ 'postids' ]['ids'] = explode( ',', $old_conditions[ 'postids' ]['ids'] );
			    }
			    $new_conditions[] = array(
				'type' => 'postids',
				'operator' => $operator,
				'value' => $old_conditions[ 'postids' ]['ids']
			    );
			}			
		    }
		    
		    error_log(print_r('NEW CONDITIONS', true));
		    error_log(print_r($new_conditions, true));
		    
		    $options['conditions'] = $new_conditions;
		    
		    // save conditions
		    update_post_meta( $_ad->ID, $option_key, $options );
		}
		
		error_log(print_r('up to 1.7', true));
	}
    
}