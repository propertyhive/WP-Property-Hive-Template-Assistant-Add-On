<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class PH_Template_Assistant_Text_Translation {

	public function __construct() {

		$current_settings = get_option( 'propertyhive_template_assistant', array() );

		if ( isset($current_settings['text_translations']) && is_array($current_settings['text_translations']) && !empty($current_settings['text_translations']) )
        {
            add_filter( 'gettext', array( $this, 'template_assistant_text_translation'), 20, 3 );
        }

	}

	public function template_assistant_text_translation( $translated_text, $text, $domain )
    {
    	$current_settings = get_option( 'propertyhive_template_assistant', array() );

        foreach ( $current_settings['text_translations'] as $text_translation )
        {
            if ( $text_translation['search'] == $translated_text )
            {
                $translated_text = $text_translation['replace'];
            }
        }

        return $translated_text;
    }

    /**
     * Get template assistant text translation settings
     *
     * @return array Array of settings
     */
    public function get_template_assistant_text_translation_settings() {

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Text Substitution', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_text_translation_settings' )

        );

        /*$settings[] = array(
            'title' => __( 'Show Flags', 'propertyhive' ),
            'id'        => 'flags_active',
            'type'      => 'checkbox',
            'default'   => ( ( isset($current_settings['flags_active']) && $current_settings['flags_active'] == '1' ) ? 'yes' : ''),
            'desc'      => 'If checked flags will be shown in search results over the property thumbnail containing the property availability or marketing flag if one selected'
        );*/

        $existing_translations = array();
        if ( isset($current_settings['text_translations']) && is_array($current_settings['text_translations']) && !empty($current_settings['text_translations']) )
        {
            foreach ( $current_settings['text_translations'] as $text_translation )
            {
                $existing_translations[] = '<tr><td><input type="text" name="search[]" value="' . $text_translation['search'] . '"></td><td><input type="text" name="replace[]" value="' . $text_translation['replace'] . '"></td></tr>';
            }
        }

        $settings[] = array(
            'type'      => 'html',
            'html'      => '

            <style type="text/css">.form-table .titledesc { display:none; }</style>

            <table cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th width="200">Text To Replace</th>
                        <th width="200">Replace With</th>
                    </tr>
                </thead>
                <tbody>
                    ' . implode("", $existing_translations) . '
                    <tr>
                        <td><input type="text" name="search[]" placeholder="e.g. Make Enquiry"></td>
                        <td><input type="text" name="replace[]" placeholder="e.g. Request Viewing"></td>
                    </tr>
                </tbody>
            </table>'
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_text_translation_settings');

        return $settings;
    }

}