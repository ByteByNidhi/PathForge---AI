<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DemoSubscriptionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriptionController extends Controller
{
    public function index(DemoSubscriptionCatalog $catalog): View
    {
        return view('admin.subscriptions.index', [
            'summary' => $catalog->summary(),
            'subscriptions' => $catalog->all(),
        ]);
    }

    public function show(string $subscription, DemoSubscriptionCatalog $catalog): View
    {
        $record = $catalog->find($subscription);

        if ($record === null) {
            throw new NotFoundHttpException;
        }

        return view('admin.subscriptions.show', [
            'subscription' => $record,
        ]);
    }

    public function upgrade(string $subscription, DemoSubscriptionCatalog $catalog): RedirectResponse
    {
        if ($catalog->find($subscription) === null) {
            throw new NotFoundHttpException;
        }

        return redirect()
            ->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Upgrade Plan is a demonstration control only. No payment was processed.');
    }
}
