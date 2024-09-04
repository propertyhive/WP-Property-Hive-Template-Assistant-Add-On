<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PH_Template_Assistant_Search_Results {

	public function __construct() {

		$current_settings = get_option( 'propertyhive_template_assistant', array() );

		add_filter( 'loop_search_results_per_page',  array( $this, 'template_assistant_loop_search_results_per_page' ) );
        add_filter( 'loop_search_results_columns', array( $this, 'template_assistant_search_result_columns' ) );
        add_filter( 'honeycomb_loop_columns', array( $this, 'template_assistant_search_result_columns' ) );
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

        // template specific function
        add_filter( 'ph_get_template_part', array( $this, 'template_loader' ), 10, 3 );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_template_assistant_template_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'load_template_assistant_template_styles' ) );
	}

    public function load_template_assistant_template_styles()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $suffix = '';
        $plugin_url = plugins_url( '', dirname( __FILE__ ) );
        $assets_path = untrailingslashit( str_replace( array( 'http:', 'https:' ), '', $plugin_url ) ) . '/assets/';

        if ( isset($current_settings['search_result_layout']) )
        {
            if ( $current_settings['search_result_layout'] == '1' )
            {
                // List
                if ( isset($current_settings['search_result_template_list']) && !empty($current_settings['search_result_template_list']) )
                {
                    wp_enqueue_style( 'propertyhive_template_assistant_template_', $assets_path . 'css/template-list-' . (int)$current_settings['search_result_template_list'] . $suffix . '.css', array(), PH_TEMPLATE_ASSISTANT_VERSION );
                }
            }

            if ( $current_settings['search_result_layout'] == '2' )
            {
                // Card
                if ( isset($current_settings['search_result_template_card']) && !empty($current_settings['search_result_template_card']) )
                {
                    wp_enqueue_style( 'propertyhive_template_assistant_template_', $assets_path . 'css/template-card-' . (int)$current_settings['search_result_template_card'] . $suffix . '.css', array(), PH_TEMPLATE_ASSISTANT_VERSION );
                }
            }
        }
    }

    public function template_loader( $template, $slug, $name )
    {
        // NEED TO CATER FOR ALL TEMPLATES, NOT JUST content-property, but also content-property-featured etc
        // MAYBE ADD FILTERS

        if ( $slug == 'content' && $name == 'property' )
        {
            // Loading the content-property.php template

            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            if ( isset($current_settings['search_result_layout']) )
            {
                if ( $current_settings['search_result_layout'] == '1' )
                {
                    // List
                    if ( isset($current_settings['search_result_template_list']) && !empty($current_settings['search_result_template_list']) )
                    {
                        // Need to load custom template
                        /*if ( file_exists( dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . "/templates/{$slug}-{$name}-list-{$current_settings['search_result_template_card']}.php" ) ) {
                            $template = dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . "/templates/{$slug}-{$name}-list-{$current_settings['search_result_template_card']}.php";
                        }*/
                    }
                }

                if ( $current_settings['search_result_layout'] == '2' )
                {
                    if ( isset($current_settings['search_result_template_card']) && !empty($current_settings['search_result_template_card']) )
                    {
                        // Need to load custom template
                        if ( file_exists( dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . "/templates/{$slug}-{$name}-card.php" ) )
                        {
                            add_action( 'propertyhive_before_search_results_loop_item_title', array( $this, 'media_counts' ), 1 );

                            if ( has_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_actions' ) !== false ) 
                            {
                                for ( $priority = 0; $priority <= 100; ++$priority ) 
                                {
                                    remove_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_actions', $priority );
                                }
                            }

                            $template = dirname(PH_TEMPLATE_ASSISTANT_PLUGIN_FILE) . "/templates/{$slug}-{$name}-card.php";
                        }
                    }
                }
            }
        }

        return $template;
    }

    public function media_counts()
    {
        global $property;

        $images = array();
        if ( get_option('propertyhive_images_stored_as', '') == 'urls' )
        {
            if ( !empty($property->photo_urls) )
            {
                $images = $property->photo_urls;
            }
        }
        else
        {
            if ( !empty($property->photos) )
            {
                $images = $property->photos;
            }
        }

        $floorplans = array();
        if ( get_option('propertyhive_floorplans_stored_as', '') == 'urls' )
        {
            if ( !empty($property->floorplan_urls) )
            {
                $floorplans = $property->floorplan_urls;
            }
        }
        else
        {
            if ( !empty($property->floorplans) )
            {
                $floorplans = $property->floorplans;
            }
        }

        $virtual_tours = $property->get_virtual_tours();

        if ( !empty($images) || !empty($floorplans) || !empty($virtual_tours) )
        {
            echo '<div class="media-counts">';
            if ( !empty($floorplans) )
            {
                echo '<div><svg fill="none" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><path d="M10 .5h4.5v14H.5V.5h4l3 2m-1 12v-7M4 7.5h5m3 0h2.5" stroke="#ffffff" class="stroke-000000"></path></svg></div>';
            }
            if ( !empty($virtual_tours) )
            {
                echo '<div><svg fill="none" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><path d="m6.5 5.5.248-.434A.5.5 0 0 0 6 5.5h.5Zm0 4H6a.5.5 0 0 0 .748.434L6.5 9.5Zm3.5-2 .248.434a.5.5 0 0 0 0-.868L10 7.5ZM7.5 14A6.5 6.5 0 0 1 1 7.5H0A7.5 7.5 0 0 0 7.5 15v-1ZM14 7.5A6.5 6.5 0 0 1 7.5 14v1A7.5 7.5 0 0 0 15 7.5h-1ZM7.5 1A6.5 6.5 0 0 1 14 7.5h1A7.5 7.5 0 0 0 7.5 0v1Zm0-1A7.5 7.5 0 0 0 0 7.5h1A6.5 6.5 0 0 1 7.5 1V0ZM6 5.5v4h1v-4H6Zm.748 4.434 3.5-2-.496-.868-3.5 2 .496.868Zm3.5-2.868-3.5-2-.496.868 3.5 2 .496-.868Z" fill="#ffffff" class="fill-000000"></path></svg></div>';
            }
            if ( !empty($images) )
            {
                echo '<div><svg fill="none" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg"><path d="m4.5 3.5.354-.354a.5.5 0 0 0-.708 0L4.5 3.5ZM1.5 1h12V0h-12v1Zm12.5.5v12h1v-12h-1ZM13.5 14h-12v1h12v-1ZM1 13.5v-12H0v12h1Zm.5.5a.5.5 0 0 1-.5-.5H0A1.5 1.5 0 0 0 1.5 15v-1Zm12.5-.5a.5.5 0 0 1-.5.5v1a1.5 1.5 0 0 0 1.5-1.5h-1ZM13.5 1a.5.5 0 0 1 .5.5h1A1.5 1.5 0 0 0 13.5 0v1Zm-12-1A1.5 1.5 0 0 0 0 1.5h1a.5.5 0 0 1 .5-.5V0Zm-1 11h14v-1H.5v1Zm.354-3.146 4-4-.708-.708-4 4 .708.708Zm3.292-4 7 7 .708-.708-7-7-.708.708ZM10.5 5a.5.5 0 0 1-.5-.5H9A1.5 1.5 0 0 0 10.5 6V5Zm.5-.5a.5.5 0 0 1-.5.5v1A1.5 1.5 0 0 0 12 4.5h-1Zm-.5-.5a.5.5 0 0 1 .5.5h1A1.5 1.5 0 0 0 10.5 3v1Zm0-1A1.5 1.5 0 0 0 9 4.5h1a.5.5 0 0 1 .5-.5V3Z" fill="#ffffff" class="fill-000000"></path></svg></div>';
            }
            echo '</div>';
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

        // Layouts - List
        $layouts = array(
            '1' => array(
                'name' => 'Simple',
                'thumbnail' => '',
            ),
            '2' => array(
                'name' => 'Multi-Picture',
                'thumbnail' => '',
            )
        );

        $html = '<style type="text/css">
            #row_search_result_template_list {  }
            #row_search_result_template_list .layouts {  }
            #row_search_result_template_list .layouts .layout { display:inline-block; margin-right:20px; }
            #row_search_result_template_list .layouts .layout input[type="radio"] { display:none; }
            #row_search_result_template_list .layouts .layout label { border:1px solid #CCC; padding:10px; cursor:pointer; }
            #row_search_result_template_list .layouts .layout  input[type="radio"]:checked + label { box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25); border-color: #2271b1; }
        </style>';

        $html .= '<div class="layouts">';
            $html .= '<div class="layout no-layout"><input type="radio" name="search_result_template_list" id="search_result_template_list_none" value=""' . ( ( !isset($current_settings['search_result_template_list']) || (isset($current_settings['search_result_template_list']) && $current_settings['search_result_template_list'] == '' ) ) ? ' checked' : '' ) . '><label for="search_result_template_list_none"><img src="" alt=""></label></div>';
            foreach ( $layouts as $i => $layout )
            {
                $html .= '<div class="layout"><input type="radio" name="search_result_template_list" id="search_result_template_list_' . $i . '" value="' . $i . '"' . ( (isset($current_settings['search_result_template_list']) && $current_settings['search_result_template_list'] == $i ) ? ' checked' : '' ) . '><label for="search_result_template_list_' . $i . '"><img src="' . $layout['thumbnail'] . '" alt=""></label></div>';
            }
        $html .= '</div>';

        $settings[] = array(
            'title' => __( 'Template', 'propertyhive' ),
            'id'        => 'search_result_template_list',
            'type'      => 'html',
            'html' => $html
        );

        // Layouts - Card
        $layouts = array(
            '1' => array(
                'name' => 'Simple',
                'thumbnail' => '',
            ),
            '2' => array(
                'name' => 'Multi-Picture',
                'thumbnail' => '',
            )
        );

        $html = '<style type="text/css">
            #row_search_result_template_card {  }
            #row_search_result_template_card .layouts {  }
            #row_search_result_template_card .layouts .layout { display:inline-block; margin-right:20px; }
            #row_search_result_template_card .layouts .layout input[type="radio"] { display:none; }
            #row_search_result_template_card .layouts .layout label { border:1px solid #CCC; padding:10px; cursor:pointer; }
            #row_search_result_template_card .layouts .layout  input[type="radio"]:checked + label { box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25); border-color: #2271b1; }
        </style>';

        $html .= '<div class="layouts">';
            $html .= '<div class="layout no-layout"><input type="radio" name="search_result_template_card" id="search_result_template_card_none" value=""' . ( ( !isset($current_settings['search_result_template_card']) || (isset($current_settings['search_result_template_card']) && $current_settings['search_result_template_card'] == '' ) ) ? ' checked' : '' ) . '><label for="search_result_template_card_none"><img src="" alt=""></label></div>';
            foreach ( $layouts as $i => $layout )
            {
                $html .= '<div class="layout"><input type="radio" name="search_result_template_card" id="search_result_template_card_' . $i . '" value="' . $i . '"' . $i . '"' . ( (isset($current_settings['search_result_template_card']) && $current_settings['search_result_template_card'] == $i ) ? ' checked' : '' ) . '><label for="search_result_template_card_' . $i . '"><img src="' . $layout['thumbnail'] . '" alt=""></label></div>';
            }
        $html .= '</div>';

        $settings[] = array(
            'title' => __( 'Template', 'propertyhive' ),
            'id'        => 'search_result_template_card',
            'type'      => 'html',
            'html'      => $html
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

                ph_show_template_rows();

                jQuery(\'#search_result_layout\').change(function()
                {
                    ph_show_template_rows();
                });
            });

            function ph_show_template_rows()
            {
                var layout = jQuery(\'#search_result_layout\').val();

                jQuery(\'#row_search_result_template_list\').hide();
                jQuery(\'#row_search_result_template_card\').hide();

                if ( layout == \'1\' ) // List
                {
                    jQuery(\'#row_search_result_template_list\').show();
                }
                if ( layout == \'2\' ) // Card
                {
                    jQuery(\'#row_search_result_template_card\').show();
                }
            }
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

        if ( isset($current_settings['search_result_columns']) && in_array((int)$current_settings['search_result_columns'], array(3,4)) )
        {
            return 12;
        }

        return $cols;
    }

    public function template_assistant_search_result_columns( $cols = 1 )
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( isset($current_settings['search_result_columns']) && in_array((int)$current_settings['search_result_columns'], array(1,2,3,4)) )
        {
            return (int)$current_settings['search_result_columns'];
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