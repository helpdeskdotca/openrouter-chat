<?php
/*
Plugin Name: OpenRouter AI Chat
Description: Enterprise AI Chatbot with Sessions, History Toggle, and New Chat.
Version: 7.1
Author: ITLogics.com/Helpdesk.ca
*/

if (!defined('ABSPATH')) exit;

// ==========================================
// 1. DATABASE SETUP
// ==========================================
register_activation_hook(__FILE__, 'orc_create_tables');

function orc_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_logs = $wpdb->prefix . 'orc_chat_logs';
    // Added session_id column
    $sql_logs = "CREATE TABLE $table_logs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        session_id varchar(50) DEFAULT '' NOT NULL, 
        user_ip varchar(100) NOT NULL,
        page_url varchar(255) NOT NULL,
        role varchar(20) NOT NULL,
        message text NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $table_leads = $wpdb->prefix . 'orc_leads';
    $sql_leads = "CREATE TABLE $table_leads (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_logs);
    dbDelta($sql_leads);
}

// ==========================================
// 2. ADMIN SETTINGS
// ==========================================
function orc_add_admin_menu() {
    add_options_page('OpenRouter Chat', 'OpenRouter Chat', 'manage_options', 'openrouter-chat', 'orc_settings_page');
}
add_action('admin_menu', 'orc_add_admin_menu');

function orc_settings_init() {
    // API
    register_setting('orc_settings_group', 'orc_api_key');
    register_setting('orc_settings_group', 'orc_model_id');
    register_setting('orc_settings_group', 'orc_system_prompt');
    register_setting('orc_settings_group', 'orc_knowledge_base'); 
    
    // Content
    register_setting('orc_settings_group', 'orc_chat_title');
    register_setting('orc_settings_group', 'orc_welcome_msg');
    register_setting('orc_settings_group', 'orc_bot_avatar');
    
    // Visuals
    register_setting('orc_settings_group', 'orc_bubble_size'); 
    register_setting('orc_settings_group', 'orc_position');
    register_setting('orc_settings_group', 'orc_dark_mode');
    
    // Color Studio
    register_setting('orc_settings_group', 'orc_accent_color');
    register_setting('orc_settings_group', 'orc_chat_bg_color');
    register_setting('orc_settings_group', 'orc_bot_bubble_color');
    register_setting('orc_settings_group', 'orc_bot_text_color');
    register_setting('orc_settings_group', 'orc_user_bubble_color');
    register_setting('orc_settings_group', 'orc_user_text_color');
    
    // Security & Data
    register_setting('orc_settings_group', 'orc_enable_debug');
    register_setting('orc_settings_group', 'orc_rate_limit'); 
    register_setting('orc_settings_group', 'orc_require_lead'); 
    register_setting('orc_settings_group', 'orc_enable_history'); // NEW: Toggle History
}
add_action('admin_init', 'orc_settings_init');

// --- HANDLE ACTIONS ---
add_action('admin_init', 'orc_handle_admin_actions');
function orc_handle_admin_actions() {
    if (!current_user_can('manage_options')) return;
    global $wpdb;

    // Leads
    if (isset($_POST['orc_export_csv'])) {
        check_admin_referer('orc_action_nonce');
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}orc_leads ORDER BY created_at DESC", ARRAY_A);
        orc_csv_download($rows, 'leads_export');
    }
    if (isset($_POST['orc_delete_all_leads'])) {
        check_admin_referer('orc_action_nonce');
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}orc_leads");
        wp_redirect(admin_url('options-general.php?page=openrouter-chat&tab=leads&msg=deleted')); exit;
    }
    if (isset($_GET['orc_delete_lead'])) {
        check_admin_referer('orc_del_nonce');
        $wpdb->delete($wpdb->prefix . 'orc_leads', array('id' => intval($_GET['orc_delete_lead'])));
        wp_redirect(admin_url('options-general.php?page=openrouter-chat&tab=leads')); exit;
    }

    // History
    if (isset($_POST['orc_export_history'])) {
        check_admin_referer('orc_action_nonce');
        $rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}orc_chat_logs ORDER BY timestamp DESC", ARRAY_A);
        orc_csv_download($rows, 'chat_history_export');
    }
    if (isset($_POST['orc_delete_all_history'])) {
        check_admin_referer('orc_action_nonce');
        $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}orc_chat_logs");
        wp_redirect(admin_url('options-general.php?page=openrouter-chat&tab=history&msg=deleted')); exit;
    }
    if (isset($_GET['orc_delete_history'])) {
        check_admin_referer('orc_del_nonce');
        $wpdb->delete($wpdb->prefix . 'orc_chat_logs', array('id' => intval($_GET['orc_delete_history'])));
        wp_redirect(admin_url('options-general.php?page=openrouter-chat&tab=history')); exit;
    }
}

