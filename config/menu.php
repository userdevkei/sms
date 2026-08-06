<?php

return [
    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'dashboard', 'permission' => null],

    ['label' => 'Admissions', 'icon' => 'bi-clipboard-check', 'permission' => 'admissions.view', 'children' => [
        ['label' => 'Applications', 'route' => 'admissions.index', 'permission' => 'admissions.view'],
        ['label' => 'New Application', 'route' => 'admissions.create', 'permission' => 'admissions.create'],
    ]],

    ['label' => 'Students', 'icon' => 'bi-people', 'permission' => 'students.view', 'children' => [
        ['label' => 'All Students', 'route' => 'students.index', 'permission' => 'students.view'],
        ['label' => 'Add Student', 'route' => 'students.create', 'permission' => 'students.manage'],
        ['label' => 'Import Students', 'route' => 'students.import.create', 'permission' => 'students.manage'],
    ]],

    ['label' => 'Curriculum', 'icon' => 'bi-mortarboard', 'permission' => 'curriculum.view', 'children' => [
        ['label' => 'Education Levels', 'route' => 'curriculum.education-levels.index', 'permission' => 'curriculum.view'],
        ['label' => 'Grade Levels', 'route' => 'curriculum.grade-levels.index', 'permission' => 'curriculum.view'],
        ['label' => 'Learning Areas', 'route' => 'curriculum.learning-areas.index', 'permission' => 'curriculum.view'],
        ['label' => 'Pathways (Senior Secondary)', 'route' => 'curriculum.pathways.index', 'permission' => 'curriculum.view'],
        ['label' => 'Streams / Classes', 'route' => 'curriculum.streams.index', 'permission' => 'curriculum.view'],
    ]],

    ['label' => 'Academic Terms', 'icon' => 'bi-calendar' , 'route' => 'curriculum.academic-terms.index', 'permission' => 'curriculum.view'],

    ['label' => 'Finance', 'icon' => 'bi-cash-coin', 'permission' => null, 'children' => [
        ['label' => 'Invoices', 'route' => 'finance.invoices.index', 'permission' => 'invoices.view'],
        ['label' => 'Student Transport', 'route' => 'finance.transport.student-route-stops.index', 'permission' => 'other_charges.view'],
        ['label' => 'Payments', 'route' => 'finance.payments.index', 'permission' => 'payments.view'],
        ['label' => 'Other Charges', 'route' => 'finance.other-charges.index', 'permission' => 'other_charges.view'],
        ['label' => 'Exemptions & Scholarships', 'route' => 'finance.exemptions.index', 'permission' => 'exemptions.view'],
        ['label' => 'Fee Structures', 'route' => 'finance.fee-structures.index', 'permission' => 'fee_structures.view'],
        ['label' => 'Vote heads', 'route' => 'finance.voteheads.index', 'permission' => 'fee_structures.view'],
        ['label' => 'Other Charge Types', 'route' => 'finance.other-charge-types.index', 'permission' => 'other_charges.view'],
        ['label' => 'Fee Statement', 'route' => 'finance.my-statement', 'permission' => 'my-statement.view'],
        ['label' => 'Payments', 'route' => 'finance.my-payments', 'permission' => 'my-payments.view'],
    ]],

    ['label' => 'Results', 'icon' => 'bi-journal-text', 'permission' => 'results.view', 'children' => [
        ['label' => 'Assessments', 'route' => 'results.assessments.index', 'permission' => 'results.view'],
        ['label' => 'Subject Teacher Assignments', 'route' => 'results.assignments.index', 'permission' => 'results.view'],
        ['label' => 'Assessment Types', 'route' => 'results.assessment-types.index', 'permission' => 'results.view'],
        ['label' => 'Report Cards', 'route' => 'results.report-cards.index', 'permission' => 'results.view'],
        ['label' => 'Grading Bands', 'route' => 'results.grading-bands.index', 'permission' => 'curriculum.view'],
        ['label' => 'My Results', 'route' => 'results.my-results.index', 'permission' => 'my-results.view'],
    ]],

    ['label' => 'Progression', 'icon' => 'bi-arrow-up-right-circle', 'route' => 'curriculum.progression.index', 'permission' => 'progression.view'],

    ['label' => 'Transport', 'icon' => 'bi-bus-front', 'permission' => 'transport.view', 'children' => [
        ['label' => 'Fleet (Vehicles)', 'route' => 'transport.vehicles.index', 'permission' => 'transport.view'],
        ['label' => 'Drivers', 'route' => 'transport.drivers.index', 'permission' => 'transport.view'],
        ['label' => 'Routes & Stops', 'route' => 'transport.transport-routes.index', 'permission' => 'transport.view'],
        ['label' => 'Vehicle/Driver Assignments', 'route' => 'transport.route-assignments.index', 'permission' => 'transport.view'],
    ]],
    ['label' => 'Accommodation', 'icon' => 'bi-building', 'permission' => 'accommodation.view', 'children' => [
        ['label' => 'Hostels', 'route' => 'accommodation.hostels.index', 'permission' => 'accommodation.view'],
        ['label' => 'Reservations', 'route' => 'accommodation.reservations.index', 'permission' => 'accommodation.view'],
        ['label' => 'Room Allocations', 'route' => 'accommodation.allocations.index', 'permission' => 'accommodation.view'],
    ]],

    ['label' => 'HR', 'icon' => 'bi-person-badge', 'route' => 'hr.index', 'permission' => 'hr.view'],
    ['label' => 'Accounting', 'icon' => 'bi-calculator', 'route' => 'accounting.index', 'permission' => 'accounting.view'],

    ['label' => 'Administration', 'icon' => 'bi-gear', 'permission' => 'admin.view', 'children' => [
        ['label' => 'Users', 'route' => 'users.index', 'permission' => 'users.view'],
        ['label' => 'Roles & Permissions', 'route' => 'roles.index', 'permission' => 'roles.manage'],
        ['label' => 'School Settings', 'route' => 'settings.index', 'permission' => 'settings.manage'],
    ]],
];
