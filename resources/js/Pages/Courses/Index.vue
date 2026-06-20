<template>
    <AuthenticatedLayout>
        <template #header>
            Short Courses & Intake Management
        </template>

        <div class="space-y-8">
            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'courses'" 
                        :class="[
                            activeTab === 'courses' ? 'border-brand-gold text-brand-blue font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all'
                        ]">
                        Course Catalog Templates
                    </button>
                    <button @click="activeTab = 'intakes'" 
                        :class="[
                            activeTab === 'intakes' ? 'border-brand-gold text-brand-blue font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all'
                        ]">
                        Scheduled Intakes & Batches
                    </button>
                </nav>
            </div>

            <!-- Tab 1: Course Templates -->
            <div v-show="activeTab === 'courses'" class="space-y-6">
                <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <div>
                        <h3 class="text-lg font-bold text-brand-blue">All Course Offerings</h3>
                        <p class="text-sm text-gray-500">Create, edit, and publish course curricula templates across units.</p>
                    </div>
                    <button @click="openCreateCourseModal" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-6 py-2.5 rounded-full transition shadow flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create New Course
                    </button>
                </div>

                <!-- Courses List Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="course in courses" :key="course.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col hover:shadow-md transition">
                        <div class="p-6 flex-grow space-y-4">
                            <div class="flex justify-between items-start">
                                <span class="px-2.5 py-1 text-xs font-bold bg-amber-50 text-amber-700 rounded-full uppercase tracking-wider">
                                    {{ course.category }}
                                </span>
                                <span :class="[
                                    course.is_published ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500',
                                    'px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider'
                                ]">
                                    {{ course.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-brand-blue line-clamp-1" :title="course.title">{{ course.title }}</h4>
                                <p class="text-xs text-gray-400 font-medium mt-1">Code: {{ course.code }} | {{ course.duration_weeks }} Weeks</p>
                            </div>
                            <p class="text-sm text-gray-600 line-clamp-3 leading-relaxed">{{ course.description || 'No description provided.' }}</p>
                            
                            <div class="pt-4 border-t border-gray-100 flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-gray-400 font-semibold uppercase">Pricing Fee</p>
                                    <p class="text-lg font-black text-brand-blue mt-0.5">{{ course.currency }} {{ Number(course.price).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400 font-semibold uppercase">Functional Unit</p>
                                    <p class="text-xs font-bold text-brand-gold-dark mt-0.5">{{ course.department ? course.department.code : 'General MSULI' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 items-center">
                            <button v-if="['executive_director', 'deputy_director', 'ict_administrator'].some(r => $page.props.auth.roles.includes(r))" 
                                    @click="deleteCourse(course)" 
                                    class="text-sm font-semibold text-red-650 hover:text-red-500 transition mr-auto">
                                Delete Course
                            </button>
                            <button @click="openEditCourseModal(course)" class="text-sm font-semibold text-brand-blue hover:text-brand-gold transition">
                                Edit Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Scheduled Intakes -->
            <div v-show="activeTab === 'intakes'" class="space-y-6">
                <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <div>
                        <h3 class="text-lg font-bold text-brand-blue">Scheduled Course Batches</h3>
                        <p class="text-sm text-gray-500">Manage calendar schedules, assign instructors, and set student intake limits.</p>
                    </div>
                    <button @click="openCreateIntakeModal" class="bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold px-6 py-2.5 rounded-full transition shadow flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Schedule Intake
                    </button>
                </div>

                <!-- Intakes Table -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-4 font-semibold">Course</th>
                                    <th class="px-6 py-4 font-semibold">Intake Session</th>
                                    <th class="px-6 py-4 font-semibold">Instructor</th>
                                    <th class="px-6 py-4 font-semibold">Start & End Dates</th>
                                    <th class="px-6 py-4 font-semibold">Capacity</th>
                                    <th class="px-6 py-4 font-semibold">Status</th>
                                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <tr v-for="intake in intakes" :key="intake.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-brand-blue">{{ intake.course.title }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">Code: {{ intake.course.code }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-semibold text-gray-700">{{ intake.name }}</td>
                                    <td class="px-6 py-4 text-gray-600 font-medium">
                                        {{ intake.instructor ? intake.instructor.name : 'Not Assigned' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        <div>{{ formatDate(intake.start_date) }}</div>
                                        <div class="text-xs text-gray-400">to {{ formatDate(intake.end_date) }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-700">{{ intake.capacity }} Students</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
                                            :class="{
                                                'bg-green-150 text-green-700': intake.status === 'open',
                                                'bg-amber-150 text-amber-700': intake.status === 'draft',
                                                'bg-red-150 text-red-700': intake.status === 'closed',
                                                'bg-purple-150 text-purple-700': intake.status === 'completed'
                                            }">
                                            {{ intake.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button @click="openEditIntakeModal(intake)" class="text-brand-gold-dark hover:text-brand-blue font-bold text-xs uppercase transition">
                                            Edit Schedule
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="intakes.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        No scheduled course intakes found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Modal -->
        <div v-if="courseModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="courseModalOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center rounded-t-2xl">
                    <h3 class="text-lg font-bold">{{ isEditingCourse ? 'Edit Course Details' : 'Create New Course Offering' }}</h3>
                    <button @click="courseModalOpen = false" class="text-gray-300 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form @submit.prevent="saveCourse" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Course Title</label>
                            <input v-model="courseForm.title" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required placeholder="e.g. Zimbabwean Sign Language Beginners" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Course Code</label>
                            <input v-model="courseForm.code" type="text" :disabled="isEditingCourse" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm disabled:bg-gray-100" required placeholder="e.g. ZSL-101" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Category</label>
                            <select v-model="selectedCategoryVal" @change="updateCategoryVal" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required>
                                <option value="" disabled>Select Category</option>
                                <option v-for="cat in courseCategories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                            <div v-if="selectedCategoryVal === 'Other'" class="mt-2 transition-all duration-300">
                                <input v-model="customCategoryVal" type="text" @input="updateCategoryVal" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required placeholder="Specify custom category" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Functional Unit</label>
                            <select v-model="courseForm.department_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm">
                                <option :value="null">General MSULI</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.code }} - {{ dept.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Duration (Weeks)</label>
                            <input v-model="courseForm.duration_weeks" type="number" min="1" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Tuition Fee</label>
                            <input v-model="courseForm.price" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Currency</label>
                            <input v-model="courseForm.currency" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required placeholder="USD, ZiG" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Course Description</label>
                            <textarea v-model="courseForm.description" rows="3" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" placeholder="Provide syllabus outline or summary..."></textarea>
                        </div>
                        <div class="col-span-2 flex items-center gap-2 py-2">
                            <input v-model="courseForm.is_published" type="checkbox" id="is_published" class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue" />
                            <label for="is_published" class="text-sm font-bold text-gray-700 select-none">Publish Course to Public Catalog immediately</label>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="courseModalOpen = false" class="px-5 py-2.5 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-sm">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-sm">Save Course</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Intake Modal -->
        <div v-if="intakeModalOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" @click.self="intakeModalOpen = false">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl my-8 max-h-[90vh] flex flex-col">
                <div class="bg-[#0a1f44] text-white px-6 py-4 flex justify-between items-center rounded-t-2xl flex-shrink-0">
                    <h3 class="text-lg font-bold">{{ isEditingIntake ? 'Edit Intake Schedule' : 'Schedule New Intake Session' }}</h3>
                    <button @click="intakeModalOpen = false" class="text-gray-300 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="overflow-y-auto flex-1">
                <form @submit.prevent="saveIntake" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2" v-if="!isEditingIntake">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Select Course</label>
                            <select v-model="intakeForm.course_id" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required>
                                <option :value="null" disabled>Choose a course to schedule...</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.code }} - {{ course.title }}</option>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Session / Intake Name</label>
                            <input v-model="intakeForm.name" type="text" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required placeholder="e.g. Winter 2026 Intake, Batch A" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Start Date</label>
                            <input v-model="intakeForm.start_date" type="date" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">End Date</label>
                            <input v-model="intakeForm.end_date" type="date" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Class Capacity</label>
                            <input v-model="intakeForm.capacity" type="number" min="1" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Status</label>
                            <select v-model="intakeForm.status" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm" required>
                                <option value="draft">Draft</option>
                                <option value="open">Open (Enrolling)</option>
                                <option value="closed">Closed</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <!-- Instructor Combobox — uses Teleport to escape modal overflow clipping -->
                        <div class="col-span-2" ref="dropdownContainer">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1.5">Assign Instructor</label>
                            <div class="relative">
                                <input 
                                    id="instructor-search-input"
                                    v-model="instructorSearch" 
                                    type="text" 
                                    placeholder="Search instructor by name, role, or unit..." 
                                    class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm pl-3 pr-10 py-2.5"
                                    @focus="handleFocus"
                                    @input="showInstructorDropdown = true"
                                    autocomplete="off"
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <!-- Teleported dropdown — renders at body level to avoid any overflow/z-index clipping -->
                            <Teleport to="body">
                                <div 
                                    v-show="showInstructorDropdown && intakeModalOpen"
                                    :style="dropdownStyle"
                                    class="fixed bg-white border border-gray-200 rounded-xl shadow-2xl overflow-y-auto divide-y divide-gray-100 text-sm"
                                    style="z-index: 9999;"
                                >
                                    <div 
                                        class="px-4 py-2.5 text-gray-500 cursor-pointer hover:bg-gray-50 font-bold sticky top-0 bg-white border-b border-gray-100"
                                        @mousedown.prevent="selectInstructor(null)"
                                    >
                                        — Unassigned
                                    </div>
                                    <div 
                                        v-for="ins in filteredInstructors" 
                                        :key="ins.id"
                                        class="px-4 py-2.5 cursor-pointer hover:bg-brand-gold/5 transition flex flex-col text-left"
                                        @mousedown.prevent="selectInstructor(ins)"
                                    >
                                        <span class="font-bold text-gray-800">{{ ins.name }}</span>
                                        <span class="text-xs text-gray-400 mt-0.5">{{ ins.role_name }} — {{ ins.unit_code }}</span>
                                    </div>
                                    <div v-if="filteredInstructors.length === 0" class="px-4 py-4 text-xs text-gray-400 italic text-center">
                                        No matching instructors found.
                                    </div>
                                </div>
                            </Teleport>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="intakeModalOpen = false" class="px-5 py-2.5 rounded-full border border-gray-300 font-semibold text-gray-700 hover:bg-gray-50 transition text-sm">Cancel</button>
                        <button type="submit" class="px-5 py-2.5 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-white font-bold transition shadow text-sm">Save Intake</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    courses: Array,
    intakes: Array,
    departments: Array,
    instructors: Array,
});

const activeTab = ref('courses');

// Course Modals
const courseModalOpen = ref(false);
const isEditingCourse = ref(false);
const editingCourseId = ref(null);
const courseForm = reactive({
    title: '',
    code: '',
    category: '',
    description: '',
    department_id: null,
    price: 0,
    currency: 'USD',
    duration_weeks: 6,
    is_published: false
});

const courseCategories = [
    'Braille',
    'Translation',
    'Interpretation',
    'Chewa',
    'ChiBarwe',
    'English',
    'Kalanga',
    'Koisan',
    'Nambya',
    'Ndau',
    'Ndebele',
    'Shangani',
    'Shona',
    'Sign Language',
    'Sotho',
    'Tonga',
    'Tswana',
    'Venda',
    'Xhosa',
    'Other'
];

const selectedCategoryVal = ref('');
const customCategoryVal = ref('');

const updateCategoryVal = () => {
    if (selectedCategoryVal.value === 'Other') {
        courseForm.category = customCategoryVal.value;
    } else {
        courseForm.category = selectedCategoryVal.value;
    }
};

const openCreateCourseModal = () => {
    isEditingCourse.value = false;
    editingCourseId.value = null;
    courseForm.title = '';
    courseForm.code = '';
    courseForm.category = '';
    courseForm.description = '';
    courseForm.department_id = null;
    courseForm.price = 0;
    courseForm.currency = 'USD';
    courseForm.duration_weeks = 6;
    courseForm.is_published = false;
    selectedCategoryVal.value = '';
    customCategoryVal.value = '';
    courseModalOpen.value = true;
};

const openEditCourseModal = (course) => {
    isEditingCourse.value = true;
    editingCourseId.value = course.id;
    courseForm.title = course.title;
    courseForm.code = course.code;
    courseForm.category = course.category;
    courseForm.description = course.description;
    courseForm.department_id = course.department_id;
    courseForm.price = course.price;
    courseForm.currency = course.currency;
    courseForm.duration_weeks = course.duration_weeks;
    courseForm.is_published = course.is_published;
    
    if (course.category && courseCategories.includes(course.category)) {
        selectedCategoryVal.value = course.category;
        customCategoryVal.value = '';
    } else if (course.category) {
        selectedCategoryVal.value = 'Other';
        customCategoryVal.value = course.category;
    } else {
        selectedCategoryVal.value = '';
        customCategoryVal.value = '';
    }
    
    courseModalOpen.value = true;
};

const saveCourse = () => {
    if (isEditingCourse.value) {
        Inertia.put(route('courses.update', editingCourseId.value), courseForm, {
            onSuccess: () => {
                courseModalOpen.value = false;
            }
        });
    } else {
        Inertia.post(route('courses.store'), courseForm, {
            onSuccess: () => {
                courseModalOpen.value = false;
            }
        });
    }
};

const deleteCourse = (course) => {
    if (confirm(`Are you sure you want to permanently delete the course "${course.title}" (${course.code})? This action cannot be undone.`)) {
        Inertia.delete(route('courses.destroy', course.id));
    }
};

// Intake Modals
const intakeModalOpen = ref(false);
const isEditingIntake = ref(false);
const editingIntakeId = ref(null);
const intakeForm = reactive({
    course_id: null,
    name: '',
    start_date: '',
    end_date: '',
    capacity: 20,
    status: 'draft',
    instructor_id: null
});

// Combobox Search-Enabled Instructor Dropdown
const dropdownContainer = ref(null);
const instructorSearch = ref('');
const showInstructorDropdown = ref(false);
const dropdownPos = ref({ top: 0, left: 0, width: 300 });

// Computed style for the Teleported dropdown — positions it below the input
const dropdownStyle = computed(() => ({
    top: dropdownPos.value.top + 'px',
    left: dropdownPos.value.left + 'px',
    width: dropdownPos.value.width + 'px',
    maxHeight: '15rem',
}));

const updateDropdownPosition = () => {
    const input = document.getElementById('instructor-search-input');
    if (input) {
        const rect = input.getBoundingClientRect();
        dropdownPos.value = {
            top: rect.bottom + window.scrollY + 4,
            left: rect.left + window.scrollX,
            width: rect.width,
        };
    }
};

const filteredInstructors = computed(() => {
    const q = instructorSearch.value.toLowerCase().trim();
    const currentIns = props.instructors.find(ins => ins.id === intakeForm.instructor_id);
    const currentDisp = currentIns ? `${currentIns.name} — ${currentIns.role_name} (${currentIns.unit_code})`.toLowerCase() : '';
    
    if (!q || q === currentDisp) return props.instructors;
    
    return props.instructors.filter(ins => {
        const name = ins.name.toLowerCase();
        const role = ins.role_name.toLowerCase();
        const unit = ins.unit_code.toLowerCase();
        return name.includes(q) || role.includes(q) || unit.includes(q);
    });
});

const selectInstructor = (ins) => {
    if (ins) {
        intakeForm.instructor_id = ins.id;
        instructorSearch.value = `${ins.name} — ${ins.role_name} (${ins.unit_code})`;
    } else {
        intakeForm.instructor_id = null;
        instructorSearch.value = '';
    }
    showInstructorDropdown.value = false;
};

const handleFocus = (e) => {
    updateDropdownPosition();
    showInstructorDropdown.value = true;
    e.target.select();
};

const handleClickOutside = (event) => {
    if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
        showInstructorDropdown.value = false;
        if (intakeForm.instructor_id) {
            const current = props.instructors.find(ins => ins.id === intakeForm.instructor_id);
            if (current) {
                instructorSearch.value = `${current.name} — ${current.role_name} (${current.unit_code})`;
                return;
            }
        }
        instructorSearch.value = '';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('scroll', updateDropdownPosition, true);
    window.addEventListener('resize', updateDropdownPosition);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('scroll', updateDropdownPosition, true);
    window.removeEventListener('resize', updateDropdownPosition);
});

const openCreateIntakeModal = () => {
    isEditingIntake.value = false;
    editingIntakeId.value = null;
    intakeForm.course_id = props.courses.length > 0 ? props.courses[0].id : null;
    intakeForm.name = '';
    intakeForm.start_date = '';
    intakeForm.end_date = '';
    intakeForm.capacity = 20;
    intakeForm.status = 'draft';
    intakeForm.instructor_id = null;
    instructorSearch.value = '';
    intakeModalOpen.value = true;
};

const openEditIntakeModal = (intake) => {
    isEditingIntake.value = true;
    editingIntakeId.value = intake.id;
    intakeForm.course_id = intake.course_id;
    intakeForm.name = intake.name;
    intakeForm.start_date = formatDateForInput(intake.start_date);
    intakeForm.end_date = formatDateForInput(intake.end_date);
    intakeForm.capacity = intake.capacity;
    intakeForm.status = intake.status;
    intakeForm.instructor_id = intake.instructor_id;
    
    if (intake.instructor_id) {
        const ins = props.instructors.find(i => i.id === intake.instructor_id);
        if (ins) {
            instructorSearch.value = `${ins.name} — ${ins.role_name} (${ins.unit_code})`;
        } else {
            instructorSearch.value = '';
        }
    } else {
        instructorSearch.value = '';
    }
    intakeModalOpen.value = true;
};

const saveIntake = () => {
    if (isEditingIntake.value) {
        Inertia.put(route('course-intakes.update', editingIntakeId.value), intakeForm, {
            onSuccess: () => {
                intakeModalOpen.value = false;
            }
        });
    } else {
        Inertia.post(route('course-intakes.store'), intakeForm, {
            onSuccess: () => {
                intakeModalOpen.value = false;
            }
        });
    }
};

// Helpers
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
};
</script>
