<?php

namespace App\Services\Communication;

use App\Models\AdmissionGuardian;
use App\Models\Communication;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class AudienceResolver
{
    public function resolve(Communication $comm): Collection
    {
        $rows = match ($comm->audience_type) {
            'manual'    => $this->manual($comm),
            'students'  => $this->students($comm),
            'staff'     => $this->staff($comm),
            'guardians' => $this->guardians($comm),
            default     => collect(),
        };

        return $rows->filter()->unique('destination')->values();
    }

    protected function destination(Communication $comm, ?string $email, ?string $phone): ?string
    {
        if ($comm->channel === 'email') {
            return filled($email) ? trim($email) : null;
        }

        return $this->normalizePhone($phone); // sms + whatsapp
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '254') => $digits,
            str_starts_with($digits, '0')   => '254' . substr($digits, 1),
            strlen($digits) === 9           => '254' . $digits,
            default                         => $digits,
        };
    }

    protected function row(Communication $comm, ?string $email, ?string $phone, ?string $type, $id, array $placeholders): ?array
    {
        $destination = $this->destination($comm, $email, $phone);

        return $destination ? [
            'destination'    => $destination,
            'recipient_type' => $type,
            'recipient_id'   => $id,
            'placeholders'   => $placeholders,
        ] : null;
    }

    protected function students(Communication $comm): Collection
    {
        $params = $comm->audience_params ?? [];

        $query = User::query()->whereHas('roles', fn ($q) => $q->where('slug', 'student'));

        $query = match ($params['mode'] ?? 'all') {
            'grade'    => $query->whereHas('currentEnrollment', function ($q) use ($params) {
                $q->where($q->getModel()->qualifyColumn('grade_level_id'), $params['grade_level_id'] ?? null);
            }),
            'specific' => $query->whereIn('id', $params['ids'] ?? []),
            default    => $query,
        };

        $isFeeBalance = $comm->template?->trigger_key === 'finance.fee_balance_reminder';

        if ($isFeeBalance) {
            $query
                ->withSum(['invoices as total_charged' => fn ($q) => $q->whereNull('deleted_at')], 'total_amount')
                ->withSum(['payments as total_paid' => fn ($q) => $q->whereNull('deleted_at')], 'amount');
        }

        return $query->get()
            ->when($isFeeBalance, fn ($c) => $c->map(function ($s) {
                $s->balance_total = (float) ($s->total_charged ?? 0) - (float) ($s->total_paid ?? 0);
                return $s;
            })->filter(fn ($s) => $s->balance_total > 0))
            ->map(fn ($s) => $this->row($comm, $s->email, $s->phone_number, User::class, $s->id, [
                'student_name'   => trim("{$s->first_name} {$s->last_name}"),
                'student_number' => $s->userID,
                'balance'        => $isFeeBalance ? number_format($s->balance_total, 2) : null,
            ]));
    }

    protected function staff(Communication $comm): Collection
    {
        $params   = $comm->audience_params ?? [];
        $excluded = ['student', 'parent', 'guardian']; // match your roles.name / roles.slug values

        $query = User::query()
            ->whereNotExists(function ($q) use ($excluded) {
                $q->select(DB::raw(1))
                    ->from('role_users')
                    ->join('roles', 'roles.id', '=', 'role_users.role_id')
                    ->whereColumn('role_users.user_id', 'users.id')
                    ->whereIn('roles.slug', $excluded);
            });

        if (($params['mode'] ?? 'all') === 'specific') {
            $query->whereIn('id', $params['ids'] ?? []);
        }

        return $query->get()
            ->map(fn ($u) => $this->row($comm, $u->email, $u->phone_number, User::class, $u->id, ['name' => $u->name]));
    }

    protected function guardians(Communication $comm): Collection
    {
        $params   = $comm->audience_params ?? [];
        $included = ['parent', 'guardian']; // match your roles.name / roles.slug values

        $query = User::query()
            ->whereExists(function ($q) use ($included) {
                $q->select(DB::raw(1))
                    ->from('role_users')
                    ->join('roles', 'roles.id', '=', 'role_users.role_id')
                    ->whereColumn('role_users.user_id', 'users.id')
                    ->whereIn('roles.slug', $included);
            });

        if (($params['mode'] ?? 'all') === 'specific') {
            $query->whereIn('users.id', $params['ids'] ?? []);
        }

        return $query->get()
            ->map(fn ($u) => $this->row(
                $comm,
                $u->email,
                $u->phone_number,
                User::class,
                $u->id,
                ['name' => $u->full_name] // users have first_name/last_name, so "name" is null
            ));
    }

    protected function manual(Communication $comm): Collection
    {
        $isEmail     = $comm->channel === 'email';
        $withBalance = $this->usesBalance($comm);

        $destinations = collect($comm->audience_params['manual_recipients'] ?? [])
            ->map(fn ($d) => trim((string) $d))
            ->filter()
            ->unique()
            ->values();

        // destination => accounts that contact belongs to (empty = not in the system: send as typed)
        $usersByDest = $this->usersForDestinations($comm, $destinations);
        $allUsers    = $usersByDest->flatMap(fn ($c) => $c->all());

        // Children of any matched guardian (active links only).
        $guardianIds = $allUsers->filter(fn ($u) => $this->hasRole($u, Role::GUARDIAN_SLUG))->pluck('id')->unique()->values();

        $linksByGuardian = $guardianIds->isEmpty()
            ? collect()
            : DB::table('guardian_student')
                ->whereIn('guardian_id', $guardianIds)->where('status', 'active')
                ->get(['guardian_id', 'student_id'])
                ->groupBy('guardian_id');

        $children = $linksByGuardian->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $linksByGuardian->flatten(1)->pluck('student_id')->unique())->get()->keyBy('id');

        // Balances: only worked out when the message actually uses {{balance}} (invoiced minus paid, like students()).
        $balances = collect();

        if ($withBalance) {
            $studentIds = $allUsers->filter(fn ($u) => $this->hasRole($u, 'student'))->pluck('id')
                ->merge($children->keys())->unique()->values();

            if ($studentIds->isNotEmpty()) {
                $balances = User::query()->whereIn('id', $studentIds)
                    ->withSum(['invoices as total_charged' => fn ($q) => $q->whereNull('deleted_at')], 'total_amount')
                    ->withSum(['payments as total_paid' => fn ($q) => $q->whereNull('deleted_at')], 'amount')
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->id => (float) ($s->total_charged ?? 0) - (float) ($s->total_paid ?? 0)]);
            }
        }

        return $destinations->map(function ($d) use ($comm, $isEmail, $withBalance, $usersByDest, $linksByGuardian, $children, $balances) {
            $users = $usersByDest->get($d, collect());

            // Not in the system: pass the message through exactly as typed.
            if ($users->isEmpty()) {
                return $isEmail
                    ? $this->row($comm, $d, null, null, null, [])
                    : $this->row($comm, null, $d, null, null, []);
            }

            // Students this contact is about: the student themself and/or a guardian's children.
            $students = collect();

            foreach ($users as $u) {
                if ($this->hasRole($u, 'student')) {
                    $students->push($u);
                }

                if ($this->hasRole($u, Role::GUARDIAN_SLUG)) {
                    foreach ($linksByGuardian->get($u->id, []) as $link) {
                        if ($child = $children->get($link->student_id)) {
                            $students->push($child);
                        }
                    }
                }
            }

            $placeholders = $this->manualPlaceholders($users, $students->unique('id')->values(), $balances, $withBalance);

            // One account matched => record it as the recipient (the portal inbox uses this); several => no single recipient.
            $single = $users->count() === 1 ? $users->first() : null;
            $type   = $single ? User::class : null;

            return $isEmail
                ? $this->row($comm, $d, null, $type, $single?->id, $placeholders)
                : $this->row($comm, null, $d, $type, $single?->id, $placeholders);
        });
    }

    /** Placeholder values for a manual recipient. Keys with nothing to say are simply left out. */
    protected function manualPlaceholders(Collection $users, Collection $students, Collection $balances, bool $withBalance): array
    {
        $full = fn ($u) => trim("{$u->first_name} {$u->last_name}");

        // Who is being addressed when accounts share one contact: guardian, then staff, then student.
        $rank = fn ($u) => $this->hasRole($u, Role::GUARDIAN_SLUG) ? 0 : ($this->hasRole($u, 'student') ? 2 : 1);
        $top  = $users->groupBy($rank)->sortKeys()->first();

        $ph = [
            'name'       => $top->map($full)->implode(', '),
            'first_name' => $top->pluck('first_name')->implode(', '),
            'last_name'  => $top->pluck('last_name')->implode(', '),
            'user_id'    => $top->pluck('userID')->implode(', '),
        ];

        $guardians = $users->filter(fn ($u) => $this->hasRole($u, Role::GUARDIAN_SLUG));

        if ($guardians->isNotEmpty()) {
            $ph['guardian_name']       = $guardians->map($full)->implode(', ');
            $ph['guardian_first_name'] = $guardians->pluck('first_name')->implode(', ');
        }

        if ($students->isNotEmpty()) {
            $ph += [
                'student_name'       => $students->map($full)->implode(', '),
                'student_first_name' => $students->pluck('first_name')->implode(', '),
                'student_number'     => $students->pluck('userID')->implode(', '),
            ];

            if ($withBalance) {
                $ph['balance'] = number_format($students->sum(fn ($s) => max($balances[$s->id] ?? 0, 0)), 2);
            }
        }

        return $ph;
    }

    /** destination => accounts (any role) that email/phone belongs to. Only matched destinations appear. */
    protected function usersForDestinations(Communication $comm, Collection $destinations): Collection
    {
        $isEmail = $comm->channel === 'email';

        // Emails compare lower-cased; phones on their last 9 digits (0729…, 254729… and +254729… all match).
        $keyOf = fn (?string $v) => $isEmail
            ? strtolower(trim((string) $v))
            : substr((string) $this->normalizePhone($v), -9);

        $keys = $destinations->map(fn ($d) => $keyOf($d))->filter()->unique()->values();

        if ($keys->isEmpty()) {
            return collect();
        }

        $users = User::query()
            ->when(
                $isEmail,
                fn ($q) => $q->whereIn(DB::raw('LOWER(email)'), $keys->all()),
                fn ($q) => $q->whereIn(DB::raw("RIGHT(REGEXP_REPLACE(phone_number, '[^0-9]', ''), 9)"), $keys->all())
            )
            ->with('roles:id,name,slug')
            ->get();

        $map = collect();

        foreach ($destinations as $d) {
            $k = $keyOf($d);

            if ($k === '') {
                continue;
            }

            $hit = $users->filter(fn ($u) => $keyOf($isEmail ? $u->email : $u->phone_number) === $k)->values();

            if ($hit->isNotEmpty()) {
                $map[$d] = $hit;
            }
        }

        return $map;
    }

    protected function hasRole(User $u, string $role): bool
    {
        return $u->roles->contains(fn ($r) => $r->slug === $role || $r->name === $role);
    }

    /** True when anything this message can render uses {{balance}}, so balances are only computed when needed. */
    protected function usesBalance(Communication $comm): bool
    {
        $t = $comm->template;

        if ($t?->trigger_key === 'finance.fee_balance_reminder') {
            return true;
        }

        $text = implode(' ', array_filter([$comm->subject, $comm->body, $t?->subject, $t?->sms_body, $t?->email_body, $t?->whatsapp_body]));

        return (bool) preg_match('/\{\{\s*balance\s*\}\}/i', $text);
    }
}
