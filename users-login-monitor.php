<?php
/*
Plugin Name: Users Login Monitor
Plugin URI: wpgear.xyz/users-login-monitor
Description: Ext Security. Dashboard & Daily-Digest about users activity whith WhoIs. Check out the features of the PRO version: <a href="http://wpgear.xyz/users-login-monitor-pro/">"Users Login Monitor PRO"</a>.
Version: 4.16
Author: WPGear
Author URI: http://wpgear.xyz
License: GPLv2
*/
	
	$UsersLoginMonitor_Plugin_URL = plugin_dir_url( __FILE__); // со слэшем на конце
	
	$admin_email = get_option('admin_email');
	
	$UsersLoginMonitor_Dashboard_LastLogin 		= intval (get_option('ulm_dashboard_lastlogin', 10 ));		// Количество пользователей в виджете консоли.	
	$UsersLoginMonitor_Digest_Enable 			= intval (get_option('ulm_digest', 1 ));					// On/Off - ежеденвного дайджеста о логинах.
	$UsersLoginMonitor_Digest_Email 			= get_option('ulm_email', $admin_email );					// Адрес получателя ежеденвного дайджеста о логинах.
	$UsersLoginMonitor_Digest_Date 				= get_option('ulm_digest_date', '0000.00.00' );				// Дата последнего дайджеста.
	$UsersLoginMonitor_Activity_Control_Period	= intval (get_option('ulm_period_control_activity', 60 ));	// Период контроля Активности.

	include_once(__DIR__ .'/includes/functions.php');
	
	/* Admin Console - Styles.
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_admin_style ($hook) {
		$screen = get_current_screen();
		$screen_base = $screen->base;
	
		if ($screen_base == 'dashboard' || $screen_base == 'users-login-monitor/includes/options') {
			global $UsersLoginMonitor_Plugin_URL;
			
			wp_enqueue_style ('ulm_admin_style', $UsersLoginMonitor_Plugin_URL .'admin-style.css');			
			wp_enqueue_style ('fontawesome_4.7.0', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
		}
	}
	add_action ('admin_enqueue_scripts', 'UsersLoginMonitor_admin_style' );	
	
	/* Create plugin SubMenu
	----------------------------------------------------------------- */	
	add_action('admin_menu', 'UsersLoginMonitor_Action_create_menu');	
	function UsersLoginMonitor_Action_create_menu () {
		add_users_page(
			__( 'ULM Options | ', 'textdomain' ),
			__( 'Users Login Monitor', 'textdomain' ),
			'edit_dashboard',
			plugin_dir_path(__FILE__) .'includes/options.php',
			''
		);
	}	
		
	/* Creat new columns in "Users".
	----------------------------------------------------------------- */
	add_filter ('manage_users_columns', 'UsersLoginMonitor_Filter_columns');	
	function UsersLoginMonitor_Filter_columns ($column) {
		$column['last_login'] 	= 'Last Login';
		$column['user_ip'] 		= 'IP Details';
		
		return $column;
	}	
	
	/* After Login
	----------------------------------------------------------------- */
	add_action ('wp_login', 'UsersLoginMonitor_Action_AddToLog_aboutLogin', 10, 2);
	function UsersLoginMonitor_Action_AddToLog_aboutLogin ($user_login, $user) {
		global $wpdb;

		$User_ID 		= $user -> ID;
		$user_login 	= $user -> user_login;
		$display_name	= $user -> display_name;
		
		$timestamp = date ('Y-m-d H:i:s');
		$timezone = get_option ('gmt_offset');
		$TimeStamp_data = strtotime ($timestamp) + $timezone * 3600;
		$TimeStamp_str = date ("Y-m-d H:i:s", $TimeStamp_data);	

		$user_ip = isset ($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
		$user_ip = isset ($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $user_ip;
		$user_ip = isset ($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $user_ip;
        
		// Try use Browscap PHP Lib.
        $browser 	= get_browser(null, true);
		
		$browser_log = '---';
		
		if ($browser) {
			$browser_log = $browser['browser'] .', ' .$browser['platform'] .', ' .$browser['device_type'];
		} else {
            // Try use Plugin: 'Quick Browscap'. https://wordpress.org/plugins/quick-browscap/
            global $quick_browscap;

            if (isset($quick_browscap) && is_object($quick_browscap)) {
                $bw_info = $quick_browscap->get_browser(null, true);

                $browser_log = $bw_info['Browser'] .', ' .$bw_info['Platform'];
            }
        }

		if ($user_ip) {
			$WhoIs = json_decode (UsersLoginMonitor_Get_WhoIs ($user_ip));

			$WhoIs_asn 			= $WhoIs -> as;
			$WhoIs_country 		= $WhoIs -> country;
			$WhoIs_city			= $WhoIs -> city;
			$WhoIs_countryCode 	= $WhoIs -> countryCode;
			$WhoIs_isp 			= $WhoIs -> isp;
			$WhoIs_org 			= $WhoIs -> org;

			$IP_Details = $WhoIs_isp ." | " .$WhoIs_country." | " .$WhoIs_city ." | " .$WhoIs_asn;					
		} else {
			$user_ip = '---';
			$IP_Details = '---';
		}

		$meta_key = 'ulm_lastlogin';
		$meta_value = $TimeStamp_str;
		update_user_meta ($User_ID, $meta_key, $meta_value);
		
		$meta_key = 'ulm_user_ip';
		$meta_value = $user_ip;
		update_user_meta ($User_ID, $meta_key, $meta_value);	

		$meta_key = 'ulm_browser';
		$meta_value = $browser_log;
		update_user_meta ($User_ID, $meta_key, $meta_value);
		
		$meta_key = 'ulm_whois';
		$meta_value = $IP_Details;
		update_user_meta ($User_ID, $meta_key, $meta_value);		

		$meta_key = 'ulm_triger';
		$meta_value = 1;
		update_user_meta ($User_ID, $meta_key, $meta_value);
		
		$meta_key = 'ulm_count_login_total';
		$meta_value = intval (get_user_meta ($User_ID, $meta_key, true));
		update_user_meta ($User_ID, $meta_key, $meta_value + 1);

		$meta_key = 'ulm_count_login_success';
		$meta_value = intval (get_user_meta ($User_ID, $meta_key, true));
		update_user_meta ($User_ID, $meta_key, $meta_value + 1);
	}	
	
	/* Login Failed
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_LoginFailed($username){
		$User = get_user_by('login', $username);
		
		$User_ID = null;
		
		if ($User) {
		   $User_ID = $User->ID;
		} else {
			$User = get_user_by('email', $username);
			
			if ($User) {
			   $User_ID = $User->ID;
			}			
		}
		
		if ($User_ID) {
			$meta_key = 'ulm_count_login_total';
			$meta_value = intval (get_user_meta ($User_ID, $meta_key, true));
			update_user_meta ($User_ID, $meta_key, $meta_value + 1);
		}
	}
	add_action ('wp_login_failed', 'UsersLoginMonitor_LoginFailed');	
	
	/* Create "Last Login" column in "Users".
	----------------------------------------------------------------- */			
	function UsersLoginMonitor_last_login_column ($val, $column_name, $User_ID) {
		if ('last_login' != $column_name ) {
			return $val;
		} else {
			$meta_key = 'ulm_lastlogin';
			$User_LastLogin = get_user_meta ($User_ID, $meta_key, true);
			
			return $User_LastLogin;
		}
	}
	add_filter ('manage_users_custom_column', 'UsersLoginMonitor_last_login_column', 10, 3 );	

	/* Create "IP Details" column in "Users".
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_IP_Detail_column ($val, $column_name, $User_ID) {
		if ('user_ip' != $column_name ) {
			return $val;
		} else {
			$meta_key = 'ulm_user_ip';
			$user_ip = get_user_meta ($User_ID, $meta_key, true);
			
			$meta_key = 'ulm_browser';
			$browser_log = get_user_meta ($User_ID, $meta_key, true);
			
			$IP_Details = $user_ip .' ' .$browser_log;
			
			return $IP_Details;
		}
	}	
	add_filter ('manage_users_custom_column', 'UsersLoginMonitor_IP_Detail_column', 10, 3 );		
	
	/* Make "Last Login" column - Sortable.
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_last_login_column_sortable ($sortable_columns) {
		$sortable_columns['last_login'] = 'last_login';
		return $sortable_columns;
	}
	add_filter ('manage_users_sortable_columns', 'UsersLoginMonitor_last_login_column_sortable' );	
	
	/* Sorting "Last Login" column.
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_last_login_column_orderby ( $user_query ) {	
		global $wpdb, $current_screen;
		if ($current_screen->id != 'users') return;
			
		$usermeta_table = $wpdb->prefix .'usermeta';
		$users_table 	= $wpdb->prefix .'users';
		
		$vars = $user_query->query_vars;	
		
		if($vars['orderby'] == 'last_login') {
			$user_query->query_from = " FROM $users_table INNER JOIN $usermeta_table ON ($users_table.ID = $usermeta_table.user_id)";
			$user_query->query_where = " WHERE $usermeta_table.meta_key = 'ulm_lastlogin'";	
			$user_query->query_orderby = " ORDER BY $usermeta_table.meta_value ". $vars['order'];
		}
		
		return $user_query;	
	}
	add_filter ('pre_user_query','UsersLoginMonitor_last_login_column_orderby');		
	
	/* Create Users-Login-Monitor DashboardWidget
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_Dashboard_Widgets_UsersLogin () {
		if (current_user_can('edit_dashboard')) {
			global $wp_meta_boxes;
			
			wp_add_dashboard_widget('ulm_lastlogin_widget', 'Users Login Monitor', 'UsersLoginMonitor_Dashboard_UsersLogin');			
		}
	}
	add_action ('wp_dashboard_setup', 'UsersLoginMonitor_Dashboard_Widgets_UsersLogin');	
	
	/* Users-Login-Monitor DashboardWidget
	----------------------------------------------------------------- */	
	function UsersLoginMonitor_Dashboard_UsersLogin () {
		global $wpdb;
		global $UsersLoginMonitor_Dashboard_LastLogin;
		
		$users_table 	= $wpdb->prefix .'users';
		$usermeta_table = $wpdb->prefix .'usermeta';

		$Query = "
			SELECT $users_table.ID, $users_table.user_login, $usermeta_table.meta_value as last_login
			FROM $users_table 
			INNER JOIN $usermeta_table ON ($users_table.ID = $usermeta_table.user_id)
			WHERE meta_key = 'ulm_lastlogin' ORDER BY meta_value DESC LIMIT %d";
			
		$users = $wpdb->get_results ($wpdb->prepare ($Query, $UsersLoginMonitor_Dashboard_LastLogin));			
		?>
		
		<table style="width: 100%">
			<tbody style="text-align: left;">
				<th><h3>Last Login</h3></th>
				<th><h3>Login</h3></th>
				<th><h3>WhoIs IP</h3></th>
				<th><h3>Success</h3></th>
				<th><h3>Device</h3></th>
				<?php 
				
				foreach ($users as $user) {
					$User_ID 	= $user->ID;
					$User_Login = $user->user_login;
					
					$meta_key = 'ulm_user_ip';
					$User_IP = get_user_meta( $User_ID, $meta_key, true );
					
					$meta_key = 'ulm_browser';
					$User_Device = get_user_meta( $User_ID, $meta_key, true );

					$meta_key = 'ulm_whois';
					$WhoIs = get_user_meta( $User_ID, $meta_key, true );

					$meta_key = 'ulm_count_login_success';
					$User_Login_Success = intval (get_user_meta ($User_ID, $meta_key, true));

					$meta_key = 'ulm_count_login_total';
					$User_Login_Total = intval (get_user_meta ($User_ID, $meta_key, true));	

					$Login_Counts = '---';
					if ($User_Login_Total > 0) {
						$Login_Counts = round($User_Login_Success * 100 / $User_Login_Total,0);
						$Login_Counts .= "% ($User_Login_Total)";
					}
					?>
					<tr>
						<td>
							<?php echo date('Y-m-d H:i', strtotime($user->last_login));?>
						</td>
						
						<td>
							<a href="<?php echo get_edit_user_link($User_ID); ?>"><?php echo $User_Login; ?></a>
						</td>
						
						<td>
							<span class="ulm_dashboard_widget_whois" title="<?php echo $WhoIs; ?>">
								<?php echo $User_IP; ?>
							</span>
						</td>
						
						<td>
							<span class="ulm_dashboard_widget_counts" title="<?php echo $User_Login_Total; ?>">
								<?php echo $Login_Counts; ?>
							</span>
						</td>						
						
						<td>
							<?php echo $User_Device; ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		
		<script>
			var ULM_Widget = document.getElementById("ulm_lastlogin_widget");
			var ULM_Widget_Header = ULM_Widget.getElementsByTagName("h2")[0];
			
			ULM_Widget_Header.innerHTML = '<span title="Click to open Setup-Option Page"><a href="/wp-admin/users.php?page=users-login-monitor%2Fincludes%2Foptions.php" class="ulm_dashboard_widget_header">Users Login Monitor</a></span>';
		</script>		
	<?php }		

	/* Create Digest and Send, if a new day has come.
	----------------------------------------------------------------- */
	add_action ('init','UsersLoginMonitor_Action_Processing');	
	function UsersLoginMonitor_Action_Processing (){
		global $wpdb, $UsersLoginMonitor_Digest_Enable, $UsersLoginMonitor_Digest_Date, $UsersLoginMonitor_Digest_Email;
		
		if($UsersLoginMonitor_Digest_Enable) {
            $ULM_Digest_OK = false;
			
			$timestamp = date('Y-m-d H:i:s');
			$timezone = get_option('gmt_offset');
			$TimeStamp_data = strtotime ($timestamp) + $timezone * 3600;
			$Today = date ("Y.m.d", $TimeStamp_data);
			$Digest_TimeStamp = date ("Y.m.d  H:i", $TimeStamp_data);			
			
			$UsersLoginMonitor_Digest_Date = get_option('ulm_digest_date', '0000.00.00' );
			
			if ($Today != $UsersLoginMonitor_Digest_Date) {				
				$usermeta_table = $wpdb->prefix .'usermeta';
				$users_table 	= $wpdb->prefix .'users';
				
				$Query = "SELECT * FROM $usermeta_table WHERE meta_key = 'ulm_lastlogin' AND %d ORDER BY meta_value DESC";
				$users = $wpdb->get_results ($wpdb->prepare ($Query, 1));	

				$Report_Table = "
					<table style='width: 100%; border-collapse: collapse; border-style: solid; border-width: 2px;'>
						<tbody>
							<tr style='border-style: solid; border-width: thin; background-color: #4CAF50; color: white;'>
								<th style='border: 1px solid #ddd; padding: 8px; border-bottom-color: grey;'>Login</th>
								<th style='border: 1px solid #ddd; padding: 8px; border-bottom-color: grey;'>Name</th>
								<th style='border: 1px solid #ddd; padding: 8px; border-bottom-color: grey;'>Role</th>
								<th style='border: 1px solid #ddd; padding: 8px; border-bottom-color: grey;'>Last Login</th>
								<th style='border: 1px solid #ddd; padding: 8px; border-bottom-color: grey;'>IP Address</th>
								<th style='border: 1px solid #ddd; padding: 8px; border-bottom-color: grey;'>Device</th>
							</tr>
				";
						
				foreach ($users as $user) {
					$User_ID = $user->user_id;
					
					$meta_key = 'ulm_triger';
					$User_toDigest = get_user_meta ($User_ID, $meta_key, true);	
					
					if ($User_toDigest) {
						// Включаем в Дайджест.
						$ULM_Digest_OK = true;
						
						$LastLogin = date('Y-m-d H:i', strtotime ($user->meta_value));

						$Query = "SELECT user_login, display_name FROM $users_table WHERE ID = %d";						
						$User_Detail = $wpdb->get_row ($wpdb->prepare ($Query, $User_ID));
						
						$User_Login 		= $User_Detail->user_login;
						$User_DisplayName	= $User_Detail->display_name;
						
						$meta_key = 'ulm_user_ip';
						$User_IP = get_user_meta ($User_ID, $meta_key, true);
						
						$meta_key = 'ulm_browser';
						$Browser_Log = get_user_meta ($User_ID, $meta_key, true);
						
						$User_Info 	= get_userdata ($User_ID);
						$User_Roles = implode (', ', $User_Info -> roles);				

						$Report_Table .= "
							<tr>	
								<td style='border: 1px solid #ddd; padding: 8px;'>$User_Login</td>
								<td style='border: 1px solid #ddd; padding: 8px;'>$User_DisplayName</td>
								<td style='border: 1px solid #ddd; padding: 8px;'>$User_Roles</td>
								<td style='border: 1px solid #ddd; padding: 8px;'>$LastLogin</td>				
								<td style='border: 1px solid #ddd; padding: 8px;'>$User_IP</td>
								<td style='border: 1px solid #ddd; padding: 8px;'>$Browser_Log</td>								
							</tr>
						";
						
						$meta_key = 'ulm_triger';
						$meta_value = '0';
						update_user_meta ($User_ID, $meta_key, $meta_value);
					}				
				}
			
				update_option ('ulm_digest_date', $Today);
                $Report_Table .= "</tbody></table>";
			}
			

			if ($ULM_Digest_OK) {
				// Send Digest.
				// $Site_title = get_bloginfo('name');
				// $subject = "$Site_title | Users Login Monitor. Daily-Digest.";
				$subject = "Users Login Monitor. Daily-Digest.";
				
				// $admin_email = get_option('admin_email');
				// $from = $admin_email;
				
				// $headers[] = "From: Users Login Monitor <$from>";
				$headers[] = "Content-Type: text/html";
				$headers[] = "charset=UTF-8";
				
				$to = $UsersLoginMonitor_Digest_Email;
				
				$message = "Users Login Monitor. Daily-Digest $Digest_TimeStamp\r\n\r\n";
				$message .= $Report_Table;			
				
				// формируем HTML контент вместо Text, т.к почему-то нарушается форматирование текста. Переводы строки не работают ((
				$message = wpautop($message);
				wp_mail($to, $subject, $message, $headers);		
			}	
		}

		// Init Active Users Processing
		$ULM_Users_Active = get_transient('ulm_users_active');

		global $UsersLoginMonitor_Activity_Control_Period;

		$User = wp_get_current_user ();

		if ($User) {
			$User_ID = $User -> ID;
			
			if ($User_ID) {
				if (!is_array($ULM_Users_Active)) {
					$ULM_Users_Active = array();
				}
				
				if ( !isset($ULM_Users_Active[$User_ID]['last_time']) || $ULM_Users_Active[$User_ID]['last_time'] <= time() - $UsersLoginMonitor_Activity_Control_Period ){
					$ULM_Users_Active[$User_ID] = array(
						'id' => $User_ID,
						'last_time' => time(),
					);
					
					// Transient. Устанавливаем со временем жизни 24 часа
					set_transient ('ulm_users_active', $ULM_Users_Active, 86400);			
				}			
			}
		}		
	}	
	
	/* Admin Bar. Active Users Info
	----------------------------------------------------------------- */
	add_action ('admin_bar_menu','UsersLoginMonitor_Action_AdminBar_Info', 999);
	function UsersLoginMonitor_Action_AdminBar_Info () {
		global $wp_admin_bar;
		
		if (!current_user_can('edit_dashboard')) {
			return;
		}
			
		$ULM_Count_Active_Users = 1;
		
		$ULM_Counts_Active_Users = UsersLoginMonitor_Get_Counts_Active_Users ();
		
		if ($ULM_Counts_Active_Users) {
			$ULM_Count_Active_Users = $ULM_Counts_Active_Users['on'];
		}		
		
		$wp_admin_bar -> add_menu (
			array (
				'id' => 'ulm_active_users_info', 
				'title' => '<span class="">' . __( 'Activity (' . $ULM_Count_Active_Users . ')') .'</span>',
				'href' => esc_url (admin_url('users.php?page=users-login-monitor%2Fincludes%2Foptions.php&tab=activity'))
			)
		);
	}	