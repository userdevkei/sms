<?php

namespace App\Http\Controllers;

use App\Models\AdmissionAnswer;
use App\Models\AdmissionApplication;
use App\Models\AdmissionEvent;
use App\Models\AdmissionGuardian;
use App\Models\AdmissionLevelSetting;
use App\Models\AdmissionRequirement;
use App\Models\EducationLevel;
use App\Models\GradeLevel;
use App\Services\Admissions\AdmissionCodeService;
use App\Services\Admissions\AdmissionNotifier;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicAdmissionController extends Controller
{
    private const SESSION_KEY = 'admission_application_id';
    private const LAST_STEP   = 5;

    public function __construct(
        private AdmissionCodeService $codes,
        private AdmissionNotifier $notifier,
    ) {}

    /* ==================================================================
     | Landing: start / continue / lost code
     ================================================================== */
    public function landing(Request $request)
    {
        $resume = ($id = $request->session()->get(self::SESSION_KEY)) ? AdmissionApplication::find($id) : null;

        $levels = EducationLevel::whereIn('id', $this->openLevelIds())->orderBy('sequence')->get(['id', 'name']);

        return view('admissions.public.landing', compact('resume', 'levels'));
    }

    public function start(Request $request)
    {
        $request->merge(['contact_phone' => $this->normalizePhone($request->input('contact_phone'))]);

        $data = $request->validateWithBag('start', [
            'contact_name'  => ['required', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'required_without:contact_email', 'regex:/^254[17]\d{8}$/'],
            'contact_email' => ['nullable', 'required_without:contact_phone', 'email', 'max:150'],
        ], [
            'contact_phone.required_without' => 'Enter a phone number or an email address — we need one to send your continuation code.',
            'contact_email.required_without' => 'Enter an email address or a phone number — we need one to send your continuation code.',
            'contact_phone.regex'            => 'Enter a valid Kenyan mobile number, e.g. 0712 345 678.',
        ]);

        $application = AdmissionApplication::create([
            'reference'     => AdmissionApplication::generateReference(),
            'status'        => 'draft',
            'current_step'  => 1,
            'contact_name'  => $data['contact_name'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'contact_email' => isset($data['contact_email']) ? strtolower($data['contact_email']) : null,
        ]);

        $code = $this->codes->issue($application);
        $this->notifier->sendCode($application, $code);
        AdmissionEvent::record($application, 'started', 'Application started.');

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $application->id);

        // The plain code is flashed once so the applicant can copy it, even if SMS/email is slow.
        return redirect()->route('apply.wizard')->with('new_code', $code);
    }

    public function continueWithCode(Request $request)
    {
        $request->validateWithBag('continue', ['code' => ['required', 'string', 'max:30']]);

        $application = $this->codes->find($request->input('code'));

        if (! $application) {
            return back()->withInput()->withErrors(
                ['code' => "We couldn't find an application with that code. Check it and try again."],
                'continue'
            );
        }

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, $application->id);

        return redirect()->route($application->isEditable() ? 'apply.wizard' : 'apply.status');
    }

    public function resendCode(Request $request)
    {
        $data = $request->validateWithBag('resend', ['contact' => ['required', 'string', 'max:150']]);

        $contact = trim($data['contact']);
        $isEmail = str_contains($contact, '@');
        $phone   = $isEmail ? null : $this->normalizePhone($contact);

        $applications = AdmissionApplication::query()
            ->when(
                $isEmail,
                fn ($q) => $q->where('contact_email', strtolower($contact)),
                fn ($q) => $q->where('contact_phone', $phone)
            )
            ->latest()->limit(5)->get();

        foreach ($applications as $application) {
            $code = $this->codes->issue($application);   // rotates: the old code stops working
            $this->notifier->sendCode($application, $code, $isEmail ? 'email' : 'sms');
        }

        // Same answer whether or not anything matched, so this can't be used to find out who has applied.
        return redirect()->route('apply.landing')->with('resend_sent', true);
    }

    public function exit(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('apply.landing')
            ->with('info', 'You have left your application. Use your continuation code any time to come back.');
    }

    /* ==================================================================
     | The wizard
     ================================================================== */
    public function wizard(Request $request)
    {
        $application = $this->resolve($request);

        if (! $application->isEditable()) {
            return redirect()->route('apply.status');
        }

        $maxStep = min(max((int) $application->current_step, 1), self::LAST_STEP);
        $step    = min(max((int) $request->query('step', $maxStep), 1), $maxStep);

        $application->load('guardians');

        $data = [
            'application' => $application,
            'step'        => $step,
            'maxStep'     => $maxStep,
            'steps'       => AdmissionApplication::STEPS,
        ];

        if ($step === 2) {
            $data['guardianRows'] = $this->guardianRows($application);
        }

        if ($step === 3) {
            $data['gradeGroups'] = GradeLevel::where('status', 'active')
                ->whereIn('education_level_id', $this->openLevelIds())
                ->with('educationLevel')->orderBy('sequence')->get()
                ->groupBy(fn ($g) => $g->educationLevel?->name ?? 'Other');
            $data['years']       = $this->academicYears();
            $data['defaultYear'] = (string) (now()->month >= 9 ? now()->year + 1 : now()->year);
        }

        if ($step >= 4) {
            $data['requirements'] = $this->requirementsFor($application);
            $data['answers']      = $application->answers()->get()->keyBy('admission_requirement_id');
            $data['levelSetting'] = AdmissionLevelSetting::forLevel($application->education_level_id);
        }

        if ($step === 5) {
            $application->load('gradeLevel.educationLevel');
        }

        return view('admissions.public.wizard', $data);
    }

    public function saveStep(Request $request, int $step)
    {
        $application = $this->resolve($request);

        abort_unless($application->isEditable(), 403);
        abort_unless($step >= 1 && $step <= 4, 404);

        if ($step > max(1, (int) $application->current_step)) {
            return redirect()->route('apply.wizard');   // can't skip ahead
        }

        match ($step) {
            1 => $this->saveStudent($request, $application),
            2 => $this->saveGuardians($request, $application),
            3 => $this->saveGrade($request, $application),
            4 => $this->saveRequirements($request, $application),
        };

        $application->forceFill(['current_step' => max((int) $application->current_step, $step + 1)])->save();

        if ($request->input('action') === 'exit') {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('apply.landing')
                ->with('success', 'Your progress is saved. Use your continuation code any time to pick up where you left off.');
        }

        return redirect()->route('apply.wizard', ['step' => $step + 1]);
    }

    public function submit(Request $request)
    {
        $application = $this->resolve($request);

        abort_unless($application->isEditable(), 403);

        $request->validate(
            ['declaration' => ['accepted']],
            ['declaration.accepted' => 'Please confirm the declaration before submitting.']
        );

        // Re-check everything, so an incomplete application can never be submitted.
        if ($problem = $this->firstIncompleteStep($application)) {
            [$step, $message] = $problem;

            return redirect()->route('apply.wizard', ['step' => $step])->withErrors(['general' => $message]);
        }

        $resubmitted = $application->status === 'changes_requested';

        $application->forceFill([
            'status'       => 'submitted',
            'submitted_at' => now(),
            'current_step' => self::LAST_STEP,
        ])->save();

        AdmissionEvent::record(
            $application,
            $resubmitted ? 'resubmitted' : 'submitted',
            $resubmitted ? 'Application updated and resubmitted.' : 'Application submitted.'
        );

        $this->notifier->notify(
            $application,
            'We received your application',
            "We have received the application for {$application->full_name}. You will be notified as soon as it has been reviewed."
        );

        return redirect()->route('apply.status')->with('submitted', true);
    }

    /* ==================================================================
     | Status page (submitted and beyond)
     ================================================================== */
    public function status(Request $request)
    {
        $application = $this->resolve($request);

        $application->load(['guardians', 'gradeLevel.educationLevel', 'answers.requirement']);
        $events = $application->events()->where('is_public', true)->get();

        return view('admissions.public.status', compact('application', 'events'));
    }

    public function file(Request $request, AdmissionAnswer $answer)
    {
        $application = $this->resolve($request);

        abort_unless($answer->admission_application_id === $application->id && $answer->hasFile(), 404);

        return Storage::disk(AdmissionAnswer::DISK)->download($answer->file_path, $answer->file_name);
    }

    /* ==================================================================
     | Step savers
     ================================================================== */
    private function saveStudent(Request $request, AdmissionApplication $application): void
    {
        $data = $request->validate([
            'first_name'           => ['required', 'string', 'max:80'],
            'middle_name'          => ['nullable', 'string', 'max:80'],
            'last_name'            => ['required', 'string', 'max:80'],
            'gender'               => ['required', Rule::in(['male', 'female'])],
            'date_of_birth'        => ['required', 'date', 'before:today', 'after:1990-01-01'],
            'birth_certificate_no' => ['nullable', 'string', 'max:50'],
            'citizenship'          => ['required', 'string', 'max:60'],
            'religion'             => ['nullable', 'string', 'max:60'],
            'county'               => ['nullable', 'string', 'max:80'],
            'sub_county'           => ['nullable', 'string', 'max:80'],
            'ward'                 => ['nullable', 'string', 'max:80'],
            'home_address'         => ['nullable', 'string', 'max:255'],
            'student_email'        => ['nullable', 'email', 'max:150'],
            'special_needs'        => ['nullable', 'string', 'max:1000'],
            'medical_conditions'   => ['nullable', 'string', 'max:1000'],
        ]);

        $application->update($data);
    }

    private function saveGuardians(Request $request, AdmissionApplication $application): void
    {
        $rows = collect($request->input('guardians', []))
            ->map(fn ($g) => array_merge((array) $g, ['phone' => $this->normalizePhone($g['phone'] ?? null)]))
            ->all();
        $request->merge(['guardians' => $rows]);

        $fields = ['relationship', 'full_name', 'phone', 'email', 'id_number', 'occupation', 'address'];
        $rules  = [];

        foreach ([0, 1, 2] as $i) {
            foreach ($fields as $field) {
                // Row 0 must be complete; rows 1 and 2 only need to be complete if the person started filling them in.
                $others = collect($fields)->reject(fn ($f) => $f === $field)->map(fn ($f) => "guardians.$i.$f")->implode(',');
                $mustHave = in_array($field, ['relationship', 'full_name', 'phone'], true);

                $presence = $i === 0 && $mustHave
                    ? ['required']
                    : ($mustHave ? ['nullable', "required_with:$others"] : ['nullable']);

                $rules["guardians.$i.$field"] = array_merge($presence, match ($field) {
                    'relationship' => [Rule::in(array_keys(AdmissionGuardian::RELATIONSHIPS))],
                    'full_name'    => ['string', 'max:150'],
                    'phone'        => ['regex:/^254[17]\d{8}$/'],
                    'email'        => ['email', 'max:150'],
                    'id_number'    => ['string', 'max:30'],
                    'occupation'   => ['string', 'max:100'],
                    'address'      => ['string', 'max:255'],
                });
            }
        }

        $messages = [
            'guardians.*.phone.regex'  => 'Enter a valid Kenyan mobile number, e.g. 0712 345 678.',
            'guardians.*.*.required'   => 'This field is required.',
        ];

        $validated = $request->validate($rules, $messages);

        $application->guardians()->delete();

        foreach ($validated['guardians'] as $i => $g) {
            if (blank($g['full_name'] ?? null)) {
                continue;
            }

            AdmissionGuardian::create([
                'admission_application_id' => $application->id,
                'relationship' => $g['relationship'],
                'full_name'    => $g['full_name'],
                'id_number'    => $g['id_number'] ?? null,
                'phone'        => $g['phone'] ?? null,
                'email'        => $g['email'] ?? null,
                'occupation'   => $g['occupation'] ?? null,
                'address'      => $g['address'] ?? null,
                'is_primary'   => (int) $i === 0,
                'is_emergency' => (int) $i === 2,
            ]);
        }
    }

    private function saveGrade(Request $request, AdmissionApplication $application): void
    {
        $data = $request->validate([
            'academic_year'      => ['required', Rule::in($this->academicYears())],
            'grade_level_id'     => ['required', 'string', Rule::exists('grade_levels', 'id')->where('status', 'active')],
            'previous_school'    => ['nullable', 'string', 'max:255'],
            'previous_grade'     => ['nullable', 'string', 'max:60'],
            'reason_for_leaving' => ['nullable', 'string', 'max:500'],
            'sibling_details'    => [Rule::requiredIf($request->boolean('has_sibling_in_school')), 'nullable', 'string', 'max:255'],
        ], [
            'sibling_details.required' => 'Tell us the name and class of the sibling.',
        ]);

        $grade = GradeLevel::findOrFail($data['grade_level_id']);

        if (! in_array($grade->education_level_id, $this->openLevelIds(), true)) {
            throw ValidationException::withMessages(['grade_level_id' => 'Admissions are currently closed for this level.']);
        }

        $application->update($data + [
                'education_level_id'    => $grade->education_level_id,
                'has_sibling_in_school' => $request->boolean('has_sibling_in_school'),
                'needs_boarding'        => $request->boolean('needs_boarding'),
                'needs_transport'       => $request->boolean('needs_transport'),
            ]);

        // Switching to another education level changes the requirements — drop answers that no longer apply.
        $validIds = $this->requirementsFor($application)->pluck('id');
        $application->answers()->whereNotIn('admission_requirement_id', $validIds)->get()
            ->each(function (AdmissionAnswer $a) {
                $a->deleteFile();
                $a->delete();
            });

        $application->sibling_details = $application->has_sibling_in_school ? $application->sibling_details : null;
        $application->save();
    }

    private function saveRequirements(Request $request, AdmissionApplication $application): void
    {
        $requirements = $this->requirementsFor($application);
        $existing     = $application->answers()->get()->keyBy('admission_requirement_id');

        $rules = [];
        $names = [];
        foreach ($requirements as $req) {
            $rules["req.{$req->id}"] = $this->rulesFor($req, $existing->get($req->id));
            $names["req.{$req->id}"] = $req->label;
        }

        $request->validate($rules, [], $names);

        foreach ($requirements as $req) {
            $key    = "req.{$req->id}";
            $answer = $existing->get($req->id) ?? new AdmissionAnswer([
                'admission_application_id' => $application->id,
                'admission_requirement_id' => $req->id,
            ]);

            if ($req->isFile()) {
                if (! $request->hasFile($key)) {
                    continue;   // keep whatever was uploaded before
                }

                $file = $request->file($key);
                $answer->deleteFile();
                $answer->file_path = $file->storeAs(
                    "admissions/{$application->id}",
                    Str::random(24).'.'.strtolower($file->getClientOriginalExtension()),
                    AdmissionAnswer::DISK
                );
                $answer->file_name = $file->getClientOriginalName();
            } elseif ($req->type === 'checkbox') {
                $answer->value = $request->boolean($key) ? '1' : '0';
            } else {
                $answer->value = $request->input($key);
            }

            $answer->save();
        }
    }

    /** Validation rules for one dynamic requirement. */
    private function rulesFor(AdmissionRequirement $req, ?AdmissionAnswer $existing): array
    {
        if ($req->type === 'checkbox') {
            return $req->is_required ? ['accepted'] : ['nullable', 'boolean'];
        }

        if ($req->isFile()) {
            // A file uploaded earlier satisfies "required" when the applicant returns to this step.
            $presence = ($req->is_required && ! $existing?->hasFile()) ? ['required'] : ['nullable'];

            return array_merge($presence, ['file', 'mimes:'.implode(',', $req->extensions()), 'max:'.$req->max_size_kb]);
        }

        $presence = $req->is_required ? ['required'] : ['nullable'];

        return array_merge($presence, match ($req->type) {
            'textarea' => ['string', 'max:2000'],
            'number'   => ['numeric'],
            'date'     => ['date'],
            'select'   => [Rule::in($req->options ?? [])],
            default    => ['string', 'max:255'],
        });
    }

    /* ==================================================================
     | Helpers
     ================================================================== */
    /** @return array{0:int,1:string}|null the first step that still needs attention */
    private function firstIncompleteStep(AdmissionApplication $a): ?array
    {
        $a->load('guardians');

        foreach (['first_name', 'last_name', 'gender', 'date_of_birth', 'citizenship'] as $field) {
            if (blank($a->{$field})) {
                return [1, 'Please complete the student details.'];
            }
        }

        $primary = $a->primaryGuardian();
        if (! $primary || blank($primary->full_name) || blank($primary->phone) || blank($primary->relationship)) {
            return [2, 'Please add at least one parent or guardian with a phone number.'];
        }

        if (blank($a->grade_level_id) || blank($a->academic_year)) {
            return [3, 'Please choose the grade level you are applying for.'];
        }

        $answers = $a->answers()->get()->keyBy('admission_requirement_id');

        foreach ($this->requirementsFor($a) as $req) {
            if (! $req->is_required) {
                continue;
            }

            $answer = $answers->get($req->id);
            $done   = match (true) {
                $req->isFile()            => $answer && $answer->hasFile(),
                $req->type === 'checkbox' => $answer && $answer->value === '1',
                default                   => $answer && filled($answer->value),
            };

            if (! $done) {
                return [4, "\u{201C}{$req->label}\u{201D} is required."];
            }
        }

        return null;
    }

    private function resolve(Request $request): AdmissionApplication
    {
        $application = ($id = $request->session()->get(self::SESSION_KEY)) ? AdmissionApplication::find($id) : null;

        if (! $application) {
            throw new HttpResponseException(
                redirect()->route('apply.landing')
                    ->with('info', 'Your session ended. Enter your continuation code to pick up where you left off.')
            );
        }

        return $application;
    }

    private function requirementsFor(AdmissionApplication $application): Collection
    {
        if (! $application->education_level_id) {
            return collect();
        }

        return AdmissionRequirement::active()
            ->where('education_level_id', $application->education_level_id)
            ->orderBy('sort_order')->orderBy('created_at')->get();
    }

    /** Guardian rows for the form: 0 = primary, 1 = second guardian, 2 = emergency contact. */
    private function guardianRows(AdmissionApplication $application): array
    {
        $rows = [0 => [], 1 => [], 2 => []];

        foreach ($application->guardians as $g) {
            $rows[$g->is_primary ? 0 : ($g->is_emergency ? 2 : 1)] =
                $g->only(['relationship', 'full_name', 'id_number', 'phone', 'email', 'occupation', 'address']);
        }

        return $rows;
    }

    /** @return string[] education level ids currently open for applications */
    private function openLevelIds(): array
    {
        $closed = AdmissionLevelSetting::where('is_open', false)->pluck('education_level_id');

        return EducationLevel::where('status', 'active')->whereNotIn('id', $closed)->pluck('id')->all();
    }

    /** @return string[] */
    private function academicYears(): array
    {
        return [(string) now()->year, (string) (now()->year + 1)];
    }

    /** 0712 345 678 / +254712345678 / 712345678 → 254712345678 */
    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return match (true) {
            $digits === ''                 => null,
            str_starts_with($digits, '254') => $digits,
            str_starts_with($digits, '0')   => '254'.substr($digits, 1),
            strlen($digits) === 9           => '254'.$digits,
            default                         => $digits,
        };
    }
}
