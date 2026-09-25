<form method="POST" action="{{ route('apply.save-step', 1) }}" novalidate>
    @csrf

    <h4 class="fw-bold mb-1">Student details</h4>
    <p class="text-muted mb-4">Tell us about the child who is applying. Fields marked <span class="text-danger">*</span> are required.</p>

    <div class="row g-3">
        <x-admissions.field name="first_name"  label="First name"  :value="$application->first_name"  required col="col-md-4" autocomplete="given-name" />
        <x-admissions.field name="middle_name" label="Middle name" :value="$application->middle_name" col="col-md-4" />
        <x-admissions.field name="last_name"   label="Last name"   :value="$application->last_name"   required col="col-md-4" autocomplete="family-name" />

        <x-admissions.field name="gender" label="Gender" type="select" :options="['male' => 'Male', 'female' => 'Female']" :value="$application->gender" required col="col-md-4" />
        <x-admissions.field name="date_of_birth" label="Date of birth" type="date" :value="$application->date_of_birth?->format('Y-m-d')" :max="now()->subDay()->format('Y-m-d')" required col="col-md-4" />
        <x-admissions.field name="citizenship" label="Nationality" :value="$application->citizenship ?? 'Kenyan'" required col="col-md-4" />

        <x-admissions.field name="birth_certificate_no" label="Birth certificate number" :value="$application->birth_certificate_no" col="col-md-6" />
        <x-admissions.field name="religion" label="Religion" :value="$application->religion" col="col-md-6" />
    </div>

    <div class="app-section-title">Where the student lives</div>
    <div class="row g-3">
        <x-admissions.field name="county"     label="County"     :value="$application->county"     col="col-md-4" />
        <x-admissions.field name="sub_county" label="Sub-county" :value="$application->sub_county" col="col-md-4" />
        <x-admissions.field name="ward"       label="Ward"       :value="$application->ward"       col="col-md-4" />
        <x-admissions.field name="home_address" label="Home address / estate" :value="$application->home_address" placeholder="e.g. Kitengela, Milimani Estate" />
    </div>

    <div class="app-section-title">Health &amp; support</div>
    <div class="row g-3">
        <x-admissions.field name="medical_conditions" label="Medical conditions or allergies" type="textarea" :value="$application->medical_conditions"
                            hint="Anything the school should know to keep the child safe. Leave blank if none." />
        <x-admissions.field name="special_needs" label="Special learning needs or support" type="textarea" :value="$application->special_needs"
                            hint="For example a learning difficulty or a disability that needs support." />
        <x-admissions.field name="student_email" label="Student's own email" type="email" :value="$application->student_email" col="col-md-6" hint="Optional — most young learners don't have one." />
    </div>

    @include('admissions.public.steps._nav', ['step' => 1])
</form>
