<?php

namespace EffyAds\Laravel\Http\Controllers;

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

        $existing = Advertisement::where('platform_id', $data['platform_id'])->first();

        $ad = Advertisement::updateOrCreate(
            ['platform_id' => $data['platform_id']],
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
    public function index()
    {
        $ads = Advertisement::all();

        return response()->json([
            'success' => true,
            'count' => $ads->count(),
            'advertisements' => $ads,
        ]);
    }

    // GET /effyads/v1/advertisements/{platform_id}
    public function show(string $platformId)
    {
        $ad = Advertisement::where('platform_id', $platformId)->first();

        if (! $ad) {
            return $this->notFound();
        }

        return response()->json($ad);
    }

    // PUT /effyads/v1/advertisements/{platform_id}
    public function update(Request $request, string $platformId)
    {
        $ad = Advertisement::where('platform_id', $platformId)->first();

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
    public function destroy(string $platformId)
    {
        $ad = Advertisement::where('platform_id', $platformId)->first();

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
    public function syncStatus()
    {
        return response()->json([
            'success' => true,
            'total_ads' => Advertisement::count(),
            'laravel_version' => app()->version(),
            'package_version' => '1.0.0',
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

        $toDelete = Advertisement::whereNotIn('platform_id', $platformIds)->get();

        $deleted = [];
        if ($deleteMissing) {
            foreach ($toDelete as $ad) {
                $deleted[] = ['platform_id' => $ad->platform_id, 'id' => $ad->id, 'title' => $ad->title];
                $ad->delete();
            }
        }

        return response()->json([
            'success' => true,
            'platform_ads_count' => count($platformIds),
            'local_ads_count' => Advertisement::count() + count($deleted),
            'deleted_count' => count($deleted),
            'deleted_ads' => $deleted,
            'timestamp' => now()->toDateTimeString(),
        ]);
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
