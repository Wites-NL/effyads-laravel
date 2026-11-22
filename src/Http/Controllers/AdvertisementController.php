<?php

namespace EffyAds\Laravel\Http\Controllers;

use Composer\InstalledVersions;
use EffyAds\Laravel\EffyAds;
use EffyAds\Laravel\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class AdvertisementController extends Controller
{
    protected function rules(): array
    {
        return [
            'platform_id' => ['required', 'string'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'link' => ['required', 'url'],
            'media_url' => ['nullable', 'url'],
            'media_width' => ['nullable', 'integer', 'min:1'],
            'media_height' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'targeting' => ['nullable', 'array'],
            'status' => ['required', 'in:draft,scheduled,active,paused,expired'],
        ];
    }

    // POST /effyads/v1/advertisements — create, or update if platform_id already exists
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $site = Advertisement::siteColumnValue(EffyAds::site($request));

        $existing = Advertisement::forSite(EffyAds::site($request))->where('platform_id', $data['platform_id'])->first();

        $ad = Advertisement::updateOrCreate(
            ['site' => $site, 'platform_id' => $data['platform_id']],
            $data + ['last_synced_at' => now()]
        );

        return response()->json([
            'success' => true,
            'id' => $ad->id,
            'platform_id' => $ad->platform_id,
            'message' => $existing ? 'Advertisement updated successfully' : 'Advertisement created successfully',
        ], $existing ? 200 : 201);
    }

    // GET /effyads/v1/advertisements
    public function index(Request $request)
    {
        $ads = Advertisement::forSite(EffyAds::site($request))->get();

        return response()->json([
            'success' => true,
            'count' => $ads->count(),
            'advertisements' => $ads,
        ]);
    }

    // GET /effyads/v1/advertisements/{platform_id}
    public function show(Request $request, string $platformId)
    {
        $ad = $this->find($request, $platformId);

        if (! $ad) {
            return $this->notFound();
        }

        return response()->json($ad);
    }

    // PUT /effyads/v1/advertisements/{platform_id}
    public function update(Request $request, string $platformId)
    {
        $ad = $this->find($request, $platformId);

        if (! $ad) {
            return $this->notFound();
        }

        $data = $this->validated($request);
        $ad->update($data + ['last_synced_at' => now()]);

        return response()->json([
            'success' => true,
            'id' => $ad->id,
            'platform_id' => $ad->platform_id,
            'message' => 'Advertisement updated successfully',
        ]);
    }

    // DELETE /effyads/v1/advertisements/{platform_id}
    public function destroy(Request $request, string $platformId)
    {
        $ad = $this->find($request, $platformId);

        if (! $ad) {
            return $this->notFound();
        }

        $ad->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully',
        ]);
    }

    // GET /effyads/v1/sync/status
    public function syncStatus(Request $request)
    {
        return response()->json([
            'success' => true,
            'site' => EffyAds::site($request),
            'site_url' => $request->getSchemeAndHttpHost(),
            'total_ads' => Advertisement::forSite(EffyAds::site($request))->count(),
            'laravel_version' => app()->version(),
            'package_version' => static::packageVersion(),
            'last_check' => now()->toDateTimeString(),
        ]);
    }

    // POST /effyads/v1/sync/bulk — reconcile local ads against the platform's known ids
    public function bulkSync(Request $request)
    {
        $validated = $request->validate([
            'platform_ids' => ['required', 'array'],
            'platform_ids.*' => ['string'],
            'delete_missing' => ['nullable', 'boolean'],
        ]);

        $platformIds = $validated['platform_ids'];
        $deleteMissing = $validated['delete_missing'] ?? true;

        $site = EffyAds::site($request);

        $toDelete = Advertisement::forSite($site)->whereNotIn('platform_id', $platformIds)->get();

        $deleted = [];
        if ($deleteMissing) {
            foreach ($toDelete as $ad) {
                $deleted[] = ['platform_id' => $ad->platform_id, 'id' => $ad->id, 'title' => $ad->title];
                $ad->delete();
            }
        }

        return response()->json([
            'success' => true,
            'site' => $site,
            'platform_ads_count' => count($platformIds),
            'local_ads_count' => Advertisement::forSite($site)->count() + count($deleted),
            'deleted_count' => count($deleted),
            'deleted_ads' => $deleted,
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    protected static function packageVersion(): string
    {
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled('effyads/laravel')) {
            return InstalledVersions::getPrettyVersion('effyads/laravel') ?? 'unknown';
        }

        return 'unknown';
    }

    protected function find(Request $request, string $platformId): ?Advertisement
    {
        return Advertisement::forSite(EffyAds::site($request))->where('platform_id', $platformId)->first();
    }

    protected function validated(Request $request): array
    {
        return Validator::make($request->all(), $this->rules())->validate();
    }

    protected function notFound()
    {
        return response()->json([
            'success' => false,
            'message' => 'Advertisement not found',
        ], 404);
    }
}
