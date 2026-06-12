<?php

namespace App\Http\Controllers;

use App\Services\IncidentService;

class IncidentController extends Controller
{
    public function __construct(
        private IncidentService $incidentService
    ) {}

    public function index()
    {
        abort_unless(auth()->user()->can('view incidents'), 403, 'Access denied.');
        
        $data = $this->incidentService->getIncidentsData();

        return view('incidents', $data);
    }
}
