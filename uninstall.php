<?php
/*
* WPGear. Users Login Monitor
* uninstall.php
*/

	// if uninstall.php is not called by WordPress, die
	if (!defined('WP_UNINSTALL_PLUGIN')) {
		die;
	}
	
	if (!function_exists ('UsersLoginMonitor_Check_Plugin_Installed')) {
		include_once(__DIR__ .'/includes/functions.php');
	}	

	// Удаляем настройки Плагина
	if (UsersLoginMonitor_Check_Plugin_Installed ('users-login-monitor-pro')) {
		// Нельзя удалять некоторые Общие Настройки, т.к. имеется Плагин "users-login-monitor-pro"
	} else {	
		delete_option('ulm_dashboard_lastlogin');
		delete_option('ulm_digest');
		delete_option('ulm_email');
		delete_option('ulm_digest_date');
		
		// Удаляем метаполя Плагина у всех Пользователей
		global $wpdb;
		$ulm_usermeta_table = $wpdb->prefix .'usermeta';
		$Query = "DELETE FROM $ulm_usermeta_table WHERE meta_key IN ('ulm_browser', 'ulm_lastlogin', 'ulm_triger', 'ulm_user_ip')";
		
		$wpdb->query($Query);		
	}