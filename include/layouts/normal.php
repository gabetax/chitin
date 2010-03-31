<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Site Name<?php if (isset($page['title'])) echo ' - ' . $page['title']; ?></title>
	<link href="<?php echo PathToRoot::get(); ?>styles/chitin.css" rel="stylesheet" type="text/css" />
<?php if (isset($redir) && !empty($redir))
	echo '<meta http-equiv="refresh" content="4;url=' . $redir .'" />';
?>
</head>
<body<?php if (isset($page['onload'])) echo ' onload="'.$page['onload'].'"'; ?>>
<?php if (isset($page['content'])) echo $page['content']; ?>
</body>
</html>