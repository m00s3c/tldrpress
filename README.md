# TLDRPress

TLDRPress is a lightweight WordPress plugin for generating AI-powered TL;DR summaries for blog posts, publishers, news sites, and editorial teams.

Summaries are generated in the WordPress admin and stored permanently in post meta. The plugin never calls AI services during frontend page loads.

[View TL;DR Example ➞](https://tldrpress.dev/demos/cybertoaster9000-allegedly-exposes-internal-pancake-analytics-database/)

---

## Features

- Generate summaries with the OpenAI API
- Manual generation from the post editor
- Optional auto-generation when a post is published
- Bulk generation tool with AJAX batch processing
- Bullet or paragraph summaries
- Short, medium, or long summary lengths
- Editable saved summaries
- Automatic frontend display with configurable placement
- Manual display with shortcode support
- Gutenberg block support
- Elementor widget support
- Responsive frontend summary box
- Dark mode compatible styling

---

## Requirements

- WordPress 6.2 or higher
- PHP 7.4 or higher
- OpenAI API key

---

## Installation

1. Upload the plugin ZIP through **Plugins → Add New**
2. Activate **TLDRPress**
3. Add your OpenAI API key in **TLDRPress Settings**
4. Choose your preferred summary settings
5. Start generating summaries

---

## Shortcode

Display the current post summary:

```text
[tldrpress]
```

Display a specific post summary:

```text
[tldrpress id="123"]
```

---

## Elementor & Gutenberg

TLDRPress includes both a dedicated Elementor widget and Gutenberg block for flexible summary placement inside templates, posts, and layouts.

---

## Bulk Generation

Generate summaries for existing posts using the built-in AJAX-powered bulk generation tool.

---

## Frontend Styling

The frontend TL;DR box uses stable CSS classes for theme and builder overrides.

You can add custom styling in your theme, WordPress Additional CSS, Elementor custom CSS, or a site-specific plugin.

```css
.tldrpress-box {}
.tldrpress-header {}
.tldrpress-icon {}
.tldrpress-content {}
.tldrpress-content ul {}
.tldrpress-content li {}
```

### Example Custom Styling

```css
/* Outer TL;DR card */
.tldrpress-box {
  margin: 2rem 0;
  padding: 1.25rem 1.5rem;
  background: #f8fafc;
  border: 1px solid #dbe4ee;
  border-left: 4px solid #2563eb;
  border-radius: 10px;
  box-shadow: none;
}

/* Heading row */
.tldrpress-header {
  margin-bottom: 0.75rem;
}

/* Heading text */
.tldrpress-header h2 {
  color: #0f172a;
  font-size: 1.1rem;
  font-weight: 700;
}

/* Optional icon */
.tldrpress-icon {
  background: #0f172a;
  color: #ffffff;
}

/* Summary body text */
.tldrpress-content {
  color: #334155;
  font-size: 1rem;
  line-height: 1.7;
}

/* Bullet spacing */
.tldrpress-content li {
  margin-bottom: 0.45rem;
}

/* Mobile adjustments */
@media (max-width: 600px) {
  .tldrpress-box {
    padding: 1rem;
    border-radius: 8px;
  }

  .tldrpress-header h2 {
    font-size: 1rem;
  }
}
```

---

## Privacy

When generating summaries, TLDRPress sends post content to OpenAI for processing. Generated summaries are stored locally in your WordPress database as post meta.

TLDRPress does not send visitor data to OpenAI and does not call OpenAI on frontend page loads.

---

## License

TLDRPress is licensed under the GNU General Public License v2 or later (GPLv2+), the same license used by WordPress.

This plugin is free software. You may redistribute and/or modify it under the terms of the GPL.
