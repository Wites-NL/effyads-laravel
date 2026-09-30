<?php

namespace EffyAds\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'effyads:install';

    protected $description = 'Generate an EffyAds API key and print setup instructions';

    public function handle(): int
    {
        if (config('effyads.api_key')) {
            $this->warn('EFFYADS_API_KEY is already set in your environment.');
            $this->line('Current key: '.config('effyads.api_key'));

            return self::SUCCESS;
        }

        $key = Str::random(64);

        $this->info('Add the following to your .env file:');
        $this->newLine();
        $this->line("EFFYADS_API_KEY={$key}");
        $this->line('EFFYADS_PLATFORM_URL=https://effyads.com');
        $this->newLine();
        $this->info('Then run: php artisan migrate');
        $this->info('Then paste this API key and your site URL into the EffyAds dashboard to connect this site.');

        return self::SUCCESS;
    }
}
