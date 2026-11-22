<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\File;

class InstallCommandTest extends TestCase
{
    private string $envPath;

    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envPath = $this->app->environmentFilePath();
        $this->backup = File::exists($this->envPath) ? File::get($this->envPath) : null;
        File::delete(config_path('effyads.php'));
    }

    protected function tearDown(): void
    {
        File::delete(config_path('effyads.php'));
        $this->backup === null ? File::delete($this->envPath) : File::put($this->envPath, $this->backup);

        parent::tearDown();
    }

    public function test_publishes_config_and_prints_env_lines_for_a_new_key(): void
    {
        config(['effyads.api_key' => null]);

        $this->artisan('effyads:install')
            ->expectsOutputToContain('Published config/effyads.php.')
            ->expectsOutputToContain('EFFYADS_PLATFORM_URL=https://platform.test')
            ->expectsOutputToContain('EFFYADS_QUEUE_CONNECTION=sync')
            ->expectsOutputToContain('EFFYADS_API_KEY=')
            ->expectsConfirmation('Run php artisan migrate now?', 'no')
            ->assertSuccessful();

        $this->assertFileExists(config_path('effyads.php'));
    }

    public function test_existing_key_is_masked_unless_show_key_is_given(): void
    {
        $this->artisan('effyads:install')
            ->expectsOutputToContain('test-a…')
            ->expectsOutputToContain('--show-key')
            ->doesntExpectOutputToContain(self::API_KEY)
            ->expectsConfirmation('Run php artisan migrate now?', 'no')
            ->assertSuccessful();

        $this->artisan('effyads:install', ['--show-key' => true])
            ->expectsOutputToContain(self::API_KEY)
            ->expectsConfirmation('Run php artisan migrate now?', 'no')
            ->assertSuccessful();
    }

    public function test_write_env_adds_missing_lines_without_overwriting(): void
    {
        File::put($this->envPath, "APP_NAME=Test\nEFFYADS_PLATFORM_URL=https://custom.test\n");
        config(['effyads.api_key' => null]);

        $this->artisan('effyads:install', ['--write-env' => true])
            ->expectsConfirmation('Add EFFYADS_API_KEY, EFFYADS_QUEUE_CONNECTION to .env?', 'yes')
            ->expectsConfirmation('Run php artisan migrate now?', 'no')
            ->assertSuccessful();

        $env = File::get($this->envPath);
        $this->assertStringContainsString('EFFYADS_PLATFORM_URL=https://custom.test', $env);
        $this->assertSame(1, substr_count($env, 'EFFYADS_PLATFORM_URL='));
        $this->assertMatchesRegularExpression('/^EFFYADS_API_KEY=\w{64}$/m', $env);
        $this->assertStringContainsString('EFFYADS_QUEUE_CONNECTION=sync', $env);
    }

    public function test_can_run_migrations(): void
    {
        $this->artisan('effyads:install')
            ->expectsConfirmation('Run php artisan migrate now?', 'yes')
            ->assertSuccessful();
    }
}
