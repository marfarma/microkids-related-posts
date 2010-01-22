=== Microkid's Related Posts ===
Contributors: microkid
Tags: related, posts, seo, content, articles, relevant, similar
Requires at least: 2.5
Tested up to: 2.9.1
Stable tag: trunk

Increase your pageviews and SEO by manually selecting related posts for your content. Easy reciprocal linking of related posts and pages.

== Description ==

This plugin makes it super easy to manually select related posts. You can search and select posts that fit **your** criteria, all from within the write/edit post screen. 

Creating a system of related posts/pages on your site will result in more page views per unique visitor, and will improve GoogleBots understanding of your content.

See the official plugin homepage for [Microkids Related Posts](http://www.microkid.net/wordpress/related-posts/ "Wordpress Related Posts") for more info and discussion.

= Features =

* Easily find posts or pages that might be related to the one you are writing with an integrated AJAX powered search utility
* Easily add and remove relations with a single click. No page reloads, no pop-ups
* The relationship created works **reciprocal**, which means that if post A is added as related to post B, post B is automatically added as related to post A as well (this option can be turned off easily through the settings panel)
* Multiple widget support
* Shortcode support: add the [related-posts] shortcode to your post content to display related posts anywhere you want
* Automatically display a list of related posts underneath your posts content. No need to add any PHP tags in your template files, but several [functions](http://www.microkid.net/wordpress/related-posts/#API "Microkids Related Posts API functions") available for advanced users.
* Flexible customization of the way the related posts are displayed

== Installation ==

1. Download the plugin and unzip,
1. Put the related-posts folder in your wp-content/plugins folder,
1. Activate the plugin through the Wordpress admin,
1. The plugin will automatically display related posts underneath your posts content. You can change this and some other settings through the plugins options page under Settings > Related Posts.
1. If you want to display the related posts somewhere else on your page, there is a set of [API functions](http://www.microkid.net/wordpress/related-posts/#API "Microkids Related Posts API functions") you can place in your theme files. You can also use the [related-posts] shortcode to display related posts within your content, or use the available widget.

== Frequently Asked Questions == 

= Does this plugin have a widget? =
Yes, if your theme supports it you can display related posts as a widget in your dynamic sidebars.

= Does this plugin work with Wordpress versions &lt; 2.5? =
No, it is not backward compatible with Wordpress version older than 2.5.

= What will be displayed if there are no related posts? =
You can use a custom message, or display nothing at all (no text, no code).

= How can I customize the way the related posts are displayed? =
There is a set of [API functions](http://www.microkid.net/wordpress/related-posts/#API "Microkids Related Posts API functions") available you can use in your themes.

= I'm having trouble using this plugin. How can I reach you? =
Please leave me a comment at the [Microkids Related Posts](http://www.microkid.net/wordpress/related-posts/ "Microkids Related Posts") homepage.

== Screenshots ==

1. The plugin will appear in the write/edit post screen.
2. The Related Posts options page
3. The Related Posts widget

== Change Log ==

= 2.5 =
* Improved the AJAX search utility with a more fluent way of updating the results list
* Added support for multiple instances of the widget by using the new [Widget API](http://codex.wordpress.org/Version_2.8#New_Widgets_API) introduced in Wordpress 2.8
* Fixed a bug where the widget wouldn't show on pages
* Related private posts are now displayed to users who have the necessary priviliges

= 2.4 =
* You can now add drafts and planned posts as related posts, without them appearing on your site before they are properly published.
* Added shortcode support - you can now place the [related-posts] shortcode within the content of your post to display the related posts anywhere you want.
* (For advanced users) Added a parameter to the MRP_get_related_posts function to optionally get the related posts in an object, so you can easily use it in other plugins or your own custom way of displaying the list of related posts. For documentation on this, see [the API section](http://www.microkid.net/wordpress/related-posts/#API "Microkids Related Posts API functions").

= 2.3 =
* Added option to manage search scope: post content, post title, or both
* Slightly improved the search query when searching for related posts
* Added a limit of 50 search results to help the plugin work smoothly on larger sites.
* Fixed some IE6 layout issues
* Fixed issue where, in non reciprocal mode, after editing a post it would lose its passive connections to other posts
* Fixed issue where hitting the enter key while in the search box would submit the page
* Fixed issue where hitting a none character key (like shift, ctrl, etc.) in the search box would trigger the AJAX search

= 2.2 =
* Added optimized SQL for related posts query provided by [Peter Raganitsch](http://blog.oracleapex.at "Peter Raganitsch]")
* Added option for turning reciprocal function off provided by [Brock Boland](http://www.brockboland.com/ "Brock Boland]")
* Fixed some layout issues in Wordpress 2.7
* The search tool now searches in post title and post content.

= 2.1.1 =
* Solved an issue with shorthand PHP tags.

= 2.1 =
* Solved an issue where in WP 2.6 relationships with revisions were created when editing a post.

= 2.0 =
* Added the option to display related posts underneath your post content automatically
* Added the extended customization options for the way the list of related posts is displayed:
 * Using a custom title
 * Choosing the HTML header element (h1, h2, etc.) of the title for the related posts section
 * Custom message to display when there are no related posts, with the option no displaying anything at all (no text, no code)
* Added widget support
* Added support for adding related pages

= 1.1 =
* Fixed a small issue with the paths to the .js and .css files which made the plugin break on blogs that reside in subdirectories.