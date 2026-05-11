# Nafas Chatbot Pro v2.0.0

Enterprise-grade AI chatbot plugin for WordPress & Elementor.  
Unified, production-ready rewrite merging the best of both previous versions.

---

## 📦 Installation

1. Upload the `nafas-chatbot-pro` folder to `/wp-content/plugins/`
2. Activate the plugin from **Plugins → Installed Plugins**
3. Go to **Nafas Chatbot** in the WordPress admin menu
4. Configure your AI API key and settings

---

## ✨ Feature Overview

### AI Providers
| Provider | Notes |
|----------|-------|
| **AvalAI** | Iranian service, ideal for Persian content |
| **OpenAI**  | GPT-4o-mini, GPT-4o, etc. |
| **Custom**  | Any OpenAI-compatible endpoint |

### Multi-view Chat Interface
- **Main Menu** — greeting + navigation buttons
- **Company Chat** — AI chat about the company
- **Product Chat** — product-specific AI assistant
- **ADR Form** — adverse drug reaction reporting
- **Consult Form** — consultation request
- **Success Screen** — confirmation after form submission

### UX Features
- ✅ RTL/LTR direction support
- ✅ Dark / Light / Auto theme
- ✅ Floating launcher with unread badge
- ✅ Persistent conversation history (localStorage)
- ✅ Markdown rendering (bold, italic, code, lists, links)
- ✅ Emoji picker
- ✅ Browser notifications
- ✅ Keyboard accessible (WCAG AA)
- ✅ Smooth animations
- ✅ Responsive (mobile-optimized)

### Admin Features
- 📊 Dashboard with usage metrics
- 📋 Full chat log with pagination
- 📥 Export log as CSV or JSON
- 🗑️ Clear log
- 🔔 Bale (بله) notifications
- 📨 Telegram notifications
- 🚦 Rate limiting per IP (minute + hour)
- ⚡ Response caching
- 🔒 Nonce-verified AJAX requests

---

## 🔧 Shortcode Usage

Basic:
```
[nafas_chatbot]
```

With parameters:
```
[nafas_chatbot
  company_name="شرکت من"
  provider="avalai"
  model="gpt-4o-mini"
  theme_primary="#0066cc"
  theme_mode="auto"
  text_direction="rtl"
  show_consult="true"
  panel_width="380px"
]
```

### All Shortcode Parameters

| Parameter | Values | Default |
|-----------|--------|---------|
| `company_name` | text | from settings |
| `company_id` | text | from settings |
| `products_json` | JSON string | from settings |
| `provider` | `avalai` \| `openai` \| `custom` | from settings |
| `model` | model name | `gpt-4o-mini` |
| `system_prompt` | text | from settings |
| `temperature` | 0–2 | `0.7` |
| `max_tokens` | 64–4096 | `512` |
| `history_length` | 0–12 | `6` |
| `show_launcher` | `true`\|`false` | `true` |
| `show_company` | `true`\|`false` | `true` |
| `show_products` | `true`\|`false` | `true` |
| `show_adr` | `true`\|`false` | `true` |
| `show_consult` | `true`\|`false` | `true` |
| `floating_mode` | `true`\|`false` | `true` |
| `open_by_default` | `true`\|`false` | `false` |
| `text_direction` | `rtl`\|`ltr` | `rtl` |
| `theme_mode` | `auto`\|`light`\|`dark` | `auto` |
| `launcher_position` | `right`\|`left` | `right` |
| `panel_width` | CSS value | `380px` |
| `panel_height` | CSS value | `600px` |
| `font_family` | font stack | Vazirmatn... |
| `theme_primary` | hex color | `#b01618` |

---

## 🎨 Elementor Widget

Add the **Nafas Chatbot Pro** widget from the Elementor panel.  
All settings are available as live-preview controls inside Elementor.

---

## 🔔 Bale / Telegram Notifications

When a form is submitted (ADR or Consult), notifications are sent to:
- **Bale** (بله) if token + chat_id are configured
- **Telegram** if bot token + chat_id are configured

Both can be active simultaneously.

---

## 🚦 Security

- All AJAX requests are protected with WordPress nonces
- Rate limiting: configurable per-IP requests per minute/hour
- Server-side API keys (never exposed to browser)
- Full input sanitization and output escaping
- XSS-safe DOM construction (no unsafe innerHTML from user input)

---

## 📁 File Structure

```
nafas-chatbot-pro/
├── nafas-chatbot-pro.php      ← Main plugin file
├── includes/
│   ├── elementor-widget.php   ← Elementor widget class
│   ├── admin-settings.php     ← Settings page renderer
│   └── admin-logs.php         ← Logs page renderer
├── assets/
│   ├── chatbot.css            ← All widget styles (scoped, no conflicts)
│   ├── chatbot.js             ← All widget logic (vanilla JS, no jQuery)
│   └── admin.css              ← Admin dashboard styles
└── README.md
```

---

## 🛠️ Requirements

- WordPress 6.2+
- PHP 8.0+
- Elementor 3.x (optional, for widget)

---

## 📝 Changelog

### v2.0.0 (2026-02-21)
- Complete professional rewrite
- Merged and improved all features from both previous versions
- Added Telegram notifications
- Added chat logging with export
- Added response caching
- Added dark mode & auto theme
- Full accessibility (ARIA, keyboard nav)
- Vanilla JS — no jQuery dependency on frontend
- Zero CSS conflicts (fully scoped)
- PHP 8.0+ typed properties and modern syntax
