<?php $classes = sfModeratedContentPeer::getTypes() ?>
<?php $current_class = $sf_params->get('filters[object_model]') ?>
<select name="filters[object_model]">
  <option value="">All types</option>
<?php foreach ($classes as $class => $type): ?>
  <option value="<?php echo $class ?>" <?php echo $class == $current_class ? 'selected="Selected"' : '' ?>><?php echo $type ?></option>
<?php endforeach; ?>
</select>