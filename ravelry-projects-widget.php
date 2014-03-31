<?php
/**
 * @package Ravelry_projects_widget
 * @version 1.1
 */
/*
Plugin Name: Ravelry projects widget
Description: Show your recent Ravelry projects in a widget.
Version: 1.1
Text Domain: ravelry-projects-widget
Author: Annika Lindstedt
Author URI: http://www.annikalindstedt.com
License: GPL2

Copyright 2012-2013  Annika Lindstedt  (email : annika.lindstedt@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

class Ravelry_projects_widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'ravelry_projects',
			'Ravelry projects',
			array( 'description' => __( "Your most recent Ravelry projects.", "ravelry-projects-widget" ))
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$ravelry_username = $instance['ravelry_username'];
		$nr_projects = $instance['nr_projects'];
		$finished_projects = $instance['finished_projects'];
		$project_notes = isset($instance['project_notes']) ? $instance['project_notes'] : 'checked';
		$ravelry_profile_link = $instance['ravelry_profile_link'];
		$error_message = false;

		include_once(ABSPATH . WPINC . '/feed.php');
		
		if($ravelry_username != "") {
			$rss_feed_address = "http://www.ravelry.com/projects/$ravelry_username.rss".($finished_projects == "" ? "" : "?status=finished");

			// Get a SimplePie feed object from the specified feed source.
			$rss = fetch_feed($rss_feed_address);
			
			if (!is_wp_error( $rss ) ) { // Checks that the object is created correctly 
				$nr_projects = (int)$nr_projects;
			    $maxitems = $rss->get_item_quantity($nr_projects);
				
			    // Build an array of all the items, starting with element 0 (first element).
			    $rss_items = $rss->get_items(0, $maxitems); 
				
				if ($maxitems == 0) {
					$error_message = __( "Sorry, you have no Ravelry projects to show yet. Start with a new knitting or crocheting project right away!", "ravelry-projects-widget" );
				}
			} else {
				$error_message = __( "Something went wrong, check your Ravelry username.", "ravelry-projects-widget" );
			}
		} else {
			$error_message = __( "You need to enter a Ravelry username in your widget.", "ravelry-projects-widget" );
		}
		
		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		?>
		
		<?php if(!$error_message) { ?>
			<ul class="my-ravelry-projects">
			    <?php // Loop through each feed item and display each item as a hyperlink.
			    foreach ( $rss_items as $item ) : ?>
			    <li>
					<?php $projectstest = new SimpleXMLElement('<item>' . $item->get_content() . '</item>'); ?>
					<?php if($projectstest->img->asXML() != "") { ?>
						<div><?php echo $projectstest->img->asXML(); ?></div>
					<?php } ?>
					<a href='<?php echo esc_url( $item->get_permalink() ); ?>' class="project-title"><?php echo esc_html( $item->get_title() ); ?></a>
					<span class="rav-proj-date"><?php echo $item->get_date('j F Y'); ?></span>
					<?php 
						if($project_notes != "") {
							echo $projectstest->div->asXML();
						}
					?>
			    </li>
			    <?php endforeach; ?>
			</ul>
		<?php } else {
			echo "<p>$error_message</p>";
		}
		
		if($ravelry_profile_link != "" && $ravelry_username != "") { ?>
			<p class="rav-proj-profile-link"><a href="http://www.ravelry.com/people/<?php echo $ravelry_username; ?>" target="_blank"><img src="<?php echo plugins_url('ravelry-text-icon.png', __FILE__); ?>" alt="Ravelry" /> <?php _e( "I'm", "ravelry-projects-widget" ); ?> <?php echo $ravelry_username; ?> <?php _e( "on Ravelry.", "ravelry-projects-widget" ); ?></a></p>
		<?php }
		
		echo $after_widget;
	}

    /**
     * @param $atts shortcode attributes
     * @return string shortcode text
     */
    public function shortcode($atts) {
        extract( shortcode_atts( array(
            'before_title' => '<span class="rav-proj-title">',
            'title' => '',
            'after_title' => '</span>',
            'before' => '<div class="my-ravelry-projects">',
            'after' => '</div>',
            'username' => '',
            'nr_projects' => '5',
            'finished_projects' => '',
            'project_notes' => '1',
            'ravelry_profile_link' => '1',
        ), $atts ) );
        ob_start();
        $this->widget(array(
            'before_widget' => $before,
            'before_title' => $before_title,
            'after_title' => $after_title,
            'after_widget' => $after,
        ), array(
            'title' => $title,
            'ravelry_username' => $username,
            'nr_projects' => $nr_projects,
            'finished_projects' => $finished_projects == '1' ? '1' : '',
            'project_notes' => $project_notes == '1' ? '1' : '',
            'ravelry_profile_link' => $ravelry_profile_link == '1' ? '1' : '',
        ));
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }


	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['ravelry_username'] = strip_tags( $new_instance['ravelry_username'] );
		$instance['nr_projects'] = strip_tags( $new_instance['nr_projects'] );
		$instance['finished_projects'] = strip_tags( $new_instance['finished_projects'] == "" ? "" : "checked" );
		$instance['project_notes'] = strip_tags( $new_instance['project_notes'] == "" ? "" : "checked" );
		$instance['ravelry_profile_link'] = strip_tags( $new_instance['ravelry_profile_link'] == "" ? "" : "checked" );
		
		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance, 
			array(
				'title' => '',
				'ravelry_username' => '',
				'nr_projects' => '',
				'finished_projects' => '',
				'ravelry_profile_link' => '',
				'project_notes' => 'checked'
				)
		);
		
		$title = strip_tags($instance['title']);
		$ravelry_username = strip_tags($instance['ravelry_username']);
		$nr_projects = strip_tags($instance['nr_projects']);
		$finished_projects = strip_tags($instance['finished_projects']);
		$project_notes = strip_tags($instance['project_notes']);
		$ravelry_profile_link = strip_tags($instance['ravelry_profile_link']);
				
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( "Title:", "ravelry-projects-widget" ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<label for="<?php echo $this->get_field_id( 'ravelry_username' ); ?>"><?php _e( "Ravelry username:", "ravelry-projects-widget" ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'ravelry_username' ); ?>" name="<?php echo $this->get_field_name( 'ravelry_username' ); ?>" type="text" value="<?php echo esc_attr( $ravelry_username ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'nr_projects' ); ?>"><?php _e( "Number of projects to show:", "ravelry-projects-widget" ); ?></label> 
		<input id="<?php echo $this->get_field_id( 'nr_projects' ); ?>" name="<?php echo $this->get_field_name( 'nr_projects' ); ?>" type="text" size="3" value="<?php echo esc_attr( $nr_projects ); ?>" />
		</p>
		<p>
		<input id="<?php echo $this->get_field_id( 'finished_projects' ); ?>" name="<?php echo $this->get_field_name( 'finished_projects' ); ?>" type="checkbox" <?php echo ($finished_projects == "" ? "" : "checked"); ?> />
		<label for="<?php echo $this->get_field_id( 'finished_projects' ); ?>"><?php _e( "Only show finished projects", "ravelry-projects-widget" ); ?></label> 
		</p>
		<p>
		<input id="<?php echo $this->get_field_id( 'project_notes' ); ?>" name="<?php echo $this->get_field_name( 'project_notes' ); ?>" type="checkbox" <?php echo ($project_notes == "" ? "" : "checked"); ?> />
		<label for="<?php echo $this->get_field_id( 'project_notes' ); ?>"><?php _e( "Show my project notes", "ravelry-projects-widget" ); ?></label> 
		</p>
		<p>
		<input id="<?php echo $this->get_field_id( 'ravelry_profile_link' ); ?>" name="<?php echo $this->get_field_name( 'ravelry_profile_link' ); ?>" type="checkbox" <?php echo ($ravelry_profile_link == "" ? "" : "checked"); ?> />
		<label for="<?php echo $this->get_field_id( 'ravelry_profile_link' ); ?>"><?php _e( "Show link to my Ravelry profile", "ravelry-projects-widget" ); ?></label>
		</p>
		<?php 
	}

}

/**
 * @param $atts shortcode attributes
 * @return string shortcode text
 */
function ravelry_projects_shortcode($atts) {
    $widget = new Ravelry_projects_widget();
    return $widget->shortcode($atts);
}

add_shortcode( 'ravelry-projects', 'ravelry_projects_shortcode' );

add_action( 'widgets_init', create_function( '', 'register_widget( "Ravelry_projects_widget" );' ) );

$plugin_dir = basename( dirname( __FILE__ ) );
load_plugin_textdomain( 'ravelry-projects-widget', null, $plugin_dir . '/languages/' );

if ( is_active_widget( false, false, 'ravelry_projects' ) ) {
	add_action('wp_enqueue_scripts', 'ravelry_projects_init');
}

function ravelry_projects_init() {
	wp_register_style( 'ravelry_projects', plugins_url('ravelry-projects-widget.css', __FILE__), '', '1.1', 'all' );
	wp_enqueue_style( 'ravelry_projects' );
}

?>
