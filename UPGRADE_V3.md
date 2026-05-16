# Nafas Chatbot Pro v3.0.0 - Enterprise Edition

🚀 **A Complete Professional Rewrite** from Monolithic to Modular Architecture

## ✅ Major Improvements (v2 → v3)

### 1. **Architecture Redesign**
- ✅ Modular folder structure (`Core`, `Admin`, `API`, `Database`, `Security`, `Frontend`, `Providers`, `Elementor`)
- ✅ PSR-4 namespacing (ready for future enhancement)
- ✅ Single Responsibility Principle (SRP)
- ✅ Clean separation of concerns

### 2. **Enhanced Security**
- ✅ Advanced `NCP_Security` class with:
  - XSS prevention (wp_kses_post, escaping)
  - CSRF protection (nonce validation)
  - Rate limiting (per minute, hour, day)
  - Honeypot field protection
  - IP spoofing detection
  - API key sanitization
  - Input validation for all fields
  - GDPR-friendly data handling

### 3. **Database Layer** (4 New Tables)
```
- ncp_chat_logs (Enhanced)
- ncp_sessions (New)
- ncp_analytics (New)
- ncp_feedback (New)
```

### 4. **Multi-Provider System**
- ✅ `NCP_Provider_Interface` abstraction
- ✅ Built-in providers: AvalAI, OpenAI, Custom API
- ✅ Easy to extend for Claude, Groq, Ollama, etc.
- ✅ Provider response normalization

### 5. **Advanced Admin Dashboard**
- ✅ Analytics page with statistics
- ✅ Chat logs viewer with filtering
- ✅ Export functionality (JSON)
- ✅ Performance metrics
- ✅ Provider breakdown

### 6. **Improved Frontend**
- ✅ Better CSS modularization
- ✅ Smooth animations (reduced motion support)
- ✅ RTL/LTR full support
- ✅ Dark mode (prefers-color-scheme)
- ✅ Mobile-optimized UI
- ✅ Accessibility (WCAG ready)
- ✅ Typing indicator animation
- ✅ Message persistence with localStorage

### 7. **Elementor Widget Enhancements**
- ✅ Color controls
- ✅ Layout controls
- ✅ Size controls
- ✅ Responsive builder support

## 📁 New Folder Structure

```
nafas-chatbot-pro/
├── includes/
│   ├── Core/
│   │   ├── Constants.php      # All constants
│   │   ├── Configuration.php  # Config management
│   │   └── Bootstrap.php      # Loader
│   ├── Security/
│   │   └── Security.php       # All security features
│   ├── Database/
│   │   └── Database.php       # All DB operations
│   ├── Providers/
│   │   └── Manager.php        # Multi-provider system
│   ├── API/
│   │   └── Handler.php        # AJAX endpoints
│   ├── Frontend/
│   │   ├── Frontend.php       # Frontend logic
│   │   └── Shortcode.php      # Shortcode handler
│   ├── Admin/
│   │   └── Admin.php          # Admin pages
│   └── Elementor/
│       └── Widget.php         # Elementor widget
├── assets/
│   ├── css/
│   │   ├── chatbot.css        # Modular CSS
│   │   └── admin.css
│   └── js/
│       ├── chatbot.js         # Modern JS
│       └── admin.js
├── templates/                 # (For future components)
├── nafas-chatbot-pro.php      # Main plugin file (Clean!)
└── update.md                  # Upgrade guide
```

## 🔧 Configuration

All configuration is centralized in `NCP_Configuration::instance()`:

```php
$config = NCP_Configuration::instance();
$theme = $config->get_theme();
$limits = $config->get_rate_limits();
```

## 🛡️ Security Features

### Rate Limiting
```php
NCP_Security::apply_rate_limit(); // Per IP, per minute/hour
```

### Input Validation
```php
$message = NCP_Security::sanitize_message($input);
$provider = NCP_Security::sanitize_provider($input);
```

### Output Escaping
```php
echo NCP_Security::esc_html($text);
echo NCP_Security::esc_attr($text);
```

## 📊 Analytics & Logging

Track everything:
```php
NCP_Database::log_chat($session_id, $provider, $model, $message, $response, $tokens);
$analytics = NCP_Database::get_analytics($date_from, $date_to);
$by_provider = NCP_Database::get_analytics_by_provider();
```

