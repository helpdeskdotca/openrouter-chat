jQuery(document).ready(function($) {
    let chatHistory = [];
    let sessionId = '';
    const storageKey = 'orc_chat_history_v1';
    const sessionKey = 'orc_chat_session_id';
    
    // Elements
    const chatWindow = $('#orc-chat-window');
    const msgContainer = $('#orc-chat-messages');
    const inputArea = $('#orc-input-area');
    const leadForm = $('#orc-lead-form');
    const typingIndicator = $('#orc-typing');
    const sendBtn = $('#orc-send-btn');
    const leadSubmitBtn = $('#orc-lead-submit');
    const bubble = $('#orc-chat-bubble');
    const closeBtn = $('#orc-close-btn');
    const resetBtn = $('#orc-reset-btn'); // NEW BUTTON
    
    function init() {
        // Load Session ID or Create New
        sessionId = localStorage.getItem(sessionKey);
        if (!sessionId) {
            sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem(sessionKey, sessionId);
        }

        const saved = localStorage.getItem(storageKey);
        if (saved) {
            try {
                chatHistory = JSON.parse(saved);
                chatHistory.forEach(msg => {
                    appendMessageUI(msg.role, msg.content, false);
                });
            } catch(e) {}
        }
        checkLeadStatus();
    }
    
    function checkLeadStatus() {
        const hasLead = localStorage.getItem('orc_lead_submitted');
        if (orc_ajax.require_lead === '1' && !hasLead) {
            leadForm.addClass('orc-visible');
            msgContainer.hide(); inputArea.hide();
        } else {
            leadForm.removeClass('orc-visible');
            msgContainer.show(); inputArea.css('display', 'flex');
        }
    }
    init();

    // --- NEW CHAT LOGIC ---
    resetBtn.on('click', function(e) {
        e.stopPropagation(); // Prevent closing window
        if(confirm('Start a new chat? This will clear current conversation.')) {
            // 1. Clear UI
            msgContainer.find('.orc-message:not(:first)').remove(); // Keep welcome msg
            // 2. Clear Data
            chatHistory = [];
            localStorage.removeItem(storageKey);
            // 3. New Session ID
            sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem(sessionKey, sessionId);
            // 4. Focus
            $('#orc-user-input').focus();
        }
    });

    function toggleChat() {
        if (chatWindow.hasClass('orc-open')) {
            chatWindow.removeClass('orc-open');
        } else {
            chatWindow.addClass('orc-open');
            checkLeadStatus(); 
            if (leadForm.hasClass('orc-visible')) $('#orc-lead-name').focus();
            else setTimeout(() => $('#orc-user-input').focus(), 100);
            scrollToBottom();
        }
    }
    bubble.on('click', toggleChat);
    closeBtn.on('click', toggleChat);

    leadSubmitBtn.on('click', function() {
        const name = $('#orc-lead-name').val().trim();
        const email = $('#orc-lead-email').val().trim();
        if(!name || !email) { alert('Please enter your Name and Email.'); return; }
        const originalText = leadSubmitBtn.text();
        leadSubmitBtn.text('Connecting...').prop('disabled', true);

        $.post(orc_ajax.ajax_url, {
            action: 'orc_save_lead',
            nonce: orc_ajax.nonce,
            name: name,
            email: email
        }).done(function(response) {
            if (response.success) {
                localStorage.setItem('orc_lead_submitted', 'true');
                leadForm.fadeOut(200, function() {
                    leadForm.removeClass('orc-visible');
                    msgContainer.fadeIn(200);
                    inputArea.css('display', 'flex').hide().fadeIn(200);
                    $('#orc-user-input').focus();
                });
            } else {
                alert('Error saving. Please try again.');
                leadSubmitBtn.text(originalText).prop('disabled', false);
            }
        }).fail(function() {
            localStorage.setItem('orc_lead_submitted', 'true');
            leadForm.removeClass('orc-visible');
            msgContainer.show(); inputArea.css('display', 'flex');
        });
    });

    function sendMessage() {
        const inputField = $('#orc-user-input');
        const message = inputField.val().trim();
        if (message === '') return;

        inputField.val('');
        appendMessageUI('user', message, true);
        typingIndicator.addClass('orc-active'); 
        scrollToBottom();

        const apiMessages = chatHistory.map(m => ({role: m.role, content: m.content}));

        $.ajax({
            url: orc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'orc_chat_request',
                messages: apiMessages,
                page_context: "Page: " + orc_ajax.page_title + " | URL: " + orc_ajax.page_url,
                session_id: sessionId, // SEND SESSION ID
                nonce: orc_ajax.nonce
            },
            success: function(response) {
                typingIndicator.removeClass('orc-active');
                if (response.success) {
                    appendMessageUI('assistant', response.data, true);
                } else {
                    appendMessageUI('assistant', response.data, false);
                }
            },
            error: function() {
                typingIndicator.removeClass('orc-active');
                appendMessageUI('assistant', 'Chat not available right now please try again later.', false);
            }
        });
    }

    function appendMessageUI(role, text, save) {
        let rawHtml = text;
        if (typeof marked !== 'undefined') {
            rawHtml = role === 'user' ? escapeHtml(text) : marked.parse(text);
        } else {
            rawHtml = escapeHtml(text);
        }
        const className = role === 'user' ? 'orc-user-msg' : 'orc-bot-msg';
        msgContainer.append('<div class="orc-message ' + className + '">' + rawHtml + '</div>');
        scrollToBottom();
        if (save) {
            chatHistory.push({ role: role === 'user' ? 'user' : 'assistant', content: text });
            localStorage.setItem(storageKey, JSON.stringify(chatHistory));
        }
    }

    function scrollToBottom() {
        const el = document.getElementById('orc-chat-messages');
        if(el) el.scrollTop = el.scrollHeight;
    }
    function escapeHtml(text) {
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
    }

    sendBtn.on('click', sendMessage);
    $('#orc-user-input').on('keypress', function(e) { if (e.which == 13) sendMessage(); });
});