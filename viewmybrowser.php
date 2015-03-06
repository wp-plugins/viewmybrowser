<?php
/*
Plugin Name: ViewMyBrowser Support
Plugin URI: http://www.viewmybrowser.com/
Description: Zur einbindung der ViewMyBrowser Supportlösung in ihre Wordpress Homepage
Version: 1.0
Author: ViewMyBrowser
Author URI: http://www.viewmybrowser.com/
Update Server: http://app.viewmybrowser.com/vmb/plugins/wordpress/viewmybrowser/
*/

/**
 * ViewMyBrowser plugin für Wordpress
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, see <http://www.gnu.org/licenses/>.
 *
 * Verbatim copying and distribution of this entire article is permitted in any medium without royalty provided this notice is preserved.
 *
 * @link http://www.viewmybrowser.com
 * @version 1.3
 * @copyright Copyright: 2012 EGOTEC GmbH
 * @author EGOTEC GmbH <sales@viewmybrowser.com>
 */

error_reporting(0);

add_action('widgets_init', array('ViewMyBrowser', 'init'));

class ViewMyBrowser {
	//Variabelen Setzen
	public static $siteID;
	public static $clientID;
	public static $current_user;
	public static $showInFrontEnd;
	public static $showInBackEnd;
	public static $updated = false;
	public static $plugin_url;

	public static function init() {
		if ($_POST['action'] == 'update') {
			self::update();
		}
		self::$current_user = wp_get_current_user();
		self::$siteID = self::getSiteID();
		self::$showInFrontEnd = self::getShowInFrontEnd();
		self::$showInBackEnd = self::getShowInBackEnd();
		self::$plugin_url =  plugin_dir_url() . "viewmybrowser/";
		
		if(isset(self::$current_user->display_name)) {
			self::$clientID = self::$current_user->display_name;
		} elseif(isset(self::$current_user->user_email)) {
			self::$clientID = self::$current_user->user_email;
		} elseif(isset(self::$current_user->user_login)) {
			self::$clientID = self::$current_user->user_login;
		} else {
			self::$clientID = __("Guest");
		}

		if(is_admin()) {
			self::createAdminMenu();
		} else {
			self::showViewMyBrowserUI();
		}
	}

	public static function update() {
		self::updateSiteID($_POST['siteID']);
		self::updateShowInFrontEnd(isset($_POST['showInVMBFrontend']));
		self::updateShowInBackEnd(isset($_POST['showInVMBBackend']));
		self::$updated = true;
	}

	//SiteID
	public static function getSiteID() {
		return get_option('siteID');
	}

	public static function updateSiteID($siteID) {
		update_option('siteID', $siteID);
	}
	
	//Show in Frontend
	public static function getShowInFrontEnd() {
		return get_option('showInVMBFrontend');
	}
	
	public static function updateShowInFrontEnd($stat) {
		update_option('showInVMBFrontend', $stat);
	}
	
	//Show in Backend
	public static function getShowInBackEnd() {
		return get_option('showInVMBBackend');
	}
	
	public static function updateShowInBackEnd($stat) {
		update_option('showInVMBBackend', $stat);
	}
	
	public static function createAdminMenu() {
		add_action('admin_menu', array('ViewMyBrowser', 'viewmybrowser_menu_create'));
	}

	public static function showViewMyBrowserUI() {
		if(self::$showInFrontEnd && self::$siteID != "") {
			add_action('wp_head', array('ViewMyBrowser', 'viewmybrowser_add_label'));
		}
	}

	public static function getCodeSnippet($hidden) {
		return '<script type="text/javascript">'.
			"var _vmb = _vmb || {};".
			"_vmb.source = window.location.protocol + '//app.viewmybrowser.com/';".
			"_vmb.siteID = '" . htmlspecialchars(self::$siteID) . "';".
			"_vmb.clientID = '" . htmlspecialchars(self::$clientID) . "';".
			"_vmb.hidden = " . ($hidden  == true ? 'true' : 'false') . ";".

			"(function() {".
			"var vmb = document.createElement('script'); vmb.type = 'text/javascript'; vmb.async = false; vmb.charset='utf-8';".
			"vmb.src = _vmb.source + 'client.js';".
			"var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(vmb, s);".
			"})();".
		"</script>";
	}

	/* Frontend */

	//Einbinden der Augen
	public function viewmybrowser_add_label($content) {
		echo ViewMyBrowser::getCodeSnippet(false);
	}

	/* Admin Panel */

