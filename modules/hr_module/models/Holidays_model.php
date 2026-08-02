<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Holidays_model extends App_Model
{
    private $tbl;

    public function __construct()
    {
        parent::__construct();
        $this->tbl = db_prefix() . 'hr_holidays';
        $this->_ensure_table();
    }

    private function _ensure_table()
    {
        if (!$this->db->table_exists($this->tbl)) {
            $this->db->query('CREATE TABLE `' . $this->tbl . '` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(191) NOT NULL,
              `holiday_date` date NOT NULL,
              `type` enum(\'government\',\'company\') NOT NULL DEFAULT \'government\',
              `year` int(4) NOT NULL,
              `created_by` int(11) DEFAULT NULL,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_year` (`year`),
              KEY `idx_date` (`holiday_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ';');
        }

        // Tracks the last time the day-before announcement was actually sent
        // (automated cron or the manual "Send Announcement" button) - null
        // means it has never gone out for this holiday.
        $col = $this->db->query("SHOW COLUMNS FROM `" . $this->tbl . "` LIKE 'announcement_sent_at'")->num_rows();
        if ($col === 0) {
            $this->db->query("ALTER TABLE `" . $this->tbl . "` ADD COLUMN `announcement_sent_at` DATETIME DEFAULT NULL AFTER `type`");
        }

        // Optional range end - null means a single-day holiday (the original
        // behavior); when set, `holiday_date`..`end_date` (inclusive) are all
        // part of the same holiday event, e.g. a multi-day Eid period entered
        // as one row instead of one row per day.
        $col = $this->db->query("SHOW COLUMNS FROM `" . $this->tbl . "` LIKE 'end_date'")->num_rows();
        if ($col === 0) {
            $this->db->query("ALTER TABLE `" . $this->tbl . "` ADD COLUMN `end_date` DATE DEFAULT NULL AFTER `holiday_date`");
        }
    }

    // The last day of this holiday's range - itself, for a single-day holiday.
    private function _effective_end($row)
    {
        return $row->end_date ?: $row->holiday_date;
    }

    public function mark_announcement_sent($id)
    {
        $this->db->where('id', $id)->update($this->tbl, ['announcement_sent_at' => date('Y-m-d H:i:s')]);
    }

    // {date} placeholder value for announcement messages - a single formatted
    // date for a single-day holiday, or "start - end" for a multi-day range.
    public function date_label($holiday)
    {
        $end = $this->_effective_end($holiday);
        if ($end === $holiday->holiday_date) {
            return _d($holiday->holiday_date);
        }
        return _d($holiday->holiday_date) . ' - ' . _d($end);
    }

    // {day_name} placeholder value - same single-day/range logic as date_label().
    public function day_name_label($holiday)
    {
        $end = $this->_effective_end($holiday);
        if ($end === $holiday->holiday_date) {
            return date('l', strtotime($holiday->holiday_date));
        }
        return date('l', strtotime($holiday->holiday_date)) . ' - ' . date('l', strtotime($end));
    }

    public function get_all($year = null)
    {
        if (!$year) $year = (int) date('Y');
        return $this->db->where('year', $year)
            ->order_by('holiday_date', 'ASC')
            ->get($this->tbl)->result();
    }

    public function get($id)
    {
        return $this->db->where('id', $id)->get($this->tbl)->row();
    }

    public function get_dates_in_range($from_date, $to_date)
    {
        return array_keys($this->get_holiday_names_in_range($from_date, $to_date));
    }

    // Expands every holiday whose range overlaps [$from_date, $to_date] into
    // one date => name entry per day, clipped to the requested window - so a
    // multi-day holiday (e.g. a 6-day Eid range stored as a single row) still
    // marks every one of its days on the calendar/leave-day calculations, the
    // same as if it had been entered as separate single-day rows.
    public function get_holiday_names_in_range($from_date, $to_date)
    {
        // holiday_date <= $to_date is a safe necessary condition (a range can't
        // overlap a window that ends before it even starts) - the symmetric
        // "ends after $from_date" check is done in PHP below since it depends
        // on the optional end_date column.
        $rows = $this->db->where('holiday_date <=', $to_date)
            ->get($this->tbl)->result();
        $map = [];
        foreach ($rows as $row) {
            $row_end = $this->_effective_end($row);
            if ($row_end < $from_date) continue;
            $clip_from = max($row->holiday_date, $from_date);
            $clip_to   = min($row_end, $to_date);
            for ($ts = strtotime($clip_from); $ts <= strtotime($clip_to); $ts += 86400) {
                $map[date('Y-m-d', $ts)] = $row->name;
            }
        }
        return $map;
    }

    // Is $date any day within a holiday's range (start..end inclusive)? Used
    // for holiday-rate/exclusion checks (overtime, leave day counting) where
    // every day of a multi-day holiday should count.
    public function get_holiday_on_date($date)
    {
        $rows = $this->db->where('holiday_date <=', $date)->get($this->tbl)->result();
        foreach ($rows as $row) {
            if ($date >= $row->holiday_date && $date <= $this->_effective_end($row)) {
                return $row;
            }
        }
        return null;
    }

    // Is $date specifically the FIRST day of a holiday's range? Used by the
    // automated day-before announcement (Hr_module_model::send_holiday_reminder())
    // so a multi-day holiday only ever triggers ONE reminder (the eve of day
    // one), not a repeat every day of the range.
    public function get_holiday_starting_on($date)
    {
        return $this->db->where('holiday_date', $date)->get($this->tbl)->row();
    }

    public function get_weekly_off_days()
    {
        $CI = &get_instance();
        if (!isset($CI->Hr_module_model)) {
            $CI->load->model('hr_module/Hr_module_model');
        }
        $val = $CI->Hr_module_model->get_setting('weekly_off_days', '5');
        if ($val === '' || $val === null) return [];
        return array_map('intval', explode(',', $val));
    }

    /**
     * Count working days between two dates (inclusive),
     * excluding weekly off days and public/company holidays.
     */
    public function count_working_days($from_date, $to_date)
    {
        $weekly_off = $this->get_weekly_off_days();
        $holidays   = $this->get_dates_in_range($from_date, $to_date);

        $start = strtotime($from_date);
        $end   = strtotime($to_date);
        $count = 0;

        for ($ts = $start; $ts <= $end; $ts += 86400) {
            $dow  = (int) date('w', $ts); // 0=Sun, 1=Mon, …, 5=Fri, 6=Sat
            $date = date('Y-m-d', $ts);
            if (!in_array($dow, $weekly_off) && !in_array($date, $holidays)) {
                $count++;
            }
        }
        return $count;
    }

    // One entry per calendar day (ranges expanded) - used by the leave-apply
    // form's calendar overlay, which checks each individual day against this list.
    public function get_as_json($year = null)
    {
        $rows   = $this->get_all($year);
        $result = [];
        foreach ($rows as $h) {
            $row_end = $this->_effective_end($h);
            for ($ts = strtotime($h->holiday_date); $ts <= strtotime($row_end); $ts += 86400) {
                $result[] = [
                    'date' => date('Y-m-d', $ts),
                    'name' => $h->name,
                    'type' => $h->type,
                ];
            }
        }
        return $result;
    }

    public function add($data)
    {
        $data['end_date']   = !empty($data['end_date']) ? $data['end_date'] : null;
        $data['year']       = (int) date('Y', strtotime($data['holiday_date']));
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['end_date'] = !empty($data['end_date']) ? $data['end_date'] : null;
        $data['year'] = (int) date('Y', strtotime($data['holiday_date']));
        $this->db->where('id', $id)->update($this->tbl, $data);
        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete($this->tbl);
        return $this->db->affected_rows() > 0;
    }
}
