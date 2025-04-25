<?php
/**
 * Events
 *
 * This registers event hooks and watches for action.
 *
 * @package jeepers-peepers
 * @author	Blobfolio, LLC <hello@blobfolio.com>
 */

namespace blobfolio\wp\audit;

use WP_User;

class events {

	const ACTIONS = array(
		// Logins.
		'meow_do_apocalypse'=>1,
		'after_password_reset'=>2,
		'delete_user'=>2,
		'user_register'=>1,
		'wp_login'=>1,
		'wp_login_failed'=>1,
		// Networking.
		'http_api_debug'=>5,
		// Plugins.
		'activated_plugin'=>1,
		'deactivated_plugin'=>1,
		// Posts.
		'delete_post'=>1,
		'publish_post'=>2,
	);

	// Some things don't have proper actions.
	const FILTERS = array(
		// Logins.
		'send_password_change_email'=>3,
		// Networking.
		'http_request_args'=>1,
		// Plugins.
		'upgrader_post_install'=>3,
		// Content.
		'wp_die_handler'=>1,
		'wp_handle_upload'=>2,
	);

	const PRIORITY = 50;

	protected static $init = false;
	protected static $network_request_time;

	/**
	 * Init
	 *
	 * Register hooks.
	 *
	 * @return void Nothing.
	 */
	public static function init() {
		if (! static::$init) {
			static::$init = true;

			foreach (static::ACTIONS as $action=>$args) {
				\add_action(
					$action,
					array(static::class, $action),
					static::PRIORITY,
					$args
				);
			}

			foreach (static::FILTERS as $action=>$args) {
				\add_filter(
					$action,
					array(static::class, $action),
					static::PRIORITY,
					$args
				);
			}

			// And localize.
			\add_action('plugins_loaded', array(static::class, 'localize'));

			// Set up custom actions so plugins and themes can hook safely.
			foreach (log::SEVERITIES as $action) {
				\add_action(
					"syslog_$action",
					array('blobfolio\\wp\\audit\\log', $action),
					10,
					2
				);
			}
		}
	}



	// -----------------------------------------------------------------
	// Logins
	// -----------------------------------------------------------------

	/**
	 * New User
	 *
	 * @param int $user_id User ID.
	 * @return void Nothing.
	 */
	public static function user_register($user_id=0) {
		$user_id = (int) $user_id;
		if ($user_id > 0) {
			$user = \get_user_by('id', $user_id);
			$username = '';
			if (
				$user instanceof WP_User &&
				isset($user->user_login) &&
				! empty($user->user_login)
			) {
				$username = \sprintf("\x20%s", $user->user_login);
			}

			$message = \sprintf(
				\__('User created', 'jeepers-peepers') . ': %s',
				$username
			);
			log::warning($message);
		}
	}

	/**
	 * New Login
	 *
	 * @param string $title Title.
	 * @return void Nothing.
	 */
	public static function wp_login($title='') {
		if (! $title) {
			$title = \__('Unknown', 'jeepers-peepers');
		}

		$message = \sprintf(
			\__('User authentication succeeded', 'jeepers-peepers') . ': %s',
			$title
		);
		log::notice($message);
	}

	/**
	 * Failed Login
	 *
	 * @param string $title Title.
	 * @return void Nothing.
	 */
	public static function wp_login_failed($title='') {
		if (! $title) {
			$title = \__('Unknown', 'jeepers-peepers');
		}

		// Hook into the Apocalypse Meow whitelist if available
		// so we can distinguish between people who should be
		// punished for failing too much.
		$whitelist = '';
		if (
			\function_exists('meow_is_whitelisted') &&
			\meow_is_whitelisted(\BLOBAUDIT_IP)
		) {
			$whitelist = '(' . \__('whitelist', 'jeepers-peepers') . ')';
		}

		$title = \sanitize_user($title, true);
		$message = \sprintf(
			\__('User', 'jeepers-peepers') . " $whitelist " . \__('authentication failed', 'jeepers-peepers') . ': %s',
			$title
		);
		log::error($message);
	}

	/**
	 * Banned Login
	 *
	 * @param string $title Title.
	 * @return void Nothing.
	 */
	public static function meow_do_apocalypse($title='') {
		if (! $title) {
			$title = \__('Unknown', 'jeepers-peepers');
		}

		$title = \sanitize_user($title, true);
		$message = "Apocalypse Meow: $title";

		log::error($message);
	}

