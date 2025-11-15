// Global variables
let currentUserId = null;
let messageUpdateInterval = null;
const API_BASE_URL = window.BASE_URL || '/';
let isSearching = false;
let searchTimeout = null;
let lastMessageTimestamp = null;
let lastActiveConversation = null;
let lastGlobalMessageTimestamp = null;
let autoSelectUserId = null;

// Debug logging
SecureLogger.info('Counselor Message JS file loaded');

// Small helper to normalize image URLs
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

// Initialize mobile sidebar functionality
function initializeMobileSidebar() {
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const conversationsSidebar = document.getElementById('conversationsSidebar');
    const mobileSidebarOverlay = document.getElementById('mobileSidebarOverlay');
    
    if (!mobileSidebarToggle || !conversationsSidebar || !mobileSidebarOverlay) return;
    
    // Function to close sidebar
    function closeSidebar() {
        conversationsSidebar.classList.remove('active');
        mobileSidebarOverlay.classList.remove('active');
        mobileSidebarToggle.classList.remove('hidden');
        document.body.style.overflow = '';
    }
    
    // Function to open sidebar
    function openSidebar() {
        conversationsSidebar.classList.add('active');
        mobileSidebarOverlay.classList.add('active');
        mobileSidebarToggle.classList.add('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    // Toggle sidebar when button is clicked
    mobileSidebarToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (conversationsSidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    
    // Close sidebar when overlay is clicked
    mobileSidebarOverlay.addEventListener('click', function(e) {
        e.stopPropagation();
        closeSidebar();
    });
    
    // Close sidebar when a conversation is selected (mobile only)
    document.addEventListener('click', function(e) {
        const conversationItem = e.target.closest('.conversation-item');
        if (conversationItem && window.innerWidth <= 768) {
            setTimeout(() => {
                closeSidebar();
            }, 300);
        }
    });
    
    // Ensure sidebar is closed on initial load (mobile only)
    if (window.innerWidth <= 768) {
        closeSidebar();
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', async function() {
    SecureLogger.info('DOM Content Loaded');
    try {
        const isLoggedIn = await checkSession();
        if (isLoggedIn) {
            SecureLogger.info('Counselor is logged in, initializing...');
            
            // Initialize mobile sidebar
            initializeMobileSidebar();
            
            // Initialize message input first
            await initializeMessageInput();
            
            // Get the user ID from either URL parameter, localStorage, or highlighted conversation
            const urlParams = new URLSearchParams(window.location.search);
            const userIdFromUrl = urlParams.get('user');
            const userIdFromStorage = localStorage.getItem('selectedConversation');
            const highlightedUserId = localStorage.getItem('highlightConversation');
            const selectedUserId = userIdFromUrl || userIdFromStorage || highlightedUserId;
            
            SecureLogger.info('Selected user ID from URL:', userIdFromUrl);
            SecureLogger.info('Selected user ID from storage:', userIdFromStorage);
            SecureLogger.info('Highlighted user ID:', highlightedUserId);
            
            // Set the global autoSelectUserId
            autoSelectUserId = selectedUserId;
            SecureLogger.info('autoSelectUserId at DOMContentLoaded:', autoSelectUserId);
            // Load conversations
            await loadConversations();
            
            // Remove the stored conversation IDs
            localStorage.removeItem('selectedConversation');
            localStorage.removeItem('highlightConversation');
            // Remove the URL parameter without refreshing
            if (userIdFromUrl) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
            // Do NOT call selectConversation here anymore
        } else {
            console.error('Counselor is not logged in');
            window.location.href = (window.BASE_URL || '/') + 'auth/logout';
        }
    } catch (error) {
        console.error('Error during initialization:', error);
        window.location.href = (window.BASE_URL || '/') + 'auth/logout';
    }

    // Add event delegation for sidebar conversation selection
    const userList = document.querySelector('.conversations-list');
    if (userList) {
        userList.addEventListener('click', function(e) {
            const card = e.target.closest('.conversation-item');
            if (card && card.dataset.id) {
                selectConversation(card.dataset.id);
            }
        });
    }
    // Enable sidebar search
    initializeSearch();

    // Remove dashboard highlight flag when messages.php is opened
    localStorage.removeItem('dashboardMessageHighlight');

    // Auto-select conversation if coming from dashboard
    const selectedUserId = localStorage.getItem('selectedConversation');
    if (selectedUserId) {
        const interval = setInterval(() => {
            const card = document.querySelector(`.conversation-item[data-id='${selectedUserId}']`);
            if (card) {
                // Simulate click or call selectConversation
                if (typeof selectConversation === 'function') {
                    selectConversation(selectedUserId);
                } else {
                    card.click();
                }
                localStorage.removeItem('selectedConversation');
                clearInterval(interval);
            }
        }, 100);
    }
});

// Check session status
async function checkSession() {
    SecureLogger.info('Checking session...');
    try {
        const response = await fetch((window.BASE_URL || '/') + 'counselor/session/check', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        SecureLogger.info('Session check result:', data);

        if (!data.loggedin || !data.user_id || data.role !== 'counselor') {
            console.error('Session invalid:', data);
            return false;
        }

        return true;
    } catch (error) {
        console.error('Session check failed:', error);
        return false;
    }
}

// Initialize all functions first
function initializeMobileMenu() {
    if (!checkSession()) return;
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function () {
            mobileMenu.style.display = mobileMenu.style.display === 'block' ? 'none' : 'block';
        });
    }
}

function initializeStickyHeader() {
    const header = document.querySelector('header');
    const main = document.querySelector('main');
    if (header && main) {
        const headerHeight = header.offsetHeight;
        const placeholder = document.createElement('div');
        placeholder.style.height = headerHeight + 'px';
        placeholder.style.display = 'none';
        placeholder.id = 'header-placeholder';
        header.parentNode.insertBefore(placeholder, header.nextSibling);

        window.addEventListener('scroll', function () {
            if (window.pageYOffset > 10) {
                header.classList.add("sticky-header");
                placeholder.style.display = 'block';
            } else {
                header.classList.remove("sticky-header");
                placeholder.style.display = 'none';
            }
        });
    }
}

function initializeSidebar() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if (sidebarToggle && sidebar && overlay) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
}

function initializeSearch() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const cards = document.querySelectorAll('.conversation-item');
        
        isSearching = !!searchTerm;
        if (searchTimeout) clearTimeout(searchTimeout);
        // If searching, pause polling for 10 seconds after last input
        if (isSearching) {
            if (messageUpdateInterval) clearInterval(messageUpdateInterval);
            searchTimeout = setTimeout(() => {
                isSearching = false;
                startMessagePolling();
            }, 10000);
        }
        
        cards.forEach(card => {
            const name = card.querySelector('.conversation-name')?.textContent.toLowerCase() || '';
            const lastMessage = card.querySelector('.conversation-last-message')?.textContent.toLowerCase() || '';
            if (name.includes(searchTerm) || lastMessage.includes(searchTerm)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

function handleFileAttachment(event) {
    const file = event.target.files[0];
    if (file) {
        const attachmentPreview = document.createElement('div');
        attachmentPreview.className = 'attachment-preview';
        attachmentPreview.innerHTML = `
            <i class="fas fa-file"></i>
            <span>${file.name}</span>
            <button class="remove-attachment">×</button>
        `;

        const messageInputContainer = document.querySelector('.message-input-container');
        if (messageInputContainer) {
            messageInputContainer.appendChild(attachmentPreview);
            
            attachmentPreview.querySelector('.remove-attachment').addEventListener('click', () => {
                attachmentPreview.remove();
                event.target.value = '';
            });
        }
    }
}

// Initialize message input
async function initializeMessageInput() {
    SecureLogger.info('Initializing message input...');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const attachButton = document.getElementById('attachment-button');
    const emojiButton = document.getElementById('emoji-button');

    if (!messageInput || !sendButton) {
        console.error('Message input or send button not found');
        return;
    }

    // Initialize message input state
    messageInput.disabled = true;
    messageInput.placeholder = 'Select a conversation to reply...';
    sendButton.disabled = true;
    if (attachButton) attachButton.disabled = true;
    if (emojiButton) emojiButton.disabled = true;
    
    // Initialize chat header with default state
    initializeChatHeader();

    // Add event listeners
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Handle file attachments
    if (attachButton) {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.style.display = 'none';
        document.body.appendChild(fileInput);

        attachButton.addEventListener('click', () => {
            if (!attachButton.disabled) {
                fileInput.click();
            }
        });
        fileInput.addEventListener('change', handleFileAttachment);
    }

    // Add auto-resize for textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    SecureLogger.info('Message input initialized');
}

// Initialize chat header with default state
function initializeChatHeader() {
    const chatHeader = document.querySelector('.chat-user-info');
    if (chatHeader) {
        const userNameElement = chatHeader.querySelector('.user-name');
        const userStatusElement = chatHeader.querySelector('.user-status');
        const headerAvatar = chatHeader.querySelector('.user-avatar');
        
        if (userNameElement) userNameElement.textContent = 'Messages';
        if (userStatusElement) {
            userStatusElement.textContent = 'Select a conversation to start messaging';
            userStatusElement.className = 'user-status';
        }
        if (headerAvatar) {
            headerAvatar.innerHTML = '<i class="fas fa-user"></i>';
        }
    }
}

// Start message polling
async function startMessagePolling() {
    SecureLogger.info('Starting message polling...');
    // Keep the conversations list static during message polling
    if (messageUpdateInterval) clearInterval(messageUpdateInterval);
    messageUpdateInterval = setInterval(async () => {
        if (!isSearching && currentUserId) {
            await loadMessages(currentUserId);
        }
        // Update status indicators every 30 seconds
        updateStatusIndicators();
    }, 1500);
}

// Update status indicators for all conversations
function updateStatusIndicators() {
    const conversationItems = document.querySelectorAll('.conversation-item');
    conversationItems.forEach(item => {
        const lastActivity = item.dataset.lastActivity;
        const lastLogin = item.dataset.lastLogin;
        const statusElement = item.querySelector('.conversation-status');
        if (statusElement && (lastActivity || lastLogin)) {
            const statusInfo = calculateOnlineStatus(lastActivity, lastLogin, item.dataset.logoutTime);
            statusElement.textContent = statusInfo.text;
            statusElement.className = `conversation-status ${statusInfo.class}`;
        }
    });
    
    // Update chat header status if a conversation is selected
    if (currentUserId) {
        const activeCard = document.querySelector(`.conversation-item[data-id="${currentUserId}"]`);
        if (activeCard) {
            const userStatusElement = document.querySelector('.user-status');
            if (userStatusElement) {
                const lastActivity = activeCard.dataset.lastActivity;
                const lastLogin = activeCard.dataset.lastLogin;
                const statusInfo = calculateOnlineStatus(lastActivity, lastLogin, activeCard.dataset.logoutTime);
                userStatusElement.textContent = statusInfo.text;
                userStatusElement.className = `user-status ${statusInfo.class}`;
            }
        }
    }
}

function highlightConversation(userId) {
    const card = document.querySelector(`.conversation-item[data-id="${userId}"]`);
    if (card) {
        // Add a temporary highlight class
        card.classList.add('highlight-new-message');
        
        // Remove the class after animation completes
        setTimeout(() => {
            card.classList.remove('highlight-new-message');
        }, 2000);
    }
}

// Load conversations
async function loadConversations() {
    try {
        const isLoggedIn = await checkSession();
        if (!isLoggedIn) {
            window.location.href = (window.BASE_URL || '/') + 'auth/logout';
            return;
        }

        const userList = document.querySelector('.conversations-list');
        if (!userList) return;

        // Only show loading state if there are no conversations yet
        if (!userList.querySelector('.conversation-item')) {
            userList.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><span>Loading conversations...</span></div>';
        }

        const response = await fetch((window.BASE_URL || '/') + 'counselor/message/operations?action=get_conversations', {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load conversations');
        }

        const data = await response.json();
        if (data.success) {
            // Check for new messages and auto-select the newest conversation if needed
            if (Array.isArray(data.conversations) && data.conversations.length > 0) {
                // Remove loading state if present
                const loading = userList.querySelector('.loading-state');
                if (loading) loading.remove();
                // First, update the conversations list
                updateConversations(data.conversations);
                
                // Find the conversation with the newest message
                let newestConversation = null;
                let newestTimestamp = lastGlobalMessageTimestamp || 0;
                
                data.conversations.forEach(conv => {
                    if (conv.last_message_time) {
                        const msgTime = new Date(conv.last_message_time).getTime();
                        if (msgTime > newestTimestamp && parseInt(conv.unread_count) > 0) {
                            newestTimestamp = msgTime;
                            newestConversation = conv;
                        }
                    }
                });
                
                // If we found a newer message than what we've seen before
                if (newestConversation && newestTimestamp > lastGlobalMessageTimestamp) {
                    SecureLogger.info('New message detected in conversation:', newestConversation.user_id);
                    lastGlobalMessageTimestamp = newestTimestamp;
                    
                    // If no conversation is selected yet or auto-switching is allowed, switch to this one
                    if (!currentUserId || newestConversation.user_id === currentUserId) {
                        selectConversation(newestConversation.user_id);
                    } else {
                        // Make the card flash or indicate new message without switching
                        highlightConversation(newestConversation.user_id);
                    }
                }
            } else {
                throw new Error(data.message || 'Failed to load conversations');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        const userList = document.querySelector('.user-list');
        if (userList && !userList.querySelector('.student-card')) {
            userList.innerHTML = `
                <div class="error-message">
                    ${error.message}
                </div>`;
        }
    }
}

function updateConversations(conversations) {
        const userList = document.querySelector('.conversations-list');
        if (!userList) return;

        if (!Array.isArray(conversations) || conversations.length === 0) {
            if (!userList.querySelector('.no-conversations')) {
                userList.innerHTML = `
                <div class="no-conversations">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                </div>`;
            }
            return;
        }

    // Create a map of existing conversations
    const existingCards = new Map();
    userList.querySelectorAll('.conversation-item').forEach(card => {
        existingCards.set(card.dataset.id, card);
    });

    // Update or create cards
    conversations.forEach(conv => {
        // Backend returns other_user_id, other_username, other_profile_picture
        const otherUserId = conv.other_user_id || conv.user_id;
        const otherUserName = conv.other_username || conv.name || 'Unknown';
        const otherAvatar = resolveImageUrl(conv.other_profile_picture || 'Photos/profile.png');
        const unreadCount = parseInt(conv.unread_count) || 0;
        const lastMessage = conv.last_message || 'No messages yet';
        const lastMessageTime = conv.last_message_time ? formatMessageTime(conv.last_message_time) : '';
        const lastMessageType = conv.last_message_type || 'received';
        
        // Format last message based on who sent it
        let formattedLastMessage = lastMessage;
        if (lastMessageType === 'sent') {
            formattedLastMessage = `You: ${lastMessage}`;
        } else if (lastMessageType === 'received') {
            formattedLastMessage = `Sent a Message: ${lastMessage}`;
        }
        
        // Truncate last message for sidebar preview
        let truncatedLastMessage = formattedLastMessage;
        const maxPreviewLength = 20;
        if (truncatedLastMessage.length > maxPreviewLength) {
            truncatedLastMessage = truncatedLastMessage.substring(0, maxPreviewLength - 3) + '...';
        }
        // Calculate online status
        const statusInfo = calculateOnlineStatus(conv.last_activity, conv.last_login, conv.logout_time);
        
        const cardHtml = `
            <div class="conversation-item ${otherUserId === currentUserId ? 'active' : ''}" 
                 data-id="${otherUserId}" data-last-activity="${conv.last_activity || ''}" data-last-login="${conv.last_login || ''}" data-logout-time="${conv.logout_time || ''}">
                <div class="conversation-avatar">
                    <img src="${otherAvatar}" alt="avatar" style="width:46px;height:46px;border-radius:50%;object-fit:cover;"/>
                </div>
                <div class="conversation-details">
                    <div class="conversation-name">${otherUserName}</div>
                    <div class="conversation-last-message">${truncatedLastMessage}</div>
                    <div class="conversation-status ${statusInfo.class}">${statusInfo.text}</div>
                </div>
                <div class="conversation-meta">
                    <div class="conversation-time">${lastMessageTime}</div>
                    ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ''}
                </div>
            </div>
        `;

        const existingCard = existingCards.get(String(otherUserId));
        if (existingCard) {
            // Update existing card content
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = cardHtml;
            const newCard = tempDiv.firstElementChild;
            
            // Update only the content that changed
            const existingName = existingCard.querySelector('.conversation-name');
            const existingMessage = existingCard.querySelector('.conversation-last-message');
            const existingTime = existingCard.querySelector('.conversation-time');
            const existingBadge = existingCard.querySelector('.unread-badge');
            const existingStatus = existingCard.querySelector('.conversation-status');
            
            const newName = newCard.querySelector('.conversation-name');
            const newMessage = newCard.querySelector('.conversation-last-message');
            const newTime = newCard.querySelector('.conversation-time');
            const newBadge = newCard.querySelector('.unread-badge');
            const newStatus = newCard.querySelector('.conversation-status');
            
            if (existingName && newName && existingName.textContent !== newName.textContent) {
                existingName.textContent = newName.textContent;
            }
            if (existingMessage && newMessage && existingMessage.textContent !== newMessage.textContent) {
                existingMessage.textContent = newMessage.textContent;
            }
            if (existingTime && newTime && existingTime.textContent !== newTime.textContent) {
                existingTime.textContent = newTime.textContent;
            }
            
            // Update status
            if (existingStatus && newStatus) {
                existingStatus.textContent = newStatus.textContent;
                existingStatus.className = newStatus.className;
            } else if (newStatus) {
                // Add status element if it doesn't exist
                const conversationDetails = existingCard.querySelector('.conversation-details');
                if (conversationDetails) {
                    conversationDetails.appendChild(newStatus);
                }
            }
            
            // Update badge
            if (existingBadge) {
                if (newBadge) {
                    existingBadge.textContent = newBadge.textContent;
                } else {
                    existingBadge.remove();
                }
            } else if (newBadge) {
                existingCard.querySelector('.conversation-meta').appendChild(newBadge);
            }
            
            // Update active state
            if (otherUserId === currentUserId) {
                existingCard.classList.add('active');
            } else {
                existingCard.classList.remove('active');
            }
            
            // Update dataset
            existingCard.dataset.lastActivity = conv.last_activity || '';
            existingCard.dataset.lastLogin = conv.last_login || '';
            
            existingCards.delete(conv.user_id);
        } else {
            // Add new card
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = cardHtml;
            userList.appendChild(tempDiv.firstElementChild);
        }
    });

    // Remove cards that no longer exist
    existingCards.forEach(card => card.remove());

    // Highlight conversation card if coming from dashboard
    const highlightId = localStorage.getItem('highlightStudentCard');
    if (highlightId) {
        setTimeout(() => {
            const card = document.querySelector(`.conversation-item[data-id='${highlightId}']`);
            SecureLogger.info('Highlighting card for userId:', highlightId, 'Found:', !!card);
            if (card) {
                card.classList.add('highlight-student-card');
                setTimeout(() => card.classList.remove('highlight-student-card'), 2000);
            }
            localStorage.removeItem('highlightStudentCard');
        }, 300);
    }

    // Auto-select conversation if needed
    SecureLogger.info('autoSelectUserId at updateConversations:', autoSelectUserId);
    if (autoSelectUserId) {
        selectConversation(autoSelectUserId);
        autoSelectUserId = null;
    }
}

function pausePolling() {
    if (messageUpdateInterval) clearInterval(messageUpdateInterval);
}

function resumePolling(delay = 1) {
    setTimeout(() => {
        startMessagePolling();
    }, delay);
}

function selectConversation(userId) {
    pausePolling();
    SecureLogger.info('Selecting conversation:', userId);
    if (!userId) return;
    
    // Store the last active conversation
    lastActiveConversation = currentUserId;
    
    // Reset last message timestamp when switching conversations
    lastMessageTimestamp = null;
    
    // Clear existing messages
    const messagesContainer = document.getElementById('messages-container');
    if (messagesContainer) {
        messagesContainer.innerHTML = `
            <div class="empty-state" id="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>Loading Messages...</h5>
                <p>Please wait while we load the conversation.</p>
            </div>
        `;
    }

    currentUserId = userId;

    // Update active state in the conversation list
    document.querySelectorAll('.conversation-item').forEach(card => {
        card.classList.remove('active');
        // Ensure string comparison
        if (String(card.getAttribute('data-id')) === String(userId)) {
            card.classList.add('active');
            // Add highlight effect
            card.classList.add('highlight-new-message');
            setTimeout(() => {
                card.classList.remove('highlight-new-message');
            }, 2000);
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    // Update the chat header with user information
    const activeCard = document.querySelector(`.conversation-item[data-id="${userId}"]`);
    if (activeCard) {
        const userName = activeCard.querySelector('.conversation-name').textContent;
        const avatarImg = activeCard.querySelector('.conversation-avatar img')?.getAttribute('src');
        const chatHeader = document.querySelector('.chat-user-info');
        if (chatHeader) {
            const userNameElement = chatHeader.querySelector('.user-name');
            const userStatusElement = chatHeader.querySelector('.user-status');
            const headerAvatar = chatHeader.querySelector('.user-avatar');
            if (userNameElement) userNameElement.textContent = userName;
            if (userStatusElement) {
                // Get the last_activity, last_login, and logout_time from the conversation data
                const lastActivity = activeCard.dataset.lastActivity;
                const lastLogin = activeCard.dataset.lastLogin;
                const logoutTime = activeCard.dataset.logoutTime;
                const statusInfo = calculateOnlineStatus(lastActivity, lastLogin, logoutTime);
                userStatusElement.textContent = statusInfo.text;
                userStatusElement.className = `user-status ${statusInfo.class}`;
            }
            if (headerAvatar && avatarImg) {
                headerAvatar.innerHTML = `<img src="${avatarImg}" alt="avatar" style="width:50px;height:50px;border-radius:50%;object-fit:cover;"/>`;
            }
        }
    } else {
        // Reset chat header when no conversation is selected
        const chatHeader = document.querySelector('.chat-user-info');
        if (chatHeader) {
            const userNameElement = chatHeader.querySelector('.user-name');
            const userStatusElement = chatHeader.querySelector('.user-status');
            const headerAvatar = chatHeader.querySelector('.user-avatar');
            if (userNameElement) userNameElement.textContent = 'Messages';
            if (userStatusElement) {
                userStatusElement.textContent = 'Select a conversation to start messaging';
                userStatusElement.className = 'user-status';
            }
            if (headerAvatar) {
                headerAvatar.innerHTML = '<i class="fas fa-user"></i>';
            }
        }
    }

    // Enable message input
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const attachButton = document.getElementById('attachment-button');
    const emojiButton = document.getElementById('emoji-button');

    if (messageInput) {
        messageInput.disabled = false;
        messageInput.placeholder = 'Type your message...';
        messageInput.focus();
    }
    if (sendButton) sendButton.disabled = false;
    if (attachButton) attachButton.disabled = false;
    if (emojiButton) emojiButton.disabled = false;

    // Load messages for the selected user
    loadMessages(userId).then(() => {
        // After messages are loaded, mark them as read
        markMessagesAsRead(userId);
        // Resume polling
        resumePolling();
    });
}

async function loadMessages(userId) {
    try {
        const isLoggedIn = await checkSession();
        if (!isLoggedIn) return;

        if (!userId) {
            console.error('No user ID provided for loading messages');
            return;
        }

        SecureLogger.info('Loading messages for user:', userId);

        const response = await fetch((window.BASE_URL || '/') + `counselor/message/operations?action=get_messages&user_id=${userId}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        SecureLogger.info('Received messages data:', data);

        if (data.success && Array.isArray(data.messages)) {
            // Filter messages for the current conversation
            const conversationMessages = data.messages.filter(msg => {
                const isCurrentConversation = (msg.sender_id === userId || msg.receiver_id === userId);
                SecureLogger.info('Message:', msg, 'Is current conversation:', isCurrentConversation);
                return isCurrentConversation;
            });
            
            // Check if there are new messages
            if (conversationMessages.length > 0) {
                const latestMessage = conversationMessages[conversationMessages.length - 1];
                const messageTime = new Date(latestMessage.created_at).getTime();
                
                // Always update display if it's the first load or if there are new messages
                if (!lastMessageTimestamp || messageTime > lastMessageTimestamp) {
                    SecureLogger.info('Updating messages display with:', conversationMessages);
                    lastMessageTimestamp = messageTime;
                    displayMessages(conversationMessages);
                    markMessagesAsRead(userId);
                }
            } else {
                // If no messages, still update display to show empty state
                displayMessages([]);
            }
        } else {
            throw new Error(data.message || 'Failed to load messages');
        }
    } catch (error) {
        console.error('Error:', error);
        showErrorMessage(error.message);
    }
}

function displayMessages(messages) {
    const messagesContainer = document.getElementById('messages-container');
    const emptyState = document.getElementById('empty-state');
    
    if (!messagesContainer || !emptyState) return;
    
    SecureLogger.info('Displaying messages:', messages);
    
    if (!Array.isArray(messages) || messages.length === 0) {
        emptyState.style.display = 'flex';
        messagesContainer.innerHTML = `
            <div class="empty-state" id="empty-state">
                <i class="fas fa-inbox"></i>
                <h5>No Messages Yet</h5>
                <p>Start the conversation by sending a message.</p>
            </div>
        `;
        return;
    }

    emptyState.style.display = 'none';
    let html = '<div class="p-3">';
    
    // Sort messages by timestamp to ensure correct order
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
            </div>
        `;
    });

    html += '</div>';
    
    // Update the messages container
    messagesContainer.innerHTML = html;
    
    // Scroll to bottom after messages are loaded
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
}

function markMessagesAsRead(userId) {
    if (!userId) return;
    
    fetch((window.BASE_URL || '/') + 'counselor/message/operations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=mark_read&user_id=${userId}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to mark messages as read:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking messages as read:', error);
    });
}

async function sendMessage() {
    pausePolling();
    try {
        const isLoggedIn = await checkSession();
        if (!isLoggedIn) {
            window.location.href = `${API_BASE_URL}/Landing_Page.html`;
            return;
        }
        if (!currentUserId) {
            showErrorMessage('Please select a conversation first');
            return;
        }
        const messageInput = document.getElementById('message-input');
        if (!messageInput || !messageInput.value.trim()) {
            showErrorMessage('Please enter a message');
            return;
        }
        const messageText = messageInput.value.trim();
        const sendButton = document.getElementById('send-button');
        messageInput.disabled = true;
        sendButton.disabled = true;

        const response = await fetch((window.BASE_URL || '/') + 'counselor/message/operations?action=send_message', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: `receiver_id=${currentUserId}&message=${encodeURIComponent(messageText)}`
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json();
        if (data.success) {
            // Clear input and reset height
            messageInput.value = '';
            messageInput.style.height = 'auto';

            // Create and append the new message immediately
            const messagesContainer = document.getElementById('messages-container');
            const newMessageHtml = `
                <div class="d-flex justify-content-end mb-3">
                    <div class="message sent">
                        <div class="message-content bg-primary text-white rounded-3 p-2 px-3 shadow-sm">
                            <p class="mb-1">${messageText}</p>
                        </div>
                        <small class="text-white-50 message-time">Just now</small>
                    </div>
                </div>
            `;
            
            // Remove empty state if it exists
            const emptyState = document.getElementById('empty-state');
            if (emptyState) {
                emptyState.style.display = 'none';
            }

            // Append the new message
            if (messagesContainer.innerHTML.includes('empty-state')) {
                messagesContainer.innerHTML = '<div class="p-3">' + newMessageHtml + '</div>';
            } else {
                const messageWrapper = messagesContainer.querySelector('.p-3');
                if (messageWrapper) {
                    messageWrapper.insertAdjacentHTML('beforeend', newMessageHtml);
                }
            }

            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;

            // Update conversation list in the background
            loadConversations();
            
            // Resume polling after a short delay
            resumePolling(1000);
        } else {
            throw new Error(data.message || 'Failed to send message');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        showErrorMessage(error.message);
        resumePolling();
    } finally {
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        if (messageInput) {
            messageInput.disabled = false;
            messageInput.focus();
        }
        if (sendButton) {
            sendButton.disabled = false;
        }
    }
}

function formatMessageTime(timestamp) {
    if (!timestamp) return '';
    
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    // If less than a minute ago
    if (diff < 60000) {
        return 'Just now';
    }
    
    // If less than an hour ago
    if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000);
        return `${minutes}m ago`;
    }
    
    // If today
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }
    
    // If this year
    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    
    // Otherwise show full date
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function showErrorMessage(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 3000);
}

// Calculate online status based on last_activity, last_login, and logout_time
function calculateOnlineStatus(lastActivity, lastLogin, logoutTime) {
    // Get all available times
    const activityTime = lastActivity ? new Date(lastActivity) : null;
    const loginTime = lastLogin ? new Date(lastLogin) : null;
    const logoutTimeDate = logoutTime ? new Date(logoutTime) : null;
    
    // Check if logout_time equals last_activity (exact match)
    if (logoutTimeDate && activityTime && logoutTimeDate.getTime() === activityTime.getTime()) {
        return {
            status: 'offline',
            text: 'Offline',
            class: 'status-offline'
        };
    }
    
    // Find the most recent time between last_activity and last_login
    let mostRecentTime = null;
    
    if (activityTime && loginTime) {
        // Use the more recent of the two
        mostRecentTime = activityTime > loginTime ? activityTime : loginTime;
    } else if (activityTime) {
        mostRecentTime = activityTime;
    } else if (loginTime) {
        mostRecentTime = loginTime;
    }
    
    if (!mostRecentTime) {
        return {
            status: 'offline',
            text: 'Offline',
            class: 'status-offline'
        };
    }

    const now = new Date();
    const diffInMinutes = Math.floor((now - mostRecentTime) / (1000 * 60));

    if (diffInMinutes <= 5) {
        return {
            status: 'online',
            text: 'Online',
            class: 'status-online'
        };
    } else if (diffInMinutes <= 60) {
        return {
            status: 'active',
            text: `Last active ${diffInMinutes}m ago`,
            class: 'status-active-recent'
        };
    } else {
        return {
            status: 'offline',
            text: 'Offline',
            class: 'status-offline'
        };
    }
}

function getStatusHighlightClass(conv) {
    if (/^online$/i.test(conv.status_text)) {
        return 'status-online'; // green
    }
    if (/^active \d+ (minute|hour)s? ago$/i.test(conv.status_text)) {
        return 'status-active-recent'; // yellow
    }
    return 'status-offline'; // gray
}

// Basic HTML escaper for message content
function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}