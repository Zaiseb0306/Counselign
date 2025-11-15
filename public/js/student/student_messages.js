// Student Messages - adapted from counselor_messages.js
let currentUserId = null;
let messageUpdateInterval = null;
let isSearching = false;
let searchTimeout = null;
let lastMessageTimestamp = null; // Changed: Now resets per conversation
let autoSelectUserId = null;

function resolveImageUrl(path) {
    try {
        if (!path) return (window.BASE_URL || '/') + 'Photos/profile.png';
        const trimmed = String(path).trim();
        if (/^https?:\/\//i.test(trimmed)) return trimmed;
        if (trimmed.startsWith('/')) return (window.BASE_URL || '/') + trimmed.replace(/^\/+/, '');
        return (window.BASE_URL || '/') + trimmed;
    } catch (_) {
        return (window.BASE_URL || '/') + 'Photos/profile.png';
    }
}

function initializeMobileSidebar() {
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const conversationsSidebar = document.getElementById('conversationsSidebar');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');
    if (!mobileSidebarToggle || !conversationsSidebar || !mobileSidebarOverlay) return;
    function closeSidebar() {
        conversationsSidebar.classList.remove('active');
        mobileSidebarOverlay.classList.remove('active');
        mobileSidebarToggle.classList.remove('hidden');
        document.body.style.overflow = '';
    }
    function openSidebar() {
        conversationsSidebar.classList.add('active');
        mobileSidebarOverlay.classList.add('active');
        mobileSidebarToggle.classList.add('hidden');
        document.body.style.overflow = 'hidden';
    }
    mobileSidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (conversationsSidebar.classList.contains('active')) closeSidebar(); else openSidebar();
    });
    mobileSidebarOverlay.addEventListener('click', function(e) { e.stopPropagation(); closeSidebar(); });
    if (window.innerWidth <= 768) closeSidebar();
}

document.addEventListener('DOMContentLoaded', async function() {
    const ok = await checkSession();
    if (!ok) { window.location.href = (window.BASE_URL || '/') + 'auth/logout'; return; }
    initializeMobileSidebar();
    await initializeMessageInput();
    await loadConversations();
    const userList = document.querySelector('.conversations-list');
    if (userList) {
        userList.addEventListener('click', function(e) {
            const card = e.target.closest('.conversation-item');
            if (card && card.dataset.id) selectConversation(card.dataset.id);
        });
    }
    initializeSearch();
});

async function checkSession() {
    try {
        const res = await fetch((window.BASE_URL || '/') + 'student/session/check', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });
        if (!res.ok) return false;
        const data = await res.json().catch(() => ({ loggedin: false }));
        return !!(data.loggedin && data.user_id && data.role === 'student');
    } catch (e) {
        return false;
    }
}

function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        const cards = document.querySelectorAll('.conversation-item');
        isSearching = !!term;
        if (searchTimeout) clearTimeout(searchTimeout);
        if (isSearching) {
            if (messageUpdateInterval) clearInterval(messageUpdateInterval);
            searchTimeout = setTimeout(() => { isSearching = false; startMessagePolling(); }, 10000);
        }
        cards.forEach(card => {
            const name = card.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
            const last = card.querySelector('.conversation-last-message')?.textContent.toLowerCase() || '';
            card.style.display = (name.includes(term) || last.includes(term)) ? 'flex' : 'none';
        });
    });
}

async function initializeMessageInput() {
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    if (!messageInput || !sendButton) return;
    messageInput.disabled = true;
    messageInput.placeholder = 'Select a conversation to reply...';
    sendButton.disabled = true;
    initializeChatHeader();
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}

function initializeChatHeader() {
    const header = document.querySelector('.chat-user-info');
    if (!header) return;
    const nameEl = header.querySelector('.user-name');
    const statusEl = header.querySelector('.user-status');
    const avatar = header.querySelector('.user-avatar');
    if (nameEl) nameEl.textContent = 'Messages';
    if (statusEl) { statusEl.textContent = 'Select a conversation to start messaging'; statusEl.className = 'user-status'; }
    if (avatar) avatar.innerHTML = '<i class="fas fa-user"></i>';
}

async function startMessagePolling() {
    if (messageUpdateInterval) clearInterval(messageUpdateInterval);
    messageUpdateInterval = setInterval(async () => { 
        if (!isSearching && currentUserId) {
            await loadMessages(currentUserId, true); // CHANGED: Added silent parameter
        }
    }, 1500);
}

async function loadConversations() {
    const userList = document.querySelector('.conversations-list');
    if (!userList) return;
    if (!userList.querySelector('.conversation-item')) userList.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><span>Loading counselors...</span></div>';
    
    const res = await fetch((window.BASE_URL || '/') + 'student/message/operations?action=get_counselor_conversations', { credentials: 'include' });
    const data = await res.json();
    
    if (data.success && Array.isArray(data.counselors)) {
        updateConversations(data.counselors, false);
    } else {
        userList.innerHTML = '<div class="no-conversations"><i class="fas fa-user-md"></i><p>No counselors available</p></div>';
    }
}

