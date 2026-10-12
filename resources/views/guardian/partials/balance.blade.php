@if($balance > 0.004)
    <span class="g-pill g-pill-due"><i class="bi bi-exclamation-circle"></i> Outstanding KES {{ number_format($balance, 2) }}</span>
@elseif($balance < -0.004)
    <span class="g-pill g-pill-ok"><i class="bi bi-check-circle"></i> Credit KES {{ number_format(abs($balance), 2) }}</span>
@else
    <span class="g-pill g-pill-ok"><i class="bi bi-check-circle"></i> Fully paid</span>
@endif
