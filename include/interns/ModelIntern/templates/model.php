<?php

include_once 'lib/Inflector.php';
include_once 'helpers/PHPHelper.php';

$fields = $source->getFields();
echo PHPHelper::OPEN . "\n";
?>
class <?php echo Inflector::camelize($name); ?> extends BaseRow {
	protected $table_name = '<?php echo Inflector::pluralize($name); ?>';
	
	public function validate ($type) {
		$errors = array();
<?php foreach ($fields as $field) { ?>
		if (empty($this-><?php echo $field->getName(); ?>)) $errors['<?php echo $name . '_' . $field->getName(); ?>'] = "<strong><?php echo Inflector::humanize($field->getName()); ?></strong> was not filled out";
<?php } ?>
		return $errors;
	}
}

function <?php echo Inflector::camelize($name); ?>Table () { return new <?php echo Inflector::camelize($name); ?>(); }
<?php echo PHPHelper::CLOSE; ?>
