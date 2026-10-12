<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Guardian\GuardianInbox;
use Illuminate\Http\Request;

class GuardianMessageController extends Controller
{
    public function __construct(private GuardianInbox $inbox) {}

    /** Across all students (+ messages sent to the guardian). */
    public function index(Request $request)
    {
        return $this->listing($request, null);
    }

    /** One child only. */
    public function forChild(Request $request, User $child)
    {
        return $this->listing($request, $child);
    }

    public function show(Request $request, string $msg)
    {
        return $this->detail($request, $msg, null);
    }

    public function showForChild(Request $request, User $child, string $msg)
    {
        return $this->detail($request, $msg, $child);
    }

    private function listing(Request $request, ?User $child)
    {
        $guardian = $request->user();
        $children = $this->inbox->children($guardian);

        $scope = $child
            ? GuardianInbox::SCOPE_STUDENTS
            : (in_array($request->query('scope'), [GuardianInbox::SCOPE_ME, GuardianInbox::SCOPE_STUDENTS], true)
                ? $request->query('scope')
                : GuardianInbox::SCOPE_ALL);

        $childFilter = $request->query('child');

        $filters = [
            'channel' => $request->query('channel'),
            'q'       => trim((string) $request->query('q', '')),
            'child'   => (! $child && $scope !== GuardianInbox::SCOPE_ME && $children->has($childFilter)) ? $childFilter : null,
        ];

        $paginator = $this->inbox->paginate($guardian, $child, $filters, $scope);

        $items = $paginator->getCollection()->map(function ($row) use ($guardian, $children, $child) {
            $c = $this->inbox->content($row, $guardian, $children);

            return [
                'url'     => $child
                    ? route('guardian.students.messages.show', ['child' => $child->id, 'msg' => $row->id])
                    : route('guardian.messages.show', $row->id),
                'channel' => $c['channel'],
                'subject' => $c['subject'],
                'snippet' => $this->inbox->snippet($c),
                'kind'    => $c['kind'],
                'child'   => $c['child']?->first_name,
                'sent_at' => $c['sent_at'],
            ];
        });

        $counts = $child ? null : $this->inbox->counts($guardian);

        return view('guardian.messages.index', compact('child', 'children', 'items', 'paginator', 'filters', 'scope', 'counts'));
    }

    private function detail(Request $request, string $msg, ?User $child)
    {
        $guardian = $request->user();

        // Same scoped query as the list: anything not theirs (or not this child's) is a 404.
        $row = $this->inbox->query($guardian, $child)->with('communication.template')->findOrFail($msg);

        $content = $this->inbox->content($row, $guardian, $this->inbox->children($guardian));
        $doc     = $content['html'] !== null ? $this->inbox->emailDocument($content['html']) : null;

        $backUrl = $child ? route('guardian.students.messages', $child->id) : route('guardian.messages');

        return view('guardian.messages.show', compact('content', 'doc', 'backUrl'));
    }
}
