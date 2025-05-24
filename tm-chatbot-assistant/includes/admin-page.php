<?php
function chatbot_admin_menu() {
    add_submenu_page(
        'options-general.php',
        __('Chatbot Content Export', 'tm-chatbot-assistant'),
        __('Chatbot Export', 'tm-chatbot-assistant'),
        'manage_options',
        'chatbot-export',
        'chatbot_export_page'
    );
}

add_action('admin_menu', 'chatbot_admin_menu');

function chatbot_export_page() {
    ?>
    <div class="wrap">
        <h2><?php echo esc_html__('Export Website Content for Chatbot', 'tm-chatbot-assistant'); ?></h2>
        
        <p style="background: #f1f1f1; padding: 10px; border-left: 4px solid #0073aa;">
            <strong><?php echo esc_html__('Select all required pages and posts from the list below to create a JSON data file for training the assistant.', 'tm-chatbot-assistant'); ?></strong><br>
            <?php echo esc_html__('The training file contains page Title, Page URL and any text on that webpages.', 'tm-chatbot-assistant'); ?><br>
            <?php echo esc_html__('Once downloaded, upload the file to the assistant in the OpenAI Assistants page:', 'tm-chatbot-assistant'); ?>
            <a href="https://platform.openai.com/assistants/" target="_blank">https://platform.openai.com/assistants</a>.<br>
            <?php echo esc_html__('Note that Assistants and API Keys now reside within an OpenAI Project. You can have multiple Assistants and API Keys in a project but the billing breakdown is per project. So logically keep clients in separate projects.', 'tm-chatbot-assistant'); ?>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('chatbot_export_action', 'chatbot_export_nonce'); ?>
            <input type="hidden" name="action" value="chatbot_export">
            <?php
            $args = array(
                'post_type' => array('post', 'page'),
                'posts_per_page' => -1
            );
            $posts = get_posts($args);

            if ($posts) {
                echo '<label><input type="checkbox" id="select-all"> <strong>' . esc_html__('Select All', 'tm-chatbot-assistant') . '</strong></label>';
                echo '<ul id="post-list">';
                foreach ($posts as $post) {
                    echo '<li><input type="checkbox" class="post-checkbox" name="chatbot_selected_posts[]" value="' . esc_attr($post->ID) . '"> ' . esc_html($post->post_title) . '</li>';
                }
                echo '</ul>';
                submit_button(esc_html__('Generate and Download', 'tm-chatbot-assistant'));
            } else {
                echo '<p>' . esc_html__('No posts or pages found.', 'tm-chatbot-assistant') . '</p>';
            }
            ?>
        </form>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        let selectAllCheckbox = document.getElementById("select-all");
        let checkboxes = document.querySelectorAll(".post-checkbox");
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener("change", function () {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
            });
        }
    });
    </script>
    <?php
}

function chatbot_export_generate_file() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to perform this action.', 'tm-chatbot-assistant'));
    }

    $nonce = isset($_POST['chatbot_export_nonce']) ? sanitize_text_field(wp_unslash($_POST['chatbot_export_nonce'])) : '';

if (!wp_verify_nonce($nonce, 'chatbot_export_action')) {
    wp_die(esc_html__('Security check failed.', 'tm-chatbot-assistant'));
}


    if (isset($_POST['chatbot_selected_posts'])) {
        $selected_posts = array_map('absint', wp_unslash($_POST['chatbot_selected_posts']));
        $export_data = [];

        foreach ($selected_posts as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $export_data[] = [
                    'title' => get_the_title($post_id),
                    'url' => get_permalink($post_id),
                   'content' => wp_strip_all_tags($post->post_content)
                ];
            }
        }

        $parsed_url = wp_parse_url(get_site_url());
		$site_url = isset($parsed_url['host']) ? $parsed_url['host'] : 'site';

        $file_name = sanitize_file_name($site_url . '-assistant-data.json');

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $file_name;
        file_put_contents($file_path, json_encode($export_data, JSON_PRETTY_PRINT));

        $nonce = wp_create_nonce('chatbot_export_action');
wp_safe_redirect(admin_url('options-general.php?page=chatbot-export&chatbot_download=' . $nonce . '&file=' . urlencode($file_name)));
exit;
    }
}

add_action('admin_post_chatbot_export', 'chatbot_export_generate_file');

function chatbot_export_download_link() {
    if (
        isset($_GET['chatbot_download'], $_GET['file']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['chatbot_download'])), 'chatbot_export_action')
    ) {
        $file = sanitize_file_name(wp_unslash($_GET['file']));
        $upload_dir = wp_upload_dir();
        $file_url = $upload_dir['url'] . '/' . $file;

        echo '<div class="updated"><p>' .
             esc_html__('Download your chatbot training file:', 'tm-chatbot-assistant') . ' <a href="' .
             esc_url($file_url) . '" download>' .
             esc_html__('Download JSON File', 'tm-chatbot-assistant') .
             '</a></p></div>';
    }
}

add_action('admin_notices', 'chatbot_export_download_link');