function calculateOnlineStatus(lastActivity, lastLogin, logoutTime) {
    const activityTime = lastActivity ? new Date(lastActivity) : null;
    const loginTime = lastLogin ? new Date(lastLogin) : null;
    const logoutTimeDate = logoutTime ? new Date(logoutTime) : null;
    if (logoutTimeDate && activityTime && logoutTimeDate.getTime() === activityTime.getTime()) {
        return { status: 'offline', text: 'Offline', class: 'status-offline' };
    }
    let mostRecentTime = activityTime || loginTime;
    if (activityTime && loginTime) mostRecentTime = activityTime > loginTime ? activityTime : loginTime;
    if (!mostRecentTime) return { status: 'offline', text: 'Offline', class: 'status-offline' };
    const diffInMinutes = Math.floor((new Date() - mostRecentTime) / (1000 * 60));
    if (diffInMinutes <= 5) return { status: 'online', text: 'Online', class: 'status-online' };
    if (diffInMinutes <= 60) return { status: 'active', text: `Last active ${diffInMinutes}m ago`, class: 'status-active-recent' };
    return { status: 'offline', text: 'Offline', class: 'status-offline' };
}

function updateConversations(items, isCounselorList = false) {
    const list = document.querySelector('.conversations-list');
    if (!list) return;
    list.innerHTML = '';
    
    items.forEach(item => {
        const otherUserId = item.counselor_id;
        const otherName = item.name || 'Counselor';
        const avatarPath = item.profile_picture || 'Photos/profile.png';
        const otherAvatar = resolveImageUrl(avatarPath);
        const unread = parseInt(item.unread_count) || 0;
        
        // Format last message
        let last = '';
        if (item.last_message) {
            const prefix = item.last_message_type === 'sent' ? 'You: ' : '';
            last = prefix + item.last_message;
        } else {
            last = 'No messages yet';
        }
        
        const lastTime = item.last_message_time ? formatMessageTime(item.last_message_time) : '';
        const statusInfo = calculateOnlineStatus(item.last_activity, item.last_login, item.logout_time);
        
        const card = document.createElement('div');
        card.className = `conversation-item${otherUserId === currentUserId ? ' active' : ''}${unread > 0 ? ' has-unread' : ''}`;
        card.dataset.id = otherUserId;
        card.dataset.lastActivity = item.last_activity || '';
        card.dataset.lastLogin = item.last_login || '';
        card.dataset.logoutTime = item.logout_time || '';
        card.innerHTML = `
            <div class="conversation-avatar">
                <img src="${otherAvatar}" alt="avatar" style="width:46px;height:46px;border-radius:50%;object-fit:cover;"/>
            </div>
            <div class="conversation-details">
                <div class="conversation-name">${otherName}</div>
                <div class="conversation-last-message">${last.length>30? last.substring(0,27)+'...': last}</div>
                <div class="conversation-status ${statusInfo.class}">${statusInfo.text}</div>
            </div>
            <div class="conversation-meta">
                <div class="conversation-time">${lastTime}</div>
                ${unread > 0 ? `<span class="unread-badge">${unread}</span>` : ''}
            </div>`;
        list.appendChild(card);
    });
}

function selectConversation(userId) {
    pausePolling();
    if (!userId) return;
    
    // CHANGED: Reset timestamp when switching conversations
    lastMessageTimestamp = null;
    
    const container = document.getElementById('messages-container');
    if (container) container.innerHTML = `<div class="empty-state" id="empty-state"><i class=\"fas fa-inbox\"></i><h5>Loading Messages...</h5><p>Please wait while we load the conversation.</p></div>`;
    currentUserId = userId;
    document.querySelectorAll('.conversation-item').forEach(card => {
        card.classList.toggle('active', String(card.dataset.id) === String(userId));
    });
    const activeCard = document.querySelector(`.conversation-item[data-id="${userId}"]`);
    if (activeCard) {
        const userName = activeCard.querySelector('.conversation-name').textContent;
        const avatarImg = activeCard.querySelector('.conversation-avatar img')?.getAttribute('src');
        const chatHeader = document.querySelector('.chat-user-info');
        if (chatHeader) {
            const nameEl = chatHeader.querySelector('.user-name');
            const statusEl = chatHeader.querySelector('.user-status');
            const avatar = chatHeader.querySelector('.user-avatar');
            if (nameEl) nameEl.textContent = userName;
            if (statusEl) {
                const statusInfo = calculateOnlineStatus(activeCard.dataset.lastActivity, activeCard.dataset.lastLogin, activeCard.dataset.logoutTime);
                statusEl.textContent = statusInfo.text;
                statusEl.className = `user-status ${statusInfo.class}`;
            }
            if (avatar && avatarImg) avatar.innerHTML = `<img src="${avatarImg}" alt="avatar" style="width:50px;height:50px;border-radius:50%;object-fit:cover;"/>`;
        }
    } else {
        initializeChatHeader();
    }
    const input = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-button');
    if (input) { input.disabled = false; input.placeholder = 'Type your message...'; input.focus(); }
    if (sendBtn) sendBtn.disabled = false;
    
    // CHANGED: Force load messages for new conversation
    loadMessages(userId, false).then(() => { 
        markMessagesAsRead(userId); 
        startMessagePolling(); 
    });
}

