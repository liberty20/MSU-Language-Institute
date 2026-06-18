<template>
    <Head title="Internal Messages" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <span class="text-white font-extrabold">Internal Messaging System</span>
            </div>
        </template>

        <div class="h-[calc(100vh-12rem)] min-h-[500px] flex rounded-3xl overflow-hidden bg-white border border-gray-150 shadow-xl animate-scaleUp">
            
            <!-- Contacts Sidebar (Left Pane) -->
            <div class="w-full md:w-80 flex flex-col border-r border-gray-150 bg-gray-50 flex-shrink-0" :class="{'hidden md:flex': activeContact}">
                <div class="p-4 border-b border-gray-150 bg-white">
                    <h3 class="text-xs font-black text-brand-blue uppercase tracking-wider mb-3">Conversations</h3>
                    <!-- Search contacts -->
                    <div class="relative">
                        <input v-model="searchQuery" type="text" placeholder="Search contacts..." 
                               class="w-full text-xs rounded-xl border-gray-200 pl-8 pr-3 py-2.5 focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] placeholder-gray-400 font-medium" />
                        <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- Contacts List -->
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100/50">
                    <button v-for="contact in filteredContacts" :key="contact.id" 
                            @click="selectContact(contact)" 
                            class="w-full p-4 flex gap-3 text-left transition hover:bg-gray-100/60 relative focus:outline-none"
                            :class="{'bg-[#0a1f44]/[0.03] font-bold border-l-4 border-[#0a1f44]': activeContact?.id === contact.id, 'border-l-4 border-transparent': activeContact?.id !== contact.id}">
                        
                        <!-- Contact Avatar -->
                        <div class="w-10 h-10 rounded-full bg-[#0a1f44]/5 text-[#0a1f44] font-black text-sm flex items-center justify-center border border-[#0a1f44]/15 flex-shrink-0 uppercase relative">
                            {{ contact.name.charAt(0) }}
                            <span v-if="contact.unread_count > 0" class="absolute -top-1 -right-1 bg-amber-500 text-[#0a1f44] font-extrabold text-[9px] px-1.5 py-0.5 rounded-full border border-white">
                                {{ contact.unread_count }}
                            </span>
                        </div>

                        <!-- Name and last message preview -->
                        <div class="flex-grow min-w-0">
                            <div class="flex justify-between items-baseline mb-0.5">
                                <h4 class="text-xs font-black text-gray-800 truncate">{{ contact.name }}</h4>
                                <span v-if="contact.last_message" class="text-[9px] text-gray-400 font-semibold">{{ formatTime(contact.last_message.created_at) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[9px] font-extrabold text-gray-400 uppercase tracking-wider">{{ contact.role.replace('_', ' ') }}</span>
                                <span v-if="contact.last_message" class="text-[10px] text-gray-500 truncate max-w-[140px] font-medium">{{ contact.last_message.message }}</span>
                            </div>
                        </div>
                    </button>

                    <div v-if="filteredContacts.length === 0" class="p-8 text-center text-xs text-gray-400 italic py-16">
                        No contacts found.
                    </div>
                </div>
            </div>

            <!-- Messages Stream (Right Pane) -->
            <div class="flex-1 flex flex-col bg-white" :class="{'hidden': !activeContact, 'flex': activeContact}">
                <template v-if="activeContact">
                    <!-- Active Chat Header -->
                    <div class="px-6 py-4 border-b border-gray-150 flex items-center justify-between bg-gray-50">
                        <div class="flex items-center gap-3">
                            <!-- Back Button for Mobile -->
                            <button @click="backToContacts" class="md:hidden text-gray-500 hover:text-brand-blue mr-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>

                            <div class="w-10 h-10 rounded-full bg-[#0a1f44] text-brand-gold font-black text-sm flex items-center justify-center shadow-sm uppercase">
                                {{ activeContact.name.charAt(0) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-gray-800 leading-tight">{{ activeContact.name }}</h4>
                                <span class="text-[10px] font-extrabold text-brand-gold-dark uppercase tracking-wider">{{ activeContact.role.replace('_', ' ') }} &bull; {{ activeContact.email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Message Feed -->
                    <div ref="messageContainer" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50">
                        <div v-for="msg in messages" :key="msg.id" class="flex flex-col" :class="msg.sender_id === $page.props.auth.user.id ? 'items-end' : 'items-start'">
                            
                            <!-- Bubble -->
                            <div class="max-w-[70%] rounded-2xl px-4 py-3 shadow-sm relative text-xs" 
                                 :class="msg.sender_id === $page.props.auth.user.id ? 'bg-[#0a1f44] text-white rounded-tr-none' : 'bg-white text-gray-800 border border-gray-150 rounded-tl-none'">
                                <p class="leading-relaxed whitespace-pre-line break-words">{{ msg.message }}</p>
                            </div>

                            <!-- Metadata -->
                            <div class="flex items-center gap-1.5 mt-1 text-[9px] text-gray-400 font-semibold" :class="msg.sender_id === $page.props.auth.user.id ? 'mr-1' : 'ml-1'">
                                <span>{{ formatDateTime(msg.created_at) }}</span>
                                <span v-if="msg.sender_id === $page.props.auth.user.id">
                                    &bull; {{ msg.read_at ? 'Read' : 'Delivered' }}
                                </span>
                            </div>
                        </div>

                        <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center text-xs text-gray-400 italic py-16">
                            Start of conversation. Type your message below.
                        </div>
                    </div>

                    <!-- Input Box -->
                    <form @submit.prevent="sendChatMessage" class="p-4 border-t border-gray-150 bg-white flex gap-3">
                        <textarea v-model="newMessage" @keydown.enter.prevent="handleEnter" placeholder="Type a message..." rows="1"
                                  class="flex-1 text-xs rounded-xl border-gray-200 focus:border-[#f5c242] focus:ring-1 focus:ring-[#0a1f44] py-3 px-4 resize-none min-h-[42px] max-h-32 font-medium" required></textarea>
                        <button type="submit" class="bg-[#0a1f44] hover:bg-[#143264] text-white p-3 rounded-xl transition flex-shrink-0 flex items-center justify-center shadow-md">
                            <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        </button>
                    </form>
                </template>
                <template v-else>
                    <div class="flex-grow flex flex-col items-center justify-center text-center p-8 bg-gray-50/20">
                        <svg class="w-16 h-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <h4 class="text-sm font-black text-gray-600 uppercase tracking-wide">Select a Conversation</h4>
                        <p class="text-xs text-gray-400 mt-1 max-w-sm leading-relaxed">Choose a contact from the list on the left to start a secure internal conversation.</p>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    contacts: Array,
});

const searchQuery = ref('');
const activeContact = ref(null);
const messages = ref([]);
const newMessage = ref('');
const messageContainer = ref(null);

let pollInterval = null;

// Filter contacts based on search query
const filteredContacts = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.contacts;
    }
    const query = searchQuery.value.toLowerCase();
    return props.contacts.filter(c => 
        c.name.toLowerCase().includes(query) || 
        c.email.toLowerCase().includes(query) ||
        c.role.toLowerCase().includes(query)
    );
});

// Select contact to load messages
const selectContact = (contact) => {
    activeContact.value = contact;
    fetchMessages(contact.id);
    markAsRead(contact.id);

    // Clear previous polling
    if (pollInterval) {
        clearInterval(pollInterval);
    }

    // Start polling messages every 5 seconds
    pollInterval = setInterval(() => {
        fetchMessages(contact.id, false);
    }, 5000);
};

const backToContacts = () => {
    activeContact.value = null;
    if (pollInterval) {
        clearInterval(pollInterval);
    }
};

const fetchMessages = (contactId, shouldScroll = true) => {
    axios.get(route('chat.messages', contactId)).then(res => {
        messages.value = res.data.messages;
        if (shouldScroll) {
            scrollToBottom();
        }
    }).catch(err => console.error(err));
};

const markAsRead = (contactId) => {
    axios.post(route('chat.read', contactId)).then(() => {
        // Reset contact unread count locally
        const contact = props.contacts.find(c => c.id === contactId);
        if (contact) {
            contact.unread_count = 0;
        }
    }).catch(err => console.error(err));
};

const sendChatMessage = () => {
    if (!newMessage.value.trim() || !activeContact.value) return;

    const msgText = newMessage.value;
    newMessage.value = '';

    axios.post(route('chat.send'), {
        receiver_id: activeContact.value.id,
        message: msgText,
    }).then(res => {
        if (res.data.status === 'success') {
            messages.value.push(res.data.message);
            
            // Update last message preview in contacts list
            const contact = props.contacts.find(c => c.id === activeContact.value.id);
            if (contact) {
                contact.last_message = {
                    message: res.data.message.message,
                    created_at: res.data.message.created_at,
                };
            }
            scrollToBottom();
        }
    }).catch(err => console.error(err));
};

const handleEnter = (e) => {
    if (!e.shiftKey) {
        sendChatMessage();
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ' ' +
           date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
};

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});
</script>

<style scoped>
@keyframes scaleUp {
    from {
        opacity: 0;
        transform: scale(0.98);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.animate-scaleUp {
    animation: scaleUp 0.15s ease-out forwards;
}
</style>
