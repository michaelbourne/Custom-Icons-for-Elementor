<?php
// phpcs:ignore Squiz.Commenting.FileComment.WrongStyle -- WordPress plugin header requires this comment style.
/*
Plugin Name:       Custom Icons for Elementor
Description:       Add custom icon fonts to the built in Elementor controls
Version:           1.0.0
Author:            Michael Bourne
Author URI:        https://michaelbourne.ca
License:           GPL3
Requires at least: 5.4
Tested up to:      6.9
Stable tag:        1.0.0
Requires PHP:      7.4
License URI:       https://www.gnu.org/licenses/gpl-3.0.en.html
Text Domain:       custom-icons-for-elementor
Domain Path:       /languages
*/

/**
 * Custom Icons for Elementor plugin bootstrap.
 *
 * @package CustomIconsForElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

defined( 'ECICONS_ROOT' ) || define( 'ECICONS_ROOT', __DIR__ );
defined( 'ECICONS_URI' ) || define( 'ECICONS_URI', plugin_dir_url( __FILE__ ) );
defined( 'ECICONS_VERSION' ) || define( 'ECICONS_VERSION', '1.0.0' );
defined( 'ECICONS_UPLOAD' ) || define( 'ECICONS_UPLOAD', 'elementor_icons_files' );
defined( 'ECICONS_ACTION_SAVE_FONT' ) || define( 'ECICONS_ACTION_SAVE_FONT', 'ecicons_save_font' );
defined( 'ECICONS_ACTION_DELETE_FONT' ) || define( 'ECICONS_ACTION_DELETE_FONT', 'ecicons_delete_font' );
defined( 'ECICONS_ACTION_REGENERATE_FONT' ) || define( 'ECICONS_ACTION_REGENERATE_FONT', 'ecicons_regenerate_font' );

/**
 * Class ECIcons
 *
 * This class is responsible for handling custom icons in Elementor.
 * It provides functionality to integrate and manage custom icons
 * within the Elementor page builder.
 *
 * @package CustomIconsForElementor
 */
class ECIcons {

	/**
	 * Core singleton class
	 *
	 * @var self - pattern realization
	 */
	private static $instance;

	/**
	 * Prefix for plugin
	 *
	 * @var $prefix
	 */
	public string $prefix;

	/**
	 * Path upload folder
	 *
	 * @var $upload_dir
	 */
	public string $upload_dir;

	/**
	 * URL upload folder
	 *
	 * @var $upload_url
	 */
	public string $upload_url;

	/**
	 * Prefix for custom icons
	 *
	 * @var $prefix_icon
	 */
	public string $prefix_icon;

	/**
	 * Plugin version
	 *
	 * @var string $version
	 */
	public string $version;

	/**
	 * Registered admin page hook suffix.
	 *
	 * @var string
	 */
	public string $admin_page_hook = '';

	/**
	 * Flag to avoid duplicate Elementor hook registration.
	 *
	 * @var bool
	 */
	private bool $legacy_icons_registered = false;


	/**
	 * Constructor.
	 */
	private function __construct() {

		// merge font css to single .css file.
		include_once ECICONS_ROOT . '/includes/merge.css.php';

		// save font class.
		include_once ECICONS_ROOT . '/includes/save.font.php';

		// add menu item.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 99 );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		// add admin styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// load icon stylesheet in frontend/editor contexts.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 100 );
		add_action( 'elementor/editor/before_enqueue_styles', array( $this, 'enqueue_scripts' ), 100 );
		add_action( 'elementor/preview/enqueue_styles', array( $this, 'enqueue_scripts' ), 100 );

		// register icon integrations when Elementor is ready.
		add_action( 'elementor/init', array( $this, 'register_elementor_hooks' ) );
		add_action( 'plugins_loaded', array( $this, 'register_elementor_hooks' ), 20 );
		add_action( 'admin_notices', array( $this, 'maybe_show_elementor_notice' ) );

		// Keep this filter available for integrators; selector-level handling occurs during icon registration.
		apply_filters( 'eci_drop_fa', false );

		// default wp upload directory.
		$upload = wp_upload_dir();

		// main variables.
		$this->prefix      = 'eci_';
		$this->prefix_icon = 'efs-';
		$this->upload_dir  = $upload['basedir'] . '/' . ECICONS_UPLOAD;
		$this->upload_url  = $upload['baseurl'] . '/' . ECICONS_UPLOAD;

		// set plugin version.
		$this->version = ECICONS_VERSION;

		// SSL fix because WordPress core function wp_upload_dir() doesn't check protocol.
		if ( is_ssl() ) {
			$this->upload_url = str_replace( 'http://', 'https://', (string) $this->upload_url );
		}

		// load translations.
		add_action( 'plugins_loaded', array( $this, 'eci_load_textdomain' ) );
	}

