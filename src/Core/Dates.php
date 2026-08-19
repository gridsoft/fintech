<?php

declare(strict_types=1);

namespace App\Core;

use DateTime;

/**
 * Мали помошни функции за датуми — рокот на плаќање се внесува како број
 * денови (не датум), а датумите се прикажуваат во македонски формат (д.м.г).
 */
class Dates
{
    public static function addDays(string $date, int $days): string
    {
        $dt = new DateTime($date);
        $dt->modify(($days >= 0 ? '+' : '') . $days . ' days');

        return $dt->format('Y-m-d');
    }

    /** Апсолутен број денови помеѓу два датуми (ISO Y-m-d) — за прикажување стандардниот рок при уредување. */
    public static function daysBetween(string $fromDate, string $toDate): int
    {
        return (new DateTime($fromDate))->diff(new DateTime($toDate))->days;
    }

    /** Македонски формат за прикажување (не за <input type=date>, чија value мора да остане ISO): д.м.гггг. */
    public static function formatMk(?string $isoDate): string
    {
        if (!$isoDate) {
            return '—';
        }

        $dt = DateTime::createFromFormat('Y-m-d', $isoDate);

        return $dt ? $dt->format('d.m.Y') : $isoDate;
    }
}
