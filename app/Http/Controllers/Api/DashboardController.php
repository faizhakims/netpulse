<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;

class DashboardController extends BaseApiController
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index()
    {
        if (!auth()->user()->can('view dashboard')) {
            return $this->error('Forbidden.', 403);
        }

        $data = $this->dashboardService->getDashboardData();

        return $this->success(new DashboardResource($data));
    }
}
