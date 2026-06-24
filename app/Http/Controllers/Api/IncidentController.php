<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\Request;

/**
 * GET /api/incidents
 * GET /api/incidents/{id}
 * PUT /api/incidents/{id}   → manual resolve
 */
class IncidentController extends BaseApiController
{
    public function __construct(private IncidentService $incidentService) {}

    /**
     * GET /api/incidents
     * Supports: ?status=active|resolved, ?search=, ?sort=, ?page=
     */
    public function index(Request $request)
    {
        if (!auth()->user()->can('view incidents')) {
            return $this->error('Forbidden.', 403);
        }

        $query = Incident::query();

        // Status filter
        $status = $request->query('status', 'all');
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'resolved') {
            $query->resolved();
        }

        // Search by device name or issue
        if ($search = $request->query('search')) {
            $query->where(fn($q) =>
                $q->whereHas('device', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhere('issue', 'like', "%{$search}%")
            );
        }

        // Sort
        $sort = $request->query('sort', '-started_at');
        $dir  = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $col  = ltrim($sort, '-');
        $allowed = ['started_at', 'resolved_at', 'status'];
        if (!in_array($col, $allowed)) $col = 'started_at';

        $query->orderBy($col, $dir);

        $incidents = $query->paginate((int) $request->query('per_page', 20));

        return $this->success([
            'items'        => IncidentResource::collection($incidents->items()),
            'total'        => $incidents->total(),
            'per_page'     => $incidents->perPage(),
            'current_page' => $incidents->currentPage(),
            'last_page'    => $incidents->lastPage(),
        ]);
    }

    /**
     * GET /api/incidents/{id}
     */
    public function show(int $id)
    {
        if (!auth()->user()->can('view incidents')) {
            return $this->error('Forbidden.', 403);
        }

        $incident = Incident::find($id);

        if (!$incident) {
            return $this->error('Incident not found.', 404);
        }

        return $this->success(new IncidentResource($incident));
    }

    /**
     * PUT /api/incidents/{id}  — manually resolve an incident
     * Body: {"action": "resolve"} (optional note)
     */
    public function update(Request $request, int $id)
    {
        if (!auth()->user()->can('manage incidents')) {
            return $this->error('Forbidden.', 403);
        }

        $request->validate([
            'action' => 'required|in:resolve',
        ]);

        $incident = Incident::find($id);

        if (!$incident) {
            return $this->error('Incident not found.', 404);
        }

        if (!$incident->isActive()) {
            return $this->error('Incident is already resolved.', 409);
        }

        $secs = $incident->started_at ? now()->diffInSeconds($incident->started_at) : 0;
        $incident->update([
            'resolved_at' => now(),
            'duration'    => $incident->displayDuration(),
        ]);

        return $this->success(new IncidentResource($incident->fresh()), 'Incident resolved.');
    }
}