function orc_csv_download($rows, $filename) {
    if(empty($rows)) return;
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array_keys($rows[0]));
    foreach ($rows as $row) fputcsv($output, $row);
    fclose($output);
    exit;
}

function orc_settings_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
    ?>
    <div class="wrap">
        <h1>OpenRouter Chat Suite</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=openrouter-chat&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="?page=openrouter-chat&tab=knowledge" class="nav-tab <?php echo $active_tab == 'knowledge' ? 'nav-tab-active' : ''; ?>">Knowledge Base</a>
            <a href="?page=openrouter-chat&tab=history" class="nav-tab <?php echo $active_tab == 'history' ? 'nav-tab-active' : ''; ?>">Chat History</a>
            <a href="?page=openrouter-chat&tab=leads" class="nav-tab <?php echo $active_tab == 'leads' ? 'nav-tab-active' : ''; ?>">Leads</a>
        </h2>

        <?php if ($active_tab == 'settings'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('orc_settings_group'); do_settings_sections('orc_settings_group'); ?>
                
                <div style="background: #fff; border-left: 4px solid #46b450; padding: 20px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin:0;">Need Support</h3>
                    <p>Contact us anytime at helpdesk.ca.</p>
                    <a href="https://www.helpdesk.ca/" target="_blank" class="button button-secondary">Support</a>
                </div>

                <table class="form-table">
                    <tr><th colspan="2"><h3>⚙️ API Configuration</h3></th></tr>
                    <tr><th>API Key</th><td><input type="password" name="orc_api_key" value="<?php echo esc_attr(get_option('orc_api_key')); ?>" class="regular-text"></td></tr>
                    <tr><th>Model ID</th><td><input type="text" name="orc_model_id" value="<?php echo esc_attr(get_option('orc_model_id', 'openai/gpt-3.5-turbo')); ?>" class="regular-text"></td></tr>
                    <tr><th>System Prompt</th><td><textarea name="orc_system_prompt" rows="3" class="large-text"><?php echo esc_textarea(get_option('orc_system_prompt', 'You are a helpful assistant.')); ?></textarea></td></tr>

                    <tr><th colspan="2"><h3>🎨 Color Studio</h3></th></tr>
                    <tr><th>Accent Color</th><td><input type="color" id="orc_accent" name="orc_accent_color" value="<?php echo esc_attr(get_option('orc_accent_color', '#0073aa')); ?>"></td></tr>
                    <tr><th>Window Background</th><td><input type="color" id="orc_bg" name="orc_chat_bg_color" value="<?php echo esc_attr(get_option('orc_chat_bg_color', '#ffffff')); ?>"></td></tr>
                    
                    <tr><th scope="row">Bot Message</th>
                        <td>
                            Background: <input type="color" id="orc_bot_bg" name="orc_bot_bubble_color" value="<?php echo esc_attr(get_option('orc_bot_bubble_color', '#e5e5ea')); ?>">
                            Text: <input type="color" id="orc_bot_txt" name="orc_bot_text_color" value="<?php echo esc_attr(get_option('orc_bot_text_color', '#333333')); ?>">
                        </td>
                    </tr>
                    <tr><th scope="row">User Message</th>
                        <td>
                            Background: <input type="color" id="orc_user_bg" name="orc_user_bubble_color" value="<?php echo esc_attr(get_option('orc_user_bubble_color', '#0073aa')); ?>">
                            Text: <input type="color" id="orc_user_txt" name="orc_user_text_color" value="<?php echo esc_attr(get_option('orc_user_text_color', '#ffffff')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button type="button" class="button" onclick="resetColors()">🔄 Reset Colors</button>
                            <script>
                            function resetColors() {
                                document.getElementById('orc_accent').value = '#0073aa';
                                document.getElementById('orc_bg').value = '#ffffff';
                                document.getElementById('orc_bot_bg').value = '#e5e5ea';
                                document.getElementById('orc_bot_txt').value = '#333333';
                                document.getElementById('orc_user_bg').value = '#0073aa';
                                document.getElementById('orc_user_txt').value = '#ffffff';
                            }
                            </script>
                        </td>
                    </tr>

                    <tr><th colspan="2"><h3>🖥️ Appearance</h3></th></tr>
                    <tr><th>Title</th><td><input type="text" name="orc_chat_title" value="<?php echo esc_attr(get_option('orc_chat_title', 'AI Assistant')); ?>" class="regular-text"></td></tr>
                    <tr><th>Welcome Message</th><td><textarea name="orc_welcome_msg" rows="2" class="large-text"><?php echo esc_textarea(get_option('orc_welcome_msg', 'Hello! How can I help?')); ?></textarea></td></tr>
                    <tr><th>Avatar URL</th><td><input type="url" name="orc_bot_avatar" value="<?php echo esc_attr(get_option('orc_bot_avatar')); ?>" class="regular-text"></td></tr>
                    <tr><th>Bubble Size (px)</th><td><input type="number" name="orc_bubble_size" value="<?php echo esc_attr(get_option('orc_bubble_size', '60')); ?>" class="small-text"></td></tr>
                    <tr><th>Position</th><td><select name="orc_position"><option value="right" <?php selected(get_option('orc_position'), 'right'); ?>>Right</option><option value="left" <?php selected(get_option('orc_position'), 'left'); ?>>Left</option></select></td></tr>
                    <tr><th>Dark Mode</th><td><input type="checkbox" name="orc_dark_mode" value="1" <?php checked(1, get_option('orc_dark_mode')); ?>> Enable</td></tr>
                    
                    <tr><th colspan="2"><h3>🛡️ Security & Data</h3></th></tr>
                    <tr><th>Rate Limit</th><td><input type="number" name="orc_rate_limit" value="<?php echo esc_attr(get_option('orc_rate_limit', '50')); ?>" class="small-text"> / hour</td></tr>
                    <tr><th>Lead Capture</th><td><input type="checkbox" name="orc_require_lead" value="1" <?php checked(1, get_option('orc_require_lead')); ?>> Require Name/Email</td></tr>
                    
                    <!-- NEW: HISTORY TOGGLE -->
                    <tr><th>Chat History</th><td><input type="checkbox" name="orc_enable_history" value="1" <?php checked(1, get_option('orc_enable_history', 1)); ?>> Enable History Capture (Save to DB)</td></tr>
                    
                    <tr><th>Debug Mode</th><td><input type="checkbox" name="orc_enable_debug" value="1" <?php checked(1, get_option('orc_enable_debug')); ?>> Log errors</td></tr>
                </table>
                <?php submit_button(); ?>
            </form>

        <?php elseif ($active_tab == 'knowledge'): ?>
            <form method="post" action="options.php">
                <?php settings_fields('orc_settings_group'); ?>
                <h3>🧠 Knowledge Base</h3>
                <textarea name="orc_knowledge_base" rows="20" class="large-text"><?php echo esc_textarea(get_option('orc_knowledge_base')); ?></textarea>
                <?php submit_button(); ?>
            </form>

        <?php elseif ($active_tab == 'history'): ?>
            <h3>📜 Chat History</h3>
            <form method="post" style="margin-bottom:15px;">
                <?php wp_nonce_field('orc_action_nonce'); ?>
                <input type="submit" name="orc_export_history" value="📥 Export CSV" class="button button-primary">
                <input type="submit" name="orc_delete_all_history" value="🗑️ Delete All" class="button button-link-delete" onclick="return confirm('Delete ALL history?');">
            </form>
            <?php 
                global $wpdb;
                $table_name = $wpdb->prefix . 'orc_chat_logs';
                // Check if session_id column exists (backward compatibility)
                $col_check = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'session_id'");
                $has_session = !empty($col_check);

                $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 200");
                echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Time</th><th>Session ID</th><th>Role</th><th>Page</th><th>Message</th><th>Action</th></tr></thead><tbody>';
                foreach($results as $row) {
                    $del_link = wp_nonce_url(admin_url('options-general.php?page=openrouter-chat&tab=history&orc_delete_history='.$row->id), 'orc_del_nonce');
                    $session_display = $has_session && isset($row->session_id) ? substr($row->session_id, 0, 8).'...' : 'N/A';
                    echo "<tr>
                        <td>{$row->timestamp}</td>
                        <td><code>{$session_display}</code></td>
                        <td><strong>{$row->role}</strong></td>
                        <td><a href='{$row->page_url}' target='_blank'>Link</a></td>
                        <td>".esc_html(substr($row->message, 0, 80))."...</td>
                        <td><a href='{$del_link}' style='color:red;' onclick=\"return confirm('Delete?');\">X</a></td>
                    </tr>";
                }
                echo '</tbody></table>';
            ?>

        <?php elseif ($active_tab == 'leads'): ?>
            <h3>👥 Leads</h3>
            <form method="post" style="margin-bottom:15px;">
                <?php wp_nonce_field('orc_action_nonce'); ?>
                <input type="submit" name="orc_export_csv" value="📥 Export CSV" class="button button-primary">
                <input type="submit" name="orc_delete_all_leads" value="🗑️ Delete All" class="button button-link-delete" onclick="return confirm('Delete ALL leads?');">
            </form>
            <?php 
                global $wpdb;
                $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}orc_leads ORDER BY created_at DESC");
                echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Date</th><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>';
                foreach($results as $row) {
                    $del_link = wp_nonce_url(admin_url('options-general.php?page=openrouter-chat&tab=leads&orc_delete_lead='.$row->id), 'orc_del_nonce');
                    echo "<tr><td>{$row->created_at}</td><td>".esc_html($row->name)."</td><td>".esc_html($row->email)."</td><td><a href='{$del_link}' style='color:red;' onclick=\"return confirm('Delete?');\">Delete</a></td></tr>";
                }
                echo '</tbody></table>';
            ?>
        <?php endif; ?>
    </div>
    <?php
}

