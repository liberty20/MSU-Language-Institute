<template>
    <Head title="Short Courses Public Information Portal" />

    <div class="min-h-screen bg-gray-50 text-gray-800 font-sans selection:bg-brand-gold selection:text-[#0a1f44]">
        <!-- Premium Navbar Header -->
        <header class="bg-[#0a1f44] border-b border-brand-gold/25 sticky top-0 z-40 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3.5 group">
                    <img src="/msu-logo-2.png" alt="MSU Logo" class="h-11 sm:h-14 w-auto object-contain bg-white p-1 rounded-lg shadow-sm group-hover:scale-105 transition-transform duration-300" />
                    <div>
                        <h1 class="text-white font-black text-base sm:text-lg tracking-wide leading-none group-hover:text-brand-gold transition-colors">MSUNLI</h1>
                        <p class="text-[0.6rem] sm:text-[0.65rem] text-brand-gold uppercase tracking-wider font-bold mt-0.5">National Language Institute</p>
                    </div>
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center gap-6">
                    <Link href="/" class="text-sm font-semibold text-gray-200 hover:text-brand-gold transition">
                        Home
                    </Link>
                    <a href="#catalog" class="text-sm font-semibold text-gray-200 hover:text-brand-gold transition">
                        Course Catalog
                    </a>
                    <a href="#faq" class="text-sm font-semibold text-gray-200 hover:text-brand-gold transition">
                        FAQ
                    </a>
                    <Link v-if="!$page.props.auth.user" :href="canLogin ? route('login') : '/login'" 
                          class="bg-brand-gold hover:bg-yellow-400 text-[#0a1f44] font-bold px-5 py-2 rounded-full transition shadow-md text-xs uppercase tracking-wider">
                        Portal Login
                    </Link>
                    <Link v-else :href="route('dashboard')" 
                          class="bg-brand-gold hover:bg-yellow-400 text-[#0a1f44] font-bold px-5 py-2 rounded-full transition shadow-md text-xs uppercase tracking-wider">
                        Go to Dashboard
                    </Link>
                </div>

                <!-- Hamburger Button for Mobile -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-200 hover:text-brand-gold focus:outline-none p-1.5 rounded-lg transition-colors" aria-label="Toggle Navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation Menu -->
            <div v-show="mobileMenuOpen" class="md:hidden bg-[#0a1f44] border-t border-brand-gold/15 shadow-xl px-6 py-4 space-y-4">
                <Link href="/" @click="mobileMenuOpen = false" class="block text-sm font-semibold text-gray-200 hover:text-brand-gold transition">
                    Home
                </Link>
                <a href="#catalog" @click="mobileMenuOpen = false" class="block text-sm font-semibold text-gray-200 hover:text-brand-gold transition">
                    Course Catalog
                </a>
                <a href="#faq" @click="mobileMenuOpen = false" class="block text-sm font-semibold text-gray-200 hover:text-brand-gold transition">
                    FAQ
                </a>
                <div class="border-t border-brand-gold/15 pt-4">
                    <Link v-if="!$page.props.auth.user" :href="canLogin ? route('login') : '/login'" @click="mobileMenuOpen = false"
                          class="block w-full text-center bg-brand-gold hover:bg-yellow-400 text-[#0a1f44] font-bold px-5 py-2.5 rounded-full transition shadow-md text-xs uppercase tracking-wider">
                        Portal Login
                    </Link>
                    <Link v-else :href="route('dashboard')" @click="mobileMenuOpen = false"
                          class="block w-full text-center bg-brand-gold hover:bg-yellow-400 text-[#0a1f44] font-bold px-5 py-2.5 rounded-full transition shadow-md text-xs uppercase tracking-wider">
                        Go to Dashboard
                    </Link>
                </div>
            </div>
        </header>

        <!-- 1. Hero Section -->
        <section class="relative py-24 text-white overflow-hidden border-b border-brand-gold/15 bg-cover bg-center" style="background-image: url('/lang-image.jpg');">
            <!-- Dark Overlay (55% opacity) -->
            <div class="absolute inset-0 bg-black/55 z-0"></div>
            
            <div class="absolute right-0 top-0 translate-x-12 -translate-y-12 opacity-10 bg-yellow-400 w-96 h-96 rounded-full blur-3xl"></div>
            <div class="absolute left-0 bottom-0 -translate-x-12 translate-y-12 opacity-5 bg-blue-400 w-80 h-80 rounded-full blur-3xl"></div>
            
            <div class="max-w-5xl mx-auto px-6 text-center space-y-8 relative z-10">
                <span class="inline-block py-1.5 px-4 rounded-full bg-brand-gold/10 text-brand-gold text-xs font-black tracking-widest uppercase mb-2 border border-brand-gold/20 backdrop-blur-sm">
                    MSUNLI Short Courses Programme
                </span>
                <h2 class="text-4xl md:text-6xl font-black tracking-tight leading-none text-white max-w-4xl mx-auto drop-shadow-md" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
                    Master Languages &amp; Professional Competence
                </h2>
                <p class="text-gray-200 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed font-bold drop-shadow" style="text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);">
                    Expand your global outlook and advance your career. We offer highly certified, industry-recognized short courses spanning official languages, special needs communication, and translation competence.
                </p>
                <div class="pt-4 flex flex-wrap justify-center gap-4">
                    <a href="#catalog" class="bg-brand-gold hover:bg-yellow-400 text-[#0a1f44] font-extrabold px-8 py-3.5 rounded-full transition shadow-lg transform hover:-translate-y-0.5 text-sm uppercase tracking-wider">
                        Apply Now
                    </a>
                    <a href="#catalog" class="bg-white/10 hover:bg-white/15 border border-white/20 text-white font-extrabold px-8 py-3.5 rounded-full transition shadow text-sm uppercase tracking-wider backdrop-blur-sm">
                        View Available Courses
                    </a>
                    <a href="#contact" class="bg-transparent hover:text-brand-gold text-gray-200 font-extrabold px-6 py-3.5 rounded-full transition text-sm uppercase tracking-wider">
                        Contact Us
                    </a>
                </div>
            </div>
        </section>

        <!-- 2. Available Courses Catalogue -->
        <section id="catalog" class="py-20 max-w-7xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="inline-block py-1 px-3.5 rounded-full bg-[#0a1f44]/10 text-[#0a1f44] text-xs font-black tracking-widest uppercase mb-3">
                    Course Registry
                </span>
                <h3 class="text-[#0a1f44] font-black text-3xl md:text-4xl mb-3">Explore Available Courses</h3>
                <p class="text-gray-500 text-sm">Use the search bar and filter tags to find language or professional competency programs tailored for you.</p>
            </div>

            <!-- Controls: Search and Filters -->
            <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm space-y-4 mb-10">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Search Input -->
                    <div class="md:col-span-1 relative">
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Search by name, description, code..." 
                            class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm pl-4 pr-10 py-2.5"
                        />
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                    <!-- Category Select -->
                    <div>
                        <select v-model="selectedCategory" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm py-2.5">
                            <option value="">All Categories</option>
                            <option v-for="cat in uniqueCategories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>
                    <!-- Level Select -->
                    <div>
                        <select v-model="selectedLevel" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm py-2.5">
                            <option value="">All Levels</option>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>
                    <!-- Status Select -->
                    <div>
                        <select v-model="selectedStatus" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold text-sm py-2.5">
                            <option value="">All Statuses</option>
                            <option value="Open">Open for Registration</option>
                            <option value="Closed">Registration Closed</option>
                            <option value="Full">Class Full</option>
                            <option value="Upcoming">Upcoming Intake</option>
                        </select>
                    </div>
                </div>

                <!-- Active Filter Tags -->
                <div class="flex flex-wrap items-center gap-2 pt-2 text-xs">
                    <span class="text-gray-400 font-bold uppercase tracking-wider mr-2">Quick Filters:</span>
                    <button 
                        @click="selectedCategory = ''; selectedLevel = ''; selectedStatus = ''; searchQuery = '';" 
                        class="px-3 py-1 rounded-full border border-gray-200 hover:bg-gray-50 text-gray-600 transition"
                        v-if="selectedCategory || selectedLevel || selectedStatus || searchQuery"
                    >
                        Clear All Filters
                    </button>
                    <button 
                        v-for="cat in quickFilterCategories" 
                        :key="cat"
                        @click="selectedCategory = cat"
                        class="px-3.5 py-1 rounded-full transition font-semibold"
                        :class="selectedCategory === cat ? 'bg-[#0a1f44] text-[#f5c242]' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- Catalogue Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="course in filteredCourses" :key="course.id" class="bg-white rounded-3xl shadow-sm border border-gray-150 overflow-hidden flex flex-col hover:shadow-md hover:border-brand-gold/30 transition duration-300 group">
                    <div class="p-8 flex-grow space-y-4">
                        <div class="flex justify-between items-start">
                            <span class="px-3 py-0.5 text-[0.65rem] font-black bg-brand-gold/10 text-brand-gold-dark border border-brand-gold/20 rounded-full uppercase tracking-widest">
                                {{ course.category }}
                            </span>
                            <span class="px-3 py-0.5 text-[0.65rem] font-black rounded-full uppercase tracking-wider border"
                                :class="{
                                    'bg-green-50 text-green-700 border-green-200': getCourseStatus(course.id) === 'Open',
                                    'bg-amber-50 text-amber-700 border-amber-200': getCourseStatus(course.id) === 'Full',
                                    'bg-blue-50 text-blue-700 border-blue-200': getCourseStatus(course.id) === 'Upcoming',
                                    'bg-gray-100 text-gray-500 border-gray-250': getCourseStatus(course.id) === 'Closed'
                                }">
                                {{ 
                                    getCourseStatus(course.id) === 'Open' ? 'Open for Registration' :
                                    getCourseStatus(course.id) === 'Full' ? 'Class Full' :
                                    getCourseStatus(course.id) === 'Upcoming' ? 'Upcoming Intake' : 'Registration Closed'
                                }}
                            </span>
                        </div>
                        <div>
                            <h4 class="text-xl font-black text-[#0a1f44] leading-snug group-hover:text-brand-gold-dark transition-colors duration-300" :title="course.title">
                                {{ course.title }}
                            </h4>
                            <p class="text-xs text-gray-400 font-bold mt-1 uppercase tracking-wider">Code: {{ course.code }} | {{ course.duration_weeks }} Weeks</p>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-3 leading-relaxed">{{ course.description || 'Gain standard language proficiency and technical competency designed by national language experts.' }}</p>
                        
                        <!-- Dynamic Start and End Dates -->
                        <div v-if="getActiveIntake(course.id)" class="bg-gray-50 p-4 rounded-2xl border border-gray-150 space-y-2 text-xs text-gray-650 font-medium">
                            <div class="flex justify-between">
                                <span class="text-gray-400 font-semibold">Start Date:</span>
                                <span class="text-gray-800 font-bold">{{ formatDate(getActiveIntake(course.id)?.start_date) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 font-semibold">End Date:</span>
                                <span class="text-gray-800 font-bold">{{ formatDate(getActiveIntake(course.id)?.end_date) }}</span>
                            </div>
                        </div>
                        <div v-else class="bg-gray-50 p-4 rounded-2xl border border-gray-150 text-xs text-gray-400 italic text-center">
                            No active intake dates scheduled.
                        </div>

                        <div class="pt-5 border-t border-gray-50 flex justify-between items-center text-xs font-bold text-gray-500">
                            <div>
                                <p class="text-[0.6rem] text-gray-400 uppercase tracking-widest">Tuition Fee</p>
                                <p class="text-base font-black text-[#0a1f44] mt-0.5">{{ course.currency }} {{ Number(course.price).toLocaleString(undefined, {minimumFractionDigits: 2}) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[0.6rem] text-gray-400 uppercase tracking-widest">Delivery Mode</p>
                                <p class="text-xs text-gray-700 mt-0.5">Physical / Hybrid</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <button @click="showCourseDetails(course)" class="text-xs font-black text-[#0a1f44] hover:text-brand-gold transition uppercase tracking-wider">
                            View Syllabus &amp; Details
                        </button>
                        <!-- Dynamic Apply Button linked directly to the open intake modal -->
                        <button 
                            v-if="getCourseStatus(course.id) === 'Open'"
                            @click="openEnrollModal(getActiveIntake(course.id))" 
                            class="bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black px-5 py-2.5 rounded-full text-xs transition uppercase tracking-wider shadow"
                        >
                            Apply Now
                        </button>
                        <button 
                            v-else-if="getCourseStatus(course.id) === 'Full'"
                            disabled 
                            class="bg-amber-100 text-amber-700 font-black px-5 py-2.5 rounded-full text-xs cursor-not-allowed uppercase tracking-wider border border-amber-200"
                        >
                            Class Full
                        </button>
                        <button 
                            v-else-if="getCourseStatus(course.id) === 'Upcoming'"
                            disabled 
                            class="bg-blue-50 text-blue-700 font-black px-5 py-2.5 rounded-full text-xs cursor-not-allowed uppercase tracking-wider border border-blue-150"
                        >
                            Upcoming
                        </button>
                        <button 
                            v-else
                            disabled 
                            class="bg-gray-100 text-gray-400 font-black px-5 py-2.5 rounded-full text-xs cursor-not-allowed uppercase tracking-wider"
                        >
                            Closed
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredCourses.length === 0" class="col-span-full bg-white p-16 rounded-3xl border border-gray-150 text-center shadow-sm max-w-lg mx-auto">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h4 class="font-extrabold text-[#0a1f44] text-lg">No Matching Courses</h4>
                    <p class="text-sm text-gray-500 mt-1">Adjust your search keyword or selected category filter.</p>
                </div>
            </div>
        </section>

        <!-- 3. Course Details Modal -->
        <div v-if="detailsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="detailsModalOpen = false">
            <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="bg-[#0a1f44] text-white px-8 py-5 flex justify-between items-center border-b border-brand-gold/25">
                    <div>
                        <span class="px-2.5 py-0.5 text-[0.6rem] bg-brand-gold/15 text-brand-gold border border-brand-gold/30 rounded font-black uppercase tracking-widest">{{ selectedCourse.category }}</span>
                        <h3 class="text-lg font-black mt-1">{{ selectedCourse.title }} ({{ selectedCourse.code }})</h3>
                    </div>
                    <button @click="detailsModalOpen = false" class="text-gray-300 hover:text-white transition p-1 rounded-full hover:bg-white/5">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-8 max-h-[70vh] overflow-y-auto space-y-6 text-sm leading-relaxed text-gray-600">
                    <div>
                        <h4 class="font-black text-[#0a1f44] text-xs uppercase tracking-wider mb-2">Program Overview</h4>
                        <p>{{ selectedCourse.description || 'This course is tailored to provide core linguistic competency, structural fluency, and cultural awareness. Handled by native experts and certified instructors from Midlands State University.' }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100">
                        <div>
                            <h4 class="font-black text-[#0a1f44] text-xs uppercase tracking-wider mb-2">Learning Outcomes</h4>
                            <ul class="list-disc pl-5 space-y-1 text-xs">
                                <li>Develop fundamental vocabulary and core conversational competence.</li>
                                <li>Master syntax, phraseology, and language application models.</li>
                                <li>Acquire professional translation and cultural interpretation skills.</li>
                                <li>Satisfy both coursework and practical assessment modules.</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-black text-[#0a1f44] text-xs uppercase tracking-wider mb-2">Entry Requirements</h4>
                            <p class="text-xs">Open to all community members, professionals, and students. A minimum of secondary school education is recommended for advanced language structure modules.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100 text-xs">
                        <div>
                            <h4 class="font-black text-[#0a1f44] text-xs uppercase tracking-wider mb-2">Assessment &amp; Certification</h4>
                            <p>Calculated through **Continuous Assessment (CA)** including quizzes, tests (50%), and standard coursework **Assignments (50%)**. Upon completion, a certificate of competence is issued.</p>
                        </div>
                        <div>
                            <h4 class="font-black text-[#0a1f44] text-xs uppercase tracking-wider mb-2">Course Coordination</h4>
                            <p>Managed directly by the **{{ selectedCourse.department ? selectedCourse.department.name : 'MSU National Language Institute' }}**. Assigned to certified native linguists and lecturers.</p>
                        </div>
                    </div>
                </div>
                <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="detailsModalOpen = false" class="px-5 py-2.5 rounded-full border border-gray-300 font-bold text-gray-700 hover:bg-gray-100 transition text-xs uppercase tracking-wider">Close Details</button>
                    <button v-if="hasOpenIntake(selectedCourse.id)" @click="enrollFromModal" class="px-6 py-2.5 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black transition shadow text-xs uppercase tracking-wider">Enroll Now</button>
                </div>
            </div>
        </div>

        <!-- 4. Application Process Section -->
        <section class="py-20 bg-white border-t border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="inline-block py-1 px-3.5 rounded-full bg-brand-gold/10 text-brand-gold-dark text-xs font-black tracking-widest uppercase mb-3">
                        LIFECYCLE
                    </span>
                    <h3 class="text-[#0a1f44] font-black text-3xl md:text-4xl mb-3">The Enrollment Process</h3>
                    <p class="text-gray-500 text-sm">We maintain a transparent 10-step institutional review workflow to register and confirm student admissions.</p>
                </div>

                <!-- 10 Step Process Steps -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                    <div v-for="(step, index) in steps" :key="index" class="bg-gray-50 p-6 rounded-2xl border border-gray-150 relative shadow-sm hover:shadow-md transition">
                        <div class="absolute -top-4 left-6 w-8 h-8 rounded-full bg-[#0a1f44] text-[#f5c242] font-black flex items-center justify-center border border-brand-gold text-xs shadow-md">
                            {{ index + 1 }}
                        </div>
                        <h4 class="font-bold text-gray-800 text-sm mt-2 mb-1.5">{{ step.title }}</h4>
                        <p class="text-xs text-gray-500 leading-relaxed">{{ step.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. Course Fees & Payment Info -->
        <section class="py-20 bg-gray-50 border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6">
                        <span class="inline-block py-1 px-3.5 rounded-full bg-brand-gold/10 text-brand-gold-dark text-xs font-black tracking-widest uppercase border border-brand-gold/20">
                            PAYMENTS
                        </span>
                        <h3 class="text-[#0a1f44] font-black text-3xl md:text-4xl leading-tight">
                            Course Fees &amp; Tuition Payment
                        </h3>
                        <p class="text-gray-600 leading-relaxed text-sm">
                            Tuition fees vary per course batch and are configured dynamically by the Executive Directorate. All payments must be processed directly into the MSU National Language Institute {{ bankingDetails.bank }} accounts listed. Once paid, remember to upload your deposit slip or proof of transfer in the application form below.
                        </p>
                        
                        <div class="bg-amber-50 p-5 rounded-2xl border border-amber-200 text-amber-900 text-xs leading-relaxed space-y-2">
                            <h5 class="font-black uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-[#f5c242]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                Verification Disclaimer
                            </h5>
                            <p>Proofs of payment undergo mandatory double-verification by our Administrative Assistant and the ICT Administrator. Intentional upload of fraudulent deposit clips will result in permanent suspension and cancellation of access.</p>
                        </div>
                    </div>

                    <!-- Banking Details Card -->
                    <div class="bg-white p-8 rounded-3xl border border-gray-150 shadow-md space-y-5">
                        <div class="border-b border-gray-100 pb-4">
                            <h4 class="font-black text-[#0a1f44] text-base">{{ bankingDetails.bank }} Accounts Details</h4>
                            <p class="text-xs text-gray-400">Official Institutional Deposit Channels</p>
                        </div>
                        <div class="space-y-3.5 text-xs text-gray-600">
                            <div class="flex justify-between py-1.5 border-b border-gray-50">
                                <span class="font-bold text-gray-400">Account Name:</span>
                                <span class="font-extrabold text-gray-800 text-right">{{ bankingDetails.account_name || 'Midlands State University' }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-50">
                                <span class="font-bold text-gray-400">Bank Name:</span>
                                <span class="font-bold text-gray-800">{{ bankingDetails.bank }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-50">
                                <span class="font-bold text-gray-400">Branch Name:</span>
                                <span class="font-bold text-gray-800">{{ bankingDetails.branch }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-50">
                                <span class="font-bold text-gray-400">Local Currency Account (ZiG):</span>
                                <span class="font-black text-[#0a1f44]">{{ bankingDetails.account_number }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-50">
                                <span class="font-bold text-gray-400">Nostro USD Account:</span>
                                <span class="font-black text-blue-700">{{ bankingDetails.nostro_number }}</span>
                            </div>
                            <div class="flex justify-between py-1.5 border-b border-gray-50">
                                <span class="font-bold text-gray-400">Accepted Currencies:</span>
                                <span class="font-bold text-gray-800">{{ bankingDetails.currency_accepted }}</span>
                            </div>
                            <div class="flex justify-between py-1.5">
                                <span class="font-bold text-gray-400">Payment Modes:</span>
                                <span class="font-bold text-gray-800">ZIPIT, RTGS, Cash Deposits, Nostro Transfer</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>



        <!-- 7. FAQ Section -->
        <section id="faq" class="py-20 bg-white border-t border-b border-gray-100">
            <div class="max-w-4xl mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="inline-block py-1 px-3.5 rounded-full bg-brand-gold/10 text-brand-gold-dark text-xs font-black tracking-widest uppercase mb-3">
                        SUPPORT
                    </span>
                    <h3 class="text-[#0a1f44] font-black text-3xl md:text-4xl mb-3">Frequently Asked Questions</h3>
                    <p class="text-gray-500 text-sm">Find answers to commonly asked questions regarding registrations, payments, and timelines.</p>
                </div>

                <div class="space-y-4">
                    <div v-for="(faq, i) in faqs" :key="i" class="border border-gray-150 rounded-2xl overflow-hidden bg-gray-50 shadow-sm transition-all duration-300">
                        <button 
                            @click="toggleFaq(i)"
                            class="w-full px-6 py-4 text-left font-bold text-[#0a1f44] flex justify-between items-center text-sm focus:outline-none"
                        >
                            <span>{{ faq.question }}</span>
                            <span class="transition-transform duration-300" :class="activeFaq === i ? 'rotate-180' : ''">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </button>
                        <div 
                            v-show="activeFaq === i"
                            class="px-6 pb-5 pt-1 text-xs text-gray-600 leading-relaxed border-t border-gray-150 bg-white animate-in slide-in-from-top-1 duration-200"
                        >
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. Student Testimonials Section -->
        <section class="py-20 bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="inline-block py-1 px-3.5 rounded-full bg-[#0a1f44]/10 text-brand-blue text-xs font-black tracking-widest uppercase mb-3">
                        SUCCESS STORIES
                    </span>
                    <h3 class="text-[#0a1f44] font-black text-3xl md:text-4xl mb-3">Student Testimonials</h3>
                    <p class="text-gray-500 text-sm">Discover experiences and success stories of professionals and community advocates who trained at MSULI.</p>
                </div>



                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="(t, idx) in testimonials" :key="idx" class="bg-gray-50 p-8 rounded-3xl border border-gray-150 shadow-sm hover:shadow-md transition duration-300 relative flex flex-col justify-between">
                        <!-- Admin Delete Button -->
                        <button 
                            v-if="$page.props.auth.user && ($page.props.auth.roles.includes('ict_administrator') || $page.props.auth.roles.includes('executive_director'))" 
                            @click="deleteTestimonial(t.id)" 
                            class="absolute top-6 right-6 text-red-500 hover:text-red-700 transition p-1.5 hover:bg-red-50 rounded-full z-20"
                            title="Delete Testimony"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>

                        <span class="text-4xl text-brand-gold/30 font-serif absolute top-3 left-4">“</span>
                        <p class="text-xs text-gray-600 leading-relaxed italic z-10 relative pt-4 mb-6">
                            {{ t.text }}
                        </p>
                        <div class="border-t border-gray-200/60 pt-4 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-[#0a1f44] text-brand-gold flex items-center justify-center font-bold text-sm">
                                {{ t.name.charAt(0) }}
                            </div>
                            <div>
                                <h5 class="font-bold text-gray-800 text-xs">{{ t.name }}</h5>
                                <p class="text-[0.65rem] text-gray-400 font-semibold uppercase mt-0.5">{{ t.course }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="contact" class="bg-[#0a1f44] text-white pt-16 mt-auto border-t border-brand-gold/25">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pb-12 grid grid-cols-1 md:grid-cols-3 gap-12 text-left">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <img src="/msu-logo-2.png" alt="MSU Logo" class="h-10 w-auto bg-white p-1 rounded shadow-sm" />
                        <div>
                            <h2 class="text-lg font-black tracking-wider text-white">MSUNLI</h2>
                        </div>
                    </div>
                    <p class="text-gray-400 text-xs leading-relaxed max-w-sm">
                        Have questions about enrollment eligibility, offline tuition payment modes, corporate cohort training, or specialized language support? Feel free to contact our administrative desk during working hours.
                    </p>
                </div>
                <div>
                    <h3 class="text-[#f5c242] font-bold uppercase tracking-wider text-xs mb-6">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><Link :href="route('login')" class="text-gray-400 hover:text-white transition text-xs">Portal Login</Link></li>
                        <li><a href="#" @click.prevent="scrollToCatalog" class="text-gray-400 hover:text-[#f5c242] transition text-xs">Available Courses</a></li>
                        <li><button @click="showAbout = true" class="text-gray-400 hover:text-white transition text-xs text-left cursor-pointer">About Us</button></li>
                        <li><button @click="showTerms = true" class="text-gray-400 hover:text-white transition text-xs text-left cursor-pointer">Terms &amp; Conditions</button></li>
                        <li><button @click="showPrivacy = true" class="text-gray-400 hover:text-white transition text-xs text-left cursor-pointer">Privacy Policy</button></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-[#f5c242] font-bold uppercase tracking-wider text-xs mb-6">Contact Us</h3>
                    <ul class="space-y-3 text-xs text-gray-400">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ contactInfo.location || 'MSU Graduate School of Business Leadership Campus, Language Institute Gweru, Zimbabwe' }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <a :href="'mailto:' + contactInfo.email" class="hover:text-white transition text-xs">{{ contactInfo.email }}</a>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>
                                Telephone: <a :href="'tel:' + contactInfo.phone" class="hover:text-white transition text-xs font-semibold">{{ contactInfo.phone }}</a><br/>
                                Mobile: <a :href="'tel:' + contactInfo.mobile" class="hover:text-white transition text-xs font-semibold">{{ contactInfo.mobile }}</a>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Footer Strip -->
            <div class="h-2 w-full flex">
                <div class="h-full flex-1 bg-green-600"></div>
                <div class="h-full flex-1 bg-yellow-400"></div>
                <div class="h-full flex-1 bg-red-600"></div>
                <div class="h-full flex-1 bg-black"></div>
                <div class="h-full flex-1 bg-red-600"></div>
                <div class="h-full flex-1 bg-yellow-400"></div>
                <div class="h-full flex-1 bg-green-600"></div>
            </div>
            <div class="bg-black/90 py-4 text-center text-xs text-gray-500">
                &copy; {{ new Date().getFullYear() }} Midlands State University. All rights reserved.
            </div>
        </footer>

        <!-- Application Modal -->
        <div v-if="enrollModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeEnrollModal">
            <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="bg-[#0a1f44] text-white px-8 py-5 flex justify-between items-center border-b border-brand-gold/25">
                    <div>
                        <span class="px-2.5 py-0.5 text-[0.6rem] bg-brand-gold/15 text-brand-gold border border-brand-gold/30 rounded font-black uppercase tracking-widest">Admissions Entry</span>
                        <h3 class="text-lg font-black mt-1">Application for: {{ selectedIntake.course.title }}</h3>
                        <p class="text-[10px] text-gray-300 mt-0.5 uppercase font-bold tracking-wider">Cohort: {{ selectedIntake.name }}</p>
                    </div>
                    <button @click="closeEnrollModal" class="text-gray-300 hover:text-white transition p-1 rounded-full hover:bg-white/5">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submitEnrollment" class="p-8 space-y-4 max-h-[70vh] overflow-y-auto text-xs">
                    <!-- Status Banners inside modal -->
                    <div v-if="flashSuccess" class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-xl shadow-sm font-semibold">
                        {{ flashSuccess }}
                    </div>
                    <div v-if="flashError" class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-xl shadow-sm font-semibold">
                        {{ flashError }}
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">Applying For</label>
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-150 font-bold text-brand-blue flex justify-between items-center">
                                <span>{{ selectedIntake.course.title }} ({{ selectedIntake.name }})</span>
                                <span class="text-xs text-brand-gold-dark">{{ selectedIntake.course.currency }} {{ Number(selectedIntake.course.price).toLocaleString() }}</span>
                            </div>
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">Student Full Name *</label>
                            <input v-model="form.full_name" type="text" required class="w-full text-xs rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold" placeholder="e.g. John Doe" />
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">National ID / Passport Number *</label>
                            <input v-model="form.national_id_number" type="text" required class="w-full text-xs rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold" placeholder="e.g. 63-1234567-A-80" />
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">Email Address *</label>
                            <input v-model="form.email" type="email" required class="w-full text-xs rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold" placeholder="e.g. john@gmail.com" />
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">Phone Number *</label>
                            <input v-model="form.phone" type="text" required class="w-full text-xs rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold" placeholder="e.g. +263 772 111 222" />
                        </div>
                        
                        <div class="col-span-2">
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">Physical Address *</label>
                            <input v-model="form.physical_address" type="text" required class="w-full text-xs rounded-xl border-gray-300 shadow-sm focus:border-brand-gold focus:ring-brand-gold" placeholder="e.g. 123 Main Street, Gweru" />
                        </div>

                        <!-- ID File Upload -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">Upload National ID Copy *</label>
                            <input type="file" @change="handleIdUpload" required class="w-full text-[10px] text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:bg-[#0a1f44]/15 file:text-[#0a1f44] hover:file:bg-[#0a1f44]/25" />
                            <span class="text-[9px] text-gray-400 block mt-0.5">PDF or image format, max 10MB</span>
                        </div>

                        <!-- Payment Proof Upload -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-700 uppercase tracking-wide mb-1">Upload Proof of Payment *</label>
                            <input type="file" @change="handlePaymentUpload" required class="w-full text-[10px] text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:bg-[#0a1f44]/15 file:text-[#0a1f44] hover:file:bg-[#0a1f44]/25" />
                            <span class="text-[9px] text-gray-400 block mt-0.5">PDF or image format, max 10MB</span>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-gray-150 flex justify-end gap-3">
                        <button type="button" @click="closeEnrollModal" class="px-5 py-2.5 rounded-full border border-gray-300 font-bold text-gray-700 hover:bg-gray-50 transition text-[10px] uppercase tracking-wider">Cancel</button>
                        <button type="submit" :disabled="submitting" class="px-6 py-2.5 rounded-full bg-[#0a1f44] hover:bg-[#0c2859] text-[#f5c242] font-black transition shadow text-[10px] uppercase tracking-wider disabled:opacity-50">
                            {{ submitting ? 'Submitting Application...' : 'Submit Application' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modals for About, Terms, Privacy -->
        <!-- About Us Modal -->
        <div v-if="showAbout" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200" @click.self="showAbout = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[85vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-8 py-5 flex justify-between items-center z-10">
                    <h2 class="text-2xl font-black text-[#0a1f44]">About Us</h2>
                    <button @click="showAbout = false" class="text-gray-400 hover:text-red-500 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-8 prose prose-blue max-w-none text-gray-650 font-medium">
                    <p>Welcome to the Midlands State University National Language Institute (MSUNLI) Integrated Language Services and Operations Management System. MSUNLI is a national centre of excellence dedicated to promoting language development, research, innovation, translation, brailling, consultancy, and sign language services across Zimbabwe’s sixteen officially recognized languages.</p>
                    <p class="mt-4">Our mission is to enhance communication, inclusivity, and accessibility through professional language services supported by modern digital solutions. This platform was developed to streamline service delivery, improve operational efficiency, strengthen collaboration, and provide clients with a centralized and transparent system for accessing language-related services.</p>
                    <p class="mt-4">Through this system, clients can conveniently submit service requests, track progress, receive quotations, and engage with qualified language experts while enabling the Institute to maintain high standards of accountability, quality, and performance monitoring.</p>
                    
                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <h4 class="text-lg font-bold text-[#0a1f44] mb-2">Supported Official Languages of Zimbabwe</h4>
                        <p class="text-xs text-gray-500 mb-6">MSUNLI actively supports translation, research, resource development, and professional short courses across all 16 official languages:</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div v-for="lang in zimbabweanLanguages" :key="lang" class="flex items-center gap-2.5 p-3 bg-gray-50 rounded-xl border border-gray-100 hover:border-[#f5c242] hover:bg-[#0a1f44]/5 transition-all duration-300 group">
                                <span class="w-2 h-2 rounded-full bg-[#f5c242] group-hover:scale-125 transition-transform"></span>
                                <span class="text-xs font-semibold text-gray-700 group-hover:text-[#0a1f44] transition-colors">{{ lang }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms & Conditions Modal -->
        <div v-if="showTerms" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200" @click.self="showTerms = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-8 py-5 flex justify-between items-center z-10">
                    <h2 class="text-2xl font-black text-[#0a1f44]">Terms & Conditions</h2>
                    <button @click="showTerms = false" class="text-gray-400 hover:text-red-500 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-8 prose prose-blue max-w-none text-gray-650 font-medium space-y-4">
                    <p>By accessing and using the MSUNLI Integrated Language Services and Operations Management System, you agree to comply with the following terms and conditions:</p>
                    <ol class="list-decimal pl-5 space-y-2 text-xs">
                        <li>The platform is intended for authorized use by MSUNLI staff, stakeholders, clients, and approved service providers only.</li>
                        <li>Users are responsible for providing accurate and complete information when submitting service requests, quotations, or documentation.</li>
                        <li>All service requests submitted through the platform are subject to review, approval processes, and institutional policies of Midlands State University National Language Institute.</li>
                        <li>Unauthorized access, misuse of the system, or attempts to compromise system security are strictly prohibited and may result in disciplinary or legal action.</li>
                        <li>MSUNLI reserves the right to modify, suspend, or update system functionalities and services without prior notice when necessary for operational improvements or security purposes.</li>
                        <li>All uploaded documents, translations, reports, and communications within the system remain subject to confidentiality, institutional regulations, and applicable intellectual property laws.</li>
                        <li>Users are responsible for maintaining the confidentiality of their login credentials and account activities.</li>
                        <li>The Institute does not guarantee uninterrupted availability of the platform due to possible maintenance, infrastructure limitations, or technical failures.</li>
                        <li>Continued use of the system constitutes acceptance of these terms and conditions.</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Privacy Policy Modal -->
        <div v-if="showPrivacy" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200" @click.self="showPrivacy = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-8 py-5 flex justify-between items-center z-10">
                    <h2 class="text-2xl font-black text-[#0a1f44]">Privacy Policy</h2>
                    <button @click="showPrivacy = false" class="text-gray-400 hover:text-red-500 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-8 prose prose-blue max-w-none text-gray-650 font-medium space-y-4">
                    <p>The Midlands State University National Language Institute (MSUNLI) is committed to protecting the privacy, confidentiality, and security of all users and information processed through this platform.</p>
                    <ol class="list-decimal pl-5 space-y-2 text-xs">
                        <li>The system collects and stores information necessary for managing language service requests, operational workflows, reporting, and communication purposes.</li>
                        <li>Personal information such as names, contact details, uploaded documents, and service records will only be used for legitimate institutional and operational purposes.</li>
                        <li>MSUNLI implements role-based access controls and security measures to protect user data from unauthorized access, disclosure, alteration, or loss.</li>
                        <li>Information submitted through the platform will not be shared with unauthorized third parties except where required by law, institutional policy, or official service delivery processes.</li>
                        <li>Users are responsible for safeguarding their account credentials and immediately reporting any suspected unauthorized access.</li>
                        <li>System activity, transactions, and operational records may be logged for security monitoring, auditing, performance measurement, and service improvement purposes.</li>
                        <li>MSUNLI may update this Privacy Policy periodically to align with institutional requirements, legal obligations, and technological developments.</li>
                        <li>By using this platform, users consent to the collection, processing, and secure storage of information necessary for the effective delivery of MSUNLI services.</li>
                    </ol>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { ref, reactive, computed } from 'vue';

const props = defineProps({
    courses: Array,
    intakes: Array,
    faqs: Array,
    testimonials: Array,
    pendingTestimonials: Array,
    announcements: Array,
    contactInfo: Object,
    bankingDetails: Object,
    canLogin: Boolean,
});

// Search and Filtering State
const searchQuery = ref('');
const selectedCategory = ref('');
const selectedLevel = ref('');
const selectedStatus = ref('');

const showAbout = ref(false);
const showTerms = ref(false);
const showPrivacy = ref(false);
const mobileMenuOpen = ref(false);

const zimbabweanLanguages = [
    'Chewa', 'ChiBarwe', 'English', 'Kalanga', 'Koisan', 'Nambya', 'Ndau', 'Ndebele',
    'Shangani', 'Shona', 'Sign Language', 'Sotho', 'Tonga', 'Tswana', 'Venda', 'Xhosa'
];

const scrollToCatalog = () => {
    document.getElementById('catalog')?.scrollIntoView({ behavior: 'smooth' });
};

// Dynamically retrieve unique categories from database courses
const uniqueCategories = computed(() => {
    const categories = props.courses.map(c => c.category).filter(Boolean);
    return [...new Set(categories)].sort();
});

const quickFilterCategories = computed(() => {
    return uniqueCategories.value.slice(0, 5);
});

// Get the active/open intake for a course
const getActiveIntake = (courseId) => {
    const courseIntakes = props.intakes.filter(i => i.course_id === courseId);
    return courseIntakes.find(i => i.status === 'open') || courseIntakes[0] || null;
};

// Get the dynamic status of a course: Open, Closed, Full, Upcoming
const getCourseStatus = (courseId) => {
    const courseIntakes = props.intakes.filter(i => i.course_id === courseId);
    if (courseIntakes.length === 0) return 'Closed';
    
    const openIntake = courseIntakes.find(i => i.status === 'open');
    if (openIntake) {
        const verifiedCount = openIntake.enrollments 
            ? openIntake.enrollments.filter(e => e.payment_status === 'verified').length 
            : 0;
        if (verifiedCount >= openIntake.capacity) {
            return 'Full';
        }
        return 'Open';
    }

    const upcomingIntake = courseIntakes.find(i => new Date(i.start_date) > new Date());
    if (upcomingIntake) {
        return 'Upcoming';
    }

    return 'Closed';
};

// Helper: check if a course has at least one open intake
const hasOpenIntake = (courseId) => {
    return getCourseStatus(courseId) === 'Open';
};

// Enroll trigger from Details Modal
const enrollFromModal = () => {
    const intake = getActiveIntake(selectedCourse.value.id);
    if (intake) {
        detailsModalOpen.value = false;
        openEnrollModal(intake);
    }
};

// Filtered Courses Computed
const filteredCourses = computed(() => {
    return props.courses.filter(course => {
        const matchesSearch = !searchQuery.value.trim() || [
            course.title,
            course.code,
            course.category,
            course.description
        ].some(val => val && val.toLowerCase().includes(searchQuery.value.toLowerCase().trim()));

        const matchesCategory = !selectedCategory.value || course.category === selectedCategory.value;
        const matchesLevel = !selectedLevel.value || (course.description && course.description.toLowerCase().includes(selectedLevel.value.toLowerCase()));
        const matchesStatus = !selectedStatus.value || getCourseStatus(course.id) === selectedStatus.value;

        return matchesSearch && matchesCategory && matchesLevel && matchesStatus;
    });
});

// 10 Steps Configuration
const steps = [
    { title: 'Select Course', description: 'Explore available language courses and select an open intake batch.' },
    { title: 'Fill Form', description: 'Complete the personal and contact details on our direct admission form.' },
    { title: 'Documents Upload', description: 'Upload a clear scanned copy of your national ID card or passport.' },
    { title: 'Proof of Payment', description: 'Deposit tuition fees at CBZ and upload your copy slip or transfer proof.' },
    { title: 'Verification', description: '' },
    { title: 'Recommendation', description: '' },
    { title: 'Approval', description: '' },
    { title: 'Seat Allocation', description: 'The system locks your registration and allocates your classroom seat.' },
    { title: 'Student Account', description: 'A student portal profile is automatically created in the system.' },
    { title: 'Welcome Credentials', description: 'An acceptance email is sent containing your login link and password.' }
];

// Course Details Modal State
const detailsModalOpen = ref(false);
const selectedCourse = ref(null);

const showCourseDetails = (course) => {
    selectedCourse.value = course;
    detailsModalOpen.value = true;
};

// FAQ Accordion State
const activeFaq = ref(null);
const toggleFaq = (index) => {
    if (activeFaq.value === index) {
        activeFaq.value = null;
    } else {
        activeFaq.value = index;
    }
};

// Enroll Form Modal State
const enrollModalOpen = ref(false);
const selectedIntake = ref(null);
const submitting = ref(false);
const flashSuccess = ref('');
const flashError = ref('');

const form = reactive({
    full_name: '',
    national_id_number: '',
    email: '',
    phone: '',
    physical_address: ''
});

const nationalIdCopyFile = ref(null);
const paymentProofFile = ref(null);

const openEnrollModal = (intake) => {
    selectedIntake.value = intake;
    form.full_name = '';
    form.national_id_number = '';
    form.email = '';
    form.phone = '';
    form.physical_address = '';
    nationalIdCopyFile.value = null;
    paymentProofFile.value = null;
    flashSuccess.value = '';
    flashError.value = '';
    enrollModalOpen.value = true;
};

const closeEnrollModal = () => {
    enrollModalOpen.value = false;
};

const handleIdUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        flashError.value = 'File size exceeds 10MB limit.';
        e.target.value = '';
        nationalIdCopyFile.value = null;
        return;
    }
    nationalIdCopyFile.value = file;
    flashError.value = '';
};

const handlePaymentUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        flashError.value = 'File size exceeds 10MB limit.';
        e.target.value = '';
        paymentProofFile.value = null;
        return;
    }
    paymentProofFile.value = file;
    flashError.value = '';
};

const submitEnrollment = () => {
    if (!nationalIdCopyFile.value) {
        flashError.value = 'Please upload a copy of your National ID.';
        return;
    }
    if (!paymentProofFile.value) {
        flashError.value = 'Please upload your proof of payment.';
        return;
    }

    submitting.value = true;
    flashError.value = '';
    flashSuccess.value = '';

    const formData = new FormData();
    formData.append('course_intake_id', selectedIntake.value.id);
    formData.append('full_name', form.full_name);
    formData.append('national_id_number', form.national_id_number);
    formData.append('national_id_copy', nationalIdCopyFile.value);
    formData.append('email', form.email);
    formData.append('phone', form.phone);
    formData.append('physical_address', form.physical_address);
    formData.append('payment_proof', paymentProofFile.value);

    Inertia.post(route('courses.apply'), formData, {
        onSuccess: () => {
            submitting.value = false;
            flashSuccess.value = 'Application submitted successfully! Your registration is now pending review.';
            setTimeout(() => {
                closeEnrollModal();
            }, 3000);
        },
        onError: (err) => {
            submitting.value = false;
            if (Object.keys(err).length > 0) {
                flashError.value = Object.values(err).join(' ');
            } else {
                flashError.value = 'Something went wrong. Please check your form input.';
            }
        }
    });
};

// Date Format Helper
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

// Admin Testimonial Moderation Helpers

const deleteTestimonial = (id) => {
    if (!confirm('Are you sure you want to delete this testimonial? This action cannot be undone.')) return;
    Inertia.delete(route('admin.testimonials.destroy', { id: id }), {
        onSuccess: () => {
            alert('Testimonial deleted successfully.');
        }
    });
};
</script>

<style scoped>
.bg-brand-gold {
    background-color: #f5c242;
}
.text-brand-gold {
    color: #f5c242;
}
.text-brand-gold-dark {
    color: #e0ac2b;
}
.border-brand-gold {
    border-color: #f5c242;
}
</style>
