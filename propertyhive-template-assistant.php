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
        add_action( 'admin_enqueue_scripts', array( $this, 'load_template_assistant_admin_scripts' ) );

        add_filter( 'propertyhive_settings_tabs_array', array( $this, 'add_settings_tab' ), 19 );
        add_action( 'propertyhive_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'propertyhive_sections_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'propertyhive_settings_save_' . $this->id, array( $this, 'save' ) );

        add_action( 'propertyhive_admin_field_search_forms_table', array( $this, 'search_forms_table' ) );
        add_action( 'propertyhive_admin_field_search_form_fields', array( $this, 'search_form_fields' ) );

        // Set columns
        add_filter( 'loop_search_results_per_page',  array( $this, 'template_assistant_loop_search_results_per_page' ) );
        add_filter( 'loop_search_results_columns', array( $this, 'template_assistant_search_result_columns' ) );
        add_filter( 'post_class', array( $this, 'template_assistant_property_columns_post_class'), 20, 3 );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );
        if ( isset($current_settings['search_forms']) && !empty($current_settings['search_forms']) )
        {
            foreach ( $current_settings['search_forms'] as $id => $form )
            {
                add_filter( 'propertyhive_search_form_fields_' . $id, function($fields)
                {
                    $form_id = str_replace( "propertyhive_search_form_fields_", "", current_filter() );

                    $current_settings = get_option( 'propertyhive_template_assistant', array() );

                    $new_fields = ( 
                        ( 
                            isset($current_settings['search_forms'][$form_id]['active_fields'])
                            &&
                            !empty($current_settings['search_forms'][$form_id]['active_fields'])
                        ) ? 
                        $current_settings['search_forms'][$form_id]['active_fields'] : 
                        $fields 
                    );
                    
                    // Remove any fields that are in the $fields array but not active in active_fields, excluding hidden fields
                    $hidden_fields = array();
                    foreach ( $fields as $field_id => $field )
                    {
                        if ( !isset($new_fields[$field_id]) && $field['type'] != 'hidden' )
                        {
                            unset($fields[$field_id]);
                        }

                        if ( isset($field['type']) && $field['type'] == 'hidden' && !isset($new_fields[$field_id]) )
                        {
                            $new_fields[$field_id] = $field;
                        }
                    }

                    // Merge the new with existing (if existing exists)
                    foreach ( $new_fields as $field_id => $new_field )
                    {
                        $fields[$field_id] = array_merge( ( isset($fields[$field_id]) ? $fields[$field_id] : array() ), $new_field );
                    }

                    // Set order
                    $new_ordered_fields = array();
                    foreach ( $new_fields as $field_id => $new_field )
                    {
                        $new_ordered_fields[$field_id] = $fields[$field_id];
                    }
                    $fields = $new_ordered_fields;

                    return $fields;
                } , 99, 1 );
            }
        }
    }

    /**
     * Output sections
     */
    public function output_sections() {
        global $current_section;

        $sections = array(
            ''         => __( 'Search Results', 'propertyhive' ),
            'search-forms'         => __( 'Search Forms', 'propertyhive' ),
        );

        if ( empty( $sections ) )
            return;

        echo '<ul class="subsubsub">';

        $array_keys = array_keys( $sections );

        foreach ( $sections as $id => $label )
            echo '<li><a href="' . admin_url( 'admin.php?page=ph-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';

        echo '</ul><br class="clear" />';
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

    public function load_template_assistant_admin_scripts()
    {
        wp_enqueue_script( 'jquery-ui-accordion' );
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

        if ( $current_section ) 
        {
            switch ($current_section)
            {
                case "search-forms": { $hide_save_button = true; $settings = $this->get_template_assistant_search_forms_settings(); break; }
                case "addsearchform": { $settings = $this->get_template_assistant_search_form_settings(); break; }
                case "editsearchform": { $settings = $this->get_template_assistant_search_form_settings(); break; }
                default: { die("Unknown setting section"); }
            }
        }
        else
        {
            $settings = $this->get_template_assistant_settings(); 
        }
        
        propertyhive_admin_fields( $settings );
    }

    /**
     * Uses the Property Hive options API to save settings.
     *
     * @uses propertyhive_update_options()
     * @uses self::get_settings()
     */
    public function save() {

        global $current_section;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( $current_section ) 
        {
            switch ($current_section)
            {
                case "search-forms": 
                {
                    // Nothing to do
                    break; 
                }
                case "addsearchform": 
                case "editsearchform": 
                {
                    $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

                    $existing_search_forms = ( (isset($current_settings['search_forms'])) ? $current_settings['search_forms'] : array() );

                    if ( $current_section == 'editsearchform' && $current_id != 'default' && !isset($existing_search_forms[$current_id]) )
                    {
                        die("Trying to edit a non-existant search form. Please go back and try again");
                    }

                    if ( isset($existing_search_forms[$current_id]) )
                    {
                        unset($existing_search_forms[$current_id]);
                    }

                    $current_id = ( ( isset($_POST['form_id']) && $_POST['form_id'] != '' ) ? str_replace("-", "_", sanitize_title($_POST['form_id'])) : $current_id );
                    if ($current_section == 'addsearchform' && trim($current_id) != '' )
                    {
                        $current_id = 'custom';
                    }

                    $active_fields = array();
                    $inactive_fields = array();

                    if ( isset($_POST['active_fields_order']) && $_POST['active_fields_order'] != '' )
                    {
                        $field_ids = explode("|", $_POST['active_fields_order']);
                        if ( !empty($field_ids) )
                        {
                            foreach ( $field_ids as $field_id )
                            {
                                $active_fields[$field_id] = array(
                                    'type' => ( isset($_POST['type'][$field_id]) ? stripslashes($_POST['type'][$field_id]) : '' ),
                                    'show_label' => ( ( isset($_POST['show_label'][$field_id]) && $_POST['show_label'][$field_id] == '1' ) ? true : false ),
                                    'label' => ( isset($_POST['label'][$field_id]) ? stripslashes($_POST['label'][$field_id]) : '' ),
                                );

                                if ( isset($_POST['before'][$field_id]) && $_POST['before'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['before'] = stripslashes($_POST['before'][$field_id]);
                                }
                                if ( isset($_POST['after'][$field_id]) && $_POST['after'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['after'] = stripslashes($_POST['after'][$field_id]);
                                }

                                if ( isset($_POST['option_keys'][$field_id]) && is_array($_POST['option_keys'][$field_id]) && !empty($_POST['option_keys'][$field_id]) )
                                {
                                    $options = array();
                                    foreach ( $_POST['option_keys'][$field_id] as  $i => $key )
                                    {
                                        $options[$key] = $_POST['options_values'][$field_id][$i];
                                    }
                                    $active_fields[$field_id]['options'] = $options;
                                }
                            }
                        }
                    }

                    if ( isset($_POST['inactive_fields_order']) && $_POST['inactive_fields_order'] != '' )
                    {
                        $field_ids = explode("|", $_POST['inactive_fields_order']);
                        if ( !empty($field_ids) )
                        {
                            foreach ( $field_ids as $field_id )
                            {
                                $inactive_fields[$field_id] = array(
                                    'type' => ( isset($_POST['type'][$field_id]) ? stripslashes($_POST['type'][$field_id]) : '' ),
                                    'show_label' => ( ( isset($_POST['show_label'][$field_id]) && $_POST['show_label'][$field_id] == '1' ) ? true : false ),
                                    'label' => ( isset($_POST['label'][$field_id]) ? stripslashes($_POST['label'][$field_id]) : '' ),
                                );

                                if ( isset($_POST['before'][$field_id]) && $_POST['before'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['before'] = stripslashes($_POST['before'][$field_id]);
                                }
                                if ( isset($_POST['after'][$field_id]) && $_POST['after'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['after'] = stripslashes($_POST['after'][$field_id]);
                                }

                                if ( isset($_POST['option_keys'][$field_id]) && is_array($_POST['option_keys'][$field_id]) && !empty($_POST['option_keys'][$field_id]) )
                                {
                                    $options = array();
                                    foreach ( $_POST['option_keys'][$field_id] as  $i => $key )
                                    {
                                        $options[$key] = $_POST['options_values'][$field_id][$i];
                                    }
                                    $inactive_fields[$field_id]['options'] = $options;
                                }
                            }
                        }
                    }

                    $existing_search_forms[$current_id] = array(
                        'active_fields' => $active_fields,
                        'inactive_fields' => $inactive_fields,
                    );

                    $current_settings['search_forms'] = $existing_search_forms;

                    update_option( 'propertyhive_template_assistant', $current_settings );

                    break; 
                }
                default: { die("Unknown setting section"); }
            }
        }
        else
        {
            $propertyhive_template_assistant = array(
                'search_result_columns' => $_POST['search_result_columns'],
                'search_result_layout' => $_POST['search_result_layout'],
                'search_result_css' => trim($_POST['search_result_css']),
            );

            $propertyhive_template_assistant = array_merge($current_settings, $propertyhive_template_assistant);

            update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
        }
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

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_search_results_settings');

        return $settings;
    }

    public function get_template_assistant_search_forms_settings()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Search Forms', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_search_forms_settings' )

        );

        $settings[] = array(
            'type' => 'search_forms_table',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_search_forms_settings');

        return $settings;
    }

    public function get_template_assistant_search_form_settings()
    {
        global $current_section;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( !isset($current_settings['search_forms']) || isset($current_settings['search_forms']) && empty($current_settings['search_forms']) )
        {
            $current_settings['search_forms'] = array(
                'default' => array()
            );
        }

        $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

        $search_form_details = array();

        if ($current_id != '')
        {
            $search_forms = $current_settings['search_forms'];

            if (isset($search_forms[$current_id]))
            {
                $search_form_details = $search_forms[$current_id];
            }
            else
            {
                die('Trying to edit a search form which does not exist. Please go back and try again.');
            }
        }

        $settings = array(

            array( 'title' => __( ( $current_section == 'addsearchform' ? 'Add Search Form' : 'Edit Search Form' ), 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'searchforms' ),

        );

        $custom_attributes = array();
        if ($current_id == 'default' || $current_section == 'editsearchform')
        {
            $custom_attributes['disabled'] = 'disabled';
        }

        $settings[] = array(
            'title' => __( 'ID', 'propertyhive' ),
            'id'        => 'form_id',
            'default'   => ( (isset($current_id)) ? $current_id : ''),
            'type'      => 'text',
            'desc_tip'  =>  false,
            'custom_attributes' => $custom_attributes
        );

        $settings[] = array(
            'type' => 'search_form_fields',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'searchforms');

        return $settings;
    }

    /**
     * Output list of search forms
     *
     * @access public
     * @return void
     */
    public function search_forms_table() {
        global $wpdb, $post;
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=addsearchform' ); ?>" class="button alignright"><?php echo __( 'Add New Search Form', 'propertyhive' ); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Search Forms', 'propertyhive' ) ?></th>
            <td class="forminp">
                <table class="ph_portals widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="id"><?php _e( 'ID', 'propertyhive' ); ?></th>
                            <th class="shortcode"><?php _e( 'Shortcode', 'propertyhive' ); ?></th>
                            <th class="settings">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            $current_settings = get_option( 'propertyhive_template_assistant', array() );
                            $search_forms = array();
                            if ($current_settings !== FALSE)
                            {
                                if (isset($current_settings['search_forms']))
                                {
                                    $search_forms = $current_settings['search_forms'];
                                }
                            }

                            if ( !isset($search_forms['default']) )
                            {
                                $search_forms['default'] = array();
                            }

                            if (!empty($search_forms))
                            {
                                foreach ($search_forms as $id => $search_form)
                                {
                                    echo '<tr>';
                                        echo '<td class="id">' . $id . '</td>';
                                        echo '<td class="shortcode"><pre style="background:#EEE; padding:5px; display:inline">[property_search_form id="' . $id . '"]</pre></td>';
                                        echo '<td class="settings">
                                            <a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=editsearchform&id=' . $id ) . '">' . __( 'Edit', 'propertyhive' ) . '</a>
                                        </td>';
                                    echo '</tr>';
                                }
                            }
                            else
                            {
                                echo '<tr>';
                                    echo '<td align="center" colspan="3">' . __( 'No search forms exist', 'propertyhive' ) . '</td>';
                                echo '</tr>';
                            }
                        ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=addsearchform' ); ?>" class="button alignright"><?php echo __( 'Add New Search Form', 'propertyhive' ); ?></a>
            </td>
        </tr>
        <?php
    }

    private function output_search_form_field( $id, $field )
    {
        echo '
        <div class="group" id="' . $id . '">
            <h3>' . $id . '</h3>
            <div>

                <input type="hidden" name="type[' . $id . ']" id="type_'.$id.'" value="' . ( ( isset($field['type']) ) ? $field['type'] : '' ) . '">

                <p><label for="show_label_'.$id.'">Show Label:</label> <input type="checkbox" name="show_label[' . $id . ']" id="show_label_'.$id.'" value="1"' . ( ( isset($field['show_label']) && $field['show_label'] === true ) ? ' checked' : '' ) . '></p>
                
                <p><label for="label_'.$id.'">Label:</label> <input type="text" name="label[' . $id . ']" id="label_'.$id.'" value="' . ( ( isset($field['label']) ) ? $field['label'] : '' ) . '"></p>
                
                <p><label for="before_'.$id.'">Before:</label> <input type="text" name="before[' . $id . ']" id="before_'.$id.'" value="' . ( ( isset($field['before']) ) ? htmlentities($field['before']) : '' ) . '"></p>
                
                <p><label for="after_'.$id.'">After:</label> <input type="text" name="after[' . $id . ']" id="after_'.$id.'" value="' . ( ( isset($field['after']) ) ? htmlentities($field['after']) : '' ) . '"></p>';

        if ( isset($field['options']) && !taxonomy_exists($id) )
        {
            echo '<p><label for="">Options: ';
            if ( $id != 'department' )
            {
                echo '<a href="" class="add-search-form-field-option" id="add_search_form_field_option_' . $id . '">Add Option</a>';
            }
            echo '</label><br>';

            echo '<span class="form-field-options" id="sortable_options_' . $id . '">';
            $i = 0;
            foreach ( $field['options'] as $key => $value )
            {
                echo '<span style="display:block">';
                echo '<input type="text" name="option_keys[' . $id . '][]" value="' . $key . '">';
                echo '<input type="text" name="options_values[' . $id . '][]" value="' . $value . '">';
                echo '</span>';

                ++$i;
            }
            echo '</span>';

            echo '</p>';
        }

        echo '</div>
        </div>';
    }

    /**
     * Output list of search form active/inactive fields
     *
     * @access public
     * @return void
     */
    public function search_form_fields() {
        global $wpdb, $post;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( !isset($current_settings['search_forms']) || isset($current_settings['search_forms']) && empty($current_settings['search_forms']) )
        {
            $current_settings['search_forms'] = array(
                'default' => array()
            );
        }

        $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

        $search_form_details = array();

        if ($current_id != '')
        {
            $search_forms = $current_settings['search_forms'];

            if (isset($search_forms[$current_id]))
            {
                $search_form_details = $search_forms[$current_id];
            }
            else
            {
                die('Trying to edit search form which does not exist. Please go back and try again.');
            }
        }

        $all_fields = ph_get_search_form_fields();
        $all_fields['address_keyword'] = array(
            'type' => 'text',
            'label' => __( 'Location', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-address_keyword">'
        );
        if ( class_exists('PH_Radial_Search') )
        {
            $all_fields['radius'] = array(
                'type' => 'select',
                'label' => __( 'Radius', 'propertyhive' ),
                'show_label' => true,
                'before' => '<div class="control control-radius">',
                'options' => array(
                    '' => 'This Area Only',
                    '1' => 'Within 1 Mile',
                    '2' => 'Within 2 Miles',
                    '3' => 'Within 3 Miles',
                    '5' => 'Within 5 Miles',
                    '10' => 'Within 10 Miles'
                )
            );
        }
        $all_fields['location'] = array(
            'type' => 'location',
            'label' => __( 'Location', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-location">'
        );
        $all_fields['parking'] = array(
            'type' => 'parking',
            'label' => __( 'Parking', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-parking">'
        );
        $all_fields['outside_space'] = array(
            'type' => 'outside_space',
            'label' => __( 'Outside Space', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-outside_space">'
        );
        $all_fields['availability'] = array(
            'type' => 'availability',
            'label' => __( 'Status', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-availability">'
        );
        $all_fields['marketing_flag'] = array(
            'type' => 'marketing_flag',
            'label' => __( 'Marketing Flag', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-marketing_flag">'
        );
        $all_fields['tenure'] = array(
            'type' => 'tenure',
            'label' => __( 'Tenure', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-tenure">'
        );
        $all_fields['sale_by'] = array(
            'type' => 'sale_by',
            'label' => __( 'Sale By', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-sale_by">'
        );
        $all_fields['furnished'] = array(
            'type' => 'furnished',
            'label' => __( 'Furnished', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-furnished">'
        );

        $form_controls = ph_get_search_form_fields();
        $active_fields = apply_filters( 'propertyhive_search_form_fields_' . $current_id, $form_controls );

        $inactive_fields = array();
        foreach ( $all_fields as $id => $field )
        {
            if ( !isset($active_fields[$id]) )
            {
                if ( isset($search_form_details['inactive_fields'][$id]) && !empty($search_form_details['inactive_fields'][$id]) )
                {
                    $field = array_merge($field, $search_form_details['inactive_fields'][$id]);
                }
                $inactive_fields[$id] = $field;
            }
        }
?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Active Fields', 'propertyhive' ) ?></th>
            <td class="forminp">
                <div id="sortable1" class="connectedSortable" style="min-height:30px;">
                <?php
                    foreach ( $active_fields as $id => $field )
                    {
                        if ( isset( $field['type'] ) && $field['type'] == 'hidden' ) { continue; }

                        $this->output_search_form_field( $id, $field );
                    }
                ?>
                </div>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Inactive Fields', 'propertyhive' ) ?></th>
            <td class="forminp">
                <div id="sortable2" class="connectedSortable" style="min-height:30px;">
                <?php
                    foreach ( $inactive_fields as $id => $field )
                    {
                        if ( isset( $field['type'] ) && $field['type'] == 'hidden' ) { continue; }

                        $this->output_search_form_field( $id, $field );
                    }
                ?>
                </div>
            </td>
        </tr>

        <input type="hidden" name="active_fields_order" id="active_fields_order" value="<?php
            $field_ids = array();
            foreach ( $active_fields as $id => $field )
            {
                $field_ids[] = $id;
            }
            echo implode("|", $field_ids);
        ?>">
        <input type="hidden" name="inactive_fields_order" id="inactive_fields_order" value="<?php
            $field_ids = array();
            foreach ( $inactive_fields as $id => $field )
            {
                $field_ids[] = $id;
            }
            echo implode("|", $field_ids);
        ?>">

        <script>
            jQuery(document).ready(function($)
            {
                $( "#sortable1" )
                .accordion({
                    collapsible: true,
                    active: false,
                    header: "> div > h3",
                    heightStyle: "content"
                })
                .sortable({
                    axis: "y",
                    handle: "h3",
                    connectWith: ".connectedSortable",
                    stop: function( event, ui ) 
                    {
                        // IE doesn't register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        $( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable('toArray');
                        
                        $('#active_fields_order').val( fields_order.join("|") );
                    }
                });

                $( "#sortable2" )
                .accordion({
                    collapsible: true,
                    active: false,
                    header: "> div > h3",
                    heightStyle: "content"
                })
                .sortable({
                    axis: "y",
                    handle: "h3",
                    connectWith: ".connectedSortable",
                    stop: function( event, ui ) 
                    {
                        // IE doesn't register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        $( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable('toArray');
                        
                        $('#inactive_fields_order').val( fields_order.join("|") );
                    }
                });

                // Handle add/remove options
                $('body').on('click', '.add-search-form-field-option', function(e)
                {
                    e.preventDefault();

                    var this_id = $(this).attr('id').replace("add_search_form_field_option_", "");

                    var clone = $('#sortable_options_' + this_id).children('span').eq(0).clone();
                    clone.find('input').val('');

                    clone.appendTo( $('#sortable_options_' + this_id) );

                    add_remove_option_links();
                });

                $('body').on('click', '.remove-search-form-field-option', function(e)
                {
                    e.preventDefault();
                    
                    $(this).parent().remove();

                    add_remove_option_links();
                });

                add_remove_option_links();
            });

            function add_remove_option_links()
            {
                jQuery('.connectedSortable .group a.remove-search-form-field-option').remove();

                jQuery('.connectedSortable .group').each(function()
                {
                    if ( jQuery(this).find('.add-search-form-field-option').length > 0 )
                    {   
                        console.log(jQuery(this).find('.form-field-options span').length);
                        if ( jQuery(this).find('.form-field-options span').length > 1 )
                        {
                            jQuery(this).find('.form-field-options span').append(' <a href="" class="remove-search-form-field-option">X</a>');
                        }
                    }
                });
            }
        </script>
<?php
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