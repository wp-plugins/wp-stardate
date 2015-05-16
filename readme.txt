=== wp-stardate ===
Contributors: doddo
Tags: dates, stardate
Requires at least: 4.0.1
Tested up to: 4.2.2
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add stardates to your wordpress weblog

== Description ==

A powerful versitale stardate plugin with a wide array of functionalities for integrating stardates with your wordpress blog.

* URL `%stardate%` permalink rewrite support for posts.
* Filter various date functions, to have them display stardate instead, or filter nothing at all, and use the...
* `<?php the_stardate() ?>` function (or `<?pgp get_the_stardate() ?>`) inside of your themes, to get posts stardate.
* Aapproximate stardate based on several different formulas and variants and sources (like trekguide and wikipedia), including XI (for modern movies) as well as Classic (from TNG, VOY ...).
* Shortcodes: `[stardate]` will expand to the current timestamp in stardate fmt.

The [stardate](https://wordpress.org/plugins/stardate/) plugin by croakingtoad is supported to wordpress 3.0.5, and has not been updated in five years. Therefore this plugin reincorporates the features of that stardate plugin and adds more functionality besides.


== Installation ==

1. unpack the zip into the `/wp-content/plugins` dir
1. Activate the plugin through the 'Plugins' menu
1. From the options menu, configure the thing and press "make it so".
1. press the "Generate stardates" button to associate stardate with all old posts.


== Screenshots ==

1. Default theme using stardate "Classic" format with the "override_get_date" setting.
2. URL with `/%stardate%/%postname%/` in the permalink structure.


== Changelog ==

= 1.0 =
Initial release.

= 0.1 =
First beta version with potential bugs in it.

