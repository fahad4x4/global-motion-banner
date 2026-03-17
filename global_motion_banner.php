<?php
/*
Plugin Name: Global Motion Banner
Description: Multi-type animated banners — marquee, static, fade, slider, sticky — with page targeting, scheduling, and visitor dismiss support.
Version: 2.1
Author: Fahad4x4
Author URI: https://github.com/fahad4x4
*/

if (!defined('IN_GS')) { die('you cannot load this page directly.'); }

// ────────────────────────────────────────────────────────────────────────────
// Constants
// ────────────────────────────────────────────────────────────────────────────
define('GMB_ID',      'global_motion_banner');
define('GMB_XML',     GSDATAOTHERPATH . 'global_motion_banners.xml');
define('GMB_VERSION', '2.1');

// ────────────────────────────────────────────────────────────────────────────
// Validation Helpers
// ────────────────────────────────────────────────────────────────────────────
function gmb_validate_hex($value, $fallback = '#000000') {
    $v = trim((string)$value);
    return preg_match('/^#[a-fA-F0-9]{6}$/', $v) ? $v : $fallback;
}
function gmb_validate_int($value, $min, $max, $fallback) {
    $v = (int)$value;
    return ($v >= $min && $v <= $max) ? $v : $fallback;
}
function gmb_validate_enum($value, array $allowed, $fallback) {
    return in_array((string)$value, $allowed, true) ? (string)$value : $fallback;
}
function gmb_sanitize_url($value) {
    $v = trim((string)$value);
    return filter_var($v, FILTER_VALIDATE_URL) ? $v : '';
}
function gmb_sanitize_text($value) {
    return trim(strip_tags((string)$value));
}

// ────────────────────────────────────────────────────────────────────────────
// Safe Output Helpers
// ────────────────────────────────────────────────────────────────────────────
function gmb_e($val) {
    echo htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
function gmb_attr($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
function gmb_out($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

// ────────────────────────────────────────────────────────────────────────────
// Nonce — بدون session، يعتمد على GSKEY مثل باقي إضافات GS
// ────────────────────────────────────────────────────────────────────────────
function gmb_create_nonce() {
    $secret = defined('GSKEY') ? GSKEY : md5(__FILE__);
    return hash_hmac('sha256', 'gmb_' . date('YmdH'), $secret);
}
function gmb_verify_nonce($token) {
    if (!defined('IN_GS')) return false;
    $secret  = defined('GSKEY') ? GSKEY : md5(__FILE__);
    $current = hash_hmac('sha256', 'gmb_' . date('YmdH'), $secret);
    $prev    = hash_hmac('sha256', 'gmb_' . date('YmdH', strtotime('-1 hour')), $secret);
    return hash_equals($current, (string)$token)
        || hash_equals($prev,    (string)$token);
}

// ────────────────────────────────────────────────────────────────────────────
// Banner Default Values
// ────────────────────────────────────────────────────────────────────────────
function gmb_banner_defaults() {
    return array(
        'id'              => '',
        'title'           => 'New Banner',
        'type'            => 'marquee',
        'enabled'         => '1',
        'order'           => '0',
        'text'            => 'Welcome! 🌟',
        'slider_messages' => 'Welcome! 🌟||Check our offers 🛍️||Free shipping today ✈️',
        'fade_messages'   => 'Welcome! 🌟||Great deals await 💎',
        'bg_color'        => '#2c3e50',
        'text_color'      => '#ffffff',
        'bg_img'          => '',
        'content_img'     => '',
        'banner_height'   => '60',
        'img_height'      => '40',
        'font_size'       => '18',
        'font_weight'     => 'bold',
        'dir'             => 'right',
        'speed'           => '20',
        'repeat_count'    => '3',
        'slider_interval' => '4000',
        'fade_duration'   => '3000',
        'closeable'       => '1',
        'close_duration'  => 'session',
        'close_btn_style' => 'circle',
        'target'          => 'all',
        'target_pages'    => '',
        'exclude_pages'   => '',
        'start_date'      => '',
        'end_date'        => '',
    );
}

// ────────────────────────────────────────────────────────────────────────────
// Data Layer
// ────────────────────────────────────────────────────────────────────────────
function gmb_load_all() {
    if (!file_exists(GMB_XML)) return array();
    $xml = @simplexml_load_file(GMB_XML);
    if (!$xml || !isset($xml->banner)) return array();
    $defaults = gmb_banner_defaults();
    $banners  = array();
    foreach ($xml->banner as $b) {
        $banner = array();
        foreach ($defaults as $key => $def) {
            $banner[$key] = isset($b->$key) ? (string)$b->$key : $def;
        }
        $banners[] = $banner;
    }
    usort($banners, function($a, $b) { return (int)$a['order'] - (int)$b['order']; });
    return $banners;
}

function gmb_save_all(array $banners) {
    $xml = new SimpleXMLElement('<banners></banners>');
    foreach ($banners as $b) {
        $node = $xml->addChild('banner');
        foreach ($b as $k => $v) {
            $node->addChild($k, htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'));
        }
    }
    $xml->asXML(GMB_XML);
}

function gmb_get_banner($id) {
    foreach (gmb_load_all() as $b) {
        if ((string)$b['id'] === (string)$id) return $b;
    }
    return null;
}

function gmb_next_id() {
    $banners = gmb_load_all();
    if (empty($banners)) return 1;
    return max(array_map(function($b) { return (int)$b['id']; }, $banners)) + 1;
}

function gmb_delete_banner($id) {
    $banners = array_values(array_filter(gmb_load_all(), function($b) use ($id) {
        return (string)$b['id'] !== (string)$id;
    }));
    gmb_save_all($banners);
}

// ────────────────────────────────────────────────────────────────────────────
// Validate & Sanitize POST Data
// ────────────────────────────────────────────────────────────────────────────
function gmb_validate_post($p, $existing_id = '') {
    $b = array();
    $b['id']    = ($existing_id !== '') ? (string)(int)$existing_id : (string)gmb_next_id();
    $b['order'] = (string)gmb_validate_int($p['order'] ?? 0, 0, 9999, 0);
    $b['title'] = gmb_sanitize_text($p['title'] ?? '') ?: 'New Banner';
    $b['type']  = gmb_validate_enum($p['type'] ?? 'marquee',
                    array('marquee','static','fade','slider','sticky'), 'marquee');
    $b['enabled']   = isset($p['enabled'])   ? '1' : '0';
    $b['closeable'] = isset($p['closeable']) ? '1' : '0';
    $b['text'] = gmb_sanitize_text($p['text'] ?? '');
    $to_pipe = function($raw) {
        $lines = explode("\n", str_replace("\r", '', (string)$raw));
        return implode('||', array_filter(array_map('trim', $lines)));
    };
    $b['slider_messages'] = $to_pipe($p['slider_messages'] ?? '');
    $b['fade_messages']   = $to_pipe($p['fade_messages']   ?? '');
    $b['bg_color']      = gmb_validate_hex($p['bg_color']    ?? '', '#2c3e50');
    $b['text_color']    = gmb_validate_hex($p['text_color']  ?? '', '#ffffff');
    $b['bg_img']        = gmb_sanitize_url($p['bg_img']      ?? '');
    $b['content_img']   = gmb_sanitize_url($p['content_img'] ?? '');
    $b['banner_height'] = (string)gmb_validate_int($p['banner_height'] ?? 60,   20,  300,   60);
    $b['img_height']    = (string)gmb_validate_int($p['img_height']    ?? 40,   10,  200,   40);
    $b['font_size']     = (string)gmb_validate_int($p['font_size']     ?? 18,   10,  100,   18);
    $b['font_weight']   = gmb_validate_enum($p['font_weight'] ?? 'bold',
                            array('normal','bold','600'), 'bold');
    $b['dir']             = gmb_validate_enum($p['dir'] ?? 'right', array('right','left'), 'right');
    $b['speed']           = (string)gmb_validate_int($p['speed']           ?? 20,   2,  120,   20);
    $b['repeat_count']    = (string)gmb_validate_int($p['repeat_count']    ?? 3,    1,   10,    3);
    $b['slider_interval'] = (string)gmb_validate_int($p['slider_interval'] ?? 4000, 500, 30000, 4000);
    $b['fade_duration']   = (string)gmb_validate_int($p['fade_duration']   ?? 3000, 500, 20000, 3000);
    $b['close_duration']  = gmb_validate_enum($p['close_duration']  ?? 'session',
                                array('session','1day','7days','30days','forever'), 'session');
    $b['close_btn_style'] = gmb_validate_enum($p['close_btn_style'] ?? 'circle',
                                array('circle','square','text'), 'circle');
    $b['target']        = gmb_validate_enum($p['target'] ?? 'all',
                            array('all','homepage','specific','exclude'), 'all');
    $b['target_pages']  = gmb_sanitize_text($p['target_pages']  ?? '');
    $b['exclude_pages'] = gmb_sanitize_text($p['exclude_pages'] ?? '');
    $date_ok = function($v) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$v); };
    $b['start_date'] = $date_ok($p['start_date'] ?? '') ? $p['start_date'] : '';
    $b['end_date']   = $date_ok($p['end_date']   ?? '') ? $p['end_date']   : '';
    return $b;
}

// ────────────────────────────────────────────────────────────────────────────
// Register Plugin
// ────────────────────────────────────────────────────────────────────────────
register_plugin(
    GMB_ID,
    'Global Motion Banner',
    GMB_VERSION,
    'Fahad4x4',
    'https://github.com/fahad4x4',
    'Multi-type banners with targeting and scheduling — v' . GMB_VERSION,
    'settings',
    'gmb_admin_page'
);
add_action('theme-header',     'gmb_display_banners');
add_action('settings-sidebar', 'createSideMenu', array(GMB_ID, '📢 Motion Banner'));

// ────────────────────────────────────────────────────────────────────────────
// Admin Router
// ────────────────────────────────────────────────────────────────────────────
function gmb_admin_page() {
    $action = isset($_GET['gmb_action']) ? strip_tags((string)$_GET['gmb_action']) : 'list';
    $bid    = isset($_GET['gmb_bid'])    ? (int)$_GET['gmb_bid']                   : 0;
    $notice = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST['gmb_nonce']) || !gmb_verify_nonce($_POST['gmb_nonce'])) {
            $notice = '<div class="gmb-notice gmb-notice-error">Security check failed. Please refresh the page and try again.</div>';
            echo $notice; gmb_render_list(); return;
        }
        if (isset($_POST['gmb_save_banner'])) {
            $eid    = isset($_POST['gmb_bid']) && $_POST['gmb_bid'] !== '' ? (int)$_POST['gmb_bid'] : '';
            $banner = gmb_validate_post($_POST, $eid !== '' ? (string)$eid : '');
            $all    = gmb_load_all();
            if ($eid !== '') {
                $updated = false;
                foreach ($all as &$b) {
                    if ((string)$b['id'] === (string)$eid) { $b = $banner; $updated = true; break; }
                }
                unset($b);
                if (!$updated) $all[] = $banner;
            } else {
                $all[] = $banner;
            }
            gmb_save_all($all);
            $notice = '<div class="gmb-notice gmb-notice-success">Banner saved successfully.</div>';
            echo $notice; gmb_render_list(); return;
        }
        if (isset($_POST['gmb_delete'])) {
            gmb_delete_banner((int)$_POST['gmb_bid']);
            $notice = '<div class="gmb-notice gmb-notice-success">Banner deleted.</div>';
            echo $notice; gmb_render_list(); return;
        }
        if (isset($_POST['gmb_toggle'])) {
            $all = gmb_load_all();
            foreach ($all as &$b) {
                if ((string)$b['id'] === (string)$_POST['gmb_bid']) {
                    $b['enabled'] = ($b['enabled'] === '1') ? '0' : '1';
                    break;
                }
            }
            unset($b);
            gmb_save_all($all);
            gmb_render_list(); return;
        }
    }

    echo $notice;

    if ($action === 'edit' && $bid > 0) {
        $banner = gmb_get_banner($bid);
        gmb_render_edit_form($banner ?: gmb_banner_defaults());
    } elseif ($action === 'add') {
        gmb_render_edit_form(gmb_banner_defaults());
    } else {
        gmb_render_list();
    }
}

