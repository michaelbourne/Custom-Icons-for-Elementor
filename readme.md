
# Custom Icons for Elementor

Enables the user to add their own custom icons to the built in Elementor icon controls and elements, thereby removing the reliance on FontAwesome and providing a better opportunity for branding with custom icon sets on your websites. Works exclusively through the use of Fontello's free icon font service. 

## Features  
* Add unlimited icons to your website via Fontello font packs
* View the icons in each uploaded pack, delete individual packs if desired
* Use icons anywhere you would normally with the default Elementor icon selectors
* Support for the new Icon Manager in Elementor 2.6+

---

Please note, this plugin requires the Elementor Page Builder to be installed and active. [Elementor is a free plugin](https://en-ca.wordpress.org/plugins/elementor/).

This plugin relies on a third party service for it's functionality provided by [Fontello](http://fontello.com). No private information is sent to their server, rather their provided webfont downloads are what's used to add fonts to this plugin.

Looking for a video tutorial? [Here it is!](https://youtu.be/Rnu9XVD8AdI)

### Frequently Asked Questions

**What can I do with this plugin?**

You can add your own icons from Fontello to Elementor (free and pro). From exisiting icon fonts to totally custom SVG icons, no more messing around with CSS or image elements. It's now all baked in.

**Can I upload more than one Fontello package to a single site?**

You bet! Upload as many as you like, they will all work. However, make sure you give each font a unique name (text box beside the Fontello download button).

**How do I remove Font Awesome icons from the default icon selectors, so that only my custom icons are available?**

Add this to your functions.php file in a child theme:

`add_filter('eci_drop_fa', '__return_true');`

**HELP! It doesnt work?!**

There is a small, small chance this plugin may not work on your web host. This is caused by two things generally: a mod_security rule flagging the ZIP upload, or the lack of PHP libraries needed to unzip files (ZipArchive). Here's the good news: your host can fix both of these easily. If they refuse, consider moving to a more modern host.

---

### Changelog

**0.3.1**
* Small fix for Elementor 2.6

**0.3**
* Added support for the new Elementor v2.6+ icons manager
* Fixed query string parameter on backend to prevent collisions with other plugins

**0.2.4**
* Supress PHP warning if Fontello file can't be read by server
* Add URL fallback for Fontello file reading (some servers are setup in a way that prevents server path reading)
* Fix "empty Fontello file name" issues by defaulting to ZIP file name instead of random string, thereby fixing the "disappearing icons" in unnamed files
* Fix CSS issues in editor and icon lists

**0.2.3**
* Adjusted priority of custom function to prevent certain themes from breaking it
* Change to parse css function to prevent a possible but rare error 
* Change to CSS display of icons from inline-block (FA style) to block (E style)
* Added filter to remove FA icons from default selectors

**0.2.2**
* Regen error fix

**0.2.1**
* Improve CSS Regen to fix changed URLs
* Tweak CSS display of icons to match native icons 
* Change CSS font-face path to relative URLs
* Add uninstall method to clean up left over files

**0.1.4**
* Fix the "empty box" icon error seen on some sites. Please reupload any affected fonts and regen your css.

**0.1.3**
* Fix error where an un-named font will not render properly
* Edit content directory reference

**0.1.2**
* Rewriting of help instructions to be more clear
* Limit upload area to zip files only to prevent confusion
* Added javascript translations
* Fixed icon font rendering after upload
* Added additional error alerts on font upload for hosts with no zip support

**0.1.1**
* Fix incorrect URI constant
* Fix jQuery reloading of stylsheet in admin page 
* Fix internationalization and regenerate language files
* Added French translation, care of Jean @momo-fr

**0.1.0**
* Initial Public Version