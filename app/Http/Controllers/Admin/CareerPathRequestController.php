<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CareerPathRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CareerPathRequestController extends Controller
{
    public function index(): View
    {
        $groups = CareerPathRequest::query()
            ->select([
                'requested_path',
                DB::raw('COUNT(*) as request_count'),
                DB::raw('MAX(created_at) as latest_at'),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count"),
            ])
            ->groupBy('requested_path')
            ->orderByDesc('request_count')
            ->orderBy('requested_path')
            ->get();

        $requests = CareerPathRequest::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.career-path-requests.index', [
            'groups' => $groups,
            'requests' => $requests,
        ]);
    }

    public function markReviewed(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_path' => ['required', 'string', 'max:120'],
        ]);

        CareerPathRequest::query()
            ->where('requested_path', $validated['requested_path'])
            ->where('status', CareerPathRequest::STATUS_PENDING)
            ->update(['status' => CareerPathRequest::STATUS_REVIEWED]);

        return redirect()
            ->route('admin.career-path-requests.index')
            ->with('success', 'Marked as reviewed.');
    }
}
