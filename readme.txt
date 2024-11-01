=== WP-Brightkite ===
Contributors: technosailor
Donate link: http://technosailor.com/donation/
Tags: social networking, geolocation, brightkite, geotag
Requires at least: 2.5.1
Tested up to: 2.6.1-alpha
Stable tag: trunk

This plugin provides geolocation metadata throughout the blog based on Brightkite data.

== Description ==

This plugin provides geodata for posts associated with authors utilizing <a href="http://brightkite.com">Brightkite</a>, the location based micro-content social network.

Brightkite provides data regarding the users activities via KML. This plugin parses the KML to determine latitude and longitude and geotags RSS2 and Atom feeds according to the <a href="http://postneo.com/icbm/">ICBM Namespace</a>. Additional functionality allows for Google Map integration with posts.

== Installation ==

1. Upload the `wp-brightkite/` directory to `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Fill out Brightkite user data on your profile page. Note: Standard WP permissions apply.
1. Use the `<?php post_gmap() ?>` within your template to print a mini 10x10 map icon, clickable for Google Maps location.

== Frequently Asked Questions ==

= How can I get a Brightkite invite? =

Ask around on Twitter or elsewhere. There's invites to be had.

= Can I change the size of the Google Map template tag? =

Not yet, but I plan to do that in future versions.