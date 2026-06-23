<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var array $counts    */
/** @var int   $demo_emp  */
/** @var int   $talha_emp */
if (!isset($counts))    $counts    = [];
if (!isset($demo_emp))  $demo_emp  = 0;
if (!isset($talha_emp)) $talha_emp = 0;
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="tw-font-semibold">
              <i class="fa fa-database tw-mr-2 text-info"></i>HR Database Status
            </h4>
          </div>
          <div class="panel-body">
            <?php
            $seeded = ($demo_emp > 0 || $talha_emp > 0);
            ?>
            <div class="alert alert-<?php echo $seeded ? 'success' : 'warning'; ?>">
              <?php if ($seeded): ?>
                <i class="fa fa-check-circle tw-mr-1"></i>
                <strong>Demo data is seeded.</strong>
                <?php if ($demo_emp > 0): ?><?php echo $demo_emp; ?> DEMO- employees found.<?php endif; ?>
                <?php if ($talha_emp > 0): ?> Talha (ALPHA-EMP-001) employee record found.<?php endif; ?>
              <?php else: ?>
                <i class="fa fa-exclamation-triangle tw-mr-1"></i>
                <strong>No demo employees found.</strong> Run the seeder first.
              <?php endif; ?>
            </div>

            <table class="table table-bordered table-striped table-condensed tw-mt-4">
              <thead>
                <tr>
                  <th>Table</th>
                  <th class="tw-text-right">Row Count</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($counts as $tbl => $count): ?>
                <tr>
                  <td><code><?php echo db_prefix() . $tbl; ?></code></td>
                  <td class="tw-text-right">
                    <?php if ($count === 'TABLE MISSING'): ?>
                      <span class="label label-danger">MISSING</span>
                    <?php else: ?>
                      <strong><?php echo $count; ?></strong>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($count === 'TABLE MISSING'): ?>
                      <span class="text-danger"><i class="fa fa-times tw-mr-1"></i>Table does not exist — re-install module</span>
                    <?php elseif ($count == 0): ?>
                      <span class="text-muted"><i class="fa fa-minus tw-mr-1"></i>Empty</span>
                    <?php else: ?>
                      <span class="text-success"><i class="fa fa-check tw-mr-1"></i>Has data</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <div class="tw-mt-4 tw-flex tw-gap-2">
              <a href="<?php echo admin_url('hr_module/demo_data'); ?>" class="btn btn-default">
                <i class="fa fa-arrow-left tw-mr-1"></i>Back to Seeder
              </a>
              <a href="<?php echo admin_url('hr_module/demo_data/status'); ?>" class="btn btn-info">
                <i class="fa fa-refresh tw-mr-1"></i>Refresh
              </a>
              <?php if ($demo_emp == 0): ?>
              <a href="<?php echo admin_url('hr_module/demo_data/run'); ?>"
                 class="btn btn-success"
                 onclick="return confirm('Seed demo HR data now?')">
                <i class="fa fa-play-circle tw-mr-1"></i>Seed Demo Data Now
              </a>
              <?php else: ?>
              <a href="<?php echo admin_url('hr_module/demo_data/reset'); ?>"
                 class="btn btn-danger"
                 onclick="return confirm('Delete all DEMO- data?')">
                <i class="fa fa-trash tw-mr-1"></i>Reset Demo Data
              </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
