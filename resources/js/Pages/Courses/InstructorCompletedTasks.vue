<template>
    <Head title="Completed Tasks" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Completed Assignments & Tasks</span>
                <span class="text-xs font-semibold px-3 py-1 bg-green-100 text-green-700 rounded-full uppercase tracking-wider">
                    Instructor History
                </span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Header Banner -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-brand-blue">Operational Task Archive</h3>
                    <p class="text-sm text-gray-500">View and inspect details, timelines, client profiles, and subtask compliance for all your completed projects.</p>
                </div>
                <div class="bg-green-50 px-4 py-3 rounded-xl border border-green-100 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold text-lg">
                        {{ completedAssignments.length }}
                    </span>
                    <div>
                        <div class="text-xs font-bold text-green-800 uppercase tracking-wider">Total Completed</div>
                        <div class="text-xs text-green-650">Assignments archived</div>
                    </div>
                </div>
            </div>

            <!-- List of Completed Assignments -->
            <div class="space-y-4" v-if="completedAssignments.length > 0">
                <div v-for="asg in completedAssignments" :key="asg.id" 
                     class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow transition">
                    
                    <!-- Card Header -->
                    <div class="p-6 border-b border-gray-100 flex flex-wrap justify-between items-start gap-4 bg-gray-50/20">
                        <div class="space-y-1 flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 text-xs font-bold bg-[#0a1f44]/10 text-[#0a1f44] rounded uppercase tracking-wider">
                                    {{ asg.service_request ? asg.service_request.service_category.replace(/_/g, ' ') : 'General' }}
                                </span>
                                <span class="text-xs text-gray-400 font-semibold uppercase">
                                    Assigned Role: {{ asg.role_in_task || 'Expert' }}
                                </span>
                            </div>
                            <h4 class="text-base font-bold text-brand-blue truncate" v-if="asg.service_request">
                                {{ asg.service_request.title }}
                            </h4>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-right text-xs">
                                <div class="text-gray-400 font-medium">Completed On</div>
                                <div class="font-bold text-gray-800">{{ formatDate(asg.completed_at) }}</div>
                            </div>
                            <button @click="toggleExpand(asg.id)" 
                                    class="p-2 border border-gray-200 hover:bg-gray-50 text-gray-500 rounded-xl transition">
                                <svg class="w-5 h-5 transition-transform duration-300" 
                                     :class="expandedId === asg.id ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-6 space-y-4">
                        <div class="text-sm text-gray-600 leading-relaxed" v-if="asg.service_request">
                            {{ asg.service_request.description || 'No description provided.' }}
                        </div>

                        <!-- Notes/Comments -->
                        <div class="text-xs text-gray-500 bg-gray-50 p-3.5 rounded-xl border border-gray-100 italic" v-if="asg.notes">
                            <span class="font-bold text-gray-700 not-italic block mb-0.5">Instructor Notes:</span>
                            "{{ asg.notes }}"
                        </div>

                        <!-- Brief Timeline -->
                        <div class="flex flex-wrap gap-x-8 gap-y-2 text-xs text-gray-400 font-semibold uppercase pt-2">
                            <div>Started: <span class="text-gray-650 font-bold">{{ formatDate(asg.started_at) }}</span></div>
                            <div>Completed: <span class="text-gray-650 font-bold">{{ formatDate(asg.completed_at) }}</span></div>
                            <div>Subtasks: <span class="text-gray-650 font-bold">{{ asg.tasks.length }} items</span></div>
                        </div>
                    </div>

                    <!-- Collapsible Expander Details -->
                    <div v-show="expandedId === asg.id" class="px-6 pb-6 pt-2 border-t border-gray-100 bg-gray-55/20 transition-all duration-300 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <!-- Left: Client Profile -->
                            <div class="space-y-3">
                                <h5 class="text-xs font-bold text-brand-gold-dark uppercase tracking-wider border-b border-gray-100 pb-1.5 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-brand-gold-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5"/></svg>
                                    Client Profile Information
                                </h5>
                                <div class="bg-white p-4 rounded-xl border border-gray-100 text-xs space-y-2" v-if="asg.service_request && asg.service_request.client">
                                    <div v-if="asg.service_request.client.organization" class="font-bold text-sm text-gray-800 mb-1">
                                        {{ asg.service_request.client.organization }}
                                    </div>
                                    <div class="grid grid-cols-3">
                                        <span class="text-gray-400 font-semibold">Contact Person:</span>
                                        <span class="col-span-2 text-gray-700 font-bold">{{ asg.service_request.client.contact_person }}</span>
                                    </div>
                                    <div class="grid grid-cols-3">
                                        <span class="text-gray-400 font-semibold">Email Address:</span>
                                        <span class="col-span-2 text-gray-700 font-bold">{{ asg.service_request.client.email }}</span>
                                    </div>
                                    <div class="grid grid-cols-3">
                                        <span class="text-gray-400 font-semibold">Phone Number:</span>
                                        <span class="col-span-2 text-gray-700 font-bold">{{ asg.service_request.client.phone || 'N/A' }}</span>
                                    </div>
                                    <div class="grid grid-cols-3">
                                        <span class="text-gray-400 font-semibold">Office Address:</span>
                                        <span class="col-span-2 text-gray-700 font-bold whitespace-pre-wrap">{{ asg.service_request.client.address || 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="text-xs text-gray-400 italic" v-else>No client profile associated.</div>
                            </div>

                            <!-- Right: Subtasks Breakdown -->
                            <div class="space-y-3">
                                <h5 class="text-xs font-bold text-brand-gold-dark uppercase tracking-wider border-b border-gray-100 pb-1.5 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-brand-gold-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                    Subtasks compliance checklist
                                </h5>
                                <div class="bg-white rounded-xl border border-gray-100 overflow-hidden divide-y divide-gray-100 text-xs" v-if="asg.tasks && asg.tasks.length > 0">
                                    <div v-for="t in asg.tasks" :key="t.title" class="p-3 flex items-start justify-between gap-3 hover:bg-gray-50 transition">
                                        <div class="space-y-0.5">
                                            <div class="font-bold text-gray-800">{{ t.title }}</div>
                                            <div class="text-gray-500 font-medium" v-if="t.description">{{ t.description }}</div>
                                            <div class="text-[10px] text-gray-400 mt-1" v-if="t.completed_at">Completed: {{ formatDate(t.completed_at) }}</div>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wider flex-shrink-0"
                                              :class="t.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                            {{ t.status }}
                                        </span>
                                    </div>
                                </div>
                                <div class="bg-white p-4 rounded-xl border border-gray-100 text-center text-xs text-gray-400 italic" v-else>
                                    No operational subtasks were registered for this assignment.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center text-gray-500" v-else>
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <h4 class="text-base font-bold text-brand-blue mb-1">No Completed Assignments</h4>
                <p class="text-sm text-gray-400">All assignments currently in progress are shown in the main dashboard logs.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/inertia-vue3';
import { ref } from 'vue';

const props = defineProps({
    completedAssignments: Array,
});

const expandedId = ref(null);

const toggleExpand = (id) => {
    if (expandedId.value === id) {
        expandedId.value = null;
    } else {
        expandedId.value = id;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};
</script>
