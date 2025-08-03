<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectApp - Conversation Testing</title>
    <script src="https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: #007bff; color: white; padding: 15px; border-radius: 8px 8px 0 0; font-weight: bold; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; margin-right: 10px; margin-bottom: 10px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn:hover { opacity: 0.8; }
        .chat-container { display: flex; height: 500px; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .conversations-list { width: 300px; background: #f8f9fa; border-right: 1px solid #ddd; overflow-y: auto; }
        .chat-area { flex: 1; display: flex; flex-direction: column; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; background: white; }
        .chat-input { padding: 15px; border-top: 1px solid #ddd; background: #f8f9fa; }
        .message { margin-bottom: 15px; }
        .message.own { text-align: right; }
        .message-bubble { display: inline-block; padding: 10px 15px; border-radius: 18px; max-width: 70%; word-wrap: break-word; }
        .message.own .message-bubble { background: #007bff; color: white; }
        .message:not(.own) .message-bubble { background: #e9ecef; color: #333; }
        .message-info { font-size: 12px; color: #666; margin-top: 5px; }
        .conversation-item { padding: 15px; border-bottom: 1px solid #ddd; cursor: pointer; }
        .conversation-item:hover { background: #e9ecef; }
        .conversation-item.active { background: #007bff; color: white; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.info { background: #cce7ff; color: #004085; border: 1px solid #99d3ff; }
        .pusher-status { position: fixed; top: 20px; right: 20px; padding: 10px; border-radius: 4px; font-weight: bold; }
        .pusher-connected { background: #d4edda; color: #155724; }
        .pusher-disconnected { background: #f8d7da; color: #721c24; }
        .users-list { max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; }
        .user-item { padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
        .user-item:hover { background: #f8f9fa; }
        .message-input-container { display: flex; gap: 10px; }
        .message-input-container input { flex: 1; }
        .logs { max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div id="app" class="container">
        <h1>ConnectApp - Conversation Testing</h1>

        <!-- Instructions -->
        <div class="card">
            <div class="card-header">Testing Instructions</div>
            <div class="card-body">
                <ol style="margin-left: 20px; list-style: decimal;">
                    <li><strong>Login:</strong> Use test credentials (default: lawalthb@gmail.com / 12345678) or visit <a href="/test-users" target="_blank">test-users</a> for more options</li>
                    <li><strong>Create Conversation:</strong> Search for users and click to start a conversation</li>
                    <li><strong>Test Real-time:</strong> Open multiple browser tabs/windows with different users to test real-time chat</li>
                    <li><strong>Monitor Debug:</strong> Check the debug logs section for Pusher connection and message broadcasting details</li>
                    <li><strong>Pusher Dashboard:</strong> Check your Pusher dashboard for real-time activity monitoring</li>
                </ol>
                <p style="margin-top: 15px;"><strong>Note:</strong> This interface tests the full conversation module including API authentication, Pusher real-time broadcasting, and message persistence.</p>
            </div>
        </div>

        <!-- Pusher Status -->
        <div v-if="pusherConnected !== null" class="pusher-status" :class="pusherConnected ? 'pusher-connected' : 'pusher-disconnected'">
            Pusher: @{{ pusherConnected ? 'Connected' : 'Disconnected' }}
        </div>

        <!-- Login Section -->
        <div v-if="!isLoggedIn" class="card">
            <div class="card-header">Login</div>
            <div class="card-body">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" v-model="loginForm.email" class="form-control" placeholder="Enter email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" v-model="loginForm.password" class="form-control" placeholder="Enter password">
                </div>
                <button @click="login" class="btn btn-primary" :disabled="loading">
                    @{{ loading ? 'Logging in...' : 'Login' }}
                </button>
            </div>
        </div>

        <!-- Main Chat Interface -->
        <div v-if="isLoggedIn">
            <!-- User Info -->
            <div class="card">
                <div class="card-header">Welcome, @{{ currentUser.name }}!</div>
                <div class="card-body">
                    <p><strong>ID:</strong> @{{ currentUser.id }}</p>
                    <p><strong>Email:</strong> @{{ currentUser.email }}</p>
                    <button @click="logout" class="btn btn-danger">Logout</button>
                    <button @click="loadConversations" class="btn btn-primary">Refresh Conversations</button>
                </div>
            </div>

            <!-- Create Conversation -->
            <div class="card">
                <div class="card-header">Create New Conversation</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Search Users</label>
                        <input type="text" v-model="userSearch" @input="searchUsers" class="form-control" placeholder="Search users...">
                    </div>
                    <div v-if="searchedUsers.length > 0" class="users-list">
                        <div v-for="user in searchedUsers" :key="user.id" @click="createConversation(user)" class="user-item">
                            <strong>@{{ user.name }}</strong> (@{{ user.email }})
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Interface -->
            <div class="card">
                <div class="card-header">Chat Interface</div>
                <div class="card-body">
                    <div class="chat-container">
                        <!-- Conversations List -->
                        <div class="conversations-list">
                            <h4 style="padding: 15px; border-bottom: 1px solid #ddd;">Conversations</h4>
                            <div v-if="conversations.length === 0" style="padding: 15px; text-align: center; color: #666;">
                                No conversations yet
                            </div>
                            <div v-for="conversation in conversations"
                                 :key="conversation.id"
                                 @click="selectConversation(conversation)"
                                 class="conversation-item"
                                 :class="{ active: activeConversation && activeConversation.id === conversation.id }">
                                <div><strong>@{{ getConversationName(conversation) }}</strong></div>
                                <div style="font-size: 12px; color: #666;" v-if="conversation.latest_message">
                                    @{{ conversation.latest_message.message || 'File' }}
                                </div>
                            </div>
                        </div>

                        <!-- Chat Area -->
                        <div class="chat-area">
                            <div v-if="!activeConversation" style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                                Select a conversation to start chatting
                            </div>
                            <div v-else>
                                <!-- Chat Header -->
                                <div style="padding: 15px; border-bottom: 1px solid #ddd; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center;">
                                    <strong>@{{ getConversationName(activeConversation) }}</strong>
                                    <div>
                                        <button @click="refreshMessages" class="btn btn-success" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">
                                            Refresh Messages
                                        </button>
                                        <button @click="testAddMessage" class="btn btn-warning" style="padding: 5px 10px; font-size: 12px;">
                                            Test Add Message
                                        </button>
                                    </div>
                                </div>

                                <!-- Messages -->
                                <div class="chat-messages" ref="messagesContainer">
                                    <!-- Debug Info -->
                                    <div style="background: #f0f0f0; padding: 10px; margin-bottom: 10px; border-radius: 4px; font-size: 12px;">
                                        <strong>Debug:</strong> Messages count: @{{ messages.length }} | Active conversation: @{{ activeConversation?.id }}
                                    </div>

                                    <div v-for="message in messages" :key="message.id"
                                         class="message" :class="{ own: message.user_id === currentUser.id }">
                                        <div class="message-bubble">
                                            <div>@{{ message.message }}</div>
                                        </div>
                                        <div class="message-info">
                                            @{{ message.user.name }} • @{{ formatTime(message.created_at) }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Message Input -->
                                <div class="chat-input">
                                    <div class="message-input-container">
                                        <input type="text"
                                               v-model="newMessage"
                                               @keypress.enter="sendMessage"
                                               class="form-control"
                                               placeholder="Type a message...">
                                        <button @click="sendMessage" class="btn btn-primary" :disabled="!newMessage.trim()">
                                            Send
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debug Logs -->
            <div class="card">
                <div class="card-header">Debug Logs</div>
                <div class="card-body">
                    <button @click="clearLogs" class="btn btn-warning">Clear Logs</button>
                    <div class="logs">
                        <div v-for="(log, index) in logs" :key="index">
                            [@{{ log.time }}] @{{ log.message }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Messages -->
        <div v-if="statusMessage" class="status" :class="statusType">
            @{{ statusMessage }}
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    // Auth
                    isLoggedIn: false,
                    currentUser: null,
                    authToken: null,
                    loginForm: {
                        email: 'lawalthb@gmail.com', // Default for testing
                        password: '12345678'
                    },

                    // UI State
                    loading: false,
                    statusMessage: '',
                    statusType: 'info',
                    logs: [],

                    // Users & Search
                    userSearch: '',
                    searchedUsers: [],

                    // Conversations
                    conversations: [],
                    activeConversation: null,
                    messages: [],
                    newMessage: '',

                    // Pusher
                    pusher: null,
                    pusherConnected: null,
                    subscribedChannels: {}
                }
            },

            mounted() {
                this.log('Application mounted');
                this.checkExistingAuth();
            },

            methods: {
                log(message) {
                    const now = new Date();
                    this.logs.unshift({
                        time: now.toLocaleTimeString(),
                        message: message
                    });
                    console.log(`[${now.toLocaleTimeString()}] ${message}`);
                },

                clearLogs() {
                    this.logs = [];
                },

                showStatus(message, type = 'info') {
                    this.statusMessage = message;
                    this.statusType = type;
                    setTimeout(() => {
                        this.statusMessage = '';
                    }, 5000);
                },

                checkExistingAuth() {
                    const token = localStorage.getItem('auth_token');
                    const user = localStorage.getItem('current_user');

                    if (token && user) {
                        this.authToken = token;
                        this.currentUser = JSON.parse(user);
                        this.isLoggedIn = true;
                        this.setupAxios();
                        this.initializePusher();
                        this.loadConversations();
                        this.log('Restored existing authentication');
                    }
                },

                async login() {
                    this.loading = true;
                    this.log('Attempting login...');

                    try {
                        const response = await axios.post('/api/v1/login', {
                            email: this.loginForm.email,
                            password: this.loginForm.password
                        });

                        if (response.data.success) {
                            this.authToken = response.data.data.token;
                            this.currentUser = response.data.data.user;
                            this.isLoggedIn = true;

                            // Store in localStorage
                            localStorage.setItem('auth_token', this.authToken);
                            localStorage.setItem('current_user', JSON.stringify(this.currentUser));

                            this.setupAxios();
                            this.initializePusher();
                            this.loadConversations();

                            this.log(`Login successful for user: ${this.currentUser.name}`);
                            this.showStatus('Login successful!', 'success');
                        } else {
                            throw new Error(response.data.message || 'Login failed');
                        }
                    } catch (error) {
                        this.log(`Login failed: ${error.response?.data?.message || error.message}`);
                        this.showStatus(error.response?.data?.message || error.message, 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                logout() {
                    this.isLoggedIn = false;
                    this.currentUser = null;
                    this.authToken = null;
                    this.conversations = [];
                    this.messages = [];
                    this.activeConversation = null;

                    localStorage.removeItem('auth_token');
                    localStorage.removeItem('current_user');

                    if (this.pusher) {
                        this.pusher.disconnect();
                        this.pusher = null;
                        this.pusherConnected = null;
                    }

                    this.log('Logged out successfully');
                    this.showStatus('Logged out successfully', 'info');
                },

                setupAxios() {
                    axios.defaults.headers.common['Authorization'] = `Bearer ${this.authToken}`;
                    axios.defaults.headers.common['Accept'] = 'application/json';
                    axios.defaults.headers.common['Content-Type'] = 'application/json';
                },

                initializePusher() {
                    this.log('Initializing Pusher connection...');

                    this.pusher = new Pusher('{{ config("broadcasting.connections.pusher.key") }}', {
                        cluster: '{{ config("broadcasting.connections.pusher.options.cluster") }}',
                        encrypted: true,
                        authEndpoint: '/broadcasting/auth',
                        auth: {
                            headers: {
                                'Authorization': `Bearer ${this.authToken}`,
                                'Accept': 'application/json'
                            }
                        }
                    });

                    this.pusher.connection.bind('connected', () => {
                        this.pusherConnected = true;
                        this.log('Pusher connected successfully');
                    });

                    this.pusher.connection.bind('disconnected', () => {
                        this.pusherConnected = false;
                        this.log('Pusher disconnected');
                    });

                    this.pusher.connection.bind('error', (error) => {
                        this.pusherConnected = false;
                        this.log(`Pusher connection error: ${JSON.stringify(error)}`);
                    });
                },

                async searchUsers() {
                    if (this.userSearch.length < 2) {
                        this.searchedUsers = [];
                        return;
                    }

                    try {
                        const response = await axios.get(`/api/v1/search/users?q=${this.userSearch}`);
                        if (response.data.success) {
                            this.searchedUsers = response.data.data.users || [];
                            this.log(`Found ${this.searchedUsers.length} users`);
                        }
                    } catch (error) {
                        this.log(`User search failed: ${error.message}`);
                    }
                },

                async createConversation(user) {
                    this.log(`Creating conversation with user: ${user.name}`);

                    try {
                        const response = await axios.post('/api/v1/messages/send', {
                            recipient_id: user.id,
                            type: 'text',
                            message: `Hello ${user.name}! 👋`
                        });

                        if (response.data.success) {
                            this.log('Conversation created successfully');
                            this.showStatus('Conversation created!', 'success');
                            this.loadConversations();
                            this.userSearch = '';
                            this.searchedUsers = [];
                        }
                    } catch (error) {
                        this.log(`Failed to create conversation: ${error.response?.data?.message || error.message}`);
                        this.showStatus(error.response?.data?.message || error.message, 'error');
                    }
                },

                async loadConversations() {
                    this.log('Loading conversations...');

                    try {
                        const response = await axios.get('/api/v1/conversations');
                        if (response.data.success) {
                            this.conversations = response.data.data.conversations || [];
                            this.log(`Loaded ${this.conversations.length} conversations`);
                        }
                    } catch (error) {
                        this.log(`Failed to load conversations: ${error.message}`);
                    }
                },

                async selectConversation(conversation) {
                    this.log(`Selecting conversation: ${conversation.id}`);
                    this.activeConversation = conversation;

                    // Unsubscribe from previous channel
                    Object.keys(this.subscribedChannels).forEach(channelName => {
                        this.pusher.unsubscribe(channelName);
                        delete this.subscribedChannels[channelName];
                    });

                    // Subscribe to conversation channel
                    const channelName = `private-conversation.${conversation.id}`;
                    const channel = this.pusher.subscribe(channelName);
                    this.subscribedChannels[channelName] = channel;

                    channel.bind('pusher:subscription_succeeded', () => {
                        this.log(`Successfully subscribed to ${channelName}`);
                    });

                    channel.bind('pusher:subscription_error', (error) => {
                        this.log(`Failed to subscribe to ${channelName}: ${JSON.stringify(error)}`);
                    });

                    channel.bind('message.sent', (data) => {
                        this.log(`Received real-time message: ${JSON.stringify(data)}`);

                        // Check if we have the message data and it's for the current conversation
                        if (data.message && data.message.conversation_id == this.activeConversation.id) {
                            // Check if message already exists to prevent duplicates
                            const existingMessage = this.messages.find(msg => msg.id === data.message.id);
                            if (!existingMessage) {
                                // Use Vue's reactive array methods
                                this.messages = [...this.messages, data.message];
                                this.log(`Added new message to conversation ${data.message.conversation_id}`);

                                this.$nextTick(() => {
                                    this.scrollToBottom();
                                });
                            } else {
                                this.log(`Message ${data.message.id} already exists, skipping`);
                            }
                        } else {
                            this.log(`Message not for current conversation or missing data`);
                        }
                    });

                    // Load messages
                    await this.loadMessages(conversation.id);
                },

                async loadMessages(conversationId) {
                    this.log(`Loading messages for conversation: ${conversationId}`);

                    try {
                        const response = await axios.get(`/api/v1/conversations/${conversationId}/messages`);
                        if (response.data.success) {
                            this.messages = response.data.data.messages || [];
                            this.log(`Loaded ${this.messages.length} messages`);
                            this.$nextTick(() => {
                                this.scrollToBottom();
                            });
                        }
                    } catch (error) {
                        this.log(`Failed to load messages: ${error.message}`);
                    }
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || !this.activeConversation) return;

                    const message = this.newMessage.trim();
                    this.newMessage = '';

                    this.log(`Sending message: "${message}"`);

                    try {
                        const response = await axios.post(`/api/v1/conversations/${this.activeConversation.id}/messages`, {
                            type: 'text',
                            message: message
                        });

                        if (response.data.success) {
                            this.log('Message sent successfully');
                            // Message will be added via Pusher real-time event
                        }
                    } catch (error) {
                        this.log(`Failed to send message: ${error.response?.data?.message || error.message}`);
                        this.showStatus(error.response?.data?.message || error.message, 'error');
                        this.newMessage = message; // Restore message on error
                    }
                },

                getConversationName(conversation) {
                    if (conversation.type === 'group') {
                        return conversation.name || 'Group Chat';
                    }

                    const otherUser = conversation.users?.find(user => user.id !== this.currentUser.id);
                    return otherUser ? otherUser.name : 'Unknown User';
                },

                formatTime(timestamp) {
                    return new Date(timestamp).toLocaleTimeString();
                },

                scrollToBottom() {
                    if (this.$refs.messagesContainer) {
                        this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
                    }
                },

                async refreshMessages() {
                    if (!this.activeConversation) return;

                    this.log('Manually refreshing messages...');
                    await this.loadMessages(this.activeConversation.id);
                    this.showStatus('Messages refreshed!', 'success');
                },

                testAddMessage() {
                    if (!this.activeConversation) return;

                    const testMessage = {
                        id: Date.now(),
                        conversation_id: this.activeConversation.id,
                        user_id: 999,
                        message: 'Test message - ' + new Date().toLocaleTimeString(),
                        type: 'text',
                        created_at: new Date().toISOString(),
                        user: {
                            id: 999,
                            name: 'Test User',
                            profile_image: null
                        }
                    };

                    this.messages = [...this.messages, testMessage];
                    this.log('Added test message to check Vue reactivity');
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            }
        }).mount('#app');
    </script>
</body>
</html>
