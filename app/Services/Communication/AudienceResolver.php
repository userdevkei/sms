<?php

// app/Services/Communication/AudienceResolver.php — replace the whole class body
namespace App\Services\Communication;

use App\Models\AdmissionGuardian;
use App\Models\Communication;
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

    protected function manual(Communication $comm): Collection
    {
        return collect($comm->audience_params['manual_recipients'] ?? [])
            ->map(fn ($d) => $comm->channel === 'email'
                ? $this->row($comm, $d, null, null, null, [])
                : $this->row($comm, null, $d, null, null, []));
    }

    protected function students(Communication $comm): Collection
{
    $params = $comm->audience_params ?? [];

    $query = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'student'));

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
                    ->whereIn('roles.name', $excluded);
            });

        if (($params['mode'] ?? 'all') === 'specific') {
            $query->whereIn('id', $params['ids'] ?? []);
        }

        return $query->get()
            ->map(fn ($u) => $this->row($comm, $u->email, $u->phone_number, User::class, $u->id, ['name' => $u->name]));
    }

    protected function guardians(Communication $comm): Collection
    {
        $params = $comm->audience_params ?? [];

        $query = AdmissionGuardian::query();

        if (($params['mode'] ?? 'all') === 'specific') {
            $query->whereIn('id', $params['ids'] ?? []);
        }

        return $query->get()
            ->map(fn ($g) => $this->row($comm, $g->email, $g->phone_number, AdmissionGuardian::class, $g->id, ['name' => $g->full_name]));
    }
}
