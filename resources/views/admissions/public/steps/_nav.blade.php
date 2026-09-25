{{-- Back / Save & exit / Save & continue. The primary button comes first in the DOM so Enter submits "continue". --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4 pt-3 border-top">
    @if ($step > 1)
        <a href="{{ route('apply.wizard', ['step' => $step - 1]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    @else
        <span></span>
    @endif

    <div class="d-flex gap-2 flex-row-reverse">
        <button type="submit" name="action" value="next" class="btn btn-sm btn-primary">Save &amp; continue <i class="bi bi-arrow-right ms-1"></i></button>
        <button type="submit" name="action" value="exit" class="btn btn-sm btn-link text-muted text-decoration-none">Save &amp; exit</button>
    </div>
</div>
