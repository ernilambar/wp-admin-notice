<?php
/**
 * PHPUnit bootstrap: stubs the WordPress functions used by Notice.
 *
 * The library itself has no WordPress dependency beyond the handful of
 * functions stubbed below, so tests run without a WordPress install.
 *
 * @package WPAdminNotice
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Notice.php bails unless it believes it is inside WordPress.
defined( 'WPINC' ) || define( 'WPINC', 'wp-includes' );

/**
 * Mutable test state.
 *
 * Reset between tests via wpan_reset_state() so option writes, user meta and
 * the simulated clock never leak across test methods.
 */
final class WPAN_Test_State {

	/** @var array Site options keyed by option name. */
	public static $options = [];

	/** @var array User meta keyed by meta key. */
	public static $user_meta = [];

	/** @var array Hook names passed to add_action(). */
	public static $actions = [];

	/** @var array Hook names passed to do_action(). */
	public static $did_actions = [];

	/** @var string Value returned by the current_datetime() stub. */
	public static $now = '2026-01-01 00:00:00';

	/** @var bool Value returned by the current_user_can() stub. */
	public static $can = true;

	/** @var string Screen ID returned by the get_current_screen() stub. */
	public static $screen = 'dashboard';

	/** @var bool Value returned by the is_admin() stub. */
	public static $is_admin = true;

	/** @var bool Whether get_current_screen() should exist. */
	public static $has_screen_function = true;
}

/**
 * Restore all stub state to its defaults.
 *
 * @return void
 */
function wpan_reset_state() {
	WPAN_Test_State::$options             = [];
	WPAN_Test_State::$user_meta           = [];
	WPAN_Test_State::$actions             = [];
	WPAN_Test_State::$did_actions         = [];
	WPAN_Test_State::$now                 = '2026-01-01 00:00:00';
	WPAN_Test_State::$can                 = true;
	WPAN_Test_State::$screen              = 'dashboard';
	WPAN_Test_State::$is_admin            = true;
	WPAN_Test_State::$has_screen_function = true;
}

// ---------------------------------------------------------------------------
// WordPress function stubs.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return WPAN_Test_State::$is_admin;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		WPAN_Test_State::$actions[] = $hook;
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		WPAN_Test_State::$did_actions[] = $hook;
	}
}

if ( ! function_exists( 'get_site_option' ) ) {
	function get_site_option( $option, $default = false ) {
		return array_key_exists( $option, WPAN_Test_State::$options )
			? WPAN_Test_State::$options[ $option ]
			: $default;
	}
}

if ( ! function_exists( 'update_site_option' ) ) {
	function update_site_option( $option, $value ) {
		WPAN_Test_State::$options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key = '', $single = false ) {
		return array_key_exists( $key, WPAN_Test_State::$user_meta )
			? WPAN_Test_State::$user_meta[ $key ]
			: '';
	}
}

if ( ! function_exists( 'update_user_meta' ) ) {
	function update_user_meta( $user_id, $key, $value, $prev_value = '' ) {
		WPAN_Test_State::$user_meta[ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 1;
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return (object) [ 'ID' => 1 ];
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return WPAN_Test_State::$can;
	}
}

if ( ! function_exists( 'current_datetime' ) ) {
	function current_datetime() {
		return new DateTimeImmutable( WPAN_Test_State::$now );
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $key, $value = '' ) {
		return 'https://example.test/wp-admin/index.php?' . rawurlencode( $key ) . '=' . rawurlencode( $value );
	}
}

if ( ! function_exists( 'wp_nonce_url' ) ) {
	function wp_nonce_url( $actionurl, $action = -1, $name = '_wpnonce' ) {
		return $actionurl . '&' . $name . '=' . wp_create_nonce( $action );
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		// Deterministic stand-in so tests can forge a valid nonce.
		return 'nonce-' . md5( (string) $action );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = -1 ) {
		return hash_equals( wp_create_nonce( $action ), (string) $nonce ) ? 1 : false;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		$str = strip_tags( (string) $str );
		$str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
		return trim( (string) $str );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen() {
		if ( ! WPAN_Test_State::$has_screen_function ) {
			return null;
		}

		return (object) [ 'id' => WPAN_Test_State::$screen ];
	}
}
