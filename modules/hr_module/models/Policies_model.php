<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Policies_model extends App_Model
{
    private $tbl;
    private $tbl_revisions;

    public function __construct()
    {
        parent::__construct();
        $this->tbl           = db_prefix() . 'hr_policies';
        $this->tbl_revisions = db_prefix() . 'hr_policy_revisions';
        $this->_ensure_tables();
    }

    // Lazily creates the policy tables on first use, so this works immediately
    // without requiring the module to be reactivated (mirrors install.php).
    private function _ensure_tables()
    {
        if (!$this->db->table_exists($this->tbl)) {
            $this->db->query('CREATE TABLE `' . $this->tbl . '` (
              `id` int(10) unsigned NOT NULL,
              `title` varchar(191) NOT NULL,
              `type` enum(\'public\',\'private\') NOT NULL DEFAULT \'public\',
              `department_id` int(11) DEFAULT NULL,
              `department_ids` text DEFAULT NULL,
              `content_type` enum(\'pdf\',\'text\') NOT NULL DEFAULT \'text\',
              `content` longtext DEFAULT NULL,
              `attachment` text DEFAULT NULL,
              `status` enum(\'pending\',\'published\',\'rejected\') NOT NULL DEFAULT \'pending\',
              `rejection_reason` text DEFAULT NULL,
              `created_by` int(11) DEFAULT NULL,
              `approved_by` int(11) DEFAULT NULL,
              `approved_at` datetime DEFAULT NULL,
              `published_at` datetime DEFAULT NULL,
              `created_at` datetime NOT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `department_id` (`department_id`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ';');
            $this->db->query('ALTER TABLE `' . $this->tbl . '` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
        }
        if (!$this->db->table_exists($this->tbl_revisions)) {
            $this->db->query('CREATE TABLE `' . $this->tbl_revisions . '` (
              `id` int(10) unsigned NOT NULL,
              `policy_id` int(10) unsigned NOT NULL,
              `title` varchar(191) NOT NULL,
              `type` enum(\'public\',\'private\') NOT NULL DEFAULT \'public\',
              `department_id` int(11) DEFAULT NULL,
              `department_ids` text DEFAULT NULL,
              `content_type` enum(\'pdf\',\'text\') NOT NULL DEFAULT \'text\',
              `content` longtext DEFAULT NULL,
              `attachment` text DEFAULT NULL,
              `status` enum(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\',
              `rejection_reason` text DEFAULT NULL,
              `submitted_by` int(11) DEFAULT NULL,
              `reviewed_by` int(11) DEFAULT NULL,
              `reviewed_at` datetime DEFAULT NULL,
              `created_at` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `policy_id` (`policy_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $this->db->char_set . ';');
            $this->db->query('ALTER TABLE `' . $this->tbl_revisions . '` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
        }

        // Upgrade: attachment now holds a JSON-encoded array of files (multiple
        // upload support), not a single filename - widen from varchar(255) to text.
        foreach ([$this->tbl, $this->tbl_revisions] as $table) {
            $col = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE 'attachment'")->row();
            if ($col && stripos($col->Type, 'varchar') !== false) {
                $this->db->query("ALTER TABLE `{$table}` MODIFY `attachment` TEXT DEFAULT NULL");
            }
        }

        // Upgrade: a policy can now target multiple departments - add a
        // comma-separated `department_ids` column (the old single `department_id`
        // stays, unused going forward, and is backfilled into the new column so
        // existing single-department policies keep working exactly as before).
        foreach ([$this->tbl, $this->tbl_revisions] as $table) {
            $col = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE 'department_ids'")->num_rows();
            if ($col === 0) {
                $this->db->query("ALTER TABLE `{$table}` ADD COLUMN `department_ids` TEXT DEFAULT NULL AFTER `department_id`");
                $this->db->query("UPDATE `{$table}` SET `department_ids` = `department_id` WHERE `department_id` IS NOT NULL");
            }
        }
    }

    // Small, mostly-static lookup used to resolve department names in bulk without
    // an N+1 query per policy row (the departments table itself is tiny).
    private function _dept_name_map()
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach ($this->db->select('departmentid, name')->get(db_prefix() . 'departments')->result() as $d) {
                $map[(int) $d->departmentid] = $d->name;
            }
        }
        return $map;
    }

    private function _decode_department_ids($raw)
    {
        if (!$raw) {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', $raw))));
    }

    // Attaches `department_id_list` (decoded array of ints) and `department_names`
    // (comma-joined display string) to a row or array of rows, leaving the raw
    // `department_ids` CSV column untouched so callers that need to copy it
    // verbatim (e.g. approve_revision()) still can.
    private function _attach_department_names($rows)
    {
        $single = !is_array($rows);
        $list   = $single ? [$rows] : $rows;
        $map    = $this->_dept_name_map();
        foreach ($list as $r) {
            if (!$r) continue;
            $ids = $this->_decode_department_ids($r->department_ids);
            $r->department_id_list = $ids;
            $names = array_filter(array_map(function ($id) use ($map) { return $map[$id] ?? null; }, $ids));
            $r->department_names = $names ? implode(', ', $names) : '';
        }
        return $single ? ($list[0] ?? null) : $list;
    }

    // Normalizes a stored `attachment` value into a list of ['file'=>, 'name'=>]
    // - handles the current JSON-array format, a legacy single-filename string
    // (from before multi-file support), and an empty/null value.
    public function decode_attachments($raw)
    {
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        return [['file' => $raw, 'name' => $raw]];
    }

    // ── Policies ─────────────────────────────────────────────────────────

    public function get($id)
    {
        $row = $this->db->select('p.*,
                CONCAT(cs.firstname," ",cs.lastname) as created_by_name,
                CONCAT(aps.firstname," ",aps.lastname) as approved_by_name', false)
            ->from($this->tbl . ' p')
            ->join(db_prefix() . 'staff cs', 'cs.staffid = p.created_by', 'left')
            ->join(db_prefix() . 'staff aps', 'aps.staffid = p.approved_by', 'left')
            ->where('p.id', $id)
            ->get()->row();
        return $this->_attach_department_names($row);
    }

    // Every policy a viewer is entitled to see is already filtered by the controller
    // (department/public rules) - this just returns everything for a given filter set,
    // used both for the employee-facing list and the admin "all policies" list.
    // $filters['department_id'] matches any policy whose department set includes it.
    public function get_all($filters = [])
    {
        $this->db->select('p.*', false)->from($this->tbl . ' p');

        if (!empty($filters['status'])) {
            $this->db->where('p.status', $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $this->db->where('FIND_IN_SET(' . (int) $filters['department_id'] . ', p.department_ids) > 0', null, false);
        }
        if (!empty($filters['type'])) {
            $this->db->where('p.type', $filters['type']);
        }
        $this->db->order_by('p.created_at', 'DESC');
        $rows = $this->db->get()->result();
        return $this->_attach_department_names($rows);
    }

    // Policies visible to a regular employee: published, and either public or
    // targeting their own department.
    public function get_visible_for_department($department_id)
    {
        $this->db->select('p.*', false)->from($this->tbl . ' p')
            ->where('p.status', 'published')
            ->group_start()
                ->where('p.type', 'public');
        if ($department_id) {
            $this->db->or_where('FIND_IN_SET(' . (int) $department_id . ', p.department_ids) > 0', null, false);
        }
        $this->db->group_end()->order_by('p.created_at', 'DESC');
        $rows = $this->db->get()->result();
        return $this->_attach_department_names($rows);
    }

    public function get_pending()
    {
        $this->db->select('p.*, CONCAT(cs.firstname," ",cs.lastname) as created_by_name', false)
            ->from($this->tbl . ' p')
            ->join(db_prefix() . 'staff cs', 'cs.staffid = p.created_by', 'left')
            ->where('p.status', 'pending')
            ->order_by('p.created_at', 'ASC');
        $rows = $this->db->get()->result();
        return $this->_attach_department_names($rows);
    }

    public function add($data)
    {
        $data['status']     = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl, $data);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('HR Policy Submitted [ID: ' . $id . ', Title: ' . $data['title'] . ']');
        }
        return $id;
    }

    public function approve($id)
    {
        $policy = $this->get($id);
        if (!$policy || $policy->status !== 'pending') {
            return ['success' => false, 'message' => 'Only a pending policy can be approved.'];
        }
        $this->db->where('id', $id)->update($this->tbl, [
            'status'       => 'published',
            'approved_by'  => get_staff_user_id(),
            'approved_at'  => date('Y-m-d H:i:s'),
            'published_at' => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Policy Approved & Published [ID: ' . $id . ', Title: ' . $policy->title . ']');
        return ['success' => true];
    }

    public function reject($id, $reason = '')
    {
        $policy = $this->get($id);
        if (!$policy || $policy->status !== 'pending') {
            return ['success' => false, 'message' => 'Only a pending policy can be rejected.'];
        }
        $this->db->where('id', $id)->update($this->tbl, [
            'status'           => 'rejected',
            'rejection_reason' => $reason ?: null,
        ]);
        log_activity('HR Policy Rejected [ID: ' . $id . ', Title: ' . $policy->title . ']');
        return ['success' => true];
    }

    public function delete($id)
    {
        $policy = $this->get($id);
        $this->db->where('policy_id', $id)->delete($this->tbl_revisions);
        $this->db->where('id', $id)->delete($this->tbl);
        $deleted = $this->db->affected_rows() > 0;
        if ($deleted) {
            log_activity('HR Policy Deleted [ID: ' . $id . ', Title: ' . ($policy ? $policy->title : '') . ']');
        }
        return $deleted;
    }

    // ── Revisions (edits to an already-published policy) ────────────────

    public function get_revision($id)
    {
        $row = $this->db->select('r.*,
                CONCAT(sb.firstname," ",sb.lastname) as submitted_by_name', false)
            ->from($this->tbl_revisions . ' r')
            ->join(db_prefix() . 'staff sb', 'sb.staffid = r.submitted_by', 'left')
            ->where('r.id', $id)
            ->get()->row();
        return $this->_attach_department_names($row);
    }

    public function get_pending_revision($policy_id)
    {
        $row = $this->db->where('policy_id', $policy_id)->where('status', 'pending')
            ->order_by('id', 'DESC')
            ->get($this->tbl_revisions)->row();
        return $this->_attach_department_names($row);
    }

    public function get_pending_revisions()
    {
        $this->db->select('r.*, p.title as policy_title,
                CONCAT(sb.firstname," ",sb.lastname) as submitted_by_name', false)
            ->from($this->tbl_revisions . ' r')
            ->join($this->tbl . ' p', 'p.id = r.policy_id', 'left')
            ->join(db_prefix() . 'staff sb', 'sb.staffid = r.submitted_by', 'left')
            ->where('r.status', 'pending')
            ->order_by('r.created_at', 'ASC');
        $rows = $this->db->get()->result();
        return $this->_attach_department_names($rows);
    }

    // Stages a proposed change to an existing published policy - the live hr_policies
    // row is left untouched until this is approved, so employees keep seeing the
    // previous content in the meantime.
    public function submit_revision($policy_id, $data)
    {
        if ($this->get_pending_revision($policy_id)) {
            return ['success' => false, 'message' => 'A pending update is already awaiting review for this policy.'];
        }
        $data['policy_id']    = $policy_id;
        $data['status']       = 'pending';
        $data['submitted_by'] = get_staff_user_id();
        $data['created_at']   = date('Y-m-d H:i:s');
        $this->db->insert($this->tbl_revisions, $data);
        $id = $this->db->insert_id();
        if (!$id) {
            return ['success' => false, 'message' => 'Could not save the update.'];
        }
        log_activity('HR Policy Update Submitted [Policy ID: ' . $policy_id . ', Revision ID: ' . $id . ']');
        return ['success' => true, 'id' => $id];
    }

    // Approving a revision copies its fields onto the live policy row, so employees
    // now see the updated content; the old content is gone (superseded), matching
    // "before that employee can watch previous policy" only up until approval.
    public function approve_revision($id)
    {
        $revision = $this->get_revision($id);
        if (!$revision || $revision->status !== 'pending') {
            return ['success' => false, 'message' => 'Only a pending update can be approved.'];
        }
        $this->db->where('id', $revision->policy_id)->update($this->tbl, [
            'title'          => $revision->title,
            'type'           => $revision->type,
            'department_ids' => $revision->department_ids,
            'content_type'   => $revision->content_type,
            'content'        => $revision->content,
            'attachment'     => $revision->attachment,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        $this->db->where('id', $id)->update($this->tbl_revisions, [
            'status'      => 'approved',
            'reviewed_by' => get_staff_user_id(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Policy Update Approved & Published [Policy ID: ' . $revision->policy_id . ', Revision ID: ' . $id . ']');
        return ['success' => true, 'policy_id' => $revision->policy_id];
    }

    public function reject_revision($id, $reason = '')
    {
        $revision = $this->get_revision($id);
        if (!$revision || $revision->status !== 'pending') {
            return ['success' => false, 'message' => 'Only a pending update can be rejected.'];
        }
        $this->db->where('id', $id)->update($this->tbl_revisions, [
            'status'           => 'rejected',
            'rejection_reason' => $reason ?: null,
            'reviewed_by'      => get_staff_user_id(),
            'reviewed_at'      => date('Y-m-d H:i:s'),
        ]);
        log_activity('HR Policy Update Rejected [Policy ID: ' . $revision->policy_id . ', Revision ID: ' . $id . ']');
        return ['success' => true, 'policy_id' => $revision->policy_id];
    }
}
