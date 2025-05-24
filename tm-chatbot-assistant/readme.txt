=== TM Chatbot Assistant ===
Contributors: tonymarriott  
Plugin URI: https://tony-marriott.com/tm-chatbot-assistant-plugin/  
Author URI: https://tony-marriott.com   
Tags: chatbot, AI assistant, OpenAI, AI chatbot, GPT  
Requires at least: 5.8  
Tested up to: 6.8  
Requires PHP: 8.0  
Stable tag: 1.0.0  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

A powerful AI chatbot for WP that integrates with OpenAI's Assistants API. Provide intelligent, conversational support to your website visitors.

== Description ==

**TM Chatbot Assistant** is a fully conversational AI chatbot plugin for WordPress, powered by OpenAI’s Assistants API (v2). Add an intelligent, context-aware assistant to your site for customer support, lead generation, product guidance, and more — all with zero coding.

Chatbot Assistants are created in OpenAI.com. Chatbot conversation is between your website front-end Chatbot and OpenAI.com.

You will need an OpenAI account from https://auth.openai.com/create-account.
See https://openai.com/policies/terms-of-use/ and https://openai.com/policies/privacy-policy/

### Key Features

- Seamless integration with OpenAI’s Assistants API (v2)  
- Floating, customizable chatbox UI  
- Multi-turn conversations using assistant threads  
- Supports assistant instructions, memory, and file-based training  
- Customizable colors, title, placeholder text, avatar, and default question  
- Choose exactly which pages or posts display the chatbot  
- Secure backend communication via server-side PHP  

Whether you're answering FAQs, guiding visitors, or capturing leads, TM Chatbot Assistant lets you deploy a smart AI experience in just minutes.

== Installation ==


Installation from within WordPress

    Visit Plugins > Add New.
    Search for Plugin Check.
    Install and activate the Plugin Check plugin.

Manual installation

    Upload the entire plugin-check folder to the /wp-content/plugins/ directory.
    Visit Plugins.
    Activate the Plugin Check plugin.




3. Go to **Settings > Chatbot Display** to choose which pages will show the chatbot
4. Go to **Settings > Chatbot Assistant** to configure:
   - OpenAI API Key  
   - Assistant ID  
   - Chatbox title, placeholder, assistant name  
   - Avatar image and default question  
   - Colors (background, text)  
5. Go to **Settings > Chatbot Export** to create the assistant training file from your website.

You must also create your Assistant at: https://platform.openai.com/assistants  
Be sure to add structured instructions and upload any training files you exported using this plugin. 

== Frequently Asked Questions ==

= Do I need an OpenAI account? =  
Yes, you’ll need an OpenAI account with an API key and a configured Assistant.

= Does it support fully conversational chat? =  
Yes — it uses the OpenAI thread system to maintain multi-turn context.

= Can I train the assistant on my website content? =  
Yes. The plugin lets you export selected pages/posts into a file you can upload to OpenAI.

= Will the chatbot appear everywhere? =  
No. You control where it shows via the **Chatbot Display** admin page.

== Screenshots ==

1. Chatbot box on the front end  
2. Chatbot Assistant Settings  
3. Chatbot Display options  
4. Chatbot Export options  

== Changelog ==

= 1.0.0 =
* Initial release with OpenAI Assistants support
* Floating chatbot UI


== License ==

This plugin is licensed under the **GPLv2 or later**.  
See https://www.gnu.org/licenses/gpl-2.0.html for details.
