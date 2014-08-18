<?php
/*

Plugin Name: WP Marketing
Plugin URI: http://WPMarketing.guru
Description: WPMarketing is a suite of apps that helps you engage your customers, create landing pages, rocket your conversions, connect with your visitors, and boost your profits. New Apps are added on a regular basis.
Version: 1.0
Contributors: dallas22ca
Author: Dallas Read
Author URI: http://www.DallasRead.com
Text Domain: wpmarketing
Tags: marketing, customer support, customer service
Requires at least: 3.9.1
Tested up to: 3.9.1
Stable tag: trunk
License: MIT

Copyright (c) 2014 Dallas Read.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

ini_set("display_errors",1);
ini_set("display_startup_errors",1);
error_reporting(-1);

class WPMarketing {
  public static $wpmarketing_instance;
	const version = "1.0";
	const db = "1.0";

  public static function init() {
    if ( is_null( self::$wpmarketing_instance ) ) { self::$wpmarketing_instance = new WPMarketing(); }
    return self::$wpmarketing_instance;
  }

  private function __construct() {
		global $mustache;
		define("WPMARKETING_ROOT", dirname(__FILE__));
		
		require_once WPMARKETING_ROOT . "/admin/php/vendor/Mustache/Autoloader.php";
		Mustache_Autoloader::register();
		$mustache = new Mustache_Engine(array(
			"loader" => new Mustache_Loader_FilesystemLoader(WPMARKETING_ROOT . "/public/php/templates/sway_page/widgets")
		));
		
    add_action( "admin_menu", array( $this, "menu_page" ) );
		add_action( "admin_init", array( $this, "admin_footer" ) );
		add_action( "plugins_loaded", array( $this, "db_check" ) );
		add_filter( "template_include", array( $this, "sway_page_template_path") );
		
		add_action( "wp_enqueue_scripts", array( $this, "wp_enqueue_scripts") );
		add_action( "wp_footer", array( $this, "wp_footer") );
		
		add_action( "wp_ajax_unlock", array( $this, "unlock" ) );
		add_action( "wp_ajax_start_free_trial", array( $this, "start_free_trial" ) );
		
		add_action( "wp_ajax_sway_page_create", array( $this, "sway_page_create" ) );
		add_action( "wp_ajax_sway_page_show", array( $this, "sway_page_show" ) );
		add_action( "wp_ajax_sway_page_update", array( $this, "sway_page_update" ) );
		
		add_action( "wp_ajax_nopriv_convert_alert_track", array( $this, "convert_alert_track" ) );
		add_action( "wp_ajax_convert_alert_track", array( $this, "convert_alert_track" ) );
		add_action( "wp_ajax_convert_alert_status", array( $this, "convert_alert_status" ) );
		add_action( "wp_ajax_convert_alert_poll", array( $this, "convert_alert_poll" ) );
		
		register_activation_hook( __FILE__, array( $this, "db_check" ) );
    register_uninstall_hook( __FILE__, array( $this, "uninstall" ) );
  }
  
  public static function menu_page() {
    add_menu_page( "WP Marketing", "Marketing", 7, "wpmarketing", array("WPMarketing", "admin_panel"), "dashicons-editor-expand", 25 );
  }
  
  public static function admin_panel() {
		global $wpmarketing;
		global $just_activated;
		global $mustache;
		
    WPMarketing::parse_params();
    $wpmarketing = WPMarketing::settings();
		
		if (!isset($wpmarketing["subscriber_email"]) || $wpmarketing["subscriber_email"] == "") {
			require_once "admin/php/activate.php";
		} else {
			require_once "admin/php/structure.php";
		}
  }
	
	public static function admin_footer() {
		wp_register_style( "wpmarketing_style", plugins_url("admin/css/style.css", __FILE__) );
		wp_enqueue_style( array( "wpmarketing_style", "thickbox" ) );

		wp_register_script( "wpmarketing_script", plugins_url("admin/js/admin.js", __FILE__) );
		wp_register_script( "mustache", plugins_url("admin/js/vendor/mustache.js", __FILE__) );
		wp_register_script( "wpmarketing_sway_page", plugins_url("admin/js/apps/sway_page.js", __FILE__) );
		wp_register_script( "wpmarketing_convert_alert", plugins_url("admin/js/apps/convert_alert.js", __FILE__) );
		
		wp_enqueue_script( array( "wpmarketing_sway_page", "wpmarketing_convert_alert", "wpmarketing_script", "mustache", "thickbox", "jquery-ui-sortable", "jquery" ) );
	}
	
	public static function wp_enqueue_scripts() {
		wp_enqueue_script( array( "jquery" ) );
	}
	
	public static function wp_footer() {
		global $wpmarketing;
		$wpmarketing = WPMarketing::settings();
		
		if (array_key_exists("convert_alert_status", $wpmarketing) && $wpmarketing["convert_alert_status"] == "on") {
			require_once WPMARKETING_ROOT . "/public/php/apps/convert_alert.php";
		}
	}
	
	public static function db_check() {
		global $wpdb;
		
		if (get_option("wpmarketing_db_version") != WPMarketing::db) {
			
			$charset_collate = '';

			if ( ! empty( $wpdb->charset ) ) {
			  $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
			}

			if ( ! empty( $wpdb->collate ) ) {
			  $charset_collate .= " COLLATE {$wpdb->collate}";
			}
    
			$visitors = "CREATE TABLE " . $wpdb->prefix . "wpmarketing_visitors" . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				wpmkey varchar(32) NOT NULL,
				email varchar(255),
				user_id mediumint(9),
				data text NOT NULL,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id),
				INDEX  wpmkey_index (wpmkey ASC),
				INDEX  user_id_index (user_id ASC)
			) $charset_collate;";
			
			$events = "CREATE TABLE " . $wpdb->prefix . "wpmarketing_events" . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				visitor_id mediumint(9) NOT NULL,
				description varchar(255),
				verb varchar(36),
				data text NOT NULL,
				created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				PRIMARY KEY  (id),
				INDEX  visitor_id_index (visitor_id ASC)
			) $charset_collate;";
    
			require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
			dbDelta( $visitors );
			dbDelta( $events );
			update_option( "wpmarketing_db_version", WPMarketing::db );
		}
	}
  
  public static function uninstall() {
    // delete_option("wpmarketing_settings");
  }
  
  public static function settings($update = array()) {
		global $wpmarketing;
		
    if (empty($wpmarketing) || !empty($update)) {
			$settings = get_option("wpmarketing_settings");
			if ($settings == null) { $settings = array(); }
			
	    $defaults = array(
	      "version" => 0,
	      "db_version" => 0,
				"website" => $_SERVER["SERVER_NAME"],
	      "unlock_code" => "",
	      "subscriber_name" => "",
	      "subscriber_email" => "",
				"convert_alert_status" => "on",
				"trial_end_at" => 0
	    );
			
			if (!empty($update) || $wpmarketing != $settings) {
				$wpmarketing = array_merge($defaults, $settings);
				$wpmarketing = array_merge($wpmarketing, $update);
				update_option("wpmarketing_settings", $wpmarketing);
			}    
			
			if ($wpmarketing["unlock_code"] != "") {
				$wpmarketing["status"] = "unlocked";
			} else if ($wpmarketing["trial_end_at"] > time()) {
				$wpmarketing["status"] = "trialing";
			} else {
				$wpmarketing["status"] = "locked";
			}
		}
				
    return $wpmarketing;
  }
	
	public static function unlock() {
		$data = array( "success" => false );
		
    if (isset($_POST["unlock_code"])) {
			$unlock_code = trim($_POST["unlock_code"]);
      $request = new WP_Http;
      $result = $request->request("http://guitarvid.com/activation/wpmarketing/unlock.php?unlock_code=" . $unlock_code);
      $response = json_decode($result["body"]);
			
			if ($response->success == 1) {
        $data = WPMarketing::settings( array( "unlock_code" => $unlock_code ) );
				$data["success"] = true;
      }
		}
		
    die(json_encode($data));
	}
  
  public static function parse_params() {
		global $wpmarketing;
		global $just_activated;
		
    if (isset($_POST["email"]) && is_email($_POST["email"]) && isset($_POST["name"])) {
			WPMarketing::settings( array(
				"subscriber_name" => trim($_POST["name"]),
				"subscriber_email" => sanitize_email(trim($_POST["email"]))
			) );
			$just_activated = true;
		}
  }
	
	public static function start_free_trial() {
		global $wpmarketing;
		$data = array( "success" => false );
		
    if ($wpmarketing["trial_end_at"] == 0) {
      $data = WPMarketing::settings( array( "trial_end_at" => strtotime("+7 day") ) );
			$data["success"] = true;
		}
		
    die(json_encode($data));
	}
	
	/*
		SwayPage
	*/
	
	public static function sway_page_template_path($template) {
		global $post;

		if ( !isset( $post ) ) { return $template; }
		
		if ( get_post_meta( $post->ID, "_wp_page_template", true ) == "sway_page_template.php" ) {
			$file = plugin_dir_path( __FILE__ ) . "public/php/templates/sway_page_template.php";
			if( file_exists( $file ) ) { return $file; }
		}
		
		return $template;
	}
	
	public static function sway_page_show($id = false) {
		$data = array("success" => false);
		$response = get_post($_POST["id"], ARRAY_A);
		
		if (!empty($response)) {
			$data = $response;
			$data["post_content"] = stripslashes_deep(json_decode($response["post_content"]));
			$data["success"] = true;
		}
		
		die(json_encode($data));
	}
	
	public static function sway_page_create() {
		$data = array(
		  "post_title"    => $_POST["title"],
			"post_name"			=> $_POST["name"],
			"post_type"			=> "page",
		  "post_status"   => "draft"
		);
		
		$response = wp_insert_post( $data );
		$data["success"] = false;
		
		if ($response != 0) {
			$data["id"] = $response;
			$data["success"] = true;
			update_post_meta($data["id"], "_wp_page_template", "sway_page_template.php");
		}
		
		die(json_encode($data));
	}
	
	public static function sway_page_update() {
		$data = array(
			"ID" => $_POST["id"],
			"post_content" => addslashes(json_encode($_POST["post_content"])),
			"post_title" => $_POST["post_title"],
			"post_name" => $_POST["post_name"],
			"post_status" => $_POST["post_status"]
		);
		
		wp_update_post($data);
		$data["success"] = true;
		
		die(json_encode($data));
	}
	
	/*
		ConvertAlert
	*/
	
	public static function remote_ip() {
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$remote_ip = $_SERVER["HTTP_CLIENT_IP"];
		} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			$remote_ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			$remote_ip = $_SERVER["REMOTE_ADDR"];
		}
		if (inet_pton($remote_ip) === false) { $remote_ip = "0.0.0.0"; }
		return $remote_ip;
	}
	
	public static function request_path() {
	  $url = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://' . $_SERVER["SERVER_NAME"] : 'https://' . $_SERVER["SERVER_NAME"];
	  $url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":" . $_SERVER["SERVER_PORT"] : "";
	  $url .= $_SERVER["REQUEST_URI"];
	  return $url;
	}
	
	public static function convert_alert_status() {
		global $wpmarketing;
		
    $data = WPMarketing::settings( array(
			"convert_alert_status" => $_POST["convert_alert_status"] == "off" ? "off" : "on",
			"success" => true
		) );
		
		die(json_encode($data));
	}
	
	public static function convert_alert_track() {
		global $wpdb;
		global $wpmarketing;
		
		$visitor = array();
		$event = array();
		$user = false;
		$events_table = $wpdb->prefix . "wpmarketing_events";
		$visitors_table = $wpdb->prefix . "wpmarketing_visitors";
		
		if (isset($_POST["visitor"]["wpmkey"])) {
			$wpmkey = $_POST["visitor"]["wpmkey"];
			$visitor = $wpdb->get_row("SELECT * FROM $visitors_table WHERE wpmkey = '$wpmkey'", ARRAY_A);
		}
		
		if (empty($visitor) && isset($_POST["visitor"]["email"])) {
			$email = $_POST["visitor"]["email"];
			$visitor = $wpdb->get_row("SELECT * FROM $visitors_table WHERE email = '$email'", ARRAY_A);
			
			if (empty($visitor)) {
				$user = get_user_by_email( $email );
			}			
		}
		
		if (empty($visitor) && is_user_logged_in()) {
			$user = wp_get_current_user();
		}
		
		if (empty($visitor)) {
			$ip = WPMarketing::remote_ip();
			if ($ip == "::1") { $ip = "127.0.0.1"; }
	    $request = wp_remote_get("http://freegeoip.net/json/$ip");
			if (!is_wp_error($request)) { $geo = json_decode($request["body"]); }
			
			$visitor_data = array(
				"ip" => $ip,
        "user_agent" => $_SERVER["HTTP_USER_AGENT"]
			);
			
			$user_agent = WPMarketing::analyze_user_agent($visitor_data["user_agent"]);
			$visitor_data["os"] = $user_agent["os"];
			$visitor_data["browser"] = $user_agent["browser"];
			
			if (is_object($geo) && property_exists($geo, "city")) {
        $visitor_data["city"] = $geo->city;
        $visitor_data["province"] = $geo->region_name;
        $visitor_data["country"] = $geo->country_name;
        $visitor_data["country_code"] = strtolower($geo->country_code);
        $visitor_data["province_code"] = $geo->region_code;
        $visitor_data["latitude"] = $geo->latitude;
        $visitor_data["longitude"] = $geo->longitude;
			} else {
				$visitor_data["country_code"] = "undeterminable";
			}
			
			$visitor["wpmkey"] = WPMarketing::generate_wpmkey();
      $visitor["created_at"] = date("Y-m-d h:i:s");
      $visitor["updated_at"] = date("Y-m-d h:i:s");
			
			if ($user) {
				$visitor["email"] = $user->user_email;
				$visitor["user_id"] = $user->ID;
				$visitor_data["display_name"] = $user->display_name;
				$visitor_data["nicename"] = $user->user_nicename;
				$visitor_data["login"] = $user->user_nicename;
				$visitor_data["registered"] = $user->user_registered;
			}
			
			if (isset($visitor["email"])) {
				$visitor_data["avatar"] = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $visitor["email"] ) ) );
			} else {
				$visitor_data["avatar"] = plugins_url("admin/imgs/undeterminable.jpg", __FILE__);
			}
			
			$visitor["data"] = addslashes(json_encode($visitor_data));

			$wpdb->insert( $visitors_table, $visitor );
			$visitor["id"] = $wpdb->insert_id;
		}
		
		$description = $_POST["description"];
		$verb = $_POST["verb"];
		unset($_POST["description"]);
		unset($_POST["verb"]);
		unset($_POST["action"]);
		unset($_POST["visitor"]);
		
		$event = array(
			"visitor_id" => $visitor["id"],
			"description" => $description,
			"verb" => $verb,
			"data" => addslashes(json_encode($_POST)),
			"created_at" => date("Y-m-d h:i:s")
		);
		
		$wpdb->insert( $events_table, $event );
		$event["id"] = $wpdb->insert_id;
		
		$visitor["data"] = json_decode(stripslashes_deep($visitor["data"]));
		$event["data"] = json_decode(stripslashes_deep($event["data"]));
    $data = array(
			"visitor" => $visitor,
			"event" => $event,
			"success" => !empty($visitor) && !empty($event)
		);
		
		die(json_encode($data));
	}
	
	public static function analyze_user_agent($user_agent) {
		$results = array( "os" => "windows", "browser" => "chrome" );
		return $results;
	}
	
	public static function generate_wpmkey() {
		return md5(uniqid(rand() * rand(), true));
	}
	
	public static function convert_alert_poll() {
		global $wpmarketing;
		global $wpdb;
		
		$last_event_id = $_POST["last_event_id"];
		$events_table = $wpdb->prefix . "wpmarketing_events";
		$visitors_table = $wpdb->prefix . "wpmarketing_visitors";
		$data = array();
		
		if ($last_event_id == 0) {
			$events = $wpdb->get_results("SELECT * FROM $events_table ORDER BY id DESC LIMIT 6", ARRAY_A);
			$events = array_reverse($events);
		} else {
			$events = $wpdb->get_results("SELECT * FROM $events_table WHERE id > $last_event_id ORDER BY id ASC", ARRAY_A);
		}

		foreach ($events as $e) {
			$visitor_id = $e["visitor_id"];
			$event = json_decode(stripslashes_deep($e["data"]), true);
			$visitor = $wpdb->get_row("SELECT * FROM $visitors_table WHERE id = $visitor_id", ARRAY_A);
			
			$event["id"] = $e["id"];
			$event["description"] = $e["description"];
			$event["verb"] = $e["verb"];
			$event["visitor"] = json_decode(stripslashes_deep($visitor["data"]), true);
			$event["visitor"]["id"] = $visitor_id;
			
			array_push($data, $event);
		}
		
		die(json_encode($data));
	}
}

//delete_option("wpmarketing_settings");
WPMarketing::init();
?>
