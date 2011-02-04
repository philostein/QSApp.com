<?php

include("lib/functions.php");

/*
 * URL: plugininfo.php?asOfDate=date&updateVersion=updateVersion&qsversion=qsversion&sids=ids
 * asOfDate is the date of the last fetched date
 * updateversion and qsversion are both the currently installed version, as a hexString
 */

$shouldSendFullIndex = false;
$updated = false;
$version = null;

$asOfDate = @$_GET['asOfDate'];
if ($asOfDate) {
	$date = array();
	if(preg_match_all("/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/", $asOfDate, $date)) {
		$asOfDate = mktime(	$date[4][0], $date[5][0], $date[6][0],
							$date[2][0], $date[3][0], $date[1][0]);
		$asOfDate = strftime("%Y-%m-%d %H:%M:%S", $asOfDate);
	} else {
		$asOfDate = null;
	}
}
if (!$asOfDate)
	$shouldSendFullIndex = true;

$updateVersion = @$_GET['updateVersion'];
if ($updateVersion) {
	$version = $updateVersion;
	$updated = true;
} else {
	$version = @$_GET['qsversion'];
}

/* Convert the hexString back in an integer */
$version = sprintf("%x", $version);

$sids = @$_GET['sids'];
if ($sids) {
	$sids = explode(", ", $sids);
}

debug("asOfDate: $asOfDate, version: $version, updated: " . ($updated ? "yes" : "no") . ", full index: " . ($shouldSendFullIndex ? "yes" : "no") . ", sids: $sids");

$criteria = array();
$criteria[PLUGIN_HOST] = QS_ID;

if ($version)
	$criteria[PLUGIN_HOST_VERSION] = $version;

if ($asOfDate)
	$criteria[PLUGIN_MOD_DATE] = $asOfDate;

/* TODO: sids */

$query = Plugin::query($criteria);

$structure = array('plugins' => array());
if ($shouldSendFullIndex)
	$structure['fullIndex'] = $shouldSendFullIndex;

foreach($query as $plugin) {
	$plugin_array = $plugin->to_array();
	$plugin_structure = array();
	$plugin_structure['CFBundleIdentifier'] = $plugin_array[PLUGIN_IDENTIFIER];
	$plugin_structure['CFBundleName'] = $plugin_array[PLUGIN_NAME];
	$plugin_structure['CFBundleVersion'] = $plugin_array[PLUGIN_VERSION];
	
	if (isset($plugin_array[PLUGIN_DISPLAY_VERSION]))
		$plugin_structure['CFBundleShortVersionString'] = $plugin_array[PLUGIN_DISPLAY_VERSION];
	$plugin_structure['QSModifiedDate'] = $plugin_array[PLUGIN_MOD_DATE];
//	$plugin_structure['QSPlugIn'] = 
//	$plugin_structure['QSRequirements'] = 
	$structure['plugins'][] = $plugin_structure;
}

$td = new CFTypeDetector();  
$guessedStructure = $td->toCFType( $structure );

$plist = new CFPropertyList();
$plist->add( $guessedStructure );

echo $plist->toXML(true);
?>