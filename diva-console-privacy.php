<?php
/**
 * Plugin Name: Diva Console Privacy
 * Description: Administrator-controlled console privacy and optional public-information hardening.
 * Version: 1.1.0
 * Author: mazen
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class Diva_Console_Privacy {
    const OPTION = 'diva_console_privacy_options';
    private static $printed = false;
    private static $options;
    public static function defaults() {
        return array( 'quiet' => 1, 'errors' => 1, 'greeting' => 1, 'metadata' => 1,
            'php_display' => 1, 'headers' => 1, 'users' => 0, 'login' => 0,
            'title' => 'D I V A', 'message' => 'مرحباً بك في ديفا | Welcome to DIVA',
            'color' => '#edc4d6', 'background' => '#221326' );
    }
    public static function options() {
        if ( null === self::$options ) {
            $saved = get_option( self::OPTION, array() );
            self::$options = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
        }
        return self::$options;
    }
    public static function boot() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'links' ) );
        if ( defined( 'DIVA_CONSOLE_PRIVACY_DISABLED' ) && DIVA_CONSOLE_PRIVACY_DISABLED ) { return; }
        $o = self::options();
        add_action( 'wp_head', array( __CLASS__, 'output' ), -9999 );
        add_action( 'login_head', array( __CLASS__, 'output' ), -9999 );
        add_action( 'admin_print_scripts', array( __CLASS__, 'output' ), -9999 );
        if ( $o['metadata'] ) {
            remove_action( 'wp_head', 'wp_generator' );
            remove_action( 'wp_head', 'rsd_link' );
            remove_action( 'wp_head', 'wlwmanifest_link' );
            add_filter( 'the_generator', '__return_empty_string', 999 );
            add_filter( 'woocommerce_generator_tag', '__return_empty_string', 999 );
        }
        if ( $o['php_display'] && ! current_user_can( 'manage_options' ) ) {
            // Keep error reporting/logging intact. Earlier bootstrap/server errors need server configuration.
            @ini_set( 'display_errors', '0' );
            @ini_set( 'display_startup_errors', '0' );
        }
        if ( $o['headers'] ) {
            add_action( 'send_headers', array( __CLASS__, 'headers' ), 999 );
        }
        if ( $o['users'] ) {
            add_filter( 'rest_pre_dispatch', array( __CLASS__, 'users' ), 10, 3 );
        }
        if ( $o['login'] ) { add_filter( 'login_errors', array( __CLASS__, 'login_error' ) ); }
    }
    public static function headers() {
        if ( ! headers_sent() ) { header_remove( 'X-Powered-By' ); header_remove( 'X-Pingback' ); }
    }
    public static function users( $result, $server, $request ) {
        if ( null !== $result ) { return $result; }
        // Apply at dispatch, including embedded/batch requests. Do not alter WooCommerce/custom routes.
        if ( ! is_user_logged_in() && preg_match( '#^/wp/v2/users(?:/|$)#', $request->get_route() ) ) {
            return new WP_Error( 'rest_not_available', 'This resource requires authentication.', array( 'status' => 401 ) );
        }
        return $result;
    }
    public static function login_error( $message ) {
        global $errors;
        if ( $errors instanceof WP_Error ) {
            $codes = $errors->get_error_codes();
            $credential_codes = array( 'invalid_username', 'invalid_email', 'incorrect_password' );
            if ( $codes && ! array_diff( $codes, $credential_codes ) ) {
                return esc_html__( 'Unable to sign in. Check your details and try again.', 'diva-console-privacy' );
            }
        }
        return $message;
    }
    public static function output() {
        $o = self::options();
        if ( self::$printed || current_user_can( 'manage_options' ) || ( ! $o['quiet'] && ! $o['errors'] && ! $o['greeting'] ) ) { return; }
        self::$printed = true;
        $source = file_get_contents( __DIR__ . '/assets/visitor-console.js' );
        if ( false === $source ) { return; }
        $config = array_intersect_key( $o, array_flip( array( 'quiet', 'errors', 'greeting', 'title', 'message', 'color', 'background' ) ) );
        // JSON hex flags prevent an entered closing-script string escaping the script block.
        $json = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
        wp_print_inline_script_tag( str_replace( '/*DIVA_CONFIG*/{}', $json, $source ), array(
            'id' => 'diva-console-privacy', 'data-cfasync' => 'false', 'data-no-optimize' => '1',
            'data-no-defer' => '1', 'data-nowprocket' => '',
        ) );
    }
    public static function menu() {
        add_options_page( 'Diva Console Privacy', 'Diva Console Privacy', 'manage_options', 'diva-console-privacy', array( __CLASS__, 'page' ) );
    }
    public static function links( $links ) {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=diva-console-privacy' ) ) . '">Settings</a>' );
        return $links;
    }
    public static function register() {
        register_setting( 'diva_console_privacy', self::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( __CLASS__, 'sanitize' ), 'show_in_rest' => false ) );
    }
    public static function sanitize( $input ) {
        $input = is_array( $input ) ? $input : array();
        $out = self::defaults();
        foreach ( array( 'quiet', 'errors', 'greeting', 'metadata', 'php_display', 'headers', 'users', 'login' ) as $key ) {
            $out[ $key ] = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && '1' === (string) $input[ $key ] ? 1 : 0;
        }
        foreach ( array( 'title', 'message' ) as $key ) {
            if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) { $out[ $key ] = sanitize_text_field( $input[ $key ] ); }
        }
        foreach ( array( 'color', 'background' ) as $key ) {
            $color = isset( $input[ $key ] ) && is_string( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : null;
            if ( $color ) { $out[ $key ] = $color; }
        }
        return $out;
    }
    private static function toggle( $key, $label, $help ) {
        $o = self::options();
        echo '<p><label><input type="checkbox" name="' . esc_attr( self::OPTION . '[' . $key . ']' ) . '" value="1" ' . checked( $o[ $key ], 1, false ) . '> <strong>' . esc_html( $label ) . '</strong></label><br><span class="description">' . esc_html( $help ) . '</span></p>';
    }
    public static function page() {
        if ( ! current_user_can( 'manage_options' ) ) { return; }
        $o = self::options();
        ?>
        <div class="wrap" style="max-width:920px">
        <div style="background:#221326;color:#edc4d6;padding:24px;border-radius:12px;margin:18px 0">
        <h1 style="color:inherit;padding:0">DIVA · Console Privacy</h1><p style="margin-bottom:0">Visitor privacy controls · Version 1.1.0</p></div>
        <?php settings_errors(); ?>
        <?php if ( defined( 'DIVA_CONSOLE_PRIVACY_DISABLED' ) && DIVA_CONSOLE_PRIVACY_DISABLED ) : ?>
        <div class="notice notice-warning inline"><p>The emergency bypass is enabled in wp-config.php. All protection features are currently bypassed.</p></div>
        <?php endif; ?>
        <p><strong>Administrator debugging is always preserved.</strong> Test visitor behavior in a private browser window. These controls reduce public information; they cannot make WordPress invisible or fix vulnerable code.</p>
        <form method="post" action="options.php">
        <?php settings_fields( 'diva_console_privacy' ); ?>
        <div style="background:white;padding:18px 24px;border:1px solid #ddd;border-radius:10px;margin:18px 0">
        <h2>Visitor console</h2>
        <?php
        self::toggle( 'quiet', 'Silence console messages', 'Suppress standard JavaScript console calls for everyone except administrators.' );
        self::toggle( 'errors', 'Hide suppressible JavaScript errors', 'Suppress default uncaught error and promise-rejection reporting. Browser/network errors may remain; failing code still fails.' );
        self::toggle( 'greeting', 'Show branded greeting', 'Print your styled message once per page.' );
        foreach ( array( 'title' => 'Console title', 'message' => 'Welcome message', 'color' => 'Text color', 'background' => 'Background color' ) as $key => $label ) {
            $type = in_array( $key, array( 'color', 'background' ), true ) ? 'color' : 'text';
            echo '<p><label for="dcp-' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br><input id="dcp-' . esc_attr( $key ) . '" type="' . esc_attr( $type ) . '" class="regular-text" name="' . esc_attr( self::OPTION . '[' . $key . ']' ) . '" value="' . esc_attr( $o[ $key ] ) . '"></p>';
        }
        ?></div>
        <div style="background:white;padding:18px 24px;border:1px solid #ddd;border-radius:10px;margin:18px 0">
        <h2>Public information</h2>
        <?php
        self::toggle( 'metadata', 'Remove standard generator metadata', 'Remove core/WooCommerce generator tags and legacy editor discovery links. Asset paths and third-party signatures remain visible.' );
        self::toggle( 'php_display', 'Disable PHP error display for non-administrators', 'Request-level control after plugin initialization; keeps server logging unchanged. Earlier errors require server configuration.' );
        self::toggle( 'headers', 'Remove PHP and pingback disclosure headers', 'Remove X-Powered-By and X-Pingback on normal WordPress page responses when PHP can remove them. Proxy/server-added headers require server changes.' );
        ?></div>
        <div style="background:white;padding:18px 24px;border:1px solid #ddd;border-radius:10px;margin:18px 0">
        <h2>Optional compatibility-sensitive controls</h2>
        <?php
        self::toggle( 'users', 'Require login for core REST user endpoints', 'Off by default. Can affect public author data and embeds. Does not hide author names elsewhere or change Diva social/WooCommerce API routes.' );
        self::toggle( 'login', 'Use generic core login credential errors', 'Off by default. Reduces username hints for standard login failures. Custom OTP, password-reset and third-party forms are outside this control.' );
        ?></div>
        <?php submit_button( 'Save settings' ); ?>
        </form>
        <p><strong>After saving:</strong> purge WP Rocket and Cloudflare HTML caches. Keep logged-in pages excluded from shared caching. If scripts run before the greeting, exclude <code>diva-console-privacy</code> and <code>__divaConsolePrivacyActive</code> from script delay/combination.</p>
        <h2>Server-level protection still required</h2>
        <p>Keep PHP error display disabled in production configuration. Block public access to logs, backups, database dumps, configuration copies and directory listings at the web server. A WordPress plugin cannot block static files served directly by Apache or your CDN.</p>
        <p>Inspect Network and page source for sensitive data and remove it at its source. This plugin does not rewrite assets, disable REST APIs globally, change login URLs, or modify server files.</p>
        </div>
        <?php
    }
}
add_action( 'init', array( 'Diva_Console_Privacy', 'boot' ), 1 );
