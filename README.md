# 🚀 Global Motion Banner

> A professional multi-type banner plugin for **GetSimple CMS** — display announcements, promotions, and alerts with full control over style, behavior, targeting, and scheduling.

![Version](https://img.shields.io/badge/version-2.0-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![GetSimple](https://img.shields.io/badge/GetSimple-3.3.x-orange)

---

## ✨ Features

- **5 Banner Types** — Scrolling Marquee, Static Bar, Fading Messages, Message Slider, Sticky Notification
- **Multi-Banner Management** — Create unlimited banners, reorder, enable/disable from one screen
- **Page Targeting** — Show on all pages, homepage only, specific pages, or exclude pages
- **Scheduling** — Set a start date and end date per banner (auto show/hide)
- **Visitor Dismiss** — Close button with configurable hide duration (session, 1 day, 1 week, 1 month, forever)
- **Live Preview** — See changes in real time before saving
- **Emoji Picker** — Built-in emoji toolbar for announcements
- **Security** — CSRF nonce protection on all forms + full input validation
- **Lightweight** — Pure PHP + vanilla JS, no external dependencies

---

## 📺 Banner Types

| Type | Description |
|------|-------------|
| 📜 **Marquee** | Classic scrolling text, pauses on hover |
| 📌 **Static** | Centered fixed bar, no animation |
| ✨ **Fade** | Multiple messages that fade in and out |
| 🎠 **Slider** | Messages that slide in with a smooth animation |
| 📍 **Sticky** | Fixed to the top of the screen on scroll |

---

## 📦 Installation

1. Download `global_motion_banner.php`
2. Upload it to your GetSimple `/plugins/` directory
3. Go to **Settings → Plugins** in the admin panel and activate it
4. Click **🚀 Motion Banner** in the settings sidebar

---

## 🎯 Page Targeting Options

| Option | Behavior |
|--------|----------|
| `All Pages` | Banner appears on every page |
| `Homepage Only` | Only shows on the front page |
| `Specific Pages` | Enter page slugs (e.g. `about, contact`) |
| `All Except...` | Show everywhere except listed slugs |

---

## 📅 Scheduling

Each banner has an optional **Start Date** and **End Date**.  
Leave empty to show the banner indefinitely.  
The plugin compares dates server-side on every page load — no cron jobs needed.

---

## 🔒 Security

- All form submissions are protected with a **CSRF nonce** via PHP sessions
- Input values are validated and sanitized before saving:
  - Hex colors validated with regex
  - Numeric fields clamped to min/max ranges
  - Enum fields checked against allowed values
  - URLs validated with `FILTER_VALIDATE_URL`
  - Text fields stripped of HTML tags
- Visitor dismiss state is stored in `localStorage` / `sessionStorage` — no cookies, no server calls

---

## ⚙️ Requirements

- GetSimple CMS **3.3.x** (including Community Edition)
- PHP **7.4+**
- No additional dependencies

---

## 📁 File Structure

```
plugins/
└── global_motion_banner.php   ← single-file plugin

data/other/
└── global_motion_banners.xml  ← auto-created on first save
```

---

## 📝 Changelog

### v2.0
- Complete rewrite with multi-banner support
- Added 4 new banner types (static, fade, slider, sticky)
- Added page targeting and scheduling
- Added CSRF nonce protection
- Full input validation and sanitization
- Smart dismiss key (resets when banner content changes)
- Live admin preview

### v1.0
- Initial release — single scrolling marquee banner
- Basic settings (color, speed, direction, close button)

---

## 🤝 Contributing

Bug reports and pull requests are welcome.  
Please open an issue first to discuss what you would like to change.

---

## 📄 License

[MIT](LICENSE) — free to use, modify, and distribute.

---

Made with ❤️ by [Fahad4x4](https://github.com/fahad4x4)
