<?php
/**
 * Tests for the Notice class.
 *
 * @package WPAdminNotice
 */

use Nilambar\AdminNotice\Notice;
use PHPUnit\Framework\TestCase;

/**
 * Class NoticeTest.
 */
class NoticeTest extends TestCase {

	/**
	 * Saved copy of $_GET.
	 *
	 * @var array
	 */
	private $get_backup = [];

	/**
	 * Reset stub state before every test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		wpan_reset_state();

		$this->get_backup = $_GET;
		$_GET             = [];
	}

	/**
	 * Restore superglobals after every test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_GET = $this->get_backup;

		parent::tearDown();
	}

	// -----------------------------------------------------------------------
	// Helpers.
	// -----------------------------------------------------------------------

	/**
	 * Build a configured Notice without invoking the private constructor.
	 *
	 * The constructor also attaches hooks and processes actions, which the
	 * unit tests exercise separately.
	 *
	 * @param array $args Arguments.
	 * @return Notice
	 */
	private function make( array $args ) {
		$reflection = new ReflectionClass( Notice::class );
		$notice     = $reflection->newInstanceWithoutConstructor();

		$this->call( $notice, 'configure', $args );

		return $notice;
	}

	/**
	 * Invoke a non-public method.
	 *
	 * @param Notice $notice Notice instance.
	 * @param string $method Method name.
	 * @param mixed  ...$args Arguments.
	 * @return mixed
	 */
	private function call( $notice, $method, ...$args ) {
		$reflection = new ReflectionMethod( $notice, $method );
		$reflection->setAccessible( true );

		return $reflection->invoke( $notice, ...$args );
	}

	/**
	 * Read a private property.
	 *
	 * @param Notice $notice Notice instance.
	 * @param string $name   Property name.
	 * @return mixed
	 */
	private function get_property( $notice, $name ) {
		$reflection = new ReflectionProperty( $notice, $name );
		$reflection->setAccessible( true );

		return $reflection->getValue( $notice );
	}

	/**
	 * Write a private property.
	 *
	 * @param Notice $notice Notice instance.
	 * @param string $name   Property name.
	 * @param mixed  $value  Value.
	 * @return void
	 */
	private function set_property( $notice, $name, $value ) {
		$reflection = new ReflectionProperty( $notice, $name );
		$reflection->setAccessible( true );
		$reflection->setValue( $notice, $value );
	}

	/**
	 * Mark the notice as due by backdating its stored timestamp.
	 *
	 * @param Notice $notice Notice instance.
	 * @return void
	 */
	private function make_due( $notice ) {
		$key = $this->call( $notice, 'key', 'time' );

		WPAN_Test_State::$options[ $key ] = ( new DateTimeImmutable( WPAN_Test_State::$now ) )
			->modify( '-1 day' )
			->getTimestamp();
	}

	// -----------------------------------------------------------------------
	// Configuration.
	// -----------------------------------------------------------------------

	/**
	 * The name defaults to a title-cased version of the slug.
	 *
	 * @return void
	 */
	public function test_name_defaults_to_humanised_slug() {
		$notice = $this->make( [ 'slug' => 'my-cool-plugin' ] );

		$this->assertSame( 'My Cool Plugin', $this->get_property( $notice, 'name' ) );
	}

	/**
	 * Explicit arguments override the defaults.
	 *
	 * @return void
	 */
	public function test_explicit_arguments_override_defaults() {
		$notice = $this->make(
			[
				'slug'       => 'my-plugin',
				'name'       => 'Custom Name',
				'days'       => 30,
				'capability' => 'edit_posts',
				'screens'    => [ 'dashboard' ],
			]
		);

		$this->assertSame( 'Custom Name', $this->get_property( $notice, 'name' ) );
		$this->assertSame( 30, $this->get_property( $notice, 'days' ) );
		$this->assertSame( 'edit_posts', $this->get_property( $notice, 'capability' ) );
		$this->assertSame( [ 'dashboard' ], $this->get_property( $notice, 'screens' ) );
	}

	/**
	 * An unknown type falls back to "plugin".
	 *
	 * @return void
	 */
	public function test_unknown_type_falls_back_to_plugin() {
		$notice = $this->make(
			[
				'slug' => 'my-plugin',
				'type' => 'nonsense',
			]
		);

		$this->assertSame( 'plugin', $this->get_property( $notice, 'type' ) );
	}

	/**
	 * Configuration is skipped when the slug is missing.
	 *
	 * @return void
	 */
	public function test_configure_bails_without_slug() {
		$notice = $this->make( [] );

		$this->assertSame( '', $this->get_property( $notice, 'slug' ) );
		$this->assertSame( '', $this->get_property( $notice, 'message' ) );
	}

