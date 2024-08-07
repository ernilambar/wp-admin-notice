<?php
/**
 * WordPress Helper for showing admin notice
 *
 * @author Nilambar Sharma <nilambar@outlook.com>
 * @copyright 2022 Nilambar Sharma
 * @license MIT
 * @package WPAdminNotice
 */

namespace Nilambar\AdminNotice;

defined( 'WPINC' ) || die;

/**
 * Class Notice.
 */
class Notice {

	/**
	 * Prefix.
	 *
	 * @var string $prefix
	 *
	 * @since 1.0.0
	 */
	private $prefix = '';

	/**
	 * Name.
	 *
	 * @var string $name
	 *
	 * @since 1.0.0
	 */
	private $name = '';

	/**
	 * Type.
	 *
	 * @var string $type
	 *
	 * @since 1.0.0
	 */
	private $type = '';

	/**
	 * Slug.
	 *
	 * @var string $slug
	 *
	 * @since 1.0.0
	 */
	private $slug = '';

	/**
	 * Number of days to show the notice after.
	 *
	 * @var int $days
	 *
	 * @since 1.0.0
	 */
	private $days = 7;

	/**
	 * WP admin page screens to show notice.
	 *
	 * @var array $screens
	 *
	 * @since 1.0.0
	 */
	private $screens = [];

	/**
	 * Notice classes.
	 *
	 * @var array $classes
	 *
	 * @since 1.0.0
	 */
	private $classes = [ 'notice', 'notice-info' ];

	/**
	 * Actions link texts.
	 *
	 * @var array $action_labels
	 *
	 * @since 1.0.0
	 */
	private $action_labels = [];

	/**
	 * Message.
	 *
	 * @var string $message
	 *
	 * @since 1.0.0
	 */
	private $message = '';

	/**
	 * Minimum capability for the user to see and dismiss notice.
	 *
	 * @var string $capability
	 *
	 * @since 1.0.0
	 */
	private $capability = 'manage_options';

	/**
	 * Constructor.
	 *
	 * @param array $args Arguments.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function __construct( $args ) {
		if ( is_admin() ) {
			$this->configure( $args );
			$this->hooks();
			$this->process_actions();
		}
	}

	/**
	 * Create and get new Notice instance.
	 *
	 * @param array $args Arguments array.
	 *
	 * @since 1.0.0
	 *
	 * @return Notice
	 */
	public static function init( $args ) {
		static $notices = [];

		$slug = ( isset( $args['slug'] ) && ! empty( $args['slug'] ) ) ? $args['slug'] : '';

		if ( '' === $slug ) {
			return;
		}

		if ( ! isset( $notices[ $slug ] ) || ! $notices[ $slug ] instanceof Notice ) {
			$notices[ $slug ] = new self( $args );
		}

		return $notices[ $slug ];
	}

	/**
	 * Attach hooks.
	 *
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'admin_notices', [ $this, 'hook_notice' ] );
	}

	/**
	 * Render notice.
	 *
	 * @since 1.0.0
	 */
	public function hook_notice() {
		$this->render();
	}

	/**
	 * Return review URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string Review URL.
	 */
	public function get_review_url() {
		$url = '';

		switch ( $this->type ) {
			case 'plugin':
				$url = 'https://wordpress.org/support/plugin/' . $this->slug . '/reviews/#new-post';
				break;

			case 'theme':
				$url = 'https://wordpress.org/support/theme/' . $this->slug . '/reviews/#new-post';
				break;

			default:
				break;
		}

		return $url;
	}

	/**
	 * Render links.
	 *
	 * @since 1.0.0
	 */
	public function render_links() {
		echo '<div class="wp-admin-notice-links" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:10px;">';

		do_action( $this->prefix . '_before_admin_notice_link_items' );

		// Review link.
		if ( ! empty( $this->action_labels['review'] ) ) {
			echo '<span><a href="' . esc_url( $this->get_review_url() ) . '" target="_blank">' . esc_html( $this->action_labels['review'] ) . '</a></span>';
		}

		// Later link.
		if ( ! empty( $this->action_labels['later'] ) ) {
			$later_url = add_query_arg( $this->key( 'action' ), 'later' );
			echo '<span><a href="' . esc_url( wp_nonce_url( $later_url, $this->key( 'action' ) . '-later' ) ) . '">' . esc_html( $this->action_labels['later'] ) . '</a></span>';
		}

		// Dismiss link.
		if ( ! empty( $this->action_labels['dismiss'] ) ) {
			$dismiss_url = add_query_arg( $this->key( 'action' ), 'dismiss' );
			echo '<span><a href="' . esc_url( wp_nonce_url( $dismiss_url, $this->key( 'action' ) . '-dismiss' ) ) . '">' . esc_html( $this->action_labels['dismiss'] ) . '</a></span>';
		}

		do_action( $this->prefix . '_after_admin_notice_link_items' );

		echo '</div>';
	}

	/**
	 * Render the notice.
	 *
	 * @since 1.0.0
	 */
	public function render() {
		// Bail if not valid.
		if ( ! $this->can_show() && ! empty( $this->message ) ) {
			return;
		}
		?>

		<div id="wp-admin-notice-<?php echo esc_attr( $this->slug ); ?>" class="<?php echo esc_attr( $this->get_classes() ); ?>">
			<p><?php echo $this->message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

			<?php $this->render_links(); ?>
		</div>
		<?php
	}

