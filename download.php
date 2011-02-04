<?php
/*
 * File that get accessed when QS request an update (either plugin or main application)
 * 
 * When updating the app, we'll recieve :
 *   /download.php?id=com.blacktree.Quicksilver&type=dmg&new=yes (yes, with no version)
 * When updating a plugin, we'll recieve :
 *   /download.php?qsversion=%d&id=%@
 */

include("lib/functions.php");

$id = @$_GET['id'];
$type = @$_GET['type'];
$new = @$_GET['new'];
$qsversion = @$_GET['qsversion'];

$plugin = null;
if ($id == QS_ID) {
	$type = null;
	if (@$_GET['pre'] == "1")
		$type = "pre";
	if (@$_GET['dev'] == "1")
		$type = "dev";
	if ($type)
		$id .= ".$type";

	$plugin = Plugin::get(PLUGIN_IDENTIFIER, $id);
} else {
	$criteria = array();
	$criteria[PLUGIN_HOST] = QS_ID;
	$criteria[PLUGIN_IDENTIFIER] = $id;

	if ($qsversion)
		$criteria[PLUGIN_HOST_VERSION] = $qsversion;

	$plugin = Plugin::get(PLUGIN_IDENTIFIER, $id, $criteria);
}
dump($plugin);

if ($plugin) {
	debug("Name: " . $plugin->name);
	debug("Version: " . $plugin->displayVersion());
	debug("URL: " . $plugin->plugin_url());
}

?>