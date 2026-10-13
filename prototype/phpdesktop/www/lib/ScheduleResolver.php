<?php
declare(strict_types=1);

/**
 * Resolves the schedule pattern for one local calendar date.
 *
 * Reference anchor: 17 August 1945 is verified as Friday Legi.
 * Special rules replace Normal rules only when exactly one special rule matches.
 * If multiple special rules match, use Normal as a safe fallback and report conflict.
 */
final class ScheduleResolver
{
    private const PASARAN_ANCHOR = '1945-08-17';
    private const PASARAN_SEQUENCE = ['Legi', 'Pahing', 'Pon', 'Wage', 'Kliwon'];

    public static function getPasaran(DateTimeImmutable $date): string
    {
        $timezone = $date->getTimezone();
        $target = new DateTimeImmutable($date->format('Y-m-d'), $timezone);
        $anchor = new DateTimeImmutable(self::PASARAN_ANCHOR, $timezone);
        $difference = (int) $anchor->diff($target)->format('%r%a');
        $index = (($difference % count(self::PASARAN_SEQUENCE)) + count(self::PASARAN_SEQUENCE))
            % count(self::PASARAN_SEQUENCE);

        return self::PASARAN_SEQUENCE[$index];
    }

    /** Return the ordinal occurrence of this date's weekday within the month. */
    private static function getMonthWeek(DateTimeImmutable $date): array
    {
        $day = (int) $date->format('j');
        $daysInMonth = (int) $date->format('t');
        $ordinal = (int) floor(($day - 1) / 7) + 1;
        $isLastOccurrence = ($day + 7) > $daysInMonth;

        return [
            'value' => $isLastOccurrence ? 'last' : (string) $ordinal,
            'ordinal' => $ordinal,
            'is_last' => $isLastOccurrence,
            'label' => $isLastOccurrence ? 'Minggu terakhir' : 'Minggu ke-' . $ordinal,
        ];
    }

    /**
     * @return array{
     *   date:string,weekday:int,pasaran:string,month_week:string,month_week_label:string,
     *   mode:string,rules:array,matching_special_rules:array,conflict:bool
     * }
     */
    public static function resolve(Database $db, int $packageId, DateTimeImmutable $date): array
    {
        $weekday = (int) $date->format('N');
        $pasaran = self::getPasaran($date);
        $monthWeek = self::getMonthWeek($date);
        $normalRules = [];
        $matchingSpecialRules = [];

        foreach ($db->getDayRules($packageId, $weekday) as $rule) {
            if ((int) $rule['is_enabled'] !== 1) {
                continue;
            }

            if ((string) $rule['rule_mode'] === 'normal') {
                $normalRules[] = $rule;
                continue;
            }

            $type = (string) ($rule['special_type'] ?? '');
            $value = (string) ($rule['special_value'] ?? '');
            $matches = ($type === 'pasaran' && $value === $pasaran)
                || ($type === 'month_week' && (
                    $value === (string) $monthWeek['ordinal']
                    || ($value === 'last' && $monthWeek['is_last'])
                ));

            if ($matches) {
                $matchingSpecialRules[] = $rule;
            }
        }

        $conflict = count($matchingSpecialRules) > 1;
        if (count($matchingSpecialRules) === 1) {
            $mode = 'special';
            $selectedRules = [$matchingSpecialRules[0]];
        } else {
            $mode = $conflict ? 'normal_fallback' : 'normal';
            $selectedRules = $normalRules;
        }

        return [
            'date' => $date->format('Y-m-d'),
            'weekday' => $weekday,
            'pasaran' => $pasaran,
            'month_week' => $monthWeek['value'],
            'month_week_label' => $monthWeek['label'],
            'mode' => $mode,
            'rules' => $selectedRules,
            'matching_special_rules' => $matchingSpecialRules,
            'conflict' => $conflict,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function getEnabledEvents(Database $db, array $resolution): array
    {
        $events = [];
        foreach (($resolution['rules'] ?? []) as $rule) {
            foreach ($db->getBellEvents((int) $rule['id']) as $event) {
                if ((int) $event['is_enabled'] !== 1) {
                    continue;
                }
                $event['resolved_rule_mode'] = (string) $rule['rule_mode'];
                $event['resolved_rule_id'] = (int) $rule['id'];
                $events[] = $event;
            }
        }

        usort($events, static function (array $a, array $b): int {
            $timeComparison = strcmp((string) $a['event_time'], (string) $b['event_time']);
            return $timeComparison !== 0
                ? $timeComparison
                : ((int) $a['id'] <=> (int) $b['id']);
        });

        return $events;
    }
}
