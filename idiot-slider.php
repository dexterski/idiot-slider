<?php 
/*
Plugin Name: Idiot Slider
Plugin URI: https://bogaczek.com
Description: As simple as possible image slider using WooThemes FlexSlider 2 and native Wordpress manage components (Slides as Posts, Carousels as Tags). Use Featured Image as image for slide, Title as slide header and content as slider description – or hide it via CSS. Change site-url to add URL to slide. Show slider where you wish using Shortcode: <code>[slider carousel="carousel-name" animation="slide" slideshowspeed="5"]</code> Animation can be slide or fade; slideshowspeed is how many seconds carousel should display each slide. 
Version: 0.78
Author: Black Sun
Author URI: https://bogaczek.com
Text Domain: idiot-slider
*/
defined('ABSPATH') or die();



function register_idiot() {
	wp_register_script( 'flexslider', plugin_dir_url(__FILE__) . '/assets/flexslider/js/jquery.flexslider-min.js', array('jquery'), '1.0.0', true );
}
add_action('init', 'register_idiot');



function print_idiot() {
	global $add_idiot, $ss_atts;
	if ( $add_idiot ) {
		$speed = $ss_atts['slideshowspeed']*1000;
		echo "<script type=\"text/javascript\">
jQuery(document).ready(function($) {
	$('head').prepend($('<link>').attr({
		rel: 'stylesheet',
		type: 'text/css',
		media: 'screen',
		href: '" . plugin_dir_url(__FILE__) . "assets/flexslider/css/flexslider.css'
	}));
	$('.flexslider').flexslider({
		animation: '".$ss_atts['animation']."',
		slideshowSpeed: ".$speed.",
		controlNav: false
	});
});
</script>";
		wp_print_scripts('flexslider');
	} else {
		return;
	}
}
add_action('wp_footer', 'print_idiot');

function create_idiot_posttype() {
    $args = array(
      'public' => false,
      'show_ui' => true,
      'menu_icon' => 'dashicons-images-alt',
      'capability_type' => 'page',
      'rewrite' => array( 'slider-loc', 'post_tag' ),
      'label'  => 'Slides',
      'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail', 'page-attributes')
    );
    register_post_type( 'slider', $args );
}
add_action( 'init', 'create_idiot_posttype' );

function create_slider_carousel_tax() {
	register_taxonomy(
		'slider-loc',
		'slider',
		array(
			'label' => 'Carousels',
			'public' => false,
			'show_ui' => true,
			'show_admin_column' => true,
			'rewrite' => false
		)
	);
}
add_action( 'init', 'create_slider_carousel_tax' );

function set_default_slidermeta($post_ID){
    add_post_meta($post_ID, 'slider-url', 'http://', true);
    return $post_ID;
}
add_action('wp_insert_post', 'set_default_slidermeta');

function idiot_shortcode($atts = null) {
	global $add_idiot, $ss_atts;
	$add_idiot = true;
	$ss_atts = shortcode_atts(
		array(
			'carousel' => '',
			'limit' => -1,
			'ulid' => 'flexid',
			'animation' => 'slide',
			'slideshowspeed' => 5
		), $atts, 'slider'
	);
	$args = array(
		'post_type' => 'slider',
		'posts_per_page' => $ss_atts['limit'],
		'orderby' => 'menu_order',
		'order' => 'ASC'
	);
	if ($ss_atts['carousel'] != '') {
		$args['tax_query'] = array(
			array( 'taxonomy' => 'slider-loc', 'field' => 'slug', 'terms' => $ss_atts['carousel'] )
		);
	}
	$the_query = new WP_Query( $args );
	$slides = array();
	if ( $the_query->have_posts() ) {
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$imghtml = get_the_post_thumbnail(get_the_ID(), 'full');
			$url = get_post_meta(get_the_ID(), 'slider-url', true);
			if ($url != '' && $url != 'http://') {
				$imghtml = '<a href="'.$url.'">'.$imghtml.'</a>';
			}
			$slides[] = '
				<li>
					<div class="slide-media">'.$imghtml.'</div>
					<div class="slide-content">
						<h3 class="slide-title">'.get_the_title().'</h3>
						<div class="slide-text">'.get_the_content().'</div>
					</div>
				</li>';
		}
	}
	wp_reset_query();
	return '
	<div class="flexslider" id="'.$ss_atts['ulid'].'">
		<ul class="slides">
			'.implode('', $slides).'
		</ul>
	</div>';
}
add_shortcode( 'slider', 'idiot_shortcode' );

//Add a link to the settings page on the plugins.php page.
function idiot_action_links( $links ) {
	$links = array_merge( array(
		'<a href="' . esc_url( admin_url( '/edit.php?post_type=slider' ) ) . '">' . __( 'Add Slide', 'textdomain' ) . '</a>',
		'<a href="' . esc_url( admin_url( '/edit-tags.php?taxonomy=slider-loc&post_type=slider' ) ) . '">' . __( 'Dodaj karuzelę', 'textdomain' ) . '</a>',
		'<a href="https://github.com/woocommerce/FlexSlider" target="_blank">' . __( 'FlexSlider 2', 'textdomain' ) . '</a>'
	), $links );
	return $links;
}
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'idiot_action_links' );
?>