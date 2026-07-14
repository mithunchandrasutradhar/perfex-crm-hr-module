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

    public function get_holiday_names_in_range($from_date, $to_date)
    {
        $rows = $this->db->where('holiday_date >=', $from_date)
            ->where('holiday_date <=', $to_date)
            ->get($this->tbl)->result();
        $map = [];
        foreach ($rows as $row) {
            $map[$row->holiday_date] = $row->name;
        }
        return $map;
    }

    public function get_holiday_on_date($date)
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

    public function get_as_json($year = null)
    {
        $rows   = $this->get_all($year);
        $result = [];
        foreach ($rows as $h) {
            $result[] = [
                'date' => $h->holiday_date,
                'name' => $h->name,
                'type' => $h->type,
            ];
        }
        return $result;
    }

    public function add($data)
    {
        $data['year']       = (int) date('Y', strtotime($data['holiday_date']));
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
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
