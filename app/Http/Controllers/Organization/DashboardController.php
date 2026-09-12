<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $organization = request()->user()->currentOrganization();
        abort_unless($organization, 403);
        $this->authorize('view', $organization);

        $opportunities = $organization->opportunities()
            ->orderByDesc('id')
            ->get();

        $stats = [
            'total' => $opportunities->count(),
            'draft' => $opportunities->where('approval_status', Opportunity::APPROVAL_DRAFT)->count(),
            'pending' => $opportunities->where('approval_status', Opportunity::APPROVAL_PENDING)->count(),
            'approved' => $opportunities->where('approval_status', Opportunity::APPROVAL_APPROVED)->count(),
            'rejected' => $opportunities->where('approval_status', Opportunity::APPROVAL_REJECTED)->count(),
        ];

        return view('organization.dashboard', [
            'organization' => $organization,
            'stats' => $stats,
            'recent' => $opportunities->take(8),
            'isOwner' => request()->user()->isOrganizationOwner($organization),
        ]);
    }
}