// ────────────────────────────────────────────────────────────────────────────
// Admin Styles — Flat, Natural Colors
// ────────────────────────────────────────────────────────────────────────────
function gmb_admin_styles() { ?>
<style>
/* ── Base ── */
.gmb-wrap { max-width: 900px; font-family: 'Segoe UI', Arial, sans-serif; color: #333; font-size: 14px; }
.gmb-wrap * { box-sizing: border-box; }

/* ── Page header ── */
.gmb-page-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
.gmb-page-head h2 { margin: 0; font-size: 17px; font-weight: 600; color: #2c3e50; }

/* ── Cards ── */
.gmb-card { background: #fff; border: 1px solid #dde1e7; border-radius: 6px; padding: 20px; margin-bottom: 16px; }
.gmb-card-title { font-size: 13px; font-weight: 600; color: #555; text-transform: uppercase;
    letter-spacing: .5px; margin: 0 0 16px; padding-bottom: 10px;
    border-bottom: 1px solid #eef0f3; display: flex; align-items: center; gap: 7px; }

/* ── Grid ── */
.gmb-g2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.gmb-g3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.gmb-g4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px; }

/* ── Fields ── */
.gmb-field { display: flex; flex-direction: column; gap: 5px; }
.gmb-field label { font-size: 12px; font-weight: 600; color: #666; }
.gmb-field input[type=text],
.gmb-field input[type=number],
.gmb-field input[type=date],
.gmb-field select,
.gmb-field textarea {
    width: 100%; padding: 7px 10px; border: 1px solid #d0d5dd;
    border-radius: 5px; font-size: 13px; color: #333;
    background: #fff; outline: none; transition: border .15s;
}
.gmb-field input:focus,
.gmb-field select:focus,
.gmb-field textarea:focus { border-color: #7aacca; }
.gmb-field textarea { resize: vertical; }
.gmb-field input[type=color] { width: 44px; height: 34px; padding: 2px; border: 1px solid #d0d5dd; border-radius: 5px; cursor: pointer; }
.gmb-field small { color: #999; font-size: 11px; line-height: 1.4; }

/* ── Input + button row ── */
.gmb-input-row { display: flex; gap: 6px; align-items: stretch; }
.gmb-input-row input { flex: 1; min-width: 0; }

/* ── Toggle ── */
.gmb-toggle { display: flex; align-items: center; gap: 9px; cursor: pointer; margin-top: 4px; }
.gmb-toggle input { display: none; }
.gmb-toggle-track {
    width: 40px; height: 22px; background: #ccc; border-radius: 11px;
    position: relative; transition: background .25s; flex-shrink: 0;
}
.gmb-toggle-track::after {
    content: ''; position: absolute; width: 16px; height: 16px;
    background: #fff; border-radius: 50%; top: 3px; left: 3px;
    transition: left .25s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.gmb-toggle input:checked + .gmb-toggle-track { background: #4a9d6f; }
.gmb-toggle input:checked + .gmb-toggle-track::after { left: 21px; }
.gmb-toggle-label { font-size: 13px; color: #555; }

/* ── Buttons ── */
.gmb-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    padding: 8px 16px; border: none; border-radius: 5px; font-size: 13px;
    font-weight: 600; cursor: pointer; text-decoration: none; line-height: 1;
    transition: background .15s, box-shadow .15s; white-space: nowrap;
}
.gmb-btn-primary  { background: #3d7ab5; color: #fff; }
.gmb-btn-primary:hover  { background: #2f6091; color: #fff; }
.gmb-btn-danger   { background: #c0392b; color: #fff; }
.gmb-btn-danger:hover   { background: #a93226; }
.gmb-btn-gray     { background: #e8eaed; color: #444; border: 1px solid #d0d5dd; }
.gmb-btn-gray:hover     { background: #d8dce2; }
.gmb-btn-success  { background: #4a9d6f; color: #fff; }
.gmb-btn-success:hover  { background: #3d8460; }
.gmb-btn-sm { padding: 5px 11px; font-size: 12px; }
.gmb-btn-block { width: 100%; padding: 12px; font-size: 15px; border-radius: 5px; }

/* ── Notices ── */
.gmb-notice { padding: 11px 16px; border-radius: 5px; margin-bottom: 14px; font-size: 13px; font-weight: 600; }
.gmb-notice-success { background: #eaf6ee; color: #276744; border: 1px solid #b7dfc6; }
.gmb-notice-error   { background: #fdf0ef; color: #8b2217; border: 1px solid #f2c0bc; }

/* ── Table ── */
.gmb-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.gmb-table th {
    background: #f4f6f9; color: #666; padding: 9px 14px; text-align: left;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
    border-bottom: 2px solid #dde1e7;
}
.gmb-table td { padding: 10px 14px; border-bottom: 1px solid #eef0f3; vertical-align: middle; }
.gmb-table tr:last-child td { border-bottom: none; }
.gmb-table tr:hover td { background: #fafbfc; }
.gmb-on  { color: #4a9d6f; font-weight: 700; }
.gmb-off { color: #c0392b; font-weight: 700; }

/* ── Type badges ── */
.gmb-badge {
    display: inline-block; padding: 2px 9px; border-radius: 3px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
}
.gmb-badge-marquee { background: #e3edf7; color: #2c6fad; }
.gmb-badge-static  { background: #e6f4ec; color: #276744; }
.gmb-badge-fade    { background: #fef3e2; color: #9a6000; }
.gmb-badge-slider  { background: #f0e8f9; color: #6b3fa0; }
.gmb-badge-sticky  { background: #fdecea; color: #8b2217; }

/* ── Emoji bar ── */
.gmb-emoji-bar { display: flex; flex-wrap: wrap; gap: 2px; background: #f7f8fa;
    border: 1px solid #dde1e7; border-radius: 5px; padding: 7px; margin-top: 8px; }
.gmb-emoji-bar span { font-size: 18px; cursor: pointer; padding: 3px 5px;
    border-radius: 4px; transition: background .1s; line-height: 1.3; }
.gmb-emoji-bar span:hover { background: #e3edf7; }

/* ── Type-conditional sections ── */
.gmb-ts { display: none; }
.gmb-ts.active { display: block; }

/* ── Preview ── */
.gmb-preview-box {
    border-radius: 5px; overflow: hidden; border: 1px solid #dde1e7;
    position: relative; min-height: 60px; background: #2c3e50;
}

/* ── Disabled overlay ── */
.gmb-disabled { opacity: .45; pointer-events: none; }

/* ── Divider ── */
.gmb-divider { border: none; border-top: 1px solid #eef0f3; margin: 16px 0; }
</style>
<?php }

// ────────────────────────────────────────────────────────────────────────────
// Admin: Banner List
// ────────────────────────────────────────────────────────────────────────────
function gmb_render_list() {
    $banners = gmb_load_all();
    $base    = 'load.php?id=' . GMB_ID;
    $type_labels = array(
        'marquee' => 'Marquee', 'static' => 'Static',
        'fade'    => 'Fade',    'slider' => 'Slider', 'sticky' => 'Sticky',
    );
    $target_labels = array(
        'all' => 'All Pages', 'homepage' => 'Homepage',
        'specific' => 'Specific', 'exclude' => 'Exclude',
    );
    gmb_admin_styles(); ?>

    <div class="gmb-wrap">
        <div class="gmb-page-head">
            <h2>📢 Global Motion Banner <span style="font-weight:400;color:#aaa;font-size:13px;">v<?php echo GMB_VERSION; ?></span></h2>
            <a href="<?php echo $base; ?>&gmb_action=add" class="gmb-btn gmb-btn-primary">+ New Banner</a>
        </div>

        <?php if (empty($banners)): ?>
        <div class="gmb-card" style="text-align:center;padding:48px 20px;color:#aaa;">
            <div style="font-size:44px;margin-bottom:10px;">📢</div>
            <p style="margin:0 0 18px;font-size:15px;">No banners yet.</p>
            <a href="<?php echo $base; ?>&gmb_action=add" class="gmb-btn gmb-btn-primary">+ Create Your First Banner</a>
        </div>
        <?php else: ?>
        <div class="gmb-card" style="padding:0;overflow:hidden;">
            <table class="gmb-table">
                <thead>
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Target</th>
                        <th>Schedule</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($banners as $b): ?>
                <tr>
                    <td style="color:#bbb;font-size:12px;"><?php gmb_e($b['order']); ?></td>
                    <td>
                        <strong style="color:#2c3e50;"><?php gmb_e($b['title']); ?></strong>
                        <div style="font-size:11px;color:#bbb;margin-top:2px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?php gmb_e(mb_strimwidth($b['text'], 0, 55, '…')); ?>
                        </div>
                    </td>
                    <td>
                        <span class="gmb-badge gmb-badge-<?php gmb_e($b['type']); ?>">
                            <?php echo $type_labels[$b['type']] ?? $b['type']; ?>
                        </span>
                    </td>
                    <td>
                        <form method="post" action="<?php echo $base; ?>" style="margin:0;">
                            <input type="hidden" name="gmb_nonce" value="<?php echo gmb_create_nonce(); ?>">
                            <input type="hidden" name="gmb_bid"   value="<?php gmb_e($b['id']); ?>">
                            <button type="submit" name="gmb_toggle"
                                style="background:none;border:none;cursor:pointer;padding:0;font-size:13px;font-weight:700;">
                                <span class="<?php echo $b['enabled']==='1' ? 'gmb-on' : 'gmb-off'; ?>">
                                    <?php echo $b['enabled']==='1' ? '● On' : '○ Off'; ?>
                                </span>
                            </button>
                        </form>
                    </td>
                    <td style="font-size:12px;color:#888;">
                        <?php echo $target_labels[$b['target']] ?? $b['target']; ?>
                        <?php if ($b['target']==='specific' && $b['target_pages']): ?>
                            <div style="color:#bbb;font-size:11px;"><?php gmb_e(mb_strimwidth($b['target_pages'],0,24,'…')); ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:11px;color:#aaa;white-space:nowrap;">
                        <?php if ($b['start_date'] || $b['end_date']): ?>
                            <?php if ($b['start_date']) echo '▶ ' . gmb_attr($b['start_date']); ?>
                            <?php if ($b['start_date'] && $b['end_date']) echo ' – '; ?>
                            <?php if ($b['end_date'])   echo gmb_attr($b['end_date']); ?>
                        <?php else: ?>
                            <span style="color:#ddd;">Always</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap;">
                        <a href="<?php echo $base; ?>&gmb_action=edit&gmb_bid=<?php gmb_e($b['id']); ?>"
                           class="gmb-btn gmb-btn-gray gmb-btn-sm">Edit</a>
                        &nbsp;
                        <form method="post" action="<?php echo $base; ?>" style="display:inline;margin:0;"
                              onsubmit="return confirm('Delete banner \'<?php gmb_e($b['title']); ?>\'?')">
                            <input type="hidden" name="gmb_nonce" value="<?php echo gmb_create_nonce(); ?>">
                            <input type="hidden" name="gmb_bid"   value="<?php gmb_e($b['id']); ?>">
                            <button type="submit" name="gmb_delete" class="gmb-btn gmb-btn-danger gmb-btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ────────────────────────────────────────────────────────────────────────────
// Admin: Edit / Add Form
// ────────────────────────────────────────────────────────────────────────────
function gmb_render_edit_form(array $b) {
    $base    = 'load.php?id=' . GMB_ID;
    $is_edit = $b['id'] !== '';
    $slider_display = implode("\n", explode('||', $b['slider_messages']));
    $fade_display   = implode("\n", explode('||', $b['fade_messages']));
    gmb_admin_styles(); ?>

    <div class="gmb-wrap">

        <!-- Page header -->
        <div class="gmb-page-head">
            <div style="display:flex;align-items:center;gap:12px;">
                <a href="<?php echo $base; ?>" class="gmb-btn gmb-btn-gray gmb-btn-sm">← Back</a>
                <h2><?php echo $is_edit ? 'Edit Banner' : 'New Banner'; ?></h2>
            </div>
        </div>

        <!-- Live Preview -->
        <div class="gmb-card">
            <div class="gmb-card-title">👁 Live Preview</div>
            <div class="gmb-preview-box" id="gmb-preview" style="height:60px;">
                <div id="gmb-prev-marquee" style="white-space:nowrap;position:absolute;line-height:60px;animation:gmb-scroll-prev 20s linear infinite;">
                    <span id="gmb-prev-text" style="font-size:18px;font-weight:bold;color:#fff;font-family:sans-serif;vertical-align:middle;">
                        <?php gmb_e($b['text'] ?: 'Welcome! 🌟'); ?>
                    </span>
                </div>
                <div id="gmb-prev-center" style="display:none;width:100%;height:100%;position:absolute;top:0;left:0;align-items:center;justify-content:center;">
                    <span id="gmb-prev-center-text" style="color:#fff;font-family:sans-serif;font-size:18px;font-weight:bold;"></span>
                </div>
            </div>
            <style>@keyframes gmb-scroll-prev{0%{transform:translateX(100vw)}100%{transform:translateX(-100%)}}</style>
        </div>

        <form method="post" action="<?php echo $base; ?>">
            <input type="hidden" name="gmb_nonce" value="<?php echo gmb_create_nonce(); ?>">
            <?php if ($is_edit): ?>
            <input type="hidden" name="gmb_bid" value="<?php gmb_e($b['id']); ?>">
            <?php endif; ?>

            <!-- ── General ── -->
            <div class="gmb-card">
                <div class="gmb-card-title">⚙ General</div>
                <div class="gmb-g3">
                    <div class="gmb-field" style="grid-column:span 2;">
                        <label>Internal Title</label>
                        <input type="text" name="title" value="<?php gmb_e($b['title']); ?>" placeholder="e.g. Summer Sale Banner">
                    </div>
                    <div class="gmb-field">
                        <label>Display Order</label>
                        <input type="number" name="order" value="<?php gmb_e($b['order']); ?>" min="0" max="9999">
                        <small>Lower = shown first</small>
                    </div>
                </div>
                <hr class="gmb-divider">
                <div class="gmb-g2">
                    <div class="gmb-field">
                        <label>Banner Type</label>
                        <select name="type" id="gmb-type" onchange="gmbSwitchType(this.value)">
                            <option value="marquee" <?php echo $b['type']==='marquee'?'selected':''; ?>>Scrolling Marquee</option>
                            <option value="static"  <?php echo $b['type']==='static' ?'selected':''; ?>>Static Bar</option>
                            <option value="fade"    <?php echo $b['type']==='fade'   ?'selected':''; ?>>Fading Messages</option>
                            <option value="slider"  <?php echo $b['type']==='slider' ?'selected':''; ?>>Message Slider</option>
                            <option value="sticky"  <?php echo $b['type']==='sticky' ?'selected':''; ?>>Sticky Notification</option>
                        </select>
                    </div>
                    <div class="gmb-field">
                        <label>Status</label>
                        <label class="gmb-toggle">
                            <input type="checkbox" name="enabled" id="gmb-enabled" value="1" <?php echo $b['enabled']==='1'?'checked':''; ?>>
                            <span class="gmb-toggle-track"></span>
                            <span class="gmb-toggle-label" id="gmb-enabled-lbl"><?php echo $b['enabled']==='1'?'Enabled':'Disabled'; ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- ── Content: text ── -->
            <div class="gmb-ts <?php echo in_array($b['type'],array('marquee','static','sticky'))?'active':''; ?>" data-types="marquee static sticky">
                <div class="gmb-card">
                    <div class="gmb-card-title">📝 Announcement Text</div>
                    <div class="gmb-field">
                        <label>Text</label>
                        <textarea id="gmb-text" name="text" rows="3" oninput="gmbUpdatePreview()"><?php gmb_e($b['text']); ?></textarea>
                    </div>
                    <div class="gmb-emoji-bar">
                        <?php
                        $emojis = ['🌙','🕌','✨','🕋','🕯️','📿','🏮','🤲','🥘','🎈','🎁','🎉','🎊','🥳','💐',
                                   '📢','🔥','⚡','⭐','🌟','💥','🏷️','🛒','🛍️','💰','💎','📣','📞','📧','📱',
                                   '💬','📍','✉️','🌐','🔗','📌','✅','✔️','⚠️','🔔','🚀','🌍','✈️','⏳','🕒','🆕','💯','🔝'];
                        foreach ($emojis as $e) {
                            echo '<span onclick="gmbInsertEmoji(\'' . $e . '\')" title="' . $e . '">' . $e . '</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- ── Content: Slider ── -->
            <div class="gmb-ts <?php echo $b['type']==='slider'?'active':''; ?>" data-types="slider">
                <div class="gmb-card">
                    <div class="gmb-card-title">🎠 Slider Messages</div>
                    <div class="gmb-field">
                        <label>Messages (one per line)</label>
                        <textarea name="slider_messages" rows="6" placeholder="Welcome! 🌟&#10;Check our offers 🛍️&#10;Free shipping today ✈️"><?php gmb_e($slider_display); ?></textarea>
                        <small>Each line is a separate message shown in rotation.</small>
                    </div>
                    <hr class="gmb-divider">
                    <div class="gmb-g2">
                        <div class="gmb-field">
                            <label>Interval (ms)</label>
                            <input type="number" name="slider_interval" value="<?php gmb_e($b['slider_interval']); ?>" min="500" max="30000" step="500">
                            <small>4000 = 4 seconds per message</small>
                        </div>
                        <div class="gmb-field">
                            <label>Slide Direction</label>
                            <select name="dir" class="gmb-dir-field">
                                <option value="right" <?php echo $b['dir']==='right'?'selected':''; ?>>← Right to Left</option>
                                <option value="left"  <?php echo $b['dir']==='left' ?'selected':''; ?>>→ Left to Right</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Content: Fade ── -->
            <div class="gmb-ts <?php echo $b['type']==='fade'?'active':''; ?>" data-types="fade">
                <div class="gmb-card">
                    <div class="gmb-card-title">✨ Fading Messages</div>
                    <div class="gmb-field">
                        <label>Messages (one per line)</label>
                        <textarea name="fade_messages" rows="6" placeholder="Welcome! 🌟&#10;Great deals await you 💎"><?php gmb_e($fade_display); ?></textarea>
                        <small>Each message fades in, stays, then fades out.</small>
                    </div>
                    <hr class="gmb-divider">
                    <div class="gmb-field" style="max-width:240px;">
                        <label>Duration Per Message (ms)</label>
                        <input type="number" name="fade_duration" value="<?php gmb_e($b['fade_duration']); ?>" min="500" max="20000" step="500">
                        <small>3000 = 3 seconds per message</small>
                    </div>
                </div>
            </div>

            <!-- ── Colors & Background ── -->
            <div class="gmb-card">
                <div class="gmb-card-title">🎨 Colors & Background</div>
                <div class="gmb-g3">
                    <div class="gmb-field">
                        <label>Background Color</label>
                        <input type="color" name="bg_color" value="<?php gmb_e($b['bg_color']); ?>" onchange="gmbUpdatePreview()">
                    </div>
                    <div class="gmb-field">
                        <label>Text Color</label>
                        <input type="color" name="text_color" value="<?php gmb_e($b['text_color']); ?>" onchange="gmbUpdatePreview()">
                    </div>
                </div>
                <hr class="gmb-divider">
                <div class="gmb-g2">
                    <div class="gmb-field">
                        <label>Background Image URL</label>
                        <div class="gmb-input-row">
                            <input type="text" id="gmb-bg-img" name="bg_img"
                                   value="<?php gmb_e($b['bg_img']); ?>"
                                   placeholder="https://example.com/bg.jpg"
                                   oninput="gmbUpdatePreview()">
                            <button type="button" class="gmb-btn gmb-btn-gray gmb-btn-sm"
                                    onclick="gmbBrowse('gmb-bg-img')">📁 Browse</button>
                        </div>
                        <small>Optional — overrides background color</small>
                    </div>
                    <div class="gmb-field">
                        <label>Content Image URL</label>
                        <div class="gmb-input-row">
                            <input type="text" id="gmb-content-img" name="content_img"
                                   value="<?php gmb_e($b['content_img']); ?>"
                                   placeholder="https://example.com/logo.png">
                            <button type="button" class="gmb-btn gmb-btn-gray gmb-btn-sm"
                                    onclick="gmbBrowse('gmb-content-img')">📁 Browse</button>
                        </div>
                        <small>Optional — displayed alongside the text</small>
                    </div>
                </div>
            </div>

            <!-- ── Dimensions & Motion ── -->
            <div class="gmb-card">
                <div class="gmb-card-title">📐 Dimensions & Motion</div>
                <div class="gmb-g4">
                    <div class="gmb-field">
                        <label>Banner Height (px)</label>
                        <input type="number" name="banner_height" value="<?php gmb_e($b['banner_height']); ?>" min="20" max="300" oninput="gmbUpdatePreview()">
                    </div>
                    <div class="gmb-field">
                        <label>Font Size (px)</label>
                        <input type="number" name="font_size" value="<?php gmb_e($b['font_size']); ?>" min="10" max="100" oninput="gmbUpdatePreview()">
                    </div>
                    <div class="gmb-field">
                        <label>Image Height (px)</label>
                        <input type="number" name="img_height" value="<?php gmb_e($b['img_height']); ?>" min="10" max="200">
                    </div>
                    <div class="gmb-field">
                        <label>Font Weight</label>
                        <select name="font_weight">
                            <option value="normal" <?php echo $b['font_weight']==='normal'?'selected':''; ?>>Normal</option>
                            <option value="bold"   <?php echo $b['font_weight']==='bold'  ?'selected':''; ?>>Bold</option>
                            <option value="600"    <?php echo $b['font_weight']==='600'   ?'selected':''; ?>>Semi-Bold</option>
                        </select>
                    </div>
                </div>

                <!-- Marquee motion options -->
                <div class="gmb-ts <?php echo $b['type']==='marquee'?'active':''; ?>" data-types="marquee" id="gmb-marquee-opts">
                    <hr class="gmb-divider">
                    <div class="gmb-g3">
                        <div class="gmb-field">
                            <label>Scroll Direction</label>
                            <select name="dir" class="gmb-dir-field">
                                <option value="right" <?php echo $b['dir']==='right'?'selected':''; ?>>← Right to Left (Arabic)</option>
                                <option value="left"  <?php echo $b['dir']==='left' ?'selected':''; ?>>→ Left to Right (English)</option>
                            </select>
                        </div>
                        <div class="gmb-field">
                            <label>Scroll Speed (seconds)</label>
                            <input type="number" name="speed" value="<?php gmb_e($b['speed']); ?>" min="2" max="120" oninput="gmbUpdatePreview()">
                            <small>Lower = faster scroll</small>
                        </div>
                        <div class="gmb-field">
                            <label>Repeat Count</label>
                            <input type="number" name="repeat_count" value="<?php gmb_e($b['repeat_count']); ?>" min="1" max="10">
                            <small>Times text repeats in one pass</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Close Button ── -->
            <div class="gmb-card">
                <div class="gmb-card-title">✕ Close Button</div>
                <div class="gmb-g3">
                    <div class="gmb-field">
                        <label>Allow Visitor to Close</label>
                        <label class="gmb-toggle">
                            <input type="checkbox" name="closeable" id="gmb-closeable" value="1"
                                   <?php echo $b['closeable']==='1'?'checked':''; ?>
                                   onchange="gmbToggleCloseOpts()">
                            <span class="gmb-toggle-track"></span>
                            <span class="gmb-toggle-label" id="gmb-closeable-lbl"><?php echo $b['closeable']==='1'?'Enabled':'Disabled'; ?></span>
                        </label>
                    </div>
                    <div class="gmb-field" id="gmb-close-dur" <?php echo $b['closeable']!=='1'?'class="gmb-disabled"':''; ?>>
                        <label>Hide Duration After Close</label>
                        <select name="close_duration">
                            <option value="session" <?php echo $b['close_duration']==='session'?'selected':''; ?>>Until browser closed</option>
                            <option value="1day"    <?php echo $b['close_duration']==='1day'   ?'selected':''; ?>>1 Day</option>
                            <option value="7days"   <?php echo $b['close_duration']==='7days'  ?'selected':''; ?>>1 Week</option>
                            <option value="30days"  <?php echo $b['close_duration']==='30days' ?'selected':''; ?>>1 Month</option>
                            <option value="forever" <?php echo $b['close_duration']==='forever'?'selected':''; ?>>Forever</option>
                        </select>
                    </div>
                    <div class="gmb-field" id="gmb-close-sty" <?php echo $b['closeable']!=='1'?'class="gmb-disabled"':''; ?>>
                        <label>Button Style</label>
                        <select name="close_btn_style">
                            <option value="circle" <?php echo $b['close_btn_style']==='circle'?'selected':''; ?>>Circle</option>
                            <option value="square" <?php echo $b['close_btn_style']==='square'?'selected':''; ?>>Square</option>
                            <option value="text"   <?php echo $b['close_btn_style']==='text'  ?'selected':''; ?>>Text Only</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── Page Targeting ── -->
            <div class="gmb-card">
                <div class="gmb-card-title">🎯 Page Targeting</div>
                <div class="gmb-field" style="max-width:320px;">
                    <label>Show Banner On</label>
                    <select name="target" id="gmb-target" onchange="gmbToggleTargetFields()">
                        <option value="all"      <?php echo $b['target']==='all'     ?'selected':''; ?>>All Pages</option>
                        <option value="homepage" <?php echo $b['target']==='homepage'?'selected':''; ?>>Homepage Only</option>
                        <option value="specific" <?php echo $b['target']==='specific'?'selected':''; ?>>Specific Pages</option>
                        <option value="exclude"  <?php echo $b['target']==='exclude' ?'selected':''; ?>>All Except...</option>
                    </select>
                </div>
                <div id="gmb-target-extra" style="margin-top:14px;<?php echo !in_array($b['target'],array('specific','exclude'))?'display:none;':''; ?>">
                    <div class="gmb-g2">
                        <div class="gmb-field" id="gmb-specific-pages" style="<?php echo $b['target']!=='specific'?'display:none;':''; ?>">
                            <label>Show On (slugs, comma-separated)</label>
                            <input type="text" name="target_pages" value="<?php gmb_e($b['target_pages']); ?>" placeholder="about, contact, products">
                            <small>Page slug from URL — e.g. "about" for ?id=about</small>
                        </div>
                        <div class="gmb-field" id="gmb-exclude-pages" style="<?php echo $b['target']!=='exclude'?'display:none;':''; ?>">
                            <label>Exclude These Pages (slugs, comma-separated)</label>
                            <input type="text" name="exclude_pages" value="<?php gmb_e($b['exclude_pages']); ?>" placeholder="contact, privacy-policy">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Scheduling ── -->
            <div class="gmb-card">
                <div class="gmb-card-title">📅 Scheduling</div>
                <div class="gmb-g2">
                    <div class="gmb-field">
                        <label>Start Date (optional)</label>
                        <input type="date" name="start_date" value="<?php gmb_e($b['start_date']); ?>">
                        <small>Leave empty to show immediately</small>
                    </div>
                    <div class="gmb-field">
                        <label>End Date (optional)</label>
                        <input type="date" name="end_date" value="<?php gmb_e($b['end_date']); ?>">
                        <small>Leave empty for no expiry</small>
                    </div>
                </div>
            </div>

            <button type="submit" name="gmb_save_banner" class="gmb-btn gmb-btn-primary gmb-btn-block">Save Banner</button>
        </form>
    </div>

    <script>
    var gmbType = <?php echo json_encode($b['type']); ?>;

    // ── Browse file picker (uses GS built-in file browser) ──────────────────
    function gmbBrowse(fieldId) {
    window._gmbTarget = fieldId;

    
    var funcNum;
    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.tools && CKEDITOR.tools.addFunction) {
        funcNum = CKEDITOR.tools.addFunction(function(url) {
            var el = document.getElementById(window._gmbTarget);
            if (el) {
                el.value = url;
                el.dispatchEvent(new Event('input')); // تحديث المعاينة
                gmbUpdatePreview();
            }
        });
    } else {
        window.CKEDITOR = window.CKEDITOR || {};
        CKEDITOR.tools = CKEDITOR.tools || { _funcs: {}, _cnt: 0 };
        funcNum = ++CKEDITOR.tools._cnt;
        CKEDITOR.tools._funcs[funcNum] = function(url) {
            var el = document.getElementById(window._gmbTarget);
            if (el) {
                el.value = url;
                el.dispatchEvent(new Event('input'));
                gmbUpdatePreview();
            }
        };
        CKEDITOR.tools.callFunction = function(n, url) {
            if (CKEDITOR.tools._funcs[n]) CKEDITOR.tools._funcs[n](url);
        };
    }

    window.open(
        'filebrowser.php?type=images&CKEditorFuncNum=' + funcNum,
        'gmbFileBrowser',
        'width=730,height=500,scrollbars=yes,resizable=yes'
    );
}

    // ── Switch type ──────────────────────────────────────────────────────────
    function gmbSwitchType(type) {
        gmbType = type;
        document.querySelectorAll('.gmb-ts').forEach(function(el) {
            var types = el.getAttribute('data-types').split(' ');
            el.classList.toggle('active', types.indexOf(type) !== -1);
        });
        gmbUpdatePreview();
    }

    // ── Live preview ─────────────────────────────────────────────────────────
    function gmbUpdatePreview() {
        var bgColor  = document.querySelector('[name=bg_color]').value;
        var txtColor = document.querySelector('[name=text_color]').value;
        var height   = parseInt(document.querySelector('[name=banner_height]').value) || 60;
        var fontSize = parseInt(document.querySelector('[name=font_size]').value) || 18;
        var bgImgEl  = document.getElementById('gmb-bg-img');
        var bgImg    = bgImgEl ? bgImgEl.value.trim() : '';

        var preview   = document.getElementById('gmb-preview');
        var marqueeEl = document.getElementById('gmb-prev-marquee');
        var centerEl  = document.getElementById('gmb-prev-center');
        var textEl    = document.getElementById('gmb-prev-text');
        var cTextEl   = document.getElementById('gmb-prev-center-text');

        preview.style.background = bgImg ? "url('" + bgImg + "') center/cover" : bgColor;
        preview.style.height     = height + 'px';

        if (gmbType === 'marquee') {
            marqueeEl.style.display = '';
            centerEl.style.display  = 'none';
            var ti = document.getElementById('gmb-text');
            textEl.textContent    = ti ? ti.value : '';
            textEl.style.fontSize = fontSize + 'px';
            textEl.style.color    = txtColor;
            marqueeEl.style.lineHeight = height + 'px';
            var speedEl = document.querySelector('[name=speed]');
            var dirEl   = document.querySelector('#gmb-marquee-opts [name=dir]') || document.querySelector('.gmb-dir-field');
            var speed   = speedEl ? speedEl.value : 20;
            var dir     = dirEl   ? dirEl.value   : 'right';
            var start   = dir === 'right' ? '100vw' : '-100%';
            var end     = dir === 'right' ? '-100%' : '100vw';
            var s = document.getElementById('gmb-prev-style');
            if (!s) { s = document.createElement('style'); s.id = 'gmb-prev-style'; document.head.appendChild(s); }
            s.textContent = '@keyframes gmb-scroll-prev{0%{transform:translateX('+start+')}100%{transform:translateX('+end+')}}';
            marqueeEl.style.animation = 'gmb-scroll-prev ' + speed + 's linear infinite';
        } else {
            marqueeEl.style.display = 'none';
            centerEl.style.display  = 'flex';
            var ti = document.getElementById('gmb-text');
            var lbl = { static:'Static Bar', fade:'Fading Messages', slider:'Slider', sticky:'Sticky Notification' };
            cTextEl.textContent    = ti ? ti.value : (lbl[gmbType] || '');
            cTextEl.style.fontSize = fontSize + 'px';
            cTextEl.style.color    = txtColor;
        }
    }

    // ── Toggle label helpers ─────────────────────────────────────────────────
    document.getElementById('gmb-enabled').addEventListener('change', function() {
        document.getElementById('gmb-enabled-lbl').textContent = this.checked ? 'Enabled' : 'Disabled';
    });
    document.getElementById('gmb-closeable').addEventListener('change', function() {
        document.getElementById('gmb-closeable-lbl').textContent = this.checked ? 'Enabled' : 'Disabled';
    });

    function gmbToggleCloseOpts() {
        var on = document.getElementById('gmb-closeable').checked;
        ['gmb-close-dur','gmb-close-sty'].forEach(function(id) {
            var el = document.getElementById(id);
            if (on) el.classList.remove('gmb-disabled');
            else    el.classList.add('gmb-disabled');
        });
    }

    function gmbToggleTargetFields() {
        var val   = document.getElementById('gmb-target').value;
        var extra = document.getElementById('gmb-target-extra');
        var sp    = document.getElementById('gmb-specific-pages');
        var ex    = document.getElementById('gmb-exclude-pages');
        extra.style.display = (val === 'specific' || val === 'exclude') ? '' : 'none';
        sp.style.display    = val === 'specific' ? '' : 'none';
        ex.style.display    = val === 'exclude'  ? '' : 'none';
    }

    function gmbInsertEmoji(emoji) {
        var ta = document.getElementById('gmb-text');
        if (!ta) return;
        var s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.slice(0, s) + emoji + ta.value.slice(e);
        ta.selectionStart = ta.selectionEnd = s + emoji.length;
        ta.focus();
        gmbUpdatePreview();
    }

    // Init
    gmbSwitchType(gmbType);
    gmbUpdatePreview();
    </script>
    <?php
}

// ────────────────────────────────────────────────────────────────────────────
// Frontend: Get Current Page Slug
// ────────────────────────────────────────────────────────────────────────────
function gmb_current_slug() {
    if (!empty($_GET['id'])) {
        return strtolower(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['id']));
    }
    return '';
}

// ────────────────────────────────────────────────────────────────────────────
// Frontend: Page Targeting Check
// ────────────────────────────────────────────────────────────────────────────
function gmb_passes_target(array $b) {
    $slug    = gmb_current_slug();
    $is_home = ($slug === '');
    switch ($b['target']) {
        case 'all':      return true;
        case 'homepage': return $is_home;
        case 'specific':
            if (empty($b['target_pages'])) return false;
            $pages = array_map('trim', explode(',', strtolower($b['target_pages'])));
            return in_array($slug, $pages, true)
                || ($is_home && (in_array('', $pages, true) || in_array('index', $pages, true)));
        case 'exclude':
            if (empty($b['exclude_pages'])) return true;
            $pages = array_map('trim', explode(',', strtolower($b['exclude_pages'])));
            return !in_array($slug, $pages, true);
        default: return true;
    }
}

// ────────────────────────────────────────────────────────────────────────────
// Frontend: Schedule Check
// ────────────────────────────────────────────────────────────────────────────
function gmb_passes_schedule(array $b) {
    $today = date('Y-m-d');
    if (!empty($b['start_date']) && $today < $b['start_date']) return false;
    if (!empty($b['end_date'])   && $today > $b['end_date'])   return false;
    return true;
}

// ────────────────────────────────────────────────────────────────────────────
// Frontend: Display All Active Banners
// ────────────────────────────────────────────────────────────────────────────
function gmb_display_banners() {
    if (!function_exists('is_frontend') || !is_frontend()) return;
    $banners = gmb_load_all();
    foreach ($banners as $b) {
        if ($b['enabled'] !== '1')    continue;
        if (!gmb_passes_target($b))   continue;
        if (!gmb_passes_schedule($b)) continue;
        gmb_render_banner($b);
    }
}

// ────────────────────────────────────────────────────────────────────────────
// Frontend: Render One Banner
// ────────────────────────────────────────────────────────────────────────────
function gmb_render_banner(array $b) {
    $id          = (int)$b['id'];
    $type        = $b['type'];
    $h           = (int)$b['banner_height'];
    $font_size   = (int)$b['font_size'];
    $img_h       = (int)$b['img_height'];
    $speed       = (int)$b['speed'];
    $fw          = gmb_attr($b['font_weight']);
    $tc          = gmb_attr($b['text_color']);
    $repeat      = max(1, min(10, (int)$b['repeat_count']));
    $closeable   = ($b['closeable'] === '1');
    $close_style = $b['close_btn_style'];
    $close_dur   = $b['close_duration'];
    $text        = gmb_out($b['text']);

    $bg_img   = filter_var($b['bg_img'], FILTER_VALIDATE_URL) ? $b['bg_img'] : '';
    $bg_style = $bg_img
        ? "background:url('" . gmb_attr($bg_img) . "') center/cover no-repeat;"
        : "background-color:" . gmb_attr($b['bg_color']) . ";";

    $content_img = filter_var($b['content_img'], FILTER_VALIDATE_URL) ? $b['content_img'] : '';
    $dir         = ($b['dir'] === 'left') ? 'left' : 'right';
    list($from, $to) = ($dir === 'right') ? array('100vw', '-100%') : array('-100%', '100vw');

    $dismiss_key = 'gmb_' . md5($b['text'] . '|' . $b['bg_color'] . '|' . $b['content_img'] . '|' . GMB_VERSION . '|' . $id);

    $fade_msgs   = array_values(array_filter(array_map('trim', explode('||', $b['fade_messages']))));
    $slider_msgs = array_values(array_filter(array_map('trim', explode('||', $b['slider_messages']))));
    if (empty($fade_msgs))   $fade_msgs   = array($b['text']);
    if (empty($slider_msgs)) $slider_msgs = array($b['text']);

    $wid = "gmb-w{$id}";
    ?>
<style>
.<?php echo $wid; ?> { width:100%; overflow:hidden; transition:height .35s ease, opacity .35s ease; }
.<?php echo $wid; ?>.gmb-hidden { height:0 !important; opacity:0; pointer-events:none; }
.<?php echo $wid; ?>.gmb-hidden .gmb-bc { display:none !important; }
.<?php echo $wid; ?> .gmb-bc {
    overflow:hidden; position:relative; width:100%;
    z-index:9999; display:flex; align-items:center;
    height:<?php echo $h; ?>px;
    <?php echo $bg_style; ?>
    color:<?php echo $tc; ?>;
}
.<?php echo $wid; ?> .gmb-font {
    font-size:<?php echo $font_size; ?>px;
    font-weight:<?php echo $fw; ?>;
    font-family:'Segoe UI',Tahoma,Arial,sans-serif;
    line-height:1.2;
}
<?php if ($type === 'marquee'): ?>
.<?php echo $wid; ?> .gmb-track {
    display:flex; align-items:center; white-space:nowrap;
    position:absolute; top:0; left:0; height:100%;
    animation:<?php echo "gmb_anim_{$id}"; ?> <?php echo $speed; ?>s linear infinite;
    will-change:transform;
}
.<?php echo $wid; ?> .gmb-bc:hover .gmb-track { animation-play-state:paused; }
.<?php echo $wid; ?> .gmb-item { display:inline-flex; align-items:center; padding:0 30px; }
.<?php echo $wid; ?> .gmb-item img { height:<?php echo $img_h; ?>px; margin:0 12px; vertical-align:middle; }
@keyframes <?php echo "gmb_anim_{$id}"; ?> {
    0%   { transform:translateX(<?php echo $from; ?>); }
    100% { transform:translateX(<?php echo $to; ?>); }
}
<?php elseif ($type === 'static' || $type === 'sticky'): ?>
.<?php echo $wid; ?> .gmb-bc { justify-content:center; }
.<?php echo $wid; ?> .gmb-center { display:flex; align-items:center; gap:12px; }
.<?php echo $wid; ?> .gmb-center img { height:<?php echo $img_h; ?>px; vertical-align:middle; }
<?php if ($type === 'sticky'): ?>
.<?php echo $wid; ?> .gmb-bc { position:fixed !important; top:0; left:0; right:0; z-index:99999; }
<?php endif; ?>
<?php elseif ($type === 'fade'): ?>
.<?php echo $wid; ?> .gmb-bc { justify-content:center; }
.<?php echo $wid; ?> .gmb-fade-msg {
    position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%);
    opacity:0; transition:opacity .7s ease;
    white-space:nowrap; text-align:center;
}
.<?php echo $wid; ?> .gmb-fade-msg.active { opacity:1; }
<?php elseif ($type === 'slider'): ?>
.<?php echo $wid; ?> .gmb-bc { justify-content:center; }
.<?php echo $wid; ?> .gmb-slide-msg {
    position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%);
    opacity:0; transition:transform .5s ease, opacity .5s ease;
    white-space:nowrap; text-align:center;
}
.<?php echo $wid; ?> .gmb-slide-msg.active { opacity:1; transform:translate(-50%,-50%); }
.<?php echo $wid; ?> .gmb-slide-msg.exit  { opacity:0; transform:translate(calc(-50% <?php echo $dir==='right'?'- 80px':'+ 80px'; ?>),-50%); }
.<?php echo $wid; ?> .gmb-slide-msg.enter { opacity:0; transform:translate(calc(-50% <?php echo $dir==='right'?'+ 80px':'- 80px'; ?>),-50%); }
<?php endif; ?>
<?php if ($closeable): ?>
.<?php echo $wid; ?> .gmb-close {
    position:<?php echo $type==='sticky'?'fixed':'absolute'; ?>;
    top:50%; right:10px; transform:translateY(-50%);
    z-index:100000; cursor:pointer;
    background:rgba(0,0,0,.3); border:none; color:#fff;
    line-height:1; transition:background .15s, transform .15s;
    display:flex; align-items:center; justify-content:center;
}
.<?php echo $wid; ?> .gmb-close:hover { background:rgba(0,0,0,.6); transform:translateY(-50%) scale(1.1); }
<?php if ($close_style === 'circle'): ?>
.<?php echo $wid; ?> .gmb-close { width:24px; height:24px; border-radius:50%; font-size:13px; }
<?php elseif ($close_style === 'square'): ?>
.<?php echo $wid; ?> .gmb-close { width:24px; height:24px; border-radius:3px; font-size:13px; }
<?php else: ?>
.<?php echo $wid; ?> .gmb-close { padding:4px 10px; border-radius:3px; font-size:12px; font-weight:600; }
<?php endif; ?>
<?php endif; ?>
</style>

<div class="<?php echo $wid; ?>" id="gmb-wrapper-<?php echo $id; ?>" style="height:<?php echo $h; ?>px;">
    <div class="gmb-bc" role="<?php echo $type==='marquee'?'marquee':'banner'; ?>" aria-live="off">

        <?php if ($type === 'marquee'): ?>
        <div class="gmb-track">
            <?php for ($i = 0; $i < $repeat; $i++): ?>
            <span class="gmb-item">
                <?php if ($content_img): ?><img src="<?php gmb_e($content_img); ?>" alt=""><?php endif; ?>
                <span class="gmb-font"><?php echo $text; ?></span>
            </span>
            <?php endfor; ?>
        </div>

        <?php elseif ($type === 'static' || $type === 'sticky'): ?>
        <div class="gmb-center">
            <?php if ($content_img): ?><img src="<?php gmb_e($content_img); ?>" alt=""><?php endif; ?>
            <span class="gmb-font"><?php echo $text; ?></span>
        </div>

        <?php elseif ($type === 'fade'): ?>
        <?php foreach ($fade_msgs as $fi => $msg): ?>
        <div class="gmb-fade-msg gmb-font<?php echo $fi===0?' active':''; ?>" data-i="<?php echo $fi; ?>">
            <?php gmb_e($msg); ?>
        </div>
        <?php endforeach; ?>

        <?php elseif ($type === 'slider'): ?>
        <?php foreach ($slider_msgs as $si => $msg): ?>
        <div class="gmb-slide-msg gmb-font<?php echo $si===0?' active':''; ?>" data-i="<?php echo $si; ?>">
            <?php gmb_e($msg); ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($closeable): ?>
        <button class="gmb-close" onclick="gmbDismiss<?php echo $id; ?>()" title="Close" aria-label="Close banner">
            <?php echo ($close_style === 'text') ? 'Close ✕' : '✕'; ?>
        </button>
        <?php endif; ?>

    </div>
</div>

<script>
(function(){
    var ID       = <?php echo $id; ?>;
    var TYPE     = <?php echo json_encode($type); ?>;
    var KEY      = <?php echo json_encode($dismiss_key); ?>;
    var DURATION = <?php echo json_encode($close_dur); ?>;
    var wrapper  = document.getElementById('gmb-wrapper-<?php echo $id; ?>');
    if (!wrapper) return;

    function isHidden() {
        try {
            if (DURATION === 'session') return !!sessionStorage.getItem(KEY);
            var raw = localStorage.getItem(KEY);
            if (!raw) return false;
            var d = JSON.parse(raw);
            if (d.duration === 'forever') return true;
            return d.until && Date.now() < d.until;
        } catch(e) { return false; }
    }

    window['gmbDismiss' + ID] = function() {
        var until = null;
        if (DURATION === '1day')   until = Date.now() + 86400000;
        if (DURATION === '7days')  until = Date.now() + 604800000;
        if (DURATION === '30days') until = Date.now() + 2592000000;
        try {
            if (DURATION === 'session') {
                sessionStorage.setItem(KEY, '1');
            } else {
                localStorage.setItem(KEY, JSON.stringify({ duration: DURATION, until: until }));
            }
        } catch(e) {}
        wrapper.style.height  = wrapper.offsetHeight + 'px';
        wrapper.style.opacity = '1';
        wrapper.offsetHeight;
        wrapper.classList.add('gmb-hidden');
    };

    if (isHidden()) {
        wrapper.style.transition = 'none';
        wrapper.classList.add('gmb-hidden');
    }

    <?php if ($type === 'fade' && count($fade_msgs) > 1): ?>
    var fadeMsgs = wrapper.querySelectorAll('.gmb-fade-msg');
    var fadeIdx  = 0;
    setInterval(function() {
        fadeMsgs[fadeIdx].classList.remove('active');
        fadeIdx = (fadeIdx + 1) % fadeMsgs.length;
        fadeMsgs[fadeIdx].classList.add('active');
    }, <?php echo (int)$b['fade_duration']; ?>);
    <?php endif; ?>

    <?php if ($type === 'slider' && count($slider_msgs) > 1): ?>
    var slideMsgs = wrapper.querySelectorAll('.gmb-slide-msg');
    var slideIdx  = 0;
    setInterval(function() {
        var cur  = slideMsgs[slideIdx];
        slideIdx = (slideIdx + 1) % slideMsgs.length;
        var next = slideMsgs[slideIdx];
        next.classList.add('enter');
        setTimeout(function() {
            cur.classList.remove('active');
            cur.classList.add('exit');
            next.classList.remove('enter');
            next.classList.add('active');
            setTimeout(function() { cur.classList.remove('exit'); }, 600);
        }, 30);
    }, <?php echo (int)$b['slider_interval']; ?>);
    <?php endif; ?>

})();
</script>
    <?php
}
