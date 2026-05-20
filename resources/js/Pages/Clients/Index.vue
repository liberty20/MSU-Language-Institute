<template>
    <Head title="Clients Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Clients Directory</span>
                <button class="bg-[#0a1f44] text-white hover:bg-[#152a4d] px-4 py-2 rounded-lg font-semibold text-sm transition-colors shadow-sm">
                    + Add Client
                </button>
            </div>
        </template>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex gap-4">
                <input type="text" placeholder="Search clients..." class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 w-64" />
                <select class="border-gray-300 rounded-md shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="organization">Organization</option>
                    <option value="individual">Individual</option>
                    <option value="government">Government</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <th class="px-6 py-4 font-semibold">Client / Organization</th>
                            <th class="px-6 py-4 font-semibold">Contact Person</th>
                            <th class="px-6 py-4 font-semibold">Email & Phone</th>
                            <th class="px-6 py-4 font-semibold">Type</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        <tr v-if="clients.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No clients registered yet.</td>
                        </tr>
                        <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ client.organization || 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ client.contact_person }}</td>
                            <td class="px-6 py-4">
                                <div class="text-gray-900">{{ client.email }}</div>
                                <div class="text-gray-500 text-xs">{{ client.phone || 'No phone' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-medium capitalize bg-purple-100 text-purple-800">
                                    {{ client.client_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-medium capitalize"
                                    :class="client.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                    {{ client.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link :href="route('clients.show', client.id)" class="text-blue-600 hover:text-blue-900 font-medium text-sm">Profile</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="clients.total > 0">
                <span class="text-sm text-gray-500">
                    Showing {{ clients.from }} to {{ clients.to }} of {{ clients.total }} results
                </span>
                <div class="flex gap-1" v-if="clients.links">
                    <Link v-for="(link, i) in clients.links" :key="i" :href="link.url || '#'" 
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
    clients: Object
});
</script>
