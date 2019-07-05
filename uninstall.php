<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

function ecicons_rrmdir( $dir ) {

	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( $object != "." && $object != ".." ) {
				if ( is_dir( $dir . "/" . $object ) ) {
					ecicons_rrmdir( $dir . "/" . $object );
				} else {
					unlink( $dir . "/" . $object );
				}
			}
		}
		rmdir( $dir );
	}

}

$upload = wp_upload_dir();
$upload_dir  = $upload['basedir'] . '/elementor_icons_files';

$options = get_option( 'ec_icons_fonts' );

if ( !empty( $options ) && is_array($options) ) {

	foreach ( $options as $key => $font ) {

		if ( empty( $font['data'] ) ) {
			continue;
		}

		$font_decode = json_decode($font['data'],true);

		ecicons_rrmdir( $upload_dir . '/' . $font_decode['file_name'] );
		if ( file_exists( $upload_dir . '/' . $font_decode['name'] . '.json' ) ) {
			unlink( $upload_dir . '/' . $font_decode['name'] . '.json' );
		}

	}

}
unlink( $upload_dir . '/merged-icons-font.css');
delete_option('ec_icons_fonts');