<?php
namespace Fast\SisdikBundle\Util;

/**
 * Create monthly calendar
 */
class Calendar
{
    /**
     * @param  integer $theyear
     * @param  integer $themonth
     * @return array
     */
    public function createMonthlyCalendar($theyear = NULL, $themonth = NULL)
    {
        $now = time();

        if (!empty($theyear)) {
            $year = $theyear;
        } else {
            $year = date('Y', $now);
        }

        if (!empty($themonth)) {
            $month = $themonth;
        } else {
            $month = date('m', $now);
        }

        /*
         * want to start on sunday? use this array AND ( important! ) set $day_offset to 0 ( zero )
        * $days = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
        */
        $days = [
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            "Jum'at",
            'Sabtu',
            'Minggu',
        ];

        $months = [
            1 => 'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];

        // day offset, 1 is monday, 0 is sunday
        $day_offset = 1;

        $start_day = mktime(0, 0, 0, $month, 1, $year);
        $start_day_number = date('w', $start_day);
        $days_in_month = date('t', $start_day);
        $row = 0;
        $cal = [];
        $trow = 0;
        $blank_days = $start_day_number - $day_offset;

        if ($blank_days < 0) {
            $blank_days = 7 - abs($blank_days);
        }

        for ($x = 0; $x < $blank_days; $x++) {
            $cal[$row][$trow]['num'] = null;
            $trow++;
        }

        for ($x = 1; $x <= $days_in_month; $x++) {
            if (($x + $blank_days - 1) % 7 == 0) {
                $row++;
            }
            $cal[$row][$trow]['num'] = $x;
            $cal[$row][$trow]['ts'] = mktime(0, 0, 0, $month, $x, $year);
            if (($x + $blank_days) % 7 == 0) {
                $cal[$row][$trow]['off'] = 1;
            } else {
                $cal[$row][$trow]['off'] = null;
            }

            $trow++;
        }

        while ((($days_in_month + $blank_days) % 7) != 0) {
            $cal[$row][$trow]['num'] = null;
            $days_in_month++;
            $trow++;
        }

        return [
            'months' => $months,
            'days' => $days,
            'cal' => $cal,
            'month' => abs($month),
            'year' => $year,
        ];
    }
}
