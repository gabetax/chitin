<?php echo PHPHelper::OPEN; ?> if (isset($errors)) echo ErrorHelper::index($errors); <?php echo PHPHelper::CLOSE; ?>

<?php
echo $source->getHTML();
?>