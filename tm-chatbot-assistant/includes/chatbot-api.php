<?php

if (!defined('ABSPATH')) {
    exit;
}

// Fetch API Key & Assistant ID from plugin settings
define('OPENAI_API_KEY', get_option('tmcas_chatbot_openai_api_key', ''));
define('ASSISTANT_ID', get_option('tmcas_chatbot_assistant_id', ''));

// Register AJAX action hooks for creating threads
add_action('wp_ajax_tmcas_chatbot_create_thread', 'tmcas_chatbot_create_thread');
add_action('wp_ajax_nopriv_tmcas_chatbot_create_thread', 'tmcas_chatbot_create_thread');

// Cleanup old threads from OpenAI and WordPress
function tmcas_chatbot_delete_old_threads() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('Unauthorized', 'tm-chatbot-assistant'));
    }

    $api_key = get_option('tmcas_chatbot_openai_api_key');
    if (!$api_key) {
        return;
    }

    $now = time();
    $persistence_time = get_option('tmcas_chatbot_thread_persistence', 30);
    $threshold = $now - ($persistence_time * 60);

    $threads = get_option('tmcas_chatbot_threads', []);
    $deleted_count = 0;

    if (!empty($threads)) {
        foreach ($threads as $thread_id => $timestamp) {
            if ($timestamp < $threshold) {
                $delete_url = "https://api.openai.com/v1/threads/$thread_id";
                $response = wp_remote_request($delete_url, [
                    'method'  => 'DELETE',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'OpenAI-Beta'   => 'assistants=v2',
                        'Content-Type'  => 'application/json',
                    ],
                ]);

                $response_body = json_decode(wp_remote_retrieve_body($response), true);

                if (isset($response_body['error']['message']) && strpos($response_body['error']['message'], 'No thread found') !== false) {
                    unset($threads[$thread_id]);
                } elseif (!isset($response_body['error'])) {
                    unset($threads[$thread_id]);
                    $deleted_count++;
                }
            }
        }

        update_option('tmcas_chatbot_threads', $threads);
    }

    wp_send_json_success(['message' => "Deleted $deleted_count old threads."]);
}





add_action('wp_ajax_tmcas_chatbot_delete_old_threads', 'tmcas_chatbot_delete_old_threads');


function tmcas_chatbot_create_thread() {
    // Reuse thread from cookie if present
    if (isset($_COOKIE['chatbot_thread_id'])) {
        $thread_id = sanitize_text_field(wp_unslash($_COOKIE['chatbot_thread_id']));
        wp_send_json_success(['thread_id' => $thread_id]);
    }

    // Create a new thread via OpenAI
    $api_url = "https://api.openai.com/v1/threads";
    $headers = [
        "Authorization" => "Bearer " . OPENAI_API_KEY,
        "OpenAI-Beta"   => "assistants=v2",
        "Content-Type"  => "application/json"
    ];

    $response = wp_remote_post($api_url, [
        'headers' => $headers,
        'method'  => 'POST',
        'body'    => json_encode([]),
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['error' => __('Failed to create thread.', 'tm-chatbot-assistant')]);
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($response_body['id'])) {
        wp_send_json_error([
            'error' => __('Invalid response from OpenAI.', 'tm-chatbot-assistant'),
        ]);
    }

    $thread_id = $response_body['id'];
    $current_time = time();
    $persistence_time = get_option('tmcas_chatbot_thread_persistence', 30);

    // Store the thread ID with timestamp
    $threads = get_option('tmcas_chatbot_threads', []);
    if (!is_array($threads)) {
        $threads = [];
    }

    $threads[$thread_id] = $current_time;
    update_option('tmcas_chatbot_threads', $threads);

    // Set cookie to persist the thread for the configured time
    setcookie("chatbot_thread_id", $thread_id, time() + ($persistence_time * 60), "/");

    wp_send_json_success(['thread_id' => $thread_id]);
}


// Register AJAX handlers for sending messages
add_action('wp_ajax_tmcas_chatbot_send', 'tmcas_chatbot_send_message');
add_action('wp_ajax_nopriv_tmcas_chatbot_send', 'tmcas_chatbot_send_message');

