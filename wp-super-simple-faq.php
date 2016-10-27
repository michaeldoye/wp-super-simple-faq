<?php
/*
Plugin Name: WP Super Simple FAQ
Plugin URI: https://github.com/michaeldoye/bd-made-to-measure
Description: FAQ for WP!
Author: Web SEO Online (PTY) LTD
Author URI: https://webseo.co.za
Version: 0.0.1

	Copyright: © 2016 Web SEO Online (PTY) LTD (email : michael@webseo.co.za)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	if ( ! class_exists( 'WP_simple_faq' ) ) {
		
		/**
		 * Localisation
		 **/
		load_plugin_textdomain( 'WP_simple_faq', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

		class WP_simple_faq {

		    /**
		     * The array of templates that this plugin tracks.
		     */
		    protected $templates;

			public function __construct() {
				// called only after woocommerce has finished loading
				add_action( 'woocommerce_init', array( &$this, 'woocommerce_loaded' ) );
				// called after all plugins have loaded
				add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
				// called just before the woocommerce template functions are included
				add_action( 'init', array( &$this, 'include_template_functions' ), 20 );
				// Register post type
				add_action( 'init', array( &$this,'create_faq_post_type' ) );
				// Register custom taxonomy
				add_action( 'init', array( &$this, 'create_faq_taxonomy' ) );
				// Enqueue frontend scripts
				add_action( 'wp_enqueue_scripts', array( &$this, 'frontend_scripts' ) );
				// Add (ng) attributes to body tag - conditionally 
				add_filter( 'template_include', array( &$this, 'start_buffer_capture' ), 1 );
				// Ajax get topics
				add_action( 'wp_ajax_ssf_ajax_get_topics', array( &$this, 'ssf_ajax_get_topics' ) );
				add_action( 'wp_ajax_nopriv_ssf_ajax_get_topics', array( &$this, 'ssf_ajax_get_topics' ) );
				// Ajax get topic content
				add_action( 'wp_ajax_ssf_ajax_get_topics_content', array( &$this, 'ssf_ajax_get_topics_content' ) );
				add_action( 'wp_ajax_nopriv_ssf_ajax_get_topics_content', array( &$this, 'ssf_ajax_get_topics_content' ) );
				// Ajax get child topics
				add_action( 'wp_ajax_ssf_ajax_get_child_topics', array( &$this, 'ssf_ajax_get_child_topics' ) );
				add_action( 'wp_ajax_nopriv_ssf_ajax_get_child_topics', array( &$this, 'ssf_ajax_get_child_topics' ) );																										
				
				// indicates we are running the admin
				if ( is_admin() ) {
					// ...
				}
				
				// indicates we are being served over ssl
				if ( is_ssl() ) {
					// ...
				}

	            $this->templates = array();

	            // Add a filter to the attributes metabox to inject template into the cache.
	            add_filter(
	                'page_attributes_dropdown_pages_args',
	                 array( $this, 'register_project_templates' ) 
	            );

	            // Add a filter to the save post to inject out template into the page cache
	            add_filter(
	                'wp_insert_post_data', 
	                array( $this, 'register_project_templates' ) 
	            );

	            // Add a filter to the template include to determine if the page has our 
	            // template assigned and return it's path
	            add_filter(
	                'template_include', 
	                array( $this, 'view_project_template') 
	            );

	            // Add your templates to this array.
	            $this->templates = array(
	                'wp-ssfaq-template.php' => 'FAQ Template',
	            );				
			}


			/**
			 * Add scripts used on the front end
			 */
			public function frontend_scripts () {
				global $post;

				// Global scripts
				if ( is_page_template( 'wp-ssfaq-template.php' ) ) {  
					// JS
					wp_enqueue_script( 'angular_js', plugin_dir_url( __FILE__ ) . 'assets/js/angular-1.4.6-min.js' );	
					wp_enqueue_script( 'angular_animate_js', plugin_dir_url( __FILE__ ) . 'assets/js/angular-animate.min.js' );				 
					wp_enqueue_script( 'faq_scripts', plugin_dir_url( __FILE__ ) . 'assets/js/faq-script.js', array( 'jquery', 'angular_js' ) );

					// CSS
					wp_register_style( 'faq_css', plugin_dir_url( __FILE__ ) .'assets/css/faq.css', array(), '20161026' );
					wp_enqueue_style( 'faq_css' );				
				}

				// Create local variables here
				wp_localize_script( 'faq_scripts', 'faq', array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'product_id' => $post->ID					
				));
			}

			/**
			 * Add angularJS attributes to body tag
			 * depending on product type:
			 * It will string replace the <body> tag to add the
			 * relevant attributes such as ng-app and ng-controller
			 * @param string $template
			 * @return string 
			 **/
			public function start_buffer_capture( $template ) {

				if ( is_page_template( 'wp-ssfaq-template.php' ) ) {
					// Start Page Buffer
					ob_start( array( &$this, 'end_buffer_capture' ) );
				}
			 	return $template;
			}


			/**
			 * Add angular attributes to body tag
			 * depending on product type
			 * @param string $buffer
			 * @return string
			 **/
			public function end_buffer_capture( $buffer ) {

				if ( is_page_template( 'wp-ssfaq-template.php' ) ) {
			 		return str_replace( '<body', '<body ng-app="wp_super_simple_faq" ng-controller="faqCtrl"', $buffer );
			 	}

			 	else {
			 		return str_replace( '<body', '<body ng-app="bd_made_to_measure"', $buffer );
			 	}			 				 		
			}						

			
			/**
			 * Take care of anything that needs woocommerce to be loaded.  
			 * For instance, if you need access to the $woocommerce global
			 */
			public function woocommerce_loaded() {
				//..
			}

			
			/**
			 * Take care of anything that needs all plugins to be loaded
			 */
			public function plugins_loaded() {
				//..
			}

			
			/**
			 * Override any of the template functions from woocommerce/woocommerce-template.php 
			 * with our own template functions file
			 */
			public function include_template_functions() {
				//include( 'woocommerce-template.php' );
			}


		    public function register_project_templates( $atts ) {

		            // Create the key used for the themes cache
		            $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		            // Retrieve the cache list. 
		            // If it doesn't exist, or it's empty prepare an array
		            $templates = wp_get_theme()->get_page_templates();
		            if ( empty( $templates ) ) {
		                $templates = array();
		            } 

		            // New cache, therefore remove the old one
		            wp_cache_delete( $cache_key , 'themes');

		            // Now add our template to the list of templates by merging our templates
		            // with the existing templates array from the cache.
		            $templates = array_merge( $templates, $this->templates );

		            // Add the modified cache to allow WordPress to pick it up for listing
		            // available templates
		            wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		            return $atts;

		    } 


		    /**
		     * Checks if the template is assigned to the page
		     */
		    public function view_project_template( $template ) {

		            global $post;

		            if (!isset($this->templates[get_post_meta( 
		                $post->ID, '_wp_page_template', true 
		            )] ) ) {
		                return $template;
		            } 

		            $file = plugin_dir_path(__FILE__). get_post_meta( 
		                $post->ID, '_wp_page_template', true 
		            );

		            // Just to be safe, we check if the file exist first
		            if( file_exists( $file ) ) {
		                return $file;
		            } 
		            else { echo $file; }

		            return $template;

		    }


			public function create_faq_taxonomy() {
				register_taxonomy(
					'FAQ Topics',
					'faq',
					array(
						'label' => __( 'FAQ Topics' ),
						'rewrite' => array( 'slug' => 'faq-cats' ),
						'hierarchical' => true,
					)
				);				
			}


			public function create_faq_post_type() {
				$labels = array(
				    'name' => 'FAQ',
				    'singular_name' => 'FAQ',
				    'add_new' => 'Add New FAQ',
				    'all_items' => 'All FAQs',
				    'add_new_item' => 'Add New FAQ',
				    'edit_item' => 'Edit FAQ',
				    'new_item' => 'New FAQ',
				    'view_item' => 'View FAQ',
				    'search_items' => 'Search FAQs',
				    'not_found' =>  'No FAQs found',
				    'not_found_in_trash' => 'No FAQs found in trash',
				    'parent_item_colon' => 'Parent FAQ:',
				    'menu_name' => 'FAQs'
				);
				$args = array(
					'labels' => $labels,
					'description' => "A description for your FAQ",
					'public' => true,
					'exclude_from_search' => false,
					'publicly_queryable' => true,
					'show_ui' => true,
					'show_in_nav_menus' => true,
					'show_in_menu' => true,
					'show_in_admin_bar' => true,
					'menu_position' => 5,
					'menu_icon' => null,
					'capability_type' => 'post',
					'hierarchical' => true,
					'supports' => array('title','editor','author','thumbnail','excerpt','custom-fields','revisions','page-attributes','post-formats'),
					'has_archive' => true,
					'rewrite' => array( 'slug' => 'faq' ),
					'query_var' => true,
					'can_export' => true,
					'taxonomies' => array( 'FAQ Topics' ),
				);
				register_post_type( 'faq', $args );				
			}


			public function ssf_ajax_get_topics() {

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {	

					$categories = get_terms('FAQ Topics', array('hide_empty' => false));
					$categoryHierarchy = array();						
					$this->sort_terms_hierarchicaly( $categories, $categoryHierarchy );
					echo json_encode( $categoryHierarchy );

				}

				wp_die();	
			}

			public function ssf_ajax_get_child_topics() {

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

					$parent = $_GET['parent'];

					$categories = get_terms('FAQ Topics', array('hide_empty' => false));
					$categoryHierarchy = array();						
					$this->sort_terms_hierarchicaly( $categories, $categoryHierarchy );	

					$terms = array();
					foreach ($categoryHierarchy as $value) {
						foreach ($value->children as $val) {
							if ($val->parent == $parent) {
								$terms[] = $val;
							}
						}
					}

					echo json_encode( $terms );

				}

				wp_die();				
			}

			public function ssf_ajax_get_topics_content() {

				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

					$args = array(
						'post_type' => 'faq',
						'tax_query' => array(
							array(
								'taxonomy' => 'FAQ Topics',
								'field'    => 'id',
								'terms' => $_GET['id']
							),
						),
					);

					$query = get_posts($args);				

					echo json_encode( $query );

				}

				wp_die();				
			}


			/**
			 * Recursively sort an array of taxonomy terms hierarchically. Child categories will be
			 * placed under a 'children' member of their parent term.
			 * @param Array   $cats     taxonomy term objects to sort
			 * @param Array   $into     result array to put them in
			 * @param integer $parentId the current parent ID to put them in
			 */
			public function sort_terms_hierarchicaly(Array &$cats, Array &$into, $parentId = 0) {

			    foreach ( $cats as $i => $cat ) {
			        if ( $cat->parent == $parentId ) {
			            $into[] = $cat;
			            unset( $cats[ $i ] );
			        }
			    }

			    foreach ( $into as $topCat ) {
			        $topCat->children = array();
			        $this->sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
			    }
			}

		}

		// finally instantiate our plugin class and add it to the set of globals
		$GLOBALS['WP_simple_faq'] = new WP_simple_faq();
	}
}