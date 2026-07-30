<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-9 col-md-offset-1">
        <ol class="breadcrumb tw-mb-4">
          <li><a href="<?php echo admin_url('hr_module/performance'); ?>"><?php echo _l('hr_performance_list'); ?></a></li>
          <li class="active"><?php echo _l('hr_performance_assign'); ?></li>
        </ol>
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="tw-font-semibold tw-mb-4"><?php echo _l('hr_performance_assign'); ?></h4>
            <?php echo form_open(admin_url('hr_module/performance/add'), ['id' => 'targetForm']); ?>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group select-placeholder">
                    <label for="employee_id"><?php echo _l('hr_employee'); ?> <span class="text-danger">*</span></label>
                    <select name="employee_id" id="employee_id" class="selectpicker" required
                            data-width="100%" data-live-search="true"
                            data-none-selected-text="<?php echo _l('hr_select'); ?>">
                      <option value=""><?php echo _l('hr_select'); ?></option>
                      <?php foreach ($employees as $id => $name): ?>
                      <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('hr_performance_due_date'); ?></label>
                    <div class="input-group date">
                      <input type="text" name="due_date" class="form-control datepicker" autocomplete="off">
                      <span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_performance_target_title'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required
                       placeholder="Overall goal, e.g. Q3 Onboarding">
              </div>
              <div class="form-group">
                <label><?php echo _l('hr_performance_target_description'); ?></label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Overall context for this target..."></textarea>
              </div>

              <hr>
              <h5 class="tw-font-semibold tw-mb-2"><?php echo _l('hr_performance_sub_targets'); ?></h5>
              <p class="text-muted tw-text-sm"><?php echo _l('hr_performance_sub_targets_hint'); ?></p>

              <div id="sub-targets-wrap"></div>
              <button type="button" class="btn btn-default btn-xs tw-mb-3" id="add-sub-target-btn">
                <i class="fa-regular fa-plus tw-mr-1"></i><?php echo _l('hr_performance_add_sub_target'); ?>
              </button>

              <div class="tw-flex tw-gap-2 tw-mt-3">
                <button type="submit" class="btn btn-primary"><?php echo _l('hr_save'); ?></button>
                <a href="<?php echo admin_url('hr_module/performance'); ?>" class="btn btn-default">Cancel</a>
              </div>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    var staffOptions = <?php
        $opts = [];
        foreach ($staff as $id => $name) $opts[] = ['id' => $id, 'name' => $name];
        echo json_encode($opts);
    ?>;
    var rowIndex = 0;

    function addSubTargetRow(){
        var i = rowIndex++;
        var evaluatorOptions = staffOptions.map(function(s){
            return '<option value="'+s.id+'">'+$('<div>').text(s.name).html()+'</option>';
        }).join('');

        var $row = $(
            '<div class="panel_s sub-target-row" style="background:#f8fafc" data-index="'+i+'">' +
              '<div class="panel-body">' +
                '<div class="tw-flex tw-justify-between tw-items-center tw-mb-2">' +
                  '<strong><?php echo _l('hr_performance_sub_target_title'); ?> #<span class="row-number">'+(i+1)+'</span></strong>' +
                  '<button type="button" class="btn btn-default btn-xs remove-sub-target"><i class="fa fa-times"></i></button>' +
                '</div>' +
                '<div class="row">' +
                  '<div class="col-md-6">' +
                    '<div class="form-group">' +
                      '<input type="text" name="sub_title['+i+']" class="form-control" placeholder="<?php echo _l('hr_performance_sub_target_title'); ?>" required>' +
                    '</div>' +
                  '</div>' +
                  '<div class="col-md-3">' +
                    '<div class="form-group">' +
                      '<div class="input-group date">' +
                        '<input type="text" name="sub_due_date['+i+']" class="form-control datepicker" autocomplete="off">' +
                        '<span class="input-group-addon"><i class="fa-regular fa-calendar calendar-icon"></i></span>' +
                      '</div>' +
                    '</div>' +
                  '</div>' +
                  '<div class="col-md-3">' +
                    '<div class="form-group">' +
                      '<input type="text" name="sub_description['+i+']" class="form-control" placeholder="<?php echo _l('hr_performance_task_description'); ?>">' +
                    '</div>' +
                  '</div>' +
                '</div>' +
                '<div class="form-group select-placeholder tw-mb-0">' +
                  '<select name="sub_evaluator_ids['+i+'][]" class="selectpicker sub-evaluator-select" multiple ' +
                          'data-width="100%" data-live-search="true" data-actions-box="true" ' +
                          'data-none-selected-text="<?php echo _l('hr_performance_evaluators'); ?>">' +
                    evaluatorOptions +
                  '</select>' +
                '</div>' +
              '</div>' +
            '</div>'
        );
        $('#sub-targets-wrap').append($row);
        $row.find('.selectpicker').selectpicker();
        init_datepicker($row.find('.datepicker'));
        renumberRows();
    }

    function renumberRows(){
        $('#sub-targets-wrap .sub-target-row').each(function(idx){
            $(this).find('.row-number').text(idx + 1);
        });
    }

    $('#add-sub-target-btn').on('click', addSubTargetRow);

    $(document).on('click', '.remove-sub-target', function(){
        $(this).closest('.sub-target-row').remove();
        renumberRows();
    });

    // Start with one sub-target row
    addSubTargetRow();

    $('#targetForm').on('submit', function(e){
        var hasTitle = false;
        $('#sub-targets-wrap .sub-target-row input[name^="sub_title"]').each(function(){
            if ($.trim($(this).val()) !== '') hasTitle = true;
        });
        if (!hasTitle) {
            e.preventDefault();
            alert_float('danger', '<?php echo _l('hr_performance_no_sub_targets'); ?>');
        }
    });
});
</script>