	/**
	 * Option and meta keys are namespaced by slug.
	 *
	 * @return void
	 */
	public function test_keys_are_prefixed_with_underscored_slug() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$this->assertSame( 'my_plugin_wpan_time', $this->call( $notice, 'key', 'time' ) );
		$this->assertSame( 'my_plugin_wpan_dismissed', $this->call( $notice, 'key', 'dismissed' ) );
	}

	// -----------------------------------------------------------------------
	// Messages, URLs and classes.
	// -----------------------------------------------------------------------

	/**
	 * The default message mentions the name and the day count.
	 *
	 * @return void
	 */
	public function test_default_message_includes_name_and_days() {
		$notice = $this->make(
			[
				'slug' => 'my-plugin',
				'name' => 'My Plugin',
				'days' => 14,
			]
		);

		$message = $this->get_property( $notice, 'message' );

		$this->assertStringContainsString( '<strong>My Plugin</strong>', $message );
		$this->assertStringContainsString( '14 days', $message );
	}

	/**
	 * A supplied message replaces the default.
	 *
	 * @return void
	 */
	public function test_custom_message_is_used() {
		$notice = $this->make(
			[
				'slug'    => 'my-plugin',
				'message' => 'Please review us.',
			]
		);

		$this->assertSame( 'Please review us.', $this->get_property( $notice, 'message' ) );
	}

	/**
	 * Plugins and themes get different review URLs.
	 *
	 * @return void
	 */
	public function test_review_url_matches_type() {
		$plugin = $this->make(
			[
				'slug' => 'my-plugin',
				'type' => 'plugin',
			]
		);
		$theme  = $this->make(
			[
				'slug' => 'my-theme',
				'type' => 'theme',
			]
		);

		$this->assertSame(
			'https://wordpress.org/support/plugin/my-plugin/reviews/#new-post',
			$plugin->get_review_url()
		);
		$this->assertSame(
			'https://wordpress.org/support/theme/my-theme/reviews/#new-post',
			$theme->get_review_url()
		);
	}

	/**
	 * Required classes are always present and duplicates are removed.
	 *
	 * @return void
	 */
	public function test_classes_include_defaults_without_duplicates() {
		$notice = $this->make(
			[
				'slug'    => 'my-plugin',
				'classes' => [ 'notice', 'is-dismissible' ],
			]
		);

		$classes = explode( ' ', $this->call( $notice, 'get_classes' ) );

		$this->assertContains( 'notice', $classes );
		$this->assertContains( 'notice-info', $classes );
		$this->assertContains( 'is-dismissible', $classes );
		$this->assertSame( array_unique( $classes ), $classes );
	}

	// -----------------------------------------------------------------------
	// Visibility checks.
	// -----------------------------------------------------------------------

	/**
	 * The first check seeds the timestamp and hides the notice.
	 *
	 * @return void
	 */
	public function test_first_time_check_seeds_timestamp_and_returns_false() {
		$notice = $this->make(
			[
				'slug' => 'my-plugin',
				'days' => 7,
			]
		);

		$key = $this->call( $notice, 'key', 'time' );

		$this->assertFalse( $this->call( $notice, 'is_time_to_show' ) );
		$this->assertArrayHasKey( $key, WPAN_Test_State::$options );

		$expected = ( new DateTimeImmutable( WPAN_Test_State::$now ) )->modify( '+7 day' )->getTimestamp();

		$this->assertSame( $expected, WPAN_Test_State::$options[ $key ] );
	}

	/**
	 * The notice becomes due once the stored timestamp passes.
	 *
	 * @return void
	 */
	public function test_notice_is_due_after_the_delay() {
		$notice = $this->make(
			[
				'slug' => 'my-plugin',
				'days' => 7,
			]
		);

		$this->call( $notice, 'is_time_to_show' );

		WPAN_Test_State::$now = '2026-01-06 00:00:00';
		$this->assertFalse( $this->call( $notice, 'is_time_to_show' ) );

		WPAN_Test_State::$now = '2026-01-08 00:00:00';
		$this->assertTrue( $this->call( $notice, 'is_time_to_show' ) );
	}

	/**
	 * Dismissal is read from user meta.
	 *
	 * @return void
	 */
	public function test_dismissed_state_reads_user_meta() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$this->assertFalse( $this->call( $notice, 'is_dismissed' ) );

		WPAN_Test_State::$user_meta[ $this->call( $notice, 'key', 'dismissed' ) ] = true;

		$this->assertTrue( $this->call( $notice, 'is_dismissed' ) );
	}

	/**
	 * Capability checks defer to current_user_can().
	 *
	 * @return void
	 */
	public function test_capability_check_defers_to_wordpress() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		WPAN_Test_State::$can = false;
		$this->assertFalse( $this->call( $notice, 'is_capable' ) );

		WPAN_Test_State::$can = true;
		$this->assertTrue( $this->call( $notice, 'is_capable' ) );
	}

	/**
	 * An empty screen list allows every screen.
	 *
	 * @return void
	 */
	public function test_empty_screen_list_allows_all_screens() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		WPAN_Test_State::$screen = 'edit-post';

		$this->assertTrue( $this->call( $notice, 'in_screen' ) );
	}

	/**
	 * A screen list restricts the notice to those screens.
	 *
	 * @return void
	 */
	public function test_screen_list_restricts_display() {
		$notice = $this->make(
			[
				'slug'    => 'my-plugin',
				'screens' => [ 'dashboard', 'plugins' ],
			]
		);

		WPAN_Test_State::$screen = 'plugins';
		$this->assertTrue( $this->call( $notice, 'in_screen' ) );

		WPAN_Test_State::$screen = 'edit-post';
		$this->assertFalse( $this->call( $notice, 'in_screen' ) );
	}

	/**
	 * All conditions must pass for the notice to show.
	 *
	 * @return void
	 */
	public function test_can_show_requires_every_condition() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$this->make_due( $notice );
		$this->assertTrue( $this->call( $notice, 'can_show' ) );

		WPAN_Test_State::$user_meta[ $this->call( $notice, 'key', 'dismissed' ) ] = true;
		$this->assertFalse( $this->call( $notice, 'can_show' ) );

		WPAN_Test_State::$user_meta = [];
		WPAN_Test_State::$can       = false;
		$this->assertFalse( $this->call( $notice, 'can_show' ) );
	}

	// -----------------------------------------------------------------------
	// Rendering.
	// -----------------------------------------------------------------------

	/**
	 * The rendered markup carries the slug-based ID and the classes.
	 *
	 * @return void
	 */
	public function test_render_outputs_notice_markup() {
		$notice = $this->make(
			[
				'slug'    => 'my-plugin',
				'message' => 'Review please.',
				'classes' => [ 'is-dismissible' ],
			]
		);

		$this->make_due( $notice );

		ob_start();
		$notice->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'id="wp-admin-notice-my-plugin"', $output );
		$this->assertStringContainsString( 'notice-info', $output );
		$this->assertStringContainsString( 'is-dismissible', $output );
		$this->assertStringContainsString( 'Review please.', $output );
	}

	/**
	 * Nothing renders while the notice is not yet due.
	 *
	 * @return void
	 */
	public function test_render_outputs_nothing_before_the_notice_is_due() {
		$notice = $this->make(
			[
				'slug'    => 'my-plugin',
				'message' => 'Review please.',
			]
		);

		ob_start();
		$notice->render();
		$output = ob_get_clean();

		$this->assertSame( '', trim( $output ) );
	}

	/**
	 * An empty message suppresses the notice even when it is otherwise due.
	 *
	 * Regression test: the condition previously used && and rendered an empty
	 * notice box in this situation.
	 *
	 * @return void
	 */
	public function test_render_outputs_nothing_when_message_is_empty() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$this->make_due( $notice );
		$this->set_property( $notice, 'message', '' );

		ob_start();
		$notice->render();
		$output = ob_get_clean();

		$this->assertSame( '', trim( $output ) );
	}

	/**
	 * Action links are rendered and the surrounding hooks fire.
	 *
	 * @return void
	 */
	public function test_render_links_outputs_actions_and_fires_hooks() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		ob_start();
		$notice->render_links();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Ok, you deserve it', $output );
		$this->assertStringContainsString( 'Nope, maybe later', $output );
		$this->assertStringContainsString( 'I already did', $output );
		$this->assertStringContainsString( 'wordpress.org/support/plugin/my-plugin', $output );

		$this->assertSame(
			[
				'my_plugin_before_admin_notice_link_items',
				'my_plugin_after_admin_notice_link_items',
			],
			WPAN_Test_State::$did_actions
		);
	}

	/**
	 * Blank action labels remove the corresponding links.
	 *
	 * @return void
	 */
	public function test_blank_action_labels_hide_links() {
		$notice = $this->make(
			[
				'slug'          => 'my-plugin',
				'action_labels' => [
					'review'  => '',
					'later'   => '',
					'dismiss' => 'Dismiss',
				],
			]
		);

		ob_start();
		$notice->render_links();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'reviews/#new-post', $output );
		$this->assertStringContainsString( 'Dismiss', $output );
	}

	// -----------------------------------------------------------------------
	// Actions.
	// -----------------------------------------------------------------------

	/**
	 * A valid dismiss request records the dismissal.
	 *
	 * @return void
	 */
	public function test_dismiss_action_sets_user_meta() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$action = $this->call( $notice, 'key', 'action' );

		$_GET = [
			$action     => 'dismiss',
			'_wpnonce'  => wp_create_nonce( $action . '-dismiss' ),
		];

		$this->call( $notice, 'process_actions' );

		$this->assertTrue( $this->call( $notice, 'is_dismissed' ) );
	}

	/**
	 * A later request pushes the timestamp forward.
	 *
	 * @return void
	 */
	public function test_later_action_postpones_the_notice() {
		$notice = $this->make(
			[
				'slug' => 'my-plugin',
				'days' => 7,
			]
		);

		$action = $this->call( $notice, 'key', 'action' );
		$key    = $this->call( $notice, 'key', 'time' );

		$this->make_due( $notice );

		$_GET = [
			$action    => 'later',
			'_wpnonce' => wp_create_nonce( $action . '-later' ),
		];

		$this->call( $notice, 'process_actions' );

		$expected = ( new DateTimeImmutable( WPAN_Test_State::$now ) )->modify( '+7 day' )->getTimestamp();

		$this->assertSame( $expected, WPAN_Test_State::$options[ $key ] );
		$this->assertFalse( $this->call( $notice, 'is_time_to_show' ) );
	}

	/**
	 * A missing or wrong nonce is ignored.
	 *
	 * @return void
	 */
	public function test_actions_require_a_valid_nonce() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$action = $this->call( $notice, 'key', 'action' );

		$_GET = [
			$action    => 'dismiss',
			'_wpnonce' => 'not-a-real-nonce',
		];

		$this->call( $notice, 'process_actions' );

		$this->assertFalse( $this->call( $notice, 'is_dismissed' ) );
	}

	/**
	 * Actions are skipped for users without the capability.
	 *
	 * @return void
	 */
	public function test_actions_require_the_capability() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$action = $this->call( $notice, 'key', 'action' );

		WPAN_Test_State::$can = false;

		$_GET = [
			$action    => 'dismiss',
			'_wpnonce' => wp_create_nonce( $action . '-dismiss' ),
		];

		$this->call( $notice, 'process_actions' );

		WPAN_Test_State::$can = true;

		$this->assertFalse( $this->call( $notice, 'is_dismissed' ) );
	}

	/**
	 * An unknown action value is never acted on.
	 *
	 * @return void
	 */
	public function test_unknown_action_is_ignored() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$action = $this->call( $notice, 'key', 'action' );

		$_GET = [
			$action    => 'explode',
			'_wpnonce' => wp_create_nonce( $action . '-explode' ),
		];

		$this->call( $notice, 'process_actions' );

		$this->assertFalse( $this->call( $notice, 'is_dismissed' ) );
		$this->assertSame( [], WPAN_Test_State::$options );
	}

	// -----------------------------------------------------------------------
	// Hooks and instantiation.
	// -----------------------------------------------------------------------

	/**
	 * The admin_notices hook is registered.
	 *
	 * @return void
	 */
	public function test_hooks_registers_admin_notices() {
		$notice = $this->make( [ 'slug' => 'my-plugin' ] );

		$notice->hooks();

		$this->assertContains( 'admin_notices', WPAN_Test_State::$actions );
	}

	/**
	 * A missing slug yields no instance.
	 *
	 * @return void
	 */
	public function test_init_returns_null_without_a_slug() {
		$this->assertNull( Notice::init( [] ) );
		$this->assertNull( Notice::init( [ 'slug' => '' ] ) );
	}

	/**
	 * Repeated calls for the same slug reuse one instance.
	 *
	 * @return void
	 */
	public function test_init_reuses_the_instance_per_slug() {
		$first  = Notice::init( [ 'slug' => 'init-shared-slug' ] );
		$second = Notice::init( [ 'slug' => 'init-shared-slug' ] );
		$other  = Notice::init( [ 'slug' => 'init-other-slug' ] );

		$this->assertInstanceOf( Notice::class, $first );
		$this->assertSame( $first, $second );
		$this->assertNotSame( $first, $other );
	}
}
