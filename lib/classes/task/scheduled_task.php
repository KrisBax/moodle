<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Scheduled task abstract class.
 *
 * @package    core
 * @category   task
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\task;

/**
 * Abstract class defining a scheduled task.
 * @copyright  2013 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class scheduled_task extends task_base {

    /** Minimum minute value. */
    const MINUTEMIN = 0;
    /** Maximum minute value. */
    const MINUTEMAX = 59;

    /** Minimum hour value. */
    const HOURMIN = 0;
    /** Maximum hour value. */
    const HOURMAX = 23;

    /** Minimum day of month value. */
    const DAYMIN = 1;
    /** Maximum day of month value. */
    const DAYMAX = 31;

    /** Minimum month value. */
    const MONTHMIN = 1;
    /** Maximum month value. */
    const MONTHMAX = 12;

    /** Minimum dayofweek value. */
    const DAYOFWEEKMIN = 0;
    /** Maximum dayofweek value. */
    const DAYOFWEEKMAX = 6;
    /** Maximum dayofweek value allowed in input (7 = 0). */
    const DAYOFWEEKMAXINPUT = 7;

    /**
     * Minute field identifier.
     */
    const FIELD_MINUTE = 'minute';
    /**
     * Hour field identifier.
     */
    const FIELD_HOUR = 'hour';
    /**
     * Day-of-month field identifier.
     */
    const FIELD_DAY = 'day';
    /**
     * Month field identifier.
     */
    const FIELD_MONTH = 'month';
    /**
     * Day-of-week field identifier.
     */
    const FIELD_DAYOFWEEK = 'dayofweek';

    /**
     * Time used for the next scheduled time when a task should never run. This is 2222-01-01 00:00 GMT
     * which is a large time that still fits in 10 digits.
     */
    const NEVER_RUN_TIME = 7952342400;

    /** @var string $hour - Pattern to work out the valid hours */
    private $hour = '*';

    /** @var string $minute - Pattern to work out the valid minutes */
    private $minute = '*';

    /** @var string $day - Pattern to work out the valid days */
    private $day = '*';

    /** @var string $month - Pattern to work out the valid months */
    private $month = '*';

    /** @var string $dayofweek - Pattern to work out the valid dayofweek */
    private $dayofweek = '*';

    /** @var int $lastruntime - When this task was last run */
    private $lastruntime = 0;

    /** @var boolean $customised - Has this task been changed from it's default schedule? */
    private $customised = false;

    /** @var boolean $overridden - Does the task have values set VIA config? */
    private $overridden = false;

    /** @var int $disabled - Is this task disabled in cron? */
    private $disabled = false;

    /**
     * Get the last run time for this scheduled task.
     *
     * @return int
     */
    public function get_last_run_time() {
        return $this->lastruntime;
    }

    /**
     * Set the last run time for this scheduled task.
     *
     * @param int $lastruntime
     */
    public function set_last_run_time($lastruntime) {
        $this->lastruntime = $lastruntime;
    }

    /**
     * Has this task been changed from it's default config?
     *
     * @return bool
     */
    public function is_customised() {
        return $this->customised;
    }

    /**
     * Set customised for this scheduled task.
     *
     * @param bool
     */
    public function set_customised($customised) {
        $this->customised = $customised;
    }

    /**
     * Determine if this task is using its default configuration changed from the default. Returns true
     * if it is and false otherwise. Does not rely on the customised field.
     *
     * @return bool
     */
    public function has_default_configuration(): bool {
        $defaulttask = \core\task\manager::get_default_scheduled_task($this::class);
        if ($defaulttask->get_minute() !== $this->get_minute()) {
            return false;
        }
        if ($defaulttask->get_hour() != $this->get_hour()) {
            return false;
        }
        if ($defaulttask->get_month() != $this->get_month()) {
            return false;
        }
        if ($defaulttask->get_day_of_week() != $this->get_day_of_week()) {
            return false;
        }
        if ($defaulttask->get_day() != $this->get_day()) {
            return false;
        }
        if ($defaulttask->get_disabled() != $this->get_disabled()) {
            return false;
        }
        return true;
    }

    /**
     * Disable the task.
     */
    public function disable(): void {
        $this->set_disabled(true);
        $this->set_customised(!$this->has_default_configuration());
        \core\task\manager::configure_scheduled_task($this);
    }

    /**
     * Enable the task.
     */
    public function enable(): void {
        $this->set_disabled(false);
        $this->set_customised(!$this->has_default_configuration());
        \core\task\manager::configure_scheduled_task($this);
    }

    /**
     * Has this task been changed from it's default config?
     *
     * @return bool
     */
    public function is_overridden(): bool {
        return $this->overridden;
    }

    /**
     * Set the overridden value.
     *
     * @param bool $overridden
     */
    public function set_overridden(bool $overridden): void {
        $this->overridden = $overridden;
    }

    /**
     * Setter for $minute. Accepts a special 'R' value
     * which will be translated to a random minute.
     *
     * @param string $minute
     * @param bool $expandr - if true (default) an 'R' value in a time is expanded to an appropriate int.
     *      If false, they are left as 'R'
     */
    public function set_minute($minute, $expandr = true) {
        if ($minute === 'R' && $expandr) {
            $minute = mt_rand(self::MINUTEMIN, self::MINUTEMAX);
        }
        $this->minute = $minute;
    }

    /**
     * Getter for $minute.
     *
     * @return string
     */
    public function get_minute() {
        return $this->minute;
    }

    /**
     * Setter for $hour. Accepts a special 'R' value
     * which will be translated to a random hour.
     *
     * @param string $hour
     * @param bool $expandr - if true (default) an 'R' value in a time is expanded to an appropriate int.
     *      If false, they are left as 'R'
     */
    public function set_hour($hour, $expandr = true) {
        if ($hour === 'R' && $expandr) {
            $hour = mt_rand(self::HOURMIN, self::HOURMAX);
        }
        $this->hour = $hour;
    }

    /**
     * Getter for $hour.
     *
     * @return string
     */
    public function get_hour() {
        return $this->hour;
    }

    /**
     * Setter for $month.
     *
     * @param string $month
     */
    public function set_month($month) {
        $this->month = $month;
    }

    /**
     * Getter for $month.
     *
     * @return string
     */
    public function get_month() {
        return $this->month;
    }

    /**
     * Setter for $day.
     *
     * @param string $day
     */
    public function set_day($day) {
        $this->day = $day;
    }

    /**
     * Getter for $day.
     *
     * @return string
     */
    public function get_day() {
        return $this->day;
    }

    /**
     * Setter for $dayofweek.
     *
     * @param string $dayofweek
     * @param bool $expandr - if true (default) an 'R' value in a time is expanded to an appropriate int.
     *      If false, they are left as 'R'
     */
    public function set_day_of_week($dayofweek, $expandr = true) {
        if ($dayofweek === 'R' && $expandr) {
            $dayofweek = mt_rand(self::DAYOFWEEKMIN, self::DAYOFWEEKMAX);
        }
        $this->dayofweek = $dayofweek;
    }

    /**
     * Getter for $dayofweek.
     *
     * @return string
     */
    public function get_day_of_week() {
        return $this->dayofweek;
    }

    /**
     * Setter for $disabled.
     *
     * @param bool $disabled
     */
    public function set_disabled($disabled) {
        $this->disabled = (bool)$disabled;
    }

    /**
     * Getter for $disabled.
     * @return bool
     */
    public function get_disabled() {
        return $this->disabled;
    }

    /**
     * Override this function if you want this scheduled task to run, even if the component is disabled.
     *
     * @return bool
     */
    public function get_run_if_component_disabled() {
        return false;
    }

    /**
     * Informs whether the given field is valid.
     * Use the constants FIELD_* to identify the field.
     * Have to be called after the method set_{field}(string).
     *
     * @param string $field field identifier; expected values from constants FIELD_*.
     *
     * @return bool true if given field is valid. false otherwise.
     */
    public function is_valid(string $field): bool {
        return !empty($this->get_valid($field));
    }

    /**
     * Calculates the list of valid values according to the given field and stored expression.
     *
     * @param string $field field identifier. Must be one of those FIELD_*.
     *
     * @return array(int) list of matching values.
     *
     * @throws \coding_exception when passed an invalid field identifier.
     */
    private function get_valid(string $field): array {
        switch($field) {
            case self::FIELD_MINUTE:
                $min = self::MINUTEMIN;
                $max = self::MINUTEMAX;
                break;
            case self::FIELD_HOUR:
                $min = self::HOURMIN;
                $max = self::HOURMAX;
                break;
            case self::FIELD_DAY:
                $min = self::DAYMIN;
                $max = self::DAYMAX;
                break;
            case self::FIELD_MONTH:
                $min = self::MONTHMIN;
                $max = self::MONTHMAX;
                break;
            case self::FIELD_DAYOFWEEK:
                $min = self::DAYOFWEEKMIN;
                $max = self::DAYOFWEEKMAXINPUT;
                break;
            default:
                throw new \coding_exception("Field '$field' is not a valid crontab identifier.");
        }

        $result = $this->eval_cron_field($this->{$field}, $min, $max);
        if ($field === self::FIELD_DAYOFWEEK) {
            // For day of week, 0 and 7 both mean Sunday; if there is a 7 we set 0. The result array is sorted.
            if (end($result) === 7) {
                // Remove last element.
                array_pop($result);
                // Insert 0 as first element if it's not there already.
                if (reset($result) !== 0) {
                    array_unshift($result, 0);
                }
            }
        }
        return $result;
    }

    /**
     * Take a cron field definition and return an array of valid numbers with the range min-max.
     *
     * @param string $field - The field definition.
     * @param int $min - The minimum allowable value.
     * @param int $max - The maximum allowable value.
     * @return array(int)
     */
    public function eval_cron_field($field, $min, $max) {
        // Cleanse the input.
        $field = trim($field);

        // Format for a field is:
        // <fieldlist> := <range>(/<step>)(,<fieldlist>)
        // <step>  := int
        // <range> := <any>|<int>|<min-max>
        // <any>   := *
        // <min-max> := int-int
        // End of format BNF.

        // This function is complicated but is covered by unit tests.
        $range = array();

        $matches = array();
        preg_match_all('@[0-9]+|\*|,|/|-@', $field, $matches);

        $last = 0;
        $inrange = false;
        $instep = false;
        foreach ($matches[0] as $match) {
            if ($match == '*') {
                array_push($range, range($min, $max));
            } else if ($match == '/') {
                $instep = true;
            } else if ($match == '-') {
                $inrange = true;
            } else if (is_numeric($match)) {
                if ($min > $match || $match > $max) {
                    // This is a value error: The value lays out of the expected range of values.
                    return [];
                }
                if ($instep) {
                    // Normalise range property, account for "5/10".
                    $insteprange = $range[count($range) - 1];
                    if (!is_array($insteprange)) {
                        $range[count($range) - 1] = range($insteprange, $max);
                    }
                    for ($i = 0; $i < count($range[count($range) - 1]); $i++) {
                        if (($i) % $match != 0) {
                            $range[count($range) - 1][$i] = -1;
                        }
                    }
                    $instep = false;
                } else if ($inrange) {
                    if (count($range)) {
                        $range[count($range) - 1] = range($last, $match);
                    }
                    $inrange = false;
                } else {
                    array_push($range, $match);
                    $last = $match;
                }
            }
        }

        // If inrange or instep were not processed, there is a syntax error.
        // Cleanup any existing values to show up the error.
        if ($inrange || $instep) {
            return [];
        }

        // Flatten the result.
        $result = array();
        foreach ($range as $r) {
            if (is_array($r)) {
                foreach ($r as $rr) {
                    if ($rr >= $min && $rr <= $max) {
                        $result[$rr] = 1;
                    }
                }
            } else if (is_numeric($r)) {
                if ($r >= $min && $r <= $max) {
                    $result[$r] = 1;
                }
            }
        }
        $result = array_keys($result);
        sort($result, SORT_NUMERIC);
        return $result;
    }

    /**
     * Assuming $list is an ordered list of items, this function returns the item
     * in the list that is greater than or equal to the current value (or 0). If
     * no value is greater than or equal, this will return the first valid item in the list.
     * If list is empty, this function will return 0.
     *
     * @param int $current The current value
     * @param int[] $list The list of valid items.
     * @return int $next.
     */
    private function next_in_list($current, $list) {
        foreach ($list as $l) {
            if ($l >= $current) {
                return $l;
            }
        }
        if (count($list)) {
            return $list[0];
        }

        return 0;
    }

    /**
     * Calculate when this task should next be run based on the schedule.
     *
     * @param int $now Current time, for testing (leave 0 to use default time)
     * @return int $nextruntime.
     */
    public function get_next_scheduled_time(int $now = 0): int {
        if (!$now) {
            $now = time();
        }

        // We need to change to the server timezone before using php date() functions.
        \core_date::set_default_server_timezone();

        $validminutes = $this->get_valid(self::FIELD_MINUTE);
        $validhours = $this->get_valid(self::FIELD_HOUR);
        $validdays = $this->get_valid(self::FIELD_DAY);
        $validdaysofweek = $this->get_valid(self::FIELD_DAYOFWEEK);
        $validmonths = $this->get_valid(self::FIELD_MONTH);

        // If any of the fields contain no valid data then the task will never run.
        if (!$validminutes || !$validhours || !$validdays || !$validdaysofweek || !$validmonths) {
            return self::NEVER_RUN_TIME;
        }

        $result = self::get_next_scheduled_time_inner($now, $validminutes, $validhours, $validdays, $validdaysofweek, $validmonths);
        return $result;
    }

    /**
     * Recursively calculate the next valid time for this task.
     *
     * @param int $now Start time
     * @param array $validminutes Valid minutes
     * @param array $validhours Valid hours
     * @param array $validdays Valid days
     * @param array $validdaysofweek Valid days of week
     * @param array $validmonths Valid months
     * @param int $originalyear Zero for first call, original year for recursive calls
     * @return int Next run time
     */
    protected function get_next_scheduled_time_inner(int $now, array $validminutes, array $validhours,
            array $validdays, array $validdaysofweek, array $validmonths, int $originalyear = 0) {
        $currentyear = (int)date('Y', $now);
        if ($originalyear) {
            // In recursive calls, check we didn't go more than 8 years ahead, that indicates the
            // user has chosen an impossible date. 8 years is the maximum time, considering a task
            // set to run on 29 February over a century boundary when a leap year is skipped.
            if ($currentyear - $originalyear > 8) {
                // Use this time if it's never going to happen.
                return self::NEVER_RUN_TIME;
            }
            $firstyear = $originalyear;
        } else {
            $firstyear = $currentyear;
        }
        $currentmonth = (int)date('n', $now);

        // Evaluate month first.
        $nextvalidmonth = $this->next_in_list($currentmonth, $validmonths);
        if ($nextvalidmonth < $currentmonth) {
            $currentyear += 1;
        }
        // If we moved to another month, set the current time to start of month, and restart calculations.
        if ($nextvalidmonth !== $currentmonth) {
            $newtime = strtotime($currentyear . '-' . $nextvalidmonth . '-01 00:00');
            return $this->get_next_scheduled_time_inner($newtime, $validminutes, $validhours, $validdays,
                    $validdaysofweek, $validmonths, $firstyear);
        }

        // Special handling for dayofmonth vs dayofweek (see man 5 cron). If both are specified, then
        // it is ok to continue when either matches. If only one is specified then it must match.
        $currentday = (int)date("j", $now);
        $currentdayofweek = (int)date("w", $now);
        $nextvaliddayofmonth = self::next_in_list($currentday, $validdays);
        $nextvaliddayofweek = self::next_in_list($currentdayofweek, $validdaysofweek);
        $daysincrementbymonth = $nextvaliddayofmonth - $currentday;
        $daysinmonth = (int)date('t', $now);
        if ($nextvaliddayofmonth < $currentday) {
            $daysincrementbymonth += $daysinmonth;
        }

        $daysincrementbyweek = $nextvaliddayofweek - $currentdayofweek;
        if ($nextvaliddayofweek < $currentdayofweek) {
            $daysincrementbyweek += 7;
        }

        if ($this->dayofweek == '*') {
            $daysincrement = $daysincrementbymonth;
        } else if ($this->day == '*') {
            $daysincrement = $daysincrementbyweek;
        } else {
            // Take the smaller increment of days by month or week.
            $daysincrement = min($daysincrementbymonth, $daysincrementbyweek);
        }

        // If we moved day, recurse using new start time.
        if ($daysincrement != 0) {
            $newtime = strtotime($currentyear . '-' . $currentmonth . '-' . $currentday .
                    ' 00:00 +' . $daysincrement . ' days');
            return $this->get_next_scheduled_time_inner($newtime, $validminutes, $validhours, $validdays,
                    $validdaysofweek, $validmonths, $firstyear);
        }

        $currenthour = (int)date('H', $now);
        $nextvalidhour = $this->next_in_list($currenthour, $validhours);
        if ($nextvalidhour != $currenthour) {
            $keepcurrent = false;
            $currentdate = new \DateTimeImmutable($currentyear . '-' . $currentmonth . '-' . $currentday . ' ' . $currenthour .
                ':00');
            $lasthour = (int)date('H', $currentdate->sub(new \DateInterval('PT1S'))->getTimestamp());
            $nextafterlast = $this->next_in_list($lasthour, $validhours);
            // Special case for when the clocks go forward. If the next scheduled time would fall in an hour
            // that doesn't exist due to the clock change, then we use the next existing hour so that we don't
            // skip a run. However, the replacement hour may not appear in the valid hours list, so we check
            // whether we skipped a valid hour here to avoid recursing again and skipping its replacement.
            if (($lasthour + 1) % 24 <= $nextafterlast && $nextafterlast < $currenthour) {
                $keepcurrent = true;
            }
            if (!$keepcurrent) {
                if ($nextvalidhour < $currenthour) {
                    $offset = ' +1 day';
                } else {
                    $offset = '';
                }
                $newtime = strtotime($currentyear . '-' . $currentmonth . '-' . $currentday . ' ' . $nextvalidhour .
                    ':00' . $offset);
                return $this->get_next_scheduled_time_inner($newtime, $validminutes, $validhours, $validdays,
                    $validdaysofweek, $validmonths, $firstyear);
            }
        }

        // Round time down to an exact minute because we need to use numeric calculations on it now.
        // If we construct times based on all the components, it will mess up around DST changes
        // (because there are two times with the same representation).
        $now = intdiv($now, 60) * 60;

        $currentminute = (int)date('i', $now);
        $nextvalidminute = $this->next_in_list($currentminute, $validminutes);
        if ($nextvalidminute == $currentminute && !$originalyear) {
            // This is not a recursive call so time has not moved on at all yet. We can't use the
            // same minute as now because it has already happened, it has to be at least one minute
            // later, so update time and retry.
            $newtime = $now + 60;
            return $this->get_next_scheduled_time_inner($newtime, $validminutes, $validhours, $validdays,
                $validdaysofweek, $validmonths, $firstyear);
        }

        if ($nextvalidminute < $currentminute) {
            // The time is in the next hour so we need to recurse. Don't use strtotime at this
            // point because it will mess up around DST changes.
            $minutesforward = $nextvalidminute + 60 - $currentminute;
            $newtime = $now + $minutesforward * 60;
            return $this->get_next_scheduled_time_inner($newtime, $validminutes, $validhours, $validdays,
                $validdaysofweek, $validmonths, $firstyear);
        }

        // The next valid minute is in the same hour so it must be valid according to all other
        // checks and we can finally return it.
        return $now + ($nextvalidminute - $currentminute) * 60;
    }

    /**
     * Informs whether this task can be run.
     *
     * @return bool true when this task can be run. false otherwise.
     */
    public function can_run(): bool {
        return $this->is_component_enabled() || $this->get_run_if_component_disabled();
    }

    /**
     * Checks whether the component and the task disabled flag enables to run this task.
     * This do not checks whether the task manager allows running them or if the
     * site allows tasks to "run now".
     *
     * @return bool true if task is enabled. false otherwise.
     */
    public function is_enabled(): bool {
        return $this->can_run() && !$this->get_disabled();
    }

    /**
     * Produces a valid id string to use as id attribute based on the given FQCN class name.
     *
     * @param string $classname FQCN of a task.
     * @return string valid string to be used as id attribute.
     */
    public static function get_html_id(string $classname): string {
        return str_replace('\\', '-', ltrim($classname, '\\'));
    }
}
