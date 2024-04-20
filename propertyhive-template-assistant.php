<?php
/**
 * Plugin Name: Property Hive Template Assistant Add On
 * Plugin Uri: https://wp-property-hive.com/addons/template-assistant/
 * Description: Add On for Property Hive which assists with the layout of property pages, the fields shown on search forms and allows you to manage additional fields on the property record.
 * Version: 1.0.55
 * Author: PropertyHive
 * Author URI: https://wp-property-hive.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_Template_Assistant' ) ) :

final class PH_Template_Assistant {

    /**
     * @var string
     */
    public $version = '1.0.55';

    /**
     * @var PropertyHive The single instance of the class
     */
    protected static $_instance = null;

    /**
     * @var string
     */
    public $id = '';

    /**
     * @var string
     */
    public $label = '';

    public $search_results = null;
    public $flags = null;
    public $custom_fields = null;
    public $search_forms = null;
    public $text_translation = null;
    
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

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        // Define constants
        $this->define_constants();

        // Include required files
        $this->includes();

        add_action( 'wp_enqueue_scripts', array( $this, 'load_template_assistant_scripts' ) );
        add_action( 'wp_head', array( $this, 'load_template_assistant_styles' ) );

        add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'plugin_add_settings_link' ) );

        add_action( 'admin_notices', array( $this, 'template_assistant_error_notices') );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_template_assistant_admin_scripts' ) );

        add_filter( 'propertyhive_settings_tabs_array', array( $this, 'add_settings_tab' ), 19 );
        add_action( 'propertyhive_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'propertyhive_sections_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'propertyhive_settings_save_' . $this->id, array( $this, 'save' ) );
    }

    private function includes()
    {
        include_once( 'includes/class-ph-template-assistant-search-results.php' );
        include_once( 'includes/class-ph-template-assistant-flags.php' );
        include_once( 'includes/class-ph-template-assistant-custom-fields.php' );
        include_once( 'includes/class-ph-template-assistant-search-forms.php' );
        include_once( 'includes/class-ph-template-assistant-text-translation.php' );

        $this->search_results = new PH_Template_Assistant_Search_Results();
        $this->flags = new PH_Template_Assistant_Flags();
        $this->custom_fields = new PH_Template_Assistant_Custom_Fields();
        $this->search_forms = new PH_Template_Assistant_Search_Forms();
        $this->text_translation = new PH_Template_Assistant_Text_Translation();
    }

    /**
     * Define PH Template Assistant Constants
     */
    private function define_constants() 
    {
        define( 'PH_TEMPLATE_ASSISTANT_PLUGIN_FILE', __FILE__ );
        define( 'PH_TEMPLATE_ASSISTANT_VERSION', $this->version );
    }

    
    public function plugin_add_settings_link( $links )
    {
        $settings_link = '<a href="' . admin_url('admin.php?page=ph-settings&tab=template-assistant') . '">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    /**
     * Output sections
     */
    public function output_sections() {
        global $current_section;

        $sections = array(
            ''         => __( 'Search Results', 'propertyhive' ),
            'flags'         => __( 'Flags', 'propertyhive' ),
            'search-forms'         => __( 'Search Forms', 'propertyhive' ),
            'custom-fields'        => __( 'Additional Fields', 'propertyhive' ),
            'text-translation'         => __( 'Text Substitution', 'propertyhive' ),
        );

        if ( empty( $sections ) )
            return;

        echo '<ul class="subsubsub">';

        $array_keys = array_keys( $sections );

        foreach ( $sections as $id => $label )
            echo '<li><a href="' . admin_url( 'admin.php?page=ph-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';

        echo '</ul><br class="clear" />';
    }

    public function load_template_assistant_scripts()
    {
        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_script( 
            'ph-template-assistant', 
            $assets_path . 'js/propertyhive-template-assistant.js', 
            array('jquery'), 
            PH_TEMPLATE_ASSISTANT_VERSION,
            true
        );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( 
            is_post_type_archive('property') 
            ||
            ( isset($current_settings['search_result_css_all_pages']) && $current_settings['search_result_css_all_pages'] == 'yes' )
        )
        {
            if (
                isset($current_settings['search_result_layout']) &&
                isset($current_settings['search_result_layout']) == 2
            )
            {
                wp_enqueue_script( 'ph-template-assistant' );

                $params = array(
                    'image_ratio' => 2 / 3,
                );
                $params = apply_filters( 'propertyhive_template_assistant_script_params', $params );
                wp_localize_script( 'ph-template-assistant', 'ph_template_assistant', $params );
            }
        }
    }

    public function load_template_assistant_admin_scripts()
    {
        wp_enqueue_script( 'jquery-ui-accordion' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_script( 
            'ph-template-assistant', 
            $assets_path . 'js/admin.js', 
            array('jquery'), 
            PH_TEMPLATE_ASSISTANT_VERSION,
            true
        );
        wp_enqueue_script( 'ph-template-assistant' );

        $params = array(
            'admin_template_assistant_settings_url' => admin_url('admin.php?page=ph-settings&tab=template-assistant'),
        );
        wp_localize_script( 'ph-template-assistant', 'ph_template_assistant', $params );
    }

    public function load_template_assistant_styles()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( 
            is_post_type_archive('property') 
            ||
            ( isset($current_settings['search_result_css_all_pages']) && $current_settings['search_result_css_all_pages'] == 'yes' )
        )
        {
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

    /**
     * Output error message if core Property Hive plugin isn't active
     */
    public function template_assistant_error_notices() 
    {
        global $post;

        if ( !is_plugin_active('propertyhive/propertyhive.php') )
        {
            $message = __( "The Property Hive plugin must be installed and activated before you can use the Property Hive Template Assistant add-on", 'propertyhive' );
            echo "<div class=\"error\"> <p>$message</p></div>";
        }
        else
        {
            if ( version_compare(PH()->version, '1.5.44', '<') )
            {
                $current_settings = get_option( 'propertyhive_template_assistant', array() );

                if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
                {
                    foreach ( $current_settings['custom_fields'] as $custom_field )
                    {
                        // ensure if field is specific to department it's taken into account, else ignored
                        if ( 
                            $custom_field['meta_box'] == 'property_residential_details' 
                            ||
                            $custom_field['meta_box'] == 'property_residential_sales_details' 
                            ||
                            $custom_field['meta_box'] == 'property_residential_lettings_details' 
                            ||
                            $custom_field['meta_box'] == 'property_commercial_details' 
                        )
                        {
                            $message = __( "You have department-specific <a href=\"" . admin_url('admin.php?page=ph-settings&tab=template-assistant&section=custom-fields') . "\">additional fields</a> setup. Please ensure you're running the latest version of Property Hive to support these", 'propertyhive' );
                            echo "<div class=\"error\"> <p>$message</p></div>";
                            break;
                        }
                    }
                }
            }
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
                case "flags": { $settings = $this->flags->get_template_assistant_flags_settings(); break; }
                case "search-forms": { $hide_save_button = true; $settings = $this->search_forms->get_template_assistant_search_forms_settings(); break; }
                case "addsearchform": { $settings = $this->search_forms->get_template_assistant_search_form_settings(); break; }
                case "editsearchform": { $settings = $this->search_forms->get_template_assistant_search_form_settings(); break; }
                case "custom-fields": { $hide_save_button = true; $settings = $this->custom_fields->get_template_assistant_custom_fields_settings(); break; }
                case "addcustomfield": { $settings = $this->custom_fields->get_template_assistant_custom_field_settings(); break; }
                case "editcustomfield": { $settings = $this->custom_fields->get_template_assistant_custom_field_settings(); break; }
                case "text-translation": { $settings = $this->text_translation->get_template_assistant_text_translation_settings(); break; }
                default: { die("Unknown setting section"); }
            }
        }
        else
        {
            $settings = $this->search_results->get_template_assistant_settings(); 
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
                case "flags": 
                {
                    $propertyhive_template_assistant = array(
                        'flags_active' => ( ( isset($_POST['flags_active']) ) ? $_POST['flags_active'] : '' ),
                        'flags_active_single' => ( ( isset($_POST['flags_active_single']) ) ? $_POST['flags_active_single'] : '' ),
                        'flag_position' => $_POST['flag_position'],
                        'flag_bg_color' => $_POST['flag_bg_color'],
                        'flag_text_color' => $_POST['flag_text_color'],
                    );

                    $propertyhive_template_assistant = array_merge($current_settings, $propertyhive_template_assistant);

                    update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
                    break; 
                }
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
                    if ($current_section == 'addsearchform' && trim($current_id) == '' )
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
                                    'show_label' => ( ( isset($_POST['show_label'][$field_id]) && $_POST['show_label'][$field_id] == '1' ) ? true : false ),
                                    'label' => ( isset($_POST['label'][$field_id]) ? stripslashes($_POST['label'][$field_id]) : '' ),
                                );

                                if ( isset($_POST['type'][$field_id]) && $_POST['type'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['type'] = stripslashes($_POST['type'][$field_id]);
                                }
                                if ( isset($_POST['before'][$field_id]) && $_POST['before'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['before'] = stripslashes($_POST['before'][$field_id]);
                                }
                                if ( isset($_POST['after'][$field_id]) && $_POST['after'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['after'] = stripslashes($_POST['after'][$field_id]);
                                }
                                if ( isset($_POST['placeholder'][$field_id]) && $_POST['placeholder'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['placeholder'] = stripslashes($_POST['placeholder'][$field_id]);
                                }
                                if ( isset($_POST['min'][$field_id]) && $_POST['min'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['min'] = stripslashes($_POST['min'][$field_id]);
                                }
                                if ( isset($_POST['max'][$field_id]) && $_POST['max'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['max'] = stripslashes($_POST['max'][$field_id]);
                                }
                                if ( isset($_POST['step'][$field_id]) && $_POST['step'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['step'] = stripslashes($_POST['step'][$field_id]);
                                }
                                if ( isset($_POST['blank_option'][$field_id]) && $_POST['blank_option'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['blank_option'] = stripslashes($_POST['blank_option'][$field_id]);
                                }
                                if ( isset($_POST['parent_terms_only'][$field_id]) && $_POST['parent_terms_only'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['parent_terms_only'] = true;
                                }
                                if ( isset($_POST['dynamic_population'][$field_id]) && $_POST['dynamic_population'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['dynamic_population'] = true;
                                }
                                if ( isset($_POST['hide_empty'][$field_id]) && $_POST['hide_empty'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['hide_empty'] = true;
                                }
                                if ( isset($_POST['multiselect'][$field_id]) && $_POST['multiselect'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['multiselect'] = true;
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
                                    'show_label' => ( ( isset($_POST['show_label'][$field_id]) && $_POST['show_label'][$field_id] == '1' ) ? true : false ),
                                    'label' => ( isset($_POST['label'][$field_id]) ? stripslashes($_POST['label'][$field_id]) : '' ),
                                );

                                if ( isset($_POST['type'][$field_id]) && $_POST['type'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['type'] = stripslashes($_POST['type'][$field_id]);
                                }
                                if ( isset($_POST['before'][$field_id]) && $_POST['before'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['before'] = stripslashes($_POST['before'][$field_id]);
                                }
                                if ( isset($_POST['after'][$field_id]) && $_POST['after'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['after'] = stripslashes($_POST['after'][$field_id]);
                                }
                                if ( isset($_POST['placeholder'][$field_id]) && $_POST['placeholder'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['placeholder'] = stripslashes($_POST['placeholder'][$field_id]);
                                }
                                if ( isset($_POST['blank_option'][$field_id]) && $_POST['blank_option'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['blank_option'] = stripslashes($_POST['blank_option'][$field_id]);
                                }
                                if ( isset($_POST['parent_terms_only'][$field_id]) && $_POST['parent_terms_only'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['parent_terms_only'] = true;
                                }
                                if ( isset($_POST['dynamic_population'][$field_id]) && $_POST['dynamic_population'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['dynamic_population'] = true;
                                }
                                if ( isset($_POST['hide_empty'][$field_id]) && $_POST['hide_empty'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['hide_empty'] = true;
                                }
                                if ( isset($_POST['multiselect'][$field_id]) && $_POST['multiselect'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['multiselect'] = true;
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
                case "addcustomfield": 
                case "editcustomfield": 
                {
                    $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

                    $existing_custom_fields = ( (isset($current_settings['custom_fields'])) ? $current_settings['custom_fields'] : array() );

                    if ( $current_section == 'editcustomfield' && $current_id != 'default' && !isset($existing_custom_fields[$current_id]) )
                    {
                        die("Trying to edit a non-existant custom field. Please go back and try again");
                    }

                    $field_name = trim( ( ( isset($_POST['field_name']) ) ? sanitize_title( $_POST['field_name'] ) : '' ) );

                    if ( $field_name == '' )
                    {
                        $field_name = str_replace("-", "_", sanitize_title( $_POST['field_label'] ) );
                    }

                    $field_name = '_' . ltrim( $field_name, '_' );

                    if ( $current_section == 'addcustomfield' )
                    {
                        $existing_custom_fields[] = array(
                            'field_label' => $_POST['field_label'],
                            'field_name' => $field_name,
                            'field_type' => ( ( isset($_POST['field_type']) && $_POST['field_type'] != '' ) ? $_POST['field_type'] : 'text' ),
                            'dropdown_options' => ( ( isset($_POST['field_type']) && ( $_POST['field_type'] == 'select' || $_POST['field_type'] == 'multiselect' ) && isset($_POST['dropdown_options']) ) ? $_POST['dropdown_options'] : '' ),
                            'meta_box' => $_POST['meta_box'],
                            'display_on_website' => ( ( isset($_POST['display_on_website']) ) ? $_POST['display_on_website'] : '' ),
                            'display_on_applicant_requirements' => ( ( isset($_POST['display_on_applicant_requirements']) ) ? $_POST['display_on_applicant_requirements'] : '' ),
                            'exact_match' => ( ( isset($_POST['exact_match']) ) ? $_POST['exact_match'] : '' ),
                            'display_on_user_details' => ( ( isset($_POST['display_on_user_details']) ) ? $_POST['display_on_user_details'] : '' ),
                            'admin_list' => ( ( isset($_POST['admin_list']) ) ? $_POST['admin_list'] : '' ),
                            'admin_list_sortable' => ( ( isset($_POST['admin_list_sortable']) ) ? $_POST['admin_list_sortable'] : '' ),
                        );
                    }
                    else
                    {
                        $existing_custom_fields[$current_id] = array(
                            'field_label' => $_POST['field_label'],
                            'field_name' => $field_name,
                            'field_type' => ( ( isset($_POST['field_type']) && $_POST['field_type'] != '' ) ? $_POST['field_type'] : 'text' ),
                            'dropdown_options' => ( ( isset($_POST['field_type']) && ( $_POST['field_type'] == 'select' || $_POST['field_type'] == 'multiselect' ) && isset($_POST['dropdown_options']) ) ? $_POST['dropdown_options'] : '' ),
                            'meta_box' => $_POST['meta_box'],
                            'display_on_website' => ( ( isset($_POST['display_on_website']) ) ? $_POST['display_on_website'] : '' ),
                            'display_on_applicant_requirements' => ( ( isset($_POST['display_on_applicant_requirements']) ) ? $_POST['display_on_applicant_requirements'] : '' ),
                            'exact_match' => ( ( isset($_POST['exact_match']) ) ? $_POST['exact_match'] : '' ),
                            'display_on_user_details' => ( ( isset($_POST['display_on_user_details']) ) ? $_POST['display_on_user_details'] : '' ),
                            'admin_list' => ( ( isset($_POST['admin_list']) ) ? $_POST['admin_list'] : '' ),
                            'admin_list_sortable' => ( ( isset($_POST['admin_list_sortable']) ) ? $_POST['admin_list_sortable'] : '' ),
                        );
                    }

                    $current_settings['custom_fields'] = $existing_custom_fields;

                    // see if this custom field in used in search forms and amend the type accordingly
                    if ( $current_section != 'addcustomfield' )
                    {
                        if ( isset($current_settings['search_forms']) && !empty($current_settings['search_forms']) )
                        {
                            foreach ( $current_settings['search_forms'] as $search_form_id => $search_form )
                            {
                                // Active fields
                                if ( isset($search_form['active_fields']) && !empty($search_form['active_fields']) )
                                {
                                    foreach ( $search_form['active_fields'] as $field_id => $field_data )
                                    {
                                        if ( $field_name == $field_id )
                                        {
                                            // we found this field. Set type
                                            $current_settings['search_forms'][$search_form_id]['active_fields'][$field_id]['type'] = ( ( isset($_POST['field_type']) && $_POST['field_type'] != '' ) ? $_POST['field_type'] : 'text' );
                                        }
                                    }
                                }

                                // Inactive fields
                                if ( isset($search_form['inactive_fields']) && !empty($search_form['inactive_fields']) )
                                {
                                    foreach ( $search_form['inactive_fields'] as $field_id => $field_data )
                                    {
                                        if ( $field_name == $field_id )
                                        {
                                            // we found this field. Set type
                                            $current_settings['search_forms'][$search_form_id]['inactive_fields'][$field_id]['type'] = ( ( isset($_POST['field_type']) && $_POST['field_type'] != '' ) ? $_POST['field_type'] : 'text' );
                                        }
                                    }
                                }
                            }
                        }
                    }



                    update_option( 'propertyhive_template_assistant', $current_settings );

                    break; 
                }
                case "text-translation": 
                {
                    $text_translations = array();
                    if ( isset($_POST['search']) && is_array($_POST['search']) && !empty($_POST['search']) && isset($_POST['replace']) && is_array($_POST['replace']) && !empty($_POST['replace']) )
                    {
                        foreach ( $_POST['search'] as $i => $search )
                        {
                            if ( trim($search) != '' && trim($_POST['replace'][$i]) != '' )
                            {
                                $text_translations[] = array(
                                    'search' => $search,
                                    'replace' => $_POST['replace'][$i],
                                );
                            }
                        }
                    }

                    $propertyhive_template_assistant = array(
                        'text_translations' => $text_translations,
                    );

                    $propertyhive_template_assistant = array_merge($current_settings, $propertyhive_template_assistant);

                    update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
                    break; 
                }
                default: { die("Unknown setting section"); }
            }
        }
        else
        {
            $search_results_fields = array();
            if ( isset($_POST['search_result_fields']) && is_array($_POST['search_result_fields']) )
            {
                $search_results_fields = $_POST['search_result_fields'];

                $new_search_results_fields = array();
                foreach ( $search_results_fields as $search_results_field )
                {
                    if ( $search_results_field == 'custom_field' )
                    {  
                        if ( isset($_POST['search_result_fields_custom_field']) && $_POST['search_result_fields_custom_field'] != '' )
                        {
                            $new_search_results_fields[] = $_POST['search_result_fields_custom_field'];
                        }
                    }
                    else
                    {
                        $new_search_results_fields[] = $search_results_field;
                    }
                }

                $search_results_fields = $new_search_results_fields;
            }

            $propertyhive_template_assistant = array(
                'search_result_default_order' => $_POST['search_result_default_order'],
                'search_result_columns' => $_POST['search_result_columns'],
                'search_result_layout' => $_POST['search_result_layout'],
                'search_result_fields' => $search_results_fields,
                'search_result_image_size' => ( isset($_POST['search_result_image_size']) ? $_POST['search_result_image_size'] : 'medium' ),
                'search_result_css' => stripslashes($_POST['search_result_css']),
                'search_result_css_all_pages' => isset($_POST['search_result_css_all_pages']) ? 'yes' : '',
            );

            $propertyhive_template_assistant = array_merge($current_settings, $propertyhive_template_assistant);

            update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
        }
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