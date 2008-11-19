<?php $statuses = sfPropelModerationBehavior::getStatuses() ?>
<?php $current_status = $sf_params->get('filters[status]') ?>
<?php if ($current_status == '') $current_status = -1; ?>
<select name="filters[status]">
<?php foreach ($statuses as $status_code => $status_name): ?>
  <option value="<?php echo $status_code ?>" <?php echo $status_code == $current_status ? 'selected="Selected"' : '' ?>><?php echo $status_name ?></option>
<?php endforeach; ?>
</select>