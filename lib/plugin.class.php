<?php

define("PLUGIN_ID",					"id");
define("PLUGIN_IDENTIFIER",			"identifier");
define("PLUGIN_HOST",				"host");
define("PLUGIN_NAME",				"name");
define("PLUGIN_VERSION",			"version");
define("PLUGIN_DISPLAY_VERSION",	"displayVersion");
define("PLUGIN_SECRET", 			"secret");
define("PLUGIN_LEVEL", 				"level");
define("PLUGIN_HOST_VERSION", 		"hostVersion");
define("PLUGIN_MOD_DATE", 			"modDate");

define("LEVEL_NORMAL",	0);
define("LEVEL_BETA",	1);
define("LEVEL_PRE",		2);
define("LEVEL_DEV",		3);
define("LEVEL_SECRET",	4);

class Plugin {
	static private $plugins = null;

	private $dict = null;

	private $versions = null;

	private $latestVersion = null;

	private static function parseCriterias($criterias) {
		$where = array();
		foreach ($criterias as $criteria => $value) {
			switch ($criteria) {
				case PLUGIN_SECRET:
					if ($value)
						$where[$criteria] = "level = " . LEVEL_SECRET;
					break;
				case PLUGIN_LEVEL:
					$where[$criteria] = "level >= $value";
					break;
				case PLUGIN_HOST_VERSION:
					$where[$criteria]= "(minHostVersion <= \"$value\" OR ISNULL(minHostVersion)) AND (maxHostVersion > \"$value\" OR ISNULL(maxHostVersion))";
					break;
				case PLUGIN_MOD_DATE:
					$where[$criteria]= "(modDate > \"$value\" OR ISNULL(modDate))";
					break;
				case PLUGIN_ID:
					$where[$criteria] = "$criteria = $value";
					break;
				default: 
					$where[$criteria] = "$criteria = \"$value\"";
					break;
			}
		}
		$where = implode(" AND ", array_values($where));
		return $where;
	}
	
	static function all($secret = false, $level = LEVEL_NORMAL, $order_by = null) {
		return self::query(array(PLUGIN_SECRET => $secret, PLUGIN_LEVEL => $level), $order_by);
	}

	static function query($criterias, $order_by = null) {
		$where = self::parseCriterias($criterias);
		if (!$order_by)
			$order_by = PLUGIN_MOD_DATE . " DESC";
		$sql = "SELECT DISTINCT identifier FROM plugins WHERE $where ORDER BY $order_by;";
		$recs = fetch_db($sql);
		if (!$recs)
			return array();

		$plugins = array();
		foreach ($recs as $rec) {
			$plugin = Plugin::get(PLUGIN_IDENTIFIER, $rec['identifier'], $criterias);
			$plugins[] = $plugin;
		}
		return $plugins;
	}

	/* Fetching with criterias can prevent the versions to load completely */
	static function fetch($type, $value, $criterias = null) {
		if (!$criterias)
			$criterias = array();
		$criterias[$type] = $value;
		$fetch_version = null;
		/* We will fetch the correct version ourselves
		 * because doing it via SQL prevents the load of the other plugins
		 */
		if (@$criterias[PLUGIN_VERSION]) {
			$fetch_version = $criterias[PLUGIN_VERSION];
			unset($criterias[PLUGIN_VERSION]);
		}
		$where = self::parseCriterias($criterias);
		/* Fetch the latest item first, so it can be the first one reconstructed */
		$sql = "SELECT * FROM plugins WHERE $where ORDER BY version DESC;";
		$recs = fetch_db($sql);
		if ($recs === false)
			return null;
		$plugin_rec = array_shift($recs);
		$versions = array();
		$plugin = new Plugin($plugin_rec);
		foreach ($recs as $rec) {
			$version = new Plugin($rec);
			$version->setLatestVersion($plugin);
			$versions[] = $version;
		}
		$plugin->setVersions($versions);
		return $fetch_version ? $plugin->version($fetch_version) : $plugin;
	}

	static function get($type, $value, $criterias = null) {
		$plugin = null;
		if ($type == PLUGIN_IDENTIFIER)
			$plugin = @self::$plugins[$value];
		if (!$plugin) {
			$plugin = self::fetch($type, $value, $criterias);
			self::$plugins[$plugin->identifier] = $plugin;
		}
		return $plugin;
	}

	function __construct($dict) {
		$this->dict = $dict;
		$this->latestVersion = $this;
		$this->versions = array();
		$this->versions[$this->version] = $this;
	}

	function __tostring() {
		$version_str = " version: " . $this->displayVersion();
		$host_str = $this->host ? " (host $this->host)" : "";
		return "Plugin $this->identifier$version_str$host_str";
	}

	function __get($key) {
		if ($key == 'versions')
			return $this->versions;
		if ($key == 'latestVersion')
			return $this->latestVersion;
		return $this->dict[$key];
	}

	function image_url() {
		$file_name = fileRoot("../plugins/images/$this->identifier.{jpg,png}");
		$img_url = glob($file_name, GLOB_BRACE);
		if ($img_url === false || count($img_url) == 0)
			return webRoot("../plugins/images/noicon.png");

		return webRoot($img_url[0]);
	}

	function plugin_url() {
		$file_name = fileRoot("/plugins/files/{$this->identifier}__{$this->version}.qspkg");
		$file_url = glob($file_name, GLOB_BRACE);
		if ($file_url === false || count($file_url) == 0)
			return null;

		return webRoot($file_url[0]);
	}

	function plist_url() {
		$file_name = webRoot("/plugins/files/{$this->identifier}__{$this->version}.qsinfo");
		$file_url = glob($file_name, GLOB_BRACE);
		if ($file_url === false || count($file_url) == 0)
			return null;

		return webRoot($file_url[0]);
	}

	function versions() {
		return $this->versions;
	}

	function _setVersions($versions) {
		$this->versions = $versions;
	}

	function setVersions($versions) {
		$versions_dict = array();
		$this->versions = array();
		$versions_dict[$this->version] = $this;

		foreach ($versions as $version) {
			$versions_dict[$version->version] = $version;
			$version->_setVersions($versions_dict);
		}
		$this->_setVersions($versions_dict);
	}

	function version($version) {
		return $this->versions[$version];
	}

	function setLatestVersion($latest) {
		$this->latestVersion = $latest;
	}

	function displayVersion() {
		return ($this->displayVersion ? "v$this->displayVersion ($this->version)" : "$this->version");
	}

	function to_array() {
		$array = array(
			PLUGIN_IDENTIFIER => $this->identifier,
			PLUGIN_NAME => $this->name,
			PLUGIN_VERSION => $this->version,
			PLUGIN_MOD_DATE => new DateTime($this->modDate, new DateTimeZone('UTC')),
		);
		if ($this->displayVersion)
			$array[PLUGIN_DISPLAY_VERSION] = $this->displayVersion;
		return $array;
	}
}

?>