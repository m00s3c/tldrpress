=== TLDRPress ===
Contributors: webmaster.sh
Tags: ai, summaries, openai, tldr, posts
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate AI-powered TL;DR summaries for WordPress posts and display them automatically or with a shortcode.

== Description ==

TLDRPress helps publishers, bloggers, and news websites add concise AI-powered TL;DR summaries to WordPress posts.

Summaries are generated in the WordPress admin using the OpenAI API and saved permanently in post meta. TLDRPress never calls AI services on frontend page load, so visitors receive fast cached content and site owners stay in control of when summaries are created or regenerated.

[View a TL;DR example](https://tldrpress.dev/demos/cybertoaster9000-allegedly-exposes-internal-pancake-analytics-database/)

= Key Features =

* Generate AI summaries with OpenAI.
* Store summaries permanently in post meta.
* Never generate summaries live on the frontend.
* Generate summaries manually from the post editor.
* Optionally auto-generate summaries when posts are published.
* Choose bullet or paragraph summaries.
* Choose short, medium, or long summaries.
* Edit generated summaries before updating the post.
* Bulk-generate summaries for existing posts.
* Generate only missing summaries or regenerate all summaries.
* AJAX batch processing to avoid timeouts.
* Automatically inject a TL;DR box near the top of posts.
* Display manually with the `[tldrpress]` shortcode.
* Display a specific post summary with `[tldrpress id="123"]`.
* Works with WordPress Shortcode blocks and page-builder shortcode widgets.
* REST API endpoint for saved summaries.
* Responsive frontend summary box.
* Dark mode compatible styling.

= Display Options =

TLDRPress can automatically display summaries:

* Before content.
* After featured image.
* After the first paragraph.

For complete placement control, disable automatic frontend display under **Settings > TLDRPress** and use `[tldrpress]` in a WordPress Shortcode block or page-builder shortcode widget.

= Performance =

* AI generation happens only in the admin, during AJAX requests, or scheduled publish generation.
* Summaries are stored permanently in post meta.
* Frontend rendering reads saved post meta only.
* Bulk generation runs in small AJAX batches.
* Existing summaries are not regenerated unless requested.

= Privacy =

When a summary is generated, TLDRPress sends the post content to OpenAI for processing. The generated summary is stored in the WordPress database as post meta.

TLDRPress does not send visitor data to OpenAI and does not call OpenAI on frontend page loads. Site owners should review OpenAI's data policies and update their privacy policy as appropriate.

== Installation ==

1. Upload the plugin ZIP through **Plugins > Add New > Upload Plugin**, or upload the `tldrpress` folder to `/wp-content/plugins/`.
2. Activate **TLDRPress**.
3. Go to **Settings > TLDRPress** and add your OpenAI API key.
4. Select your preferred model, summary type, summary length, and frontend display settings.
5. Open a post and use the **TLDRPress Summary** meta box to generate your first summary.

== Frequently Asked Questions ==

= Does TLDRPress generate summaries on frontend page load? =

No. Summaries are generated in the admin and stored permanently as post meta.

= Can I place the summary manually? =

Yes. Disable automatic frontend display under **Settings > TLDRPress**, then use `[tldrpress]` wherever you want the summary to appear.

= Can I use TLDRPress with the block editor or a page builder? =

Yes. Place `[tldrpress]` in the standard WordPress Shortcode block or your page builder's shortcode widget.

= Can I customize the design? =

Yes. Override `.tldrpress-box`, `.tldrpress-header`, `.tldrpress-icon`, and `.tldrpress-content` using your theme, WordPress Additional CSS, or page-builder custom CSS.

= Can I bulk-generate summaries? =

Yes. Go to **Tools > TLDRPress** to generate missing summaries, generate summaries for all posts, or regenerate all summaries.

= Where are edited summaries saved? =

Edited summaries are saved when you use the standard WordPress post update action.

= Which AI providers are supported? =

Version 1.0.2 supports OpenAI. The provider interface allows future support for additional AI services.

= What data is sent to OpenAI? =

When you generate a summary, the post content is sent to OpenAI for processing. Visitor data is not sent.

== Screenshots ==

1. TLDRPress settings page under Settings.
2. Post editor summary meta box.
3. Bulk generation tool under Tools.
4. Frontend TL;DR summary box.
5. Shortcode placement in an editor or page builder.

== Changelog ==

= 1.0.3 =

* Updated author to webmaster.sh and refreshed readme.

= 1.0.2 =

* Removed the Save Manually button from the post editor meta box.
* Moved bulk generation to **Tools > TLDRPress**.
* Moved plugin settings to **Settings > TLDRPress**.
* Edited summaries continue to save with the standard WordPress post update action.

= 1.0.1 =

* Removed the dedicated Gutenberg block and Elementor widget.
* Standardized custom placement around the `[tldrpress]` shortcode.

= 1.0.0 =

* Initial release.
* Added OpenAI summary generation.
* Added post editor summary controls.
* Added bulk AJAX generation.
* Added automatic frontend display and shortcode support.
* Added REST API endpoint.

== Upgrade Notice ==

= 1.0.2 =

Admin navigation is now under the standard WordPress Tools and Settings menus. Edited summaries save with the normal WordPress post update action.
