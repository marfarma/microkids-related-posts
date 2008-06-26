=== Microkid's Related Posts ===
Contributors: microkid
Tags: related posts, related post, relations, cross reference, reciprocal
Requires at least: 2.5
Tested up to: 2.5.1
Stable tag: 1.0

Microkid's Related Posts plugin lets you manually select related posts using a nifty AJAX powered search utility.

== Description ==

Not satisfied with automatically generated relations between posts? That's because there's no plugin out there that smarter then you in selecting related posts!

This plugin makes it super easy to manually select related posts. You can search and select posts that fit **your** criteria, all from within the write/edit post screen.

= Features =
* Easily find posts that might be related to the one you are writing with an integrated AJAX powered search utility,
* Easily add and remove relations with a single click. No page reloads, no pop-ups,
* The relationship created works **reciprocal**, which means that if post A is added as related to post B, post B is automatically added as related to post A as well,
* Seamless integration with the Wordpress 2.5 interface (backward compatibility with older versions coming soon)

== Installation ==

1. Download the plugin and unzip,
1. Put the related-posts folder in your wp-content/plugins folder,
1. Activate the plugin through the Wordpress admin,
1. Add this PHP code to your theme files to show a `<ul>` list of related posts:

	`<?php if( function_exists("MRP_show_related_posts") ) MRP_show_related_posts(); ?>`

1. Or, if you’re looking for further integration, use this to grab the IDs of related posts:

	`MRP_get_related_posts( $post_id );`

== Frequently Asked Questions == 

= Will there be a widget for this plugin? =
Yes, it will be released soon.

= Does this plugin work with Wordpress versions &lt; 2.5? =
It's functional, but there are some layout issues. Fixing this is planned for the next release.

== Screenshots ==

1. The plugin will appear under "Advanced options" in the write/edit post screen.

