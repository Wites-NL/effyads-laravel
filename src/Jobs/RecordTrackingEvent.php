<?php

namespace EffyAds\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RecordTrackingEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(
        public string $platformId,
        public string $eventType,
        public string $ipAddress,
        public string $userAgent,
        public ?string $referrer,
        public string $siteUrl,
    ) {
    }

    /**
     * Seconds to wait before each retry when running on a real queue.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    /**
     * Send the event to the platform on the connection configured in
     * effyads.queue_connection. With "sync" (the default) it runs after the
     * response has been sent, so the visitor never waits on the platform.
     */
    public static function dispatchForTracking(mixed ...$args): void
    {
        $connection = config('effyads.queue_connection') ?: 'sync';

        $pending = static::dispatch(...$args);

        if ($connection === 'sync') {
            $pending->afterResponse();
        } else {
            $pending->onConnection($connection);
        }
    }

    public function handle(): void
    {
        $platformUrl = config('effyads.platform_url');

        if (empty($platformUrl)) {
            Log::warning('[EffyAds] platform_url not configured, skipping tracking event');

            return;
        }

        try {
            $response = Http::timeout(5)->acceptJson()->post(rtrim($platformUrl, '/').'/api/tracking', [
                'platform_id' => $this->platformId,
                'event_type' => $this->eventType,
                'ip_address' => $this->ipAddress,
                'user_agent' => $this->userAgent,
                'referrer' => $this->referrer,
                'timestamp' => now()->toDateTimeString(),
                'site_url' => $this->siteUrl,
            ]);
        } catch (ConnectionException $e) {
            $this->failOrLog('platform unreachable: '.$e->getMessage());

            return;
        }

        if ($response->failed()) {
            $this->failOrLog('tracking event failed with status '.$response->status());
        }
    }

    /**
     * On a real queue, throw so the worker retries with backoff. When run
     * inline (sync / after response) just log: there is nobody to retry.
     */
    private function failOrLog(string $message): void
    {
        if ($this->job && $this->job->getConnectionName() !== 'sync') {
            throw new RuntimeException('[EffyAds] '.$message);
        }

        Log::warning('[EffyAds] '.$message, ['platform_id' => $this->platformId, 'event' => $this->eventType]);
    }
}
