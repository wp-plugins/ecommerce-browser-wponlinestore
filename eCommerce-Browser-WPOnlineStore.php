<?php
/*
Plugin Name: eCommerce Browser (WPOnlineStore)
Plugin URI: http://www.eCommerceBrowser.com/
Description: eCommerce Browser Facebook plug-in, allows you to quickly start advertising your WP Online Store <a href="http://www.wponlinestore.com/" target="_blank">www.wponlinestore.com</a> via your Facebook page. It's designed so that after initial installation, the system will automatically keep up-to-date with your product catalog without the need to maintain a separate product list!
Author: Bright Software Solutions
Version: 1.0
Author URI: http://www.brightsoftwaresolutions.com/
*/

class fb_browser {
	var $fb_browser_key = '';
	var $fb_browser_permlink ='';
	private static $instance;
    private function __construct()   {}
    public function __clone() {trigger_error('Clone is not allowed.', E_USER_ERROR);}
    public function __wakeup() {trigger_error('Unserializing is not allowed.', E_USER_ERROR);}

    public static function getFBBrowser() //singleton
    {
        if (!isset(self::$instance)) {
            $className = __CLASS__;
            self::$instance = new $className;
			self::$instance->load_settings();
        }
        return self::$instance;
    }
	
	function install()	{
		//Create Plugin Key
		$this->fb_browser_key = $this->genRandomString();
		add_option('fb_browser_key', $this->fb_browser_key, 'eCommerce Browser Plugin Key');
		add_option('fb_browser_permlink', '/shop/', 'WP Online Shop Permlink');
	}
	
	private function genRandomString() {
			 $length = 10;
			 $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
			 $string = '';   		 
			for ($p = 0; $p < $length; $p++) {
				 $string .= $characters[mt_rand(0, strlen($characters)-1)];
			 }		 
			return $string;
	}
		
	function load_settings() {	
		$this->fb_browser_key = get_option('fb_browser_key');
		$this->fb_browser_permlink = get_option('fb_browser_permlink');
	}
	
	function save_settings() {
			$this->fb_browser_key = stripslashes($_POST['fb_browser_key']);		
			$this->fb_browser_permlink = stripslashes($_POST['fb_browser_permlink']);		
			update_option('fb_browser_key', $this->fb_browser_key);	
			update_option('fb_browser_permlink', $this->fb_browser_permlink);
			header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=eCommerceBrowser-OnlineStore.php&updated=true');
			die();
	}

	function display_settings() {
		print('
				<div class="wrap">
					<h2>'.__('eCommerce Browser for WP Online Store', 'fb_browser').'</h2>
					<form name="fb_browser" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
						<fieldset class="options">
				
							<p>
								<label for="fb_browser_key">'.__('Plugin key is used during Facebook Application setup, feel free to change this unique key a secure alternative if required. :', 'fb_browser').'</label>
								<input type="text" size="35" name="fb_browser_key" id="fb_browser_key" value="'.$this->fb_browser_key.'" />
							</p>
							<p>
								<label for="fb_browser_permlink">'.__('Please enter the PermLink of your online shop page', 'fb_browser').'</label>
								<input type="text" size="35" name="fb_browser_permlink" id="fb_browser_permlink" value="'.$this->fb_browser_permlink.'" /> ( e.g. /shop/)
							</p>
							<p><a href="'.get_bloginfo('wpurl').'/?fb_action=data_feed&n=zc_browse&p='.$this->fb_browser_key.'">'.__('Display Data Feed', 'fb_browser').'</a></p>
							<input type="hidden" name="fb_action" value="save_settings" />
						</fieldset>
						<p class="submit">
							<input type="submit" name="submit" value="'.__('Update eCommerce Browser Settings', 'fb_browser').'" />
						</p>
					</form>
				</div>
		');
	}

	function uninstall(){
		delete_option('fb_browser_key');
		delete_option('fb_browser_permlink');
		
	}

	function GenerateDataFeed(){
		require_once WP_PLUGIN_DIR . '/'.basename(dirname(__FILE__)).'/fb_browse.php';
		GenerateFeed($this->fb_browser_key);
		die();
	}
}
	function fb_browser_install() {
		//If key already exists skip install
		$value = get_option('fb_browser_key');
		if (!isset($value) || $value==''){
			$fb_browser = fb_browser::getFBBrowser();
			$fb_browser->install();
		}		
	}
	register_activation_hook(__FILE__,'fb_browser_install');
	
	function fb_browser_remove() {	
		$fb_browser = fb_browser::getFBBrowser();
		$fb_browser->uninstall();	
	}		
	register_deactivation_hook( __FILE__, 'fb_browser_remove' );
	
	//Handler Requests
	function fb_browser_request_handler() {		
		if (!empty($_REQUEST['fb_action'])) {
			switch($_REQUEST['fb_action']) {
				case 'save_settings': 
					$fb_browser = fb_browser::getFBBrowser();
					$fb_browser->save_settings();
					break;
				case 'data_feed':
					$fb_browser = fb_browser::getFBBrowser();
					$fb_browser->GenerateDataFeed();
					break;
			}
		}
	}
	add_action('init', 'fb_browser_request_handler', 99);
	
	//Create Admin Menu Item
	function settings_admin() {
		if (function_exists('add_options_page')) {
			add_options_page(
				__('eCommerce Browser', 'fb_browser')
				, __('eCommerce Browser', 'fb_browser')
				, 10
				, basename(__FILE__)
				, 'fb_browser_settings'
			);
		}
	}
	
	function fb_browser_settings() {
		$fb_browser = fb_browser::getFBBrowser();
		$fb_browser->display_settings();
	}
	add_action('admin_menu', 'settings_admin');
	
	//Check if fb_browse.php and redirect to datafeed handler
	function fb_browser_redirect() {
		$URL =  $_SERVER['REQUEST_URI'];
		$Query = $_SERVER['QUERY_STRING'];
		$pos = strpos($URL,'fb_browse.php');			
		if ($pos!== false){	
			 $location = get_bloginfo('wpurl') ."/?fb_action=data_feed&" . $Query;
			wp_redirect( $location, '301');
			exit;
		}	
		$pos = strpos($URL,'fb_redirect.php');			
		if ($pos!== false){	
			$fb_browser = fb_browser::getFBBrowser();			
			 $location = get_bloginfo('wpurl') . $fb_browser->fb_browser_permlink . "?slug=product_info.php&" . $Query;
			wp_redirect( $location, '301');
			exit;
		}
		
	}
	add_action('template_redirect', 'fb_browser_redirect');

?>