	/**
	 * Password Reset
	 *
	 * @param object $user User.
	 * @param string $password Password.
	 * @return void Nothing.
	 */
	public static function after_password_reset($user=null, $password='') {
		$username = \__('Unknown', 'jeepers-peepers');
		if (
			$user instanceof WP_User &&
			isset($user->user_login) &&
			! empty($user->user_login)
		) {
			$username = \sprintf("\x20%s", $user->user_login);
		}

		$message = \sprintf(
			\__('Password reset', 'jeepers-peepers') . ': %s',
			$username
		);
		log::warning($message);
	}

	/**
	 * Also Password Reset
	 *
	 * @param bool $send Send email.
	 * @param array $user Old User.
	 * @param array $user2 New User.
	 * @return bool Send.
	 */
	public static function send_password_change_email($send=true, $user=null, $user2=null) {

		if (\is_array($user) && isset($user['ID'])) {
			$user = \get_user_by('id', $user['ID']);
			static::after_password_reset($user, '');
		}

		// Don't override.
		return $send;
	}

	/**
	 * Delete User
	 *
	 * @param int $user_id User ID.
	 * @param int $reassign Reassign.
	 * @return void Nothing.
	 */
	public static function delete_user($user_id=0, $reassign=null) {
		$user = \get_user_by('id', $user_id);
		$username = \__('Unknown', 'jeepers-peepers');
		if (
			$user instanceof WP_User &&
			isset($user->user_login) &&
			! empty($user->user_login)
		) {
			$username = \sprintf("\x20%s", $user->user_login);
		}

		$message = \sprintf(
			\__('User deleted', 'jeepers-peepers') . ': %s',
			$username
		);
		log::warning($message);
	}



	// -----------------------------------------------------------------
	// Plugins
	// -----------------------------------------------------------------

	/**
	 * Activate Plugin
	 *
	 * @param string $plugin Plugin.
	 * @return void Nothing.
	 */
	public static function activated_plugin($plugin='') {
		$plugin = \dirname($plugin);

		$message = \sprintf(
			\__('Activated plugin', 'jeepers-peepers') . ': %s',
			$plugin
		);
		log::warning($message);
	}

	/**
	 * Activate Plugin
	 *
	 * @param string $plugin Plugin.
	 * @return void Nothing.
	 */
	public static function deactivated_plugin($plugin='') {
		$plugin = \dirname($plugin);

		$message = \sprintf(
			\__('Deactivated plugin', 'jeepers-peepers') . ': %s',
			$plugin
		);
		log::warning($message);
	}

	/**
	 * Upgrade Plugin
	 *
	 * @param bool $status Status.
	 * @param array $args Args.
	 * @param mixed $result Result.
	 * @return bool Status.
	 */
	public static function upgrader_post_install($status=true, $args=null, $result=null) {
		if (isset($args['plugin'])) {
			$plugin = \dirname($args['plugin']);

			if ($status) {
				$message = \__('Upgraded plugin', 'jeepers-peepers');
			}
			else {
				$message = \__('Plugin upgrade failed', 'jeepers-peepers');
			}

			$message .= ": $plugin";
			log::notice($message);
		}

		return $status;
	}



	// -----------------------------------------------------------------
	// Content
	// -----------------------------------------------------------------

	/**
	 * Published Post
	 *
	 * @param int $pid Post ID.
	 * @param object $post Post.
	 * @return void Nothing.
	 */
	public static function publish_post($pid, $post) {
		$message = \sprintf(
			\__('Published post', 'jeepers-peepers') . ': %s',
			$post->post_title
		);
		log::notice($message);
	}

	/**
	 * Deleted Post
	 *
	 * @param int $pid Post ID.
	 * @return void Nothing.
	 */
	public static function delete_post($pid) {
		// Don't log autosaves or revisions.
		if (! \wp_is_post_revision($pid) && ! \wp_is_post_autosave($pid)) {
			$post = \get_post($pid);

			// Deleted attachments trigger this because they have a
			// post component.
			if ('attachment' === $post->post_type) {
				$message = \sprintf(
					\__('Deleted attachment', 'jeepers-peepers') . ': %s',
					\basename($post->guid)
				);
			}
			else {
				$message = \sprintf(
					\__('Deleted post', 'jeepers-peepers') . ': %s',
					$post->post_title
				);
			}

			log::notice($message);
		}
	}

