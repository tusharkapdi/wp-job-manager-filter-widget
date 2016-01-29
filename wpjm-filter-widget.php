<?php
/*
Plugin Name: WP Job Manager Filter Widget
Plugin URI: http://amplebrain.com/plugins/wp-job-manager-filter-widget/
Description: WP Job Manager Filter widget allows job filter through Keyword, Location, Featured, Filled, Job Type and Category.
Version: 1.0
Author: <a href="mailto:tusharkapdi@gmail.com">Tushar Kapdi</a>
Author URI: http://amplebrain.com/
Text Domain: wpjmfilter
Domain Path: /languages/
Copyright: 2015 Tushar Kapdi
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WP Job Manager Filter Widget is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
WP Job Manager Filter Widget is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WP Job Manager Filter Widget. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WPJM_Filter_Widget class.
 */
class WPJM_Filter_Widget extends WP_Widget {

    /**
     * Widget construction
     */
    function __construct() {
        parent::__construct(
            'WPJMFilter', 
            __( 'WP Job Manager Filter', 'wpjmfilter' ),
            array(
                'description' => __('Displaying filtered job listing', 'wpjmfilter'),
                'classname' => 'job_listings wpjmfilter-widget'
            ),
            array( 'width' => 450)
        );

        load_plugin_textdomain('wpjmfilter', false, basename( dirname( __FILE__ ) ) . '/languages' );

        add_action( 'admin_notices', array( $this, 'wpjm_check_installed' ) );
    }

    /**
     * Check JM version
     */
    public function wpjm_check_installed() {
        if ( ! defined( 'JOB_MANAGER_VERSION' ) ) {
            ?><div class="error"><p><?php _e( 'WP Job Manager Filter Widget requires WP Job Manager to be installed!', 'wpjmfilter' ); ?></p></div><?php
        }
    }

    /**  
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    function widget( $args, $instance ) {

        if (!isset($args['widget_id'])) {
          $args['widget_id'] = null;
        }

        extract($args);
        
        $title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title'], $instance);
        $cssClass = empty($instance['cssClass']) ? '' : $instance['cssClass'];
        $location = empty($instance['location']) ? '' : $instance['location'];
        $keyword = empty($instance['keyword']) ? '' : $instance['keyword'];
        $category = !empty($instance['category']) ? explode(",", $instance['category']) : '';
        $jobtypes = !empty($instance['jobtypes']) ? explode(",", $instance['jobtypes']) : '';
        $featured = !empty($instance['featured']) ? 1 : null;
        $filled = !empty($instance['filled']) ? 1 : null;
        $number = empty($instance['number']) ? '5' : absint($instance['number']);
        if ( $cssClass ) {
            if( strpos($before_widget, 'class') === false ) {
                $before_widget = str_replace('>', 'class="'. $cssClass . '"', $before_widget);
            } else {
                $before_widget = str_replace('class="', 'class="'. $cssClass . ' ', $before_widget);
            }
        }
        
        if ( defined( 'JOB_MANAGER_VERSION' ) ) :

            $jobs   = get_job_listings( array(
                'search_location'   => $location,
                'search_keywords'   => $keyword,
                'search_categories' => $category,
                'job_types'         => $jobtypes,
                'posts_per_page'    => $number,
                'orderby'           => 'date',
                'order'             => 'DESC',
                'featured'          => $featured,
                'filled'            => $filled,
            ) );
            if ( $jobs->have_posts() ) : ?>

                <?php echo $before_widget; ?>

                <?php if ( $title ) echo $before_title . $title . $after_title; ?>

                <ul class="job_listings">

                    <?php while ( $jobs->have_posts() ) : $jobs->the_post(); ?>

                        <li class="job_listing">
                            <a href="<?php the_job_permalink(); ?>">
                                <div class="position">
                                    <h3><?php the_title(); ?></h3>
                                </div>
                                <ul class="meta">
                                    <li class="location"><?php the_job_location( false ); ?></li>
                                    <li class="company"><?php the_company_name(); ?></li>
                                    <li class="job-type <?php echo get_the_job_type() ? sanitize_title( get_the_job_type()->slug ) : ''; ?>"><?php the_job_type(); ?></li>
                                </ul>
                            </a>
                        </li>

                    <?php endwhile; ?>

                </ul>

                <?php echo $after_widget; ?>

            <?php else : ?>

            <?php endif;
            wp_reset_postdata();

        endif;
        //$content = ob_get_clean();
        //echo $content;
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
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['cssClass'] = strip_tags($new_instance['cssClass']);
        $instance['number'] = strip_tags($new_instance['number']);
        $instance['location'] = strip_tags($new_instance['location']);
        $instance['keyword'] = strip_tags($new_instance['keyword']);
        $instance['category'] = @implode(",",$new_instance['category']);
        $instance['jobtypes'] = @implode(",",$new_instance['jobtypes']);
        $instance['featured'] = isset($new_instance['featured']);
        $instance['filled'] = isset($new_instance['filled']);

        return $instance;
    }

    /**
      * Back-end widget form.
      *
      * @see WP_Widget::form()
      *
      * @param array $instance Previously saved values from database.
      */
    function form( $instance ) {
        $instance = wp_parse_args( (array) $instance, array(
            'title' => '',
            'cssClass' => '',
            'number' => '',
            'location' => '',
            'keyword' => '',
            'category' => '',
            'jobtypes' => '',
            'featured' => '',
            'filled' => ''
        ));
        $title = $instance['title'];
        $cssClass = $instance['cssClass'];
        $number = $instance['number'];
        $location = $instance['location'];
        $keyword = $instance['keyword'];
        $category = @explode(",", $instance['category']);
        $jobtypes = @explode(",", $instance['jobtypes']);
        $featured = $instance['featured'];
        $filled = $instance['filled'];
        
?>
        <?php if ( ! defined( 'JOB_MANAGER_VERSION' ) ) : ?>
        <p>
            <label style='border-left:4px solid #dd3d36;padding:5px;background:#ffe8e8;'><?php _e('WP Job Manager Filter Widget requires WP Job Manager to be installed!', 'wpjmfilter'); ?></label>
        </p>
        <?php endif; ?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'wpjmfilter'); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('cssClass'); ?>"><?php _e('CSS Classes', 'wpjmfilter'); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('cssClass'); ?>" name="<?php echo $this->get_field_name('cssClass'); ?>" type="text" value="<?php echo $cssClass; ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Limit', 'wpjmfilter'); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="number" min="1" value="<?php echo $number; ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('keyword'); ?>"><?php _e('Keyword', 'wpjmfilter'); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('keyword'); ?>" name="<?php echo $this->get_field_name('keyword'); ?>" type="text" value="<?php echo $keyword; ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('location'); ?>"><?php _e('Location', 'wpjmfilter'); ?>:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('location'); ?>" name="<?php echo $this->get_field_name('location'); ?>" type="text" value="<?php echo $location; ?>" />
        </p>

