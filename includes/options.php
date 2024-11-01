<?php
/*
* WPGear. Users Login Monitor
* options.php
*/
	
    if (!current_user_can('edit_dashboard')) {
        return;
    } 
?>
	<div class="wrap">
		<h1>Users Login Monitor.</h1>
		<hr>
	</div>
	
	<?php
	$Current_Tab = isset($_GET['tab']) ? sanitize_text_field ($_GET['tab']) : 'activity';
	
	UsersLoginMonitor_Create_Tabs ($Current_Tab);
	
	// Tab: "Activity"
	if ($Current_Tab == 'activity') { 
		include_once(dirname(__FILE__) ."/activity.php");
	}

	// Tab: "Setup"
	if ($Current_Tab == 'setup') { 
		include_once(dirname(__FILE__) ."/setup.php");
	}	
	
	// Create Tabs.
	function UsersLoginMonitor_Create_Tabs ($Current_Tab) {
		$Tabs = array( 
			'activity' 	=> 'Activity', 
			'setup' => 'Setup'
		);
		
		echo '<div class="wrap"><h2 class="nav-tab-wrapper">';
			foreach( $Tabs as $tab => $name ) {
				$class = '';
				
				if ($tab == $Current_Tab) {
					$class = ' nav-tab-active';
				} 
				
				echo "<a class='nav-tab$class' href='?page=users-login-monitor/includes/options.php&tab=$tab'>$name</a>";
			}
		echo '</h2></div>';
	}
	?>