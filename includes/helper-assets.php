<?php
	
	/**
	* File was added with version 4.3 and holds functions that are related to compressing/merging/enqueing scripts and styles
	*
	* @since 4.3
	* @added_by Kriesi
	*/




	
//reset merged assets generated by the asset manager
if(!function_exists('avia_reset_merged_assets'))
{
	add_action('ava_after_theme_update', 			'avia_reset_merged_assets' , 100, 1 ); /*after theme update*/
	add_action('ava_after_import_demo_settings', 	'avia_reset_merged_assets' , 100, 1 ); /*after demo settings imoport*/
	add_action('avia_ajax_after_save_options_page', 'avia_reset_merged_assets' , 100, 1 ); /*after options page saving*/
	
	function avia_reset_merged_assets( $options = false )
	{
		Avia_Builder()->asset_manager()->reset_db_asset_list();
	}
}
	
	
	

if( ! function_exists( 'av_asset_merging_settings' ) )
{
	/**
	 * Adjust the level of file merging in asset-manager.class.php by taking the backend options into account and filtering the file compression function
	 * 
	 * @since 4.3
	 * @added_by Kriesi
	 * @param array $which_files
	 * @return array
	 */
	function av_asset_merging_settings( $which_files )
	{		
		$css_merging = avia_get_option('merge_css', 'avia');
		$js_merging  = avia_get_option('merge_js', 'avia');
		
		if(av_count_untested_plugins() == 0)
		{
			if($css_merging == 'avia') $css_merging = "all";
			if($js_merging == 'avia')  $js_merging = "all";
		}
		
		$which_files['css'] = $css_merging;
		$which_files['js']  = $js_merging;
		
		return $which_files;
	}
	
	add_filter( 'avf_merge_assets', 'av_asset_merging_settings', 10, 1 );
}



if( ! function_exists( 'av_delete_asset_switch' ) )
{
	/**
	 * Changes if the theme deletes generated css and js files 
	 * 
	 * @since 4.3
	 * @added_by Kriesi
	 * @param bool $delete
	 * @return bool
	 */
	function av_delete_asset_switch( $delete )
	{		
		$delete = avia_get_option('delete_assets_after_save', false);
		if($delete === false || $delete == "disabled")
		{
			$delete = false;
		}
		else
		{
			$delete = true;
		}
		
		return $delete;
	}
	
	add_filter( 'avf_delete_assets', 'av_delete_asset_switch', 10, 1 );
}






if( ! function_exists( 'av_disable_emojis' ) )
{
	/**
	* Disable the emoji's. based on: https://kinsta.com/knowledgebase/disable-emojis-wordpress/
	*
	* @since 4.3
	* @added_by Kriesi
	*/
	function av_disable_emojis() 
	{
		if(avia_get_option('disable_emoji') != "disable_emoji") return false;
		
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'tiny_mce_plugins', 'av_disable_emojis_tinymce' );
		add_filter( 'wp_resource_hints', 'av_disable_emojis_remove_dns_prefetch', 10, 2 );
		
		if(get_option('use_smilies') === "1")
		{
			update_option('use_smilies', "0");
		}
	}
	
	add_action( 'init', 'av_disable_emojis' );
}


if( ! function_exists( 'av_disable_emojis_tinymce' ) )
{
/**
 * Filter function used to remove the tinymce emoji plugin.
 * 
 * @since 4.3
 * @added_by Kriesi
 * @param array $plugins 
 * @return array Difference betwen the two arrays
 */

	function av_disable_emojis_tinymce( $plugins ) 
	{
		if ( is_array( $plugins ) ) 
		{
			return array_diff( $plugins, array( 'wpemoji' ) );
		} 
		else 
		{
			return array();
		}
	}
}

if( ! function_exists( 'av_disable_emojis_remove_dns_prefetch' ) )
{
	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 *
	 * @since 4.3
	 * @added_by Kriesi	 
	 * @param array $urls URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed for.
	 * @return array Difference betwen the two arrays.
	 */
	function av_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) 
	{
		if ( 'dns-prefetch' == $relation_type ) 
		{
			/** This filter is documented in wp-includes/formatting.php */
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
	
			$urls = array_diff( $urls, array( $emoji_svg_url ) );
		}
	
		return $urls;
	}
}



