<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link :href="route('clients.index')" class="text-gray-400 hover:text-brand-blue transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </Link>
                <span>{{ client.organization || client.contact_person }}</span>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Client Profile Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden lg:col-span-1">
                <div class="p-6 bg-brand-blue text-center">
                    <div class="w-20 h-20 mx-auto rounded-full bg-brand-gold text-brand-blue flex items-center justify-center text-3xl font-bold mb-4">
                        {{ (client.organization || client.contact_person).charAt(0) }}
                    </div>
                    <h2 class="text-xl font-bold text-white">{{ client.organization || client.contact_person }}</h2>
                    <p class="text-brand-gold text-sm capitalize mt-1">{{ client.client_type }}</p>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Contact Person</p>
                        <p class="font-medium text-gray-900">{{ client.contact_person }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Email</p>
                        <a :href="`mailto:${client.email}`" class="font-medium text-brand-blue hover:underline">{{ client.email }}</a>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Phone</p>
                        <p class="font-medium text-gray-900">{{ client.phone || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Address</p>
                        <p class="font-medium text-gray-900">{{ client.address || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase mb-1">Status</p>
                        <span class="px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wide inline-block"
                            :class="client.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                            {{ client.status }}
                        </span>
                    </div>

                    <div class="pt-6 mt-6 border-t border-gray-100 flex gap-3" v-if="$page.props.auth.permissions.includes('manage clients') || $page.props.auth.permissions.includes('manage system')">
                        <Link :href="route('clients.edit', client.id)" class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold transition text-sm">
                            Edit Profile
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Service Requests -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h3 class="text-lg font-bold text-gray-900">Recent Service Requests</h3>
                        <Link :href="route('service-requests.create')" class="text-sm bg-brand-blue hover:bg-brand-blue-light text-white px-3 py-1.5 rounded shadow transition font-medium">
                            + New Request
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4 font-semibold">Reference</th>
                                    <th class="px-6 py-4 font-semibold">Service</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="req in client.service_requests" :key="req.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <Link :href="route('service-requests.show', req.id)" class="font-bold text-brand-blue hover:underline">{{ req.reference_number }}</Link>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 capitalize">{{ req.service_category.replace('_', ' ') }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wide"
                                            :class="{
                                                'bg-blue-100 text-blue-700': req.status === 'pending' || req.status === 'in_progress',
                                                'bg-green-100 text-green-700': req.status === 'completed' || req.status === 'approved',
                                                'bg-purple-100 text-purple-700': req.status === 'review' || req.status === 'quoted',
                                                'bg-red-100 text-red-700': req.status === 'cancelled'
                                            }">
                                            {{ req.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-sm">
                                        {{ new Date(req.created_at).toLocaleDateString() }}
                                    </td>
                                </tr>
                                <tr v-if="client.service_requests.length === 0">
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                        No service requests found for this client.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/inertia-vue3';

defineProps({
    client: Object
});
</script>
