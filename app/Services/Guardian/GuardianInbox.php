<?php

namespace App\Services\Guardian;

use App\Models\CommunicationRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuardianInbox
{
    public const SCOPE_ALL      = 'all';
    public const SCOPE_ME       = 'me';        // communication addressed to the guardian
    public const SCOPE_STUDENTS = 'students';  // communication addressed to / about their students

    /** Audiences that are never shown to guardians (internal / applicant flows). */
    private const HIDDEN_AUDIENCES = ['staff', 'applicants'];

    /**
     * Delivered ('sent' only) messages that belong to this guardian.
     *  students: addressed to one of their linked children
     *  me:       addressed to the guardian's own account, or manual sends to their own phone/email
     * With $child, only that child's messages.
     */
    public function query(User $guardian, ?User $child = null, string $scope = self::SCOPE_ALL): Builder
    {
        $userType = (new User)->getMorphClass(); // safe with or without a morph map

        $childIds = DB::table('guardian_student')
            ->where('guardian_id', $guardian->id)
            ->whereIn('status', ['active', 'failed'])
            ->pluck('student_id');

        if ($child) {
            $childIds = $childIds->intersect([$child->id])->values();
        }

        $phone = $this->phoneOf($guardian);
        $email = $this->emailOf($guardian);

        $branches = [];

        if ($scope !== self::SCOPE_ME) {
            $branches[] = fn ($x) => $x->where('recipient_type', $userType)->whereIn('recipient_id', $childIds);
        }

        if (! $child && $scope !== self::SCOPE_STUDENTS) {
            $branches[] = fn ($x) => $x->where('recipient_type', $userType)->where('recipient_id', $guardian->id);

            if ($phone || $email) {
                $branches[] = fn ($x) => $x->whereNull('recipient_id')->where(function ($d) use ($phone, $email) {
                    if ($phone) {
                        $d->orWhere('destination', $phone);
                    }
                    if ($email) {
                        $d->orWhereRaw('LOWER(destination) = ?', [$email]);
                    }
                });
            }
        }

        return CommunicationRecipient::query()
            ->whereIn('status', ['sent', 'failed'])
            ->whereHas('communication', fn ($c) => $c->whereNotIn('audience_type', self::HIDDEN_AUDIENCES))
            ->whereDoesntHave('communication.template', fn ($t) => $t->where('trigger_key', 'like', 'admission.%'))
            ->where(function (Builder $w) use ($branches) {
                if (! $branches) {
                    $w->whereRaw('1 = 0');

                    return;
                }

                foreach ($branches as $branch) {
                    $w->orWhere($branch);
                }
            });
    }

    public function counts(User $guardian): array
    {
        return [
            self::SCOPE_ALL      => $this->query($guardian)->count(),
            self::SCOPE_ME       => $this->query($guardian, null, self::SCOPE_ME)->count(),
            self::SCOPE_STUDENTS => $this->query($guardian, null, self::SCOPE_STUDENTS)->count(),
        ];
    }

    public function paginate(User $guardian, ?User $child, array $filters, string $scope = self::SCOPE_ALL, int $perPage = 15): LengthAwarePaginator
    {
        $userType = (new User)->getMorphClass();
        $channel  = $filters['channel'] ?? null;
        $term     = trim((string) ($filters['q'] ?? ''));
        $childId  = $filters['child'] ?? null;

        return $this->query($guardian, $child, $scope)
            ->with('communication.template')
            ->when(in_array($channel, ['email', 'sms', 'whatsapp'], true),
                fn ($q) => $q->whereHas('communication', fn ($c) => $c->where('channel', $channel)))
            ->when($childId, fn ($q) => $q->where('recipient_type', $userType)->where('recipient_id', $childId))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->whereHas('communication', fn ($c) => $c->where(fn ($w) => $w
                    ->where('subject', 'like', $like)
                    ->orWhereHas('template', fn ($t) => $t->where('subject', 'like', $like))));
            })
            ->orderByDesc('sent_at')->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** The guardian's active children, keyed by id. */
    public function children(User $guardian): Collection
    {
        return $guardian->students()->wherePivot('status', 'active')->orderBy('users.first_name')->get()->keyBy('id');
    }

    /**
     * What the guardian was sent. Uses the exact text saved at send time; older rows fall back to the template.
     * Any {{token}} still left in the text is filled in here (or shown as "—" when it can't be known).
     */
    public function content(CommunicationRecipient $row, User $guardian, Collection $children): array
    {
        $comm     = $row->communication;
        $template = $comm->template;
        $channel  = $comm->channel;
        $isEmail  = $channel === 'email';

        $stored = filled($row->message);

        if ($stored) {
            $body    = (string) $row->message;
            $subject = (string) ($row->subject ?? '');
        } else {
            $body    = $template ? $this->bodyFor($channel, $template) : (string) ($comm->body ?? '');
            $subject = $template ? (string) ($template->subject ?? $comm->subject ?? '') : (string) ($comm->subject ?? '');
        }

        if (trim(strip_tags($body)) === '') {
            $body = $isEmail ? '<p>The content of this message is no longer available.</p>' : 'The content of this message is no longer available.';
        }

        $userType = (new User)->getMorphClass();
        $child    = $row->recipient_type === $userType ? $children->get($row->recipient_id) : null;

        $vars = [
            'name'                => $guardian->full_name,
            'guardian_name'       => $guardian->full_name,
            'first_name'          => $guardian->first_name,
            'guardian_first_name' => $guardian->first_name,
            'student_name'        => $child ? trim("{$child->first_name} {$child->last_name}") : '—',
            'student_first_name'  => $child?->first_name ?? '—',
            'student_number'      => $child?->userID ?? '—',
        ];

        $render = fn (string $text, bool $html) => preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn ($m) => $html ? e($vars[$m[1]] ?? '—') : ($vars[$m[1]] ?? '—'),
            $text
        );

        $text = $isEmail ? null : $render($body, false);

        return [
            'channel'    => $channel,
            'subject'    => trim($render($subject, false)) ?: null,
            'html'       => $isEmail ? $render($body, true) : null,
            'text'       => $text,
            'text_html'  => $isEmail ? null : $this->formatText($text, $channel),
            'child'      => $child,
            'kind'       => $child ? 'student' : 'me',
            // Saved messages carry the real amount; the note is only for old rows rendered from the template.
            'has_amount' => ! $stored && (bool) preg_match('/\{\{\s*(balance|total_balance)\s*\}\}/i', $body . $subject),
            'sent_at'    => $row->sent_at ?? $row->created_at,
        ];
    }

    public function snippet(array $content): string
    {
        if ($content['html'] !== null) {
            // Block ends become spaces so "Dear Parent/Guardian,</p><p>This…" doesn't glue together.
            $spaced = preg_replace('/<\/(p|div|li|h[1-6]|tr)>|<br\s*\/?>/i', ' ', $content['html']);
            $plain  = strip_tags(html_entity_decode($spaced));
        } else {
            $plain = (string) $content['text'];

            if ($content['channel'] === 'whatsapp') {
                $plain = str_replace('*', '', $plain);
            }
        }

        return Str::limit(trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $plain)), 140);
    }

    /** The email exactly as delivered: same wrapper view and branding the send job uses. */
    public function emailDocument(string $bodyHtml): string
    {
        $html = view('emails.communication', [
            'bodyHtml'   => $bodyHtml,
            'schoolName' => setting('school_name') ?? config('app.name'),
            'logoUrl'    => $this->logoBase64(setting('logo_path')),
            'brandColor' => setting('primary_color') ?? '#0d6efd',
        ])->render();

        // Links open in a new tab.
        return stripos($html, '<head>') !== false
            ? preg_replace('/<head>/i', '<head><base target="_blank">', $html, 1)
            : '<base target="_blank">' . $html;
    }

    /* ───────────── helpers ───────────── */

    /** Safe HTML for SMS / WhatsApp text: escaped first, then WhatsApp *bold* and _italic_ shown properly. */
    private function formatText(string $text, string $channel): string
    {
        $html = e($text);

        if ($channel === 'whatsapp') {
            $html = preg_replace('/\*{1,2}([^*\n]+?)\*{1,2}/', '<strong>$1</strong>', $html);
            $html = preg_replace('/(?<![A-Za-z0-9])_([^_\n]+?)_(?![A-Za-z0-9])/', '<em>$1</em>', $html);
        }

        return nl2br($html);
    }

    private function bodyFor(string $channel, $template): string
    {
        return match ($channel) {
            'email'    => $template?->email_body,
            'sms'      => $template?->sms_body,
            'whatsapp' => $template?->whatsapp_body ?? $template?->sms_body,
            default    => null,
        } ?? '';
    }

    /** Same normalisation your AudienceResolver stores in `destination` for sms/whatsapp. */
    private function phoneOf(User $u): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $u->phone_number);

        if ($d === '') {
            return null;
        }

        if (str_starts_with($d, '0')) {
            $d = '254' . substr($d, 1);
        } elseif (strlen($d) === 9) {
            $d = '254' . $d;
        }

        return preg_match('/^254\d{9}$/', $d) ? $d : null;
    }

    private function emailOf(User $u): ?string
    {
        $e = strtolower(trim((string) $u->email));

        return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null;
    }

    private function logoBase64(?string $path): ?string
    {
        if (! $path || ! file_exists($full = base_path($path))) {
            return null;
        }

        return 'data:' . mime_content_type($full) . ';base64,' . base64_encode(file_get_contents($full));
    }
}
