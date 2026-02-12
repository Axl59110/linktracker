<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBacklinkRequest;
use App\Http\Requests\UpdateBacklinkRequest;
use App\Models\Backlink;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BacklinkController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Backlink::class, 'backlink');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $backlinks = $project->backlinks()
            ->with('latestCheck')
            ->latest()
            ->get();

        return response()->json($backlinks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBacklinkRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $backlink = $project->backlinks()->create(array_merge(
            $request->validated(),
            [
                'status' => 'active',
                'first_seen_at' => now(),
            ]
        ));

        return response()->json($backlink->load('latestCheck'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project, Backlink $backlink): JsonResponse
    {
        return response()->json($backlink->load(['latestCheck', 'checks']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBacklinkRequest $request, Project $project, Backlink $backlink): JsonResponse
    {
        $backlink->update($request->validated());

        return response()->json($backlink->load('latestCheck'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project, Backlink $backlink): JsonResponse
    {
        $backlink->delete();

        return response()->json(null, 204);
    }
}
