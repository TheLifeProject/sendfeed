=== SendFeed ===
Contributors: truthmedia
Donate link: http://truthmedia.com/engage/giving
Tags: email, rss, atom, feed, email update, feed update, feed to email, rss to email
Requires at least: 2.5
Tested up to: 3.4.1
Stable tag: 2.0

Allows WordPress bloggers to send email updates to a given email address any
time an RSS feed is updated.

== Description ==
The SendFeed plugin allows you to send the latest post(s) from your RSS feed
to an external Mailing List Manager in both text and html formats.  It is
capable of sending messages out immediately, at predefined intervals such as
daily/weekly/monthly or manually.

The text and HTML templates are completely customisable on a per feed basis
so you can tailor the emails to suit the list or feed you are using.

Potential uses include:
* Automatically kick off formatted emails to mailing list managers on blog feed update.
* Email notifications to one or more people on feed updates.
* Track your favourite blog feeds by email.

Features:
* HTML + Text Emails
* Ability to include custom mail header fields
* Flexible send schedule including fifteen minute, daily, weekly, monthly or manual sending 

Created by the [TruthMedia](http://truthmedia.com)

Programming and Design by [James Warkentin](http://www.warkensoft.com/about-me/)

== Changelog ==

= 2.0 =
* Feature: Filter log history by feed.
* Feature: Search log history for keyword.
* Feature: Better log pagination.
* Feature: Cleaned up feed list for better overview.
* Feature: Updated to properly use wp-cron functions.
* Bug Fix: Brought the codebase up-to-date for PHP 5 and WordPress 3 compat.
* Cleanup: Many parts of the code cleaned for better functionality.

= 1.6 =
* Old version

== License Notes ==
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 3 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

== BETA SOFTWARE WARNING==
Please be aware as you use this software that it is still considered to be
BETA SOFTWARE and as such may function in unexpected ways.  Of course, we
do try our best to make sure it is as stable as possible and try to address
problems as quickly as possible when they come up, but just be aware that
there may still be bugs.

In the event that you DO experience any problems with this software, we would
like to hear about it and will do our best to fix the problem.  You can let us
know in the comments associated with the post of the latest SendFeed release.
http://truthmedia.com/category/sendfeed/

== Installation ==
1.	Upload the /sendfeed/ folder and files to your WordPress plugins folder,
	located in your WordPress install under /wp-content/plugins/

2.	Browse in the WordPress admin interface to the plugin activation page and
	activate the SendFeed plugin.

3.	The plugin should now be installed and activated.  The first time you visit
	management page, the appropriate tables will be created in the database
	if necessary.

4.	You may create new contact forms by using the controls found on the
	Settings > SendFeed page.

5.	The email scheduling of SendFeed will function best on high traffic sites.
	If your site does not receive very much traffic, you may want to set up a
	cron script to load the wp-cron.php page from your site at regular 
	intervals.  Some information on doing this can be found here:
	http://www.satollo.net/how-to-make-the-wordpress-cron-work
	
	Cron example running every 15 minutes:
	*/15 * * * *	/usr/bin/php -f /path/to/your/site/wp-cron.php
	(You will want to test this on the commandline of your server to ensure it
	is working as expected) (You may also need to use /usr/bin/php-cgi instead, 
	depending on your server configuration)
	
	Alternative: Use a third party service like this one...
	https://www.setcronjob.com/

6.	Have fun, enjoy using the SendFeed plugin, and don't forget to come
	[visit us](http://truthmedia.com "TruthMedia.com"). For the
	latest	version, more extensive documentation or to stay updated see
	[our SendFeed blog](http://truthmedia.com/wordpress/sendfeed/ "TruthMedia SendFeed Blog").


