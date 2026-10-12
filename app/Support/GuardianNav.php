<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class GuardianNav
{
    /** Items appear only when their route exists and the guardian holds the permission. */
    public function sections(?User $child): array
    {
        $user = auth()->user();

        $defs = $child
            ? [
                [null, [['Dashboard', 'bi-speedometer2', 'guardian.students.show']]],
                ['Academics', [
                    ['Timetable', 'bi-calendar-week', 'guardian.students.timetable'],
                    ['Results', 'bi-journal-text', 'guardian.students.results'],
                ]],
                ['Finance', [
                    ['Statement', 'bi-file-earmark-text', 'guardian.students.statement'],
                    ['Payments', 'bi-cash-coin', 'guardian.students.payments'],
                    ['Pay Fees', 'bi-phone', 'guardian.students.pay', 'guardian-portal.pay'],
                ]],
                ['Communication', [
                    ['Messages', 'bi-chat-dots', 'guardian.students.messages'],
                ]],
                ['Student', [
                    ['Student Details', 'bi-person-gear', 'guardian.students.details', 'guardian-portal.update_student'],
                ]],
            ]
            : [
                [null, [
                    ['Dashboard', 'bi-speedometer2', 'guardian.dashboard'],
                    ['Messages', 'bi-chat-dots', 'guardian.messages'],
                    ['My Account', 'bi-person-circle', 'guardian.account'],
                ]],
            ];

        $sections = [];
        foreach ($defs as [$heading, $items]) {
            $resolved = [];
            foreach ($items as $item) {
                [$label, $icon, $route] = $item;
                $perm = $item[3] ?? null;

                if (! Route::has($route) || ($perm && ! $user?->hasPermission($perm))) {
                    continue;
                }

                $resolved[] = [
                    'label'  => $label,
                    'icon'   => $icon,
                    'route'  => $route,
                    'url'    => $child ? route($route, ['child' => $child->id]) : route($route),
                    'active' => request()->routeIs($route, $route . '.*'),
                ];
            }

            if ($resolved) {
                $sections[] = ['heading' => $heading, 'items' => $resolved];
            }
        }

        return $sections;
    }

    /** Flat list for the Quick Links tiles (everything except Dashboard). */
    public function quickLinks(User $child): array
    {
        return collect($this->sections($child))
            ->flatMap(fn ($s) => $s['items'])
            ->reject(fn ($i) => $i['route'] === 'guardian.students.show')
            ->values()->all();
    }
}
