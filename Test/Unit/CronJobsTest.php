<?php

declare( strict_types=1 );

namespace {
    if ( ! function_exists( 'sanitize_key' ) ) {
        function sanitize_key( $key ): string {
            return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) );
        }
    }

    if ( ! function_exists( 'wp_schedule_event' ) ) {
        function wp_schedule_event( $timestamp, $schedule, $hook, $args = array() ) {
            $GLOBALS['modpress_test_cron_jobs'][ (string) $hook ] = array(
                'timestamp' => $timestamp,
                'schedule'  => $schedule,
                'hook'      => $hook,
                'args'      => $args,
            );
            return true;
        }
    }

    if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
        function wp_clear_scheduled_hook( $hook, $args = array() ) {
            if ( isset( $GLOBALS['modpress_test_cron_jobs'][ (string) $hook ] ) ) {
                unset( $GLOBALS['modpress_test_cron_jobs'][ (string) $hook ] );
                return true;
            }

            return false;
        }
    }

    if ( ! function_exists( 'wp_next_scheduled' ) ) {
        function wp_next_scheduled( $hook, $args = array() ) {
            return isset( $GLOBALS['modpress_test_cron_jobs'][ (string) $hook ] ) ? $GLOBALS['modpress_test_cron_jobs'][ (string) $hook ]['timestamp'] : false;
        }
    }

    if ( ! function_exists( 'add_action' ) ) {
        function add_action( $hook, $callback = null, $priority = 10, $accepted_args = 1 ) {
            $GLOBALS['modpress_test_actions'][ (string) $hook ][] = array(
                'callback' => $callback,
                'priority' => $priority,
            );
            return true;
        }
    }

    if ( ! function_exists( 'has_action' ) ) {
        function has_action( $hook, $callback = false ) {
            if ( ! isset( $GLOBALS['modpress_test_actions'][ (string) $hook ] ) ) {
                return false;
            }

            foreach ( $GLOBALS['modpress_test_actions'][ (string) $hook ] as $action ) {
                if ( $action['callback'] === $callback ) {
                    return true;
                }
            }

            return false;
        }
    }
}

namespace ModPress\Tests\Unit {
    use ModPress\Includes\Core\CronJobs;
    use PHPUnit\Framework\TestCase;

    final class CronJobsTest extends TestCase {
        protected function setUp(): void {
            parent::setUp();
            $GLOBALS['modpress_test_cron_jobs'] = array();
            $GLOBALS['modpress_test_actions'] = array();
        }

        public function testDuplicateCronJobsAreNotScheduledTwice(): void {
            $callback = static function (): void {};

            $this->assertTrue( CronJobs::create( 'core', 'cleanup', 'hourly', array(), $callback ) );
            $this->assertFalse( CronJobs::create( 'core', 'cleanup', 'hourly', array(), $callback ) );
            $this->assertCount( 1, $GLOBALS['modpress_test_cron_jobs'] );
        }

        public function testDynamicCronJobCanBeCreatedFromUiPayload(): void {
            $callback = static function (): void {};

            $this->assertTrue( CronJobs::dynamically_create(
                array(
                    'scope'     => 'core',
                    'hook'      => 'cache_cleanup',
                    'schedule'  => 'daily',
                    'callback'  => $callback,
                    'args'      => array(),
                    'description' => 'Daily cleanup',
                )
            ) );

            $this->assertArrayHasKey( 'modpress_core_cache_cleanup', $GLOBALS['modpress_test_cron_jobs'] );
        }
    }
}
