<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TrafficResource;
use App\Services\TrafficService;

class TrafficController extends BaseApiController
{
    public function __construct(private TrafficService $trafficService) {}

    public function index()
    {
        if (!auth()->user()->can('view traffic')) {
            return $this->error('Forbidden.', 403);
        }

        $data = $this->trafficService->getTrafficData();

        return $this->success(new TrafficResource($data));
    }
}
