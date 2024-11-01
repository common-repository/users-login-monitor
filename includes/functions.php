<?php
/*
* WPGear. Users Login Monitor
* functions.php
*/
	/* Request Get_WhoIs.
	----------------------------------------------------------------- */
	function UsersLoginMonitor_Get_WhoIs ($IP) {
		global $wp_version;
		 
		$CURL_Query = "http://ip-api.com/json/$IP";
		
		$CURL_Arg = array(
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
			'blocking'    => true,
			'headers'     => array(),
			'cookies'     => array(),
			'body'        => null,
			'compress'    => false,
			'decompress'  => true,
			'sslverify'   => false,
			'stream'      => false,
			'filename'    => null
		);
			
		$CURL_Answer = wp_remote_get ($CURL_Query, $CURL_Arg);

		if (is_wp_error ($CURL_Answer)) {
			$Error_Eessage = $CURL_Answer -> get_error_message();
			
			return null;
		} else {
			if ($CURL_Answer) {
				$CURL_Answer_Code 		= isset($CURL_Answer['response']['code']) ? $CURL_Answer['response']['code'] : 'N/A';
				$CURL_Answer_Message	= isset($CURL_Answer['response']['message']) ? $CURL_Answer['response']['message'] : 'N/A';
				$CURL_Response 			= isset($CURL_Answer['body']) ? $CURL_Answer['body'] : 'N/A';	
				
				return $CURL_Response;
			}						
		}		
	}
	
	/* TimeStamp
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_Get_TimeStamp ($Format = "Y-m-d H:i:s") {
		$TimeZone 	= get_option('gmt_offset');
		$TimeStamp 	= date($Format, strtotime($TimeZone ." Hours"));		

		return $TimeStamp;
	}

	/* Get User IP
	----------------------------------------------------------------- */
	function UsersLoginMonitor_Get_UserIP () {
		$User_IP = isset ($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$User_IP = isset ($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $User_IP;

		return $User_IP;
	}
	
	/* Get Browser Info
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_Get_BrowserInfo () {
		// Try use Browscap PHP Lib.        
		
		$Browser_Log = '---';
		
		if (function_exists('get_browser') && ini_get('browscap')) {			
			$browser = get_browser(null, true);
			
			if ($browser) {
				$Browser_Log = $browser['browser'] .', ' .$browser['platform'] .', ' .$browser['device_type'];
			} else {
				// Try use Plugin: 'Quick Browscap'. https://wordpress.org/plugins/quick-browscap/
				global $quick_browscap;

				if (isset($quick_browscap) && is_object($quick_browscap)) {
					$bw_info = $quick_browscap->get_browser(null, true);

					$Browser_Log = $bw_info['Browser'] .', ' .$bw_info['Platform'];
				}
			}
		} 
		
		return $Browser_Log;		
	}	

	/* Check Plugin Installed
	----------------------------------------------------------------- */		
	function UsersLoginMonitor_Check_Plugin_Installed($Plugin_Slug = null) {
		$Result = false;
		
		if ($Plugin_Slug) {
			if (! function_exists ('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			
			$Plugins = get_plugins();
			
			foreach ($Plugins as $Plugin) {
				$Plugin_TextDomain = $Plugin['TextDomain'];
				if ($Plugin_TextDomain == $Plugin_Slug) {
					$Result = true;
				}
			}			
		}	
		
		return $Result;
	}
	
	/* RealCount
	----------------------------------------------------------------- */		
	function UsersLoginMonitor_RealCount($X) {
		// Решение проблемы с Ошибкой Обратной Совместимости PHP7*. Count()
		// теперь, в PHP7* и выше, count() работает только с параметром типа array. Возвращает не 0, если не array, а Ошибку.
		$Result = 0;
		
		if (is_array ($X)) {
			$Result = count($X);
		}
		
		return $Result;
	}	
	
	/* Check User Exist
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_User_Exists($User_ID) {
		global $wpdb;
		$UsersLoginMonitor_Table_Users = $wpdb->prefix .'users';
		
		$Query = "SELECT COUNT(*) FROM $UsersLoginMonitor_Table_Users WHERE ID = %d";
		
		$Result = $wpdb->get_var ($wpdb->prepare($Query, $User_ID));
		
		if ($Result == 1) {
			return true; 
		} else {
			return false; 
		}
	}	

	/* Get Log User - Data.
	----------------------------------------------------------------- */
	function UsersLoginMonitor_Get_Log_User_Data ($Mode, $User_ID = null) {
		global $wpdb;
		global $UsersLoginMonitor_Dashboard_LastLogin;
		
		$UsersLoginMonitor_table = $wpdb->prefix .'users_login_monitor';

		$Date = UsersLoginMonitor_Get_TimeStamp ("Y-m-d");
	
		$Records = array();
		
		// Текущая Инф. по конкретному Пользователю.
		if ($Mode == 'last_record' && $User_ID) {			
			$Query = "
				SELECT *
				FROM $UsersLoginMonitor_table 
				WHERE (
					user_id = %d
				)
				ORDER BY id DESC
			";	
			
			$Records = $wpdb->get_row ($wpdb->prepare ($Query, $User_ID));			
		}
		
		// Activity
		if ($Mode == 'activity') {
			$MembersList = implode (",", $User_ID);
			
			$Query = "
				SELECT MAX(id) as id
				FROM $UsersLoginMonitor_table 
				WHERE user_id IN ($MembersList) AND status = 'logon'
				GROUP BY user_id
				ORDER BY id DESC
			";
			
			// $Log_Records = $wpdb -> get_col ($wpdb -> prepare ($Query, $MembersList));
			// prepare with WHERE IN - some problems ((
			$Log_Records = $wpdb -> get_col ($Query);
			
			if ($Log_Records) {
				$Log_Records_List = implode (",", $Log_Records);

				if ($Log_Records_List) {			
					$Query = "
						SELECT *
						FROM $UsersLoginMonitor_table 
						WHERE id IN ($Log_Records_List) AND status = 'logon'
						ORDER BY date DESC
					";							

					$Records = $wpdb -> get_results ($Query, ARRAY_A);					
				}
			}			
		}

		return $Records;
	}	
	
	/* Get Count Active Users
	----------------------------------------------------------------- */
	function UsersLoginMonitor_Get_Counts_Active_Users () {
		global $UsersLoginMonitor_Activity_Control_Period;
		
		$ULM_Counts_Active_Users = array (
			'all' => 1,
			'on' => 1,
			'off' => 0,
		);
		
		$ULM_Users_Active = get_transient ('ulm_users_active');
		
		if ($ULM_Users_Active) {
			$ULM_Counts_Active_Users['all']	= 0;
			$ULM_Counts_Active_Users['on']	= 0;
			$ULM_Counts_Active_Users['off']	= 0;
			
			foreach ( $ULM_Users_Active as $Member ) {
				if (isset($Member['last_time']) && $Member['last_time'] > time() - $UsersLoginMonitor_Activity_Control_Period ){
					$ULM_Counts_Active_Users['on'] ++;
				} else {
					$ULM_Counts_Active_Users['off'] ++;				
				}
			}
			
			if ($ULM_Counts_Active_Users['on'] == 0) {
				// Иногда, вдруг такое бывает. Почему - непонятно.
				$ULM_Counts_Active_Users['on'] = 1;
			}
			$ULM_Counts_Active_Users['all'] = $ULM_Counts_Active_Users['on'] + $ULM_Counts_Active_Users['off'];		
		}

		return $ULM_Counts_Active_Users;
	}	