<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>Clients Directory</span>
                <Link v-if="$page.props.auth.permissions.includes('manage clients') || $page.props.auth.permissions.includes('manage system')" 
                      :href="route('clients.create')" 
                      class="bg-brand-gold hover:bg-brand-gold-dark text-brand-blue font-bold py-2 px-4 rounded-lg shadow transition text-sm">
                    + Add New Client
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <form @submit.prevent="submitSearch" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <svg class="w-5 h-5 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input v-model="form.search" type="text" placeholder="Search by name, org, email..." class="pl-10 w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue sm:text-sm">
                </div>
                <div class="w-full md:w-64">
                    <select v-model="form.status" @change="submitSearch" class="w-full border-gray-200 rounded-lg focus:ring-brand-blue focus:border-brand-blue sm:text-sm text-gray-600">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <button type="submit" class="bg-brand-blue hover:bg-brand-blue-light text-white px-6 py-2 rounded-lg text-sm font-semibold transition">
                    Filter
                </button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-6 py-4 font-semibold">Client Details</th>
                            <th class="px-6 py-4 font-semibold">Contact Info</th>
                            <th class="px-6 py-4 font-semibold">Requests</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-900">{{ client.organization || client.contact_person }}</p>
                                <p class="text-xs text-gray-500 capitalize">{{ client.client_type }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-700">{{ client.email }}</p>
                                <p class="text-xs text-gray-500">{{ client.phone || 'N/A' }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-brand-blue">
                                {{ client.service_requests_count }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                                    :class="client.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                                    {{ client.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <Link :href="route('clients.show', client.id)" class="text-brand-blue hover:text-brand-gold-dark font-medium text-sm">View</Link>
                                <Link v-if="$page.props.auth.permissions.includes('manage clients') || $page.props.auth.permissions.includes('manage system')" 
                                      :href="route('clients.edit', client.id)" 
                                      class="text-gray-500 hover:text-gray-900 font-medium text-sm">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="clients.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No clients found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="clients.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-1">
                <template v-for="(link, k) in clients.links" :key="k">
                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 border border-gray-200 rounded-lg" v-html="link.label"></div>
                    <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-2 text-sm leading-4 border rounded-lg hover:bg-gray-50 focus:border-brand-blue focus:text-brand-blue transition" :class="{ 'bg-brand-blue text-white border-brand-blue hover:bg-brand-blue-light': link.active, 'border-gray-200 text-gray-700': !link.active }" v-html="link.label"></Link>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/inertia-vue3';

const props = defineProps({
    clients: Object,
    filters: Object
});

const form = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const submitSearch = () => {
    form.get(route('clients.index'), { preserveState: true });
};
</script>
