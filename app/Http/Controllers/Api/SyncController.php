<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private SyncService $sync) {}

    public function push(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'max:500'],
            'items.*.entity' => ['required', 'string'],
            'items.*.id' => ['required', 'string'],
            'items.*.op' => ['required', 'in:create,update,delete,upsert'],
            'items.*.payload' => ['nullable', 'array'],
            'items.*.client_updated_at' => ['nullable', 'date'],
        ]);

        $items = array_map(function (array $item) {
            if (($item['op'] ?? '') === 'create' || ($item['op'] ?? '') === 'update') {
                $item['op'] = 'upsert';
            }
            if (($item['op'] ?? '') === 'upsert') {
                $item['op'] = 'upsert';
            }

            return $item;
        }, $data['items']);

        // Normalize upsert op to create/update path in SyncService (uses non-delete)
        foreach ($items as &$item) {
            if (($item['op'] ?? '') === 'upsert') {
                $item['op'] = 'create';
            }
        }
        unset($item);

        $result = $this->sync->push($request->user(), $items);

        return response()->json($result);
    }

    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'since' => ['nullable', 'date'],
        ]);

        $since = $request->query('since')
            ? Carbon::parse($request->query('since'))
            : null;

        return response()->json($this->sync->pull($request->user(), $since));
    }
}
