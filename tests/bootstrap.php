<?php

define( 'TESTS_PROJECT_DIR', dirname( __DIR__, 1 ) );

if ( file_exists( TESTS_PROJECT_DIR . '/build-phpunit/vendor/autoload.php' ) ) {
	require_once TESTS_PROJECT_DIR . '/build-phpunit/vendor/autoload.php';
}

if ( file_exists( TESTS_PROJECT_DIR . '/vendor/autoload.php' ) ) {
	require_once TESTS_PROJECT_DIR . '/vendor/autoload.php';
}
