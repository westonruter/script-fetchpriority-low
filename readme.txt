=== Script Fetch Priority Low ===

Contributors: westonruter
Tested up to: 6.8
Stable tag:   0.1.0
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Tags:         performance

Improves performance for the LCP metric by setting fetchpriority=low on script modules for the Interactivity API and on the comment-reply script.

== Description ==

This plugin improves performance for the LCP metric by setting <code>fetchpriority=low</code> on script modules (and modulepreload links) for the Interactivity API as well as on the <code>comment-reply</code> script.

This is a workaround to implement [#61734](https://core.trac.wordpress.org/ticket/61734).

== Installation ==

1. Download the plugin [ZIP from GitHub](https://github.com/westonruter/script-fetchpriority-low/archive/refs/heads/main.zip) or if you have a local clone of the repo, run `npm run plugin-zip`.
2. Visit **Plugins > Add New Plugin** in the WordPress Admin.
3. Click **Upload Plugin**.
4. Select the `script-fetchpriority-low.zip` file on your system from step 1 and click **Install Now**.
5. Click the **Activate Plugin** button.

You may also install and update via [Git Updater](https://git-updater.com/).

== Changelog ==

= 0.1.0 =

* Initial release.
