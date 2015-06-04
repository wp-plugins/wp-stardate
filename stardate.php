<?php
/*
Plugin Name: wp-stardate
Plugin URI: https://github.com/doddo/wp-plugin-stardate
Version: 0
License: GPL2
Description: Convert all dates to stardates (where applicable). 
Author: Petter Hassberg
Author URI: https://github.com/doddo
*/

// Security recommendation from wp.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// the options page etc
include( plugin_dir_path( __FILE__ ) . 'src/options.php');

// Actions
// For the permalinks
add_action('init', 'stardate_init');

//filters
stardate_add_filters_actions_hooks();

// Functions
function stardate_add_filters_actions_hooks()
{
    /*
     * Get add filters (and actions)
     * 
     */
    // For the taxonomy
    add_filter('post_link', 'stardate_permalink', 10, 3);
    add_filter('post_type_link', 'stardate_permalink', 10, 3);

    if ( get_option( 'stardate_override_get_date' ) == 1 )
    {
        // Should get_date func. be filtered??
        add_filter('get_the_date', 'get_the_stardate', 10, 3);
    }

    if ( get_option( 'stardate_override_date' ) == 1 )
    {
        // Should dates be filtered??
        add_filter('the_date', 'the_stardate', 10, 3);
    }

    if ( get_option( 'stardate_override_time' ) == 1 )
    {
        // Should the_time  be filtered??
        add_filter('the_time', 'the_stardate', 10, 3);
    }
    
    // On save, associate stardate with post...
    add_action( 'save_post', 'stardate_post', 10, 2 );
    
    // On rewrite update, associate stardate with ALL posts...
    add_action( 'generate_rewrite_rules', 'stardate_all_posts', 10, 0);
    
    // Creates the stardate shortcode 
    add_shortcode('stardate','stardate_shortcode');
    
    // When plugins is activated, it should set some sensible default values!!
    register_activation_hook( __FILE__, 'stardate_activate' );
    
    // When plugin is deactivated, it should clean up after itself!!
    register_deactivation_hook( __FILE__, 'stardate_deactivate' );

}

function stardate_init()
{
    if (!taxonomy_exists('stardate'))
    {
        register_taxonomy( 
            'stardate', 
            'post',
             array(   
       'hierarchical' => FALSE, 
              'label' => __('Stardate'),
             'public' => TRUE,
            'show_ui' => FALSE,
          'query_var' => 'stardate',
              'terms' => 'stardate',
            'rewrite' => TRUE
            )
        );
        register_taxonomy(
                'stardate-parent',
                'post',
                array(
          'hirarchical' => TRUE 
                        
                  )
        );
    }
}

function stardate_permalink($permalink, $post_id, $leavename)
{
    /**
     * Manages the stardate permalinks.
     * 
     * @var string $permalink the permalink
     * @var int $post_id the post_id
     * @var string $leavename some leavename
     */
    $post = get_post($post_id);
    
    if (! $post) return $permalink;
    if (strpos($permalink, '%stardate%') === FALSE) return $permalink;
    
    $terms = wp_get_object_terms($post->ID, 'stardate');
    
    if (!is_wp_error($terms) && !empty($terms) && is_object($terms[0])){
        $taxonomy_slug = $terms[0]->slug;
    } else {
        // Does this post have a date associated with it maybe?
        if ($post->post_date)
        {
            // if so generate the slug like this:
            $taxonomy_slug =  sanitize_title_with_dashes(
                    calculate_stardate(mysql2date('c', $post->post_date)));
        } else {
            // else just set unknown for now...
            $taxonomy_slug = "unknown";            
        }
    }
    
    return str_replace('%stardate%', $taxonomy_slug, $permalink);
    
}

function stardate_shortcode()
{
    /**
     * The stardate shortcode
     */
    return stardate_now();
}

function the_stardate($the_date, $date_format, $post)
{
    /**
     * echo the timestamp in startdate fmt. with optional prefix. (like Stardate)
     * also sets or update the stardate post_term
     *
     * @var string $the_date the date passed on from the filters section.
     * @var string $date_format Date format sent from filters. not used.
     * @var post $post The word press post to read date from
     *
     */
    echo get_the_stardate($the_date, $date_format, $post);
}

function get_the_stardate($the_date, $date_format, $post)
{
    /**
     * Return the timestamp in startdate fmt. with optional prefix. (like Stardate)
     * also sets or update the stardate post_term
     *
     * @var string $the_date the date passed on from the filters section.
     * @var string $date_format Date format sent from filters. not used.
     * @var post $post The word press post to read date from 
     */
    $stardate;
    // Check if there is any stardate to be found for post already...
    $terms = wp_get_post_terms($post->ID, 'stardate');
    
    if (is_wp_error($terms) || empty($terms) || empty($terms[0]->name)) 
    {
        // If not, associate one with the post
        stardate_post($post->ID, $post);
        
        // then try again....
        $terms = wp_get_post_terms($post->ID, 'stardate');
        $stardate = $terms[0]->name;
    } else {
        $stardate = $terms[0]->name;
    }
    
    return $stardate;
}


function stardate_now($style=NULL)
{
    /**
     * Get the current timestamp in Stardate fmt.
     */
    return calculate_stardate(date('c'), $style);
}