        <p>
            <input id="<?php echo $this->get_field_id('featured'); ?>" name="<?php echo $this->get_field_name('featured'); ?>" type="checkbox" <?php checked(isset($instance['featured']) ? $instance['featured'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('featured'); ?>"><?php _e('Featured?', 'wpjmfilter'); ?></label>
        </p>

        <p>
            <input id="<?php echo $this->get_field_id('filled'); ?>" name="<?php echo $this->get_field_name('filled'); ?>" type="checkbox" <?php checked(isset($instance['filled']) ? $instance['filled'] : 0); ?> />&nbsp;<label for="<?php echo $this->get_field_id('filled'); ?>"><?php _e('Filled?', 'wpjmfilter'); ?></label>
        </p>

        <p>
            <label><?php _e('Job Types', 'wpjmfilter'); ?>:</label>
            <?php if(taxonomy_exists('job_listing_type')) : ?>
                <?php $job_listing_type=get_terms( "job_listing_type", array(
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                        'hide_empty' => false,
                        'fields'     => 'all'
                    ) );?>
                <?php foreach ( $job_listing_type as $type ) : ?>
                    <input type="checkbox" name="<?php echo $this->get_field_name('jobtypes'); ?>[]" id="<?php echo $this->get_field_id('jobtypes').$type->slug; ?>" value="<?php echo $type->slug; ?>" <?php checked( @in_array( $type->slug, $jobtypes ), true ); ?> id="jobtypes_<?php echo $type->slug; ?>" /> 
                    <label for="<?php echo $this->get_field_id('jobtypes').$type->slug; ?>" class="<?php echo sanitize_title( $type->name ); ?>"><?php echo $type->name; ?></label>
                <?php endforeach; ?>
            <?php else : ?>
                <label><?php _e('Not found', 'wpjmfilter'); ?>:</label>
            <?php endif; ?>
        </p>

        <p>
            <label><?php _e('Category', 'wpjmfilter'); ?>:</label>
            <?php if ( ! get_option( 'job_manager_enable_categories' ) ) { ?>
            <?php $job_listing_category=get_terms( "job_listing_category", array(
                'orderby'       => 'name',
                'order'         => 'ASC',
                'hide_empty'    => false,
            ) );?>
            <?php foreach ( $job_listing_category as $cat ) : ?>
                <input type="checkbox" name="<?php echo $this->get_field_name('category'); ?>[]" id="<?php echo $this->get_field_id('category').$cat->slug; ?>" value="<?php echo $cat->slug; ?>" <?php checked( @in_array( $cat->slug, $category ), true ); ?> id="jobtypes_<?php echo $cat->slug; ?>" /> <label for="<?php echo $this->get_field_id('category').$cat->slug; ?>" class="<?php echo sanitize_title( $cat->name ); ?>"><?php echo $cat->name; ?></label>
            <?php endforeach; ?>
            <?php }else{ ?><label><?php _e('Disabled', 'wpjmfilter'); ?>:</label><?php } ?>
        </p>        
        
        <p class="etw-credits">
            <?php _e('Enjoy this plugin? Please <a href="http://amplebrain.com/donate/" target="_blank">donate to support development</a>.', 'wpjmfilter'); ?>
        </p>

<?php
    }
}

/**
 * Register the widget
 */
function wpjm_filter_widget_init() {
        register_widget('WPJM_Filter_Widget');
}
add_action('widgets_init', 'wpjm_filter_widget_init');
