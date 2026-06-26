<template>
    <Head title="Welcome to MSUNLI" />
    <div class="min-h-screen bg-gray-50 font-sans text-gray-900 selection:bg-brand-gold selection:text-brand-blue flex flex-col">
        
        <!-- Navigation -->
        <nav class="fixed w-full z-50 transition-all duration-300 bg-[#0a1f44] shadow-lg" :class="{ 'py-4': scrolled, 'py-6': !scrolled }">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <img src="/msu-logo-2.png" alt="MSU Logo" class="h-14 w-auto bg-white p-1 rounded-lg shadow-sm" />
                    <div>
                        <h1 class="text-xl font-black text-white tracking-wide">MSUNLI</h1>
                        <p class="text-[0.65rem] text-[#f5c242] uppercase tracking-widest font-bold">Midlands State University</p>
                        <p class="text-[0.65rem] text-gray-300 uppercase tracking-widest">National Language Institute</p>
                    </div>
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#services" @click.prevent="toggleServices" class="text-sm font-semibold text-gray-200 hover:text-[#f5c242] transition cursor-pointer">Services</a>
                    <Link :href="route('courses.public')" class="text-sm font-semibold text-gray-200 hover:text-[#f5c242] transition cursor-pointer">Short Courses</Link>
                    <a href="#announcements" class="text-sm font-semibold text-gray-200 hover:text-[#f5c242] transition cursor-pointer">Announcements</a>
                    <button @click="showAbout = true" class="text-sm font-semibold text-gray-200 hover:text-[#f5c242] transition cursor-pointer">About Us</button>
                    
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" 
                          class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-6 py-2.5 rounded-full transition shadow-lg transform hover:-translate-y-0.5 text-sm">
                        Go to Dashboard
                    </Link>
                    <Link v-else :href="route('login')" 
                          class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-6 py-2.5 rounded-full transition shadow-lg transform hover:-translate-y-0.5 text-sm">
                        Portal Login
                    </Link>
                </div>
                
                <!-- Hamburger Button for Mobile -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-200 hover:text-[#f5c242] focus:outline-none p-1.5 rounded-lg transition-colors" aria-label="Toggle Navigation">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile Navigation Menu -->
            <div v-show="mobileMenuOpen" class="md:hidden bg-[#0a1f44] border-t border-white/10 shadow-xl px-6 py-4 space-y-4">
                <a href="#services" @click="mobileMenuOpen = false; toggleServices()" class="block text-sm font-semibold text-gray-255 hover:text-[#f5c242] transition cursor-pointer">Services</a>
                <Link :href="route('courses.public')" @click="mobileMenuOpen = false" class="block text-sm font-semibold text-gray-255 hover:text-[#f5c242] transition cursor-pointer">Short Courses</Link>
                <a href="#announcements" @click="mobileMenuOpen = false" class="block text-sm font-semibold text-gray-255 hover:text-[#f5c242] transition cursor-pointer">Announcements</a>
                <button @click="mobileMenuOpen = false; showAbout = true" class="block w-full text-left text-sm font-semibold text-gray-255 hover:text-[#f5c242] transition cursor-pointer">About Us</button>
                <div class="border-t border-white/10 pt-4">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" @click="mobileMenuOpen = false"
                          class="block w-full text-center bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-6 py-2.5 rounded-full transition shadow-lg text-sm">
                        Go to Dashboard
                    </Link>
                    <Link v-else :href="route('login')" @click="mobileMenuOpen = false"
                          class="block w-full text-center bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-6 py-2.5 rounded-full transition shadow-lg text-sm">
                        Portal Login
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden bg-[#0a1f44] bg-cover bg-center" style="background-image: url('/msuli-build.jpg');">
            <!-- Overlay -->
            <div class="absolute inset-0 bg-[#0a1f44]/85 z-0"></div>
            
            <!-- Decorative Elements -->
            <div class="absolute inset-0 w-full h-full z-0 pointer-events-none">
                <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-[#0c2859] to-transparent"></div>
                <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-[#f5c242] opacity-10 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-gray-50 to-transparent"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-6 lg:px-8 flex flex-col lg:flex-row items-center justify-center gap-12">
                <div class="text-center z-10 max-w-4xl">
                    <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 text-[#f5c242] text-xs font-bold tracking-widest uppercase mb-6 backdrop-blur-sm border border-white/10">
                        Language for national development
                    </span>
                    <h2 class="text-4xl md:text-5xl lg:text-7xl font-extrabold text-white leading-[1.1] mb-6">
                        Official Language <br/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#f5c242] to-yellow-200">Solutions Hub</span>
                    </h2>
                    <p class="text-lg text-gray-300 mb-10 max-w-2xl mx-auto leading-relaxed">
                        Seamlessly manage your language service requests or enroll in professional short courses. From certified Swahili training, Unified English Braille to Zimbabwean Sign Language, MSULI delivers excellence.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-8 py-4 rounded-full transition shadow-xl transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            Go to Dashboard &rarr;
                        </Link>
                        <Link v-else :href="route('login')" class="bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-8 py-4 rounded-full transition shadow-xl transform hover:-translate-y-1 flex items-center justify-center gap-2">
                            Access Portal &amp; Enroll
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </Link>
                        <Link :href="route('courses.public')" class="bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold px-8 py-4 rounded-full transition backdrop-blur-sm flex items-center justify-center cursor-pointer text-center">
                            Explore Courses
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-12 bg-white border-b border-gray-100 relative z-20 -mt-10 mx-6 lg:mx-auto max-w-6xl rounded-2xl shadow-xl">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 px-8 text-center">
                <div>
                    <p class="text-4xl font-black text-[#0a1f44]">16+</p>
                    <p class="text-sm text-gray-500 font-medium mt-1">Supported Languages</p>
                </div>
                <div>
                    <p class="text-4xl font-black text-[#0a1f44]">24/7</p>
                    <p class="text-sm text-gray-500 font-medium mt-1">Digital Portal Access</p>
                </div>
                <div>
                    <p class="text-4xl font-black text-[#0a1f44]">100%</p>
                    <p class="text-sm text-gray-500 font-medium mt-1">Certified Experts</p>
                </div>
                <div>
                    <p class="text-4xl font-black text-[#0a1f44]">8+</p>
                    <p class="text-sm text-gray-500 font-medium mt-1">Short Course Programs</p>
                </div>
            </div>
        </section>

        <!-- Announcements Section -->
        <section id="announcements" class="py-20 bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <span class="inline-block py-1 px-4 rounded-full bg-brand-gold/10 text-[#f5c242] text-xs font-bold tracking-widest uppercase mb-3">
                        Latest Notices
                    </span>
                    <h3 class="text-[#0a1f44] font-black text-3xl md:text-4xl mb-4">News &amp; Announcements</h3>
                    <p class="text-gray-600 text-base">Stay updated with current events, academic updates, and intake schedules at the MSU National Language Institute.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Notice 1 -->
                    <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-2xl border border-gray-150 shadow-sm relative group overflow-hidden">
                        <div class="absolute top-0 left-0 w-2 h-full bg-brand-gold"></div>
                        <span class="text-[0.7rem] font-bold text-gray-400 uppercase tracking-widest">May 28, 2026</span>
                        <h4 class="text-lg font-black text-[#0a1f44] mt-2 mb-3 group-hover:text-[#f5c242] transition-colors">Braille Competency Certification</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">Applications for the Winter 2026 Unified English Braille (UEB) training batches are officially open. Enrollment capacity is limited to 25 seats per intake. Standard tuition fees apply.</p>
                    </div>

                    <!-- Notice 2 -->
                    <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-2xl border border-gray-150 shadow-sm relative group overflow-hidden">
                        <div class="absolute top-0 left-0 w-2 h-full bg-blue-600"></div>
                        <span class="text-[0.7rem] font-bold text-gray-400 uppercase tracking-widest">May 25, 2026</span>
                        <h4 class="text-lg font-black text-[#0a1f44] mt-2 mb-3 group-hover:text-blue-600 transition-colors">Weekend Swahili Conversational Intake</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">Interactive Swahili Swac-101 conversational cohorts will begin operations on Saturday mornings at the Gweru Main Campus. Ideal for corporate stakeholders and business travelers.</p>
                    </div>

                    <!-- Notice 3 -->
                    <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-2xl border border-gray-150 shadow-sm relative group overflow-hidden">
                        <div class="absolute top-0 left-0 w-2 h-full bg-green-600"></div>
                        <span class="text-[0.7rem] font-bold text-gray-400 uppercase tracking-widest">May 20, 2026</span>
                        <h4 class="text-lg font-black text-[#0a1f44] mt-2 mb-3 group-hover:text-green-600 transition-colors">Sign Language Evening Cohorts</h4>
                        <p class="text-sm text-gray-600 leading-relaxed">To promote national inclusivity, evening Zimbabwean Sign Language batches are now scheduled from 5:30 PM to 7:30 PM to accommodate public service and healthcare personnel.</p>
                    </div>
                </div>
            </div>
        </section>



        <!-- Services Section -->
        <section id="services" v-show="showServices" class="py-24 bg-white transition-all duration-500">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h3 class="text-[#0a1f44] font-black text-3xl md:text-4xl mb-4">Our Core Services</h3>
                    <p class="text-gray-600 text-lg">We provide comprehensive language solutions tailored for academic, corporate, and individual needs.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Service 1 -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-150 hover:shadow-xl transition-shadow duration-300 group">
                        <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                        </div>
                        <h4 class="text-xl font-bold text-gray-900 mb-3">Translation</h4>
                        <p class="text-gray-600 text-sm leading-relaxed">Professional document translation ensuring absolute accuracy and cultural nuance across multiple languages.</p>
                    </div>

                    <!-- Service 2 -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-150 hover:shadow-xl transition-shadow duration-300 group">
                        <div class="w-14 h-14 bg-green-50 text-green-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <h4 class="text-xl font-bold text-gray-900 mb-3">Editing & Proofreading</h4>
                        <p class="text-gray-600 text-sm leading-relaxed">Refining academic manuscripts, corporate reports, and publications for impeccable grammar and flow.</p>
                    </div>

                    <!-- Service 3 -->
                    <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-150 hover:shadow-xl transition-shadow duration-300 group">
                        <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                        <h4 class="text-xl font-bold text-gray-900 mb-3">Brailling Services</h4>
                        <p class="text-gray-600 text-sm leading-relaxed">Converting standard texts into Braille, promoting inclusivity and accessibility for the visually impaired.</p>
                    </div>

                    <!-- Service 4 -->
                    <div class="bg-[#0a1f44] rounded-2xl p-8 shadow-xl border border-[#0c2859] hover:-translate-y-2 transition-transform duration-300 group relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-[#f5c242] rounded-full opacity-10 blur-xl"></div>
                        <div class="w-14 h-14 bg-white/10 text-[#f5c242] rounded-xl flex items-center justify-center mb-6 backdrop-blur-sm">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-3">Sign Language</h4>
                        <p class="text-gray-300 text-sm leading-relaxed">Professional sign language interpretation for events, broadcasts, and individual consultations.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 relative bg-gradient-to-r from-[#0a1f44] to-[#0c2859] text-white overflow-hidden">
            <div class="max-w-4xl mx-auto px-6 text-center relative z-10">
                <h2 class="text-3xl md:text-5xl font-black text-white mb-6">Register and Access Our Client Portal Today</h2>
                <p class="text-xl text-gray-300 mb-10">Sign up today to experience a streamlined and professional service by accessing your personalized client portal anytime, from anywhere, and receiving tailored guidance and support from our professional language experts.</p>
                <Link :href="route('register')" class="inline-block bg-[#f5c242] hover:bg-yellow-400 text-[#0a1f44] font-bold px-10 py-4 rounded-full transition shadow-lg transform hover:-translate-y-1 text-lg">
                    Register
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer id="contact" class="bg-[#0a1f44] text-white pt-16 mt-auto">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 pb-12 grid grid-cols-1 md:grid-cols-3 gap-12">
                <div>
                    <div class="flex items-center gap-3 mb-6">
                        <img src="/msu-logo-2.png" alt="MSU Logo" class="h-10 w-auto bg-white p-1 rounded shadow-sm" />
                        <div>
                            <h2 class="text-lg font-black tracking-wider">MSUNLI</h2>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed max-w-sm">
                        Midlands State University National Language Institute is dedicated to providing world-class language solutions, fostering communication, and promoting inclusivity.
                    </p>
                </div>
                <div>
                    <h3 class="text-[#f5c242] font-bold uppercase tracking-wider text-sm mb-6">Quick Links</h3>
                    <ul class="space-y-3">
                        <li><Link :href="route('login')" class="text-gray-400 hover:text-white transition text-sm">Portal Login</Link></li>
                        <li><Link :href="route('courses.public')" class="text-gray-400 hover:text-[#f5c242] transition text-sm">Available Courses</Link></li>
                        <li><a href="#services" @click.prevent="toggleServices" class="text-gray-400 hover:text-white transition text-sm cursor-pointer">Our Services</a></li>
                        <li><button @click="showAbout = true" class="text-gray-400 hover:text-white transition text-sm cursor-pointer">About Us</button></li>
                        <li><button @click="showTerms = true" class="text-gray-400 hover:text-white transition text-sm cursor-pointer">Terms &amp; Conditions</button></li>
                        <li><button @click="showPrivacy = true" class="text-gray-400 hover:text-white transition text-sm cursor-pointer">Privacy Policy</button></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-[#f5c242] font-bold uppercase tracking-wider text-sm mb-6">Contact Us</h3>
                    <ul class="space-y-3 text-sm text-gray-400">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>{{ contactInfo ? contactInfo.location : 'P Bag 9055, Senga Road Gweru Zimbabwe' }}</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>
                                <a :href="'mailto:' + (contactInfo ? contactInfo.email : 'info@languageinstitute.msu.ac.zw')" class="hover:text-white transition">
                                    {{ contactInfo ? contactInfo.email : 'info@languageinstitute.msu.ac.zw' }}
                                </a>
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-gray-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <span>
                                Telephone: <a :href="'tel:' + (contactInfo ? contactInfo.phone : '+263 54 2260331')" class="hover:text-white transition font-semibold">{{ contactInfo ? contactInfo.phone : '+263 54 2260331' }}</a><br/>
                                Mobile: <a :href="'tel:' + (contactInfo ? contactInfo.mobile : '+263 772 123 456')" class="hover:text-white transition font-semibold">{{ contactInfo ? contactInfo.mobile : '+263 772 123 456' }}</a>
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
        


        <!-- Modals for About, Terms, Privacy -->
        <!-- About Us Modal -->
        <div v-if="showAbout" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showAbout = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[85vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-8 py-5 flex justify-between items-center z-10">
                    <h2 class="text-2xl font-black text-[#0a1f44]">About Us</h2>
                    <button @click="showAbout = false" class="text-gray-400 hover:text-red-500 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-8 prose prose-blue max-w-none text-gray-600">
                    <p>Welcome to the Midlands State University National Language Institute (MSUNLI) Integrated Language Services and Operations Management System. MSUNLI is a national centre of excellence dedicated to promoting language development, research, innovation, translation, brailling, consultancy, and sign language services across Zimbabwe’s sixteen officially recognized languages.</p>
                    <p>Our mission is to enhance communication, inclusivity, and accessibility through professional language services supported by modern digital solutions. This platform was developed to streamline service delivery, improve operational efficiency, strengthen collaboration, and provide clients with a centralized and transparent system for accessing language-related services.</p>
                    <p>Through this system, clients can conveniently submit service requests, track progress, receive quotations, and engage with qualified language experts while enabling the Institute to maintain high standards of accountability, quality, and performance monitoring.</p>
                    
                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <h4 class="text-lg font-bold text-[#0a1f44] mb-2">Supported Official Languages of Zimbabwe</h4>
                        <p class="text-sm text-gray-500 mb-6">MSUNLI actively supports translation, research, resource development, and professional short courses across all 16 official languages:</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div v-for="lang in zimbabweanLanguages" :key="lang" class="flex items-center gap-2.5 p-3 bg-gray-50 rounded-xl border border-gray-100 hover:border-[#f5c242] hover:bg-[#0a1f44]/5 transition-all duration-300 group">
                                <span class="w-2 h-2 rounded-full bg-[#f5c242] group-hover:scale-125 transition-transform"></span>
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-[#0a1f44] transition-colors">{{ lang }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms & Conditions Modal -->
        <div v-if="showTerms" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showTerms = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-8 py-5 flex justify-between items-center z-10">
                    <h2 class="text-2xl font-black text-[#0a1f44]">Terms & Conditions</h2>
                    <button @click="showTerms = false" class="text-gray-400 hover:text-red-500 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-8 prose prose-blue max-w-none text-gray-600">
                    <p>By accessing and using the MSUNLI Integrated Language Services and Operations Management System, you agree to comply with the following terms and conditions:</p>
                    <ol class="list-decimal pl-5 space-y-2">
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
        <div v-if="showPrivacy" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showPrivacy = false">
            <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[80vh] overflow-y-auto shadow-2xl">
                <div class="sticky top-0 bg-white border-b border-gray-100 px-8 py-5 flex justify-between items-center z-10">
                    <h2 class="text-2xl font-black text-[#0a1f44]">Privacy Policy</h2>
                    <button @click="showPrivacy = false" class="text-gray-400 hover:text-red-500 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-8 prose prose-blue max-w-none text-gray-600">
                    <p>The Midlands State University National Language Institute (MSUNLI) is committed to protecting the privacy, confidentiality, and security of all users and information processed through this platform.</p>
                    <ol class="list-decimal pl-5 space-y-2">
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
import { ref, onMounted, onUnmounted, nextTick, reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';

const scrolled = ref(false);
const mobileMenuOpen = ref(false);
const showServices = ref(false);
const showAbout = ref(false);
const showTerms = ref(false);
const showPrivacy = ref(false);

const props = defineProps({
    courses: Array,
    intakes: Array,
    contactInfo: Object,
});

const zimbabweanLanguages = [
    'Chewa', 'ChiBarwe', 'English', 'Kalanga', 'Koisan', 'Nambya', 'Ndau', 'Ndebele',
    'Shangani', 'Shona', 'Sign Language', 'Sotho', 'Tonga', 'Tswana', 'Venda', 'Xhosa'
];

const getCourseIntakes = (courseId) => {
    if (!props.intakes) return [];
    return props.intakes.filter(i => i.course_id === courseId);
};

const enrollModalOpen = ref(false);
const selectedIntake = ref(null);

const form = reactive({
    full_name: '',
    national_id_number: '',
    email: '',
    phone: '',
    physical_address: '',
});

const nationalIdCopyFile = ref(null);
const paymentProofFile = ref(null);
const submitting = ref(false);
const flashError = ref('');
const flashSuccess = ref('');

const enrollNow = (intake) => {
    selectedIntake.value = intake;
    enrollModalOpen.value = true;
};

const closeEnrollModal = () => {
    enrollModalOpen.value = false;
    selectedIntake.value = null;
    paymentProofFile.value = null;
    nationalIdCopyFile.value = null;
    form.full_name = '';
    form.national_id_number = '';
    form.email = '';
    form.phone = '';
    form.physical_address = '';
    flashError.value = '';
    flashSuccess.value = '';
};

const handleIdUpload = (e) => {
    const file = e.target.files[0];
    if (file && file.size > 10 * 1024 * 1024) {
        flashError.value = 'Validation Error: National ID copy file size exceeds the maximum 10MB limit.';
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
        flashError.value = 'Validation Error: Proof of Payment file size exceeds the maximum 10MB limit.';
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
                flashError.value = 'An error occurred during submission. Please check all fields.';
            }
        }
    });
};

const toggleServices = () => {
    showServices.value = !showServices.value;
    if (showServices.value) {
        nextTick(() => {
            document.getElementById('services')?.scrollIntoView({ behavior: 'smooth' });
        });
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

const handleScroll = () => {
    scrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>
