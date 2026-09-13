<?php

namespace App\Services;

class DemoSubscriptionCatalog
{
    /**
     * Demonstration-only subscription records. Not connected to a payment gateway.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $today = now()->startOfDay();

        return [
            [
                'id' => 'demo-sub-1',
                'user' => 'Aisha Rahman',
                'email' => 'aisha.demo@pathforge.test',
                'plan' => 'Pro',
                'amount' => 499,
                'currency' => 'INR',
                'start_date' => $today->copy()->subMonths(4)->toDateString(),
                'renews_on' => $today->copy()->addDays(18)->toDateString(),
                'status' => 'Active',
                'payment_method' => 'Demo / Not Connected',
                'history' => [
                    ['date' => $today->copy()->subMonth()->toDateString(), 'amount' => 499, 'status' => 'Paid (demo)'],
                    ['date' => $today->copy()->subMonths(2)->toDateString(), 'amount' => 499, 'status' => 'Paid (demo)'],
                    ['date' => $today->copy()->subMonths(3)->toDateString(), 'amount' => 499, 'status' => 'Paid (demo)'],
                ],
            ],
            [
                'id' => 'demo-sub-2',
                'user' => 'Rohan Mehta',
                'email' => 'rohan.demo@pathforge.test',
                'plan' => 'Premium',
                'amount' => 899,
                'currency' => 'INR',
                'start_date' => $today->copy()->subMonths(8)->toDateString(),
                'renews_on' => $today->copy()->addDays(6)->toDateString(),
                'status' => 'Expiring Soon',
                'payment_method' => 'Demo / Not Connected',
                'history' => [
                    ['date' => $today->copy()->subDays(24)->toDateString(), 'amount' => 899, 'status' => 'Paid (demo)'],
                    ['date' => $today->copy()->subMonths(2)->toDateString(), 'amount' => 899, 'status' => 'Paid (demo)'],
                ],
            ],
            [
                'id' => 'demo-sub-3',
                'user' => 'Priya Nair',
                'email' => 'priya.demo@pathforge.test',
                'plan' => 'Pro',
                'amount' => 499,
                'currency' => 'INR',
                'start_date' => $today->copy()->subYear()->toDateString(),
                'renews_on' => $today->copy()->subDays(12)->toDateString(),
                'status' => 'Expired',
                'payment_method' => 'Demo / Not Connected',
                'history' => [
                    ['date' => $today->copy()->subMonths(13)->toDateString(), 'amount' => 499, 'status' => 'Paid (demo)'],
                    ['date' => $today->copy()->subDays(12)->toDateString(), 'amount' => 499, 'status' => 'Failed (demo)'],
                ],
            ],
            [
                'id' => 'demo-sub-4',
                'user' => 'Dev Patel',
                'email' => 'dev.demo@pathforge.test',
                'plan' => 'Premium',
                'amount' => 899,
                'currency' => 'INR',
                'start_date' => $today->copy()->subDays(20)->toDateString(),
                'renews_on' => $today->copy()->addMonth()->toDateString(),
                'status' => 'Active',
                'payment_method' => 'Demo / Not Connected',
                'history' => [
                    ['date' => $today->copy()->subDays(20)->toDateString(), 'amount' => 899, 'status' => 'Paid (demo)'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        foreach ($this->all() as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array{total: int, active: int, expiring: int, revenue: int}
     */
    public function summary(): array
    {
        $rows = $this->all();
        $active = collect($rows)->whereIn('status', ['Active', 'Expiring Soon'])->count();
        $expiring = collect($rows)->where('status', 'Expiring Soon')->count();
        $revenue = collect($rows)
            ->flatMap(fn (array $row) => $row['history'])
            ->where('status', 'Paid (demo)')
            ->sum('amount');

        return [
            'total' => count($rows),
            'active' => $active,
            'expiring' => $expiring,
            'revenue' => $revenue,
        ];
    }
}