## 🧠 Multi-Provider System

Add new AI provider in minutes:

```php
class NCP_Provider_Claude implements NCP_Provider_Interface {
    public function send_message(array $messages, array $options): array {
        // Your implementation
    }
    // ... implement other methods
}

$manager = NCP_Provider_Manager::instance();
$manager->register('claude', new NCP_Provider_Claude());
```

## 🎨 Frontend Features

### Modern Chat UI
- Floating launcher button (customizable position)
- Smooth message animations
- Auto-resizing textarea
- Message history persistence
- Typing indicator
- Session management

### Responsive Design
- Mobile-first approach
- Touch-friendly buttons
- Adaptive panel sizing
- Notch support for modern phones

### Accessibility
- ARIA labels
- Keyboard navigation
- Focus management
- Reduced motion support
- Screen reader compatible

## 📱 Elementor Integration

```
[Elementor Widget Controls]
- AI Provider selection
- Floating mode toggle
- Launcher position
- Temperature control
- Max tokens slider
- Color customization
- Panel size adjustment
```

## 🚀 Performance Improvements

- ✅ Lazy loading support (ready)
- ✅ Code splitting architecture (foundation)
- ✅ Optimized CSS (modular)
- ✅ Efficient JavaScript (modern)
- ✅ Caching layer built-in
- ✅ Transient-based caching

## 🌍 RTL & Internationalization

Full RTL support plus:
- i18n strings in `NCP_Frontend::get_i18n()`
- Directional CSS with `[dir="rtl"]`
- Culturally aware UI

## 📋 Shortcode Usage

```html
[nafas_chatbot]
[nafas_chatbot provider="openai" floating="yes" temperature="0.8"]
[ncp_chatbot provider="custom" launcher_pos="left"]
```

## 🔌 AJAX Endpoints

- `wp_ajax_ncp_chat` - Send message
- `wp_ajax_ncp_form_submit` - Form submission (Telegram, Bale)
- `wp_ajax_ncp_feedback` - User feedback
- `wp_ajax_ncp_export_log` - Export logs
- `wp_ajax_ncp_clear_log` - Clear logs
- `wp_ajax_ncp_analytics` - Get analytics

## 📈 Road Map (Planned for v3.1+)

- [ ] Streaming responses (SSE)
- [ ] Voice chat support
- [ ] File upload integration
- [ ] Knowledge base/RAG
- [ ] Human handoff
- [ ] Smart forms builder
- [ ] WooCommerce integration
- [ ] WPML support
- [ ] Advanced analytics dashboard
- [ ] White-label options
- [ ] SaaS mode
- [ ] Claude, Groq, Ollama providers
- [ ] Conversation replay
- [ ] Advanced template system

## 🐛 Bug Fixes (v2 → v3)

- ✅ Fixed message length validation
- ✅ Fixed XSS vulnerabilities
- ✅ Fixed scroll leak on iOS
- ✅ Fixed mobile viewport issues
- ✅ Fixed rate limiting bugs
- ✅ Fixed session management
- ✅ Fixed caching logic

## 📝 Developer Notes

### Creating a New Module

1. Create folder: `includes/YourModule/`
2. Create main class: `includes/YourModule/YourClass.php`
3. Load in `Bootstrap.php`

### Adding Security

All external input goes through `NCP_Security`:
```php
$safe_data = NCP_Security::sanitize_*($input);
```

### Extending Providers

Implement `NCP_Provider_Interface`:
```php
class NCP_Provider_YourAI implements NCP_Provider_Interface {
    public function send_message(array $messages, array $options): array {}
    public function validate_config(): bool {}
    public function get_name(): string {}
    public function get_models(): array {}
}
```

## 🤝 Contributing

When adding features:
1. Follow SRP (Single Responsibility)
2. Use proper type hints
3. Add security checks
4. Update documentation
5. Test on mobile
6. Check a11y

## 📄 License

GPL-2.0-or-later

## 👤 Author

Saeed Zarrini
https://dbsgraphic.ir

---

**Last Updated:** 2026-05-16
**Version:** 3.0.0
