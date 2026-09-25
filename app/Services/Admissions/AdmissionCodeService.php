<?php

namespace App\Services\Admissions;

use App\Models\AdmissionApplication;

/**
 * Continuation codes are the applicant's only credential (there is no login).
 * They are 10 characters from an unambiguous 31-character alphabet (~50 bits),
 * stored only as a keyed hash, and shown/sent to the applicant once per issue.
 */
class AdmissionCodeService
{
    // No 0/O, 1/I/L — codes get read out of SMS messages and typed on phones.
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        do {
            $raw = '';
            for ($i = 0; $i < 10; $i++) {
                $raw .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $code = substr($raw, 0, 5).'-'.substr($raw, 5);
        } while (AdmissionApplication::withTrashed()->where('code_hash', $this->hash($code))->exists());

        return $code;
    }

    public function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    /** "k7q3z 9pxlm", "K7Q3Z9PXLM", "k7q3z-9pxlm" → "K7Q3Z-9PXLM" */
    public function normalize(string $input): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));

        return strlen($clean) === 10 ? substr($clean, 0, 5).'-'.substr($clean, 5) : $clean;
    }

    public function find(string $input): ?AdmissionApplication
    {
        $code = $this->normalize($input);

        if (strlen($code) !== 11) {
            return null;
        }

        return AdmissionApplication::where('code_hash', $this->hash($code))->first();
    }

    /** Issue (or rotate) the code for an application. Returns the plain code — the only time it exists. */
    public function issue(AdmissionApplication $application): string
    {
        $code = $this->generate();
        $application->forceFill(['code_hash' => $this->hash($code)])->save();

        return $code;
    }
}
