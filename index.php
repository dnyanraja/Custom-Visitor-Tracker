<?php /**
 * Plugin Name: Custom Visitor Tracker
 * Plugin URI: http://webandseoguide.tk
 * Description: Get live details about visitor count, current online peoples etc
 * Version: 1.0.0
 * Author: Ganesh Veer
 * Author URI: 
 * License: GPL2
 **/

global $cvt_db_version;
$cvt_db_version = '1.0';


/*global $visitor;
$visitor = array('count'      => 0, 'ip'        => $_SERVER['REMOTE_ADDR']);

add_action( 'wp', 'my_setcookie' );
function my_setcookie() {
	global $visitor;
	static $count;
	if(!isset( $_COOKIE['counter'])) {
		setcookie( 'counter', 1, time() + 3600);
		//$GLOBALS['visitor']['count'] = (int)$_COOKIE['counter'];
		$count = (int)$_COOKIE['counter'];
	}else{
		$temp = (int)$_COOKIE['counter'] + 1;
		setcookie('counter', $temp,  time() + 3600);

		//$GLOBALS['visitor']['count'] = (int)$_COOKIE['counter'];
		$count = (int)$_COOKIE['counter'];	
	}
	echo $count;
}

add_action( 'wp_head', 'my_getcookie' );

function my_getcookie() {
	global $visitor;
	//$visitcount = $_COOKIE['my-name'];
	//echo $GLOBALS['visitor']['count'];
	my_setcookie();
 	//echo "<script type='text/javascript'>alert('$alert')</script>";
}*/


add_action('wp_dashboard_setup', 'custom_tracker_dashboard_widgets');

function custom_tracker_dashboard_widgets() {
	global $wp_meta_boxes;
	wp_add_dashboard_widget('custom_tracker_widget', 'Visitor Information Tracking', 'custom_tracker_dashboard_help');


/*
	
	Code to move our widget at top on dashboard

*/
	// Get the regular dashboard widgets array 
 	// (which has our new widget already but at the end)
 	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
 	
 	// Backup and delete our new dashboard widget from the end of the array
 	$custom_tracker_widget_backup = array( 'custom_tracker_widget' => $normal_dashboard['custom_tracker_widget'] );
 	unset( $normal_dashboard['custom_tracker_widget'] );
 
 	// Merge the two arrays together so our widget is at the beginning
 	$sorted_dashboard = array_merge( $custom_tracker_widget_backup, $normal_dashboard );
 
 	// Save the sorted array back into the original metaboxes  
 	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
}

function custom_tracker_dashboard_help() {
	global $wpdb;

	echo '<p>Welcome to Visitor Information Tracking</p>';
	echo  'Display top <select id="topvisits"><option value="5">5</option>
		  <option value="10">10</option>
		  <option value="15">15</option>
		  <option value="20">20</option>
		  <option value="-1" selected>All</option>
		</select>most visited page details';
	$table_name = $wpdb->prefix . 'visitor';
	// this will get the data from your table
	$retrieve_data = $wpdb->get_results( "SELECT * FROM $table_name  order by count DESC; " );
	?>
	<table class="widefat" id="mtable">
	<thead><tr><td>Page URL </td><td>Visit Count</td><td>User Name </td></tr></thead><tbody>
	<?php
	//$c = 0;
	foreach ($retrieve_data as $retrieved_data){ 
		//if($c<10){
		?>
			<tr>
			<td><?php echo get_site_url().$retrieved_data->pageurl; ?></td>
			<td><?php echo $retrieved_data->count; ?></td>
			<td><?php echo $retrieved_data->name; ?></td>
			</tr>
			<?php 
	//}
	//$c++;
	}
	?></tbody>
	</table><?php
}
add_action( 'init', 'jal_install' );
add_action( 'wp_loaded', 'jal_install_data' );

function jal_install() {
	global $wpdb;
	global $cvt_db_version;

	$table_name = $wpdb->prefix . 'visitor';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		name tinytext NOT NULL,
		ip int(18) NOT NULL,		
		pageurl text NOT NULL,
		count int(20),
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'cvt_db_version', $cvt_db_version );
}

function jal_install_data() {
	global $wpdb;
	global $current_user; 
	global $wp;


		if ( 0 == $current_user->ID ) {
			$welcome_name = 'Anonymus';
		} else {
		    $welcome_name = $current_user->user_firstname ;
		}


	if(check_innerurls() == true){
		$pageurl = strtok($_SERVER["REQUEST_URI"],'?');
		$ip = $_SERVER['REMOTE_ADDR'];
		$table_name = $wpdb->prefix . 'visitor';

		$results = $wpdb->get_var( $wpdb->prepare("SELECT *FROM {$wpdb->prefix}visitor WHERE pageurl = %s AND name = %s", $pageurl, $welcome_name) );
		//var_dump($results);

		if($results <= 0){	
			$wpdb->insert( 
				$table_name, 
				array( 
					'time' => current_time( 'mysql' ), 
					'name' => $welcome_name, 
					'ip' => $ip, 
					'pageurl' => $pageurl,
					'count' => 1
					) 
				);
			}
			else{
		$ttlcount = $wpdb->get_var("SELECT count FROM {$wpdb->prefix}visitor WHERE id = " . (int) $results);
		$ttlcount = $ttlcount + 1;
				$wpdb->replace( 
						$table_name, 
						array( 
					            'id' => (int)$results,
					            'time' => current_time( 'mysql' ), 
								'name' => $welcome_name, 
								'ip' => $ip, 
								'pageurl' => $pageurl,
								'count' => $ttlcount
						), 
						array( 
					         	'%d',
					         	'%d',
								'%s', 
								'%d',
								'%s',
								'%d' 
						) 
					);
			}

	}

}

function check_innerurls(){

	$pgurl = strtok($_SERVER["REQUEST_URI"],'?');
	$owned_urls = array('wp-content', 'wp-admin', 'wp-cron.php', 'wp-login.php');

	foreach ($owned_urls as $url) {
	    //if (strstr($string, $url)) { // mine version
	    if (strpos($pgurl, $url) !== FALSE) { // Yoshi version
	        //echo "Match found"; 
	        return false;
	    }
	}
	//echo "Not found!";
return true;

}


/*
 * The JavaScript for our number of records to show dropdown
 */
function custom_visitor_track_script() {
  ?>
  <script type="text/javascript" >
 jQuery(document).ready(function($) {

 	$('#topvisits').bind('change', function () {
	    show(0, this.value);
	});
	function show(min, max) {
    	var $table = $('#mtable'), // the table we are using
       	$rows = $table.find('tbody tr'); // the rows we want to select
    	min = min ? min - 1 : 0;
    	max = max ? max : $rows.length;
    	$rows.hide().slice(min, max).show(); // hide all rows, then show only the range we want
    	return false;    
	}
});
  </script>
  <?php
}
add_action( 'admin_footer', 'custom_visitor_track_script' );
