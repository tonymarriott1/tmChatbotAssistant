<?php
/**
 * Plugin Name: TM Chatbot Assistant
 * Plugin URI: https://tony-marriott.com/tm-chatbot-assistant
 * Description: A powerful AI chatbot for WordPress that integrates with OpenAI's Assistants API. Provides interactive and intelligent conversations for customer support, FAQs, and more.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Author: Tony Marriott
 * Author URI: https://tony-marriott.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tm-chatbot-assistant
 * Domain Path: /languages
 * Tags: chatbot, AI assistant, OpenAI, AI chatbot, customer support, GPT
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin paths
define('TMCAS_CHATBOT_ASSISTANT_DIR', plugin_dir_path(__FILE__));
define('TMCAS_CHATBOT_ASSISTANT_URL', plugin_dir_url(__FILE__));

// Include API handler
include_once TMCAS_CHATBOT_ASSISTANT_DIR . 'includes/chatbot-api.php';

// Enqueue frontend assets
function tmcas_chatbot_enqueue_scripts() {
     $plugin_version = '1.0.2'; 

    wp_enqueue_script(
        'tm-chatbot-script',
        TMCAS_CHATBOT_ASSISTANT_URL . 'assets/chatbot.js',
        ['jquery'],
        $plugin_version, // Add version here
        true
    );

    wp_enqueue_style(
        'tm-chatbot-style',
        TMCAS_CHATBOT_ASSISTANT_URL . 'assets/chatbot.css',
        [],
        $plugin_version // Add version here
    );

    // Pass dynamic data to JavaScript
    wp_localize_script('tm-chatbot-script', 'tmcas_chatbot_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'chatbot_nonce'       => wp_create_nonce('tmcas_chatbot_action'), //  Add this line
        'assistant_name' => get_option('tmcas_chatbot_assistant_name', esc_html__('AI Assistant', 'tm-chatbot-assistant')),
        'background_color' => get_option('tmcas_chatbot_background_color', '#0073aa'),
        'text_color' => get_option('tmcas_chatbot_text_color', '#ffffff'),
        'thread_expiry_minutes' => intval(get_option('tmcas_chatbot_thread_persistence', 30)),
    ]);
}
add_action('wp_enqueue_scripts', 'tmcas_chatbot_enqueue_scripts');


// Add chatbot HTML to footer
// Display chatbot container and load JS only on selected posts/pages
function tmcas_chatbot_display_chatbox() {
    if (!is_singular()) return;

    $allowed_ids = get_option('chatbot_display_ids', []);
    global $post;

    if (!in_array($post->ID, $allowed_ids)) return;

    $chatbox_title = get_option('tmcas_chatbot_title', __('Chat with AI', 'tm-chatbot-assistant'));
    $assistant_name = get_option('tmcas_chatbot_assistant_name', __('AI Assistant', 'tm-chatbot-assistant'));
    $chatbox_placeholder = get_option('tmcas_chatbot_placeholder', __('Type your message...', 'tm-chatbot-assistant'));
    $default_question = get_option('tmcas_chatbot_default_question', '');

    // Get the assistant avatar image
    $selected_avatar = get_option('tmcas_chatbot_avatar', 'female');
    $avatar_filename = ($selected_avatar === 'male') ? 'male-assistant-image.png' : 'default-assistant-image.png';
    $avatar_url = plugins_url('images/' . $avatar_filename, __FILE__);
    $image_url = plugins_url('images/writing.gif', __FILE__);

    echo '<div id="tm-chatbot-avatar-container">
        <img src="' . esc_url($avatar_url) . '" alt="' . esc_attr__('Assistant Avatar', 'tm-chatbot-assistant') . '" id="tm-chatbot-avatar">
    </div>

    <div id="tm-chatbot-container" class="hidden">
        <div id="tm-chatbot-header">
            <img src="' . esc_url($avatar_url) . '" alt="' . esc_attr__('Assistant Avatar', 'tm-chatbot-assistant') . '" id="tm-chatbot-header-avatar">
            <span id="tm-chatbot-name">' . esc_html($assistant_name) .'</span>

            <div class="tm-chatbot-buttons">
                <button id="tm-chatbot-new-conversation" title="' . esc_attr__('Start New Conversation', 'tm-chatbot-assistant') . '" class="tm-chatbot-header-button">↻</button>
                <button id="tm-chatbot-fullscreen" title="' . esc_attr__('Full Screen', 'tm-chatbot-assistant') . '">⛶</button>
                <button id="tm-chatbot-close" title="' . esc_attr__('Close', 'tm-chatbot-assistant') . '" class="tm-chatbot-header-button">X</button>
            </div>
        </div>

        <div id="tm-chatbot-title" style="color:#ffffff;font-size:1px;">' . esc_html($chatbox_title) . '</div>

        <div id="tm-chatbot-messages">
            <div class="chatbot-intro"></div>
        </div>

        <div id="tm-chatbot-loader" class="hidden">
            <img src="' . esc_url($image_url) . '" alt="' . esc_attr__('Chatbot is thinking...', 'tm-chatbot-assistant') . '" id="tm-chatbot-writing"/>
        </div>

        <div id="tm-chatbot-input-container">
            <input type="text" id="tm-chatbot-input" placeholder="' . esc_attr($chatbox_placeholder) . '">
           <button id="tm-chatbot-send">' . esc_html__('Send', 'tm-chatbot-assistant') . '</button>
        </div>';

    if (!empty($default_question)) {
        echo '<button id="tm-chatbot-default-question" class="tm-chatbot-default-button">' . esc_html($default_question) . '</button>';
    }

    echo '</div>'; // close tm-chatbot-container
    echo '<div id="chatbot-container"></div>';
}



// Hook to display the chatbot container in the site's footer
add_action('wp_footer', 'tmcas_chatbot_display_chatbox');

// Hook to add the admin menu
add_action('admin_menu', 'tmcas_chatbot_add_admin_menu');

function tmcas_chatbot_add_admin_menu() {
    // Add chatbot settings page
    add_options_page(
        __('Chatbot Settings', 'tm-chatbot-assistant'),   // Page title
        __('Chatbot Assistant', 'tm-chatbot-assistant'),  // Menu title
        'manage_options',
        'tm-chatbot-settings',
        'tmcas_chatbot_settings_page'
    );
}

// Add display settings submenu
add_action('admin_menu', 'tmcas_chatbot_add_display_settings_submenu');

function tmcas_chatbot_add_display_settings_submenu() {
    add_options_page(
        __('Chatbot Display Settings', 'tm-chatbot-assistant'),
        __('Chatbot Display', 'tm-chatbot-assistant'),
        'manage_options',
        'chatbot-display-settings',
        'tmcas_chatbot_display_settings_page'
    );
}

// Display settings page
function tmcas_chatbot_display_settings_page() {
    if (
    isset($_POST['chatbot_display_settings_nonce']) &&
    check_admin_referer('chatbot_display_settings_action', 'chatbot_display_settings_nonce')
) {
    if (isset($_POST['chatbot_display_ids']) && is_array($_POST['chatbot_display_ids'])) {
        $selected = array_map('intval', wp_unslash($_POST['chatbot_display_ids']));
        update_option('chatbot_display_ids', $selected);
        echo '<div class="updated"><p>' . esc_html__('Settings saved.', 'tm-chatbot-assistant') . '</p></div>';
    }
}


    $selected_ids = get_option('chatbot_display_ids', []);
    $all_posts = get_posts([
        'post_type' => ['post', 'page'],
        'numberposts' => -1,
        'orderby' => 'post_type',
        'order' => 'ASC'
    ]);

    echo '<div class="wrap"><h1>' . esc_html__('Chatbot Display Settings', 'tm-chatbot-assistant') . '</h1>';
    echo '<form method="post">';
wp_nonce_field('chatbot_display_settings_action', 'chatbot_display_settings_nonce');

	echo '<p><label><input type="checkbox" id="select-all"> ' . esc_html__('Select/Deselect All', 'tm-chatbot-assistant') . '</label></p>';

    echo '<ul style="max-height: 400px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px;">';

    foreach ($all_posts as $post) {
        $checked = in_array($post->ID, $selected_ids) ? 'checked' : '';
        echo '<li>';
        echo '<label>';
        $checked_attr = checked(in_array($post->ID, $selected_ids), true, false);
		echo '<input type="checkbox" class="chatbot-checkbox" name="chatbot_display_ids[]" value="' . esc_attr($post->ID) . '" ' . esc_attr($checked_attr) . '>';

        echo esc_html($post->post_title) . ' (' . esc_html($post->post_type) . ')';
        echo '</label>';
        echo '</li>';
    }

    echo '</ul>';
    echo '<p><input type="submit" class="button button-primary" value="' . esc_attr__('Save Changes', 'tm-chatbot-assistant') . '"></p>';
    echo '</form></div>';

    // Add select/deselect all JS
   echo '<script>';
echo 'document.addEventListener("DOMContentLoaded", function() {';
echo '    const selectAll = document.getElementById("select-all");';
echo '    const checkboxes = document.querySelectorAll(".chatbot-checkbox");';
echo '    selectAll.addEventListener("change", function() {';
echo '        checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });';
echo '    });';
echo '});';
echo '</script>';

}




// display the settings page
function tmcas_chatbot_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Chatbot Assistant Settings', 'tm-chatbot-assistant'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('tmcas_chatbot_settings_group');
            do_settings_sections('tm-chatbot-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Hook to initialize settings
add_action('admin_init', 'tmcas_chatbot_register_settings');

function tmcas_chatbot_register_settings() {
    // Register settings
    register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_openai_api_key', [	'sanitize_callback' => 'sanitize_text_field',
]);

	register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_assistant_id', [
    'sanitize_callback' => 'sanitize_text_field',
]);

	register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_title', [
    'sanitize_callback' => 'sanitize_text_field',
]);

	register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_assistant_name', [
    'sanitize_callback' => 'sanitize_text_field',
]);

	register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_placeholder', [
    'sanitize_callback' => 'sanitize_text_field',
]);

register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_default_question', [
    'sanitize_callback' => 'sanitize_text_field',
]);

	register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_background_color', [
    'sanitize_callback' => 'sanitize_hex_color',
]);

	register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_text_color', [
    'sanitize_callback' => 'sanitize_hex_color',
]);

	register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_thread_persistence', [
    'sanitize_callback' => 'absint',
]);

register_setting('tmcas_chatbot_settings_group', 'tmcas_chatbot_avatar', [
    'sanitize_callback' => 'sanitize_text_field',
]);


    add_settings_section(
        'tmcas_chatbot_main_section',
        __('Chatbot Settings', 'tm-chatbot-assistant'),
        null,
        'tm-chatbot-settings'
    );

    add_settings_field('tmcas_chatbot_avatar', __('Chatbot Avatar', 'tm-chatbot-assistant'), 'tmcas_chatbot_avatar_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_openai_api_key', __('OpenAI API Key', 'tm-chatbot-assistant'), 'tmcas_chatbot_openai_api_key_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_assistant_id', __('OpenAI Assistant ID', 'tm-chatbot-assistant'), 'tmcas_chatbot_assistant_id_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_title', __('Chatbox Title', 'tm-chatbot-assistant'), 'tmcas_chatbot_title_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_assistant_name', __('Assistant Name', 'tm-chatbot-assistant'), 'tmcas_chatbot_assistant_name_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_placeholder', __('Chatbox Placeholder', 'tm-chatbot-assistant'), 'tmcas_chatbot_placeholder_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_default_question', __('Initial Default Question', 'tm-chatbot-assistant'), 'tmcas_chatbot_default_question_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_background_color', __('Chatbox Background Color', 'tm-chatbot-assistant'), 'tmcas_chatbot_background_color_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_text_color', __('Chatbox Text Color', 'tm-chatbot-assistant'), 'tmcas_chatbot_text_color_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
    add_settings_field('tmcas_chatbot_thread_persistence', __('Thread Persistence', 'tm-chatbot-assistant'), 'tmcas_chatbot_thread_persistence_callback', 'tm-chatbot-settings', 'tmcas_chatbot_main_section');
}

function tmcas_chatbot_thread_persistence_callback() {
    $persistence_time = get_option('tmcas_chatbot_thread_persistence', 30);
    echo '<input type="number" name="tmcas_chatbot_thread_persistence" value="' . esc_attr($persistence_time) . '" min="1" />';
    echo '<p class="description">' . esc_html__('Set how long a chat thread...', 'tm-chatbot-assistant') . '<br>';

    echo '<p class="description">' . esc_html__('Threads will timeout after this time.', 'tm-chatbot-assistant') . '<br>';
    echo esc_html__('User can restart a new conversation by clicking the ↻ button.', 'tm-chatbot-assistant') . '</p>';
}

function tmcas_chatbot_avatar_callback() {
    $selected_avatar = get_option('tmcas_chatbot_avatar', 'female');
    ?>
    <select name="tmcas_chatbot_avatar">
        <option value="female" <?php selected($selected_avatar, 'female'); ?>>
            <?php esc_html_e('Female Assistant', 'tm-chatbot-assistant'); ?>
        </option>
        <option value="male" <?php selected($selected_avatar, 'male'); ?>>
            <?php esc_html_e('Male Assistant', 'tm-chatbot-assistant'); ?>
        </option>
    </select>
    <p class="description"><?php esc_html_e('Select the chatbot avatar image.', 'tm-chatbot-assistant'); ?></p>

    <?php
}





// Callback function to render Background Color input
function tmcas_chatbot_background_color_callback() {
    $background_color = get_option('tmcas_chatbot_background_color', '#0073aa');
    echo '<input type="color" name="tmcas_chatbot_background_color" value="' . esc_attr($background_color) . '">';
}

// Callback function to render Text Color input
function tmcas_chatbot_text_color_callback() {
    $text_color = get_option('tmcas_chatbot_text_color', '#ffffff');
    echo '<input type="color" name="tmcas_chatbot_text_color" value="' . esc_attr($text_color) . '">';
}

// Callback function to render Default Question input
function tmcas_chatbot_default_question_callback() {
    $default_question = get_option('tmcas_chatbot_default_question', '');
    echo '<input type="text" name="tmcas_chatbot_default_question" value="' . esc_attr($default_question) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('Enter an initial default question. This question will appear as a button below the SEND button in the chatbox.', 'tm-chatbot-assistant') . '</p>';
}

// Callback function to render Chatbox Title input
function tmcas_chatbot_title_callback() {
    $title = get_option('tmcas_chatbot_title', __('Chat with AI', 'tm-chatbot-assistant'));
    echo '<input type="text" name="tmcas_chatbot_title" value="' . esc_attr($title) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('I.e. Electrician, Plumbing Expert, Customer Service, Mortgage Advisor etc.', 'tm-chatbot-assistant') . '</p>';
}

// Callback function to render Assistant Name input
function tmcas_chatbot_assistant_name_callback() {
    $assistant_name = get_option('tmcas_chatbot_assistant_name', __('AI Assistant', 'tm-chatbot-assistant'));
    echo '<input type="text" name="tmcas_chatbot_assistant_name" value="' . esc_attr($assistant_name) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('Personal name for your assistant.', 'tm-chatbot-assistant') . '</p>';
}

// Callback function to render Placeholder input
function tmcas_chatbot_placeholder_callback() {
    $placeholder = get_option('tmcas_chatbot_placeholder', __('Type your message...', 'tm-chatbot-assistant'));
    echo '<input type="text" name="tmcas_chatbot_placeholder" value="' . esc_attr($placeholder) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('Placeholder prompt in the user input box.', 'tm-chatbot-assistant') . '</p>';
}

// Callback function to render OpenAI API Key input
function tmcas_chatbot_openai_api_key_callback() {
    $api_key = get_option('tmcas_chatbot_openai_api_key');
    echo '<input type="text" name="tmcas_chatbot_openai_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('Get', 'tm-chatbot-assistant') . ' <a href="' . esc_url('https://platform.openai.com/api-keys') . '" target="_blank">' . esc_html__('API key', 'tm-chatbot-assistant') . '</a> ' . esc_html__('from OpenAI', 'tm-chatbot-assistant') . '</p>';
}

// Callback function to render Assistant ID input
function tmcas_chatbot_assistant_id_callback() {
    $assistant_id = get_option('tmcas_chatbot_assistant_id');
    echo '<input type="text" name="tmcas_chatbot_assistant_id" value="' . esc_attr($assistant_id) . '" class="regular-text">';
    echo '<p class="description">' . esc_html__('Your assistant_id from', 'tm-chatbot-assistant') . ' <a href="https://platform.openai.com/assistants/" target="_blank">' . esc_html__('OpenAI Assistants', 'tm-chatbot-assistant') . '</a></p>';
}



// Include the admin page for post selection and content extraction
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
}

