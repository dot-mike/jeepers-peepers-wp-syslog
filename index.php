<?php
/**
 * An extensible tool for recording WordPress events to a system log.
 *
 * @package jeepers-peepers
 * @version 0.5.4
 *
 * @wordpress-plugin
 * Plugin Name: Jeepers Peepers: WP Syslog
 * Plugin URI: https://wordpress.org/plugins/jeepers-peepers/
 * Description: An extensible tool for recording WordPress events to a system log.
 * Author: Blobfolio, LLC
 * Author URI: https://blobfolio.com/
 * Version: 0.5.4
 * Text Domain: jeepers-peepers
 * Domain Path: /languages/
 * License: WTFPL
 * License URI: http://www.wtfpl.net/
 */

// phpcs:disable SlevomatCodingStandard.Namespaces

// This must be called through WordPress.
if (! defined('ABSPATH')) {
	exit;
}



// ---------------------------------------------------------------------
// Setup
// ---------------------------------------------------------------------

// The plugin base path.
define('BLOBAUDIT_BASE_PATH', dirname(__FILE__) . '/');

// Is this installed as a Must-Use plugin?
$blobaudit_must_use = (
	defined('WPMU_PLUGIN_DIR') &&
	@is_dir(WPMU_PLUGIN_DIR) &&
	(0 === strpos(BLOBAUDIT_BASE_PATH, WPMU_PLUGIN_DIR))
);
define('BLOBAUDIT_MUST_USE', $blobaudit_must_use);



// Abort if the requirements aren't met.
if (
	version_compare(PHP_VERSION, '7.0.0') < 0 ||
	('WIN' === strtoupper(substr(PHP_OS, 0, 3))) ||
	(function_exists('is_multisite') && is_multisite())
) {
	/**
	 * Localization
	 *
	 * Deal with translations. Not sure why WP needs separate
	 * functions for must-use vs normal plugins, but whatever.
	 *
	 * @return void Nothing.
	 */
	function blobaudit_localize() {
		if (BLOBAUDIT_MUST_USE) {
			load_muplugin_textdomain(
				'jeepers-peepers',
				basename(BLOBAUDIT_BASE_PATH) . '/languages'
			);
		}
		else {
			load_plugin_textdomain(
				'jeepers-peepers',
				false,
				basename(BLOBAUDIT_BASE_PATH) . '/languages'
			);
		}
	}
	add_action('plugins_loaded', 'blobaudit_localize');

	/**
	 * Deactivate Plugin
	 *
	 * If installed the normal way, we can remove it automatically.
	 *
	 * @return void Nothing.
	 */
	function blobaudit_deactivate() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins(plugin_basename(__FILE__));
	}
	if (! BLOBAUDIT_MUST_USE) {
		add_action('admin_init', 'blobaudit_deactivate');
	}

	/**
	 * Admin Notice
	 *
	 * @return void Nothing.
	 */
	function blobaudit_notice() {
		?>
		<div class="error">
			<p>
				<?php
				echo __('Sorry, but your server does not meet the requirements for running', 'jeepers-peepers') . ' <strong>Jeepers Peepers</strong>. ';
				if (! BLOBAUDIT_MUST_USE) {
					echo __('It has been automatically deactivated for you.', 'jeepers-peepers');
				}
				else {
					echo __('Because you have added the plugin to the Must-Use folder, it must be manually deleted.', 'jeepers-peepers');
				}
				?>
			</p>
		</div>
		<?php
		if (isset($_GET['activate'])) {
			unset($_GET['activate']);
		}
	}
	add_action('admin_notices', 'blobaudit_notice');

	return;
} // End requirements failure.



// Come up with a site URL for logging purposes.
if (! defined('BLOBAUDIT_SITE_URL')) {
	$blobaudit_site_url = preg_replace(
		'/^www\./',
		'',
		parse_url(strtolower(site_url()), PHP_URL_HOST)
	);
	define('BLOBAUDIT_SITE_URL', $blobaudit_site_url);
}

// The log path.
if (! defined('BLOBAUDIT_LOG_PATH')) {
	define(
		'BLOBAUDIT_LOG_PATH',
		'/var/log/wordpress/' . BLOBAUDIT_SITE_URL . '.log'
	);
}

// Use UTC times for logging.
if (! defined('BLOBAUDIT_LOG_UTC')) {
	define(
		'BLOBAUDIT_LOG_UTC',
		true
	);
}

// The visitor IP.
if (
	isset($_SERVER['REMOTE_ADDR']) &&
	filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)
) {
	define('BLOBAUDIT_IP', $_SERVER['REMOTE_ADDR']);
}
else {
	define('BLOBAUDIT_IP', '127.0.0.1');
}

// GDPR notice.
add_action('admin_init', function() {
	if (function_exists('wp_add_privacy_policy_content')) {
		$privacy = __('This site records CMS events such as post and plugin changes to a standard system log for security and audit purposes. Where possible, these entries include the public IP address and/or WordPress username of the individual responsible.', 'jeepers-peepers');

		// Add the notice!
		wp_add_privacy_policy_content(
			'Jeepers Peepers',
			wp_kses_post(wpautop($privacy))
		);
	}
});

// Make sure the log works and exists.
try {
	// Try to create it if it doesn't exist.
	if (! @file_exists(BLOBAUDIT_LOG_PATH)) {
		@file_put_contents(BLOBAUDIT_LOG_PATH, '');
	}

	// Make sure it is a writeable file.
	if (
		! @is_file(BLOBAUDIT_LOG_PATH) ||
		! @is_writable(BLOBAUDIT_LOG_PATH)
	) {
		return;
	}
} catch (Throwable $e) {
	return;
} catch (Exception $e) {
	return;
}

// --------------------------------------------------------------------- end setup



// ---------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------

// Autoloader.
require BLOBAUDIT_BASE_PATH . 'lib/autoload.php';
\blobfolio\wp\audit\events::init();

// --------------------------------------------------------------------- end bootstrap
