<?php
/*
Plugin Name: Custom Icons for Elementor
Description: Add custom icon fonts to the built in Elementor controls
Version:     0.3.1
Author:      Michael Bourne
Author URI:  https://michaelbourne.ca
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
Text Domain: custom-icons-for-elementor
Domain Path: /languages
*/
/*
Custom Icons for Elementor is a plugin for WordPress that enables you to add custom icon fonts to the built in Elementor controls.
Copyright (c) 2018 Michael Bourne.

The Custom Icons for Elementor Plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>

You can contact me at michael@michaelbourne.ca
*/

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

defined( 'ECIcons_ROOT' ) or define( 'ECIcons_ROOT', dirname( __FILE__ ) );
defined( 'ECIcons_URI' ) or define( 'ECIcons_URI', plugin_dir_url( __FILE__ ) );
defined( 'ECIcons_VERSION' ) or define( 'ECIcons_VERSION', '0.3.1' );
defined( 'ECIcons_UPLOAD' ) or define( 'ECIcons_UPLOAD', 'elementor_icons_files' );

class ECIcons {

	/**
	 * Core singleton class
	 *
	 * @var self - pattern realization
	 */
	private static $_instance;

	/**
	 * Prefix for plugin
	 *
	 * @var $prefix
	 */
	public $prefix;

	/**
	 * Path upload folder
	 *
	 * @var $upload_dir
	 */
	public $upload_dir;

	/**
	 * URL upload folder
	 *
	 * @var $upload_url
	 */
	public $upload_url;

	/**
	 * Prefix for custom icons
	 *
	 * @var $prefix_icon
	 */
	public $prefix_icon;

