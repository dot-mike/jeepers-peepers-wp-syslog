=== Jeepers Peepers: WP Syslog ===
Contributors: blobfolio
Donate link: https://blobfolio.com/donate.html
Tags: system log, syslog, event log, audit trail, security
Requires at least: 4.7
Tested up to: 6.8
Requires PHP: 7.3
Stable tag: 0.5.4
License: WTFPL
License URI: http://www.wtfpl.net/

An extensible tool for recording WordPress events to a system log.

== Description ==

Jeepers Peepers provides an extensible interface for recording WordPress events — user logins, file uploads, post deletions, etc. — to a standard system log.

The resulting audit trail can then be incorporated into powerful log-monitoring tools like [OSSEC](https://ossec.github.io/) for pre-emptive protection and, in the unfortunate event of a hack, used as a vital reference in the post-mortem investigation.

The following events are automatically logged:

 * Content: `wp_die()` triggered;
 * Content: attachment deleted;
 * Content: attachment sideloaded;
 * Content: attachment uploaded;
 * Content: post deleted;
 * Content: post published; 
 * Network: GET, HEAD, POST, etc., requests;
 * Plugin: activated;
 * Plugin: deactivated;
 * Plugin: upgraded;
 * User: deleted;
 * User: login banned (via [Apocalypse Meow](https://wordpress.org/plugins/apocalypse-meow/));
 * User: login failed;
 * User: login succeeded;
 * User: new user;
 * User: password reset;

Each log entry records:

 * UTC timestamp;
 * Severity level;
 * User IP address (or `127.0.0.1` if automated);
 * Logged in username (if applicable);
 * Event message;

It will look something like this:

`
WordPressAudit 2017-05-24 16:35:45 [warning] yourdomain.com 68.256.55.123 "tiffany" "Deactivated plugin: look-see-security-scanner"
`

== Requirements ==

 * WordPress 4.7 or later.
 * PHP 7.3 or later.
 * Linux host.
 * Single-site instance.
 * Log file must be writeable by WordPress.

Please note: it is **not safe** to run WordPress atop a version of PHP that has reached its [End of Life](http://php.net/supported-versions.php). Future releases of this plugin might, out of necessity, drop support for old, unmaintained versions of PHP. To ensure you continue to receive plugin updates, bug fixes, and new features, just make sure PHP is kept up-to-date. :)

== Frequently Asked Questions ==

= Is this compatible with Multi-Site? =

Sorry, no. This plugin can only be added to standard (single-site) WordPress installations.

= The log isn't updating... =

1. Make sure the log file exists. The default location is `/var/log/wordpress/{YOUR_SITE_DOMAIN}.log`, but this can be overridden by defining a constant in your `wp-config.php` file (see the relevant FAQ section below).
2. Make sure WordPress/PHP can reach the file. For PHP sites with `open_basedir` restrictions, this means whitelisting the path to the log file. If the hosting environment is chrooted or jailed, the log location will need to be within the same boundaries.
3. Make sure the log file's ownership/permissions allow PHP to write changes to it. This varies by environment, but a good place to start is assigning the same owner:group to the log file used by your WordPress files.

= Does this require any theme or config changes? =

By default, the log is written to `/var/log/wordpress/{YOUR_SITE_DOMAIN}.log`. If this path exists and works for you, then no, logging will happen without any intervention.

To modify the default behavior, you will need to define a couple constants in your `wp-config.php` file. See the relevant FAQ section below.

= List of configuration constants =

The following constants can be defined in your `wp-config.php` file to override the default behaviors.

 * (*string*) **BLOBAUDIT_SITE_URL** Your site's domain, for logging purposes. By default, this will be your site's domain name, lowercased, and without a leading `www.` subdomain.
 * (*string*) **BLOBAUDIT_LOG_PATH** The absolute path to the log file. Default: `/var/log/wordpress/{YOUR_SITE_DOMAIN}.log`
 * (*bool*) **BLOBAUDIT_LOG_UTC** Record datetimes in UTC rather than the site's timezone. Default: `true`

When using a custom log location, please choose one that is outside the web root. You don't want just anybody looking at it. :)

= Logging custom events =

The plugin includes action callbacks you can trigger in your code to record a custom event.

`
// In order of severity...
do_action('syslog_debug', $message, $internal);
do_action('syslog_notice', $message, $internal);
do_action('syslog_info', $message, $internal);
do_action('syslog_warning', $message, $internal);
do_action('syslog_error', $message, $internal);
do_action('syslog_critical', $message, $internal);
`

All actions accept the following:

 * (*string*) **$message** The event message.
 * (*bool*) (*optional*) **$internal** Prefix the severity with an `@` to mark it as "internal". Default: `FALSE`

== Screenshots ==

1. Example log file.

== Installation ==

Nothing fancy!  You can use the built-in installer on the Plugins page or extract and upload the `jeepers-peepers` folder to your plugins directory via FTP.

To install this plugin as [Must-Use](https://codex.wordpress.org/Must_Use_Plugins), download, extract, and upload the `jeepers-peepers` folder to your mu-plugins directory via FTP. Please note: MU Plugins are removed from the usual update-checking process, so you will need to handle future updates manually.

== Privacy Policy ==

Jeepers Peepers records CMS events such as post and plugin changes to a standard system log for security and audit purposes. Where possible, these entries include the public IP address and/or WordPress username of the individual responsible.

This plugin does not send any of this information to remote locations or third parties.

Please note: Jeepers Peepers *DOES NOT* integrate with any WordPress GDPR "Personal Data" features. (Selective erasure of audit logs would undermine the very purpose of this plugin! Haha.)

== Changelog ==

= 0.5.4 =
* [New] `BLOBAUDIT_LOG_UTC` constant for toggling between UTC/site-time for event dates.

= 0.5.3 =
* [New] Log network requests.
* [Improve] Minor code optimizations.

= 0.5.2 =
* [New] Add privacy policy hook for GDPR compliance.

= 0.5.1 =
* [New] Initial launch!

== Upgrade Notice ==

= 0.5.4 =
This release adds a new `BLOBAUDIT_LOG_UTC` configuration constant allowing sites to record events in the site's local timezone rather than UTC.

= 0.5.3 =
This release adds network request logging and minor code optimizations.

= 0.5.2 =
Add privacy policy hook for GDPR compliance.

= 0.5.1 =
This is the first WP-hosted release! Woo!