function orc_enqueue_scripts() {
    wp_enqueue_style('orc-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('marked-js', 'https://cdn.jsdelivr.net/npm/marked/marked.min.js', array(), null, true);
    wp_enqueue_script('orc-script', plugin_dir_url(__FILE__) . 'chat.js', array('jquery', 'marked-js'), '7.1', true);

    wp_localize_script('orc-script', 'orc_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('orc_chat_nonce'),
        'require_lead' => get_option('orc_require_lead'),
        'page_title' => get_the_title(),
        'page_url'   => get_permalink()
    ));
}
add_action('wp_enqueue_scripts', 'orc_enqueue_scripts');

function orc_add_chat_to_footer() {
    $accent = get_option('orc_accent_color', '#0073aa');
    $bg = get_option('orc_chat_bg_color', '#ffffff');
    $bot_bg = get_option('orc_bot_bubble_color', '#e5e5ea');
    $bot_txt = get_option('orc_bot_text_color', '#333333');
    $user_bg = get_option('orc_user_bubble_color', '#0073aa');
    $user_txt = get_option('orc_user_text_color', '#ffffff');
    $pos = get_option('orc_position', 'right');
    $dark = get_option('orc_dark_mode') ? 'orc-dark' : 'orc-light';
    $title = get_option('orc_chat_title', 'AI Assistant');
    $welcome = get_option('orc_welcome_msg', 'Hello! How can I help?');
    $avatar = get_option('orc_bot_avatar');
    $size = get_option('orc_bubble_size', '60');
    if(empty($size)) $size = 60;

    echo "<style>:root { 
        --orc-accent: {$accent}; 
        --orc-bubble-size: {$size}px;
        --orc-bg: {$bg};
        --orc-bot-bg: {$bot_bg};
        --orc-bot-txt: {$bot_txt};
        --orc-user-bg: {$user_bg};
        --orc-user-txt: {$user_txt};
    }</style>";
    ?>
    <div id="orc-widget-container" class="orc-pos-<?php echo $pos; ?> <?php echo $dark; ?>">
        <div id="orc-chat-window" class="orc-hidden">
            <div id="orc-chat-header">
                <div class="orc-header-title">
                    <?php if($avatar) echo "<img src='$avatar' class='orc-avatar-img'>"; ?>
                    <span><?php echo esc_html($title); ?></span>
                </div>
                <!-- Header Actions -->
                <div class="orc-header-actions">
                    <button id="orc-reset-btn" title="New Chat">↺</button>
                    <button id="orc-close-btn" title="Close">×</button>
                </div>
            </div>
            
            <div id="orc-lead-form">
                <p>Please enter your details to start chatting.</p>
                <input type="text" id="orc-lead-name" placeholder="Your Name">
                <input type="email" id="orc-lead-email" placeholder="Your Email">
                <button id="orc-lead-submit">Start Chat</button>
            </div>

            <div id="orc-chat-messages">
                <div class="orc-message orc-bot-msg"><?php echo nl2br(esc_html($welcome)); ?></div>
            </div>
            
            <div id="orc-typing" class="orc-hidden">
                <div class="orc-dot"></div><div class="orc-dot"></div><div class="orc-dot"></div>
            </div>

            <div id="orc-input-area">
                <input type="text" id="orc-user-input" placeholder="Type a message...">
                <button id="orc-send-btn">Send</button>
            </div>
        </div>
        <button id="orc-chat-bubble">
            <svg viewBox="0 0 24 24" width="50%" height="50%" fill="white"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg>
        </button>
    </div>
    <?php
}
add_action('wp_footer', 'orc_add_chat_to_footer');

