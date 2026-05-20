<template>
    <Head title="Tasks Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Task Dashboard</span>
                <button class="bg-[#f5c242] text-[#0a1f44] hover:bg-yellow-500 px-4 py-2 rounded-lg font-semibold text-sm transition-colors shadow-sm">
                    + Assign Task
                </button>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex gap-4">
                <input type="text" placeholder="Search tasks..." class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 w-64" />
                <select class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Task</th>
                            <th class="px-6 py-4 font-semibold">Assigned To</th>
                            <th class="px-6 py-4 font-semibold">Service Request</th>
                            <th class="px-6 py-4 font-semibold">Priority</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold">Deadline</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr v-if="tasks.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No tasks currently assigned.</td>
                        </tr>
                        <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ task.title }}</div>
                                <div class="text-xs text-gray-500 truncate w-48">{{ task.description }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold">
                                        {{ task.assigned_to_user?.name?.charAt(0) || 'U' }}
                                    </div>
                                    {{ task.assigned_to_user?.name || 'Unassigned' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ task.service_request?.reference_number }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-medium capitalize" 
                                    :class="{
                                        'bg-red-100 text-red-800': task.priority === 'urgent',
                                        'bg-orange-100 text-orange-800': task.priority === 'high',
                                        'bg-blue-100 text-blue-800': task.priority === 'medium',
                                        'bg-gray-100 text-gray-800': task.priority === 'low'
                                    }">
                                    {{ task.priority }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': task.status === 'pending',
                                        'bg-blue-100 text-blue-800': task.status === 'in_progress',
                                        'bg-green-100 text-green-800': task.status === 'completed',
                                        'bg-red-100 text-red-800': task.status === 'overdue'
                                    }">
                                    {{ task.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ new Date(task.deadline).toLocaleDateString() }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('tasks.show', task.id)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Update</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="tasks.total > 0">
                <span class="text-sm text-gray-500">
                    Showing {{ tasks.from }} to {{ tasks.to }} of {{ tasks.total }} results
                </span>
                <div class="flex gap-1" v-if="tasks.links">
                    <Link v-for="(link, i) in tasks.links" :key="i" :href="link.url || '#'" 
                          class="px-3 py-1 rounded border text-sm" 
                          :class="link.active ? 'bg-[#0a1f44] text-white border-[#0a1f44]' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                          v-html="link.label">
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/inertia-vue3';

defineProps({
    tasks: Object
});
</script>
