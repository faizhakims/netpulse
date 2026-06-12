<?php

namespace App\Http\Controllers;

use App\Services\TrafficService;

class TrafficController extends Controller
{
    public function __construct(
        private TrafficService $trafficService
    ) {}

    public function index()
    {
        abort_unless(auth()->user()->can('view traffic'), 403, 'Access denied.');
        
        $data = $this->trafficService->getTrafficData();

        return view('traffic', $data);
    }
}