<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PH_Template_Assistant_Custom_Fields {

	public function __construct() {

		$current_settings = get_option( 'propertyhive_template_assistant', array() );

        add_action( 'admin_init', array( $this, 'check_for_delete_custom_field') );
        add_action( 'admin_init', array( $this, 'check_for_reorder_custom_fields') );

        add_action( 'propertyhive_admin_field_custom_field_dropdown_options', array( $this, 'custom_field_dropdown_options' ) );

        add_action( 'propertyhive_admin_field_custom_fields_table', array( $this, 'custom_fields_table' ) );

        add_filter( 'propertyhive_applicant_list_check', array( $this, 'applicant_list_check' ), 10, 3 );

        add_action( 'propertyhive_property_meta_list_end', array( $this, 'display_custom_fields_on_website' ) );
        add_filter( 'propertyhive_user_details_form_fields', array( $this, 'display_custom_fields_on_user_details' ), 10, 1 );
        add_action( 'propertyhive_applicant_registered', array( $this, 'save_custom_fields_on_user_details' ), 10, 2 );
        add_action( 'propertyhive_account_details_updated', array( $this, 'save_custom_fields_on_user_details' ), 10, 2 );

        add_filter( 'propertyhive_property_query_meta_query', array( $this, 'custom_fields_in_meta_query' ) );

        add_filter( 'manage_edit-property_columns', array( $this, 'custom_fields_in_property_admin_list_edit' ) );
        add_action( 'manage_property_posts_custom_column', array( $this, 'custom_fields_in_property_admin_list' ), 2 );
        add_filter( 'manage_edit-property_sortable_columns', array( $this, 'custom_fields_in_property_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_property_admin_list_orderby' ) );

        add_filter( 'manage_edit-contact_columns', array( $this, 'custom_fields_in_contact_admin_list_edit' ) );
        add_action( 'manage_contact_posts_custom_column', array( $this, 'custom_fields_in_contact_admin_list' ), 2 );
        add_filter( 'manage_edit-contact_sortable_columns', array( $this, 'custom_fields_in_contact_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_contact_admin_list_orderby' ) );

        add_filter( 'manage_edit-enquiry_columns', array( $this, 'custom_fields_in_enquiry_admin_list_edit' ) );
        add_action( 'manage_enquiry_posts_custom_column', array( $this, 'custom_fields_in_enquiry_admin_list' ), 2 );
        add_filter( 'manage_edit-enquiry_sortable_columns', array( $this, 'custom_fields_in_enquiry_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_enquiry_admin_list_orderby' ) );

        add_filter( 'manage_edit-appraisal_columns', array( $this, 'custom_fields_in_appraisal_admin_list_edit' ) );
        add_action( 'manage_appraisal_posts_custom_column', array( $this, 'custom_fields_in_appraisal_admin_list' ), 2 );
        add_filter( 'manage_edit-appraisal_sortable_columns', array( $this, 'custom_fields_in_appraisal_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_appraisal_admin_list_orderby' ) );

        add_filter( 'manage_edit-viewing_columns', array( $this, 'custom_fields_in_viewing_admin_list_edit' ) );
        add_action( 'manage_viewing_posts_custom_column', array( $this, 'custom_fields_in_viewing_admin_list' ), 2 );
        add_filter( 'manage_edit-viewing_sortable_columns', array( $this, 'custom_fields_in_viewing_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_viewing_admin_list_orderby' ) );

        add_filter( 'manage_edit-offer_columns', array( $this, 'custom_fields_in_offer_admin_list_edit' ) );
        add_action( 'manage_offer_posts_custom_column', array( $this, 'custom_fields_in_offer_admin_list' ), 2 );
        add_filter( 'manage_edit-offer_sortable_columns', array( $this, 'custom_fields_in_offer_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_offer_admin_list_orderby' ) );

        add_filter( 'manage_edit-sale_columns', array( $this, 'custom_fields_in_sale_admin_list_edit' ) );
        add_action( 'manage_sale_posts_custom_column', array( $this, 'custom_fields_in_sale_admin_list' ), 2 );
        add_filter( 'manage_edit-sale_sortable_columns', array( $this, 'custom_fields_in_sale_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_sale_admin_list_orderby' ) );

        add_filter( 'manage_edit-tenancy_columns', array( $this, 'custom_fields_in_tenancy_admin_list_edit' ) );
        add_action( 'manage_tenancy_posts_custom_column', array( $this, 'custom_fields_in_tenancy_admin_list' ), 2 );
        add_filter( 'manage_edit-tenancy_sortable_columns', array( $this, 'custom_fields_in_tenancy_admin_list_sort' ) );
        add_filter( 'request', array( $this, 'custom_fields_in_tenancy_admin_list_orderby' ) );

        add_action( 'propertyhive_office_table_header_columns', array( $this, 'add_office_additional_field_table_header_column' ), 10 );
        add_action( 'propertyhive_office_table_row_columns', array( $this, 'add_office_additional_field_table_row_column' ), 10 );

        add_action( 'propertyhive_contact_applicant_requirements_details_fields', array( $this, 'add_applicant_requirements_fields' ), 10, 2 );
        add_action( 'propertyhive_contact_applicant_requirements_residential_details_fields', array( $this, 'add_applicant_requirements_residential_fields' ), 10, 2 );
        add_action( 'propertyhive_contact_applicant_requirements_residential_sales_details_fields', array( $this, 'add_applicant_requirements_residential_sales_fields' ), 10, 2 );
        add_action( 'propertyhive_contact_applicant_requirements_residential_lettings_details_fields', array( $this, 'add_applicant_requirements_residential_lettings_fields' ), 10, 2 );
        add_action( 'propertyhive_contact_applicant_requirements_commercial_details_fields', array( $this, 'add_applicant_requirements_commercial_fields' ), 10, 2 );

        add_action( 'propertyhive_save_contact_applicant_requirements', array( $this, 'save_applicant_requirements_fields' ), 10, 2 );

        add_filter( 'propertyhive_applicant_requirements_display', array( $this, 'applicant_requirements_display' ), 10, 3 );
        add_filter( 'propertyhive_matching_properties_args', array( $this, 'matching_properties_args' ), 10, 3 );
        add_filter( 'propertyhive_matching_applicants_check', array( $this, 'matching_applicants_check' ), 10, 4 );
        add_filter( 'propertyhive_applicant_requirements_form_fields', array( $this, 'applicant_requirements_form_fields' ), 10, 2 );
        add_action( 'propertyhive_applicant_registered', array( $this, 'applicant_registered' ), 10, 2 );
        add_action( 'propertyhive_account_requirements_updated', array( $this, 'applicant_registered' ), 10, 2 );

        add_filter( 'propertyhive_room_breakdown_data', array( $this, 'add_custom_fields_to_room_breakdown' ), 10, 3 ); // Applicable when Rooms / Student Accommodation add on active

        $tags_sections = array( 'property', 'applicant', 'owner', 'contact', 'appraisal', 'viewing', 'offer', 'sale', 'negotiator', 'general' );

        foreach ( $tags_sections as $tags_section ) {
            add_filter( 'propertyhive_document_' . $tags_section . '_merge_tags', array( $this, 'document_' . $tags_section . '_custom_fields_merge_tags' ), 10, 2 );
            add_filter( 'propertyhive_document_' . $tags_section . '_merge_values', array( $this, 'document_' . $tags_section . '_custom_fields_merge_values' ), 10, 2 );
        }

        add_filter( 'propertyhive_elementor_widgets', array( $this, 'additional_field_elementor_widget' ), 10 );
        add_filter( 'propertyhive_elementor_widget_directory', array( $this, 'additional_field_elementor_widget_dir' ), 10, 2 );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            $meta_boxes_done = array();
            $office_details_fields_exist = false;
            $offices_opening_section_done = false;
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( !in_array( $custom_field['meta_box'], $meta_boxes_done ) )
                {
                    if ( substr( $custom_field['meta_box'], 0, 6 ) == 'office' )
                    {
                        add_filter( 'propertyhive_' . $custom_field['meta_box'] . '_settings', function( $settings )
                        {
                            global $offices_opening_section_done;
                            
                            $current_id = empty( $_REQUEST['id'] ) ? '' : (int)$_REQUEST['id'];

                            $meta_box_being_done = str_replace( "propertyhive_", "", current_filter() );
                            $meta_box_being_done = str_replace( "_settings", "", $meta_box_being_done );

                            $current_settings = get_option( 'propertyhive_template_assistant', array() );

                            foreach ( $current_settings['custom_fields'] as $custom_field )
                            {
                                if ( $custom_field['meta_box'] == $meta_box_being_done )
                                {
                                    if ( !$offices_opening_section_done )
                                    {
                                        $settings[] = array( 'title' => __( 'Additional Fields', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'office_template_assistant_additional_field' );
                                        $offices_opening_section_done = true;
                                    }

                                    switch ( $custom_field['field_type'] )
                                    {
                                        case "image":
                                        {
                                            if ( !did_action( 'wp_enqueue_media' ) )
                                                wp_enqueue_media();

                                            $settings[] = array(
                                                'title'     => $custom_field['field_label'],
                                                'id'        => $custom_field['field_name'],
                                                'default'   => get_post_meta($current_id, $custom_field['field_name'], TRUE),
                                                'type'      => $custom_field['field_type'],
                                                'desc_tip'  =>  false,
                                            );
                                            break;
                                        }
                                        case "select":
                                        {
                                            $options = array('' => '');
                                            if ( isset($custom_field['dropdown_options']) && is_array($custom_field['dropdown_options']) && !empty($custom_field['dropdown_options']) )
                                            {
                                                foreach ( $custom_field['dropdown_options'] as $dropdown_option )
                                                {
                                                    $options[$dropdown_option] = $dropdown_option;
                                                }
                                            }

                                            $settings[] = array(
                                                'title'     => $custom_field['field_label'],
                                                'id'        => $custom_field['field_name'],
                                                'default'   => get_post_meta($current_id, $custom_field['field_name'], TRUE),
                                                'type'      => $custom_field['field_type'],
                                                'desc_tip'  => false,
                                                'options'   => $options,
                                            );
                                            break;
                                        }
                                        default:
                                        {
                                            $settings[] = array(
                                                'title'     => $custom_field['field_label'],
                                                'id'        => $custom_field['field_name'],
                                                'default'   => get_post_meta($current_id, $custom_field['field_name'], TRUE),
                                                'type'      => $custom_field['field_type'],
                                                'desc_tip'  =>  false,
                                            );
                                        }
                                    }
                                }
                            }

                            if ( $offices_opening_section_done )
                            {
                                $settings[] = array( 'type' => 'sectionend', 'id' => 'office_template_assistant_additional_field');
                            }

                            return $settings;
                        });

                        $office_details_fields_exist = true;

                        add_action( 'propertyhive_save_office', function( $post_id )
                        {
                            $meta_box_being_done = 'office_details';

                            $current_settings = get_option( 'propertyhive_template_assistant', array() );

                            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
                            {
                                $current_settings['custom_fields'] = apply_filters( 'propertyhive_template_assistant_custom_fields_to_save', $current_settings['custom_fields'] );

                                foreach ( $current_settings['custom_fields'] as $custom_field )
                                {
                                    if ( $custom_field['meta_box'] == $meta_box_being_done )
                                    {
                                        update_post_meta( $post_id, $custom_field['field_name'], (isset($_POST[$custom_field['field_name']]) ? $_POST[$custom_field['field_name']] : '') );
                                    }
                                }
                            }
                        });
                    }
                    else
                    {
                        add_action( 'propertyhive_' . $custom_field['meta_box'] . '_fields', function()
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
                                        propertyhive_wp_select( apply_filters( 'propertyhive_template_assistant_custom_field_args_' . ltrim($custom_field['field_name'], '_'), array( 
                                            'id' => $custom_field['field_name'], 
                                            'label' => $custom_field['field_label'], 
                                            'desc_tip' => false,
                                            'options' => $options
                                        ), $thepostid ) );
                                    }
                                    elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'multiselect' )
                                    {
    ?>
    <p class="form-field <?php echo $custom_field['field_name']; ?>_field"><label for="<?php echo $custom_field['field_name']; ?>"><?php _e( $custom_field['field_label'], 'propertyhive' ); ?></label>
            <select id="<?php echo $custom_field['field_name']; ?>" name="<?php echo $custom_field['field_name']; ?>[]" multiple="multiple" data-placeholder="<?php _e( 'Select ' . $custom_field['field_label'], 'propertyhive' ); ?>" class="multiselect attribute_values">
                <?php
                    $selected_values = get_post_meta( $thepostid, $custom_field['field_name'], true );
                    if ( !is_array($selected_values) && $selected_values == '' )
                    {
                        $selected_values = array();
                    }
                    elseif ( !is_array($selected_values) && $selected_values != '' )
                    {
                        $selected_values = array($selected_values);
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
                                        propertyhive_wp_textarea_input( apply_filters( 'propertyhive_template_assistant_custom_field_args_' . ltrim($custom_field['field_name'], '_'), array( 
                                            'id' => $custom_field['field_name'], 
                                            'label' => $custom_field['field_label'], 
                                            'desc_tip' => false,
                                            'type' => 'text'
                                        ), $thepostid ) );
                                    }
                                    elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'date' )
                                    {
                                        propertyhive_wp_text_input( apply_filters( 'propertyhive_template_assistant_custom_field_args_' . ltrim($custom_field['field_name'], '_'), array( 
                                            'id' => $custom_field['field_name'], 
                                            'label' => $custom_field['field_label'], 
                                            'desc_tip' => false,
                                            'type' => 'date',
                                            'class' => 'small',
                                        ), $thepostid ) );
                                    }
                                    elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'checkbox' )
                                    {
                                        propertyhive_wp_checkbox( apply_filters( 'propertyhive_template_assistant_custom_field_args_' . ltrim($custom_field['field_name'], '_'), array( 
                                            'id' => $custom_field['field_name'], 
                                            'label' => $custom_field['field_label'], 
                                            'desc_tip' => false,
                                        ), $thepostid ) );
                                    }
                                    elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'image' )
                                    {
                                        propertyhive_wp_photo_upload( apply_filters( 'propertyhive_template_assistant_custom_field_args_' . ltrim($custom_field['field_name'], '_'), array( 
                                            'id' => $custom_field['field_name'], 
                                            'label' => $custom_field['field_label'], 
                                            'desc_tip' => false,
                                            'button_label' => __( 'Select Image', 'propertyhive' )
                                        ), $thepostid ) );
                                    }
                                    elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'file' )
                                    {
                                        propertyhive_wp_file_upload( apply_filters( 'propertyhive_template_assistant_custom_field_args_' . ltrim($custom_field['field_name'], '_'), array( 
                                            'id' => $custom_field['field_name'], 
                                            'label' => $custom_field['field_label'], 
                                            'desc_tip' => false,
                                            'button_label' => __( 'Select File', 'propertyhive' )
                                        ), $thepostid ) );
                                    }
                                    else
                                    {
                                        propertyhive_wp_text_input( apply_filters( 'propertyhive_template_assistant_custom_field_args_' . ltrim($custom_field['field_name'], '_'), array( 
                                            'id' => $custom_field['field_name'], 
                                            'label' => $custom_field['field_label'], 
                                            'desc_tip' => false,
                                            'type' => 'text'
                                        ), $thepostid ) );
                                    }
                                }
                            }
                        });

                        add_action( 'propertyhive_save_' . $custom_field['meta_box'],  function( $post_id )
                        {
                            $meta_box_being_done = str_replace( "propertyhive_save_", "", current_filter() );

                            $current_settings = get_option( 'propertyhive_template_assistant', array() );

                            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
                            {
                                $current_settings['custom_fields'] = apply_filters( 'propertyhive_template_assistant_custom_fields_to_save', $current_settings['custom_fields'] );

                                foreach ( $current_settings['custom_fields'] as $custom_field )
                                {
                                    if ( $custom_field['meta_box'] == $meta_box_being_done )
                                    {
                                        update_post_meta( $post_id, $custom_field['field_name'], (isset($_POST[$custom_field['field_name']]) ? $_POST[$custom_field['field_name']] : '') );
                                    }
                                }
                            }
                        });
                    }

                    $meta_boxes_done[] = $custom_field['meta_box'];
                }
            }

            if ( $office_details_fields_exist  )
            {
                add_filter( 'propertyhive_' . $custom_field['meta_box'] . '_settings', function( $settings )
                {
                    $settings[] = array( 'type' => 'sectionend', 'id' => 'office_location_options' );

                    return $settings;
                });
            }

            $shortcodes = array(
                'properties',
                'recent_properties',
                'featured_properties',
                'similar_properties',
            );

            foreach ( $shortcodes as $shortcode )
            {
                add_filter( 'shortcode_atts_' . $shortcode, function ($out, $pairs, $atts, $shortcode)
                {
                    $current_settings = get_option( 'propertyhive_template_assistant', array() );

                    if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
                    {
                        foreach ( $current_settings['custom_fields'] as $custom_field )
                        {
                            if ( strpos($custom_field['meta_box'], 'property') !== FALSE )
                            {
                                $out[trim($custom_field['field_name'], '_')] = ( isset($atts[trim($custom_field['field_name'], '_')]) ? $atts[trim($custom_field['field_name'], '_')] : '' );
                            }
                        }
                    }

                    return $out;
                }, 10, 4 );

                add_filter( 'propertyhive_shortcode_' . $shortcode . '_query', function ($args, $atts)
                {
                    $current_settings = get_option( 'propertyhive_template_assistant', array() );

                    if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
                    {
                        foreach ( $current_settings['custom_fields'] as $custom_field )
                        {
                            if ( strpos($custom_field['meta_box'], 'property') !== FALSE )
                            {
                                if (
                                    isset($atts[trim($custom_field['field_name'], '_')]) && 
                                    $atts[trim($custom_field['field_name'], '_')] != ''
                                )
                                {
                                    if ( !isset($args['meta_query']) )
                                    {
                                        $args['meta_query'] = array();
                                    }

                                    // Format meta query as "= value" or "LIKE value"
                                    $value = $atts[trim($custom_field['field_name'], '_')];
                                    $compare = $custom_field['field_type'] == 'multiselect' ? 'LIKE' : '=';

                                    // A comma-delimited list of values has been specified
                                    if ( strpos($value, ',') !== false )
                                    {
                                        if ( $custom_field['field_type'] == 'multiselect' )
                                        {
                                            // Format meta query as "REGEXP value1|value2"
                                            $value = '"' . str_replace(',', '"|"', $value) . '"';
                                            $compare = 'REGEXP';
                                        }
                                        else
                                        {
                                            // Format meta query as "IN array(value1, value2)"
                                            $value = explode(',', $value);
                                            $compare = 'IN';
                                        }
                                    }

                                    $args['meta_query'][] = array(
                                        'key' => $custom_field['field_name'],
                                        'value' => $value,
                                        'compare' => $compare,
                                    );
                                }
                            }
                        }
                    }
                    return $args;
                }, 99, 2 );
            }
        }

        add_action( 'propertyhive_applicant_list_additional_fields',  function()
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                $display_class = '';
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    switch ($custom_field['meta_box'])
                    {
                        case 'property_residential_details' :
                            $display_class = ' residential-only';
                            break;
                        case 'property_residential_sales_details' :
                            $display_class = ' sales-only';
                            break;
                        case 'property_residential_lettings_details' :
                            $display_class = ' lettings-only';
                            break;
                        case 'property_commercial_details' :
                            $display_class = ' commercial-only';
                            break;
                    }
                    if ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'select' )
                    {
                        ?>
                        <p class="form-field <?php echo $custom_field['field_name']; ?>_field<?php echo $display_class; ?>">
                            <label for="<?php echo $custom_field['field_name']; ?>"><?php echo $custom_field['field_label']; ?></label>
                            <select id="<?php echo $custom_field['field_name']; ?>" name="<?php echo $custom_field['field_name']; ?>">
                                <option value=""></option>
                                <?php
                                    foreach ( $custom_field['dropdown_options'] as $key => $value ) 
                                    {
                                        echo '<option value="' . esc_attr( $value ) . '"';
                                        if ( isset($_POST[$custom_field['field_name']]) && $_POST[$custom_field['field_name']] == $value )
                                        {
                                            echo ' selected';
                                        }
                                        echo '>' . esc_html( $value ) . '</option>';
                                    }
                            ?>
                            </select>
                        <?php
                    }
                    elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'multiselect' )
                    {
                        ?>
                        <p class="form-field <?php echo $custom_field['field_name']; ?>_field<?php echo $display_class; ?>">
                        <label for="<?php echo $custom_field['field_name']; ?>"><?php echo $custom_field['field_label']; ?></label>
                                <select id="<?php echo $custom_field['field_name']; ?>" name="<?php echo $custom_field['field_name']; ?>[]" multiple="multiple" data-placeholder="<?php _e( 'Select ' . $custom_field['field_label'], 'propertyhive' ); ?>" class="multiselect attribute_values">
                                    <?php
                                        $selected_values = isset($_POST[$custom_field['field_name']]) ? $_POST[$custom_field['field_name']] : array();
                                        if ( !is_array($selected_values) && $selected_values == '' )
                                        {
                                            $selected_values = array();
                                        }
                                        elseif ( !is_array($selected_values) && $selected_values != '' )
                                        {
                                            $selected_values = array($selected_values);
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
                    elseif ( isset($custom_field['field_type']) && $custom_field['field_type'] == 'checkbox' )
                    {
                        ?>
                        <p class="form-field <?php echo $custom_field['field_name']; ?>_field<?php echo $display_class; ?>">
                            <label for="<?php echo $custom_field['field_name']; ?>"><?php echo $custom_field['field_label']; ?></label>
                            <input type="checkbox" id="<?php echo $custom_field['field_name']; ?>" value="yes" name="<?php echo $custom_field['field_name']; ?>"<?php if ( isset($_POST[$custom_field['field_name']]) && $_POST[$custom_field['field_name']] == 'yes' ) { echo ' checked'; } ?>>
                        <?php
                    }
                }

            }
        });

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

    public function check_for_reorder_custom_fields()
    {
        if ( isset($_GET['neworder']) && $_GET['neworder'] != '' )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            $current_id = ( !isset( $_GET['id'] ) ) ? '' : sanitize_title( $_GET['id'] );

            $existing_custom_fields = ( (isset($current_settings['custom_fields'])) ? $current_settings['custom_fields'] : array() );

            $new_order = explode(",", $_GET['neworder']);
            $new_order = ph_clean($new_order);

            $new_custom_fields = array();

            foreach ( $new_order as $id )
            {
                $new_custom_fields[] = $existing_custom_fields[$id];
            }

            $current_settings['custom_fields'] = $new_custom_fields;

            update_option( 'propertyhive_template_assistant', $current_settings );

            header("Location: " . admin_url('admin.php?page=ph-settings&tab=template-assistant&section=custom-fields'));
            exit();
        }
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
            <td class="forminp forminp-dropdown-options"><div id="sortable_options_' . $current_id . '">';
        if ( isset($custom_field_details['dropdown_options']) && !empty($custom_field_details['dropdown_options']) )
        {
            foreach ( $custom_field_details['dropdown_options'] as $dropdown_option )
            {
                echo '
                    <div><i class="fa fa-reorder" style="cursor:pointer; opacity:0.3"></i> <input type="text" name="dropdown_options[]" value="' . $dropdown_option . '"> <a href="" class="delete-dropdown-option">Delete Option</a></div>
                ';
            }
        }
        else
        {
            // None exist
            echo '
                <div><i class="fa fa-reorder" style="cursor:pointer; opacity:0.3"></i> <input type="text" name="dropdown_options[]" placeholder="Add Option"> <a href="" class="delete-dropdown-option">Delete Option</a></div>
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

                    jQuery(\'.forminp-dropdown-options > div\').append(\'<div><i class="fa fa-reorder" style="cursor:pointer; opacity:0.3"></i> <input type="text" name="dropdown_options[]" placeholder="Add Option"> <a href="" class="delete-dropdown-option">Delete Option</a></div>\');
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

                jQuery( \'#sortable_options_' . $current_id . '\' )
                .sortable({
                    axis: "y",
                    handle: "i",
                    stop: function( event, ui ) 
                    {
                        // IE doesn\'t register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        //ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        //jQuery( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = jQuery(this).sortable(\'toArray\');
                        
                        //$(\'#active_fields_order\').val( fields_order.join("|") );
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

    public function get_template_assistant_custom_fields_settings()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Additional Fields', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_custom_fields_settings' )

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

        $current_settings = get_option( 'propertyhive_template_assistant', array() );
        $custom_fields = array();
        if ($current_settings !== FALSE)
        {
            if (isset($current_settings['custom_fields']))
            {
                $custom_fields = $current_settings['custom_fields'];
            }
        }
?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=addcustomfield' ); ?>" class="button alignright"><?php echo __( 'Add New Field', 'propertyhive' ); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php _e( 'Additional Fields', 'propertyhive' ) ?></th>
            <td class="forminp">
                <style type="text/css">
                    .ui-sortable-helper {
                        display: table;
                    }
                </style>
                <table class="ph_additional_fields widefat" cellspacing="0">
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
                    <tbody class="<?php echo !empty($custom_fields) ? 'has-rows' : ''; ?>">
                        <?php

                            if (!empty($custom_fields))
                            {
                                foreach ($custom_fields as $id => $custom_field)
                                {
                                    echo '<tr id="custom_field_' . $id . '">';
                                        echo '<td class="field-label"><span class="sort_anchor" style="cursor:grab "> ⇅ </span>' . $custom_field['field_label'] . '</td>';
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
                                            <a class="button" href="' . admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=custom-fields&action=deletecustomfield&id=' . $id ) . '" onclick="var confirmBox = confirm(\'Are you sure you wish to delete this custom field?\'); return confirmBox;">' . __( 'Delete', 'propertyhive' ) . '</a>
                                        </td>';
                                    echo '</tr>';
                                }
                            }
                            else
                            {
                                echo '<tr>';
                                    echo '<td align="center" colspan="7">' . __( 'No additional fields exist', 'propertyhive' ) . '</td>';
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
                <a href="<?php echo admin_url( 'admin.php?page=ph-settings&tab=template-assistant&section=addcustomfield' ); ?>" class="button alignright"><?php echo __( 'Add New Field', 'propertyhive' ); ?></a>
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

            array( 'title' => __( ( $current_section == 'addcustomfield' ? 'Add Additional Field' : 'Edit Additional Field' ), 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'customfield' ),

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
                'checkbox' => 'Checkbox',
                'date' => 'Date',
                'image' => 'Image',
                'file' => 'File',
            )
        );

        $settings[] = array(
            'title' => __( 'Dropdown Options', 'propertyhive' ),
            'id'        => 'dropdown_options',
            'type'      => 'custom_field_dropdown_options',
        );

        $options = array(
            'property_address' => 'Property Address',
            'property_department' => 'Property Department',
        );

        if ( get_option( 'propertyhive_active_departments_sales', '' ) == 'yes' || get_option( 'propertyhive_active_departments_lettings', '' ) == 'yes' )
        {
            $options['property_residential_details'] = __( 'Property Residential Details', 'propertyhive' );

            if ( get_option( 'propertyhive_active_departments_sales', '' ) == 'yes' )
            {
                $options['property_residential_sales_details'] = __( 'Property Residential Sales Details', 'propertyhive' );
            }
            if ( get_option( 'propertyhive_active_departments_lettings', '' ) == 'yes' )
            {
                $options['property_residential_lettings_details'] = __( 'Property Residential Lettings Details', 'propertyhive' );
            }
        }

        if ( get_option( 'propertyhive_active_departments_commercial', '' ) == 'yes' )
        {
            $options['property_commercial_details'] = __( 'Property Commercial Details', 'propertyhive' );
        }

        if ( get_option('propertyhive_module_disabled_contacts', '') != 'yes' )
        {
            $options['contact_correspondence_address'] = __( 'Contact Correspondence Address', 'propertyhive' );
            $options['contact_contact_details'] = __( 'Contact Contact Details', 'propertyhive' );
        }

        if ( get_option('propertyhive_module_disabled_enquiries', '') != 'yes' )
        {
            $options['enquiry_record_details'] = __( 'Enquiry Record Details', 'propertyhive' );
        }

        if ( get_option('propertyhive_module_disabled_appraisals', '') != 'yes' )
        {
            $options['appraisal_details'] = __( 'Appraisal Details', 'propertyhive' );
            $options['appraisal_event'] = __( 'Appraisal Event Details', 'propertyhive' );
        }

        if ( get_option('propertyhive_module_disabled_viewings', '') != 'yes' )
        {
            $options['viewing_details'] = __( 'Viewing Details', 'propertyhive' );
            $options['viewing_event'] = __( 'Viewing Event Details', 'propertyhive' );
        }

        if ( get_option('propertyhive_module_disabled_offers_sales', '') != 'yes' )
        {
            $options['offer_details'] = __( 'Offer Details', 'propertyhive' );
            $options['sale_details'] = __( 'Sale Details', 'propertyhive' );
        }

        if ( get_option( 'propertyhive_active_departments_lettings' ) == 'yes' && get_option('propertyhive_module_disabled_tenancies', '') != 'yes' )
        {
            $options['tenancy_details'] = __( 'Tenancy Details', 'propertyhive' );
            $options['tenancy_management_details'] = __( 'Tenancy Management Details', 'propertyhive' );
            $options['tenancy_deposit_scheme'] = __( 'Tenancy Deposit Scheme Details', 'propertyhive' );
            $options['tenancy_meter_readings'] = __( 'Tenancy Meter Readings', 'propertyhive' );
        }

        $options['office_details'] = __( 'Office Details', 'propertyhive' );

        $options = apply_filters( 'propertyhive_template_assistant_custom_field_sections', $options );

        $settings[] = array(
            'title' => __( 'Section', 'propertyhive' ),
            'id'        => 'meta_box',
            'default'   => ( (isset($custom_field_details['meta_box'])) ? $custom_field_details['meta_box'] : ''),
            'type'      => 'select',
            'desc'  =>  __( 'Please select which meta box on the property record this field should appear in', 'propertyhive' ),
            'options' => $options
        );

        $settings[] = array(
            'title' => __( 'Display On Website', 'propertyhive' ),
            'id'        => 'display_on_website',
            'default'   => ( (isset($custom_field_details['display_on_website']) && $custom_field_details['display_on_website'] == '1') ? 'yes' : ''),
            'type'      => 'checkbox',
        );

        if ( get_option('propertyhive_module_disabled_contacts', '') != 'yes' )
        {
            $settings[] = array(
                'title' => __( 'Add As Match Field To Applicant Requirements', 'propertyhive' ),
                'id'        => 'display_on_applicant_requirements',
                'default'   => ( (isset($custom_field_details['display_on_applicant_requirements']) && $custom_field_details['display_on_applicant_requirements'] == '1') ? 'yes' : ''),
                'type'      => 'checkbox',
            );
        }

        $settings[] = array(
            'title' => __( 'Exact Match Only If Searching On Field', 'propertyhive' ),
            'id'        => 'exact_match',
            'default'   => ( (isset($custom_field_details['exact_match']) && $custom_field_details['exact_match'] == '1') ? 'yes' : ''),
            'type'      => 'checkbox',
            'desc'  =>  __( 'If you\'re using this checkbox in property searches or matches tick this if properties should only be returned with the same ticked status. Alternatively, leave unticked for scenarios like \'Pets Allowed\' whereby properties with it ticked should come back whether search is ticked or not, but not vice versa.', 'propertyhive' ),
        );

        $settings[] = array(
            'title' => __( 'Display On Registration Form / My Account', 'propertyhive' ),
            'id'        => 'display_on_user_details',
            'default'   => ( (isset($custom_field_details['display_on_user_details']) && $custom_field_details['display_on_user_details'] == '1') ? 'yes' : ''),
            'type'      => 'checkbox',
        );

        $settings[] = array(
            'title' => __( 'Show In Admin List', 'propertyhive' ),
            'id'        => 'admin_list',
            'default'   => ( (isset($custom_field_details['admin_list']) && $custom_field_details['admin_list'] == '1') ? 'yes' : ''),
            'type'      => 'checkbox',
        );

        $settings[] = array(
            'title' => __( 'Sortable In Admin List', 'propertyhive' ),
            'id'        => 'admin_list_sortable',
            'default'   => ( (isset($custom_field_details['admin_list_sortable']) && $custom_field_details['admin_list_sortable'] == '1') ? 'yes' : ''),
            'type'      => 'checkbox',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'customfield');

        $settings[] = array(
            'type' => 'html',
            'html' => '<script>

                jQuery(document).ready(function()
                {
                    ph_hide_show_type_related_checkboxes();

                    jQuery(\'#meta_box\').change(function()
                    {
                        ph_hide_show_type_related_checkboxes();
                    });

                    jQuery(\'#field_type\').change(function()
                    {
                        ph_hide_show_type_related_checkboxes();
                    });
                });

                function ph_hide_show_type_related_checkboxes()
                {
                    var meta_box = jQuery(\'#meta_box\').val();

                    jQuery(\'#row_display_on_website\').hide();
                    jQuery(\'#row_display_on_applicant_requirements\').hide();
                    jQuery(\'#row_display_on_user_details\').hide();
                    jQuery(\'#row_exact_match\').hide();

                    jQuery(\'#row_admin_list\').show();
                    jQuery(\'#row_admin_list_sortable\').show();

                    if ( meta_box.indexOf(\'property_\') != -1 )
                    {
                        jQuery(\'#row_display_on_website\').show();
                        
                        if ( jQuery(\'#field_type\').val() == \'select\' || jQuery(\'#field_type\').val() == \'multiselect\' || jQuery(\'#field_type\').val() == \'checkbox\' )
                        {
                            jQuery(\'#row_display_on_applicant_requirements\').show();
                        }

                        if ( jQuery(\'#field_type\').val() == \'checkbox\' )
                        {
                            jQuery(\'#row_exact_match\').show();
                        }
                    }
                    if ( meta_box.indexOf(\'contact_\') != -1 )
                    {
                        jQuery(\'#row_display_on_user_details\').show();
                    }
                    if ( meta_box == \'tenancy_management_details\' )
                    {
                        jQuery(\'#row_admin_list\').hide();
                        jQuery(\'#row_admin_list_sortable\').hide();
                    }
                }

            </script>'
        );

        return $settings;
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
                                echo is_array($values) ? implode(", ", $values) : $values;
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_property->{$custom_field['field_name']}));
                        }
                        elseif ( $custom_field['field_type'] == 'image' )
                        {
                            $image_id = $the_property->{$custom_field['field_name']};
                            if ( $image_id != '' )
                            {
                                $image = wp_get_attachment_image_src( $image_id, 'thumbnail' );
                                if ($image !== FALSE)
                                {
                                    echo '<img src="' . $image[0] . '" width="150" alt="">';
                                }
                                else
                                {
                                    echo 'Image doesn\'t exist';
                                }
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'file' )
                        {
                            $file = get_attached_file( $the_property->{$custom_field['field_name']} );
                            if ( $file !== FALSE )
                            {
                                $filename = basename( $file );
                                echo '<a href="' . wp_get_attachment_url($the_property->{$custom_field['field_name']}) . '" target="_blank">' . $filename . '</a>';
                            }
                            else
                            {
                                echo 'Image doesn\'t exist';
                            }
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

    // Additional fields in contact admin list
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
                                echo is_array($values) ? implode(", ", $values) : $values;
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
                        if ( $custom_field['field_name'] == $vars['orderby'] ) 
                        {
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

    // Additional fields in enquiry admin list
    public function custom_fields_in_enquiry_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'enquiry_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_enquiry_admin_list( $column )
    {
        global $post, $propertyhive, $the_enquiry;

        if ( empty( $the_enquiry ) || $the_enquiry->ID != $post->ID ) 
        {
            $the_enquiry = new PH_Enquiry( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'enquiry_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_enquiry->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_enquiry->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo is_array($values) ? implode(", ", $values) : $values;
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_enquiry->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_enquiry->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_enquiry_admin_list_sort( $columns ) 
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'enquiry_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_enquiry_admin_list_orderby( $vars ) 
    {
        if ( isset( $vars['orderby'] ) ) 
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'enquiry_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] ) 
                        {
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

    // Additional fields in appraisal admin list
    public function custom_fields_in_appraisal_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 10) == 'appraisal_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_appraisal_admin_list( $column )
    {
        global $post, $propertyhive, $the_appraisal;

        if ( empty( $the_appraisal ) || $the_appraisal->ID != $post->ID ) 
        {
            $the_appraisal = new PH_Appraisal( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 10) == 'appraisal_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_appraisal->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_appraisal->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo is_array($values) ? implode(", ", $values) : $values;
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_appraisal->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_appraisal->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_appraisal_admin_list_sort( $columns ) 
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 10) == 'appraisal_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_appraisal_admin_list_orderby( $vars ) 
    {
        if ( isset( $vars['orderby'] ) ) 
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 10) == 'appraisal_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] ) 
                        {
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

    // Additional fields in viewing admin list
    public function custom_fields_in_viewing_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'viewing_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_viewing_admin_list( $column )
    {
        global $post, $propertyhive, $the_viewing;

        if ( empty( $the_viewing ) || $the_viewing->ID != $post->ID ) 
        {
            $the_viewing = new PH_Viewing( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'viewing_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_viewing->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_viewing->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo is_array($values) ? implode(", ", $values) : $values;
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_viewing->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_viewing->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_viewing_admin_list_sort( $columns ) 
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'viewing_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_viewing_admin_list_orderby( $vars ) 
    {
        if ( isset( $vars['orderby'] ) ) 
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'viewing_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] ) 
                        {
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

    // Additional fields in offer admin list
    public function custom_fields_in_offer_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 6) == 'offer_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_offer_admin_list( $column )
    {
        global $post, $propertyhive, $the_offer;

        if ( empty( $the_offer ) || $the_offer->ID != $post->ID ) 
        {
            $the_offer = new PH_Offer( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 6) == 'offer_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_offer->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_offer->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo is_array($values) ? implode(", ", $values) : $values;
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_offer->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_offer->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_offer_admin_list_sort( $columns ) 
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 6) == 'offer_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_offer_admin_list_orderby( $vars ) 
    {
        if ( isset( $vars['orderby'] ) ) 
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 6) == 'offer_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] ) 
                        {
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

    // Additional fields in sale admin list
    public function custom_fields_in_sale_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 5) == 'sale_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_sale_admin_list( $column )
    {
        global $post, $propertyhive, $the_sale;

        if ( empty( $the_sale ) || $the_sale->ID != $post->ID ) 
        {
            $the_sale = new PH_Sale( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 5) == 'sale_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_sale->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_sale->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo is_array($values) ? implode(", ", $values) : $values;
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_sale->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_sale->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_sale_admin_list_sort( $columns ) 
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 5) == 'sale_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_sale_admin_list_orderby( $vars ) 
    {
        if ( isset( $vars['orderby'] ) ) 
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 5) == 'sale_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] ) 
                        {
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

    // Additional fields in tenancy admin list
    public function custom_fields_in_tenancy_admin_list_edit( $existing_columns )
    {
        if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
            $existing_columns = array();
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'tenancy_' )
                {
                    $existing_columns[$custom_field['field_name']] = __( $custom_field['field_label'], 'propertyhive' );
                }
            }
        }

        return $existing_columns;
    }

    public function custom_fields_in_tenancy_admin_list( $column )
    {
        global $post, $propertyhive, $the_tenancy;

        if ( empty( $the_tenancy ) || $the_tenancy->ID != $post->ID )
        {
            $the_tenancy = new PH_Tenancy( $post->ID );
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'tenancy_' && $custom_field['field_name'] == $column )
                {
                    if ( $the_tenancy->{$custom_field['field_name']} != '' )
                    {
                        if ( $custom_field['field_type'] == 'multiselect' )
                        {
                            $values = get_post_meta( $the_tenancy->id, $custom_field['field_name'], TRUE );
                            if ( !empty($values) )
                            {
                                echo is_array($values) ? implode(", ", $values) : $values;
                            }
                        }
                        elseif ( $custom_field['field_type'] == 'date' )
                        {
                            echo date(get_option( 'date_format' ), strtotime($the_tenancy->{$custom_field['field_name']}));
                        }
                        else
                        {
                            echo $the_tenancy->{$custom_field['field_name']};
                        }
                    }
                }
            }
        }
    }

    public function custom_fields_in_tenancy_admin_list_sort( $columns )
    {
        $custom = array();

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'tenancy_' )
                {
                    $custom[$custom_field['field_name']] = $custom_field['field_name'];
                }
            }
        }

        return wp_parse_args( $custom, $columns );
    }

    public function custom_fields_in_tenancy_admin_list_orderby( $vars )
    {
        if ( isset( $vars['orderby'] ) )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
            {
                foreach ( $current_settings['custom_fields'] as $custom_field )
                {
                    if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && isset($custom_field['admin_list_sortable']) && $custom_field['admin_list_sortable'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'tenancy_' )
                    {
                        if ( $custom_field['field_name'] == $vars['orderby'] )
                        {
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

    public function add_office_additional_field_table_header_column()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 6) == 'office' )
                {
                    echo '<th>' . $custom_field['field_label'] . '</th>';
                }
            }
        }
    }

    public function add_office_additional_field_table_row_column( $office_id )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['admin_list']) && $custom_field['admin_list'] == '1' && substr($custom_field['meta_box'], 0, 6) == 'office' )
                {
                    echo '<td>';
                    switch ( $custom_field['field_type'] )
                    {
                        case "image":
                        {   
                            $image_id = get_post_meta( $office_id, $custom_field['field_name'], TRUE );
                            if ( $image_id != '' )
                            {
                                $image = wp_get_attachment_image_src( $image_id, 'thumbnail' );
                                if ($image !== FALSE)
                                {
                                    echo '<img src="' . $image[0] . '" width="150" alt="">';
                                }
                                else
                                {
                                    echo 'Image doesn\'t exist';
                                }
                            }
                            break;
                        }
                        default:
                        {
                            echo get_post_meta( $office_id, $custom_field['field_name'], TRUE );
                        }
                    }
                    echo '</td>';
                }
            }
        }
    }

    private function add_applicant_requirements_field( $custom_field, $applicant_profile, $applicant_profile_id )
    {
        switch ( $custom_field['field_type'] )
        {
            case "select":
            {
                $options = array('' => '');
                foreach ($custom_field['dropdown_options'] as $dropdown_option)
                {
                    $options[$dropdown_option] = ph_clean($dropdown_option);
                }

                propertyhive_wp_select( array( 
                    'id' => '_applicant' . $custom_field['field_name'] . '_' . $applicant_profile_id, 
                    'label' => $custom_field['field_label'], 
                    'desc_tip' => false, 
                    'custom_attributes' => array(
                        'style' => 'width:100%; max-width:150px;'
                    ),
                    'value' => ( ( isset($applicant_profile[$custom_field['field_name']]) ) ? $applicant_profile[$custom_field['field_name']] : '' ),
                    'options' => $options,
                ) );

                break;
            }
            case "multiselect":
            {
                $options = array('' => '');
                foreach ($custom_field['dropdown_options'] as $dropdown_option)
                {
                    $options[$dropdown_option] = ph_clean($dropdown_option);
                }
?>
                <p class="form-field">
                    <label for="_applicant<?php echo $custom_field['field_name']; ?>_<?php echo $applicant_profile_id; ?>"><?php echo $custom_field['field_label']; ?></label>
                    <select id="_applicant<?php echo $custom_field['field_name']; ?>_<?php echo $applicant_profile_id; ?>" name="_applicant<?php echo $custom_field['field_name']; ?>_<?php echo $applicant_profile_id; ?>[]" multiple="multiple" data-placeholder="Start typing to add <?php echo esc_attr($custom_field['field_label']); ?>..." class="multiselect attribute_values">
                        <?php
                            foreach ( $options as $option )
                            {
                                echo '<option value="' . esc_attr( $option ) . '"';
                                if ( 
                                    isset($applicant_profile[$custom_field['field_name']]) 
                                )
                                {
                                    if ( !is_array($applicant_profile[$custom_field['field_name']]) && $applicant_profile[$custom_field['field_name']] != '' )
                                    {
                                        $applicant_profile[$custom_field['field_name']] = array($applicant_profile[$custom_field['field_name']]);
                                    }

                                    if ( in_array( $option, $applicant_profile[$custom_field['field_name']] ) )
                                    {
                                        echo ' selected';
                                    }
                                }
                                echo '>' . esc_html( $option ) . '</option>';
                            }
                        ?>
                    </select>
                </p>
<?php
                break;
            }
            case "checkbox":
            {
                propertyhive_wp_checkbox( array( 
                    'id' => '_applicant' . $custom_field['field_name'] . '_' . $applicant_profile_id, 
                    'label' => $custom_field['field_label'], 
                    'desc_tip' => false, 
                    'value' => ( ( isset($applicant_profile[$custom_field['field_name']]) && $applicant_profile[$custom_field['field_name']] == 'yes' ) ? 'yes' : '' ),
                ) );

                break;
            }
        }
    }

    public function add_applicant_requirements_fields( $contact_post_id, $applicant_profile_id )
    {
        $applicant_profile = get_post_meta( $contact_post_id, '_applicant_profile_' . $applicant_profile_id, TRUE );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( 
                    isset($custom_field['display_on_applicant_requirements']) && 
                    $custom_field['display_on_applicant_requirements'] == '1' && 
                    substr($custom_field['meta_box'], 0, 9) == 'property_' &&
                    !in_array( $custom_field['meta_box'], array('property_residential_details', 'property_residential_sales_details', 'property_residential_lettings_details', 'property_commercial_details') )
                )
                {
                    $this->add_applicant_requirements_field( $custom_field, $applicant_profile, $applicant_profile_id );
                }
            }
        }
    }

    public function add_applicant_requirements_residential_fields( $contact_post_id, $applicant_profile_id )
    {
        $applicant_profile = get_post_meta( $contact_post_id, '_applicant_profile_' . $applicant_profile_id, TRUE );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( 
                    isset($custom_field['display_on_applicant_requirements']) && 
                    $custom_field['display_on_applicant_requirements'] == '1' && 
                    substr($custom_field['meta_box'], 0, 9) == 'property_' &&
                    in_array( $custom_field['meta_box'], array('property_residential_details') )
                )
                {
                    $this->add_applicant_requirements_field( $custom_field, $applicant_profile, $applicant_profile_id );
                }
            }
        }
    }

    public function add_applicant_requirements_residential_sales_fields( $contact_post_id, $applicant_profile_id )
    {
        $applicant_profile = get_post_meta( $contact_post_id, '_applicant_profile_' . $applicant_profile_id, TRUE );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( 
                    isset($custom_field['display_on_applicant_requirements']) && 
                    $custom_field['display_on_applicant_requirements'] == '1' && 
                    substr($custom_field['meta_box'], 0, 9) == 'property_' &&
                    in_array( $custom_field['meta_box'], array('property_residential_sales_details') )
                )
                {
                    $this->add_applicant_requirements_field( $custom_field, $applicant_profile, $applicant_profile_id );
                }
            }
        }
    }

    public function add_applicant_requirements_residential_lettings_fields( $contact_post_id, $applicant_profile_id )
    {
        $applicant_profile = get_post_meta( $contact_post_id, '_applicant_profile_' . $applicant_profile_id, TRUE );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( 
                    isset($custom_field['display_on_applicant_requirements']) && 
                    $custom_field['display_on_applicant_requirements'] == '1' && 
                    substr($custom_field['meta_box'], 0, 9) == 'property_' &&
                    in_array( $custom_field['meta_box'], array('property_residential_lettings_details') )
                )
                {
                    $this->add_applicant_requirements_field( $custom_field, $applicant_profile, $applicant_profile_id );
                }
            }
        }
    }

    public function add_applicant_requirements_commercial_fields( $contact_post_id, $applicant_profile_id )
    {
        $applicant_profile = get_post_meta( $contact_post_id, '_applicant_profile_' . $applicant_profile_id, TRUE );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( 
                    isset($custom_field['display_on_applicant_requirements']) && 
                    $custom_field['display_on_applicant_requirements'] == '1' && 
                    substr($custom_field['meta_box'], 0, 9) == 'property_' &&
                    in_array( $custom_field['meta_box'], array('property_commercial_details') )
                )
                {
                    $this->add_applicant_requirements_field( $custom_field, $applicant_profile, $applicant_profile_id );
                }
            }
        }
    }

    public function save_applicant_requirements_fields( $contact_post_id, $applicant_profile_id )
    {
        $applicant_profile = get_post_meta( $contact_post_id, '_applicant_profile_' . $applicant_profile_id, TRUE );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    switch ( $custom_field['field_type'] )
                    {
                        case "select":
                        case "multiselect":
                        {
                            if ( isset($_POST['_applicant' . $custom_field['field_name'] . '_' . $applicant_profile_id]) )
                            {
                                $applicant_profile[$custom_field['field_name']] = ph_clean($_POST['_applicant' . $custom_field['field_name'] . '_' . $applicant_profile_id]);
                            }
                            break;
                        }
                        case "checkbox":
                        {
                            if ( isset($_POST['_applicant' . $custom_field['field_name'] . '_' . $applicant_profile_id]) )
                            {
                                $applicant_profile[$custom_field['field_name']] = ph_clean($_POST['_applicant' . $custom_field['field_name'] . '_' . $applicant_profile_id]);
                            }
                            else
                            {
                                $applicant_profile[$custom_field['field_name']] = '';
                            }
                            break;
                        }
                    }
                }
            }
        }

        update_post_meta( $contact_post_id, '_applicant_profile_' . $applicant_profile_id, $applicant_profile );
    }

    public function applicant_requirements_display( $requirements, $contact_post_id, $applicant_profile )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    if ( isset($applicant_profile[$custom_field['field_name']]) )
                    {
                        switch ( $custom_field['field_type'] )
                        {
                            case "select":
                            case "checkbox":
                            {
                                if ( $applicant_profile[$custom_field['field_name']] != '' )
                                {
                                    $requirements[] = array(
                                        'label' => $custom_field['field_label'],
                                        'value' => ph_clean($applicant_profile[$custom_field['field_name']]),
                                    );
                                }
                                break;
                            }
                            case "multiselect":
                            {
                                if ( !is_array($applicant_profile[$custom_field['field_name']]) && $applicant_profile[$custom_field['field_name']] != '' )
                                {
                                    $applicant_profile[$custom_field['field_name']] = array($applicant_profile[$custom_field['field_name']]);
                                }

                                if ( !empty($applicant_profile[$custom_field['field_name']]) )
                                {
                                    $sliced_terms = array_slice( ph_clean($applicant_profile[$custom_field['field_name']]), 0, 2 );
                                    $requirements[] = array(
                                        'label' => $custom_field['field_label'],
                                        'value' => implode(", ", $sliced_terms) . ( (count($applicant_profile[$custom_field['field_name']]) > 2) ? '<span title="' . addslashes( implode(", ", $applicant_profile[$custom_field['field_name']]) ) .'"> + ' . (count($applicant_profile[$custom_field['field_name']]) - 2) . ' more</span>' : '' )
                                    );
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $requirements;
    }

    public function matching_properties_args( $args, $contact_post_id, $applicant_profile )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    if ( isset($applicant_profile[$custom_field['field_name']]) )
                    {
                        // ensure if field is specific to department it's taken into account, else ignored
                        if ( 
                            $custom_field['meta_box'] == 'property_residential_sales_details' 
                            ||
                            $custom_field['meta_box'] == 'property_residential_lettings_details' 
                            ||
                            $custom_field['meta_box'] == 'property_commercial_details' 
                        )
                        {
                            $meta_box_department = str_replace("property_", "", $custom_field['meta_box']);
                            $meta_box_department = str_replace("_details", "", $meta_box_department);
                            $meta_box_department = str_replace("_", "-", $meta_box_department);

                            if ( 
                                isset( $applicant_profile['department'] ) && 
                                ( $applicant_profile['department'] == $meta_box_department || ph_get_custom_department_based_on($applicant_profile['department']) == $meta_box_department )
                            )
                            {

                            }
                            else
                            {
                                continue;
                            }
                        }

                        switch ( $custom_field['field_type'] )
                        {
                            case "select":
                            {
                                if ( $applicant_profile[$custom_field['field_name']] != '' )
                                {
                                    $args['meta_query'][] = array(
                                        'key' => $custom_field['field_name'],
                                        'value' => $applicant_profile[$custom_field['field_name']],
                                    );
                                }
                                break;
                            }
                            case "multiselect":
                            {
                                if ( !is_array($applicant_profile[$custom_field['field_name']]) && $applicant_profile[$custom_field['field_name']] != '' )
                                {
                                    $applicant_profile[$custom_field['field_name']] = array($applicant_profile[$custom_field['field_name']]);
                                }

                                if ( !empty($applicant_profile[$custom_field['field_name']]) )
                                {
                                    $sub_meta_query = array(
                                        'relation' => 'OR'
                                    );

                                    foreach ( $applicant_profile[$custom_field['field_name']] as $option )
                                    {
                                        $sub_meta_query[] = array(
                                            'key' => $custom_field['field_name'],
                                            'value' => $option,
                                            'compare' => 'LIKE',
                                        );
                                    }
                                    
                                    $args['meta_query'][] = $sub_meta_query;
                                }
                                break;
                            }
                            case "checkbox":
                            {
                                if ( $custom_field['exact_match'] == '' )
                                {
                                    if ( $applicant_profile[$custom_field['field_name']] != '' )
                                    {
                                        $args['meta_query'][] = array(
                                            'key' => $custom_field['field_name'],
                                            'value' => $applicant_profile[$custom_field['field_name']],
                                        );
                                    }
                                }
                                else
                                {
                                    // should match exactly only
                                    if ( $applicant_profile[$custom_field['field_name']] == 'yes' )
                                    {
                                        $args['meta_query'][] = array(
                                            'key' => $custom_field['field_name'],
                                            'value' => 'yes',
                                        );
                                    }
                                    else
                                    {
                                        $args['meta_query'][] = array(
                                            'relation' => 'OR',
                                            array(
                                                'key' => $custom_field['field_name'],
                                                'value' => '',
                                            ),
                                            array(
                                                'key' => $custom_field['field_name'],
                                                'compare' => 'NOT EXISTS',
                                            )
                                        );

                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $args;
    }

    public function matching_applicants_check( $check, $property, $contact_post_id, $applicant_profile )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    // ensure if field is specific to department it's taken into account, else ignored
                    if ( 
                        $custom_field['meta_box'] == 'property_residential_sales_details' 
                        ||
                        $custom_field['meta_box'] == 'property_residential_lettings_details' 
                        ||
                        $custom_field['meta_box'] == 'property_commercial_details' 
                    )
                    {
                        $meta_box_department = str_replace("property_", "", $custom_field['meta_box']);
                        $meta_box_department = str_replace("_details", "", $meta_box_department);
                        $meta_box_department = str_replace("_", "-", $meta_box_department);

                        if ( 
                            isset( $applicant_profile['department'] ) && 
                            ( $applicant_profile['department'] == $meta_box_department || ph_get_custom_department_based_on($applicant_profile['department']) == $meta_box_department )
                        )
                        {

                        }
                        else
                        {
                            continue;
                        }
                    }

                    if ( isset($applicant_profile[$custom_field['field_name']]) )
                    {
                        switch ( $custom_field['field_type'] )
                        {
                            case "select":
                            {
                                if ( 
                                    $applicant_profile[$custom_field['field_name']] == '' ||
                                    $property->{$custom_field['field_name']} == $applicant_profile[$custom_field['field_name']]
                                )
                                {

                                }
                                else
                                {
                                    return false;
                                }
                                break;
                            }
                            case "multiselect":
                            {
                                if ( !is_array($applicant_profile[$custom_field['field_name']]) && $applicant_profile[$custom_field['field_name']] != '' )
                                {
                                    $applicant_profile[$custom_field['field_name']] = array($applicant_profile[$custom_field['field_name']]);
                                }

                                if ( empty($applicant_profile[$custom_field['field_name']]) )
                                {

                                }
                                else
                                {
                                    $property_values = !is_array($property->{$custom_field['field_name']}) ? array($property->{$custom_field['field_name']}) : $property->{$custom_field['field_name']};
                                    if ( empty($property_values) )
                                    {
                                        return false;
                                    }

                                    $applicant_values = $applicant_profile[$custom_field['field_name']];

                                    $value_exists = false;

                                    foreach ( $property_values as $property_value )
                                    {
                                        foreach ( $applicant_values as $applicant_value )
                                        {
                                            if ( $property_value == $applicant_value )
                                            {
                                                $value_exists = true;
                                            }
                                        }
                                    }

                                    if ( !$value_exists )
                                    {
                                        return false;
                                    }
                                }

                                break;
                            }
                            case "checkbox":
                            {
                                if ( $custom_field['exact_match'] == '' )
                                {
                                    // not exact match (i.e. pets allowed)
                                    if ( 
                                        $applicant_profile[$custom_field['field_name']] == '' ||
                                        $property->{$custom_field['field_name']} == $applicant_profile[$custom_field['field_name']]
                                    )
                                    {

                                    }
                                    else
                                    {
                                        return false;
                                    }
                                }
                                else
                                {
                                    // exact match
                                    if (
                                        $property->{$custom_field['field_name']} == $applicant_profile[$custom_field['field_name']]
                                    )
                                    {

                                    }
                                    else
                                    {
                                        return false;
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $check;
    }

    public function applicant_list_check( $check, $contact_post_id, $applicant_profile )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    // ensure if field is specific to department it's taken into account, else ignored
                    if ( 
                        $custom_field['meta_box'] == 'property_residential_sales_details' 
                        ||
                        $custom_field['meta_box'] == 'property_residential_lettings_details' 
                        ||
                        $custom_field['meta_box'] == 'property_commercial_details' 
                    )
                    {
                        $meta_box_department = str_replace("property_", "", $custom_field['meta_box']);
                        $meta_box_department = str_replace("_details", "", $meta_box_department);
                        $meta_box_department = str_replace("_", "-", $meta_box_department);

                        if ( 
                            isset( $_POST['department'] ) && 
                            ( $_POST['department'] == $meta_box_department || ph_get_custom_department_based_on($_POST['department']) == $meta_box_department )
                        )
                        {

                        }
                        else
                        {
                            continue;
                        }
                    }

                    
                    if ( isset($applicant_profile[$custom_field['field_name']]) )
                    {
                        switch ( $custom_field['field_type'] )
                        {
                            case "select":
                            {
                                if ( !empty($_POST[$custom_field['field_name']]) )
                                {
                                    if ( 
                                        $applicant_profile[$custom_field['field_name']] == '' ||
                                        $_POST[$custom_field['field_name']] == $applicant_profile[$custom_field['field_name']]
                                    )
                                    {

                                    }
                                    else
                                    {
                                        return false;
                                    }
                                }
                                break;
                            }
                            case "multiselect":
                            {
                                if ( !empty($_POST[$custom_field['field_name']]) )
                                {
                                    if ( !is_array($applicant_profile[$custom_field['field_name']]) && $applicant_profile[$custom_field['field_name']] != '' )
                                    {
                                        $applicant_profile[$custom_field['field_name']] = array($applicant_profile[$custom_field['field_name']]);
                                    }

                                    if ( empty($applicant_profile[$custom_field['field_name']]) )
                                    {

                                    }
                                    else
                                    {
                                        $property_values = $_POST[$custom_field['field_name']];
                                        if ( empty($property_values) )
                                        {
                                            return false;
                                        }

                                        $applicant_values = $applicant_profile[$custom_field['field_name']];

                                        $value_exists = false;

                                        foreach ( $property_values as $property_value )
                                        {
                                            foreach ( $applicant_values as $applicant_value )
                                            {
                                                if ( $property_value == $applicant_value )
                                                {
                                                    $value_exists = true;
                                                }
                                            }
                                        }

                                        if ( !$value_exists )
                                        {
                                            return false;
                                        }
                                    }
                                }

                                break;
                            }
                            case "checkbox":
                            {
                                if ( $custom_field['exact_match'] == '' )
                                {
                                    // not exact match (i.e. pets allowed)
                                    if ( 
                                        $applicant_profile[$custom_field['field_name']] == '' ||
                                        $_POST[$custom_field['field_name']] == $applicant_profile[$custom_field['field_name']]
                                    )
                                    {

                                    }
                                    else
                                    {
                                        return false;
                                    }
                                }
                                else
                                {
                                    // exact match
                                    if ( isset($_POST[$custom_field['field_name']]) )
                                    {
                                        if (
                                            $_POST[$custom_field['field_name']] == $applicant_profile[$custom_field['field_name']]
                                        )
                                        {

                                        }
                                        else
                                        {
                                            return false;
                                        }
                                    }
                                    else
                                    {
                                        if (
                                            '' == $applicant_profile[$custom_field['field_name']]
                                        )
                                        {

                                        }
                                        else
                                        {
                                            return false;
                                        }
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        return $check;
    }

    public function applicant_requirements_form_fields( $form_controls, $applicant_profile = false )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    switch ( $custom_field['field_type'] )
                    {
                        case "select":
                        case "multiselect":
                        {
                            $options = array('' => '');
                            foreach ($custom_field['dropdown_options'] as $dropdown_option)
                            {
                                $options[$dropdown_option] = ph_clean($dropdown_option);
                            }

                            $value = isset($applicant_profile[$custom_field['field_name']]) ? $applicant_profile[$custom_field['field_name']] : '';
                            /*if ( is_array($value) && !empty($value) )
                            {
                                $value = $value[0];
                            }*/

                            $form_controls[$custom_field['field_name']] = array(
                                'type' => 'select',
                                'label' => $custom_field['field_label'],
                                'required' => false,
                                'show_label' => true,
                                'value' => $value,
                                'options' => $options,
                                'multiselect' => $custom_field['field_type'] == 'multiselect' ? true : false
                            );

                            break;
                        }
                        case "checkbox":
                        {
                            $value = isset($applicant_profile[$custom_field['field_name']]) ? $applicant_profile[$custom_field['field_name']] : '';

                            $form_controls[$custom_field['field_name']] = array(
                                'type' => 'checkbox',
                                'label' => $custom_field['field_label'],
                                'required' => false,
                                'show_label' => true,
                                'value' => $value,
                            );

                            break;
                        }
                    }
                }
            }
        }

        return $form_controls;
    }

    public function applicant_registered( $contact_post_id, $user_id )
    {
        $applicant_profile = get_post_meta( $contact_post_id, '_applicant_profile_' . ( isset($_POST['profile_id']) && $_POST['profile_id'] != '' ? (int)$_POST['profile_id'] : '0' ), TRUE );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_applicant_requirements']) && $custom_field['display_on_applicant_requirements'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
                {
                    switch ( $custom_field['field_type'] )
                    {
                        case "select":
                        {
                            $applicant_profile[$custom_field['field_name']] = isset($_POST[$custom_field['field_name']]) ? ph_clean($_POST[$custom_field['field_name']]) : '';
                            break;
                        }
                        case "multiselect":
                        {
                            if ( isset($_POST[$custom_field['field_name']]) )
                            {
                                if ( !is_array($_POST[$custom_field['field_name']]) )
                                {
                                    $_POST[$custom_field['field_name']] = array($_POST[$custom_field['field_name']]);
                                }
                            }
                            $applicant_profile[$custom_field['field_name']] = isset($_POST[$custom_field['field_name']]) ? $_POST[$custom_field['field_name']] : array();
                            break;
                        }
                        case "checkbox":
                        {
                            $applicant_profile[$custom_field['field_name']] = isset($_POST[$custom_field['field_name']]) ? ph_clean($_POST[$custom_field['field_name']]) : '';
                            break;
                        }
                    }
                }
            }
        }

        update_post_meta( $contact_post_id, '_applicant_profile_' . ( isset($_POST['profile_id']) && $_POST['profile_id'] != '' ? (int)$_POST['profile_id'] : '0' ), $applicant_profile );
    }

    public function add_custom_fields_to_room_breakdown( $room_data, $post_id, $room )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( isset($custom_field['display_on_website']) && $custom_field['display_on_website'] == '1' && $custom_field['meta_box'] == 'property_rooms_breakdown' )
                {
                    if ( $room->{$custom_field['field_name']} != '' )
                    {
                        $room_data[] = array(
                            'class' => sanitize_title($custom_field['field_name']),
                            'label' => __( $custom_field['field_label'], 'propertyhive' ),
                            'value' => $room->{$custom_field['field_name']}
                        );
                    }
                }
            }
        }

        return $room_data;
    }

    public function additional_field_elementor_widget( $widgets )
    {
        global $property;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $custom_fields = ( (isset($current_settings['custom_fields'])) ? $current_settings['custom_fields'] : array() );

        foreach ( $custom_fields as $custom_field )
        {
            if ( substr($custom_field['meta_box'], 0, 9) == 'property_' )
            {
                $widgets[] = 'Property Additional Field';
            }
        }

        return $widgets;
    }

    public function additional_field_elementor_widget_dir( $widget_dir, $widget )
    {
        if ( $widget == 'Property Additional Field' )
        {
            $widget_dir = 'elementor-widgets';
            $widget_dir = dirname(__FILE__) . "/includes/" . $widget_dir;
        }

        return $widget_dir;
    }

    private function document_custom_fields_merge_tags($merge_tags, $post_id, $post_type)
    {
        // Add additional custom field names to tags array
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                // Only add fields that are related to the current section of the merge
                if ( substr($custom_field['meta_box'], 0, strlen($post_type)+1) === $post_type . '_' ) {
                    // Add field name twice to cater for tag with and without preceding underscore
                    $merge_tags[] = $custom_field['field_name'];
                    $merge_tags[] = ltrim( $custom_field['field_name'], '_' );
                }
            }
        }
        return $merge_tags;
    }

    public function document_property_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'property');
    }

    public function document_applicant_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'applicant');
    }

    public function document_owner_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'owner');
    }

    public function document_contact_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'contact');
    }

    public function document_appraisal_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'appraisal');
    }

    public function document_viewing_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'viewing');
    }

    public function document_offer_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'offer');
    }

    public function document_sale_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'sale');
    }

    public function document_negotiator_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'negotiator');
    }

    public function document_general_custom_fields_merge_tags($merge_tags, $post_id)
    {
        return $this->document_custom_fields_merge_tags($merge_tags, $post_id, 'general');
    }

    private function document_custom_fields_merge_values($merge_values, $post_id, $post_type)
    {
        // Add additional custom fields values to tags values array
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                // Only add fields that are related to the current section of the merge
                if ( substr($custom_field['meta_box'], 0, strlen($post_type)+1) === $post_type . '_' ) {
                    // Add value twice to cater for tag with and without preceding underscore
                    $merge_values[] = htmlspecialchars(get_post_meta( $post_id, $custom_field['field_name'], TRUE ));
                    $merge_values[] = htmlspecialchars(get_post_meta( $post_id, $custom_field['field_name'], TRUE ));
                }
            }
        }
        return $merge_values;
    }

    public function document_property_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'property');
    }

    public function document_applicant_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'applicant');
    }

    public function document_owner_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'owner');
    }

    public function document_contact_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'contact');
    }

    public function document_appraisal_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'appraisal');
    }

    public function document_viewing_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'viewing');
    }

    public function document_offer_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'offer');
    }

    public function document_sale_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'sale');
    }

    public function document_negotiator_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'negotiator');
    }

    public function document_general_custom_fields_merge_values($merge_values, $post_id)
    {
        return $this->document_custom_fields_merge_values($merge_values, $post_id, 'general');
    }

    public function custom_fields_in_meta_query( $meta_query )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                if ( 
                    $custom_field['meta_box'] == 'property_residential_sales_details' 
                    ||
                    $custom_field['meta_box'] == 'property_residential_lettings_details' 
                    ||
                    $custom_field['meta_box'] == 'property_commercial_details' 
                )
                {
                    // this is a department specific field. Make sure department in question in being searched
                    $meta_box_department = str_replace("property_", "", $custom_field['meta_box']);
                    $meta_box_department = str_replace("_details", "", $meta_box_department);
                    $meta_box_department = str_replace("_", "-", $meta_box_department);

                    if ( 
                        isset( $_REQUEST['department'] ) && 
                        ( $_REQUEST['department'] == $meta_box_department || ph_get_custom_department_based_on($_REQUEST['department']) == $meta_box_department )
                    )
                    {

                    }
                    else
                    {
                        continue;
                    }
                }

                if ( $custom_field['field_type'] == 'checkbox' )
                {
                    if ( $custom_field['exact_match'] == '' )
                    {
                        // not exact match (i.e. pets allowed)
                        if ( isset($_REQUEST[$custom_field['field_name']]) && ph_clean( $_REQUEST[$custom_field['field_name']] ) == 'yes' )
                        {
                            $meta_query[] = array(
                                'key'     => $custom_field['field_name'],
                                'value'   => ph_clean( $_REQUEST[$custom_field['field_name']] ),
                            );
                        }
                    }
                    else
                    {
                        // should match exactly only (i.e. something only)
                        if ( isset($_REQUEST[$custom_field['field_name']]) && ph_clean( $_REQUEST[$custom_field['field_name']] ) == 'yes' )
                        {
                            $meta_query[] = array(
                                'key' => $custom_field['field_name'],
                                'value' => 'yes',
                            );
                        }
                        else
                        {
                            $meta_query[] = array(
                                'relation' => 'OR',
                                array(
                                    'key' => $custom_field['field_name'],
                                    'value' => '',
                                ),
                                array(
                                    'key' => $custom_field['field_name'],
                                    'compare' => 'NOT EXISTS',
                                )
                            );

                        }
                    }
                }
                else
                {
                    if ( 
                        isset( $_REQUEST[$custom_field['field_name']] ) && $_REQUEST[$custom_field['field_name']] != '' 
                    )
                    {
                        if ( 
                            ( $custom_field['field_type'] == 'select' || $custom_field['field_type'] == 'multiselect' ) &&
                            is_array($_REQUEST[$custom_field['field_name']])
                        )
                        {
                            $sub_meta_query = array('relation' => 'OR');
                            foreach ( $_REQUEST[$custom_field['field_name']] as $value )
                            {
                                $sub_meta_query[] = array(
                                    'key'     => $custom_field['field_name'],
                                    'value'   => ph_clean( $value ),
                                    'compare' => 'LIKE',
                                );
                            }
                            $meta_query[] = $sub_meta_query;
                        }
                        elseif ( $custom_field['field_type'] == 'select' )
                        {
                            $meta_query[] = array(
                                'key'     => $custom_field['field_name'],
                                'value'   => ph_clean( $_REQUEST[$custom_field['field_name']] ),
                                'compare' => '=',
                            );
                        }
                        else
                        {
                            $meta_query[] = array(
                                'key'     => $custom_field['field_name'],
                                'value'   => ph_clean( $_REQUEST[$custom_field['field_name']] ),
                                'compare' => 'LIKE',
                            );
                        }
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
            if ( isset($custom_field['display_on_website']) && $custom_field['display_on_website'] == '1' && substr($custom_field['meta_box'], 0, 9) == 'property_' )
            {
                $label = '<span class="' . trim($custom_field['field_name'], '_') . '_label">' . $custom_field['field_label'] . ': </span>';

                if ( $custom_field['field_type'] == 'multiselect' )
                {
                    $values = get_post_meta( $property->id, $custom_field['field_name'], TRUE );

                    if ( !empty($values) )
                    {
                        echo '<li class="' . trim($custom_field['field_name'], '_') . '">' . $label;
                        echo is_array($values) ? implode(", ", $values) : $values;
                    }
                }
                elseif ( $custom_field['field_type'] == 'date' )
                {
                    if ( $property->{$custom_field['field_name']} != '' )
                    {
                        ?>
                        <li class="<?php echo trim($custom_field['field_name'], '_'); ?>">
                            <?php echo $label . date(get_option( 'date_format' ), strtotime($property->{$custom_field['field_name']})); ?>
                        </li>
                        <?php
                    }
                }
                elseif ( $custom_field['field_type'] == 'image' )
                {
                    if ( $property->{$custom_field['field_name']} != '' )
                    {
                        ?>
                        <li class="<?php echo trim($custom_field['field_name'], '_'); ?>">
                            <?php echo $label . wp_get_attachment_image($property->{$custom_field['field_name']}); ?>
                        </li>
                        <?php
                        }
                }
                elseif ( $custom_field['field_type'] == 'file' )
                {
                    if ( $property->{$custom_field['field_name']} != '' )
                    {
                        ?>
                        <li class="<?php echo trim($custom_field['field_name'], '_'); ?>">
                            <?php echo $label . '<a href="' . wp_get_attachment_url($property->{$custom_field['field_name']}) . '" target="_blank">' . __( 'View', 'propertyhive' ) . '</a>'; ?>
                        </li>
                        <?php
                    }
                }
                else
                {
                    if ( $property->{$custom_field['field_name']} != '' )
                    {
                        $value = trim( $property->{$custom_field['field_name']} );

                        // If the custom field value is an email address or a URL, automatically make it a link
                        if ( apply_filters( 'propertyhive_auto_hyperlink_custom_fields', true ) )
                        {
                            if ( filter_var($value, FILTER_VALIDATE_URL) )
                            {
                                $value = '<a href="' . $value . '" target="_blank">' . $value . '</a>';
                            }
                            elseif ( filter_var($value, FILTER_VALIDATE_EMAIL) )
                            {
                                $value = '<a href="mailto:' . $value . '">' . $value . '</a>';
                            }
                        }
                        ?>
                        <li class="<?php echo trim($custom_field['field_name'], '_'); ?>">
                            <?php echo $label . $value; ?>
                        </li>
                        <?php
                    }
                }
            }
        }
    }

    public function display_custom_fields_on_user_details( $form_controls )
    {
        if ( is_user_logged_in() )
        {
            $current_user = wp_get_current_user();

            if ( $current_user instanceof WP_User )
            {
                $contact = new PH_Contact( '', $current_user->ID );
            }
        }

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $custom_fields = ( (isset($current_settings['custom_fields'])) ? $current_settings['custom_fields'] : array() );

        foreach ( $custom_fields as $custom_field )
        {
            if ( isset($custom_field['display_on_user_details']) && $custom_field['display_on_user_details'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'contact_' )
            {
                $form_controls[$custom_field['field_name']] = array(
                    'type' => $custom_field['field_type'],
                    'label' => $custom_field['field_label'],
                );

                if ( is_user_logged_in() && $current_user instanceof WP_User )
                {
                    $form_controls[$custom_field['field_name']]['value'] = $contact->{$custom_field['field_name']};
                }
                
                switch ( $custom_field['field_type'] )
                {
                    case 'select':
                    case 'multiselect':
                    {
                        $options = array('' => '');
                        if ( isset($custom_field['dropdown_options']) && is_array($custom_field['dropdown_options']) && !empty($custom_field['dropdown_options']) )
                        {
                            foreach ( $custom_field['dropdown_options'] as $dropdown_option )
                            {
                                $options[$dropdown_option] = $dropdown_option;
                            }
                        }
                        $form_controls[$custom_field['field_name']]['options'] = $options;
                        break;
                    }
                }
            }
        }
        return $form_controls;
    }

    public function save_custom_fields_on_user_details( $contact_post_id, $user_id )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $custom_fields = ( (isset($current_settings['custom_fields'])) ? $current_settings['custom_fields'] : array() );

        foreach ( $custom_fields as $custom_field )
        {
            if ( isset($custom_field['display_on_user_details']) && $custom_field['display_on_user_details'] == '1' && substr($custom_field['meta_box'], 0, 8) == 'contact_' )
            {
                update_post_meta( $contact_post_id, $custom_field['field_name'], (isset($_POST[$custom_field['field_name']]) ? $_POST[$custom_field['field_name']] : '') );
            }
        }
    }

}