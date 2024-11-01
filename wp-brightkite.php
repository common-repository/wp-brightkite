<?php
/*
Plugin Name: WP-Brightkite
Plugin URI: http://technosailor.com/
Description: Adds extended geotagging information to a WordPress blog based on Brightkite activity
Version: 1.0-RC2
Author: Aaron Brazell
Author URI: http://technosailor.com
*/

function bkite_authorprofile()
{
	global $profileuser;
	$userid = (int) $profileuser->data->ID;
	?>
		<h3><?php _e('Brightkite Geotagging'); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="brightkite"><?php _e('Brightkite Username') ?></label></th>
				<td><input type="text" name="brightkite" id="brightkite" value="<?php echo get_usermeta( $userid, 'brightkite') ?>"/></td>
			</tr>
			<tr>
				<th><label for="bkitegmapsapikey"><?php _e('Google Maps API Key') ?></label></th>
				<td><input type="text" name="bkitegmapsapikey" id="bkitegmapsapikey" value="<?php echo get_option('bkitegmapsapikey') ?>" /><small>Get a free Google Maps API key <a href="http://code.google.com/apis/maps/signup.html">here</a>.</small></td>
			</tr>
		</table>
	<?php
}
add_action('show_user_profile','bkite_authorprofile');
add_action('edit_user_profile','bkite_authorprofile');

function bkite_updatemeta()
{
	if( !is_admin() )
		return false;
	
	if( !isset( $_POST['user_id']) )
		return false;
	
	$userid = (int) $_POST['user_id'];
	
	if( !$_POST['brightkite'] )
		return false;
	
	$bkite = $_POST['brightkite'];
	update_usermeta( $userid, 'brightkite', $bkite);
	
	if( $_POST['bkitegmapsapikey'] )
		update_option('bkitegmapsapikey', $_POST['bkitegmapsapikey']);
}
add_action('init','bkite_updatemeta');

function save_bkite_loc( $post_ID )
{
	$post = get_post( $post_ID );
	if( !$brightkite = get_usermeta( $post->post_author, 'brightkite' ) )
		return false;
		
	$coordinates = bkite_lat_long();
	if ( !add_post_meta( $post_ID, 'geoloc', $coordinates, true ) )
		update_post_meta( $post_ID, 'geoloc', $coordinates );    
}
add_action('save_post','save_bkite_loc');

function post_gmap()
{
	global $post;
	get_bkite_loc( $post->post_author );
}

function get_bkite_loc( $userid )
{
	$coordinates = bkite_post_loc();
	//echo 'Latitude: ' . $lat . '; Longitude: ' . $long;
	echo '<a href="http://maps.google.com/maps?f=q&hl=en&geocode=&q=' . $coordinates['latitude'] . ',' . $coordinates['longitude'] . '&ie=UTF8&z=16&iwloc=addr"><img alt="' . $coordinates['placemark'] . '" title="' . $coordinates['placemark'] . '" src="' . get_bloginfo('siteurl') . '/wp-content/plugins/wp-brightkite/gmap1.png" style="border:1px solid #bbb; height:10px; width:10px;"/></a>';
}

function bkite_post_loc()
{
	global $post;
	if( !$coordinates = get_post_meta( $post->ID, 'geoloc', true) )
		return false;
	return $coordinates;
}
function bkite_notice()
{
	global $current_user;
	if( $brightkite = get_usermeta( $current_user->ID, 'brightkite' ) )
		return false;
		
	echo '<div class="updated fade" style="padding-top:10px; padding-bottom:10px;" id="wp-version-message">You can GEOtag your posts by filling in your Brightkite username on <a href="' . get_bloginfo('siteurl') . '/wp-admin/profile.php">your profile</a>. Posts will automatically tag your posts with your geo-location.</div>';
}
add_action('admin_notices','bkite_notice');

function bkite_current_location()
{
	$coordinates = bkite_lat_long();
	$lat = $coordinates['latitude'];
	$long = $coordinates['longitude'];
	generate_gmap($lat,$long);
	?>
		<p>Checked In: <a href="http://maps.google.com/maps?f=q&hl=en&geocode=&q=<?php echo $lat ?>,<?php echo $long ?>"><?php echo $placemark ?></a></p>
		<small>Powered by <a href="http://brightkite.com">Brightkite</a></small>
	<?php
}

function bkite_lat_long()
{
	global $current_user;
	if( !$brightkite = get_usermeta( $current_user->ID, 'brightkite' ) )
		return false;
		include_once( ABSPATH . '/wp-includes/rss.php');
		$georss = @fetch_rss('http://brightkite.com/people/' . $brightkite . '/objects.rss?limit=1&filters=checkins');
		if ( isset($georss->items) && 0 < count($georss->items) )  
		{
			foreach( $georss->items as $checkin )
			{
				$lat = (string) $checkin['geo']['lat'];
				$long = (string) $checkin['geo']['long'];
				$placemark = (string) $checkin['bk']['placename'];
			}
		}
	return array('latitude' => $lat, 'longitude' => $long, 'placemark' => $placemark);
}

function bkite_geotag_feed_ns()
{
	?>
	xmlns:icbm="http://www.postneo.com/icbm/"
	<?php
}
add_action('rss2_ns','bkite_geotag_feed_ns');
add_action('atom_ns','bkite_geotag_feed_ns');

function bkite_geotag_feeds()
{
	$coordinates = bkite_post_loc();
	?>
	<icbm:latitude><?php echo $coordinates['latitude'] ?></icbm:latitude>
	<icbm:longitude><?php echo $coordinates['longitude'] ?></icbm:longitude>
	<?php
}
add_action('rss2_item','bkite_geotag_feeds');
add_action('atom_entry','bkite_geotag_feeds');

function bkite_meta_tag()
{
	$coordinates = bkite_post_loc();
	?>
	<meta name="ICBM" content="<?php echo $coordinates['latitude'] ?>, <?php echo $coordinates['longitude'] ?>" />
	<?php
}
add_action('wp_head','bkite_meta_tag');

function bkite_meta_box()
{
	add_meta_box('bkite_current_location', __('Current Geographic Location'), 'bkite_current_location','post','advanced');
}
add_action('admin_head','bkite_meta_box');

function generate_gmap( $lat, $long, $height=300, $width=300)
{
	$gapi = get_option('bkitegmapsapikey');
	$map = '<img style="clear:both;" src="http://maps.google.com/staticmap?center=' . $lat . ',' . $long . '&zoom=14&size=' . $height . 'x' . $width . '
	&markers=' . $lat . ',' . $long . ',black&key=' . $gapi . '" alt="Google Map" />';
	echo $map;
}
?>