if( ! function_exists( 'av_disable_unused_assets' ) )
{
	/**
	* Disable unused assets based on option settings
	*
	* @since 4.3
	* @added_by Kriesi
	*/
	function av_disable_unused_assets( $disabled ) 
	{	
		if( is_admin() )
		{
			return $disabled;
		}
		
		//	for preview we need to load all elements because we did not adjust used elements
		if( is_preview() )
		{
			return array();
		}
		
		//special use cases: audio and blog - they only can be disabled manually
		if(avia_get_option('disable_mediaelement') == 'disable_mediaelement')
		{
			$disabled['av_audio'] = true;
			
			if(avia_get_option('disable_video') == 'disable_video')
			{
				$disabled['av_video'] = true;
			}
		}
		
		if(avia_get_option('disable_blog') == 'disable_blog')
		{
			$disabled['av_blog'] 			= true;
			$disabled['av_comments_list'] 	= true;
		}
		
		
		
		//auto disabling on
		$disabling_alb = avia_get_option('disable_alb_elements', 'auto');
		
		if("auto" == $disabling_alb)
		{
			$elements 		 = Avia_Builder()->element_manager()->get_elements_state( 'blog' );
			$may_be_disabled = Avia_Builder()->may_be_disabled_automatically;
			
			if(!empty($elements))
			{
				foreach($elements as $key => $value)
				{ 
					if(empty($value) && in_array($key, $may_be_disabled) ) $disabled[$key] = true;
				}
			}
			
			//check if the mailchimp widget is used. if so we do not disable that element
			if ( is_active_widget(false, false, 'avia_mailchimp_widget', true) && isset($disabled['av_mailchimp'])) 
			{
				unset($disabled['av_mailchimp']);
			}
			
			//check if background video is used. if so we need the slideshow
			if(av_slideshow_required())
			{
				unset($disabled['av_slideshow']);
			}
		}
		else if("manually" == $disabling_alb) //manually disabling on
		{
			$options = avia_get_option();
			foreach($options as $key => $option)
			{
				if( strpos($key, "av_alb_disable_") !== false)
				{
					$shortcode = str_replace("av_alb_disable_", "", $key);
					if($option == $key)
					{
						$disabled[$shortcode] = true;
					}
				}
			}
				
		}
		
		//tabs can be necessary for the cookie bar setting
		if(avia_get_option('cookie_consent') == "cookie_consent")
		{
			$buttons = json_encode(avia_get_option('msg_bar_buttons', array()));
			if(strpos($buttons, 'info_modal') !== false)
			{
				unset($disabled['av_heading']);
				unset($disabled['av_hr']);
				unset($disabled['av_tab_container']);
			}
		}
		
		return $disabled;
	}
	
	add_filter( 'avf_disable_frontend_assets', 'av_disable_unused_assets', 10, 1 );
}




if( ! function_exists( 'av_disable_button_in_backend' ) )
{
	/**
	* Disable button in backend if the user has manually set them to be disabled
	*
	* @since 4.3
	* @added_by Kriesi
	*/
	function av_disable_button_in_backend( $shortcode ) 
	{
		$key = 'av_alb_disable_'.$shortcode['shortcode'];
		$disabled = avia_get_option($key);
		
		if($key == $disabled && !empty( $shortcode['disabling_allowed'] ))
		{
			$shortcode['disabled'] = array(
				'condition' => true, 
				'text'   	=> __( 'This element is disabled in your theme options. You can enable it in Enfold &raquo; Performance', 'avia_framework' ));
		}
		
		return $shortcode;
	}
	
	if( avia_get_option('disable_alb_elements') == "manually" )
	{
		add_filter( 'avf_shortcode_insert_button_backend', 'av_disable_button_in_backend', 10, 1 );
	}
}

if( ! function_exists( 'av_video_assets_required' ) )
{
	/**
	* Checks the entries for the current page for av_video, av_audio, <audio>, <video> and background video elements. 
	*
	* @since 4.3
	* @added_by Kriesi
	* @param array $assets
	*/
	function av_video_assets_required() 
	{
		global $posts;
		
		$used 	= false;
		$regex 	= "!\[av_blog|\[av_postslider|\[av_video|\[av_audio|\[av_player|\[audio|<audio|<video|video\=\'http|video\=\"http!";
		
		foreach($posts as $post)
		{
			preg_match($regex, $post->post_content, $matches);
			
			if(isset($matches[0]) )
			{
				$used = true;
				break;
			}
			
			$format = get_post_format( $post->ID );
			
			if($format == "audio" || $format == "video")
			{
				$used = true;
				break;
			}
		}
		
		return $used;
	}
}

if( ! function_exists( 'av_slideshow_required' ) )
{
	/**
	* Checks the entries for background video elements. 
	*
	* @since 4.4
	* @added_by Kriesi
	* @param array $assets
	*/
	function av_slideshow_required() 
	{
		global $posts;
		
		$used 	= false;
		$regex 	= "!video\=\'http|video\=\"http!";
		
		foreach($posts as $post)
		{
			preg_match($regex, $post->post_content, $matches);
			
			if(isset($matches[0]) )
			{
				$used = true;
				break;
			}
		}
		
		return $used;
	}
}




