<?php
/**
* Plugin Name: Wordpress Flysystem S3
* Plugin URI: https://bluc.ir/
* Description: Upload wordpress media files to s3 media library
* Version: 1.0.0
* Author: Mehdi Abedi
* Author URI: https://bluc.ir/
**/

if (!defined('ABSPATH')) {
	exit(1);
}

function createDatabaseIfNeeded()
{
	global $wpdb;
	$table_name = $wpdb->base_prefix.'fs_s3_files';
	$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );

	$result = $wpdb->get_var( $query );

	if ( $result == $table_name ) {
		return;
	}

	$charset_collate = $wpdb->get_charset_collate();

	$query = "CREATE TABLE `{$table_name}` (
		`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`local_file` varchar(512) COLLATE utf8mb4_unicode_520_ci NOT NULL,
		`remote_file` varchar(512) COLLATE utf8mb4_unicode_520_ci NOT NULL,
		`md5` varchar(32) CHARACTER SET latin1 COLLATE latin1_danish_ci NOT NULL,
		`count` smallint(5) unsigned NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `local_file` (`local_file`,`remote_file`,`md5`) USING HASH,
		KEY `md5` (`md5`)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$res = dbDelta($query);
}

register_activation_hook(
	__FILE__,
	'createDatabaseIfNeeded'
);

require __DIR__."/vendor/autoload.php";

$serviceProvider = Abedi\WPFlysystemS3\ServiceProvider::getInstance();
$serviceProvider->boot();