	/**
	 * Check if it's time to show the notice.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if it's time to show notice.
	 */
	protected function is_time_to_show() {
		// Get the notice time.
		$time = get_site_option( $this->key( 'time' ) );

		$current_time      = current_datetime();
		$current_timestamp = $current_time->getTimestamp();

		// If not set, set now and bail.
		if ( empty( $time ) ) {
			$interval = \DateInterval::createFromDateString( $this->days . ' day' );

			$new_target_date = $current_time->add( $interval );

			$time = $new_target_date->getTimestamp();

			// Set to future.
			update_site_option( $this->key( 'time' ), $time );

			return false;
		}

		// Check if time passed or reached.
		return (int) $time <= $current_timestamp;
	}

	/**
	 * Check if the notice is already dismissed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if notice is dismissed.
	 */
	protected function is_dismissed() {
		// Get current user.
		$current_user = wp_get_current_user();

		// Check if current item is dismissed.
		return (bool) get_user_meta( $current_user->ID, $this->key( 'dismissed' ), true );
	}

	/**
	 * Check if current user has the capability.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if user has required capability.
	 */
	protected function is_capable() {
		return current_user_can( $this->capability );
	}

	/**
	 * Check if the current screen is allowed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the current screen is in the list of allowed screens.
	 */
	protected function in_screen() {
		// If not screen ID is set, show everywhere.
		if ( empty( $this->screens ) ) {
			return true;
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			return true;
		}

		// Get current screen.
		$screen = get_current_screen();

		// Check if current screen id is allowed.
		return ! empty( $screen->id ) && in_array( $screen->id, $this->screens, true );
	}

	/**
	 * Return class names for notice div.
	 *
	 * @since 1.0.0
	 *
	 * @return string Classes.
	 */
	protected function get_classes() {
		// Required classes.
		$classes = [ 'notice', 'notice-info' ];

		// Add extra classes.
		if ( ! empty( $this->classes ) && is_array( $this->classes ) ) {
			$classes = array_merge( $classes, $this->classes );
			$classes = array_unique( $classes );
		}

		return implode( ' ', $classes );
	}

	/**
	 * Get the default notice message.
	 *
	 * @since 1.0.0
	 *
	 * @return string Message.
	 */
	protected function get_message() {
		$message = sprintf(
			/* translators: 1: Name, 2: Days. */
			esc_html__( 'Hello! Seems like you have been using %1$s for more than %2$d days - that\'s awesome! Could you please do us a BIG favor and give it a 5-star rating on WordPress? This would boost our motivation and help us spread the word.', 'wp-admin-notice' ),
			'<strong>' . esc_html( $this->name ) . '</strong>',
			(int) $this->days
		);

		return $message;
	}

	/**
	 * Check if we can show the notice.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if notice should be shown.
	 */
	protected function can_show() {
		return (
			$this->in_screen() &&
			$this->is_capable() &&
			$this->is_time_to_show() &&
			! $this->is_dismissed()
		);
	}

	/**
	 * Process the notice actions.
	 *
	 * If current user is capable process actions.
	 * > Later: Extend the time to show the notice.
	 * > Dismiss: Hide the notice to current user.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function process_actions() {
		// Only if required.
		if ( ! $this->in_screen() || ! $this->is_capable() ) {
			return;
		}

		$action_list = [ 'later', 'dismiss' ];

		$action = '';

		$current_action = isset( $_GET[ $this->key( 'action' ) ] ) ? sanitize_text_field( wp_unslash( $_GET[ $this->key( 'action' ) ] ) ) : '';

		if ( in_array( $current_action, $action_list, true ) ) {
			$action = $current_action;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( wp_verify_nonce( $nonce, $this->key( 'action' ) . '-' . $action ) ) {
			switch ( $action ) {
				case 'later':
					// Show after 7 days.
					$current_time      = current_datetime();
					$current_timestamp = $current_time->getTimestamp();

					$interval = \DateInterval::createFromDateString( $this->days . ' day' );

					$new_target_date = $current_time->add( $interval );

					$time = $new_target_date->getTimestamp();

					update_site_option( $this->key( 'time' ), $time );
					break;

				case 'dismiss':
					// Do not show again to this user.
					update_user_meta( get_current_user_id(), $this->key( 'dismissed' ), true );
					break;
			}
		}
	}

	/**
	 * Configure notice.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	private function configure( $args ) {
		$slug = ( isset( $args['slug'] ) && ! empty( $args['slug'] ) ) ? $args['slug'] : '';

		if ( empty( $slug ) ) {
			return;
		}

		// Default arguments.
		$args = wp_parse_args(
			$args,
			[
				'days'          => 7,
				'name'          => ucwords( str_replace( '-', ' ', $slug ) ),
				'capability'    => 'manage_options',
				'type'          => 'plugin',
				'screens'       => [],
				'classes'       => [],
				'action_labels' => [],
			]
		);

		// Action button/link labels.
		$this->action_labels = wp_parse_args(
			(array) $args['action_labels'],
			[
				'review'  => esc_html__( 'Ok, you deserve it', 'wp-admin-notice' ),
				'later'   => esc_html__( 'Nope, maybe later', 'wp-admin-notice' ),
				'dismiss' => esc_html__( 'I already did', 'wp-admin-notice' ),
			]
		);

		if ( ! in_array( $args['type'], [ 'plugin', 'theme' ], true ) ) {
			$args['type'] = 'plugin';
		}

		$this->slug       = (string) $slug;
		$this->name       = (string) $args['name'];
		$this->type       = (string) $args['type'];
		$this->capability = (string) $args['capability'];
		$this->days       = (int) $args['days'];
		$this->screens    = (array) $args['screens'];
		$this->classes    = (array) $args['classes'];
		$this->prefix     = str_replace( '-', '_', $this->slug );
		$this->message    = empty( $args['message'] ) ? $this->get_message() : (string) $args['message'];
	}

	/**
	 * Create prefixed key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key.
	 * @return string Prefixed key.
	 */
	private function key( $key ) {
		return $this->prefix . '_wpan_' . $key;
	}
}
