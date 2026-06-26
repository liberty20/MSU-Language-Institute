<template>
    <Head title="Notification History" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <span class="text-white font-extrabold">Notification History</span>
                <button v-if="unreadCount > 0" @click="markAllRead" class="bg-[#f5c242] text-[#0a1f44] hover:bg-yellow-500 px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition shadow-sm">
                    Mark All as Read
                </button>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-150 bg-gray-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-[#0a1f44] uppercase tracking-wider">All Notifications</h3>
                    <span class="text-xs bg-brand-blue-light/10 text-[#0a1f44] px-2.5 py-1 rounded-full font-bold">
                        {{ unreadCount }} Unread
                    </span>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="notif in notifications.data" :key="notif.id" 
                         class="p-6 transition flex gap-4 items-start" 
                         :class="{'bg-[#0a1f44]/[0.02] border-l-4 border-amber-500': !notif.read_at, 'border-l-4 border-transparent': notif.read_at}">
                        
                        <!-- Notification icon based on type -->
                        <div class="p-3 rounded-xl flex-shrink-0" :class="notif.read_at ? 'bg-gray-100 text-gray-400' : 'bg-amber-50 text-amber-600'">
                            <span v-if="notif.data.type === 'Tasks' || notif.data.type === 'System Alerts'">🔔</span>
                            <span v-else-if="notif.data.type === 'Enrollment'">🎓</span>
                            <span v-else-if="notif.data.type === 'Approvals'">✍️</span>
                            <span v-else-if="notif.data.type === 'Messages'">💬</span>
                            <span v-else>📢</span>
                        </div>

                        <!-- Notification details -->
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded shadow-sm"
                                      :class="notif.read_at ? 'bg-gray-100 text-gray-500' : 'bg-amber-100 text-amber-800'">
                                    {{ notif.read_at ? 'Read' : 'Unread' }}
                                </span>
                                <span v-if="notif.data.type" class="inline-block px-2 py-0.5 text-[9px] font-bold bg-indigo-50 text-[#0a1f44] rounded uppercase tracking-wider">
                                    {{ notif.data.type.replace('_', ' ') }}
                                </span>
                            </div>

                            <h4 class="text-sm font-black text-gray-800 leading-tight">
                                {{ notif.data.title }}
                            </h4>
                            <p class="text-xs text-gray-650 mt-1 leading-relaxed">
                                {{ notif.data.message }}
                            </p>
                            <span class="text-[10px] text-gray-400 block mt-2 font-medium">
                                {{ formatDate(notif.created_at) }}
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2 flex-shrink-0 self-center">
                            <button v-if="!notif.read_at" @click="markRead(notif.id)" class="text-xs text-[#0a1f44] hover:text-brand-gold-dark font-extrabold uppercase tracking-wider border border-gray-200 hover:border-brand-gold-dark px-3 py-1.5 rounded-lg transition" title="Mark as read">
                                Mark Read
                            </button>
                            <Link v-if="notif.data.action_url" :href="route('notifications.click', notif.id)" class="text-xs text-center bg-[#0a1f44] text-white hover:bg-brand-blue font-bold px-3 py-1.5 rounded-lg transition uppercase tracking-wider shadow-sm">
                                View Action
                            </Link>
                        </div>
                    </div>

                    <div v-if="notifications.data.length === 0" class="p-16 text-center text-gray-400 italic font-semibold">
                        No notifications found in your history.
                    </div>
                </div>

                <!-- Pagination footer -->
                <div class="px-6 py-4 border-t border-gray-150 flex items-center justify-between" v-if="notifications.total > 0">
                    <span class="text-xs text-gray-500 font-bold">
                        Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} results
                    </span>
                    <div class="flex gap-1" v-if="notifications.links">
                        <Link v-for="(link, i) in notifications.links" :key="i" :href="link.url || '#'" 
                              class="px-3 py-1.5 rounded-lg border text-xs font-bold transition" 
                              :class="link.active ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-650 border-gray-200 hover:bg-gray-50'"
                              v-html="link.label">
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';
import { computed } from 'vue';
import axios from 'axios';
import { Inertia } from '@inertiajs/inertia';

const props = defineProps({
    notifications: Object
});

const unreadCount = computed(() => {
    return props.notifications.data.filter(n => !n.read_at).length;
});

const markRead = (id) => {
    axios.post(route('notifications.read', id)).then(() => {
        Inertia.reload({ only: ['notifications'] });
    }).catch(err => console.error(err));
};

const markAllRead = () => {
    axios.post(route('notifications.read-all')).then(() => {
        Inertia.reload({ only: ['notifications'] });
    }).catch(err => console.error(err));
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
