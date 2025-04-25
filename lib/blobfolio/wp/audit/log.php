<?php
/**
 * Log Helper
 *
 * This records log messages.
 *
 * @package jeepers-peepers
 * @author	Blobfolio, LLC <hello@blobfolio.com>
 */

namespace blobfolio\wp\audit;

use Throwable;
use WP_User;

class log {

	const SEVERITIES = array(
		0=>'debug',
		1=>'notice',
		2=>'info',
		3=>'warning',
		4=>'error',
		5=>'critical',
	);

	// Prevent duplicate entries in a single run.
	private static $_logged = array();

	/**
	 * Record Event
	 *
	 * @param int $severity Severity.
	 * @param string $message Message.
	 * @param bool $internal Internal.
	 * @return bool True/false.
	 */
	public static function save($severity=0, $message='', $internal=false) {
		if (! \defined('BLOBAUDIT_LOG_PATH')) {
			return false;
		}

		$user = \function_exists('wp_get_current_user') ? \wp_get_current_user() : null;
		$username = false;

		// Identify current user in session.
		if (
			$user instanceof WP_User &&
			isset($user->user_login) &&
			! empty($user->user_login)
		) {
			$username = \sprintf("\x20%s", $user->user_login);
		}

		// Fix severity value.
		$severity = (int) $severity;
		if (\array_key_exists($severity, static::SEVERITIES)) {
			$severity_name = static::SEVERITIES[$severity];
		}
		else {
			$severity_name = 'info';
		}

		// Mark the event as internal if necessary.
		if (true === $internal) {
			$severity_name = '@' . $severity_name;
		}

		// Clear event message.
		foreach (array('username', 'message') as $field) {
			$$field = \wp_kses_no_null($$field);
			$$field = \preg_replace('/\s+/u', ' ', $$field);
			$$field = \strip_tags($$field);
			$$field = \trim(\preg_replace('/\s+/u', ' ', $$field));
			$$field = \str_replace('"', '', $$field);
		}

		// Make sure we haven't already logged an identical entry
		// during this session.
		$soup = array(
			$severity_name,
			\BLOBAUDIT_SITE_URL,
			\BLOBAUDIT_IP,
			$username,
			$message,
		);
		$soup = \md5(\json_encode($soup));
		if (\in_array($soup, static::$_logged, true)) {
			return false;
		}
		static::$_logged[] = $soup;

		// Okedoke, try and log it.
		try {
			@\error_log(
				\sprintf(
					\__('WordPressAudit', 'jeepers-peepers') . " %s [%s] %s %s \"%s\" \"%s\"\n",
					\BLOBAUDIT_LOG_UTC ? \date('Y-m-d H:i:s') : \current_time('Y-m-d H:i:s'),
					$severity_name,
					\BLOBAUDIT_SITE_URL,
					\BLOBAUDIT_IP,
					$username,
					$message
				),
				3,
				\BLOBAUDIT_LOG_PATH
			);
		} catch (Throwable $e) {
			return false;
		}
	}

	/**
	 * Debug Event
	 *
	 * @param string $message Message.
	 * @param bool $internal Internal.
	 * @return bool True/false.
	 */
	public static function debug($message='', $internal=false) {
		return static::save(0, $message, $internal);
	}

	/**
	 * Notice Event
	 *
	 * @param string $message Message.
	 * @param bool $internal Internal.
	 * @return bool True/false.
	 */
	public static function notice($message='', $internal=false) {
		return static::save(1, $message, $internal);
	}

	/**
	 * Info Event
	 *
	 * @param string $message Message.
	 * @param bool $internal Internal.
	 * @return bool True/false.
	 */
	public static function info($message='', $internal=false) {
		return static::save(2, $message, $internal);
	}

	/**
	 * Warning Event
	 *
	 * @param string $message Message.
	 * @param bool $internal Internal.
	 * @return bool True/false.
	 */
	public static function warning($message='', $internal=false) {
		return static::save(3, $message, $internal);
	}

	/**
	 * Error Event
	 *
	 * @param string $message Message.
	 * @param bool $internal Internal.
	 * @return bool True/false.
	 */
	public static function error($message='', $internal=false) {
		return static::save(4, $message, $internal);
	}

	/**
	 * Critical Event
	 *
	 * @param string $message Message.
	 * @param bool $internal Internal.
	 * @return bool True/false.
	 */
	public static function critical($message='', $internal=false) {
		return static::save(5, $message, $internal);
	}
}
