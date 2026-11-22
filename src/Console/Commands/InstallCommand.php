<?php

namespace EffyAds\Laravel\Console\Commands;

use EffyAds\Laravel\EffyAds;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    public const DEFAULT_PLATFORM_URL = 'https://app.effyads.com';

    protected $signature = 'effyads:install
        {--write-env : Add the EffyAds lines to .env (existing values are never overwritten)}
        {--show-key : Show the full API key when one is already configured}
        {--site= : Multi-site: only generate and print an API key for this site, for the app to store}';

    protected $description = 'Publish the EffyAds config, generate an API key and print the .env lines';

    public function handle(): int
    {
        if (($site = $this->option('site')) !== null) {
            return $this->generateSiteKey((string) $site);
        }

        $this->publishConfig();

        $existingKey = config('effyads.api_key');
        $key = $existingKey ?: EffyAds::generateApiKey();

        $lines = [
            'EFFYADS_API_KEY' => $key,
            'EFFYADS_PLATFORM_URL' => config('effyads.platform_url') ?: self::DEFAULT_PLATFORM_URL,
            'EFFYADS_QUEUE_CONNECTION' => 'sync',
        ];

        $this->newLine();

        if ($existingKey) {
            $this->info('An API key is already configured: '.$this->displayKey($existingKey));
            if (! $this->option('show-key')) {
                $this->line('  Run <comment>php artisan effyads:install --show-key</comment> to see the full key.');
            }
        } else {
            $this->info('Generated a new API key.');
        }

        $this->newLine();
        $this->line('Add these lines to your <comment>.env</comment>:');
        $this->newLine();
        foreach ($lines as $name => $value) {
            $shown = $name === 'EFFYADS_API_KEY' && $existingKey ? $this->displayKey($value) : $value;
            $this->line("  {$name}={$shown}");
        }
        $this->newLine();
        $this->line('  <comment>EFFYADS_QUEUE_CONNECTION</comment>: "sync" sends views and clicks to EffyAds right after');
        $this->line('  the page has been delivered, without a queue worker. Only use a real queue');
        $this->line('  connection (e.g. "redis") if a worker is running for it.');
        $this->newLine();

        if ($this->option('write-env')) {
            $this->writeEnv($lines);
        }

        if ($this->confirm('Run php artisan migrate now?', true)) {
            $this->call('migrate');
        } else {
            $this->line('Remember to run <comment>php artisan migrate</comment>.');
        }

        $this->newLine();
        $this->info('Next: define zones in config/effyads.php, place <x-effyads::ad zone="..." /> in a view,');
        $this->info('then connect this site in the EffyAds dashboard with its public URL and the API key.');

        return self::SUCCESS;
    }

    protected function generateSiteKey(string $site): int
    {
        if ($site === '') {
            $this->error('--site needs a value, e.g. --site=groningen');

            return self::FAILURE;
        }

        $this->line("New API key for site <comment>{$site}</comment>:");
        $this->newLine();
        $this->line('  '.EffyAds::generateApiKey());
        $this->newLine();
        $this->line('Store it where your EffyAds::resolveApiKeyUsing() resolver reads it for this site,');
        $this->line('then paste it into the EffyAds dashboard when you connect this site.');

        return self::SUCCESS;
    }

    protected function publishConfig(): void
    {
        if (file_exists(config_path('effyads.php'))) {
            $this->line('config/effyads.php already exists, leaving it untouched.');

            return;
        }

        $this->callSilently('vendor:publish', ['--tag' => 'effyads-config']);
        $this->info('Published config/effyads.php.');
    }

    protected function displayKey(string $key): string
    {
        return $this->option('show-key') ? $key : substr($key, 0, 6).'…';
    }

    /**
     * @param  array<string, string>  $lines
     */
    protected function writeEnv(array $lines): void
    {
        $path = $this->laravel->environmentFilePath();

        if (! file_exists($path)) {
            $this->warn("No .env file found at {$path}, skipping --write-env.");

            return;
        }

        $contents = file_get_contents($path);

        $missing = array_filter(
            $lines,
            fn (string $name) => ! preg_match('/^\s*'.preg_quote($name, '/').'\s*=/m', $contents),
            ARRAY_FILTER_USE_KEY,
        );

        if ($missing === []) {
            $this->line('.env already contains all EffyAds settings, nothing written.');

            return;
        }

        if (! $this->confirm('Add '.implode(', ', array_keys($missing)).' to .env?', true)) {
            return;
        }

        $block = collect($missing)->map(fn ($value, $name) => "{$name}={$value}")->implode(PHP_EOL);
        $prefix = $contents === '' || str_ends_with($contents, "\n") ? '' : PHP_EOL;

        file_put_contents($path, $contents.$prefix.PHP_EOL.$block.PHP_EOL);

        $this->info('Updated .env.');
    }
}