function calculate_stardate($date, $style=NULL)
{
    /**
     * Uses logic suggested on trekguide (http://trekguide.com/Stardates.htm) 
     * or wikipedia which does not seem to 100% agree to translate date to stardate
     * 
     * @param date $date date to translate to stardate.
     * @param string $style the style. will get from options unless set.
     * @return string stardate
     */

    $stardate;

    if (empty($style)) $style =  get_option('stardate_style');
    $prefix = get_option('stardate_prefix');
    
    // TODO: add TNG
    
    if ($style == "XI")
    {
        /*  XI Stardates are from the newer movies. (trekguide)
         * dates may be expressed in YYYY.xx format, where YYYY is the actual four-digit year, and .xx represents the fraction
         * of the year to two decimal places (i.e., hundredths of a year). For example, January 1, 1999,
         * would correspond to Stardate 1999.00, while July 2, 1999, would correspond to Stardate 1999.50
         * (half-way through the year 1999)
         */
        $day_of_year = mysql2date("z", $date);
        $fraction_of_year = $day_of_year / 365.2422 * 100;
        $stardate = sprintf("%s.%02s", mysql2date("Y", $date), (int)$fraction_of_year);
        
    } elseif ($style == "XI_wikipedia") {
        /* the first four digits correspond to the year, while the remainder was intended to stand for the day of the year.
         *  Star Trek Into Darkness begins on stardate 2259.55, or February 24, 2259
         */
        $stardate = mysql2date("Y.z", $date);
    } elseif ($style == 'SOL') {
        /* Represent the current date in YYMM.DD format, where "YY" is the current year minus 1900,
         * MM is the current month (01-12), and DD is the current day of the month (01-31).
        */
        $yy = mysql2date("Y", $date);
        $day_of_year = mysql2date("z", $date);
        
        if ($day_of_year > 143.9)
        {
            $day_of_year -= 143.9;
            $yy -= 1922;
        } else {
            $day_of_year +=  221.3422;
            $yy -= 1923;
        }

        $dd =   $day_of_year / 365.2422 * 1000;
        $ff = ($dd - (int)$dd) * 100;
        $stardate = sprintf("%s%03s.%01s", $yy, (int)$dd, (int)$ff);
    } else {
        /* Represent the current date in YYMM.DD format, where "YY" is the current year minus 1900,
         * MM is the current month (01-12), and DD is the current day of the month (01-31).
        */
        $yy = (int)mysql2date('Y', $date) - 1900;
        $mm = mysql2date('m', $date);
        $stardate = sprintf("%s%s.%02s", $yy, $mm, mysql2date("d", $date));
    }

    return implode(' ', array($prefix, $stardate));
}

function stardate_post($post_id, $post)
{
    /**
     * Associates stardate with the post
     * 
     * @param int $post_id The post ID.
     * @param post $post The post object.
     * 
     * @return mixed what ever was result of wp_set_post_terms 
     */
    if ($post->post_date)
    {
        $stardate = calculate_stardate( mysql2date('c', $post->post_date));
        return wp_set_post_terms( $post_id, $stardate, 'stardate', FALSE);
    }    
}

function unstardate_post($post_id, $post=NULL)
{
    /**
     * Unasociate stardate with the post
     * 
     * @param int $post_id The post ID
     * @param post $post The post object
     */
    
    wp_delete_object_term_relationships( $post_id, 'stardate' );
}

function unstardate_all_posts()
{
    /**
     * Unassociates stardates with *ALL* posts
     *
     */

    foreach (get_posts() as $post)
    {
        if ($post->ID)
        {
            unstardate_post($post->ID);
        }
    }
}

function stardate_all_posts()
{
    /**
     * Associates and recalculates stardate with *ALL* posts
     * 
     * Useful when there've been a change in what stardate format to display.
     * Or when first activating this plugin, for the rewrites to work.
     *
     * Returns a array with two arrays in it. First element of nested array are
     * post->ID:s of successful updates. The second one are the ID:s of failed ones.
     */
    $failures = array();
    $successes = array();
    
    foreach (get_posts() as $post)
    {
        if ($post->ID && $post->post_date)
        {
            $r = stardate_post($post->ID, $post);
            if ( is_wp_error( $r ) || empty( $r ) )
            {
                array_push($failures, $post->ID);
            } else {
                array_push($successes, $post->ID);
            }
        }
    }
    
    return array ($successes, $failures);
}

function stardate_activate()
{
    /**
     * Activation hook, set default values for params.
     * 
     */
    add_option('stardate_prefix', 'Stardate');
    add_option('stardate_style', 'Classic');
    add_option('stardate_override_date', 1);
    
}

function stardate_deactivate()
{
    /**
     * Remove all stuff added by this hook from the posts, settings etc
     * 
     */
    
    if ( ! is_admin() )
    {
        return;
    }
    
    delete_option('stardate_prefix');
    delete_option('stardate_style');
    delete_option('stardate_override_date');
    delete_option('stardate_override_get_date');
    delete_option('stardate_override_time');
    
    unstardate_all_posts();
    
    $terms = get_terms( 'stardate', array( 'fields' => 'ids', 'hide_empty' => false ) );
    foreach ( $terms as $value ) {
        wp_delete_term( $value, 'stardate' );
    }

}

?>
