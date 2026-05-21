<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <span>User Management</span>
                <Link :href="route('admin.users.create')" class="bg-[#0a1f44] hover:bg-[#0a1f44]/80 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    + Add User
                </Link>
            </div>
        </template>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                            <th class="px-6 py-4 font-semibold">Name</th>
                            <th class="px-6 py-4 font-semibold">Email</th>
                            <th class="px-6 py-4 font-semibold">Roles</th>
                            <th class="px-6 py-4 font-semibold">Status</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-bold text-gray-900">{{ user.name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ user.email }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="role in user.roles" :key="role.id" 
                                          class="px-2 py-0.5 bg-[#0a1f44]/10 text-[#0a1f44] rounded text-xs font-medium">
                                        {{ role.name.replace(/_/g, ' ') }}
                                    </span>
                                    <span v-if="!user.roles || user.roles.length === 0" class="text-xs text-gray-400 italic">No roles</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase"
                                      :class="user.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <Link :href="route('admin.users.edit', user.id)" class="text-gray-500 hover:text-gray-900 font-medium text-sm transition">Edit</Link>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">No users found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between" v-if="users.links">
                <span class="text-sm text-gray-500">Showing {{ users.from }} to {{ users.to }} of {{ users.total }}</span>
                <div class="flex gap-1">
                    <Link v-for="(link, k) in users.links" :key="k"
                          :href="link.url || '#'"
                          class="px-3 py-1 rounded border text-sm transition"
                          :class="link.active ? 'bg-[#0a1f44] border-[#0a1f44] text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
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
    users: Object,
});
</script>