// --- CHAT LOGIC ---
add_action('wp_ajax_orc_save_lead', 'orc_save_lead');
add_action('wp_ajax_nopriv_orc_save_lead', 'orc_save_lead');
function orc_save_lead() {
    check_ajax_referer('orc_chat_nonce', 'nonce');
    global $wpdb;
    $wpdb->insert($wpdb->prefix.'orc_leads', array('name'=>sanitize_text_field($_POST['name']), 'email'=>sanitize_email($_POST['email'])));
    wp_send_json_success();
}

add_action('wp_ajax_orc_chat_request', 'orc_handle_chat_request');
add_action('wp_ajax_nopriv_orc_chat_request', 'orc_handle_chat_request');
function orc_handle_chat_request() {
    check_ajax_referer('orc_chat_nonce', 'nonce');
    global $wpdb;

    $ip = $_SERVER['REMOTE_ADDR'];
    $limit = (int)get_option('orc_rate_limit', 50);
    $trans = 'orc_limit_' . md5($ip);
    $count = (int)get_transient($trans);
    if ($count >= $limit) wp_send_json_error('Rate limit exceeded.');
    set_transient($trans, $count + 1, HOUR_IN_SECONDS);

    $api_key = get_option('orc_api_key');
    $model = get_option('orc_model_id', 'openai/gpt-3.5-turbo');
    $sys = get_option('orc_system_prompt', 'You are a helpful assistant.');
    $kb = get_option('orc_knowledge_base');
    $page_ctx = isset($_POST['page_context']) ? sanitize_text_field($_POST['page_context']) : '';
    $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : 'unknown';
    
    $full_sys = $sys . "\n\n";
    if ($kb) $full_sys .= "KNOWLEDGE BASE:\n$kb\n\n";
    if ($page_ctx) $full_sys .= "CONTEXT:\n$page_ctx\n\n";

    $msgs = isset($_POST['messages']) ? $_POST['messages'] : array();
    
    // Log User Message (Only if Enabled)
    $enable_history = get_option('orc_enable_history', 1);
    $last = end($msgs);
    if($last['role'] === 'user' && $enable_history) {
        $wpdb->insert($wpdb->prefix.'orc_chat_logs', array(
            'session_id' => $session_id,
            'user_ip' => $ip, 
            'page_url' => $_SERVER['HTTP_REFERER']??'', 
            'role' => 'user', 
            'message' => sanitize_textarea_field($last['content']), 
            'timestamp' => current_time('mysql')
        ));
    }

    $clean = array_map(function($m){ return array('role'=>sanitize_text_field($m['role']), 'content'=>sanitize_textarea_field($m['content']));}, $msgs);
    array_unshift($clean, ['role'=>'system', 'content'=>$full_sys]);

    $args = array('headers'=>array('Authorization'=>'Bearer '.$api_key, 'Content-Type'=>'application/json', 'HTTP-Referer'=>get_site_url(), 'X-Title'=>get_bloginfo('name')), 'body'=>json_encode(array('model'=>$model, 'messages'=>$clean)), 'timeout'=>45);
    $res = wp_remote_post('https://openrouter.ai/api/v1/chat/completions', $args);

    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) != 200) {
        $err_msg = is_wp_error($res) ? $res->get_error_message() : wp_remote_retrieve_body($res);
        if (false === get_transient('orc_admin_error_sent')) {
            wp_mail(get_option('admin_email'), '⚠️ AI Chatbot API Error', "The chatbot on ".get_site_url()." failed.\n\nError: $err_msg");
            set_transient('orc_admin_error_sent', true, HOUR_IN_SECONDS);
        }
        if (get_option('orc_enable_debug')) error_log('ORC Error: ' . $err_msg);
        wp_send_json_error('Chat not available right now please try again later.');
    }

    $data = json_decode(wp_remote_retrieve_body($res), true);
    if (isset($data['choices'][0]['message']['content'])) {
        $reply = $data['choices'][0]['message']['content'];
        // Log Bot Reply (Only if Enabled)
        if($enable_history) {
            $wpdb->insert($wpdb->prefix.'orc_chat_logs', array(
                'session_id' => $session_id,
                'user_ip' => $ip, 
                'page_url' => '', 
                'role' => 'bot', 
                'message' => $reply, 
                'timestamp' => current_time('mysql')
            ));
        }
        wp_send_json_success($reply);
    } else {
        wp_send_json_error('Chat not available right now please try again later.');
    }
}
// Auto-Purge Cache
add_action('updated_option', function($o){
    $opts = ['orc_position','orc_accent_color','orc_dark_mode','orc_bubble_size','orc_chat_bg_color'];
    if(in_array($o, $opts)){
        if(function_exists('rocket_clean_domain')) rocket_clean_domain();
        if(class_exists('autoptimizeCache')) autoptimizeCache::clearall();
    }
}, 10);