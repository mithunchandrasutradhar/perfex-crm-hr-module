<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
<div class="row"><div class="col-md-12">
  <ol class="breadcrumb tw-mb-3">
    <li><a href="<?php echo admin_url('hr_module/reports'); ?>"><?php echo _l('hr_reports'); ?></a></li>
    <li class="active"><?php echo $title; ?></li>
  </ol>

  <div class="row tw-mb-3">
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #4f46e5;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#4f46e5"><?php echo $total; ?></div>
      <div style="font-size:0.78rem;color:#64748b">Total Employees</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #059669;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#059669"><?php echo array_sum(array_column((array)$rows,'active')); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Active</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #0891b2;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#0891b2"><?php echo array_sum(array_column((array)$rows,'male')); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Male</div>
    </div></div>
    <div class="col-md-3"><div style="background:#fff;border-radius:8px;padding:12px 16px;border-left:3px solid #db2777;box-shadow:0 1px 3px rgba(0,0,0,.08)">
      <div style="font-size:1.6rem;font-weight:700;color:#db2777"><?php echo array_sum(array_column((array)$rows,'female')); ?></div>
      <div style="font-size:0.78rem;color:#64748b">Female</div>
    </div></div>
  </div>

  <div class="panel_s"><div class="panel-body panel-table-full">
    <table class="table table-hover">
      <thead><tr>
        <th>Department</th>
        <th class="text-right">Total</th><th class="text-right">Active</th><th class="text-right">Inactive</th>
        <th class="text-right">Permanent</th><th class="text-right">Contract</th><th class="text-right">Part-time</th>
        <th class="text-right">Male</th><th class="text-right">Female</th>
      </tr></thead>
      <tbody>
      <?php if(empty($rows)): ?><tr><td colspan="9" class="text-center text-muted" style="padding:30px">No records.</td></tr>
      <?php else: foreach($rows as $r): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($r->department_name ?? 'Unassigned'); ?></strong></td>
        <td class="text-right"><strong><?php echo $r->total; ?></strong></td>
        <td class="text-right text-success"><?php echo $r->active; ?></td>
        <td class="text-right text-muted"><?php echo $r->inactive; ?></td>
        <td class="text-right"><?php echo $r->permanent; ?></td>
        <td class="text-right"><?php echo $r->contract; ?></td>
        <td class="text-right"><?php echo $r->parttime; ?></td>
        <td class="text-right"><?php echo $r->male; ?></td>
        <td class="text-right"><?php echo $r->female; ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="active">
        <td><strong>Total</strong></td>
        <td class="text-right"><strong><?php echo $total; ?></strong></td>
        <td class="text-right"><strong><?php echo array_sum(array_column((array)$rows,'active')); ?></strong></td>
        <td class="text-right"><?php echo array_sum(array_column((array)$rows,'inactive')); ?></td>
        <td class="text-right"><?php echo array_sum(array_column((array)$rows,'permanent')); ?></td>
        <td class="text-right"><?php echo array_sum(array_column((array)$rows,'contract')); ?></td>
        <td class="text-right"><?php echo array_sum(array_column((array)$rows,'parttime')); ?></td>
        <td class="text-right"><?php echo array_sum(array_column((array)$rows,'male')); ?></td>
        <td class="text-right"><?php echo array_sum(array_column((array)$rows,'female')); ?></td>
      </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</div></div>
</div></div>
<?php init_tail(); ?>
