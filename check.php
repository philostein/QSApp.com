<?php
/*
 * File that get accessed when QS wants to check for updates
 * 
 * URL: check.php?type=rel&current=3841
 * type is either rel, pre, dev (Release, Pre-Release, Development)
 * current is the current QS version
 */

include("lib/functions.php");

$id = QS_ID;
$type = @$_GET['type'];
$current = @$_GET['current'];

if ($type && $type != 'rel')
	$id .= ".$type";

$plug = Plugin::get(PLUGIN_IDENTIFIER, $id);
die($plug ? $plug->version : "");
?>