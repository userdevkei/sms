<?php

namespace App\Http\Controllers;

use App\Models\AdmissionLevelSetting;
use App\Models\AdmissionRequirement;
use App\Models\EducationLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdmissionRequirementController extends Controller
{
    public function index(Request $request)
    {
        $levels = EducationLevel::where('status', 'active')->orderBy('sequence')->get();
        $level  = $levels->firstWhere('id', $request->query('level')) ?? $levels->first();

        $requirements = $level
            ? AdmissionRequirement::where('education_level_id', $level->id)->orderBy('sort_order')->orderBy('created_at')->get()
            : collect();

        $setting = $level ? AdmissionLevelSetting::forLevel($level->id) : null;

        return view('admissions.requirements.index', compact('levels', 'level', 'requirements', 'setting'));
    }

    public function store(Request $request)
    {
        $levelId = $request->validate([
            'education_level_id' => ['required', 'string', Rule::exists('education_levels', 'id')],
        ])['education_level_id'];

        $data = $this->validated($request);

        $next = (int) AdmissionRequirement::where('education_level_id', $levelId)->max('sort_order') + 1;

        AdmissionRequirement::create($data + ['education_level_id' => $levelId, 'sort_order' => $next, 'status' => 'active']);

        return $this->back($levelId, 'Requirement added.');
    }

    public function update(Request $request, AdmissionRequirement $requirement)
    {
        $requirement->update($this->validated($request));

        return $this->back($requirement->education_level_id, 'Requirement updated.');
    }

    public function destroy(AdmissionRequirement $requirement)
    {
        $requirement->delete();   // soft delete: answers already collected keep their label

        return $this->back($requirement->education_level_id, 'Requirement removed.');
    }

    public function move(Request $request, AdmissionRequirement $requirement)
    {
        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        $ordered = AdmissionRequirement::where('education_level_id', $requirement->education_level_id)
            ->orderBy('sort_order')->orderBy('created_at')->get()->values()->all();

        $index = collect($ordered)->search(fn ($r) => $r->id === $requirement->id);
        $swap  = $direction === 'up' ? $index - 1 : $index + 1;

        if (isset($ordered[$swap])) {
            [$ordered[$index], $ordered[$swap]] = [$ordered[$swap], $ordered[$index]];

            foreach ($ordered as $i => $r) {
                $r->update(['sort_order' => $i + 1]);
            }
        }

        return $this->back($requirement->education_level_id);
    }

    public function settings(Request $request, string $level)
    {
        $level = EducationLevel::findOrFail($level);

        $request->validate(['instructions' => ['nullable', 'string', 'max:2000']]);

        AdmissionLevelSetting::updateOrCreate(
            ['education_level_id' => $level->id],
            [
                'is_open'            => $request->boolean('is_open'),
                'requires_interview' => $request->boolean('requires_interview'),
                'instructions'       => $request->input('instructions'),
            ]
        );

        return $this->back($level->id, "Settings saved for {$level->name}.");
    }

    /* ------------------------------------------------------------------ */
    private function validated(Request $request): array
    {
        // ".pdf, .JPG" → "pdf,jpg"
        $request->merge([
            'allowed_extensions' => strtolower(str_replace([' ', '.'], '', (string) $request->input('allowed_extensions'))),
        ]);

        $data = $request->validate([
            'label'              => ['required', 'string', 'max:150'],
            'type'               => ['required', Rule::in(array_keys(AdmissionRequirement::TYPES))],
            'help_text'          => ['nullable', 'string', 'max:255'],
            'options_text'       => [Rule::requiredIf($request->input('type') === 'select'), 'nullable', 'string', 'max:2000'],
            'allowed_extensions' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(,[a-z0-9]+)*$/'],
            'max_size_kb'        => ['nullable', 'integer', 'min:100', 'max:10240'],
        ], [
            'options_text.required'      => 'Enter at least one choice (one per line) for a dropdown.',
            'allowed_extensions.regex'   => 'Use file types separated by commas, e.g. pdf, jpg, png.',
        ]);

        $isSelect = $data['type'] === 'select';
        $isFile   = $data['type'] === 'file';

        return [
            'label'              => $data['label'],
            'type'               => $data['type'],
            'is_required'        => $request->boolean('is_required'),
            'help_text'          => $data['help_text'] ?? null,
            'options'            => $isSelect
                ? collect(preg_split('/\r\n|\r|\n/', (string) ($data['options_text'] ?? '')))->map('trim')->filter()->unique()->values()->all()
                : null,
            'allowed_extensions' => $isFile ? ($data['allowed_extensions'] ?: null) : null,
            'max_size_kb'        => $isFile ? ((int) ($data['max_size_kb'] ?? 0) ?: 2048) : 2048,
        ];
    }

    private function back(string $levelId, ?string $message = null)
    {
        $redirect = redirect()->route('admissions.requirements.index', ['level' => $levelId]);

        return $message ? $redirect->with('adm_success', $message) : $redirect;
    }
}
