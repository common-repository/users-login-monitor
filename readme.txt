=== Users Login Monitor ===
Contributors: WPGear
Donate link: http://wpgear.xyz/users-login-monitor
Tags: security,login,dashboard,user,admin,users,logout,members
Requires at least: 4.1
Tested up to: 6.3.1
Requires PHP: 5.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 4.16

A freeware plugin, for daily-notify site administrator, about users who logged in during the day.

== Description ==

Ext Security.
Dashboard & Daily-Digest about users activity.
Now the console has a widget that displays last login users, whith: Date-Time, IP address (whith Whois info) and Device type/Browser.

= Features =

* Even without going to the site admin area, you will be informed about the activity of the current day.
* Any person can be a recipient of notifications. Not necessarily the Administrator.
* Now in the Admin console you have a new widget with a list of users in order of decreasing Login time.
* Determine and save the IP address, device and browser details, from which the was made Login. (if your server is configured correctly). For better informational content, in order to be able to determine the parameters of the User's devices (OS, Browser, Type Device), you should have a PHP extension on the server: "Browscap". Alternatively, you can use the Lite-Version - Plugin: "quick-browscap" from the official WP repository.
* It is important to understand that the time to enter the site and the time of the last activity of the user are different events.
* Displays "Login Success" Statistics for each User.
* Displays Count "Users Activity" in Admin Bar.
	
== Installation ==

1. Upload 'users-login-monitor' folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Make sure your system has iconv set up right, or iconv is not installed at all. If you have any problems - please ask for support. 

== Screenshots ==

1. screenshot-1.png This is the example 'Users Login Monitor' widget on Console.
2. screenshot-2.png Setup options 'Users Login Monitor'.
3. screenshot-3.png Activity Page.
4. screenshot-4.png This is the example 'Users Login Monitor' email notification.

== Frequently Asked Questions ==

NA

== Changelog ==
= 4.16 =
	2024.08.12
	* Users Activity Monitor.
	* Tested to WP 6.6.1
	
= 3.15 =
	2023.09.05
	* Fix Real IP Determination.
	* Tested to WP 6.3.1
	
= 3.14 =
	2023.04.19
	* Tested to WP: 6.2
	
= 3.13 =
	2023.03.19
	* Fix CURL Error Responses.
	* Fix WhoIs
	
= 3.12 =
	2023.03.06
	* Tested to WP: 6.1.1
	* Fix CURL Responses.
	* Add "User Role" to Log Reports.
	
= 2.11 =
	2021.05.27
	* Check installed plugin "Users Login Monitor PRO" to keep the general settings, before uninstalling.
	* Fix encoding CSS & Uninstall files.
	
= 2.10 =
	2021.05.16
	* Fix backward compatibility bug after Login Failed.
	
= 2.9 =
	2021.05.15
	* Changed files and directories structure.
	* Changed names of global variables.
	* Displays "Login Success" Statistics - rounded.
	
= 2.8 =
	2021.05.14
	* New Features: Displays "Login Success" Statistics for each User.
	
= 1.7 =
	2021.05.13
	* Added Setup-Page link to Widget Header.
	
= 1.6 =
	2021.03.11
	* Added WhoIs info for IP Details.
	* Auto switch to "quick-browscap" if PHP ext: "Browscap.ini" not set.
	
= 1.5 =
	2021.03.05
	* Fix style for Options page & Fix small issue.
	
= 1.4 =
	2021.03.05
	* Test & Fix small issue for WP 5.7
	
= 1.3 =
	2021.02.24
	* Fix SQL Query.
	
= 1.2 =
	2021.02.19
	* Corrected for the requirements of the current WP versions.
	
= 1.1 =
	2018.09.19
	* Check browscap - is configured in php.ini and exception if error.

= 1.0 =
	2018.08.27
	* Initial release	
	
== Upgrade Notice ==
= 1.5 =
	* Upgrade please.
	
= 1.4 =
	* Upgrade please.
	
= 1.3 =
	* Upgrade please.
	
= 1.2 =
	* 2021.02.19 ReOpen this Plugin. Enjoy.	