	/**
	 * Constructor.
	 */
	private function __construct() {

		// merge font css to single .css file
		include_once( ECIcons_ROOT . '/includes/merge.css.php' );

		// save font class
		include_once( ECIcons_ROOT . '/includes/save.font.php' );

		// add menu item
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 99 );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// add admin styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// for front end
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 999999 );
		add_action( 'wp_enqueue_scripts_clean', array( $this, 'enqueue_scripts' ), 10 );

		// add icon css to footer for builder support
		add_action( 'wp_print_footer_scripts', array( $this, 'insert_footer_css' ) );

		// load icons
		add_action( 'elementor/controls/controls_registered', array( $this, 'icons_filters' ), 100, 1 );
		add_filter( 'elementor/icons_manager/additional_tabs', array( $this, 'icons_filters_new' ), 9999999, 1 );

		// Provide developer filter to remove FA icons from selectors
		if ( apply_filters( 'eci_drop_fa', false ) ) {
			// TODO: this doesn't work yet, because the "default icon" in widgets requires these tabs to be active. Need to override all default icon selections in all icon based widgets to enable this.
			//add_filter( 'elementor/icons_manager/native', '__return_empty_array' );
		}

		// default wp upload directory
		$upload = wp_upload_dir();

		// main variables
		$this->prefix      = 'eci_';
		$this->prefix_icon = 'efs-';
		$this->upload_dir  = $upload['basedir'] . '/' . ECIcons_UPLOAD;
		$this->upload_url  = $upload['baseurl'] . '/' . ECIcons_UPLOAD;

		// set plugin version
		$this->version = '0.3.1';

		// SSL fix because WordPress core function wp_upload_dir() doesn't check protocol.
		if ( is_ssl() ) {
			$this->upload_url = str_replace( 'http://', 'https://', $this->upload_url );
		}

		// load translations
		add_action( 'plugins_loaded', array( $this, 'eci_load_textdomain' ) );
	}

	/**
	 * Get the instance of ECIcons Plugins
	 *
	 * @return self
	 */
	public static function getInstance() {

		if ( ! ( self::$_instance instanceof self ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;

	}

	/**
	 * @param mixed $instance
	 */
	public static function setInstance( $instance ) {

		self::$_instance = $instance;

	}

	/**
	 * Init main functions (for hook admin_init)
	 */
	public function admin_init() {

		$this->settings_init();

		$saveFont = new SaveFont_ECIcons();
		$saveFont->init();

	}

	/**
	 * Internationalization
	 */
	public function eci_load_textdomain() {
		load_plugin_textdomain( 'custom-icons-for-elementor', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add new pages to admin
	 */
	public function add_admin_menu() {

		add_submenu_page(
			'elementor',
			__( 'Custom Icons for Elementor', 'custom-icons-for-elementor' ),
			__( 'Custom Icons', 'custom-icons-for-elementor' ),
			'manage_options',
			'elementor-custom-icons',
			array(
				$this,
				'options_page',
			)
		);

	}

	/**
	 * Render all options
	 */
	public function options_page() {

		include_once 'includes/template.options.page.php';

	}


	/**
	 * ECIcons settings init
	 */
	protected function settings_init() {

		register_setting( $this->prefix . 'fontellos_page', $this->prefix . 'elementor_icons_settings' );
		add_settings_section( $this->prefix . 'fontellos_pluginPage_section', '', '', $this->prefix . 'fontellos_page' );

	}


	/**
	 * Enqueue admin scripts
	 */
	public function admin_enqueue_scripts() {

		wp_enqueue_style( 'elementor-custom-icons-css', ECIcons_URI . 'assets/css/elementor-custom-icons.css', array(), ECIcons_VERSION );

		wp_enqueue_script( 'elementor-custom-icons', ECIcons_URI . 'assets/js/elementor-custom-icons.js', array( 'jquery' ), ECIcons_VERSION, true );

		if ( is_admin() ) {
			$eci_script = array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'plugin_url'    => ECIcons_URI,
				'exist'         => __( "This font file already exists. Make sure you're giving it a unique name!", 'custom-icons-for-elementor' ),
				'failedopen'    => __( 'Failed to open the ZIP archive. If you uploaded a valid Fontello ZIP file, your host may be blocking this PHP function. Please get in touch with them.', 'custom-icons-for-elementor' ),
				'failedextract' => __( 'Failed to extract the ZIP archive. Your host may be blocking this PHP function. Please get in touch with them.', 'custom-icons-for-elementor' ),
				'emptyfile'     => __( 'Your browser failed to upload the file. Please try again.', 'custom-icons-for-elementor' ),
				'regen'         => __( 'Custom Icon CSS file has been regenerated.', 'custom-icons-for-elementor' ),
				'delete'        => __( 'Are you sure you want to delete this font?', 'custom-icons-for-elementor' ),
				'updatefailed'  => __( 'Plugin failed to update the WP options table.', 'custom-icons-for-elementor' ),
			);
			wp_localize_script( 'elementor-custom-icons', 'EC_ICONS', $eci_script );
		}

	}

	/**
	 * Enqueue public scripts
	 */
	public function enqueue_scripts() {

		if ( file_exists( $this->upload_dir . '/merged-icons-font.css' ) ) {

			$modtime = get_option( 'eci_css_timestamp' );
			if ( ! $modtime ) {
				$modtime = mt_rand(); }
			wp_enqueue_style( 'eci-icon-fonts', esc_url( $this->upload_url . '/merged-icons-font.css' ), false, $modtime );
		}

	}

	/**
	 * Add custom font CSS to footer so it actually works in the builder
	 * - to do: get this working with the builder boilerplate action
	 */
	public function insert_footer_css() {

		if ( current_user_can( 'manage_options' ) ) {

			if ( file_exists( $this->upload_dir . '/merged-icons-font.css' ) ) {

				$modtime = get_option( 'eci_css_timestamp' );
				if ( ! $modtime ) {
					$modtime = mt_rand(); }
				echo '<link rel="stylesheet" type="text/css" href="' . $this->upload_url . '/merged-icons-font.css?ver=' . $modtime . '">';
			}

		}

	}

	/**
	 * Get uploaded font package's config file
	 *
	 * @param string $file_name
	 * @return array $data
	 */
	public function get_config_font( $file_name ) {

		if ( ! is_dir( $this->upload_dir . '/' . $file_name ) ) return false;

		$file_config = glob( $this->upload_dir . '/' . $file_name . '/*/*' );
		$data        = array();
		$css_folder  = '';

		foreach ( $file_config as $key => $file ) {

			if ( strpos( $file, 'config.json' ) !== false ) {
				$file_info               = json_decode( file_get_contents( $file ) );
				$data['name']            = trim( $file_info->name );
				$data['icons']           = $file_info->glyphs;
				$data['css_prefix_text'] = $file_info->css_prefix_text;
			}

			if ( is_string( $file ) && strpos( $file, 'css' ) !== false ) {
				$file_part          = explode( ECIcons_UPLOAD . '/', $file );
				$data['css_folder'] = $file;
				$css_folder         = $file_part[1];
			}

			if ( is_string( $file ) && strpos( $file, 'font' ) !== false ) {
				$file_part = explode( ECIcons_UPLOAD . '/', $file );
				//$data['font_url'] = $this->upload_url . $file_part[1];
				$data['font_url'] = $file_part[1];
			}
		}

		if ( empty( $data['name'] ) ) {
			$noname            = explode( '.', $file_name );
			$data['name']      = $noname[0];
			$data['nameempty'] = true;
			$data['css_root']  = $data['css_folder'] . '/fontello.css';
			$data['css_url']   = $this->upload_url . '/' . $css_folder . '/fontello.css';

		} else {
			$data['css_root'] = $data['css_folder'] . '/' . $data['name'] . '.css';
			$data['css_url']  = $this->upload_url . '/' . $css_folder . '/' . $data['name'] . '.css';
		}

		$data['json_url']  = $this->upload_url . '/' . $data['name'] . '.json';
		$data['file_name'] = $file_name;

		return $data;

	}

	/**
	 * Add custom icons to Elementor registry
	 *
	 * @param object $controls_registry
	 * @return void
	 */
	public function icons_filters( $controls_registry ) {

		// Get existing icons
		$icons = $controls_registry->get_control( 'icon' )->get_settings( 'options' );

		// Provide developer filter to remove FA icons from selectors
		if ( apply_filters( 'eci_drop_fa', false ) ) {
			$icons = array();
		}

		// get loaded icon files
		$options = get_option( 'ec_icons_fonts' );

		if ( empty( $options ) ) {
			return;
		}

		foreach ( $options as $key => $font ) {
			if ( $font['status'] == '1' ) {

				$font_data = json_decode( $font['data'], true );

				$new_icons_reverse = $this->parse_css_reverse( $font_data['css_root'], $font_data['name'], $font_data['css_url'] );
				if ( ! empty( $new_icons_reverse ) && is_array( $new_icons_reverse ) ) {
					$icons = array_merge( $new_icons_reverse, $icons );
				}
			}
		}

		// send back new array
		$controls_registry->get_control( 'icon' )->set_settings( 'options', $icons );

	}


	/**
	 * Add custom icons to Elementor Icons tabs (new in v2.6+)
	 *
	 * @param array $tabs Additional tabs for new icon interface.
	 * @return array $tabs
	 */
	public function icons_filters_new( $tabs = array() ) {

		// get loaded icon files
		$options = get_option( 'ec_icons_fonts' );

		if ( empty( $options ) ) {
			return $tabs;
		}

		$newicons = [];

		foreach ( $options as $key => $font ) {
			if ( $font['status'] == '1' ) {

				$font_data  = json_decode( $font['data'], true );
				$icons      = $this->parse_css( $font_data['css_root'], $font_data['name'], $font_data['css_url'] );
				$first_icon = ! empty( $icons ) ? key( $icons ) : '';

				$newicons[ $font_data['name'] ] = [
					'name'          => $font_data['name'],
					'label'         => $font_data['name'],
					'url'           => '',
					'enqueue'       => '',
					'prefix'        => '',
					'displayPrefix' => 'eci ',
					'labelIcon'     => 'eci ' . $first_icon,
					'ver'           => ECIcons_VERSION,
					'fetchJson'     => $font_data['json_url'],
				];

			}
		}

		return array_merge( $tabs, $newicons );

	}



	/**
	 * Parse CSS file to get proper icon names
	 *
	 * @param string $css_file
	 * @param string $name
	 * @param string $url
	 * @return array $icons
	 */
	protected function parse_css( $css_file, $name, $url ) {

		$css_source = @file_get_contents( $css_file );

		if ( $css_source === false ) {
			$response = wp_remote_get( $url );

			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			    $css_source = $response['body'];
			} else {
				return null;
			}

		}

		$icons = array();

		preg_match_all( "/\.\w*?\-(.*?):\w*?\s*?{?\s*?{\s*?\w*?:\s*?\'\\\\?(\w*?)\'.*?}/", $css_source, $matches, PREG_SET_ORDER, 0 );
		foreach ( $matches as $match ) {
			$icons[ $name . '-' . $match[1] ] = $match[2];
		}

		return $icons;

	}


	/**
	 * Parse CSS file to get proper icon names (reverse parse)
	 *
	 * @param string $css_file
	 * @param string $name
	 * @param string $url
	 * @return array $icons
	 */
	protected function parse_css_reverse( $css_file, $name, $url ) {

		$css_source = @file_get_contents( $css_file );

		if ( $css_source === false ) {
			$response = wp_remote_get( $url );

			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
			    $css_source = $response['body'];
			} else {
				return null;
			}

		}

		$icons = array();

		preg_match_all( "/\.\w*?\-(.*?):\w*?\s*?{?\s*?{\s*?\w*?:\s*?\'\\\\?(\w*?)\'.*?}/", $css_source, $matches, PREG_SET_ORDER, 0 );
		foreach ( $matches as $match ) {
			$icons[ 'eci ' . $name . '-' . $match[1] ] = $match[1];
		}

		return $icons;

	}


	/**
	 * remove folder (recursive)
	 *
	 * @param string $dir
	 * @return void
	 */
	protected function rrmdir( $dir ) {

		if ( ! current_user_can( 'manage_options' ) ) return;

		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( $object != '.' && $object != '..' ) {
					if ( is_dir( $dir . '/' . $object ) ) {
						$this->rrmdir( $dir . '/' . $object );
					} else {
						unlink( $dir . '/' . $object );
					}
				}
			}
			rmdir( $dir );
		}

	}

	/**
	 * @param string $name
	 * @param bool   $default
	 * @param string $type
	 *
	 * @return bool|string
	 */
	protected function getRequest( $name, $default = false, $type = 'POST' ) {

		$TYPE = ( strtolower( $type ) == 'post' ) ? $_POST : $_GET;
		if ( ! empty( $TYPE[ $name ] ) ) {
			return sanitize_text_field( $TYPE[ $name ] );
		}

		return $default;

	}

}

function ec_icons_manager() {
	return ECIcons::getInstance();
}

ec_icons_manager();
