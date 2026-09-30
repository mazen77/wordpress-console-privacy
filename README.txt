DIVA CONSOLE PRIVACY 1.1.0 — Author: mazen

INSTALL / UPDATE
Upload this ZIP in Plugins > Add New > Upload Plugin. Replace the installed 1.0.0
when WordPress prompts. Keep the same diva-console-privacy folder.
Open Settings > Diva Console Privacy, or click Settings beside the installed plugin.
Save settings, then purge WP Rocket and Cloudflare HTML caches.

NEW IN 1.1.0
Real settings page with independent switches, editable greeting and colors.
Default-on standard generator/legacy discovery removal, request-level PHP display
suppression for non-admins, and removable X-Powered-By/X-Pingback page headers.
Optional core REST user authentication requirement and generic core login errors.
These optional controls default OFF for social feed and integration compatibility.
Settings use the WordPress Settings API, manage_options permission, nonce checks,
input sanitization, escaped HTML output and script-safe JSON encoding.

CONSOLE / PERFORMANCE
Administrators (manage_options) always retain normal console behavior.
Other roles, including shop managers and customers, use visitor settings.
No AJAX, remote requests, cron, telemetry, polling, or logging database.
One options entry, written only on settings save. Does not collect visitor errors.

LIMITS
Visitors can inspect downloaded source/network data and bypass console controls.
Browser errors, network failures, CSP errors, extensions, workers/iframes and
previously captured console methods may remain. Console suppression is cosmetic.
Errors still halt failing code. It does not fix the RMA selector or React errors.
WordPress and plugins can still be fingerprinted by public URLs and source code.
No blanket REST blocking, asset version stripping, URL renaming or server-file edits.
Metadata removal covers standard generators, not all plugin-injected signatures.
REST restriction covers anonymous /wp/v2/users routes, not authors elsewhere.
Custom OTP/password reset responses are not changed by generic core login errors.
Header removal applies to normal WP pages; web-server/CDN headers need server edits.
PHP display suppression starts during init and can be overridden by later code.
Production display_errors and WP_DEBUG_DISPLAY must also be disabled at their source.
Protect public logs/backups/config copies at the web server; PHP cannot protect
files served directly. Keep server error logs private. No automatic server changes.

CACHING
Exclude authenticated pages from shared caching. Purge HTML caches on save/update/
deactivation. Exclude diva-console-privacy and __divaConsolePrivacyActive from
JS delay/defer/combination when needed. Honor your CSP; plugin does not weaken it.

RECOVERY
Deactivate and purge caches, or set in wp-config.php before stop-editing:
define( 'DIVA_CONSOLE_PRIVACY_DISABLED', true );
This bypass disables all runtime controls while preserving access to settings.
Settings persist on deactivation/deletion, so reinstalling retains preferences.

VALIDATION
JavaScript syntax and isolated settings behavior tested. PHP runtime/live WordPress
not available here. Test settings saving and storefront/cart/checkout/login/admin
on staging. No claim of a live security audit or complete attacker-information hiding.
