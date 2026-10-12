<?php

namespace App\View\Composers;

use App\Models\User;
use App\Support\GuardianNav;
use Illuminate\View\View;

class GuardianLayoutComposer
{
    public function __construct(private GuardianNav $nav) {}

    public function compose(View $view): void
    {
        $user  = auth()->user();
        $route = request()->route();
        $param = $route?->parameter('child');
        $child = $param instanceof User ? $param : null;

        $students = $user ? $user->activeStudents()->orderBy('users.first_name')->get() : collect();

        // Switching child keeps you on the same page when the route only needs {child}.
        $switchable = $child && $route->getName() && count($route->parameters()) === 1;
        $switch = [];
        foreach ($students as $s) {
            $switch[$s->id] = $switchable
                ? route($route->getName(), ['child' => $s->id])
                : route('guardian.students.show', ['child' => $s->id]);
        }

        $view->with([
            'gUser'     => $user,
            'gChild'    => $child,
            'gStudents' => $students,
            'gSwitch'   => $switch,
            'gNav'      => $this->nav->sections($child),
        ]);
    }
}
