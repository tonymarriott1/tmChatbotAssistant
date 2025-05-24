<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin-related options
delete_option('tmcas_chatbot_background_color');
delete_option('tmcas_chatbot_text_color');
delete_option('tmcas_chatbot_title');
delete_option('tmcas_chatbot_assistant_name');
delete_option('tmcas_chatbot_placeholder');
delete_option('tmcas_chatbot_default_question');
delete_option('tmcas_chatbot_thread_persistence');
delete_option('tmcas_chatbot_openai_api_key');
delete_option('tmcas_chatbot_assistant_id');
delete_option('tmcas_chatbot_threads');
delete_option('tmcas_chatbot_avatar');