	/**
	 * Get the instance of ECIcons Plugins
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set singleton instance.
	 *
	 * @param mixed $instance Singleton instance.
	 * @return void
	 */
	public static function set_instance( $instance ) {
		self::$instance = $instance;
	}

	/**
	 * Init main functions (for hook admin_init)
	 */
	public function admin_init() {
		$this->settings_init();

		$save_font = new SaveFont_ECIcons();
		$save_font->init();
	}

	/**
	 * Register Elementor integration hooks with feature detection.
	 *
	 * @return void
	 */
	public function register_elementor_hooks() {
		if ( ! $this->is_elementor_active() || $this->legacy_icons_registered ) {
			return;
		}

		add_action( 'elementor/controls/register', array( $this, 'icons_filters' ), 100, 1 );
		add_action( 'elementor/controls/controls_registered', array( $this, 'icons_filters' ), 100, 1 );
		add_filter( 'elementor/icons_manager/additional_tabs', array( $this, 'icons_filters_new' ), 9999999, 1 );

		$this->legacy_icons_registered = true;
	}

	/**
	 * Check if Elementor is loaded.
	 *
	 * @return bool
	 */
	private function is_elementor_active() {
		return did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' );
	}

	/**
	 * Show Elementor dependency notice.
	 *
	 * @return void
	 */
	public function maybe_show_elementor_notice() {
		if ( $this->is_elementor_active() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Custom Icons for Elementor requires Elementor to be installed and active.', 'custom-icons-for-elementor' ) . '</p></div>';
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

		$this->admin_page_hook = (string) add_submenu_page(
			$this->is_elementor_active() ? 'elementor' : 'options-general.php',
			__( 'Custom Icons for Elementor', 'custom-icons-for-elementor' ),
			__( 'Custom Icons (Free)', 'custom-icons-for-elementor' ),
			'manage_options',
			'elementor-custom-icons',
			array(
				$this,
				'options_page',
			),
			11
		);
	}

	/**
	 * Render all options
	 */
	public function options_page() {
		if ( ! $this->is_elementor_active() ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Elementor is not active. Activate Elementor to use custom icon controls.', 'custom-icons-for-elementor' ) . '</p></div>';
		}
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
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( ! empty( $this->admin_page_hook ) && $hook_suffix !== $this->admin_page_hook ) {
			return;
		}

		wp_enqueue_style( 'elementor-custom-icons-css', ECICONS_URI . 'assets/css/elementor-custom-icons.css', array(), ECICONS_VERSION );

		wp_enqueue_script( 'elementor-custom-icons', ECICONS_URI . 'assets/js/elementor-custom-icons.js', array( 'jquery' ), ECICONS_VERSION, true );

		if ( is_admin() ) {
			$eci_script = array(
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'plugin_url'      => ECICONS_URI,
				'saveAction'      => ECICONS_ACTION_SAVE_FONT,
				'deleteAction'    => ECICONS_ACTION_DELETE_FONT,
				'regenAction'     => ECICONS_ACTION_REGENERATE_FONT,
				'exist'           => __( "This font file already exists. Make sure you're giving it a unique name!", 'custom-icons-for-elementor' ),
				'failedopen'      => __( 'Failed to open the ZIP archive. If you uploaded a valid Fontello ZIP file, your host may be blocking this PHP function. Please get in touch with them.', 'custom-icons-for-elementor' ),
				'failedextract'   => __( 'Failed to extract the ZIP archive. Your host may be blocking this PHP function. Please get in touch with them.', 'custom-icons-for-elementor' ),
				'emptyfile'       => __( 'Your browser failed to upload the file. Please try again.', 'custom-icons-for-elementor' ),
				'regen'           => __( 'Custom Icon CSS file has been regenerated.', 'custom-icons-for-elementor' ),
				'delete'          => __( 'Are you sure you want to delete this font?', 'custom-icons-for-elementor' ),
				'updatefailed'    => __( 'Plugin failed to update the WP options table.', 'custom-icons-for-elementor' ),
				'remove'          => __( 'Icon font family removed.', 'custom-icons-for-elementor' ),
				'deletefailed'    => __( 'Plugin failed to delete the font files.', 'custom-icons-for-elementor' ),
				'invalidfiletype' => __( 'Invalid file type. Please upload a valid Fontello ZIP file.', 'custom-icons-for-elementor' ),
				'invalidfile'     => __( 'Invalid file. Please upload a valid Fontello ZIP file.', 'custom-icons-for-elementor' ),
				'unknownerror'    => __( 'Unknown error. Please try again.', 'custom-icons-for-elementor' ),
			);
			wp_localize_script( 'elementor-custom-icons', 'EC_ICONS', $eci_script );
		}
	}

	/**
	 * Enqueue public scripts
	 */
	public function enqueue_scripts() {
		if ( ! file_exists( $this->upload_dir . '/merged-icons-font.css' ) ) {
			return;
		}

		$modtime = get_option( 'eci_css_timestamp' );
		if ( ! $modtime ) {
			$modtime = wp_rand();
		}
		wp_enqueue_style( 'eci-icon-fonts', esc_url( $this->upload_url . '/merged-icons-font.css' ), array(), $modtime );
	}

	/**
	 * Get uploaded font package's config file
	 *
	 * @param string $file_name Font file name.
	 * @return array $data Font data.
	 */
	public function get_config_font( $file_name ) {
		$font_root = $this->upload_dir . '/' . $file_name;
		if ( ! is_dir( $font_root ) ) {
			return false;
		}

		$data        = array();
		$config_path = '';
		$css_files   = array();
		$font_dir    = '';

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $font_root, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $path_info ) {
			$file = $path_info->getPathname();
			$base = strtolower( $path_info->getBasename() );

			if ( 'config.json' === $base && empty( $config_path ) ) {
				$config_path = $file;
				continue;
			}

			if ( 'css' === strtolower( $path_info->getExtension() ) ) {
				$css_files[] = $file;
			}

			if ( empty( $font_dir ) && false !== strpos( str_replace( '\\', '/', $path_info->getPath() ), '/font' ) ) {
				$font_dir = $path_info->getPath();
			}
		}

		if ( ! empty( $config_path ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read from extracted archive.
			$config_raw = file_get_contents( $config_path );
			if ( false === $config_raw ) {
				return false;
			}

			$config = json_decode( $config_raw, true );
			if ( ! is_array( $config ) || empty( $config['glyphs'] ) || ! is_array( $config['glyphs'] ) ) {
				return false;
			}

			$data['icons']           = $config['glyphs'];
			$data['css_prefix_text'] = isset( $config['css_prefix_text'] ) ? sanitize_key( (string) $config['css_prefix_text'] ) : '';
			$data['name']            = isset( $config['name'] ) ? sanitize_title( (string) $config['name'] ) : '';
		}

		if ( empty( $data['name'] ) ) {
			$noname            = explode( '.', $file_name );
			$data['name']      = $noname[0];
			$data['nameempty'] = true;
		}

		if ( empty( $font_dir ) ) {
			foreach ( $css_files as $css_file ) {
				$candidate          = dirname( $css_file ) . '/../font';
				$resolved_candidate = realpath( $candidate );
				if ( false !== $resolved_candidate && is_dir( $resolved_candidate ) ) {
					$font_dir = $resolved_candidate;
					break;
				}
			}
		}

		if ( empty( $font_dir ) ) {
			return false;
		}

		$preferred_css = ! empty( $data['nameempty'] ) ? 'fontello.css' : $data['name'] . '.css';
		$css_root      = '';

		foreach ( $css_files as $css_file ) {
			if ( strtolower( basename( $css_file ) ) === strtolower( $preferred_css ) ) {
				$css_root = $css_file;
				break;
			}
		}

		if ( empty( $css_root ) ) {
			foreach ( $css_files as $css_file ) {
				if ( 'fontello.css' === strtolower( basename( $css_file ) ) ) {
					$css_root = $css_file;
					break;
				}
			}
		}

		if ( empty( $css_root ) && ! empty( $css_files ) ) {
			$css_root = reset( $css_files );
		}

		if ( empty( $css_root ) ) {
			return false;
		}

		$css_relative  = ltrim( str_replace( '\\', '/', str_replace( $this->upload_dir, '', dirname( $css_root ) ) ), '/' );
		$font_relative = ltrim( str_replace( '\\', '/', str_replace( $this->upload_dir, '', (string) $font_dir ) ), '/' );

		$data['css_folder'] = dirname( $css_root );
		$data['css_root']   = $css_root;
		$data['css_url']    = $this->upload_url . '/' . $css_relative . '/' . basename( $css_root );
		$data['font_url']   = $font_relative;
		$data['json_url']   = $this->upload_url . '/' . $data['name'] . '.json';
		$data['file_name']  = $file_name;
		$data['icon_map']   = $this->build_icons_from_glyphs( $data['name'], $data['icons'] ?? array() );

		if ( empty( $data['icon_map'] ) ) {
			$data['icon_map'] = $this->parse_css( $data['css_root'], $data['name'], $data['css_url'] );
		}

		return $data;
	}

	/**
	 * Convert Fontello glyph metadata to icon map.
	 *
	 * @param string $font_name Font family name.
	 * @param array  $glyphs List of glyphs.
	 * @return array
	 */
	protected function build_icons_from_glyphs( $font_name, $glyphs ) {
		$icons = array();
		if ( empty( $glyphs ) || ! is_array( $glyphs ) ) {
			return $icons;
		}

		foreach ( $glyphs as $glyph ) {
			if ( ! is_array( $glyph ) || empty( $glyph['css'] ) || ! isset( $glyph['code'] ) ) {
				continue;
			}

			$icon_name = sanitize_title( (string) $glyph['css'] );
			if ( '' === $icon_name ) {
				continue;
			}

			$icons[ $font_name . '-' . $icon_name ] = dechex( (int) $glyph['code'] );
		}

		return $icons;
	}

	/**
	 * Build reverse icon map used by the legacy icon control.
	 *
	 * @param string $font_name Font family name.
	 * @param array  $icons Icon map.
	 * @return array
	 */
	protected function build_reverse_icons_map( $font_name, $icons ) {
		$reverse = array();
		if ( empty( $icons ) || ! is_array( $icons ) ) {
			return $reverse;
		}

		foreach ( $icons as $icon_key => $icon_code ) {
			$reverse[ 'eci ' . $icon_key ] = str_replace( $font_name . '-', '', (string) $icon_key );
		}

		return $reverse;
	}

	/**
	 * Read active font definitions from options.
	 *
	 * @return array
	 */
	protected function get_active_fonts() {
		$options = get_option( 'ec_icons_fonts' );
		if ( empty( $options ) || ! is_array( $options ) ) {
			return array();
		}

		$fonts = array();
		foreach ( $options as $font ) {
			if ( empty( $font['status'] ) || '1' !== (string) $font['status'] || empty( $font['data'] ) ) {
				continue;
			}

			$font_data = json_decode( $font['data'], true );
			if ( ! is_array( $font_data ) || empty( $font_data['name'] ) ) {
				continue;
			}

			$fonts[] = $font_data;
		}

		return $fonts;
	}

	/**
	 * Build icon map from stored font data.
	 *
	 * @param array $font_data Font metadata.
	 * @return array
	 */
	protected function get_icon_map_from_font_data( $font_data ) {
		if ( ! empty( $font_data['icon_map'] ) && is_array( $font_data['icon_map'] ) ) {
			return $font_data['icon_map'];
		}

		if ( ! empty( $font_data['icons'] ) && is_array( $font_data['icons'] ) ) {
			$icons = $this->build_icons_from_glyphs( $font_data['name'], $font_data['icons'] );
			if ( ! empty( $icons ) ) {
				return $icons;
			}
		}

		if ( empty( $font_data['css_root'] ) || empty( $font_data['name'] ) || empty( $font_data['css_url'] ) ) {
			return array();
		}

		return (array) $this->parse_css( $font_data['css_root'], $font_data['name'], $font_data['css_url'] );
	}

	/**
	 * Add custom icons to Elementor registry
	 *
	 * @param object $controls_registry Elementor controls registry.
	 * @return void
	 */
	public function icons_filters( $controls_registry ) {
		if ( ! is_object( $controls_registry ) || ! method_exists( $controls_registry, 'get_control' ) ) {
			return;
		}

		$icon_control = $controls_registry->get_control( 'icon' );
		if ( ! $icon_control || ! method_exists( $icon_control, 'get_settings' ) || ! method_exists( $icon_control, 'set_settings' ) ) {
			return;
		}

		$icons = (array) $icon_control->get_settings( 'options' );

		// Provide developer filter to remove FA icons from selectors.
		if ( apply_filters( 'eci_drop_fa', false ) ) {
			$icons = array();
		}

		foreach ( $this->get_active_fonts() as $font_data ) {
			$new_icons_reverse = $this->build_reverse_icons_map(
				$font_data['name'],
				$this->get_icon_map_from_font_data( $font_data )
			);
			if ( ! empty( $new_icons_reverse ) ) {
				$icons = array_merge( $new_icons_reverse, $icons );
			}
		}

		$icon_control->set_settings( 'options', $icons );
	}


	/**
	 * Add custom icons to Elementor Icons tabs (new in v2.6+)
	 *
	 * @param array $tabs Additional tabs for new icon interface.
	 * @return array $tabs
	 */
	public function icons_filters_new( $tabs = array() ) {
		$newicons = array();
		foreach ( $this->get_active_fonts() as $font_data ) {
			$icons      = $this->get_icon_map_from_font_data( $font_data );
			$first_icon = ! empty( $icons ) ? key( $icons ) : '';

			$newicons[ $font_data['name'] ] = array(
				'name'          => $font_data['name'],
				'label'         => $font_data['name'],
				'url'           => '',
				'enqueue'       => '',
				'prefix'        => '',
				'displayPrefix' => 'eci ',
				'labelIcon'     => ! empty( $first_icon ) ? 'eci ' . $first_icon : '',
				'ver'           => ECICONS_VERSION,
				'fetchJson'     => $font_data['json_url'],
			);
		}

		return array_merge( $tabs, $newicons );
	}



	/**
	 * Parse CSS file to get proper icon names
	 *
	 * @param string $css_file CSS file path.
	 * @param string $name Font name.
	 * @param string $url Font URL.
	 * @return array $icons Icon names.
	 */
	public function parse_css( $css_file, $name, $url ) {
		$css_source = $this->get_css_source( $css_file, $url );
		if ( '' === $css_source ) {
			return array();
		}

		$icons = array();

		preg_match_all( "/\.\w*?\-(.*?):\w*?\s*?{?\s*?{\s*?\w*?:\s*?\'\\\\?(\w*?)\'.*?}/", $css_source, $matches, PREG_SET_ORDER, 0 );
		foreach ( $matches as $match ) {
			$icons[ $name . '-' . $match[1] ] = $match[2];
		}

		return $icons;
	}

	/**
	 * Read CSS from local file and fallback URL.
	 *
	 * @param string $css_file CSS file path.
	 * @param string $url CSS URL.
	 * @return string
	 */
	protected function get_css_source( $css_file, $url ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file read from extracted archive.
		$css_source = file_get_contents( $css_file );
		if ( false !== $css_source ) {
			return $css_source;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
			)
		);
		if ( is_wp_error( $response ) || ! is_array( $response ) || empty( $response['body'] ) ) {
			return '';
		}

		return (string) $response['body'];
	}


