=== PropertyHive Template Assistant ===
Contributors: PropertyHive,BIOSTALL
Tags: property hive, propertyhive
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=N68UHATHAEDLN&lc=GB&item_name=BIOSTALL&no_note=0&cn=Add%20special%20instructions%20to%20the%20seller%3a&no_shipping=1&currency_code=GBP&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Requires at least: 3.8
Tested up to: 4.8.1
Stable tag: trunk
Version: 1.0.12
Homepage: http://wp-property-hive.com/addons/template-assistant/

This add on for Property Hive assists with the layout of property pages and more.

== Description ==

This add on for Property Hive assists with the layout of property search page, the fields shown on search forms and allows you to manage custom fields on the property record.

== Installation ==

= Manual installation =

The manual installation method involves downloading the Property Hive Template Assistant Add-on plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](http://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

Once installed and activated, you can access the settings for this add on by navigating to 'Property Hive > Settings > Template Assistant' from within WordPress.

= Updating =

Updating should work like a charm; as always though, ensure you backup your site just in case.

== Changelog ==

= 1.0.12 =
* Added maximum bedrooms field to the list of fields available to choose from in search form builder
* Declare support for WordPress 4.8.1

= 1.0.11 =
* Ensure that when the active departments are updated in settings that any department fields in search forms are updated accordingly

= 1.0.10 =
* Added ability to enter a 'Blank Option' when adding taxonomy field or custom field to search forms. Sets the first option in the dropdown and defaults to 'No Preference'

= 1.0.9 =
* When adding custom fields you can now choose the type of field; text, textarea or dropdown. Choosing dropdown you can then customise the options
* Added the ability to add any custom fields to the search form
* Added the ability to add placeholder to text inputs in search form builder
* Declare support for WordPress 4.8

= 1.0.8 =
* Added new 'Office' field to search form fields
* Added new 'Bedrooms' field to search form fields. This does an exact match on number of beds as opposed to min/max
* Tweaked CSS regarding column layouts in search results
* Declare support for WordPress 4.7.5

= 1.0.7 =
* Choose if custom fields should be displayed on the website. Any chosen will be appended to the bullet points in the single-property/meta.php template.

= 1.0.6 =
* Added ability to add and manage custom fields on property record

= 1.0.5 =
* Added new 'Available From' field available for selection when building search forms
* Declare support for WordPress 4.7.3

= 1.0.4 =
* Allow changing of type of department control between select and radio
* Tweaks to default search results CSS loaded

= 1.0.3 =
* Added new min/max bathrooms to list of selectable control in search form builder
* Corrected some of the classes being used on controls in search form builder
* Declare support for WordPress 4.7.2

= 1.0.2 =
* Added delete and reset options to search forms
* Corrected issue where field type was sometimes being saved as blank

= 1.0.1 =
* Added a new search form builder allowing customisation of search forms through a settings UI as opposed to having to know about PHP hooks
* Declare support for WordPress 4.7.1

= 1.0.0 =
* First working release of the add on. Contains assistance with search results page only