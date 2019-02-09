=== Custom Icons for Elementor ===
Contributors: michaelbourne
Donate link: https://www.paypal.me/yycpro
Tags: elementor, icons, fontello, icon fonts
Requires at least: 4.5
Tested up to: 5.0.3
Stable tag: 0.2.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Add custom icon fonts and SVGs to the built in Elementor icon controls

== Description ==

Enables the user to add their own custom icons to the built in Elementor icon controls and elements, thereby removing the reliance on FontAwesome and providing a better opportunity for branding with custom icon sets on your websites. Works exclusively through the use of Fontello's free icon font service. 

= Plugin Features =
 
*   Add unlimited icons and SVG icons to your website via Fontello
*   View the icons in each uploaded pack, delete individual packs if desired
*   Use icons anywhere you would normally with the default Elementor icon selectors

Please note, this plugin requires the Elementor Page Builder to be installed and active. [Elementor is a free plugin](https://en-ca.wordpress.org/plugins/elementor/).

This plugin relies on a third party service for it's functionality provided by [Fontello](http://fontello.com). No private information is sent to their server, rather their provided webfont downloads are what's used to add fonts to this plugin.

Looking for a video tutorial? [Here it is!](https://youtu.be/Rnu9XVD8AdI)

== Installation ==

1.  Upload your plugin folder to the '/wp-content/plugins' directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to Fontello.com to create your own icon font. Download the zip when done.
4.  Upload the Fontello zip file to the plugins settings page.

== Frequently Asked Questions ==

= What can I do with this plugin? =

You can add your own icons from Fontello to Elementor (free and pro). From exisiting icon fonts to totally custom SVG icons, no more messing around with CSS or image elements. It's now all baked in.

= Can I upload more than one Fontello package to a single site? =

You bet! Upload as many as you like, they will all work. However, make sure you give each font a unique name (text box beside the Fontello download button).

= HELP! It doesnt work?! =

There is a small, small chance this plugin may not work on your web host. This is caused by two things generally: a mod_security rule flagging the ZIP upload, or the lack of PHP libraries needed to unzip files (ZipArchive). Here's the good news: your host can fix both of these easily. If they refuse, consider moving to a more modern host.

== Plugin Removal ==

Removing this plugin will render your custom icons to be deleted. Take care to un-select them from your icon elements prior to plugin removal.

== Screenshots ==

None yet

== Changelog ==

= 0.2.2 =
* Regen error fix

= 0.2.1 =
* Improve CSS Regen to fix changed URLs
* Tweak CSS display of icons to match native icons 
* Change CSS font-face path to relative URLs
* Add uninstall method to clean up left over files

= 0.1.4 =
* Fix the "empty box" icon error seen on some sites. Please reupload any affected fonts and regen your css.

= 0.1.3 =
* Fix error where an un-named font will not render properly
* Edit content directory reference

= 0.1.2 =
* Rewriting of help instructions to be more clear
* Limit upload area to zip files only to prevent confusion
* Added javascript translations
* Fixed icon font rendering after upload
* Added additional error alerts on font upload for hosts with no zip support

= 0.1.1 =
* Fix incorrect URI constant
* Fix jQuery reloading of stylsheet in admin page 
* Fix internationalization and regenerate language files
* Added French translation, care of Jean @momo-fr

= 0.1.0 =
* Initial Public Version

== Upgrade Notice ==

= 0.2.1 =
You MUST 'regen CSS' after this update.

= 0.1.4 = 
Fix broken icons on some sites. Font re-upload and regen required if you're affected.

= 0.1.3 =
Fix un-named font errors

= 0.1.2 =
Small bug fixes and translations added.

= 0.1.1 =
Small bug fixes. French translation added.

= 0.1.0 =
You can't upgrade, but you can install.

== Copyright ==

Custom Icons for Elementor is a plugin for WordPress that enables you to add custom icon fonts to the built in Elementor controls.
Copyright (c) 2018 Michael Bourne.

The Custom Icons for Elementor Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>

You can contact me at michael@michaelbourne.ca