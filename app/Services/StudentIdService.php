<?php
/**
 * StudentIdService - generates, formats, normalizes and validates the
 * permanent public Student ID in the format: [SchoolCode]-[Sequence]-[CheckDigit]
 * e.g. KFS-0000238-4
 */
final class StudentIdService
{
    /**
     * Normalize an entered student ID: remove spaces, hyphens, underscores,
     * and case-fold for comparison/validation.
     */
    public static function normalize(string $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $value) ?? '');
    }

    /** Compute a check digit for school code + sequence. */
    public static function checkDigit(string $schoolCode, int $sequence): int
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $schoolCode) ?? '')
            . str_pad((string)$sequence, 7, '0', STR_PAD_LEFT);

        $sum = 0;
        $len = strlen($base);
        for ($i = 0; $i < $len; $i++) {
            $ch = $base[$i];
            $val = ctype_digit($ch) ? (int)$ch : (ord($ch) - 55); // A=10, B=11...
            $sum += $val * ($i + 1);
        }
        return (10 - ($sum % 10)) % 10;
    }

    /** Generate the public Student ID for a school and sequence. */
    public static function generate(string $schoolCode, int $sequence): string
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $schoolCode) ?? '');
        $seq = str_pad((string)$sequence, 7, '0', STR_PAD_LEFT);
        $check = self::checkDigit($code, $sequence);
        return "{$code}-{$seq}-{$check}";
    }

    /** Validate that an entered ID has a correct check digit. Format: KFS-0000238-4 */
    public static function isValid(string $value): bool
    {
        $normalized = self::normalize($value);
        if (strlen($normalized) < 5) {
            return false;
        }
        // Parse trailing digit as check digit
        $check = (int)substr($normalized, -1);
        $body = substr($normalized, 0, -1);
        // body = school code + sequence digits; split last 7 digits as sequence
        $seqPart = substr($body, -7);
        if (!ctype_digit($seqPart)) {
            return false;
        }
        $seq = (int)$seqPart;
        $code = substr($body, 0, -7);
        return self::checkDigit($code, $seq) === $check;
    }
}
