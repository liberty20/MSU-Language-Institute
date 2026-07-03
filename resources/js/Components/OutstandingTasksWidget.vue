<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-black text-[#0a1f44] flex items-center gap-2">
                <span class="inline-flex relative h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-500"></span>
                </span>
                Outstanding Tasks & Action Items
            </h3>
            <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200 shadow-sm uppercase tracking-wide">
                {{ tasks.length }} {{ tasks.length === 1 ? 'Action' : 'Actions' }} Required
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="task in tasks" :key="task.id" 
                 class="bg-white rounded-2xl p-5 border shadow-sm hover:shadow-md transition duration-300 flex flex-col justify-between group relative overflow-hidden"
                 :class="getCardClass(task.priority)">
                
                <!-- Background Accent Glow -->
                <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br opacity-5 rounded-full blur-2xl transition duration-500 group-hover:scale-150"
                     :class="getGlowClass(task.priority)"></div>

                <div class="space-y-3 relative z-10">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <!-- Module Badge with Icon -->
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-slate-100 text-[#0a1f44]">
                            <span>{{ getModuleIcon(task.module) }}</span>
                            {{ task.module }}
                        </span>

                        <!-- Priority Level -->
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-black tracking-wider uppercase border"
                              :class="getPriorityClass(task.priority)">
                            {{ task.priority }} Priority
                        </span>
                    </div>

                    <!-- Title -->
                    <h4 class="font-extrabold text-sm text-gray-800 leading-snug group-hover:text-[#0a1f44] transition-colors">
                        {{ task.title }}
                    </h4>

                    <!-- Description -->
                    <p v-if="task.description" class="text-xs text-gray-500 leading-relaxed line-clamp-2">
                        {{ task.description }}
                    </p>

                    <!-- Days Remaining / Overdue & Due Date -->
                    <div class="flex flex-col gap-1 pt-1.5 border-t border-dashed border-gray-100 text-xs">
                        <div class="flex items-center justify-between text-gray-500 font-medium">
                            <span>Required Action:</span>
                            <span class="font-black text-gray-850 bg-indigo-50/50 px-2 py-0.5 rounded text-[10px] uppercase tracking-wide">
                                {{ task.required_action }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between font-medium">
                            <span>Timeline:</span>
                            <span :class="getTimelineClass(task.days_diff)" class="font-bold flex items-center gap-1">
                                <span v-if="task.days_diff !== null && task.days_diff < 0">⚠️</span>
                                {{ getTimelineText(task.days_diff) }}
                            </span>
                        </div>
                        <div v-if="task.due_date" class="flex items-center justify-between text-[11px] text-gray-400 font-medium">
                            <span>Due Date:</span>
                            <span>{{ formatDate(task.due_date) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-gray-450 font-medium">
                            <span>Current Status:</span>
                            <span class="font-semibold text-[10px] uppercase text-slate-500">{{ task.status }}</span>
                        </div>
                    </div>
                </div>

                <!-- CTA Action Button -->
                <div class="mt-4 pt-3 border-t border-gray-50 flex justify-end">
                    <Link :href="task.action_url" 
                          class="inline-flex items-center gap-1.5 text-xs font-black text-[#0a1f44] hover:text-brand-gold-dark uppercase tracking-wider transition group/btn">
                        {{ getButtonText(task.required_action) }}
                        <svg class="w-3.5 h-3.5 transform group-hover/btn:translate-x-1 transition duration-200 text-brand-gold-dark" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/inertia-vue3';

const props = defineProps({
    tasks: {
        type: Array,
        required: true,
        default: () => []
    }
});

const getModuleIcon = (module) => {
    switch (module ? module.toLowerCase() : '') {
        case 'assignments':
            return '🎓';
        case 'service requests':
            return '💬';
        case 'quotations':
            return '📄';
        case 'approvals':
            return '✍️';
        case 'finance':
            return '💰';
        case 'notices':
        case 'announcements':
            return '📢';
        default:
            return '🔔';
    }
};

const getCardClass = (priority) => {
    switch (priority ? priority.toLowerCase() : '') {
        case 'critical':
            return 'border-rose-200 bg-gradient-to-b from-white to-rose-50/[0.05] hover:border-rose-300';
        case 'high':
            return 'border-amber-200 bg-gradient-to-b from-white to-amber-50/[0.03] hover:border-amber-300';
        case 'medium':
            return 'border-indigo-100 hover:border-indigo-200';
        default:
            return 'border-slate-200 hover:border-slate-350';
    }
};

const getGlowClass = (priority) => {
    switch (priority ? priority.toLowerCase() : '') {
        case 'critical':
            return 'from-rose-500 to-rose-600';
        case 'high':
            return 'from-amber-500 to-orange-500';
        case 'medium':
            return 'from-indigo-500 to-indigo-600';
        default:
            return 'from-slate-400 to-slate-500';
    }
};

const getPriorityClass = (priority) => {
    switch (priority ? priority.toLowerCase() : '') {
        case 'critical':
            return 'bg-rose-50 text-rose-800 border-rose-200 font-extrabold';
        case 'high':
            return 'bg-amber-50 text-amber-800 border-amber-200 font-bold';
        case 'medium':
            return 'bg-indigo-50 text-indigo-800 border-indigo-150';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-200';
    }
};

const getTimelineClass = (daysDiff) => {
    if (daysDiff === null) return 'text-slate-400 font-medium';
    if (daysDiff < 0) return 'text-rose-600 font-black';
    if (daysDiff === 0) return 'text-amber-600 font-extrabold';
    if (daysDiff <= 3) return 'text-amber-500';
    return 'text-emerald-600';
};

const getTimelineText = (daysDiff) => {
    if (daysDiff === null) return 'No strict deadline';
    if (daysDiff < 0) {
        const abs = Math.abs(daysDiff);
        return `${abs} ${abs === 1 ? 'day' : 'days'} overdue`;
    }
    if (daysDiff === 0) return 'Due today';
    return `${daysDiff} ${daysDiff === 1 ? 'day' : 'days'} remaining`;
};

const getButtonText = (action) => {
    if (!action) return 'Resolve Task';
    switch (action.toLowerCase()) {
        case 'submit':
            return 'Submit Now';
        case 'mark/grade':
            return 'Grade Submission';
        case 'verify':
        case 'verify payment':
        case 'verify enrollment payment':
            return 'Verify Now';
        case 'recommend':
            return 'Recommend';
        case 'approve':
            return 'Approve Now';
        case 'approve & pay':
            return 'Approve & Pay';
        case 'create quotation':
            return 'Create Quote';
        case 'respond/process':
            return 'Process Request';
        default:
            return 'Complete Action';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString(undefined, { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    } catch (e) {
        return dateStr;
    }
};
</script>