	/**
	 * Upload/Sideload Attachment
	 *
	 * @param array $file File.
	 * @param string $mode Mode.
	 * @return array File.
	 */
	public static function wp_handle_upload($file, $mode='upload') {
		if (\is_array($file) && isset($file['file']) && $file['file']) {
			// There are a few different routes to here.
			if ('upload' === $mode) {
				$message = \__('Uploaded attachment', 'jeepers-peepers');
			}
			elseif ('sideload' === $mode) {
				$message = \__('Sideloaded attachment', 'jeepers-peepers');
			}
			else {
				$message = \__('New attachment', 'jeepers-peepers');
			}
			$message .= ': ' . \basename($file['file']);

			log::notice($message);
		}

		return $file;
	}

	/**
	 * WP Die
	 *
	 * @param string $callback Callback.
	 * @return string Callback.
	 */
	public static function wp_die_handler($callback='') {
		if (\is_string($callback) && $callback) {
			$page = $_SERVER['REQUEST_URI'] ?? 'Unknown';
			$message = \sprintf(
				'wp_die(): %s',
				$page
			);
			log::error($message);
		}

		return $callback;
	}



	// -----------------------------------------------------------------
	// Networking
	// -----------------------------------------------------------------

	/**
	 * Network Request: Timer
	 *
	 * WordPress doesn't set a timer when fetching external resources.
	 * As a workaround, we're hijacking an early filter to set a static
	 * start time which we can access from our actual logging hook to
	 * derive an elapsed time.
	 *
	 * @param mixed $args Arguments.
	 * @return mixed Args.
	 */
	public static function http_request_args($args) {
		static::$network_request_time = \microtime(true);
		return $args;
	}

	/**
	 * Network Request: Log
	 *
	 * Log the completed network request.
	 *
	 * @param array|WP_Error $response Response.
	 * @param string $context Context.
	 * @param string $class Calling class.
	 * @param array $args Arguments.
	 * @param string $url URL.
	 * @return void Nothing.
	 */
	public static function http_api_debug($response, $context, $class, $args, string $url) {
		// Ignore CRON requests.
		if (false !== \strpos($url, 'wp-cron.php?doing_wp_cron')) {
			return;
		}

		// We need a URL.
		if (! $url) {
			return;
		}

		// Parse elapsed time.
		if (\is_float(static::$network_request_time)) {
			$elapsed = \microtime(true) - static::$network_request_time;
			static::$network_request_time = null;
		}
		else {
			$elapsed = 0.0;
		}

		if ($elapsed < 0) {
			$elapsed = 0.0;
		}

		// The method.
		$method = $args['method'] ?? 'GET';
		if (! \in_array(
			$method,
			array('DELETE', 'GET', 'HEAD', 'PATCH', 'POST', 'PUT'),
			true
		)) {
			$method = 'GET';
		}

		// The status.
		if (\is_wp_error($response)) {
			$status = 0;
		}
		else {
			$status = (int) \wp_remote_retrieve_response_code($response);
		}

		// Build the message.
		$message = \sprintf(
			'%s: %s %d %s (%0.5fs)',
			\__('Network Request', 'jeepers-peepers'),
			$method,
			$status,
			$url,
			$elapsed
		);

		// A complete failure is an error.
		if (! $status) {
			log::error($message);
		}
		// A failed status is a warning.
		elseif ($status >= 400) {
			log::warning($message);
		}
		// Everything else is just a notice.
		else {
			log::notice($message);
		}
	}



	// -----------------------------------------------------------------
	// Self Improvement
	// -----------------------------------------------------------------

	/**
	 * Localization
	 *
	 * Deal with translations. Not sure why WP needs separate
	 * functions for must-use vs normal plugins, but whatever.
	 *
	 * @return void Nothing.
	 */
	public static function localize() {
		if (\BLOBAUDIT_MUST_USE) {
			\load_muplugin_textdomain(
				'jeepers-peepers',
				\basename(\BLOBAUDIT_BASE_PATH) . '/languages'
			);
		}
		else {
			\load_plugin_textdomain(
				'jeepers-peepers',
				false,
				\basename(\BLOBAUDIT_BASE_PATH) . '/languages'
			);
		}
	}
}
