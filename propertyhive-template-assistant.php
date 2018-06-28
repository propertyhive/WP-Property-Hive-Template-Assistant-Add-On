<?php
/**
 * Plugin Name: Property Hive Template Assistant Add On
 * Plugin Uri: http://wp-property-hive.com/addons/template-assistant/
 * Description: Add On for Property Hive which assists with the layout of property pages, the fields shown on search forms and allows you to manage custom fields on the property record.
 * Version: 1.0.17
 * Author: PropertyHive
 * Author URI: http://wp-property-hive.com
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_Template_Assistant' ) ) :

final class PH_Template_Assistant {

    /**
     * @var string
     */
    public $version = '1.0.17';

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

        add_action( 'admin_init', array( $this, 'check_for_reset_search_form') );
        add_action( 'admin_init', array( $this, 'check_for_delete_search_form') );
        add_action( 'admin_init', array( $this, 'check_for_delete_custom_field') );

        add_action( 'admin_notices', array( $this, 'template_assistant_error_notices') );
        add_action( 'admin_enqueue_scripts', array( $this, 'load_template_assistant_admin_scripts' ) );

        add_filter( 'propertyhive_settings_tabs_array', array( $this, 'add_settings_tab' ), 19 );
        add_action( 'propertyhive_settings_' . $this->id, array( $this, 'output' ) );
        add_action( 'propertyhive_sections_' . $this->id, array( $this, 'output_sections' ) );
        add_action( 'propertyhive_settings_save_' . $this->id, array( $this, 'save' ) );

        add_action( 'propertyhive_admin_field_search_forms_table', array( $this, 'search_forms_table' ) );
        add_action( 'propertyhive_admin_field_search_form_fields', array( $this, 'search_form_fields' ) );
        add_action( 'propertyhive_admin_field_custom_field_dropdown_options', array( $this, 'custom_field_dropdown_options' ) );

        add_action( 'propertyhive_admin_field_custom_fields_table', array( $this, 'custom_fields_table' ) );

        add_action( 'propertyhive_update_options_general', array( $this, 'reflect_updated_departments_in_search_forms' ) );

        // Set columns
        add_filter( 'loop_search_results_per_page',  array( $this, 'template_assistant_loop_search_results_per_page' ) );
        add_filter( 'loop_search_results_columns', array( $this, 'template_assistant_search_result_columns' ) );
        add_filter( 'post_class', array( $this, 'template_assistant_property_columns_post_class'), 20, 3 );

        add_action( 'propertyhive_property_meta_list_end', array( $this, 'display_custom_fields_on_website' ) );

        add_filter( 'propertyhive_property_query_meta_query', array( $this, 'custom_fields_in_meta_query' ) );

        add_filter( 'manage_edit-property_columns', array( $this, 'custom_fields_in_property_admin_list_edit' ) );
        add_action( 'manage_property_posts_custom_column', array( $this, 'custom_fields_in_property_admin_list' ), 2 );
        add_filter( 'manage_edit-property_sortable_columns', array( $this, 'custom_fields_in_property_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_property_admin_list_orderby' ) );

        add_filter( 'manage_edit-contact_columns', array( $this, 'custom_fields_in_contact_admin_list_edit' ) );
        add_action( 'manage_contact_posts_custom_column', array( $this, 'custom_fields_in_contact_admin_list' ), 2 );
        add_filter( 'manage_edit-contact_sortable_columns', array( $this, 'custom_fields_in_contact_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_contact_admin_list_orderby' ) );
        

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

                    // Check if any of the fields at this point are setup as custom fields
                    $custom_fields = ( ( isset($current_settings['custom_fields']) ) ? $current_settings['custom_fields'] : array() );

                    foreach ( $fields as $field_id => $field )
                    {
                        foreach ( $custom_fields as $custom_field )
                        {
                            if ( $custom_field['field_name'] == $field_id && ( $custom_field['field_type'] == 'select' || $custom_field['field_type'] == 'multiselect' ) && isset($custom_field['dropdown_options']) && is_array($custom_field['dropdown_options']) )
                            {
                                $options = array('' => ( (isset($field['blank_option'])) ? $field['blank_option'] : '' ) );

                                foreach ( $custom_field['dropdown_options'] as $dropdown_option )
                                {
                                    $options[$dropdown_option] = $dropdown_option;
                                }

                                $fields[$field_id]['options'] = $options;

                                if ( $custom_field['field_type'] == 'multiselect' ) { $fields[$field_id]['type'] = 'select'; }
                            }
                        }
                    }

                    return $fields;
                } , 99, 1 );
            }
        }

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            $meta_boxes_done = array();
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( !in_array( $custom_field['meta_box'], $meta_boxes_done ) )
                {
                    add_filter( 'propertyhive_' . $custom_field['meta_box'] . '_fields', function()
                    {
                        global $thepostid;

                        $meta_box_being_done = str_replace( "propertyhive_", "", current_filter() );
                        $meta_box_being_done = str_replace( "_fields", "", $meta_box_being_done );

                        $current_settings = get_option( 'propertyhive_template_assistant', array() );

                        foreach ( $current_settings['custom_fields'] as $custom_field )
                        {
                            if ( $custom_field['meta_box'] == $meta_box_being_done )
                            {
                                if ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'select' )
                                {
                                    $options = array('' => '');
                                    if ( isset($custom_field['dropdown_options']) && is_array($custom_field['dropdown_options']) && !empty($custom_field['dropdown_options']) )
                                    {
                                        foreach ( $custom_field['dropdown_options'] as $dropdown_option )
                                        {
                                            $options[$dropdown_option] = $dropdown_option;
                                        }
                                    }
                                    propertyhive_wp_select( array( 
                                        'id' => $custom_field['field_name'], 
                                        'label' => $custom_field['field_label'], 
                                        'desc_tip' => false,
                                        'options' => $options
                                    ) );
                                }
                                elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'multiselect' )
                                {
?>
<p class="form-field <?php echo $custom_field['field_name']; ?>_field"><label for="<?php echo $custom_field['field_name']; ?>"><?php _e( $custom_field['field_label'], 'propertyhive' ); ?></label>
        <select id="<?php echo $custom_field['field_name']; ?>" name="<?php echo $custom_field['field_name']; ?>[]" multiple="multiple" data-placeholder="<?php _e( 'Select ' . $custom_field['field_label'], 'propertyhive' ); ?>" class="multiselect attribute_values">
            <?php
                $selected_values = get_post_meta( $thepostid, $custom_field['field_name'], true );
                if ( $selected_values == '' )
                {
                    $selected_values = array();
                }
                
                if ( isset($custom_field['dropdown_options']) && is_array($custom_field['dropdown_options']) && !empty($custom_field['dropdown_options']) )
                {
                    foreach ( $custom_field['dropdown_options'] as $dropdown_option )
                    {
                        echo '<option value="' . esc_attr( $dropdown_option ) . '"';
                        if ( in_array( $dropdown_option, $selected_values ) )
                        {
                            echo ' selected';
                        }
                        echo '>' . esc_html( $dropdown_option ) . '</option>';
                    }
                }
            ?>
        </select>
<?php
                                }
                                elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'textarea' )
                                {
                                    propertyhive_wp_textarea_input( array( 
                                        'id' => $custom_field['field_name'], 
                                        'label' => $custom_field['field_label'], 
                                        'desc_tip' => false,
                                        'type' => 'text'
                                    ) );
                                }
                                elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'date' )
                                {
                                    propertyhive_wp_text_input( array( 
                                        'id' => $custom_field['field_name'], 
                                        'label' => $custom_field['field_label'], 
                                        'desc_tip' => false,
                                        'type' => 'text',
                                        'class' => 'short date-picker',
                                        'placeholder' => 'YYYY-MM-DD',
                                        'custom_attributes' => array(
                                            'maxlength' => 10,
                                            'pattern' => "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"
                                        )
                                    ) );
                                }
                                else
                                {
                                    propertyhive_wp_text_input( array( 
                                        'id' => $custom_field['field_name'], 
                                        'label' => $custom_field['field_label'], 
                                        'desc_tip' => false,
                                        'type' => 'text'
                                    ) );
                                }
                            }
                        }
                    });

                    add_action( 'propertyhive_save_' . $custom_field['meta_box'],  function( $post_id )
                    {
                        $meta_box_being_done = str_replace( "propertyhive_save_", "", current_filter() );

                        $current_settings = get_option( 'propertyhive_template_assistant', array() );

                        foreach ( $current_settings['custom_fields'] as $custom_field )
                        {
                            if ( $custom_field['meta_box'] == $meta_box_being_done )
                            {
                                update_post_meta( $post_id, $custom_field['field_name'], (isset($_POST[$custom_field['field_name']]) ? $_POST[$custom_field['field_name']] : '') );
                            }
                        }
                    });

                    $meta_boxes_done[] = $custom_field['meta_box'];
                }
            }
        }
    }

    public function custom_fields_in_property_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_property_admin_list( $column )
    {
        global $post, $propertyhive, $the_property;

        if ( empty( $the_property ) || $the_property->ID != $post->ID ) 
        {
            $the_property = new PH_Property( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_property->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_property->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo implode(", ", $values);
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_property->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_property->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_property_admin_list_sort( $columns ) 
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_property_admin_list_orderby( $vars ) 
    {
        if ( isset( $vars['orderby'] ) ) 
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] ) {
                            $vars = array_merge( $vars, array(
                                'meta_key'  => $custom_field['field_name'],
                                'orderby'   => 'meta_value'
                            ) );
                        }
                    }
                }
            }
        }

        return $vars;
    }

    public function custom_fields_in_contact_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'contact_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_contact_admin_list( $column )
    {
        global $post, $propertyhive, $the_contact;

        if ( empty( $the_contact ) || $the_contact->ID != $post->ID ) 
        {
            $the_contact = new PH_Contact( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'contact_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_contact->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_contact->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo implode(", ", $values);
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_contact->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_contact->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_contact_admin_list_sort( $columns ) 
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'contact_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_contact_admin_list_orderby( $vars ) 
    {
        if ( isset( $vars['orderby'] ) ) 
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'contact_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] ) {
                            $vars = array_merge( $vars, array(
                                'meta_key'  => $custom_field['field_name'],
                                'orderby'   => 'meta_value'
                            ) );
                        }
                    }
                }
            }
        }

        return $vars;
    }

    public function reflect_updated_departments_in_search_forms()
    {
        $sales_active = get_option( 'propertyhive_active_departments_sales', '' );
        $lettings_active = get_option( 'propertyhive_active_departments_lettings', '' );
        $commercial_active = get_option( 'propertyhive_active_departments_commercial', '' );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['search_forms']) && !empty($current_settings['search_forms']) )
        {
            foreach ( $current_settings['search_forms'] as $id => $form )
            {
                if ( isset($form['active_fields']) && isset($form['active_fields']['department']) && isset($form['active_fields']['department']['options']) )
                {
                    // We have a department field in this form. Check options match current department settings

                    if ( $sales_active != 'yes' && isset($form['active_fields']['department']['options']['residential-sales']) )
                    {
                        unset($form['active_fields']['department']['options']['residential-sales']);
                    }
                    if ( $lettings_active != 'yes' && isset($form['active_fields']['department']['options']['residential-lettings']) )
                    {
                        unset($form['active_fields']['department']['options']['residential-lettings']);
                    }
                    if ( $commercial_active != 'yes' && isset($form['active_fields']['department']['options']['commercial']) )
                    {
                        unset($form['active_fields']['department']['options']['commercial']);
                    }

                    if ( $sales_active == 'yes' && !isset($form['active_fields']['department']['options']['residential-sales']) )
                    {
                        $form['active_fields']['department']['options']['residential-sales'] = 'Residential Sales';
                    }
                    if ( $lettings_active == 'yes' && !isset($form['active_fields']['department']['options']['residential-lettings']) )
                    {
                        $form['active_fields']['department']['options']['residential-lettings'] = 'Residential Lettings';
                    }
                    if ( $commercial_active == 'yes' && !isset($form['active_fields']['department']['options']['commercial']) )
                    {
                        $form['active_fields']['department']['options']['commercial'] = 'Commercial';
                    }

                    $current_settings['search_forms'][$id]['active_fields'] = $form['active_fields'];
                }
            }
        }

        update_option( 'propertyhive_template_assistant', $current_settings );
    }

    public function custom_fields_in_meta_query( $meta_query )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( 
                    isset( $_REQUEST[$custom_field['field_name']] ) && $_REQUEST[$custom_field['field_name']] != '' 
                )
                {
                    if ( $custom_field['field_type'] == 'select' )
                    {
                        $meta_query[] = array(
                            'key'     => $custom_field['field_name'],
                            'value'   => sanitize_text_field( $_REQUEST[$custom_field['field_name']] ),
                            'compare' => '=',
                        );
                    }
                    else
                    {
                        $meta_query[] = array(
                            'key'     => $custom_field['field_name'],
                            'value'   => sanitize_text_field( $_REQUEST[$custom_field['field_name']] ),
                            'compare' => 'LIKE',
                        );
                    }
                }
            }
        }

        return $meta_query;
    }

    public function display_custom_fields_on_website()
    {
        global $property;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $custom_fields = ( (isset($current_settings['custom_fields'])) ? $current_settings['custom_fields'] : array() );

        foreach ( $custom_fields as $custom_field )
        {
            if ( isset($custom_field['display_on_website']) && $custom_field['display_on_website'] == '1' )
            {
                if ( $custom_field['field_type'] == 'multiselect' )
                {
                    $values = get_post_meta( $property->id, $custom_field['field_name'], TRUE );
                    if ( !empty($values) )
                    {
                        echo '<li class="' . trim($custom_field['field_name'], '_') . '">' . $custom_field['field_label'] . ': ';
                        echo implode(", ", $values);
                    }
                }
                elseif ( $custom_field['field_type'] == 'date' )
                {
                    if ( $property->{$custom_field['field_name']} != '' ) { ?><li class="<?php echo trim($custom_field['field_name'], '_'); ?>"><?php echo $custom_field['field_label']; echo ': ' . date(get_option( 'date_format' ), strtotime($property->{$custom_field['field_name']})); ?></li><?php }
                }
                else
                {
                    if ( $property->{$custom_field['field_name']} != '' ) { ?><li class="<?php echo trim($custom_field['field_name'], '_'); ?>"><?php echo $custom_field['field_label']; echo ': ' . $property->{$custom_field['field_name']}; ?></li><?php }
                }
            }
        }
    }

    public function check_for_reset_search_form()
    {
        if ( isset($_GET['action']) && $_GET['action'] == 'resetsearchform' && isset($_GET['id']) && $_GET['id'] != '' )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            $current_id = ( !isset( $_GET['id'] ) ) ? '' : sanitize_title( $_GET['id'] );

            $existing_search_forms = ( (isset($current_settings['search_forms'])) ? $current_settings['search_forms'] : array() );

            if ( !isset($existing_search_forms[$current_id]) )
            {
                die("Trying to reset a non-existant search form. Please go back and try again");
            }

            if ( isset($existing_search_forms[$current_id]) )
            {
                $existing_search_forms[$current_id] = array();
            }

            $current_settings['search_forms'] = $existing_search_forms;

            update_option( 'propertyhive_template_assistant', $current_settings );
        }
    }

    public function check_for_delete_search_form()
    {
        if ( isset($_GET['action']) && $_GET['action'] == 'deletesearchform' && isset($_GET['id']) && $_GET['id'] != '' && $_GET['id'] != 'default' )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            $current_id = ( !isset( $_GET['id'] ) ) ? '' : sanitize_title( $_GET['id'] );

            $existing_search_forms = ( (isset($current_settings['search_forms'])) ? $current_settings['search_forms'] : array() );

            if ( !isset($existing_search_forms[$current_id]) )
            {
                die("Trying to delete a non-existant search form. Please go back and try again");
            }

            if ( isset($existing_search_forms[$current_id]) )
            {
                unset($existing_search_forms[$current_id]);
            }

            $current_settings['search_forms'] = $existing_search_forms;

            update_option( 'propertyhive_template_assistant', $current_settings );
        }
    }

    public function check_for_delete_custom_field()
    {
        if ( isset($_GET['action']) && $_GET['action'] == 'deletecustomfield' && isset($_GET['id']) && $_GET['id'] != '' )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            $current_id = ( !isset( $_GET['id'] ) ) ? '' : sanitize_title( $_GET['id'] );

            $existing_custom_fields = ( (isset($current_settings['custom_fields'])) ? $current_settings['custom_fields'] : array() );

            if ( !isset($existing_custom_fields[$current_id]) )
            {
                die("Trying to delete a non-existant custom field. Please go back and try again");
            }

            if ( isset($existing_custom_fields[$current_id]) )
            {
                unset($existing_custom_fields[$current_id]);
            }

            $current_settings['custom_fields'] = $existing_custom_fields;

            update_option( 'propertyhive_template_assistant', $current_settings );
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
            'custom-fields'        => __( 'Custom Fields', 'propertyhive' ),
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
        $assets_path = str_replace( array( 'http:', 'https:' ), '', untrailingslashit( plugins_url( '/', __FILE__ ) ) ) . '/assets/';

        wp_register_script( 
            'ph-template-assistant', 
            $assets_path . 'js/propertyhive-template-assistant.js', 
            array('jquery'), 
            PH_TEMPLATE_ASSISTANT_VERSION,
            true
        );

        if ( is_post_type_archive('property') )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if (
                isset($current_settings['search_result_layout']) &&
                isset($current_settings['search_result_layout']) == 2
            )
            {
                wp_enqueue_script( 'ph-template-assistant' );
            }
        }
    }

    public function load_template_assistant_admin_scripts()
    {
        wp_enqueue_script( 'jquery-ui-accordion' );
        wp_enqueue_script( 'jquery-ui-sortable' );
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
                case "custom-fields": { $hide_save_button = true; $settings = $this->get_template_assistant_custom_fields_settings(); break; }
                case "addcustomfield": { $settings = $this->get_template_assistant_custom_field_settings(); break; }
                case "editcustomfield": { $settings = $this->get_template_assistant_custom_field_settings(); break; }
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
                                if ( isset($_POST['blank_option'][$field_id]) && $_POST['blank_option'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['blank_option'] = stripslashes($_POST['blank_option'][$field_id]);
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

                    $field_name = trim( ( ( isset($_POST['field_name']) ) ? $_POST['field_name'] : '' ) );

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
                            'admin_list' => ( ( isset($_POST['admin_list']) ) ? $_POST['admin_list'] : '' ),
                            'admin_list_sortable' => ( ( isset($_POST['admin_list_sortable']) ) ? $_POST['admin_list_sortable'] : '' ),
                        );
                    }

                    $current_settings['custom_fields'] = $existing_custom_fields;

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

        if ( !isset($current_settings['search_forms']) )
        {
            $current_settings['search_forms'] = array();
        }
        if ( !isset($current_settings['search_forms']['default']) )
        {
            $current_settings['search_forms']['default'] = array();
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
                                            <a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=editsearchform&id=' . $id ) . '">' . __( 'Edit Fields', 'propertyhive' ) . '</a>
                                            <a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=search-forms&action=resetsearchform&id=' . $id ) . '">' . __( 'Reset To Default Fields', 'propertyhive' ) . '</a>
                                            ' .  ( ( $id != 'default' ) ? '<a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=search-forms&action=deletesearchform&id=' . $id ) . '">' . __( 'Delete', 'propertyhive' ) . '</a>' : '' ) . '
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
            <h3>' . trim( $id, '_' ) . '</h3>
            <div>';
        if ( $id == 'department' )
        {
            echo '<p><label for="type_'.$id.'">Type:</label> <select name="type[' . $id . ']" id="type_'.$id.'">
                <option value="radio"' . ( ( !isset($field['type']) || ( isset($field['type']) && $field['type'] == 'radio' ) ) ? ' selected' : '' ) . '>Radio Buttons</option>
                <option value="select"' . ( ( isset($field['type']) && $field['type'] == 'select' ) ? ' selected' : '' ) . '>Dropdown</option>
                ' . ( ( isset($field['type']) && $field['type'] != 'select' && $field['type'] != 'radio' ) ? '<option value="' . $field['type'] . '" selected>' . $field['type'] . '</option>' : '' ) . '
            </select></p>';
        }
        else
        {
            echo '<input type="hidden" name="type[' . $id . ']" id="type_'.$id.'" value="' . ( ( isset($field['type']) ) ? $field['type'] : '' ) . '">';
        }

        echo  ' <p><label for="show_label_'.$id.'">Show Label:</label> <input type="checkbox" name="show_label[' . $id . ']" id="show_label_'.$id.'" value="1"' . ( ( isset($field['show_label']) && $field['show_label'] === true ) ? ' checked' : '' ) . '></p>
                
                <p><label for="label_'.$id.'">Label:</label> <input type="text" name="label[' . $id . ']" id="label_'.$id.'" value="' . ( ( isset($field['label']) ) ? $field['label'] : '' ) . '"></p>
                
                <p><label for="before_'.$id.'">Before:</label> <input type="text" name="before[' . $id . ']" id="before_'.$id.'" value="' . ( ( isset($field['before']) ) ? htmlentities($field['before']) : '' ) . '"></p>
                
                <p><label for="after_'.$id.'">After:</label> <input type="text" name="after[' . $id . ']" id="after_'.$id.'" value="' . ( ( isset($field['after']) ) ? htmlentities($field['after']) : '' ) . '"></p>';

        if ( isset($field['type']) && in_array($field['type'], array('text', 'email', 'date', 'number', 'password')) )
        {
            echo '
            <p><label for="placeholder_'.$id.'">Placeholder:</label> <input type="text" name="placeholder[' . $id . ']" id="after_'.$id.'" value="' . ( ( isset($field['placeholder']) ) ? htmlentities($field['placeholder']) : '' ) . '"></p>
            ';
        }

        if ( taxonomy_exists($id) || ( isset($field['custom_field']) && $field['custom_field'] === true && $field['type'] == 'select' ) )
        {
            echo '
            <p><label for="blank_option_'.$id.'">Blank Option:</label> <input type="text" name="blank_option[' . $id . ']" id="blank_option_'.$id.'" value="' . ( ( isset($field['blank_option']) ) ? htmlentities($field['blank_option']) : __( 'No Preference', 'propertyhive' ) ) . '"></p>
            ';
        }

        if ( isset($field['options']) && !taxonomy_exists($id) && ( !isset($field['custom_field']) || ( isset($field['custom_field']) && $field['custom_field'] === false ) ) )
        {
            echo '<p><label for="">Options: ';

            echo '<a href="" class="add-search-form-field-option" id="add_search_form_field_option_' . $id . '">Add Option</a>';

            echo '</label><br>';

            echo '<span class="form-field-options" id="sortable_options_' . $id . '">';
            $i = 0;
            foreach ( $field['options'] as $key => $value )
            {
                echo '<span style="display:block"><i class="fa fa-reorder" style="cursor:pointer; opacity:0.3"></i> ';
                echo '<input type="text" name="option_keys[' . $id . '][]" value="' . $key . '">';
                echo '<input type="text" name="options_values[' . $id . '][]" value="' . $value . '">';
                echo '</span>';

                ++$i;
            }
            echo '</span>';

            echo '</p>';
?>
<script>
            jQuery(document).ready(function($)
            {
                $( "#sortable_options_<?php echo $id; ?>" )
                .sortable({
                    axis: "y",
                    handle: "i",
                    stop: function( event, ui ) 
                    {
                        // IE doesn't register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        //ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        //$( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable('toArray');
                        
                        //$('#active_fields_order').val( fields_order.join("|") );
                    }
                });
            });
        </script>
<?php
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

        if ( !isset($current_settings['search_forms']) )
        {
            $current_settings['search_forms'] = array();
        }
        if ( !isset($current_settings['search_forms']['default']) )
        {
            $current_settings['search_forms']['default'] = array();
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
            'before' => '<div class="control control-parking residential-only">'
        );
        $all_fields['outside_space'] = array(
            'type' => 'outside_space',
            'label' => __( 'Outside Space', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-outside_space residential-only">'
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
            'before' => '<div class="control control-furnished lettings-only">'
        );

        $bedrooms = array(
            '' => __( 'No preference', 'propertyhive' ),
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
        );

        $all_fields['bedrooms'] = array(
            'type' => 'select',
            'label' => __( 'Bedrooms', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-bedrooms residential-only">',
            'options' => $bedrooms
        );

        $bathrooms = array(
            '' => __( 'No preference', 'propertyhive' ),
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
        );

        $all_fields['maximum_bedrooms'] = array(
            'type' => 'select',
            'label' => __( 'Max Beds', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-maximum_bedrooms residential-only">',
            'options' => $bathrooms
        );
        $all_fields['minimum_bathrooms'] = array(
            'type' => 'select',
            'label' => __( 'Min Bathrooms', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-minimum_bathrooms residential-only">',
            'options' => $bathrooms
        );
        $all_fields['maximum_bathrooms'] = array(
            'type' => 'select',
            'label' => __( 'Max Bathrooms', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-maximum_bathrooms residential-only">',
            'options' => $bathrooms
        );
        $all_fields['available_date_from'] = array(
            'type' => 'date',
            'label' => __( 'Available From', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-available_date_from lettings-only">'
        );

        $all_fields['office'] = array(
            'type' => 'office',
            'label' => __( 'Office', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-office">'
        );

        $form_controls = ph_get_search_form_fields();
        $active_fields = apply_filters( 'propertyhive_search_form_fields_' . $current_id, $form_controls );

        // Add any custom fields
        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $id => $custom_field )
            {
                $all_fields[$custom_field['field_name']] = array(
                    'type' => ( ( isset($custom_field['field_type']) && ( $custom_field['field_type'] == 'select' || $custom_field['field_type'] == 'multiselect' ) ) ? 'select' : 'text' ),
                    'label' => $custom_field['field_label'],
                    'show_label' => true,
                    'before' => '<div class="control control-' . trim( $custom_field['field_name'], '_' ) . '">',
                    'custom_field' => true,
                );

                if ( isset($active_fields[$custom_field['field_name']]) )
                {
                    $active_fields[$custom_field['field_name']]['custom_field'] = true;
                }
            }
        }

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

    public function get_template_assistant_custom_fields_settings()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Custom Fields', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_custom_fields_settings' )

        );

        $settings[] = array(
            'type' => 'custom_fields_table',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_custom_fields_settings');

        return $settings;
    }

    /**
     * Output list of search forms
     *
     * @access public
     * @return void
     */
    public function custom_fields_table() {
        global $wpdb, $post;
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=addcustomfield' ); ?>" class="button alignright"><?php echo __( 'Add New Custom Field', 'propertyhive' ); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Custom Fields', 'propertyhive' ) ?></th>
            <td class="forminp">
                <table class="ph_portals widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="field-label"><?php _e( 'Field Name', 'propertyhive' ); ?></th>
                            <th class="section"><?php _e( 'Section', 'propertyhive' ); ?></th>
                            <th class="usage"><?php _e( 'Usage', 'propertyhive' ); ?></th>
                            <th class="website"><?php _e( 'Display On Website', 'propertyhive' ); ?></th>
                            <th class="admin-list"><?php _e( 'Show In Admin List', 'propertyhive' ); ?></th>
                            <th class="admin-list-sorting"><?php _e( 'Sortable In Admin List', 'propertyhive' ); ?></th>
                            <th class="settings">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            $current_settings = get_option( 'propertyhive_template_assistant', array() );
                            $custom_fields = array();
                            if ($current_settings !== FALSE)
                            {
                                if (isset($current_settings['custom_fields']))
                                {
                                    $custom_fields = $current_settings['custom_fields'];
                                }
                            }

                            if (!empty($custom_fields))
                            {
                                foreach ($custom_fields as $id => $custom_field)
                                {
                                    echo '<tr>';
                                        echo '<td class="field-label">' . $custom_field['field_label'] . '</td>';
                                        echo '<td class="section">' . ucwords( str_replace("_", " ", $custom_field['meta_box']) ) . '</td>';
                                        echo '<td class="usage">';
                                        if ( substr( $custom_field['meta_box'], 0, 8 ) == 'property' ) { echo '<pre style="background:#EEE; padding:5px; display:inline">&lt;?php $property->' . ltrim( $custom_field['field_name'], '_' ) . '; ?&gt;</pre>'; }else{ echo '-';}
                                        echo '</td>';
                                        echo '<td class="website">';
                                        if ( substr( $custom_field['meta_box'], 0, 8 ) == 'property' ) { echo ( ( isset($custom_field['display_on_website']) && $custom_field['display_on_website'] == '1' ) ? 'Yes' : 'No' ); }else{ echo '-';}
                                        echo '</td>';
                                        echo '<td class="admin-list">';
                                        echo ( ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' ) ? 'Yes' : 'No' );
                                        echo '</td>';
                                        echo '<td class="sorting">';
                                        if ( ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' ) )
                                        {
                                            echo ( ( isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' ) ? 'Yes' : 'No' );
                                        }
                                        else
                                        {
                                            echo '-';
                                        }
                                        echo '</td>';
                                        echo '<td class="settings">
                                            <a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=editcustomfield&id=' . $id ) . '">' . __( 'Edit Field', 'propertyhive' ) . '</a>
                                            <a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=custom-fields&action=deletecustomfield&id=' . $id ) . '">' . __( 'Delete', 'propertyhive' ) . '</a>
                                        </td>';
                                    echo '</tr>';
                                }
                            }
                            else
                            {
                                echo '<tr>';
                                    echo '<td align="center" colspan="4">' . __( 'No custom fields exist', 'propertyhive' ) . '</td>';
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
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=addcustomfield' ); ?>" class="button alignright"><?php echo __( 'Add New Custom Field', 'propertyhive' ); ?></a>
            </td>
        </tr>
        <?php
    }

    public function get_template_assistant_custom_field_settings()
    {
        global $current_section;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( !isset($current_settings['custom_fields']) )
        {
            $current_settings['custom_fields'] = array();
        }

        $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

        $custom_field_details = array();

        if ($current_id != '')
        {
            $custom_fields = $current_settings['custom_fields'];

            if (isset($custom_fields[$current_id]))
            {
                $custom_field_details = $custom_fields[$current_id];
            }
            else
            {
                die('Trying to edit a custom field which does not exist. Please go back and try again.');
            }
        }

        $settings = array(

            array( 'title' => __( ( $current_section == 'addcustomfield' ? 'Add Custom Field' : 'Edit Custom Field' ), 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'customfield' ),

        );

        $settings[] = array(
            'title' => __( 'Field Label', 'propertyhive' ),
            'id'        => 'field_label',
            'default'   => ( (isset($custom_field_details['field_label'])) ? $custom_field_details['field_label'] : ''),
            'type'      => 'text',
            'desc_tip'  =>  false,
            'custom_attributes' => array(
                'placeholder' => 'My New Field'
            )
        );

        if ( isset($custom_field_details['field_name']) )
        {
            $settings[] = array(
                'title' => __( 'Field Name', 'propertyhive' ),
                'id'        => 'field_name',
                'default'   => ( (isset($custom_field_details['field_name'])) ? $custom_field_details['field_name'] : ''),
                'type'      => 'text',
                'desc'  => __( 'Please note that changing this after properties have been saved will result in any data entered being lost', 'propertyhive' ),
            );
        }

        $settings[] = array(
            'title' => __( 'Field Type', 'propertyhive' ),
            'id'        => 'field_type',
            'default'   => ( (isset($custom_field_details['field_type'])) ? $custom_field_details['field_type'] : 'text'),
            'type'      => 'select',
            'desc_tip'  =>  false,
            'options'   => array(
                'text' => 'Text',
                'textarea' => 'Textarea',
                'select' => 'Dropdown',
                'multiselect' => 'Multi-Select',
                'date' => 'Date',
            )
        );

        $settings[] = array(
            'title' => __( 'Dropdown Options', 'propertyhive' ),
            'id'        => 'dropdown_options',
            'type'      => 'custom_field_dropdown_options',
        );

        $settings[] = array(
            'title' => __( 'Section', 'propertyhive' ),
            'id'        => 'meta_box',
            'default'   => ( (isset($custom_field_details['meta_box'])) ? $custom_field_details['meta_box'] : ''),
            'type'      => 'select',
            'desc'  =>  __( 'Please select which meta box on the property record this field should appear in', 'propertyhive' ),
            'options' => array(
                'property_address' => 'Property Address',
                'property_department' => 'Property Department',
                'property_residential_details' => 'Property Residential Details',
                'property_residential_sales_details' => 'Property Residential Sales Details',
                'property_residential_lettings_details' => 'Property Residential Lettings Details',
                'property_commercial_details' => 'Property Commercial Details',
                'contact_correspondence_address' => 'Contact Correspondence Address',
                'contact_contact_details' => 'Contact Contact Details',
            )
        );

        $settings[] = array(
                'title' => __( 'Display On Website', 'propertyhive' ),
                'id'        => 'display_on_website',
                'default'   => ( (isset($custom_field_details['display_on_website']) && $custom_field_details['display_on_website'] == '1') ? 'yes' : ''),
                'type'      => 'checkbox',
                'desc_tip'  =>  true,
                'desc'      => __( 'Only applicable to property related custom fields', 'propertyhive' )
            );

        $settings[] = array(
                'title' => __( 'Show In Admin List', 'propertyhive' ),
                'id'        => 'admin_list',
                'default'   => ( (isset($custom_field_details['admin_list']) && $custom_field_details['admin_list'] == '1') ? 'yes' : ''),
                'type'      => 'checkbox',
                'desc_tip'  =>  true,
                'desc'      => ''
            );

        $settings[] = array(
                'title' => __( 'Sortable In Admin List', 'propertyhive' ),
                'id'        => 'admin_list_sortable',
                'default'   => ( (isset($custom_field_details['admin_list_sortable']) && $custom_field_details['admin_list_sortable'] == '1') ? 'yes' : ''),
                'type'      => 'checkbox',
                'desc_tip'  =>  true,
                'desc'      => ''
            );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'customfield');

        return $settings;
    }

    public function custom_field_dropdown_options()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( !isset($current_settings['custom_fields']) )
        {
            $current_settings['custom_fields'] = array();
        }

        $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

        $custom_field_details = array();

        if ($current_id != '')
        {
            $custom_fields = $current_settings['custom_fields'];

            if (isset($custom_fields[$current_id]))
            {
                $custom_field_details = $custom_fields[$current_id];
            }
            else
            {
                die('Trying to edit a custom field which does not exist. Please go back and try again.');
            }
        }

        echo '
        <tr valign="top" id="row_dropdown_options">
            <th scope="row" class="titledesc">
                <label for="field_type">Dropdown Options</label>
            </th>
            <td class="forminp forminp-dropdown-options"><div>';
        if ( isset($custom_field_details['dropdown_options']) && !empty($custom_field_details['dropdown_options']) )
        {
            foreach ( $custom_field_details['dropdown_options'] as $dropdown_option )
            {
                echo '
                    <div><input type="text" name="dropdown_options[]" value="' . $dropdown_option . '"> <a href="" class="delete-dropdown-option">Delete Option</a></div>
                ';
            }
        }
        else
        {
            // None exist
            echo '
                <div><input type="text" name="dropdown_options[]" placeholder="Add Option"> <a href="" class="delete-dropdown-option">Delete Option</a></div>
            ';
        }
        echo '
                </div>
                <a href="" class="add-dropdown-option">Add New Option</a>
            </td>
        </tr>

        <script>
            jQuery(document).ready(function()
            {
                toggle_dropdown_options();

                jQuery(\'#field_type\').change(function()
                {
                    toggle_dropdown_options();
                });

                jQuery(\'body\').on(\'click\', \'a.add-dropdown-option\', function(e)
                {
                    e.preventDefault();

                    jQuery(\'.forminp-dropdown-options > div\').append(\'<div><input type="text" name="dropdown_options[]" placeholder="Add Option"> <a href="" class="delete-dropdown-option">Delete Option</a></div>\');
                });

                jQuery(\'body\').on(\'click\', \'a.delete-dropdown-option\', function(e)
                {
                    e.preventDefault();

                    var confirmBox = confirm(\'Are you sure you wish to delete this option?\');

                    if ( confirmBox )
                    {
                        jQuery(this).parent().remove();
                    }
                });
            });

            function toggle_dropdown_options()
            {
                if ( jQuery(\'#field_type\').val() == \'select\' || jQuery(\'#field_type\').val() == \'multiselect\' )
                {
                    jQuery(\'#row_dropdown_options\').show();
                }
                else
                {
                    jQuery(\'#row_dropdown_options\').hide();
                }
            }
        </script>
        ';
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