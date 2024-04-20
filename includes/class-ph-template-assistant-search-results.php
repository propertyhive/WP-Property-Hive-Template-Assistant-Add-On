<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PH_Template_Assistant_Search_Results {

	public function __construct() {

		$current_settings = get_option( 'propertyhive_template_assistant', array() );

		add_filter( 'loop_search_results_per_page',  array( $this, 'template_assistant_loop_search_results_per_page' ) );
        add_filter( 'loop_search_results_columns', array( $this, 'template_assistant_search_result_columns' ) );
        add_filter( 'post_class', array( $this, 'template_assistant_property_columns_post_class'), 20, 3 );

        if ( isset($current_settings['search_result_default_order']) && $current_settings['search_result_default_order'] != '' )
        {
            add_filter('propertyhive_default_search_results_orderby', array( $this, 'template_assistant_change_default_order'));
        }

        if ( isset($current_settings['search_result_fields']) && is_array($current_settings['search_result_fields']) && !empty($current_settings['search_result_fields']) )
        {
            add_action( 'init', array( $this, 'search_result_field_changes' ) );
        }

        if ( isset($current_settings['search_result_image_size']) && $current_settings['search_result_image_size'] != '' )
        {
            add_filter( 'property_search_results_thumbnail_size', array( $this, 'search_result_image_size_changes' ) );
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
            'title' => __( 'Default Sort Order', 'propertyhive' ),
            'id'        => 'search_result_default_order',
            'type'      => 'select',
            'default'   => ( isset($current_settings['search_result_default_order']) ? $current_settings['search_result_default_order'] : ''),
            'options'   => array(
                '' => 'Price Descending (' . __( 'default', 'propertyhive') . ')',
                'price-asc' => 'Price Ascending',
                'date' => 'Date Added',
            )
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

        $search_result_fields = array( 'price', 'floor_area', 'summary', 'actions' );
        if ( isset($current_settings['search_result_fields']) && is_array($current_settings['search_result_fields']) )
        {
            if ( !empty($current_settings['search_result_fields']) )
            {
                $search_result_fields = $current_settings['search_result_fields'];
            }
            else
            {
                $search_result_fields = array();
            }
        }

        $fields = array(
            array( 'id' => 'price', 'label' => 'Price / Rent' ),
            array( 'id' => 'floor_area', 'label' => 'Floor Area (commercial only)' ),
            array( 'id' => 'summary', 'label' => 'Summary Description' ),
            array( 'id' => 'actions', 'label' => 'Actions (i.e. More Details Button)' ),
            array( 'id' => 'rooms', 'label' => 'Rooms Counts' ),
            array( 'id' => 'availability', 'label' => 'Availability' ),
            array( 'id' => 'property_type', 'label' => 'Property Type' ),
            array( 'id' => 'available_date', 'label' => 'Available Date (lettings only)' ),
        );
        $custom_field_selected = false;
        if ( isset($current_settings['custom_fields']) && is_array($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            $label = '<select name="search_result_fields_custom_field"><option value="">Custom Field...</option>';
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                $label .= '<option value="custom_field' . $custom_field['field_name'] . '"';
                if ( in_array('custom_field' . $custom_field['field_name'], $search_result_fields) )
                {
                    $label .= ' selected';
                    $custom_field_selected = true;
                }
                $label .= '>' . $custom_field['field_label'] . '</option>';
            }
            $label .= '</select>';

            $fields[] = array( 'id' => 'custom_field', 'label' => $label );
        }

        // Need to sort order to match what's saved
        foreach ( $search_result_fields as $j => $search_result_field )
        {
            foreach ( $fields as $i => $field )
            {
                if ( $field['id'] == $search_result_field || ( $field['id'] == 'custom_field' && substr($search_result_field, 0, 12) == 'custom_field' ) )
                {
                    $fields[$i]['order'] = $j;
                }
            }
        }
        foreach ( $fields as $i => $field )
        {
            if ( !isset($field['order']) )
            {
                $fields[$i]['order'] = $i + 99;
            }
        }

        // order $fields by 'order' key
        $sorter = array();
        $ret = array();
        reset($fields);
        foreach ($fields as $ii => $va) 
        {
            $sorter[$ii] = $va['order'];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) 
        {
            $ret[$ii] = $fields[$ii];
        }
        $fields = $ret;

        $html = '<span class="form-field-options" id="sortable_options">';
        foreach ( $fields as $field )
        {
            $html .= '<span style="display:block; padding:3px 0;">
                <i class="fa fa-reorder" style="cursor:pointer; opacity:0.3"></i> &nbsp;
                <input type="checkbox" name="search_result_fields[]" value="' . $field['id'] . '"';
            if ( in_array($field['id'], $search_result_fields) || ( $field['id'] == 'custom_field' && $custom_field_selected ) )
            {
                $html .= ' checked';
            }
            $html .= '>
                ' . $field['label'] . '
            </span>';
        }
        $html .= '</span>

        <script>
            jQuery(document).ready(function($)
            {
                $( "#sortable_options" )
                .sortable({
                    axis: "y",
                    handle: "i",
                    stop: function( event, ui ) 
                    {
                        // IE doesn\'t register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        //ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        //$( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable(\'toArray\');
                        
                        //$(\'#active_fields_order\').val( fields_order.join("|") );
                    }
                });
            });
        </script>';

        $settings[] = array(
            'title' => __( 'Fields Shown', 'propertyhive' ),
            'type'      => 'html',
            'html'      => $html
        );

        if ( get_option('propertyhive_images_stored_as', '') != 'urls' )
        {
            $image_sizes = get_intermediate_image_sizes();
            $image_size_options = array();
            foreach ( $image_sizes as $image_size )
            {
                $image_size_options[$image_size] = $image_size;
            }

            $settings[] = array(
                'title' => __( 'Image Size Used', 'propertyhive' ),
                'id'        => 'search_result_image_size',
                'type'      => 'select',
                'default'   => ( isset($current_settings['search_result_image_size']) ? $current_settings['search_result_image_size'] : 'medium'),
                'options'   => $image_size_options
            );
        }

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
            'title' => __( 'Apply CSS To All Pages', 'propertyhive' ),
            'id'        => 'search_result_css_all_pages',
            'type'      => 'checkbox',
            'default'   => isset($current_settings['search_result_css_all_pages']) && $current_settings['search_result_css_all_pages'] == 'yes' ? 'yes' : '',
        );

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

    public function template_assistant_change_default_order()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        return $current_settings['search_result_default_order'];
    }

    public function search_result_image_size_changes( $image_size )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['search_result_image_size']) && $current_settings['search_result_image_size'] != '' )
        {
            $image_size = $current_settings['search_result_image_size'];
        }

        return $image_size;
    }

    public function search_result_field_changes()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        remove_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_floor_area', 5 );
        remove_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_price', 10 );
        remove_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_summary', 20 );
        remove_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_actions', 30 );

        if ( !empty($current_settings['search_result_fields']) )
        {
            $priority = 5;
            foreach ( $current_settings['search_result_fields'] as $search_result_field )
            {
                if ( substr($search_result_field, 0, 12) == 'custom_field' )
                {
                    // custom field output here
                    $custom_field = substr($search_result_field, 12);

                    add_action( 'propertyhive_after_search_results_loop_item_title', array($this, 'propertyhive_template_loop_custom_field'), $priority );

                    $priority += 5;
                    continue;
                }

                switch ( $search_result_field )
                {
                    case "price":
                    case "floor_area":
                    case "summary":
                    case "actions": 
                    {
                        add_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_' . $search_result_field, $priority );
                        break;
                    }
                    case "availability":
                    {
                        add_action( 'propertyhive_after_search_results_loop_item_title', function() { global $property; echo '<div class="availability">' . $property->availability . '</div>'; }, $priority );
                        break;
                    }
                    case "property_type":
                    {
                        add_action( 'propertyhive_after_search_results_loop_item_title', function() { global $property; echo '<div class="property-type">' . $property->property_type . '</div>'; }, $priority );
                        break;
                    }
                    case "available_date":
                    {
                        add_action( 'propertyhive_after_search_results_loop_item_title', function() { global $property; if ( $property->department == 'residential-lettings' && $property->get_available_date() != '' ) { echo '<div class="available-date">' . $property->get_available_date() . '</div>'; } }, $priority );
                        break;
                    }
                    case "rooms":
                    {
                        add_action( 'propertyhive_after_search_results_loop_item_title', function() { 
                            global $property; 

                            if ( ($property->bedrooms != '' && $property->bedrooms != '0') || ($property->bathrooms != '' && $property->bathrooms != '0') || ($property->reception_rooms != '' && $property->reception_rooms != '0') )
                            {
                                echo '<div class="rooms">';
                                if ( $property->bedrooms != '' && $property->bedrooms != '0' ) { echo '<div class="room room-bedrooms"><span class="room-count">' . $property->bedrooms . '</span> <span class="room-label">Bedroom' . ( $property->bedrooms != 1 ? 's' : '' ) . '</span></div>'; }
                                if ( $property->bathrooms != '' && $property->bathrooms != '0' ) { echo '<div class="room room-bathrooms"><span class="room-count">' . $property->bathrooms . '</span> <span class="room-label">Bathroom' . ( $property->bathrooms != 1 ? 's' : '' ) . '</span></div>'; }
                                if ( $property->reception_rooms != '' && $property->reception_rooms != '0' ) { echo '<div class="room room-receptions"><span class="room-count">' . $property->reception_rooms . '</span> <span class="room-label">Reception' . ( $property->reception_rooms != 1 ? 's' : '' ) . '</span></div>'; }
                                echo '</div>'; 
                            }
                        }, $priority );
                        break;
                    }
                    default:
                    {
                        echo 'unknown search result field requested';
                    }
                }

                $priority += 5;
            }
        }
    }

    public function propertyhive_template_loop_custom_field()
    {
        global $property; 

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        foreach ( $current_settings['search_result_fields'] as $search_result_field )
        {
            if ( substr($search_result_field, 0, 12) == 'custom_field' )
            {
                // custom field output here
                $custom_field = substr($search_result_field, 12);

                $value = $property->{$custom_field};
                $value = is_array($value) ? implode(", ", $value) : $value;

                if ( $value != '' )
                {
                    echo '<div class="custom-field custom-field-' . sanitize_title(trim($custom_field, "_")) . '">' . $value . '</div>';
                }
            }
        }
    }

}