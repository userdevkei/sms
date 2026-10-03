<?php


namespace App\Support;

class AmountInWords
{
    private const ONES = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve',
        'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
    private const TENS = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

    public static function kes(float $amount): string
    {
        $shillings = (int)floor($amount + 0.00001);
        $cents = (int)round(($amount - $shillings) * 100);
        if ($cents >= 100) {
            $shillings++;
            $cents = 0;
        }

        $out = ucfirst(self::words($shillings)) . ' Kenya Shillings';
        if ($cents > 0) {
            $out .= ' and ' . self::words($cents) . ' cents';
        }

        return $out . ' only';
    }

    private static function words(int $n): string
    {
        if ($n === 0) return 'zero';
        if ($n < 20) return self::ONES[$n];
        if ($n < 100) return self::TENS[intdiv($n, 10)] . ($n % 10 ? '-' . self::ONES[$n % 10] : '');
        if ($n < 1000) return self::ONES[intdiv($n, 100)] . ' hundred' . ($n % 100 ? ' and ' . self::words($n % 100) : '');

        foreach ([1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand'] as $div => $name) {
            if ($n >= $div) {
                $rest = $n % $div;
                return self::words(intdiv($n, $div)) . ' ' . $name . ($rest ? ($rest < 100 ? ' and ' : ' ') . self::words($rest) : '');
            }
        }

        return '';
    }
}
