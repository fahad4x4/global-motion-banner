# 🚀 Global Motion Banner

> A multi-type banner plugin for **GetSimple CMS**: show announcements, promotions, and alerts with control over style, motion, page targeting, and scheduling.

![Version](https://img.shields.io/badge/version-2.2-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![GetSimple](https://img.shields.io/badge/GetSimple-3.3.x-orange)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)

**[English](#english) | [العربية](#arabic)**

---

<a id="english"></a>

## English

### Overview

Global Motion Banner is a single-file GetSimple CMS plugin (`global_motion_banner.php`) for managing any number of site-wide banners from one admin screen. Each banner has its own type, colors, images, size, motion, close button, page targeting, and schedule. Banners are printed through the theme's header hook and stored in one XML file in GetSimple's data folder.

### Features

- **5 banner types**: Scrolling Marquee, Static Bar, Fading Messages, Message Slider, Sticky Notification.
- **Multi-banner management**: create unlimited banners, set their display order, and switch each one on or off from the list screen.
- **Page targeting**: all pages, homepage only, specific pages, or all pages except a list.
- **Scheduling**: optional start date and end date per banner; it shows and hides itself automatically.
- **Visitor dismiss**: optional close button (circle, square, or text style) with a hide duration of: until the browser is closed, 1 day, 1 week, 1 month, or forever.
- **Images**: optional background image and content image (shown beside the text), each with a **📁 Browse** button that opens GetSimple's built-in file browser.
- **Live preview**: see colors, text, height, font size, and marquee speed and direction update while you edit.
- **Emoji picker**: a built-in emoji bar for announcement text.
- **Security**: HMAC-based CSRF nonce on all admin forms, plus validation and sanitization of every input.
- **Lightweight**: pure PHP and vanilla JavaScript, no external dependencies.

### Requirements

- GetSimple CMS **3.3.x** (including Community Edition)
- PHP **7.4+** with the SimpleXML and mbstring extensions (enabled in most PHP installations)
- Write access to GetSimple's `data/other/` folder
- No additional dependencies

### Installation

1. Download `global_motion_banner.php` from this repository.
2. Upload it to your GetSimple `plugins/` directory.
3. In the admin panel, go to **Plugins** and activate **Global Motion Banner**.
4. Open **Settings → 📢 Motion Banner** in the sidebar.

### Usage

1. Click **+ New Banner** (or **+ Create Your First Banner**).
2. Give it an internal title, a display order (lower is shown first), and pick a banner type.
3. Enter the text, or the list of messages (one per line) for the Fade and Slider types.
4. Set colors, optional images, height, font size and weight, and the motion options for the chosen type.
5. Configure the close button, page targeting, and schedule.
6. Click **Save Banner**. From the list you can edit, delete, or toggle each banner **On / Off**.

The admin screen is available at `admin/load.php?id=global_motion_banner`.

#### Banner types

| Type | Description | Type-specific options |
|------|-------------|-----------------------|
| 📜 **Marquee** | Classic scrolling text; pauses on hover | Direction (right-to-left / left-to-right), speed in seconds, repeat count (1–10) |
| 📌 **Static** | Centered bar, no animation | — |
| ✨ **Fade** | Several messages that fade in and out in turn | Duration per message (500–20,000 ms) |
| 🎠 **Slider** | Messages that slide in one after another | Interval (500–30,000 ms), slide direction |
| 📍 **Sticky** | Bar fixed to the top of the screen while scrolling | — |

#### Page targeting

| Option | Behavior |
|--------|----------|
| `All Pages` | Banner appears on every page |
| `Homepage Only` | Shows only on the front page |
| `Specific Pages` | Shows only on the listed page slugs (e.g. `about, contact`) |
| `All Except...` | Shows everywhere except the listed slugs |

The slug is the page `id` from the URL (for example, `about` in `?id=about`). A request with no `id` is treated as the homepage.

#### Scheduling

Each banner has an optional **Start Date** and **End Date** (`YYYY-MM-DD`). Leave them empty to show the banner indefinitely. Dates are checked on the server on every page load, so no cron job is needed.

#### Security notes

- All admin forms carry a CSRF nonce generated with HMAC-SHA256 from GetSimple's `GSKEY`; it does not depend on PHP sessions and stays valid for the current and previous hour.
- Inputs are validated and sanitized before saving:
  - Hex colors checked with a regular expression.
  - Numeric fields limited to allowed min/max ranges.
  - Choice fields checked against allowed values.
  - URLs validated with `FILTER_VALIDATE_URL`.
  - Dates checked against the `YYYY-MM-DD` format.
  - Text fields stripped of HTML tags.
- The visitor's dismiss state is kept in the browser (`sessionStorage` / `localStorage`): no cookies and no server calls. The dismiss key changes when the banner's text, background color, or content image changes, so an edited banner reappears.

### Project structure

Repository:

```
global-motion-banner/
├── global_motion_banner.php   ← the whole plugin (single file)
├── LICENSE                    ← MIT
└── README.md
```

Data created on your site:

```
data/other/
└── global_motion_banners.xml  ← created automatically on first save
```

### Changelog

#### v2.2
- **📁 Browse** buttons now return the chosen file through GetSimple's file browser callback, so the image field and live preview update.
- Fixed a duplicate `class` attribute on the close-button options.

#### v2.1
- CSRF nonce switched from PHP sessions to an HMAC token based on `GSKEY`.
- **📁 Browse** buttons for the background and content images, using GetSimple's file browser.
- Flatter, simpler admin interface.

#### v2.0
- Complete rewrite with multi-banner support.
- 4 new banner types (static, fade, slider, sticky).
- Page targeting and scheduling.
- CSRF nonce protection.
- Full input validation and sanitization.
- Smart dismiss key (resets when the banner content changes).
- Live admin preview.

#### v1.0
- Initial release: a single scrolling marquee banner.
- Basic settings (color, speed, direction, close button).

### Contributing

Bug reports and pull requests are welcome. Please open an issue first to discuss what you would like to change.

### License

Released under the **MIT License**. See [LICENSE](LICENSE) for details.

### Author

**Fahad ALGhathbar**: [@fahad4x4](https://github.com/fahad4x4)

---

<a id="arabic"></a>

<div dir="rtl">

## العربية

### نظرة عامة

**Global Motion Banner** إضافة لنظام **GetSimple CMS** في ملف واحد (`global_motion_banner.php`)، تتيح إدارة أي عدد من الشرائط الإعلانية على مستوى الموقع من شاشة إدارة واحدة. لكل شريط نوعه وألوانه وصوره وأبعاده وحركته وزر إغلاقه وصفحات ظهوره وجدوله الزمني. تُعرض الشرائط عبر خطّاف رأس القالب (theme header)، وتُحفظ في ملف XML واحد داخل مجلد البيانات في GetSimple.

### المزايا

- **خمسة أنواع من الشرائط**: نص متحرك (Marquee)، وشريط ثابت، ورسائل متلاشية، وشرائح رسائل متتابعة، وإشعار مثبّت أعلى الشاشة.
- **إدارة عدة شرائط**: إنشاء عدد غير محدود من الشرائط، وتحديد ترتيب ظهورها، وتشغيل كل منها أو إيقافه من شاشة القائمة.
- **استهداف الصفحات**: كل الصفحات، أو الصفحة الرئيسية فقط، أو صفحات محددة، أو كل الصفحات عدا قائمة معيّنة.
- **الجدولة**: تاريخ بدء وتاريخ انتهاء اختياريان لكل شريط، فيظهر ويختفي تلقائيًا.
- **إغلاق من الزائر**: زر إغلاق اختياري (دائري أو مربع أو نصي) مع مدة إخفاء: حتى إغلاق المتصفح، أو يوم، أو أسبوع، أو شهر، أو دائمًا.
- **الصور**: صورة خلفية اختيارية وصورة محتوى تظهر بجانب النص، ولكل منهما زر **📁 Browse** يفتح متصفح الملفات المدمج في GetSimple.
- **معاينة حيّة**: تتحدّث الألوان والنص والارتفاع وحجم الخط وسرعة النص المتحرك واتجاهه أثناء التعديل.
- **منتقي الرموز التعبيرية**: شريط رموز تعبيرية مدمج لنص الإعلان.
- **الأمان**: رمز تحقق CSRF مبني على HMAC في كل نماذج الإدارة، مع التحقق من جميع المدخلات وتنقيتها.
- **خفيفة**: مكتوبة بلغة PHP وJavaScript خالصة، دون أي اعتماديات خارجية.

### المتطلبات

- نظام GetSimple CMS الإصدار **3.3.x** (بما فيه إصدار المجتمع Community Edition)
- PHP الإصدار **7.4** أو أحدث مع امتدادي SimpleXML وmbstring (مفعّلان في أغلب بيئات PHP)
- صلاحية الكتابة على مجلد `data/other/` في GetSimple
- لا توجد اعتماديات إضافية

### التثبيت

1. نزّل الملف `global_motion_banner.php` من هذا المستودع.
2. ارفعه إلى مجلد `plugins/` في موقعك.
3. من لوحة الإدارة، افتح **Plugins** وفعّل **Global Motion Banner**.
4. افتح **Settings ← 📢 Motion Banner** من القائمة الجانبية.

### الاستخدام

1. اضغط **+ New Banner** (أو **+ Create Your First Banner**).
2. أدخل عنوانًا داخليًا وترتيب الظهور (الأصغر يظهر أولًا)، واختر نوع الشريط.
3. اكتب النص، أو قائمة الرسائل (رسالة في كل سطر) لنوعَي الرسائل المتلاشية والشرائح.
4. اضبط الألوان والصور الاختيارية والارتفاع وحجم الخط وسُمكه، وخيارات الحركة الخاصة بالنوع المختار.
5. اضبط زر الإغلاق واستهداف الصفحات والجدولة.
6. اضغط **Save Banner**. ومن القائمة يمكنك تعديل كل شريط أو حذفه أو تبديل حالته بين **On / Off**.

رابط شاشة الإدارة: `admin/load.php?id=global_motion_banner`.

#### أنواع الشرائط

| النوع | الوصف | خيارات خاصة بالنوع |
|-------|-------|---------------------|
| 📜 **Marquee** | نص متحرك تقليدي يتوقف عند مرور المؤشر عليه | الاتجاه (من اليمين لليسار / من اليسار لليمين)، والسرعة بالثواني، وعدد التكرار (1–10) |
| 📌 **Static** | شريط في المنتصف بلا حركة | — |
| ✨ **Fade** | عدة رسائل تظهر وتتلاشى بالتناوب | مدة كل رسالة (500–20,000 مللي ثانية) |
| 🎠 **Slider** | رسائل تنزلق واحدة تلو الأخرى | الفاصل الزمني (500–30,000 مللي ثانية)، واتجاه الانزلاق |
| 📍 **Sticky** | شريط مثبّت أعلى الشاشة أثناء التمرير | — |

#### استهداف الصفحات

| الخيار | السلوك |
|--------|--------|
| `All Pages` | يظهر الشريط في كل الصفحات |
| `Homepage Only` | يظهر في الصفحة الرئيسية فقط |
| `Specific Pages` | يظهر في الصفحات المذكورة فقط بمعرّفاتها (مثل `about, contact`) |
| `All Except...` | يظهر في كل الصفحات عدا المذكورة |

معرّف الصفحة (slug) هو قيمة `id` في الرابط (مثل `about` في `?id=about`)، والطلب الذي لا يحتوي على `id` يُعامل على أنه الصفحة الرئيسية.

#### الجدولة

لكل شريط **تاريخ بدء** و**تاريخ انتهاء** اختياريان بصيغة `YYYY-MM-DD`. اتركهما فارغين ليظهر الشريط دون توقف. تُفحص التواريخ على الخادم عند كل تحميل للصفحة، فلا حاجة إلى مهام cron.

#### ملاحظات أمنية

- تحمل كل نماذج الإدارة رمز تحقق CSRF يُولَّد بخوارزمية HMAC-SHA256 من مفتاح `GSKEY` في GetSimple، ولا يعتمد على جلسات PHP، ويبقى صالحًا خلال الساعة الحالية والساعة السابقة.
- يُتحقق من المدخلات وتُنقّى قبل الحفظ:
  - الألوان السداسية بتعبير نمطي (Regex).
  - الحقول الرقمية ضمن حدود دنيا وعليا مسموح بها.
  - حقول الاختيار مقابل القيم المسموح بها.
  - الروابط عبر `FILTER_VALIDATE_URL`.
  - التواريخ وفق صيغة `YYYY-MM-DD`.
  - الحقول النصية بإزالة وسوم HTML.
- تُحفظ حالة إغلاق الزائر للشريط في متصفحه (`sessionStorage` / `localStorage`)، دون ملفات ارتباط ودون اتصال بالخادم. ويتغيّر مفتاح الإغلاق عند تغيير نص الشريط أو لون خلفيته أو صورة محتواه، فيعود الشريط المعدَّل إلى الظهور.

### هيكل المشروع

المستودع:

```
global-motion-banner/
├── global_motion_banner.php   ← الإضافة كاملة (ملف واحد)
├── LICENSE                    ← رخصة MIT
└── README.md
```

البيانات التي تُنشأ في موقعك:

```
data/other/
└── global_motion_banners.xml  ← يُنشأ تلقائيًا عند أول حفظ
```

### سجل التغييرات

#### الإصدار 2.2
- أزرار **📁 Browse** تعيد الملف المختار عبر متصفح ملفات GetSimple، فيتحدّث حقل الصورة والمعاينة الحيّة.
- إصلاح تكرار خاصية `class` في خيارات زر الإغلاق.

#### الإصدار 2.1
- استبدال رمز التحقق المعتمد على جلسات PHP برمز HMAC مبني على `GSKEY`.
- إضافة أزرار **📁 Browse** لصورتي الخلفية والمحتوى باستخدام متصفح ملفات GetSimple.
- واجهة إدارة أبسط بتصميم مسطّح.

#### الإصدار 2.0
- إعادة كتابة كاملة مع دعم الشرائط المتعددة.
- أربعة أنواع جديدة (ثابت، متلاشٍ، شرائح، مثبّت).
- استهداف الصفحات والجدولة.
- حماية CSRF.
- تحقق كامل من المدخلات وتنقيتها.
- مفتاح إغلاق ذكي يُعاد ضبطه عند تغيّر محتوى الشريط.
- معاينة حيّة في لوحة الإدارة.

#### الإصدار 1.0
- الإصدار الأول: شريط نص متحرك واحد.
- إعدادات أساسية (اللون، والسرعة، والاتجاه، وزر الإغلاق).

### المساهمة

نرحّب بالإبلاغ عن الأخطاء وبطلبات الدمج (Pull Requests). يُفضَّل فتح بلاغ (Issue) أولًا لمناقشة التغيير المقترح.

### الرخصة

المشروع منشور بموجب **رخصة MIT**. راجع ملف [LICENSE](LICENSE) للتفاصيل.

### المؤلف

**فهد الغثبر (Fahad ALGhathbar)**: [@fahad4x4](https://github.com/fahad4x4)

</div>
