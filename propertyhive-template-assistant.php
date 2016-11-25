<?php
/**
 * Plugin Name: Property Hive Template Assistant Add On
 * Plugin Uri: http://wp-property-hive.com/addons/template-assistat/
 * Description: Add On for Property Hive which assists with the layout of property pages
 * Version: 1.0.0
 * Author: PropertyHive
 * Author URI: http://wp-property-hive.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_Template_Assistant' ) ) :

final class PH_Template_Assistant {

    /**
     * @var string
     */
    public $version = '1.0.0';

    /**
     * @var PropertyHive The single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main Property Hive Template Assistant Instance
     *
     * Ensures only one instance of Property Hive Template Assistant is loaded or can be loaded.
     *
     * @static
     * @return Property Hive Template Assistant - Main instance
     */
    public static function instance() 
    {
        if ( is_null( self::$_instance ) ) 
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {

        $this->id    = 'template-assistant';
        $this->label = __( 'Template Assistant', 'propertyhive' );

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        add_action( 'wp_enqueue_scripts', array( $this, 'load_template_assistant_scripts' ) );
        add_action( 'wp_head', array( $this, 'load_template_assistant_styles' ) );

        add_action( 'admin_notices', array( $this, 'template_assistant_error_notices') );

        add_filter( 'propertyhive_settings_tabs_array', array( $this, 'add_settings_tab' ), 19 );
        add_action( 'propertyhive_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'propertyhive_settings_save_' . $this->id, array( $this, 'save' ) );

        // Set columns
        add_filter( 'loop_search_results_per_page',  array( $this, 'template_assistant_loop_search_results_per_page' ) );
        add_filter( 'loop_search_results_columns', array( $this, 'template_assistant_search_result_columns' ) );
        add_filter( 'post_class', array( $this, 'template_assistant_property_columns_post_class'), 20, 3 );

        // Set search results template
        //add_filter( 'ph_get_template_part', array( $this, 'template_assistant_search_result_template' ), 1, 3 );

        //$this->search_results_layout_actions();
    }

    private function includes()
    {
        //include_once( 'includes/class-ph-template-assistant-install.php' );
    }

    /**
     * Define PH Template Assistant Constants
     */
    private function define_constants() 
    {
        define( 'PH_TEMPLATE_ASSISTANT_PLUGIN_FILE', __FILE__ );
        define( 'PH_TEMPLATE_ASSISTANT_VERSION', $this->version );
    }

    public function load_template_assistant_scripts()
    {
        if ( is_post_type_archive('property') )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if (
                isset($current_settings['search_result_layout']) &&
                isset($current_settings['search_result_layout']) == 2
            )
            {
                $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

                wp_register_script( 
                    'ph-template-assistant', 
                    $assets_path . 'js/propertyhive-template-assistant.js', 
                    array('jquery'), 
                    PH_TEMPLATE_ASSISTANT_VERSION,
                    true
                );

                wp_enqueue_script( 'ph-template-assistant' );
            }
        }
    }

    public function load_template_assistant_styles()
    {
        if ( is_post_type_archive('property') )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );
            if ( isset($current_settings['search_result_css']) )
            {
                echo '<style type="text/css">
                ' . $current_settings['search_result_css'] . '
                </style>';
            }

            /*wp_enqueue_style( 'propertyhive_template_assistant_columns_css', str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/css/columns.css' );

            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( 
                isset($current_settings['search_result_layout']) && 
                file_exists(str_replace( array( 'http:', 'https:' ), '', dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . '/assets/css/content-property-' . $current_settings['search_result_layout'] . '.css') )
            )
            {
                wp_enqueue_style( 'propertyhive_template_assistant_search_result_layout_css', str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/css/content-property-' . $current_settings['search_result_layout'] . '.css' );
            }*/
        }
    }

    public function template_assistant_loop_search_results_per_page( $cols )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['search_result_columns']) && in_array($current_settings['search_result_columns'], array(3,4)) )
        {
            return 12;
        }

        return $cols;
    }

    private function search_results_layout_actions()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['search_result_layout']) )
        {
            switch ( $current_settings['search_result_layout'] )
            {
                // Normal layout
                case "1":
                {

                    break;
                }
                // Card layout 1 (thumbnail above details)
                case "2":
                {

                    break;
                }
            }
        }
    }

    public function template_assistant_search_result_columns( $cols = 1 )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['search_result_columns']) && in_array($current_settings['search_result_columns'], array(1,2,3,4)) )
        {
            return $current_settings['search_result_columns'];
        }

        return 1;
    }

    public function template_assistant_property_columns_post_class( $classes, $class = '', $post_id = '' ) 
    {
        if ( ! $post_id || get_post_type( $post_id ) !== 'property' )
            return $classes;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['search_result_columns']) && in_array($current_settings['search_result_columns'], array(2,3,4)) )
        {
            $property = get_property( $post_id );

            if ( $property ) 
            {
                $classes[] = 'ph-cols-' . $current_settings['search_result_columns'];

                if ( ($key = array_search('clear', $classes)) !== false ) 
                {
                    unset($classes[$key]);
                }
            }
        }

        return $classes;
    }

    public function template_assistant_search_result_template( $template, $slug, $name )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( 
            isset($current_settings['search_result_layout']) && 
            $current_settings['search_result_layout'] != '' &&
            $slug == 'content' &&
            $name == 'property'&&
            file_exists(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE . '/templates/content-property/' . $current_settings['search_result_layout'] . '.php')
        )
        {
            $template = dirname( PH_TEMPLATE_ASSISTANT_PLUGIN_FILE ) . '/templates/content-property/' . $current_settings['search_result_layout'] . '.php';
        }

        return $template;
    }

    /**
     * Output error message if core Property Hive plugin isn't active
     */
    public function template_assistant_error_notices() 
    {
        global $post;

        if (!is_plugin_active('propertyhive/propertyhive.php'))
        {
            $message = __( "The Property Hive plugin must be installed and activated before you can use the Property Hive Template Assistant add-on", 'propertyhive' );
            echo "<div class=\"error\"> <p>$message</p></div>";
        }
    }

    /**
     * Add a new settings tab to the Property Hive settings tabs array.
     *
     * @param array $settings_tabs Array of Property Hive setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of Property Hive setting tabs & their labels, including the Subscription tab.
     */
    public function add_settings_tab( $settings_tabs ) {
        $settings_tabs['template-assistant'] = __( 'Template Assistant', 'propertyhive' );
        return $settings_tabs;
    }

    /**
     * Uses the Property Hive admin fields API to output settings.
     *
     * @uses propertyhive_admin_fields()
     * @uses self::get_settings()
     */
    public function output() {

        global $current_section, $hide_save_button;
        
        propertyhive_admin_fields( self::get_template_assistant_settings() );
    }

    /**
     * Uses the Property Hive options API to save settings.
     *
     * @uses propertyhive_update_options()
     * @uses self::get_settings()
     */
    public function save() {

        $show = array();

        $propertyhive_template_assistant = array(
            'search_result_columns' => $_POST['search_result_columns'],
            'search_result_layout' => $_POST['search_result_layout'],
            'search_result_css' => $_POST['search_result_css'],
            //'show' => $_POST['search_result_columns'],
        );

        update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
    }

    /**
     * Get template assistant settings
     *
     * @return array Array of settings
     */
    public function get_template_assistant_settings() {

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Search Results Page Layout', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_search_results_settings' )

        );

        $settings[] = array(
            'title' => __( 'Properties Per Row', 'propertyhive' ),
            'id'        => 'search_result_columns',
            'type'      => 'select',
            'default'   => ( isset($current_settings['search_result_columns']) ? $current_settings['search_result_columns'] : '1'),
            'options'   => array(
                '1' => '1 (' . __( 'default', 'propertyhive') . ')',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            )
        );

        $settings[] = array(
            'title' => __( 'Result Layout', 'propertyhive' ),
            'id'        => 'search_result_layout',
            'type'      => 'select',
            'default'   => ( isset($current_settings['search_result_layout']) ? $current_settings['search_result_layout'] : '1'),
            'options'   => array(
                '1' => 'List Layout 1 (default)',
                '2' => 'List Layout 2 (card)',
            )
        );

        $columns_1_css = file_get_contents(dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . '/assets/css/columns-1.css');
        $columns_2_css = file_get_contents(dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . '/assets/css/columns-2.css');
        $columns_3_css = file_get_contents(dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . '/assets/css/columns-3.css');
        $columns_4_css = file_get_contents(dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . '/assets/css/columns-4.css');
        $layout_1_css = '';
        $layout_2_css = file_get_contents(dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . '/assets/css/content-property-2.css');

        $settings[] = array(
            'title' => __( 'Customise CSS', 'propertyhive' ),
            'id'        => 'search_result_css',
            'type'      => 'textarea',
            'default'   => ( isset($current_settings['search_result_css']) ? $current_settings['search_result_css'] : $columns_1_css . "\n\n" . $layout_1_css ),
            'css'       => 'height:200px;width:100%;',
        );

        if ( isset($current_settings['search_result_css']) && trim($current_settings['search_result_css']) != '' )
        {
            $settings[] = array(
                'type'      => 'html',
                'html'      => '<div id="change_warning" style="display:none; color:#900">
                    By changing the options above the CSS been regenerated. Please note that this will overwrite any customisations you\'ve previously made to the CSS.
                </div>'
            );
        }

        $settings[] = array(
            'type'      => 'html',
            'html'      => '<script>

                jQuery(document).ready(function()
                {
                    jQuery(\'#search_result_columns\').change(function()
                    {
                        generate_search_results_css();
                    });
                    jQuery(\'#search_result_layout\').change(function()
                    {
                        generate_search_results_css();
                    });
                });

                function generate_search_results_css()
                {
                    jQuery(\'#search_result_css\').val(\'\');

                    jQuery(\'#change_warning\').slideDown();

                    var columns_css = \'\';
                    var layout_css = \'\';
                    switch ( jQuery(\'#search_result_columns\').val() )
                    {
                        case \'1\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_1_css) . '";
                            break;
                        }
                        case \'2\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_2_css) . '";
                            break;
                        }
                        case \'3\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_3_css) . '";
                            break;
                        }
                        case \'4\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_4_css) . '";
                            break;
                        }
                    }

                    switch ( jQuery(\'#search_result_layout\').val() )
                    {
                        case \'1\':
                        {
                            layout_css = "' . str_replace(array("\r\n", "\n"), '\n', $layout_1_css) . '";
                            break;
                        }
                        case \'2\':
                        {
                            layout_css = "' . str_replace(array("\r\n", "\n"), '\n', $layout_2_css) . '";
                            break;
                        }
                    }

                    jQuery(\'#search_result_css\').val( columns_css + "\n\n" + layout_css );
                }

            </script>'
        );

        /*$settings[] = array(
            'title'   => __( 'Show', 'propertyhive' ),
            'desc'    => __( 'Thumbnail Image', 'propertyhive' ),
            'id'      => 'show_search_fields_thumbnail',
            'type'    => 'checkbox',
            'default' => ( (isset($current_settings['show_search_fields']) && !in_array('thumbnail', $current_settings['show_search_fields'])) ?  : 'yes' ),
            'checkboxgroup' => 'start'
        );
        
        $settings[] = array(
            'title'   => __( 'Show', 'propertyhive' ),
            'desc'    => __( 'Address', 'propertyhive' ),
            'id'      => 'show_search_fields_address',
            'type'    => 'checkbox',
            'default' => ( (isset($current_settings['show_search_fields']) && !in_array('address', $current_settings['show_search_fields'])) ?  : 'yes' ),
            'checkboxgroup' => 'middle'
        );

        $settings[] = array(
            'title'   => __( 'Show', 'propertyhive' ),
            'desc'    => __( 'Price', 'propertyhive' ),
            'id'      => 'show_search_fields_price',
            'type'    => 'checkbox',
            'default' => ( (isset($current_settings['show_search_fields']) && !in_array('price', $current_settings['show_search_fields'])) ?  : 'yes' ),
            'checkboxgroup' => 'middle'
        );

        $settings[] = array(
            'title'   => __( 'Show', 'propertyhive' ),
            'desc'    => __( 'Bedrooms and Property Type', 'propertyhive' ),
            'id'      => 'show_search_fields_bedrooms_type',
            'type'    => 'checkbox',
            'default' => ( (isset($current_settings['show_search_fields']) && !in_array('bedrooms_type', $current_settings['show_search_fields'])) ?  : 'yes' ),
            'checkboxgroup' => 'middle'
        );

        $settings[] = array(
            'title'   => __( 'Show', 'propertyhive' ),
            'desc'    => __( 'Summary Description', 'propertyhive' ),
            'id'      => 'show_search_fields_summary',
            'type'    => 'checkbox',
            'default' => ( (isset($current_settings['show_search_fields']) && !in_array('summary', $current_settings['show_search_fields'])) ?  : 'yes' ),
            'checkboxgroup' => 'middle'
        );

        $settings[] = array(
            'title'   => __( 'Show', 'propertyhive' ),
            'desc'    => __( 'Action Buttons', 'propertyhive' ),
            'id'      => 'show_search_fields_actions',
            'type'    => 'checkbox',
            'default' => ( (isset($current_settings['show_search_fields']) && !in_array('actions', $current_settings['show_search_fields'])) ?  : 'yes' ),
            'checkboxgroup' => 'end'
        );*/

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_search_results_settings');

        /*$settings[] = array( 'title' => __( 'Property Details Page Layout', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_full_details_settings' );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_full_details_settings');*/

        return $settings;
    }
}

endif;

/**
 * Returns the main instance of PH_Template_Assistant to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return PH_Template_Assistant
 */
function PHTA() {
    return PH_Template_Assistant::instance();
}

PHTA();

if( is_admin() && file_exists(  dirname( __FILE__ ) . '/propertyhive-template-assistant-update.php' ) )
{
    include_once( dirname( __FILE__ ) . '/propertyhive-template-assistant-update.php' );
}