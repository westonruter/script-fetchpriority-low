> [!NOTE]  
> This plugin is obsolete because the functionality in this plugin landed in WordPress 6.9. See [section in frontend performance field guide](https://make.wordpress.org/core/2025/11/18/wordpress-6-9-frontend-performance-field-guide/#support-specifying-fetchpriority-for-scripts-and-script-modules).

# Script Fetch Priority Low #

Contributors: westonruter  
Tested up to: 6.8  
Stable tag:   0.1.0  
License:      [GPLv2](https://www.gnu.org/licenses/gpl-2.0.html) or later  
Tags:         performance

## Description ##

This plugin improves performance for the LCP metric by setting `fetchpriority=low` on script modules (and `modulepreload` links) for the Interactivity API as well as on the `comment-reply` script.

This is a prototype to implement [#61734](https://core.trac.wordpress.org/ticket/61734) in WordPress core.

Here's an example of the changes you can expect to the page markup:

```diff
--- before.prettier.html
+++ after.prettier.html
@@ -2150,28 +2150,34 @@
       type="module"
       src="http://localhost:10008/wp-includes/js/dist/script-modules/block-library/file/view.min.js?ver=fdc2f6842e015af83140"
       id="@wordpress/block-library/file/view-js-module"
+      fetchpriority="low"
     ></script>
     <script
       type="module"
       src="http://localhost:10008/wp-includes/js/dist/script-modules/block-library/image/view.min.js?ver=e38a2f910342023b9d19"
       id="@wordpress/block-library/image/view-js-module"
+      fetchpriority="low"
     ></script>
     <script
       type="module"
       src="http://localhost:10008/wp-includes/js/dist/script-modules/block-library/navigation/view.min.js?ver=61572d447d60c0aa5240"
       id="@wordpress/block-library/navigation/view-js-module"
+      fetchpriority="low"
     ></script>
     <script
       type="module"
       src="http://localhost:10008/wp-includes/js/dist/script-modules/block-library/query/view.min.js?ver=f55e93a1ad4806e91785"
       id="@wordpress/block-library/query/view-js-module"
+      fetchpriority="low"
     ></script>
     <script
       type="module"
       src="http://localhost:10008/wp-includes/js/dist/script-modules/block-library/search/view.min.js?ver=208bf143e4074549fa89"
       id="@wordpress/block-library/search/view-js-module"
+      fetchpriority="low"
     ></script>
     <link
+      fetchpriority="low"
       rel="modulepreload"
       href="http://localhost:10008/wp-includes/js/dist/script-modules/interactivity/index.min.js?ver=55aebb6e0a16726baffb"
       id="@wordpress/interactivity-js-modulepreload"
@@ -3171,6 +3177,7 @@
       id="comment-reply-js"
       async
       data-wp-strategy="async"
+      fetchpriority="low"
     ></script>
     <script id="wp-block-template-skip-link-js-after">
       (function () {
```

See full writeup: [Improve LCP by Deprioritizing Script Modules from the Interactivity API](https://weston.ruter.net/2025/05/26/improve-lcp-by-deprioritizing-interactivity-api-script-modules/).

Related plugin: [Script Modules in Footer](https://github.com/westonruter/script-modules-in-footer)

## Installation ##

1. Download the plugin [ZIP from GitHub](https://github.com/westonruter/script-fetchpriority-low/archive/refs/heads/main.zip) or if you have a local clone of the repo, run `npm run plugin-zip`.
2. Visit **Plugins > Add New Plugin** in the WordPress Admin.
3. Click **Upload Plugin**.
4. Select the `script-fetchpriority-low.zip` file on your system from step 1 and click **Install Now**.
5. Click the **Activate Plugin** button.

You may also install and update via [Git Updater](https://git-updater.com/).

## Changelog ##

### 0.1.0 ###

* Initial release.