	/**
	 * Parse CSS file to get proper icon names (reverse parse)
	 *
	 * @param string $css_file CSS file path.
	 * @param string $name Font name.
	 * @param string $url Font URL.
	 * @return array $icons Icon names.
	 */
	public function parse_css_reverse( $css_file, $name, $url ) {
		return $this->build_reverse_icons_map( $name, $this->parse_css( $css_file, $name, $url ) );
	}


	/**
	 * Remove folder recursively.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	protected function rrmdir( $dir ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( is_dir( $dir ) && strpos( $dir, (string) $this->upload_dir ) === 0 ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( '.' !== $object && '..' !== $object ) {
					if ( is_dir( $dir . '/' . $object ) ) {
						$this->rrmdir( $dir . '/' . $object );
					} else {
						wp_delete_file( $dir . '/' . $object );
					}
				}
			}
			global $wp_filesystem;
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();
			if ( $wp_filesystem ) {
				$wp_filesystem->rmdir( $dir, true );
			}
		}
	}

	/**
	 * Read a value from request input.
	 *
	 * @param string $name          Request key.
	 * @param bool   $default_value Default value when the key is missing.
	 * @param string $type          Request input source.
	 *
	 * @return bool|string
	 */
	protected function get_request( $name, $default_value = false, $type = 'POST' ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- Read-only helper; nonce validation is enforced in mutating handlers.
		$input_type = ( strtolower( $type ) === 'post' ) ? $_POST : $_GET;
		// phpcs:enable WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		if ( isset( $input_type[ $name ] ) ) {
			return sanitize_text_field( wp_unslash( (string) $input_type[ $name ] ) );
		}

		return $default_value;
	}
}

/**
 * Get plugin singleton instance.
 *
 * @return ECIcons
 */
function ec_icons_manager() { // phpcs:ignore WordPress.Files.FileStructure.MixedFunctionsAndOO.Mixed -- Bootstrap function intentionally exposed for compatibility.
	return ECIcons::get_instance();
}

ec_icons_manager();