	//Init-AdminPanel
	public function viewmybrowser_menu_create() {
		if (ViewMyBrowser::$showInBackEnd && self::$siteID != "") {
			add_action('admin_head', array('ViewMyBrowser', 'viewmybrowser_add_label'));
		}
		add_menu_page(
			'ViewMyBrowser',
			'ViewMyBrowser', 'administrator',
			__FILE__,
			array('ViewMyBrowser', 'viewmybrowser_settings_page'),
			plugins_url('vmb_ico.png', __FILE__)
		);
		if (self::$showInBackEnd) {
			add_submenu_page(__FILE__, 
					'',
					'Live Support',
					'manage_options',
					'javascript:viewMyBrowser.show();'
			);
		}
	}

	public function viewmybrowser_settings_page() {
		if (self::$updated) {
			echo "<div class='updated'> \n";
			echo "<p><strong>".__('Settings saved.')."</strong></p>";
			echo "</div> \n";
		}
		if (self::$siteID == "") {
			echo "<div class='updated'> \n";
			echo "<p><strong>".VMBTranslate::translate('missingSiteID')."</strong></p>";
			echo "</div> \n";
		}
		print '
		<div class="wrap">
			<style type="text/css">
				td {
					padding: 5px;
				}
				
				table {
					color: #464646;
					font-size: 1.1em;
					font-family: arial, verdana, sans-serif;
				}
			</style>
			<div id="icon-options-general" class="icon32">
				<br>
			</div>
				<h2>ViewMyBrowser '.VMBTranslate::translate('settings').'</h2>
				<br />
				<form action="" method="POST"  style="border: 1px solid #999999; padding: 5px;">
					<table>
						<tr>
							<td>Site-ID:</td>
							<td><input type="text" name="siteID" value="'.htmlspecialchars(self::getSiteID()).'" style="width: 500px;"/></td>
						</tr>
						<tr>
							<td></td>
							<td>'.VMBTranslate::translate('getSiteID').'</td>
						</tr>
						<tr>
							<td></td>
							<td><a href="http://www.viewmybrowser.com/#signup" target="_blank">' . VMBTranslate::translate('here') . '</a> ' . VMBTranslate::translate('create') . '</td>
						</tr>
						<tr>
							<td><input type="checkbox" name="showInVMBBackend" value="1"'.(self::$showInBackEnd ? ' checked="checked"' : '').' /></td>
							<td><img src="' . self::$plugin_url . 'img/backend.png"></img></td>
							<td>'.VMBTranslate::translate('backend').'</td>
						</tr>
						<tr>
							<td><input type="checkbox" name="showInVMBFrontend" value="1"'.(self::$showInFrontEnd ? ' checked="checked"' : '').' /></td>
							<td><img src="' . self::$plugin_url . 'img/frontend.png"></img></td>
							<td>'.VMBTranslate::translate('frontend').'</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="' . VMBTranslate::translate('update') . '" />
						<input type="hidden" name="action" value="update" />
					</p>
				</form>
		</div>';
	}
}

class VMBTranslate {
	public static $data = array(
		'de' => array(
						'update'=>'Aktualisiere ViewMyBrowser Einstellungen',
						'backend'=>'ViewMyBrowser Widget im Adminbereich aktivieren',
						'frontend'=>'ViewMyBrowser Widget im Blog aktivieren',
						'create'=>'können sie ihren eigenen ViewMyBrowser Account erstellen',
						'here'=>'Hier',
						'getSiteID'=>'Woher bekomme ich eine ViewMyBrowser Site-ID?',
						'settings'=>'Einstellungen',
						'missingSiteID'=>'Keine Site-ID vorhanden. Bitte trangen sie eine Site-ID in den ViewMyBrowser Einstellungen ein!'
						),
		'en' => array(
						'update'=>'Update ViewMyBrowser Settings',
						'backend'=>'Enable ViewMyBrowser widget in Wordpress',
						'frontend'=>'Enable ViewMyBrowser widget in Website.',
						'create'=>'you can create your own ViewMyBrowser account.',
						'here'=>'Here',
						'getSiteID'=>'Where do I get my ViewMyBrowser Site-ID?',
						'settings'=>'Settings',
						'missingSiteID'=>'Missing ViewMyBrowser Site-ID. Please set your Site-ID in ViewMyBrowser Settings!'
						)
	);
	
	public static function translate($key) {
		$lang = strtolower(substr((WPLANG != '' ? WPLANG : 'en'), 0, 2));
		if ($lang != 'de') {
			$lang = 'en';
		}
		$result = self::$data[$lang][$key];
		return isset($result) ? $result : $key;
	}
}

function ViewMyBrowser_install() {
	add_option('siteID', '');
	add_option('showInVMBFrontend', false);
	add_option('showInVMBBackend', true);
}

function ViewMyBrowser_uninstall() {
	delete_option('siteID');
	delete_option('showInVMBFrontend');
	delete_option('showInVMBBackend');
}

register_activation_hook(__FILE__, 'ViewMyBrowser_install');
register_deactivation_hook(__FILE__, 'ViewMyBrowser_uninstall');

?>
