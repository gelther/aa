=== Advanced Ads ===
Contributors: webzunft
Donate link:https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5RRRCEBGN3UT2
Tags: ads, ad, ad inserter, ad injection, ad manager, ads manager, ad widget, adrotate, adsense, advertise, advertisements, advertising, adverts, advert, amazon, banner, banners, buysellads, chitika, clickbank, dfp, doubleclick, geotarget, geolocation, geo location, google dfp, monetization, widget
Requires at least: WP 4.2, PHP 5.3
Tested up to: 4.5
Stable tag: 1.7.1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage and optimize your ads as easy as creating posts. Including support for AdSense, ad injection, ad planning, ad widget, and ad rotation.

== Description ==

Advanced Ads is a simple ad manager made by a publisher for publishers. Based on my experience delivering millions of ads per month I built this advertising plugin as a powerful, but light weight solution to not only manage and insert banners in WordPress, but to test and optimize them as well.

[Full Feature List](https://wpadvancedads.com/features/).

= create and manage ads =

* create a banner is as easy as creating a post
* group ads to create ad rotations
* create drafts of advertisements only visible to logged in users
* set a date for when to publish the ad
* make internal notes about each ad

= ad types =

choose between different ad types that enable you to:

* insert code for ad and affiliate networks (e.g., AdSense, Chitika, Amazon, BuySellAds, DoubleClick)
* dedicated support for Google AdSense banners
* display images and image banners
* use shortcodes within ads (to also deliver advertisements from other ad plugins like AdRotate or Simple Ads Manager)
* create content rich ads with the tinymc editor
* use flash file advertisements including a fallback – included in [Pro](https://wpadvancedads.com/add-ons/advanced-ads-pro/)

= display ads =

* auto inject banner (see _ad injection_ below)
* display advertising in template files (with functions)
* display advertising in post content (with shortcakes)
* ad widget for sidebars and widget areas
* display ad groups based on customizable ad weight
* use placements in your theme to insert ads and ad groups in template files without coding
* disable all ads on individual single pages
* set start time and expiry date for advertising
* display multiple ads from a banner group (ad blocks)
* define the order of ads from an ad group and allow default ads

= display conditions =

show ads based on conditions like

* individual posts, pages and other post type
* post type
* posts by category, tags, taxonomies
* author
* archive pages by category, tags, taxonomies
* special page types like 404, attachment and front page
* hide ads on secondary queries (e.g. posts in sidebars)
* display or hide banners within the post feed

global conditions

* disable all ads in the frontend (e.g. when your ad network breaks down)
* disable all ads on 404 pages (e.g. AdSense doesn’t allow that)
* disable all ads on non-singular pages
* disable all ads in secondary queries
* disable all ads in the feed
* hide ads from bots and web crawlers

= visitor conditions =

display ads by conditions based on the visitor. [List of all visitor conditions](https://wpadvancedads.com/manual/visitor-conditions/)

* display or hide a banner for mobile visitors
* display or hide a banner for logged in visitors
* hide advertising from logged in users based on their role
* advanced visitor conditions: previous visited url (referrer), user capability, browser language, browser and device, url parameters included in [Pro](https://wpadvancedads.com/add-ons/advanced-ads-pro/)
* display ads by geo location with the [Geo Targeting add-on](https://wpadvancedads.com/add-ons/geo-targeting/)
* display ads by browser width with the [Responsive add-on](https://wpadvancedads.com/add-ons/responsive-ads/)

= ad injection | placements =

Placements to insert ads in pre-defined positions in your theme and content. [List of all placements](https://wpadvancedads.com/manual/placements/)

* ads after any given paragraph or headline in the post content
* ads at the top of the post content
* ads at the bottom of the post content
* ads before closing `</head>` tag
* ads page footer
* many more ad inserters with [add-ons](https://wpadvancedads.com/add-ons/)

= ad networks =

Advanced Ads is compatible with all ad networks and banners from affiliate programs like Google AdSense, Chitika, Clickbank, Amazon, and also Google DoubleClick (DFP).
You can also use it to insert additional ad network tags into header or footer of your site without additional coding.

= best support for mobile devices =

* display ads for mobile or desktop only
* display responsive image ads (WordPress 4.4 and later)
* ads for specific browser sizes only using [Responsive Ads](https://wpadvancedads.com/add-ons/responsive-ads/)

= Google AdSense =

* switch sizes of an ad
* switch between normal and responsive ads
* automatic limit to 3 AdSense banners according to AdSense terms of service (can be disabled)
* hide AdSense advertisements on 404 pages by default (to comply with AdSense terms)
* insert Page-Level ads code globally
* assistant for exact sizes of responsive ads with the [Responsive add-on](https://wpadvancedads.com/add-ons/responsive-ads/)

= ad blocker =

* basic features to prevent ad blocks from being removed by AdBlock and co
* prevent ad blockers from breaking sites where plugin scripts are running

= based on WordPress standards =

* integrated into WordPress using standards like custom post types, taxonomies and hooks
* easily customizable by any WordPress plugin developer

Learn more on the [plugin homepage](https://wpadvancedads.com).

Localizations: English, German, Spanish, Dutch, Italian, Portuguese

> <strong>Add-Ons</strong>
>
> * [Advanced Ads Pro](https://wpadvancedads.com/add-ons/advanced-ads-pro/) – powerful tools for ad optimizations: cache-busting, more placements, etc.
> * [Geo Targeting](https://wpadvancedads.com/add-ons/geo-targeting/) – display ads based on geo location of the visitor
> * [Tracking](https://wpadvancedads.com/add-ons/tracking/) – ad tracking and statistics
> * [Responsive Ads](https://wpadvancedads.com/add-ons/responsive-ads/) – create mobile ads or ads for specific browser sizes
> * [Sticky Ads](https://wpadvancedads.com/sticky-ads/demo/) – increase click rates with fixed, sticky, and anchor ads
> * [PopUp and Layer Ads](https://wpadvancedads.com/add-ons/popup-and-layer-ads/) – display ads and other content in layers and popups
> * [Slider](https://wpadvancedads.com/add-ons/slider/) – create a simple slider from your ads

== Installation ==

How to install the plugin and get it working?

= Using The WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'advanced ads'
3. Click 'Install Now'
4. Activate Advanced Ads on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `advanced-ads.zip` from your computer
4. Click 'Install Now'
5. Activate Advanced Ads in the Plugin dashboard

= Using FTP =

1. Download `advanced-ads.zip`
2. Extract the `advanced-ads` directory to your computer
3. Upload the `advanced-ads` directory to the `/wp-content/plugins/` directory
4. Activate Advanced Ads in the Plugin dashboard

== Displaying Ads ==

You can use functions and shortcodes to display ads and ad groups.

The integers in this example are the IDs of the elements.

Use these shortcode to insert an ad or ad group into your post/page.

`[the_ad id="24"]`
`[the_ad_group id="5"]`

Use these functions to insert an ad or ad group into your template file.

`<?php the_ad(24); ?>`
`<?php the_ad_group(5); ?>`

In addition to directly displaying ads and groups you can define ad placements and assign either an ad or group to them.

`[the_ad_placement id="header-left"]`
`<?php the_ad_placement('header-left'); ?>`

== Frequently Asked Questions ==

= Is there a revenue share? =

There is no revenue share. Advanced Ads doesn’t alter your ad codes in a way that you earn less than you would directly including the ad code in your template.

== Screenshots ==

1. Create an ad almost like you would create an article or page.
2. Align the ad and set a margin to other elements
3. Choose from various conditions where and where not to display your ad.
4. Placements that let you inject ads anywhere into your site without coding (6 in Advanced Ads + 9 through add-ons)

== Changelog ==

= 1.7.1.2 =

* tested with WP 4.5
* create random widget id, if not set yet
* allow content injection into nested paragraphs if none found at level 1 and 2
* minor security update for admin ajax actions
* fixed home condition for some cases
* fixed secondary query check
* fixed de/activation hook
* fixed Spanish translation
* updated translation files

= 1.7.1.1 =

* added Feed condition to General Display Conditions
* trim license keys when saving
* reverted too strict display condition checks for taxonomies
* shortened debug page

= 1.7.1 =

* introduced AND/OR connectors for Display Conditions
* display conditions for some pages (archive page) are now stricter
* connectors for existing visitor conditions can be changed now
* added option to reserve ad space
* set ADVANCED_ADS_DISALLOW_PHP constant to prevent usage of PHP in plain ad code
* update ad block disguise files automatically, when Advanced Ads scripts changed after an update

= 1.7.0.3 =

* added debug mode for ads, see [manual](https://wpadvancedads.com/manual/ad-debug-mode/)
* skip check for general conditions if all are on (allows ad injection also in iframes and new wp instances)
* display jQuery issue notice in display conditions
* lifted the restriction to choose each display condition only once
* add notice when ad block folder needs to be rebuild
* hide an ad with a tag condition in posts that don’t have a tag at all
* prevent broken custom queries to interfere with display conditions

= 1.7.0.2 =

* hide ad if no term is selected for taxonomy display conditions
* show warning, if no items selected in display conditions
* fixed 500 error caused by plugins using WP_Query unsecured

= 1.7.0.1 =

* hotfix for missing get_current_screen issue

= 1.7 =

* rewritten Display Conditions interface and logic, see [manual](http://wpadvancedads.com/manual/display-conditions/)
* rich content ads now render oembed links
* added author display condition
* added link to Visitor Conditions manual
* added incompatibility warning for Q2W3 plugin
* home page display condition honored also on static pages
* single post display condition honored also on archive pages
* prevent ads from being loaded in wp_head when they are not a header placement
* optimized support form and added feedback messages
* fixed general secondary query setting
* fixes for jQuery issues and message for solutions
* fix error when editor role is missing

[Changelog Archive](http://wpadvancedads.com/codex/changelog-archive/)

== Upgrade Notice ==
