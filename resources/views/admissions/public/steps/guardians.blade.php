@php
    $boxes = [
        0 => ['Primary parent / guardian', 'We contact this person about the application. Required.', true],
        1 => ['Second parent / guardian', 'Optional.', false],
        2 => ['Emergency contact', 'Someone we can call if the parents cannot be reached. Optional.', false],
    ];
@endphp

<form method="POST" action="{{ route('apply.save-step', 2) }}" novalidate>
    @csrf

    <h4 class="fw-bold mb-1">Parent / guardian details</h4>
    <p class="text-muted mb-4">Who is responsible for the student?</p>

    @foreach ($boxes as $i => [$title, $subtitle, $primary])
        @php $row = $guardianRows[$i] ?? []; @endphp

        <div class="border rounded-4 p-3 p-md-4 mb-3" style="border-color: var(--app-border) !important;">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge rounded-pill {{ $primary ? 'text-bg-primary' : 'text-bg-light border' }}">{{ $i + 1 }}</span>
                <h6 class="fw-bold mb-0">{{ $title }}</h6>
            </div>
            <div class="text-muted small mb-3">{{ $subtitle }}</div>

            <div class="row g-3">
                <x-admissions.field :name="'guardians['.$i.'][relationship]'" label="Relationship" type="select"
                                    :options="\App\Models\AdmissionGuardian::RELATIONSHIPS" :value="$row['relationship'] ?? null" :required="$primary" col="col-md-4" />
                <x-admissions.field :name="'guardians['.$i.'][full_name]'" label="Full name" :value="$row['full_name'] ?? null" :required="$primary" col="col-md-8" />
                <x-admissions.field :name="'guardians['.$i.'][phone]'" label="Phone" type="tel" :value="$row['phone'] ?? null" :required="$primary" placeholder="0712 345 678" col="col-md-6" inputmode="tel" />
                <x-admissions.field :name="'guardians['.$i.'][email]'" label="Email" type="email" :value="$row['email'] ?? null" col="col-md-6" />
                <x-admissions.field :name="'guardians['.$i.'][id_number]'" label="ID / passport number" :value="$row['id_number'] ?? null" col="col-md-4" />
                <x-admissions.field :name="'guardians['.$i.'][occupation]'" label="Occupation" :value="$row['occupation'] ?? null" col="col-md-4" />
                <x-admissions.field :name="'guardians['.$i.'][address]'" label="Address" :value="$row['address'] ?? null" col="col-md-4" />
            </div>
        </div>
    @endforeach

    @include('admissions.public.steps._nav', ['step' => 2])
</form>
