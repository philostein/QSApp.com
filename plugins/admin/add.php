<?php

include('../../lib/functions.php');

dump($_POST);
dump($_FILES);

$plist = null;

if ($_POST['submit'] == "New") {
	if (!($_FILES["plugin_file"]["error"] == 0 && $_FILES["plugin_file"]["error"] == 0))
		error("Upload failed...");

	$plist = new CFPropertyList($_FILES["info_plist_file"]['tmp_name']);
	if (!$plist)
		error("Failed to parse plist");
	$dict = $plist->getValue(true);
	$plugin_rec = array();
	$plugin_rec[PLUGIN_IDENTIFIER] = $dict->get('CFBundleIdentifier')->getValue();
	$plugin_rec[PLUGIN_NAME] = $dict->get('CFBundleName')->getValue();
	$plugin_rec[PLUGIN_VERSION] = $dict->get('CFBundleVersion')->getValue();
	if ($dict->get('CFBundleShortVersionString'))
		$plugin_rec[PLUGIN_DISPLAY_VERSION] = $dict->get('CFBundleShortVersionString')->getValue();
	// $plugin_rec[PLUGIN_LEVEL] = $dict['CFBundleShortVersionString'];
	dump($plugin_rec);
	$plugin = new Plugin($plugin_rec);
	debug($plugin);
	dump($plugin->displayVersion);
	$plugin->create();
	dump($dict->get('CFBundleIdentifier'));
	// move_uploaded_file($_FILES["plugin_file"]["tmp_name"], $plugin_path);
}
/*
	{
		["name"]=> "updates.txt"
		["type"]=> "text/plain"
		["tmp_name"]=> "/private/var/tmp/phpjOy5vq"
		["error"]=> 0
		["size"]=> 1789
	}
 */
?>
<html>
<head></head>
<body>
	<form name="plugin_add" method="post" enctype="multipart/form-data">
		<label for="plugin_file">Plugin ditto archive :</label>
		<input id="plugin_file" name="plugin_file" type="file"/><br />
		<label for="info_plist_file">Plugin Info.plist :</label>
		<input id="info_plist_file" name="info_plist_file" type="file"/><br />
		<input type="submit" name="submit" value="New" />
	</form>
	<?php if ($plist) debug($plist->toXML(true)); ?>
</body>
</html>