// CHANGED: Added silent parameter to prevent scroll when polling
async function loadMessages(userId, silent = false) {
    if (!userId) return;
    const res = await fetch((window.BASE_URL || '/') + `student/message/operations?action=get_messages&user_id=${userId}`, { credentials: 'include' });
    const data = await res.json();
    if (data.success && Array.isArray(data.messages)) {
        const msgs = data.messages.filter(m => (m.sender_id === userId || m.receiver_id === userId));
        if (msgs.length > 0) {
            const latest = msgs[msgs.length - 1];
            const t = new Date(latest.created_at).getTime();
            
            // CHANGED: Always display if no timestamp, or if new messages exist
            if (!lastMessageTimestamp || t > lastMessageTimestamp) {
                lastMessageTimestamp = t;
                displayMessages(msgs, silent);
                if (!silent) {
                    markMessagesAsRead(userId);
                }
            }
        } else {
            displayMessages([], silent);
        }
    }
}

// CHANGED: Added silent parameter
function displayMessages(messages, silent = false) {
    const container = document.getElementById('messages-container');
    if (!container) return;

    // CHANGED: Store scroll position before updating
    const wasAtBottom = silent && (container.scrollHeight - container.scrollTop - container.clientHeight < 50);

    if (!Array.isArray(messages) || messages.length === 0) {
        container.innerHTML = `
            <div class="empty-chat" id="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h5>No Messages Yet</h5>
                <p>Start the conversation by sending a message.</p>
            </div>`;
        return;
    }

    let html = '<div class="p-3">';
    messages.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    messages.forEach(msg => {
        const isSent = msg.message_type === 'sent';
        const time = formatMessageTime(msg.created_at);
        html += `
            <div class="message ${isSent ? 'sent' : 'received'}">
                <div class="message-content">
                    <p class="message-text">${escapeHtml(msg.message_text || '')}</p>
                </div>
                <p class="message-time">${time}</p>
            </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
    
    // CHANGED: Only scroll if at bottom or not silent
    if (!silent || wasAtBottom) {
        setTimeout(() => { container.scrollTop = container.scrollHeight; }, 50);
    }
}

function markMessagesAsRead(userId) {
    if (!userId) return;
    fetch((window.BASE_URL || '/') + 'student/message/operations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=mark_read&user_id=${userId}`,
        credentials: 'include'
    });
}

async function sendMessage() {
    try {
        if (!currentUserId) return;
        const input = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-button');
        const text = (input?.value || '').trim();
        if (!text) return;
        
        input.disabled = true; 
        sendBtn.disabled = true;
        
        const res = await fetch((window.BASE_URL || '/') + 'student/message/operations?action=send_message', {
            method: 'POST', 
            credentials: 'include', 
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `receiver_id=${currentUserId}&message=${encodeURIComponent(text)}`
        });
        const data = await res.json();
        
        if (data.success) {
            input.value = '';
            input.style.height = 'auto';
            
            // CHANGED: Force refresh by resetting timestamp and loading messages
            lastMessageTimestamp = null;
            
            // Small delay to ensure database is updated
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // Reload messages from server after sending
            await loadMessages(currentUserId, false);
            
            // Also refresh the conversations list to update last message
            loadConversations(); // Don't await to avoid delay
        }
    } finally {
        const input = document.getElementById('message-input');
        const sendBtn = document.getElementById('send-button');
        if (input) { input.disabled = false; input.focus(); }
        if (sendBtn) sendBtn.disabled = false;
    }
}

function pausePolling() { if (messageUpdateInterval) clearInterval(messageUpdateInterval); }

function formatMessageTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp), now = new Date(), diff = now - date;
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return `${Math.floor(diff/60000)}m ago`;
    if (date.toDateString() === now.toDateString()) return date.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' });
    if (date.getFullYear() === now.getFullYear()) return date.toLocaleDateString('en-US', { month:'short', day:'numeric' });
    return date.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
}

function escapeHtml(text) {
    return String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}