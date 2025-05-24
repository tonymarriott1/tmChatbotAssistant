
 // Function to get a cookie
    function getCookie(name) {
        let nameEQ = name + "=";
        let cookies = document.cookie.split(";");
        for (let i = 0; i < cookies.length; i++) {
            let c = cookies[i].trim();
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length);
        }
        return null;
    }



document.addEventListener("DOMContentLoaded", function() {
    let thread_id = null; // Variable to store the OpenAI thread ID for this chat session.

    /**
     * 1. Create a new OpenAI thread when the chatbox loads.
     *    This is done by sending an AJAX request to the WordPress backend (admin-ajax.php).
     */
fetch(tmcas_chatbot_ajax.ajaxurl, {
    method: "POST",
    headers: { 
        "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "action=tmcas_chatbot_delete_old_threads"
})
.then(() => {
    return fetch(tmcas_chatbot_ajax.ajaxurl, {
        method: "POST",
        headers: { 
            "Content-Type": "application/x-www-form-urlencoded",
            "OpenAI-Beta": "assistants=v2"  
        },
        body: "action=tmcas_chatbot_create_thread"
    });
})

.then(response => {
    return response.text(); // Get response as text before JSON parsing
})
.then(text => {
    return JSON.parse(text); // Attempt to parse JSON manually
})
.then(data => {
    if (data.success) {
        thread_id = data.data.thread_id;
    } else {
    }
})
.catch(error => console.error("Error creating thread:", error));


    // Select the chatbot input, send button, and message display area
    const chatbotInput = document.getElementById("tm-chatbot-input");
    const chatbotSend = document.getElementById("tm-chatbot-send");
    const chatbotMessages = document.getElementById("tm-chatbot-messages");

    // Add event listener to the send button
    chatbotSend.addEventListener("click", sendMessage);

    // Allow pressing Enter to send a message
    chatbotInput.addEventListener("keypress", function(event) {
        if (event.key === "Enter") sendMessage();
    });
        
        // Fix improperly formatted Markdown-style links: [text](url)
function formatMessage(message) {
    const markdownLinkPattern = /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g;
    message = message.replace(markdownLinkPattern, function(match, text, url) {
        return `<a href="${url}" target="_blank" class="chatbot-link">${text} 🔗</a>`;
    });

    // Fix plain URLs that are not inside Markdown links
    const urlPattern = /(?<!["'])\b(https?:\/\/[^\s<]+)(?<!["'])/g;
    message = message.replace(urlPattern, function(url) {
        return `<a href="${url}" target="_blank" class="chatbot-link">${url} 🔗</a>`;
    });

// mask errors
if (message.trim().toLowerCase() === "error: undefined") message = "Sorry, can you confirm what you said?";
    return message;
}

     
        

    /**
     * 2. Function to send a user message to OpenAI.
     *    - It sends the message to an existing OpenAI thread (using `thread_id`).
     */
function sendMessage() {
    const cookieThreadId = getCookie("chatbot_thread_id");

    // Check if cookie is missing or expired
    if (!cookieThreadId || !cookieThreadId.startsWith("thread_")) {
        chatbotMessages.innerHTML += `<div class='chatbot-message ai'>⚠️ Your session has expired. You can start a new conversation using the <strong>↻</strong> button.</div>`;
        scrollToBottom();
        return;
    }

    // Also make sure the JS variable is in sync
    if (!thread_id || thread_id !== cookieThreadId) {
        thread_id = cookieThreadId;
    }

    let userMessage = chatbotInput.value.trim();
    if (!userMessage) return;

    // Display user message in the chat window
    chatbotMessages.innerHTML += `<div class='chatbot-message user'>${userMessage}</div>`;
    chatbotInput.value = "";

    // Add temp space under question
    const spacerId = "scroll-spacer";
    chatbotMessages.innerHTML += `<div id="${spacerId}" style="height: 80px;"></div>`;
    scrollToBottom();

    // Show the loading indicator
    document.getElementById("tm-chatbot-loader").style.display = "flex";
    
    
    fetch(tmcas_chatbot_ajax.ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=tmcas_chatbot_send&message=${encodeURIComponent(userMessage)}&thread_id=${encodeURIComponent(thread_id)}&chatbot_nonce=${encodeURIComponent(tmcas_chatbot_ajax.chatbot_nonce)}`
    })
    .then(response => {
        return response.text();
    })
    .then(text => {
        return JSON.parse(text);
    })
    .then(data => {
        // Hide the loading indicator after response is received
        document.getElementById("tm-chatbot-loader").style.display = "none";

        if (data.success) {
            let assistantResponse = "Can you clarify that question please?";

            if (data.data.response && data.data.response.length > 0) {
                assistantResponse = data.data.response;
            }

            
            chatbotMessages.innerHTML += `<div class='chatbot-message ai'>${formatMessage(assistantResponse)}</div>`;

           
        } else {
            // replaces actual error ${data.error}
            chatbotMessages.innerHTML += `<div class='chatbot-message error'>I seem to have lost that info. Can you try again please?</div>`;
        }
        // remove temp spacer under question
        const spacer = document.getElementById("scroll-spacer");
if (spacer) spacer.remove();
    })
    .catch(error => {
        //replaces ${error.message}
        chatbotMessages.innerHTML += "<div>Sorry, this conversation has timed out. Click the refresh button above to start a new conversation.</div>";

        // Hide the loading indicator if an error occurs
        document.getElementById("tm-chatbot-loader").style.display = "none";
    	});
	}
});


document.addEventListener("DOMContentLoaded", function() {
    let chatbotContainer = document.getElementById("tm-chatbot-container");
    let chatbotAvatar = document.getElementById("tm-chatbot-avatar");
    let chatbotClose = document.getElementById("tm-chatbot-close");

    // Show chatbox when avatar is clicked
    chatbotAvatar.addEventListener("click", function() {
        chatbotContainer.style.display = "block";
     scrollToBottom();
    
    });

    // Close chatbox when close button is clicked
    chatbotClose.addEventListener("click", function() {
        chatbotContainer.style.display = "none";
    });
});


document.addEventListener("DOMContentLoaded", function() {
    let chatbotContainer = document.getElementById("tm-chatbot-container");
    let chatbotAvatar = document.getElementById("tm-chatbot-avatar");
    let chatbotClose = document.getElementById("tm-chatbot-close");
    let chatbotFullscreen = document.getElementById("tm-chatbot-fullscreen");

    // Show chatbox when avatar is clicked
    chatbotAvatar.addEventListener("click", function() {
        chatbotContainer.style.display = "block";
    });

    // Close chatbox when close button is clicked
    chatbotClose.addEventListener("click", function() {
        chatbotContainer.style.display = "none";
    });
 });



document.addEventListener("DOMContentLoaded", function() {
    let thread_id = getCookie("chatbot_thread_id");

    if (thread_id && thread_id.startsWith("thread_")) {
        fetchChatHistory(thread_id);
    } else {
        fetch(tmcas_chatbot_ajax.ajaxurl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=tmcas_chatbot_create_thread"
        })
        .then(response => response.json())
.then(data => {
    if (data.success && data.data && data.data.thread_id && data.data.thread_id.startsWith("thread_")) {
        thread_id = data.data.thread_id;
        setCookie("chatbot_thread_id", thread_id, tmcas_chatbot_ajax.thread_expiry_minutes);
        
        
        // Inject intro message immediately
        const chatbotMessages = document.getElementById("tm-chatbot-messages");
        const assistantName = document.querySelector("#tm-chatbot-title")?.textContent.trim() || "chat";
        chatbotMessages.innerHTML = `<p class='chatbot-message ai'>🔄 Welcome to <strong>${assistantName}</strong>. How can I help you today?</p>`;

        // Optional: scroll after DOM renders
        requestAnimationFrame(() => {
            scrollToBottom();
        });

        // Then call OpenAI fetch (which will return empty, but that’s fine)
        //fetchChatHistory(thread_id);
    } 
});
    }

    // Function to set a cookie
    function setCookie(name, value, minutes) {
        let expires = new Date();
        expires.setTime(expires.getTime() + (minutes * 60 * 1000));
        document.cookie = name + "=" + value + "; expires=" + expires.toUTCString() + "; path=/";
    }
});


document.addEventListener("DOMContentLoaded", function () {
    let newConversationButton = document.getElementById("tm-chatbot-new-conversation");

    if (newConversationButton) {
        newConversationButton.addEventListener("click", function () {
            restartChatbot();
        });
    }
});

//restart the chatbot by clearing the thread and creating a new one
function restartChatbot() {
    document.cookie = "chatbot_thread_id=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/"; // Delete the cookie
    let chatbotMessages = document.getElementById("tm-chatbot-messages");
    let assistantName = document.querySelector("#tm-chatbot-header span")?.textContent.trim() || "the assistant";
    if (chatbotMessages) {
        chatbotMessages.innerHTML = "<p class='chatbot-message ai'>🔄 Starting a new conversation with "+assistantName+"</p>";
    }

    fetch(tmcas_chatbot_ajax.ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=tmcas_chatbot_create_thread"
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.thread_id) {
            document.cookie = "chatbot_thread_id=" + data.thread_id + "; path=/"; // Set new thread ID
            
        let chatbotMessages = document.getElementById("tm-chatbot-messages");
        if (chatbotMessages) {
                chatbotMessages.innerHTML += "<p class='chatbot-message ai'>New conversation started. How can I assist you?</p>";
            }
        } 
    })
}



function fetchChatHistory(thread_id) {
    fetch(tmcas_chatbot_ajax.ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=tmcas_chatbot_fetch_history&thread_id=${encodeURIComponent(thread_id)}&chatbot_nonce=${encodeURIComponent(tmcas_chatbot_ajax.chatbot_nonce)}`

    })
    .then(response => response.json())
    .then(data => {
        if (!data.success || !data.data || !Array.isArray(data.data.messages)) {
            return;
        }


        let chatbotMessages = document.getElementById("tm-chatbot-messages");
        chatbotMessages.innerHTML = ""; // Clear chatbox

        // Reverse messages so the latest appears at the bottom
        let reversedMessages = data.data.messages.reverse();

        reversedMessages.forEach((msg) => {
    const messageContent = (typeof msg === 'object' && msg.content) ? msg.content : msg;
    const messageRole = msg.role === 'user' ? 'user' : 'ai'; // fallback
    const messageClass = `chatbot-message ${messageRole}`;

    chatbotMessages.innerHTML += `<div class='${messageClass}'>${messageContent}</div>`;
		});
    })
    .catch(error => console.error("Fetch Error:", error));
}

// scroll the chatbox to the bottom
function scrollToBottom() {
    let chatbox = document.getElementById("tm-chatbot-messages");
    if (chatbox) {
        chatbox.scrollTop = chatbox.scrollHeight;
    }
}


document.addEventListener("DOMContentLoaded", function () {
    let defaultQuestionButton = document.getElementById("tm-chatbot-default-question");
    let inputField = document.getElementById("tm-chatbot-input");
    let sendButton = document.getElementById("tm-chatbot-send");

    if (defaultQuestionButton) {
        defaultQuestionButton.addEventListener("click", function () {
            inputField.value = defaultQuestionButton.innerText;
            sendButton.click();
        });
    }
});


document.addEventListener("DOMContentLoaded", function () {
    function hexToRgb(hex) {
        hex = hex.replace(/^#/, '');
        let bigint = parseInt(hex, 16);
        let r = (bigint >> 16) & 255;
        let g = (bigint >> 8) & 255;
        let b = bigint & 255;
        return r + "," + g + "," + b;
    }

    if (tmcas_chatbot_ajax.background_color) {
        document.documentElement.style.setProperty("--chatbot-bg-color", tmcas_chatbot_ajax.background_color);
        document.documentElement.style.setProperty("--chatbot-bg-color-rgb", hexToRgb(tmcas_chatbot_ajax.background_color));
    }

    if (tmcas_chatbot_ajax.text_color) {
        document.documentElement.style.setProperty("--chatbot-text-color", tmcas_chatbot_ajax.text_color);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const chatbox = document.getElementById("tm-chatbot-container");
    const chatMessages = document.getElementById("tm-chatbot-messages");
    const fullScreenBtn = document.getElementById("tm-chatbot-fullscreen");

    let isFullScreen = false;
    let originalWidth = chatbox.style.width; // Store the original width
    let originalHeight = chatbox.style.height; // Store the original height

    fullScreenBtn.addEventListener("click", function () {
        if (!isFullScreen) {
            // Switch to full screen
            chatbox.style.width = "90vw";
            chatbox.style.height = "70vh";
            chatMessages.style.maxHeight = "40vh";
            chatMessages.style.height = "100vh";
            fullScreenBtn.innerHTML = "▼"; // Change icon to exit full screen
        } else {
            // Revert to original size (using stored values)
            chatbox.style.width = originalWidth;
            chatbox.style.height = originalHeight;
            chatMessages.style.height = "200px";
            chatMessages.style.maxHeight = "300px";
            fullScreenBtn.innerHTML = "⛶"; // Change icon back to full screen
        }

        isFullScreen = !isFullScreen;
    });
});





