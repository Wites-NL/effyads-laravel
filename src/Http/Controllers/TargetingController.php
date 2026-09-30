<?php

namespace EffyAds\Laravel\Http\Controllers;

use Illuminate\Routing\Controller;

class TargetingController extends Controller
{
    // GET /effyads/v1/targeting/options
    // WordPress exposes taxonomies/post-types/pages here; a Laravel app has no
    // equivalent built-in concept, so it exposes the app-defined zones from
    // config/effyads.php instead (see <x-effyads::ad zone="..." />).
    public function options()
    {
        $zones = collect(config('effyads.zones', []))
            ->map(fn (string $label, string $name) => ['name' => $name, 'label' => $label])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'zones' => $zones,
            ],
        ]);
    }
}
