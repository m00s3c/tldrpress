=== TLDRPress ===
Contributors: webmaster.sh
Tags: ai, summaries, openai, tldr, posts
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate AI-powered TL;DR summaries for WordPress posts and display them with automatic placement or a shortcode.

== Description ==

TLDRPress helps publishers, bloggers, and news websites add concise AI-powered TL;DR summaries to WordPress posts.

Summaries are generated in the WordPress admin using the OpenAI API and saved permanently in post meta. TLDRPress never calls AI services on frontend page load, so visitors receive fast cached content and site owners stay in control of when summaries are created or regenerated.

Use TLDRPress to add clean summary boxes near the top of long-form articles, news updates, tutorials, reviews, and editorial content.

= Key Features =

* Generate AI summaries with OpenAI.
* Store summaries in post meta.
* Never generate summaries live on the frontend.
* Generate summaries manually from the post editor.
* Optionally auto-generate summaries when posts are published.
* Choose bullet or paragraph summaries.
* Choose short, medium, or long summaries.
* Edit generated summaries manually.
* Bulk-generate summaries for existing posts.
* Generate only missing summaries.
* Regenerate all summaries.
* AJAX batch processing to avoid timeouts.
* Automatically inject a TL;DR box near the top of posts.
* Display manually with the `[tldrpress]` shortcode.
* Display a specific post summary with `[tldrpress id="123"]`.
* Works with the WordPress Shortcode block and page builder shortcode widgets.
* REST API endpoint for saved summaries.
* Responsive frontend summary box.
* Dark mode compatible styling.
* Developer hooks and provider abstraction for future AI services.

= Display Options =

TLDRPress can automatically display summaries in one of these locations:

* Before content.
* After featured image.
* After first paragraph.

For complete placement control, disable automatic frontend display and use:

* Shortcode: `[tldrpress]`

In the block editor, use the standard WordPress Shortcode block. In Elementor or other page builders, use the builder's shortcode widget or module.

= Shortcode =

Display the current post summary:

`[tldrpress]`

Display a specific post summary:

`[tldrpress id="123"]`

= Performance =

TLDRPress is designed for lightweight production use:

* AI generation only happens in the admin, during AJAX requests, or scheduled publish generation.
* Summaries are stored permanently in post meta.
* Frontend rendering reads saved post meta only.
* Bulk generation runs in small AJAX batches.
* Existing summaries are not regenerated unless requested.

= Developer Features =

TLDRPress includes hooks and filters for customization:

* `tldrpress_ai_provider`
* `tldrpress_generation_args`
* `tldrpress_openai_model`
* `tldrpress_openai_request_body`
* `tldrpress_summary_generated`
* `tldrpress_summary_box_html`

The AI provider layer is intentionally separated so future providers such as Anthropic Claude, Gemini, Ollama, or local AI models can be added without rewriting the admin or display layers.

== Installation ==

1. Upload the plugin ZIP through **Plugins > Add New > Upload Plugin**, or upload the `tldrpress` folder to `/wp-content/plugins/`.
2. Activate **TLDRPress** from the Plugins screen.
3. Go to **Settings > TLDRPress**.
4. Add your OpenAI API key.
5. Select a model, default summary type, default summary length, and frontend display settings.
6. Open a post and use the **TLDRPress Summary** meta box to generate your first summary.

== Frequently Asked Questions ==

= Does TLDRPress generate summaries on frontend page load? =

No. TLDRPress never calls OpenAI on frontend page load. Summaries are generated in the admin and stored as post meta.

= Can I place the summary manually? =

Yes. Disable automatic frontend display in **Settings > TLDRPress**, then use `[tldrpress]` wherever you want the summary to appear. In the block editor, place it in a Shortcode block. In page builders, use a shortcode widget or module.

= Can I customize the design? =

Yes. The summary box uses simple CSS classes such as `.tldrpress-box`, `.tldrpress-header`, `.tldrpress-icon`, and `.tldrpress-content`. You can override these styles in your theme, Additional CSS, or builder custom CSS.

= Can I bulk-generate summaries? =

Yes. Go to **Tools > TLDRPress** to generate missing summaries, generate summaries for all posts, or regenerate all summaries.

= Which AI providers are supported? =

Version 1.0.3 supports OpenAI. The code includes a provider interface and filters for future support of additional AI services.

= What data is sent to OpenAI? =

When you generate a summary, the post content is sent to OpenAI for processing. Visitor data is not sent, and no OpenAI requests are made from frontend page loads.

== Screenshots ==

1. TLDRPress settings page.
2. Post editor summary meta box.
3. Bulk generation tool.
4. Frontend TL;DR summary box.
5. Shortcode placement in editor or page builder.

== Changelog ==

= 1.0.3 =

* Updated author to webmaster.sh and refreshed readme.

= 1.0.2 =

* Removed the Save Manually button from the post editor meta box. Edited summaries are saved with the standard WordPress post update action.
* Moved Bulk Generate to **Tools > TLDRPress**.
* Moved plugin settings to **Settings > TLDRPress**.

= 1.0.1 =

* Removed dedicated Gutenberg block and Elementor widget in favor of the standard `[tldrpress]` shortcode for simpler manual placement.
* Updated documentation for shortcode-based placement.

= 1.0.0 =

* Initial release.
* OpenAI summary generation.
* Post editor meta box.
* Bulk AJAX generation.
* Settings page.
* Frontend automatic display.
* Shortcode support.
* REST API endpoint.

== Upgrade Notice ==

= 1.0.3 =

Updated author to webmaster.sh.

= 1.0.2 =

Admin navigation is now under the standard WordPress Tools and Settings menus. Use the normal WordPress post update action to save edited summaries.

= 1.0.1 =

Dedicated block and Elementor widget removed. Use `[tldrpress]` in a Shortcode block or page builder shortcode widget for custom placement.

= 1.0.0 =

Initial release.

== Privacy ==

TLDRPress sends post content to OpenAI only when a summary is generated. Generated summaries are stored in the WordPress database as post meta. TLDRPress does not send visitor data to OpenAI and does not call OpenAI on frontend page loads.

Site owners should review OpenAI's data policies and update their own privacy policy as appropriate.
