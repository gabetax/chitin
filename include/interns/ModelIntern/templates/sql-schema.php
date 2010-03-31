CREATE TABLE `<?php echo Inflector::pluralize($name); ?>` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	`created` timestamp NULL default NULL,
<?php
foreach ($source->getFields() as $field) {
	if ($field->getHTMLAttribute('type') != 'submit')
		echo "\t" . $field->getSQLDefinition() . ",\n";
}
?>
	PRIMARY KEY (`id`)
);