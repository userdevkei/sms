<?php

namespace App\Http\Requests;

use App\Models\StudentRouteStop;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStudentRouteStopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('transport.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_ids'        => ['required', 'array', 'min:1'],
            'user_ids.*'      => ['required', 'string', 'exists:users,id', 'distinct'],
            'route_stop_id'   => ['required', 'string', 'exists:route_stops,id'],
            'academic_year'   => ['required', 'string', 'max:9'],
            'term'            => ['required', 'integer', 'in:1,2,3'],
        ];
    }

    /**
     * Checks each selected student individually against the double-booking
     * guard, rather than failing the whole submission on the first hit —
     * this lets the controller report exactly which students were already
     * assigned instead of blocking everyone because of one conflict.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $userIds = $this->input('user_ids', []);

            if (empty($userIds)) {
                return; // 'required'/'min:1' already covers this
            }

            $alreadyAssignedIds = StudentRouteStop::query()
                ->whereIn('user_id', $userIds)
                ->where('academic_year', $this->input('academic_year'))
                ->where('term', $this->input('term'))
                ->where('status', 'active')
                ->pluck('user_id');

            foreach ($alreadyAssignedIds as $index => $userId) {
                // Find the position in the submitted array so the error
                // attaches to the right field for that student.
                $position = array_search($userId, $userIds, true);
                $validator->errors()->add("user_ids.{$position}", 'This student already has an active route stop assignment for this term.');
            }
        });
    }
}
