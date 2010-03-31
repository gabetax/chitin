<?php
include_once 'lib/Inflector.php';
$fields = $source->getFields();
?>
<table class="list">
<tr>
<?php foreach ($fields as $field) { ?>
	<th><a href="<?php echo PHPHelper::OPEN . " echo LinkHelper::column(array('sort_field' => '".$field->getName()."')); " . PHPHelper::CLOSE; ?>"><?php echo Inflector::humanize($field->getName()); ?></a></th>
<?php }?>
	<th>Edit</th>
	<th>Delete</th>
</tr>
<?php echo PHPHelper::OPEN; ?> foreach ($items as $item) { <?php echo PHPHelper::CLOSE . "\n"; ?>
<tr class="<?php echo PHPHelper::OPEN; ?> echo CycleHelper::cycle(array('values' => array('even', 'odd'))); <?php echo PHPHelper::CLOSE; ?>">
<?php foreach ($fields as $field) { ?>
	<td><?php echo PHPHelper::OPEN . ' echo htmlentities($item->' . $field->getName() . ', ENT_COMPAT, \'UTF-8\'); ' . PHPHelper::CLOSE; ?></td>
<?php }?>
	<td><a href="<?php echo PHPHelper::OPEN; ?> echo PathToRoot::get(); <?php echo PHPHelper::CLOSE; ?><?php echo Inflector::pluralize($name); ?>/edit/<?php echo PHPHelper::OPEN; ?> echo $item->id; <?php echo PHPHelper::CLOSE; ?>">Edit</a></td>
	<td><a href="<?php echo PHPHelper::OPEN; ?> echo PathToRoot::get(); <?php echo PHPHelper::CLOSE; ?><?php echo Inflector::pluralize($name); ?>/delete/<?php echo PHPHelper::OPEN; ?> echo $item->id; <?php echo PHPHelper::CLOSE; ?>" onclick="return confirm('Are you sure you want to delete <?php echo PHPHelper::OPEN; ?> echo htmlentities($item->name, ENT_COMPAT, 'UTF-8'); <?php echo PHPHelper::CLOSE; ?>?')">Delete</a></td>
</tr>
<?php echo PHPHelper::OPEN; ?> } <?php echo PHPHelper::CLOSE . "\n"; ?>
</table>
<?php echo PHPHelper::OPEN; ?> echo $pager_html; <?php echo PHPHelper::CLOSE; ?>

<a href="<?php echo PHPHelper::OPEN; ?> echo PathToRoot::get(); <?php echo PHPHelper::CLOSE; ?><?php echo Inflector::pluralize($name); ?>/add">Add a new <?php echo Inflector::humanize($name); ?></a>