function tmcas_chatbot_send_message() {
    if (!isset($_POST['message'], $_POST['thread_id'])) {
        wp_send_json_error(['message' => __('Missing message or thread ID.', 'tm-chatbot-assistant')]);
    }
    
 //Check nonce exists
    if (!isset($_POST['chatbot_nonce'])) {
        wp_send_json_error(['message' => __('Security check failed (missing nonce).', 'tm-chatbot-assistant')]);
    }

    $nonce = sanitize_text_field(wp_unslash($_POST['chatbot_nonce']));

    if (!wp_verify_nonce($nonce, 'tmcas_chatbot_action')) {
        wp_send_json_error(['message' => __('Security check failed (invalid nonce).', 'tm-chatbot-assistant')]);
    }


    $apiKey       = get_option('tmcas_chatbot_openai_api_key');
    $assistantId  = get_option('tmcas_chatbot_assistant_id');
    $userMessage = isset($_POST['message']) ? sanitize_text_field(wp_unslash($_POST['message'])) : '';
$threadId    = isset($_POST['thread_id']) ? sanitize_text_field(wp_unslash($_POST['thread_id'])) : '';

    // Step 1: Send user's message to OpenAI
    $messagePayload = [
        'role'    => 'user',
        'content' => $userMessage,
    ];

    $messageResponse = wp_remote_post("https://api.openai.com/v1/threads/{$threadId}/messages", [
        'method'  => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ],
        'body' => json_encode($messagePayload),
    ]);

    if (is_wp_error($messageResponse)) {
        wp_send_json_error(['message' => __('Failed to send message to OpenAI.', 'tm-chatbot-assistant')]);
    }

    // Step 2: Trigger assistant run
    $runResponse = wp_remote_post("https://api.openai.com/v1/threads/{$threadId}/runs", [
        'method'  => 'POST',
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ],
        'body' => json_encode(['assistant_id' => $assistantId]),
    ]);

    $runData = json_decode(wp_remote_retrieve_body($runResponse), true);
    $runId   = $runData['id'] ?? null;

    if (!$runId) {
        wp_send_json_error(['message' => __('Failed to start assistant run.', 'tm-chatbot-assistant')]);
    }

    // Step 3: Poll for completion
    $completed = false;
    for ($i = 0; $i < 20; $i++) {
        sleep(2);

        $checkResponse = wp_remote_get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'OpenAI-Beta'   => 'assistants=v2',
            ],
        ]);

        $checkData = json_decode(wp_remote_retrieve_body($checkResponse), true);
        $status    = $checkData['status'] ?? null;

        if ($status === 'completed') {
            $completed = true;
            break;
        } elseif (in_array($status, ['failed', 'cancelled'], true)) {
            wp_send_json_error(['message' => __('Assistant run failed.', 'tm-chatbot-assistant')]);
        }
    }

    if (!$completed) {
        wp_send_json_error(['message' => __('Assistant run did not complete in time.', 'tm-chatbot-assistant')]);
    }

    // Step 4: Get the assistant's response
    $messagesResponse = wp_remote_get("https://api.openai.com/v1/threads/{$threadId}/messages", [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
            'OpenAI-Beta'   => 'assistants=v2',
        ],
    ]);

    $messagesData     = json_decode(wp_remote_retrieve_body($messagesResponse), true);
    $messages         = $messagesData['data'] ?? [];
    $assistantMessage = '';

    foreach ($messages as $msg) {
        if ($msg['role'] === 'assistant') {
            $assistantMessage = implode("\n", array_map(function ($c) {
                return $c['text']['value'] ?? '';
            }, $msg['content']));
            break;
        }
    }

    // Step 5: Fallback message
    if (empty($assistantMessage)) {
        $assistantMessage = __('Sorry, I took too long thinking about that one. Perhaps you can ask again or rephrase the question?', 'tm-chatbot-assistant');
    }

    wp_send_json_success([
        'response' => $assistantMessage,
        'timeout'  => 30,
    ]);
}



add_action('wp_ajax_tmcas_chatbot_fetch_history', 'tmcas_chatbot_fetch_history');
add_action('wp_ajax_nopriv_tmcas_chatbot_fetch_history', 'tmcas_chatbot_fetch_history');

function tmcas_chatbot_fetch_history() {
	$nonce = isset($_POST['chatbot_nonce']) ? sanitize_text_field(wp_unslash($_POST['chatbot_nonce'])) : '';

if (!wp_verify_nonce($nonce, 'tmcas_chatbot_action')) {
    wp_send_json_error(['message' => __('Security check failed.', 'tm-chatbot-assistant')]);
}

    if (empty($_POST['thread_id'])) {
        wp_send_json_error(['error' => __('Missing thread ID.', 'tm-chatbot-assistant')]);
    }

   $thread_id = sanitize_text_field(wp_unslash($_POST['thread_id']));
    $api_url   = "https://api.openai.com/v1/threads/{$thread_id}/messages";

    $headers = [
        'Authorization' => 'Bearer ' . OPENAI_API_KEY,
        'OpenAI-Beta'   => 'assistants=v2',
        'Content-Type'  => 'application/json',
    ];

    $response = wp_remote_get($api_url, ['headers' => $headers]);

    if (is_wp_error($response)) {
        wp_send_json_error(['error' => __('Failed to fetch chat history.', 'tm-chatbot-assistant')]);
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($response_body['data']) || !is_array($response_body['data'])) {
        wp_send_json_error([
            'error'         => __('Invalid response from OpenAI.', 'tm-chatbot-assistant'),
            'full_response' => $response_body,
        ]);
    }

    $messages = [];

    foreach ($response_body['data'] as $msg) {
        if (!empty($msg['content'][0]['text']['value']) && !empty($msg['role'])) {
            $messages[] = [
                'role'    => sanitize_text_field($msg['role']),
                'content' => sanitize_textarea_field($msg['content'][0]['text']['value']),
            ];
        }
    }

    wp_send_json_success(['messages' => $messages]);
}
