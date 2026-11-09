<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Phase24ReleaseCheckTest extends TestCase
{
    public function test_release_command_is_available_and_read_only(): void
    {
        $exit = Artisan::call('school:release-check', ['--production' => true]);
        $output = Artisan::output();

        // Test mode must not be misrepresented as a production deployment.
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('APP_ENV is production', $output);
        $this->assertStringContainsString('No configuration or data was changed', $output);
    }

    public function test_release_command_detects_unsafe_production_settings_without_revealing_secrets(): void
    {
        config()->set('app.debug', true);
        config()->set('app.url', 'http://example.test');
        config()->set('school_security.legacy_global_routes_enabled', true);
        config()->set('session.secure', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.username', 'root');

        $exit = Artisan::call('school:release-check', ['--production' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exit);
        foreach ([
            'APP_DEBUG is disabled',
            'APP_URL uses HTTPS',
            'Legacy global routes are disabled',
            'Secure session cookies are enabled',
            'Database does not use the root account',
        ] as $description) {
            $this->assertStringContainsString('FAIL  '.$description, $output);
        }
        $this->assertStringNotContainsString('http://example.test', $output);
        $this->assertStringNotContainsString('root@', $output);
    }
}