if( ! function_exists( 'av_comments_on_builder_posts' ) )
{
	/**
	* Checks the entries for the current page for av_video, av_audio, <audio>, <video> and background video elements. 
	*
	* @since 4.3
	* @added_by Kriesi
	* @param array $assets
	*/
	add_filter( 'comments_open', 'av_comments_on_builder_posts_required', 10, 2 );
	
	function av_comments_on_builder_posts_required( $open , $post_id ) 
	{
		if($open && is_singular())
		{
			$post = get_post( $post_id );
			
			if('active' === get_post_meta( $post->ID, '_aviaLayoutBuilder_active', true ))
			{
				$regex 	= "!\[av_comments_list!";
				preg_match($regex, $post->post_content, $matches);
				
				if(!isset($matches[0]))
				{
					$open = false;
				}
				
			}
		}
		
		return $open;
	}
}



if(!function_exists('av_move_jquery_into_footer'))
{
	/**
	* moves the jquery library to the footer. only recommended to use if we know that no plugins that we dont know are active
	*
	* either call directly or hook into add_action( 'wp_enqueue_scripts', 'av_move_jquery_into_footer' , 20);
	*
	* @since 4.3
	* @added_by Kriesi
	* @param array $assets
	*/
	
	function av_move_jquery_into_footer() 
	{
		if( is_admin() ) {
	        return;
	    }
		
	    wp_scripts()->add_data( 'jquery', 'group', 1 );
	    wp_scripts()->add_data( 'jquery-core', 'group', 1 );
	    wp_scripts()->add_data( 'jquery-migrate', 'group', 1 );
	}	
}



if(!function_exists('av_count_untested_plugins'))
{
	/**
	* check how many plugins are active that we did not test the theme with.
	*
	* a plugin is considered "tested" if it still works fine after these modifications:
	* - jQuery was moved to the footer in frontend
	*
	* the function is used to set certain optimized defaults on small installations that do not use a big amount of plugins
	*
	* @since 4.3
	* @added_by Kriesi
	* @param array $assets
	*/
	
	function av_count_untested_plugins() 
	{
		//all plugins that start with the name "avia" are considered tested
		$tested_plugins = apply_filters( 'avf_tested_plugin_list' , array(
			'hello.php',
			'akismet/akismet.php',
			'layerslider/layerslider.php',
			'tiny-compress-images/tiny-compress-images.php',
			'woocommerce/woocommerce.php',
			'shortpixel-image-optimiser/wp-shortpixel.php',
			'optimus/optimus.php',
			'wp-super-cache/wp-cache.php',
			'comet-cache/comet-cache.php',
			'comet-cache-pro/comet-cache-pro.php',
			'duplicate-post/duplicate-post.php',
			'wordpress-importer/wordpress-importer.php',
			'wordpress-beta-tester/wp-beta-tester.php',
			'worker/init.php', //managewp
			'really-simple-ssl/rlrsssl-really-simple-ssl.php',
			'bbpress/bbpress.php',
		));
		
		
		$count 		= 0;
		$plugins 	= array_flip(get_option('active_plugins', array()));
		
		if(is_multisite() && function_exists('get_site_option'))
		{
		   $plugins = array_merge($plugins, get_site_option('active_sitewide_plugins', array()));
		}
		
		foreach($plugins as $path => $value)
		{
			if( !in_array($path, $tested_plugins) && strpos($path, "avia") === false)
			{
				$count++;
			}
		}
		
	   return $count;
	}	
}




if( ! function_exists( 'av_untested_plugins_debugging_info' ) )
{
	/**
	* Adds untested plugin count to debugging info
	*
	* @since 4.3
	* @added_by Kriesi
	* @param array $assets
	*/
	function av_untested_plugins_debugging_info( $info ) 
	{
		$info .= "PLAu:".av_count_untested_plugins()."\n";
		return $info;
	}
	
	add_filter( 'avf_debugging_info_add', 'av_untested_plugins_debugging_info', 10, 1 );
}


if( ! function_exists( 'av_print_above_the_fold_assets' ) )
{
	/**
	* Guesses which elements are above the fold (usually the first section or slider) and prints the css in addition
	* Currently not used
	*
	* @since 4.3
	* @added_by Kriesi
	* @param array $assets
	*/
	function av_print_above_the_fold_assets( $assets ) 
	{
		
		return $assets;
	}
	
	// add_filter( 'avf_also_print_asset', 'av_print_above_the_fold_assets', 10, 1 );
}



