<?php
/**
 * Extension that outputs Google analytics code in the footer of web pages using a tracker ID set in the system_settings DB table. If the tracker ID is not
 * present, it will not output the code.  It will also only output the GA code for the production server.
 *
 * The extension requires a GA_TRACKER_ID variable to be set in the system settings db table.
 * It will also look for an optional variable called GA_COOKIE_DOMAIN in the system settings so you can explicitly set the cookie domain.
 *
 * @package Extensions
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 2.0
 */
class GoogleAnalytics extends AbstractExtension {
	/**
	 * The user's analytics tracker ID
	 *
	 * @var string
	 */
	private $_tracker_id;
	public function run() {
		if (!defined('GA_TRACKER_ID')) {
			Console::log('GoogleAnalytics: No tracker ID provided in system settings');
		} else {
			$this->_tracker_id = GA_TRACKER_ID;
		}
	}
	protected function act_on_compile_footer() {
		if (!empty($this->_tracker_id) && SERVER_TYPE == 'PRODUCTION') {
			$tracker_id = $this->_tracker_id;
			$cookie_domain_code = '';
			if (defined('GA_COOKIE_DOMAIN') && GA_COOKIE_DOMAIN != '') {
				$cookie_domain_code = '
	_gaq.push([\'_setDomainName\', \''.GA_COOKIE_DOMAIN.'\']);
	_gaq.push([\'_setAllowHash\', false]);';
			}
			$analytics_code = <<<JAVASCRIPT
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', '$tracker_id']);$cookie_domain_code
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
	})();
</script>
JAVASCRIPT;
			$this->Biscuit->append_view_var('footer',$analytics_code);
		}
		if (SERVER_TYPE != 'PRODUCTION') {
			Console::log("GoogleAnalytics: current server is NOT production. Not rendering Google Analytics code.");
		}
	}
	/**
	 * Add file manager permissions to the system settings table. Works with Biscuit 2.1 only
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function install_migration() {
		DB::query("REPLACE INTO `system_settings` (`constant_name`, `friendly_name`, `description`, `value`, `required`, `group_name`) VALUES
		('GA_TRACKER_ID','Tracker ID','If you leave this blank the tracker will not be loaded.','', 0, 'Google Analytics'),
		('GA_COOKIE_DOMAIN','Cookie Domain','Use this when tracker needs to work across sub-domains.','', 0, 'Google Analytics')");
	}
	/**
	 * Delete file manager permissions from the system settings table. Works with Biscuit 2.1 only
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public static function uninstall_migration() {
		DB::query("DELETE FROM `system_settings` WHERE `constant_name` LIKE 'GA_%'");
	}
}
?>