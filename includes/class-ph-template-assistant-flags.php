<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PH_Template_Assistant_Flags {

	public function __construct() {

		$current_settings = get_option( 'propertyhive_template_assistant', array() );

		if ( isset($current_settings['flags_active']) && $current_settings['flags_active'] == '1' )
        {
            add_action( 'propertyhive_before_search_results_loop_item_title', array( $this, 'add_flag' ), 15 );
        }
        if ( isset($current_settings['flags_active_single']) && $current_settings['flags_active_single'] == '1' )
        {
            add_action( 'propertyhive_before_single_property_images', array( $this, 'add_flag_single' ), 5 );
        }

		add_action( 'propertyhive_elementor_widget_property_image_controls', array( $this, 'elementor_widget_property_image_controls' ), 10 );
        add_action( 'propertyhive_elementor_widget_property_image_render_after', array( $this, 'elementor_widget_property_image_render_after' ), 10, 2 );
	}

	private function get_flag()
    {
        global $property;

        $flag = $property->availability;

        if ( $property->marketing_flag != '' )
        {
            $flag = $property->marketing_flag;
        }

        $flag = apply_filters( 'propertyhive_template_assistant_flag', $flag );

        return $flag;
    }

    public function add_flag()
    {
        global $property;

        $flag = $this->get_flag();

        if ( $flag != '' )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            echo '<div class="flag flag-' . sanitize_title($flag) . '" style="position:absolute; text-transform:uppercase; font-size:13px; box-sizing:border-box; padding:7px 20px; ' . $current_settings['flag_position'] . '; color:' . $current_settings['flag_text_color'] . '; background:' . $current_settings['flag_bg_color'] . ';">' . $flag . '</div>';
        }
    }

    public function add_flag_single()
    {
        global $property;

        $flag = $this->get_flag();

        if ( $flag != '' )
        {
            $current_settings = get_option( 'propertyhive_template_assistant', array() );

            echo '<div class="flag flag-' . sanitize_title($flag) . '" style="position:absolute; z-index:99; text-transform:uppercase; font-size:13px; box-sizing:border-box; padding:7px 20px; ' . $current_settings['flag_position'] . '; color:' . $current_settings['flag_text_color'] . '; background:' . $current_settings['flag_bg_color'] . ';">' . $flag . '</div>';
        }
    }

    /**
     * Get template assistant flag settings
     *
     * @return array Array of settings
     */
    public function get_template_assistant_flags_settings() {

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Flags', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_flags_settings' )

        );

        $settings[] = array(
            'title' => __( 'Show Flags On Search Results', 'propertyhive' ),
            'id'        => 'flags_active',
            'type'      => 'checkbox',
            'default'   => ( ( isset($current_settings['flags_active']) && $current_settings['flags_active'] == '1' ) ? 'yes' : ''),
            'desc'      => 'If checked flags will be shown in search results over the property thumbnail containing the property availability or marketing flag if one selected'
        );

        $settings[] = array(
            'title' => __( 'Show Flags On Property Details', 'propertyhive' ),
            'id'        => 'flags_active_single',
            'type'      => 'checkbox',
            'default'   => ( ( isset($current_settings['flags_active_single']) && $current_settings['flags_active_single'] == '1' ) ? 'yes' : ''),
            'desc'      => 'If checked flags will be shown over the main image slideshow on the full property details page'
        );

        $settings[] = array(
            'title' => __( 'Position Over Thumbnail', 'propertyhive' ),
            'id'        => 'flag_position',
            'type'      => 'select',
            'default'   => ( isset($current_settings['flag_position']) ? $current_settings['flag_position'] : ''),
            'options'   => array(
                'top:0; left:0;' => 'Top Left',
                'top:0; right:0;' => 'Top Right',
                'bottom:0; left:0;' => 'Bottom Left',
                'bottom:0; right:0;' => 'Bottom Right',
                'top:0; left:0; right:0;' => 'Across Top',
                'bottom:0; left:0; right:0;' => 'Across Bottom',
            )
        );

        $settings[] = array(
            'title' => __( 'Background Colour', 'propertyhive' ),
            'id'        => 'flag_bg_color',
            'type'      => 'color',
            'default'   => ( isset($current_settings['flag_bg_color']) ? $current_settings['flag_bg_color'] : '#000'),
        );

        $settings[] = array(
            'title' => __( 'Text Colour', 'propertyhive' ),
            'id'        => 'flag_text_color',
            'type'      => 'color',
            'default'   => ( isset($current_settings['flag_text_color']) ? $current_settings['flag_text_color'] : '#FFF'),
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_flags_settings');

        return $settings;
    }

	public function elementor_widget_property_image_render_after( $settings, $property )
    {
        global $property;

        if ( isset($settings['show_flag']) && $settings['show_flag'] == 'yes' )
        {
            $flag = $this->get_flag();

            if ( $flag != '' )
            {
                $current_settings = get_option( 'propertyhive_template_assistant', array() );

                echo '<div class="flag flag-' . sanitize_title($flag) . '" style="position:absolute; text-transform:uppercase; font-size:13px; box-sizing:border-box; padding:7px 20px; ' . $current_settings['flag_position'] . '; color:' . $current_settings['flag_text_color'] . '; background:' . $current_settings['flag_bg_color'] . ';">' . $flag . '</div>';
            }
        }
    }

    public function elementor_widget_property_image_controls( $widget )
    {
        $widget->add_control(
            'show_flag',
            [
                'label' => __( 'Show Availability Flag', 'propertyhive' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __( 'Yes', 'propertyhive' ),
                'label_off' => __( 'No', 'propertyhive' ),
                'return_value' => 'yes',
                'default' => '',
            ]
        );

        $widget->add_control(
            'flag_note',
            [
                'label' => __( '', 'propertyhive' ),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => __( 'The flag shown will take its colour and position settings from the <a href="' . admin_url('admin.php?page=ph-settings&tab=template-assistant&section=flags') . '" target="_blank">Template Assistant Flags</a> settings area', 'propertyhive' ),
                'condition' => [
                    'show_flag' => 'yes',
                ],
            ]
        );
    }

}