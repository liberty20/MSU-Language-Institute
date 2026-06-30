<template>
    <!-- Global System Background Layer -->
    <div class="fixed inset-0 pointer-events-none z-0 bg-[#f8fafc]">
        <!-- Background Image with adjusted opacity and lowered brightness/exposure -->
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat bg-fixed"
             style="background-image: url('/images/backgrounds/msuli-build.jpg'); opacity: 0.08; filter: brightness(0.85) contrast(1.1);"></div>
    </div>

    <div class="h-screen bg-transparent flex overflow-hidden relative z-10">
        <!-- Sidebar Backdrop for Mobile -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-20 md:hidden transition-opacity duration-300"></div>

        <!-- Sidebar -->
        <aside :class="[
            'bg-brand-blue text-white shadow-xl flex-shrink-0 h-full flex flex-col transition-all duration-300 z-30',
            'fixed md:static inset-y-0 left-0 transform md:transform-none md:flex md:flex-col',
            sidebarCollapsed ? 'w-64 md:w-20' : 'w-64 md:w-64',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
        ]">
            <div :class="['p-6 flex items-center gap-3 border-b border-brand-blue-light', sidebarCollapsed ? 'md:justify-center justify-start' : 'justify-start']">
                <img src="/msu-logo-2.png" alt="MSU Logo" class="h-10 w-auto object-contain flex-shrink-0" />
                <h1 :class="['text-3xl font-extrabold tracking-widest text-white transition-all duration-300', sidebarCollapsed ? 'md:hidden' : '']">MSUNLI</h1>
            </div>
            
            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <Link v-if="!$page.props.auth.roles.includes('student')" :href="route('dashboard')" :class="navClass('dashboard')" title="Dashboard">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Dashboard</span>
                </Link>

                <Link v-if="can('view service requests')" :href="route('service-requests.index')" :class="navClass('service-requests.*')" title="Service Requests">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Service Requests</span>
                </Link>

                <Link v-if="$page.props.auth.roles.includes('client')" :href="route('reviews.index')" :class="navClass('reviews.*')" title="Reviews">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Reviews</span>
                </Link>

                <Link v-if="can('manage quotations') || can('view quotations') || can('approve quotations')" :href="route('quotations.index')" :class="navClass('quotations.*')" title="Quotations">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Quotations</span>
                </Link>

                <Link v-if="can('manage clients') || $page.props.auth.roles.includes('deputy_director')" :href="route('clients.index')" :class="navClass('clients.*')" title="Clients">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Clients</span>
                </Link>

                <Link v-if="can('view assignments') || can('manage assignments') || $page.props.auth.is_staff_outside_aos" :href="route('assignments.index')" :class="navClass('assignments.*')" title="Assignments">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Assignments</span>
                </Link>

                <Link v-if="['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'client'].some(r => $page.props.auth.roles.includes(r))" :href="route('completed-tasks.index')" :class="navClass('completed-tasks.*')" title="Completed Tasks">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Completed Tasks</span>
                </Link>

                <Link v-if="$page.props.auth.roles.includes('client')" :href="route('payments.index')" :class="navClass('payments.*')" title="Payment Details">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Payment Details</span>
                </Link>

                <Link v-if="['executive_director', 'deputy_director', 'ict_administrator'].some(r => $page.props.auth.roles.includes(r))" :href="route('finance.index')" :class="navClass('finance.*')" title="Finance">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Finance</span>
                </Link>

                <a v-if="can('manage procurement') || can('approve procurement')" href="https://procurementreq.msu.ac.zw/" :class="navClass('procurement-requests.*')" title="Procurement">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Procurement</span>
                </a>

                <a v-if="can('manage procurement') || can('approve procurement')" href="https://storesreq.msu.ac.zw/" :class="navClass('stores-requisition.*')" title="Stores Requisition">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Stores Requisition</span>
                </a>

                <Link v-if="can('view reports')" :href="route('reports.index')" :class="navClass('reports.*')" title="Reports">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Reports</span>
                </Link>

                <Link v-if="!$page.props.auth.roles.includes('student')" :href="route('chat.index')" :class="navClass('chat.*')" title="Internal Messages" class="flex justify-between items-center w-full relative">
                    <div class="flex items-center">
                        <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Internal Messages</span>
                    </div>
                    <span v-if="unreadMessagesCount > 0 && !sidebarCollapsed" class="bg-amber-500 text-[#0a1f44] font-black text-[10px] px-2 py-0.5 rounded-full shadow-sm ml-auto">
                        {{ unreadMessagesCount }}
                    </span>
                    <span v-if="unreadMessagesCount > 0 && sidebarCollapsed" class="absolute top-2 right-2 w-2.5 h-2.5 bg-amber-500 rounded-full animate-pulse border border-[#0a1f44]"></span>
                </Link>

                <!-- Short Courses (Admin / Staff) -->
                <Link v-if="['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'].some(r => $page.props.auth.roles.includes(r))" :href="route('courses.index')" :class="navClass('courses.*')" title="Short Courses">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Short Courses</span>
                </Link>

                <Link v-if="['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'].some(r => $page.props.auth.roles.includes(r))" :href="route('course-enrollments.index')" :class="navClass('course-enrollments.*')" title="Course Enrollments">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Course Enrollments</span>
                </Link>

                <!-- Course Applications (Admin / Staff Review Panel) -->
                <Link v-if="['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant'].some(r => $page.props.auth.roles.includes(r))" :href="route('course-applications.index')" :class="navClass('course-applications.*')" title="Course Applications">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Course Applications</span>
                </Link>

                <!-- Notices & Announcements (Admin + Students) -->
                <Link v-if="['executive_director', 'deputy_director', 'ict_administrator', 'admin_assistant', 'secretary'].some(r => $page.props.auth.roles.includes(r)) || $page.props.auth.roles.includes('student')" :href="route('notices.index')" :class="navClass('notices.*')" title="Notices &amp; Announcements">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Notices</span>
                </Link>

                <!-- Short Courses (Student only) -->
                <Link v-if="$page.props.auth.roles.includes('student')" :href="route('student.courses')" :class="navClass('student.courses')" title="My Short Courses">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">My Short Courses</span>
                </Link>

                <Link v-if="$page.props.auth.roles.includes('student')" :href="route('student.announcements.index')" :class="navClass('student.announcements.*')" title="Course Announcements" class="flex justify-between items-center w-full relative">
                    <div class="flex items-center">
                        <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Announcements</span>
                    </div>
                    <span v-if="$page.props.unreadAnnouncementsCount > 0 && !sidebarCollapsed" class="bg-amber-500 text-[#0a1f44] font-black text-[10px] px-2 py-0.5 rounded-full shadow-sm ml-auto">
                        {{ $page.props.unreadAnnouncementsCount }}
                    </span>
                    <span v-if="$page.props.unreadAnnouncementsCount > 0 && sidebarCollapsed" class="absolute top-2 right-2 w-2.5 h-2.5 bg-amber-500 rounded-full animate-pulse border border-[#0a1f44]"></span>
                </Link>

                <Link v-if="$page.props.auth.roles.includes('student')" :href="route('student.ca')" :class="navClass('student.ca')" title="Continuous Assessment">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">CA Marks</span>
                </Link>

                <Link v-if="$page.props.auth.roles.includes('student')" :href="route('student.timetable')" :class="navClass('student.timetable')" title="Timetable">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Timetable</span>
                </Link>

                <Link v-if="$page.props.auth.roles.includes('student')" :href="route('student.assignments')" :class="navClass('student.assignments')" title="Assignments">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Assignments</span>
                </Link>

                <Link v-if="$page.props.auth.roles.includes('student')" :href="route('student.learning-content')" :class="navClass('student.learning-content')" title="Learning Content">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Learning Content</span>
                </Link>

                <!-- Short Courses (Instructor only) -->
                <Link v-if="$page.props.auth.is_instructor" :href="route('instructor.enrollments')" :class="navClass('instructor.enrollments')" title="Student Enrollments">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Course Enrollments</span>
                </Link>

                <Link v-if="$page.props.auth.is_instructor" :href="route('instructor.announcements.index')" :class="navClass('instructor.announcements.*')" title="Announcements Manager">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Announcements Manager</span>
                </Link>
 
                <Link v-if="$page.props.auth.is_instructor" :href="route('instructor.timetable.index')" :class="navClass('instructor.timetable.index')" title="Manage Timetable">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Timetables Manager</span>
                </Link>
 
                <Link v-if="$page.props.auth.is_instructor" :href="route('instructor.ca.index')" :class="navClass('instructor.ca.index')" title="Continuous Assessment Marks">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">CA Marks Manager</span>
                </Link>
 
                <Link v-if="$page.props.auth.is_instructor" :href="route('instructor.assignments.index')" :class="navClass('instructor.assignments.index')" title="Manage Assignments">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Assignments Manager</span>
                </Link>

                <Link v-if="$page.props.auth.is_instructor" :href="route('instructor.completed-tasks')" :class="navClass('instructor.completed-tasks')" title="Completed Tasks">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Completed Tasks</span>
                </Link>

                <Link v-if="$page.props.auth.is_instructor" :href="route('instructor.learning-content.index')" :class="navClass('instructor.learning-content.*')" title="Learning Content Manager">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Learning Content</span>
                </Link>

                <a v-if="$page.props.auth.roles.includes('student')" href="/courses-catalog" :class="navClass('courses.catalog')" title="Courses Catalog">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 4a2 2 0 002-2v-1a2 2 0 00-2-2m-2 4h4m-4 4h4m-4 4h4M4 8h12M4 12h12M4 16h12"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Courses Catalog</span>
                </a>

                <Link v-if="['ict_administrator', 'executive_director', 'deputy_director'].some(r => $page.props.auth.roles.includes(r))" :href="route('admin.users.index')" :class="navClass('admin.users.*')" title="Users">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Users</span>
                </Link>

                <Link v-if="['ict_administrator', 'executive_director', 'deputy_director', 'admin_assistant'].some(r => $page.props.auth.roles.includes(r))" :href="route('admin.audit-trail')" :class="navClass('admin.audit-trail')" title="Audit Trail">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">Audit Trail</span>
                </Link>

                <Link v-if="['ict_administrator', 'executive_director', 'deputy_director'].some(r => $page.props.auth.roles.includes(r))" :href="route('admin.settings.index')" :class="navClass('admin.settings.*')" title="System Settings">
                    <svg :class="['w-5 h-5 transition-all duration-300', sidebarCollapsed ? 'md:mr-0 mr-3' : 'mr-3']" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span :class="[sidebarCollapsed ? 'md:hidden' : '']">System Settings</span>
                </Link>
            </nav>

            <!-- User Profile Bottom -->
            <div :class="['p-4 border-t border-brand-blue-light flex items-center justify-between transition-all duration-300', sidebarCollapsed ? 'md:flex-col md:gap-2 md:justify-center' : '']">
                <div :class="['flex items-center gap-3 min-w-0', sidebarCollapsed ? 'md:flex-col md:justify-center' : '']">
                    <div class="w-10 h-10 rounded-full bg-brand-gold text-brand-blue flex items-center justify-center font-bold text-lg shadow flex-shrink-0 overflow-hidden">
                        <img v-if="$page.props.auth.user.avatar" :src="'/storage/' + $page.props.auth.user.avatar" class="w-full h-full object-cover" />
                        <span v-else>{{ $page.props.auth.user.name.charAt(0) }}</span>
                    </div>
                    <div :class="['flex-1 min-w-0 transition-all duration-300', sidebarCollapsed ? 'md:hidden' : '']">
                        <p class="text-sm font-semibold truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-300 truncate uppercase">{{ $page.props.auth.roles[0] ? $page.props.auth.roles[0].replace('_', ' ') : 'User' }}</p>
                    </div>
                </div>
                <Link :href="route('profile.edit')" :class="['p-1.5 text-gray-300 hover:text-brand-gold rounded-lg hover:bg-white/5 transition flex-shrink-0', sidebarCollapsed ? 'md:mt-1' : '']" title="Edit Profile">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-grow flex flex-col h-full overflow-hidden relative">
            <!-- Header -->
            <header class="bg-brand-blue md:bg-white shadow-sm border-b border-brand-blue-light md:border-gray-200 z-20 sticky top-0 flex-shrink-0">
                <div class="px-4 md:px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3 flex-grow">
                        <!-- Mobile Hamburger Menu Button -->
                        <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-white hover:text-brand-gold focus:outline-none p-1 rounded transition duration-150 ease-in-out" aria-label="Toggle Sidebar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!sidebarOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        
                        <!-- Desktop Collapse Button -->
                        <button @click="toggleSidebar" class="hidden md:flex text-gray-500 hover:text-brand-blue hover:bg-gray-100 p-1.5 rounded-lg transition-colors focus:outline-none mr-2" :aria-label="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'" :title="sidebarCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!sidebarCollapsed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </button>
                        
                        <!-- Mobile Logo & Brand -->
                        <div class="flex items-center gap-2 md:hidden">
                            <img src="/msu-logo-2.png" alt="MSU Logo" class="h-8 w-auto object-contain bg-white p-0.5 rounded shadow-sm" />
                            <span class="text-xl font-bold tracking-wider text-white">MSUNLI</span>
                        </div>
                        
                        <!-- Desktop Page Title -->
                        <h2 class="hidden md:block text-2xl font-semibold text-brand-blue tracking-tight w-full">
                            <slot name="header" />
                        </h2>
                    </div>

                     <div class="flex items-center gap-4">
                        <!-- Centralized Notifications Dropdown -->
                        <div class="relative">
                            <button @click="toggleNotificationDropdown" class="relative p-1.5 text-white md:text-gray-500 hover:text-brand-gold md:hover:text-brand-blue rounded-lg transition-colors focus:outline-none flex items-center">
                                <span v-if="unreadCount > 0" class="text-rose-600 font-black text-sm mr-1.5 select-none">
                                    {{ unreadCount }}
                                </span>
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                            </button>
                            
                            <!-- Dropdown List panel -->
                            <div v-show="showNotifications" class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-gray-150 z-[100] py-0 overflow-hidden">
                                <div class="px-4 py-2.5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                    <span class="font-bold text-xs text-brand-blue uppercase tracking-wider">🔔 Notifications ({{ unreadCount }} unread)</span>
                                    <button v-if="unreadCount > 0" @click="markAllRead" class="text-[10px] text-brand-gold-dark hover:underline font-bold">Mark all read</button>
                                </div>
                                <div class="max-h-64 overflow-y-auto divide-y divide-gray-50">
                                    <div v-for="notif in notifications" :key="notif.id" class="px-4 py-3 hover:bg-gray-50/70 transition flex gap-3 relative" :class="{'bg-rose-50/10': !notif.read_at}">
                                        <!-- Clickable container if there is an action URL -->
                                        <Link v-if="notif.data.action_url" :href="route('notifications.click', notif.id)" @click="showNotifications = false" class="flex-1 min-w-0 text-left block">
                                            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                                <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded" 
                                                      :class="notif.read_at ? 'bg-gray-100 text-gray-500' : 'bg-rose-100 text-rose-700'">
                                                    {{ notif.read_at ? '[Read]' : '[Unread]' }}
                                                </span>
                                                <span v-if="notif.data.type" class="inline-block px-1.5 py-0.5 text-[8px] font-bold bg-blue-50 text-blue-700 rounded uppercase tracking-wider">
                                                    {{ notif.data.type.replace('_', ' ') }}
                                                </span>
                                            </div>
                                            <p class="text-xs font-bold text-gray-800 leading-tight">{{ notif.data.title }}</p>
                                            <p class="text-[11px] text-gray-500 mt-1 leading-snug">{{ notif.data.message }}</p>
                                            <span class="text-[9px] text-gray-400 block mt-1">{{ formatDate(notif.created_at) }}</span>
                                        </Link>
                                        <!-- Non-clickable text if no action URL -->
                                        <div v-else class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 mb-1 flex-wrap">
                                                <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded" 
                                                      :class="notif.read_at ? 'bg-gray-100 text-gray-500' : 'bg-rose-100 text-rose-700'">
                                                    {{ notif.read_at ? '[Read]' : '[Unread]' }}
                                                </span>
                                                <span v-if="notif.data.type" class="inline-block px-1.5 py-0.5 text-[8px] font-bold bg-gray-50 text-gray-650 rounded uppercase tracking-wider">
                                                    {{ notif.data.type.replace('_', ' ') }}
                                                </span>
                                            </div>
                                            <p class="text-xs font-bold text-gray-800 leading-tight">{{ notif.data.title }}</p>
                                            <p class="text-[11px] text-gray-500 mt-1 leading-snug">{{ notif.data.message }}</p>
                                            <span class="text-[9px] text-gray-400 block mt-1">{{ formatDate(notif.created_at) }}</span>
                                        </div>
                                        
                                        <button v-if="!notif.read_at" @click="markRead(notif.id)" class="text-gray-400 hover:text-brand-blue self-start flex-shrink-0" title="Mark as read">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </div>
                                    <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-xs text-gray-400 italic">
                                        No notifications yet.
                                    </div>
                                </div>
                                <div class="border-t border-gray-100 px-4 py-2 bg-gray-50 text-center flex-shrink-0">
                                    <Link :href="route('notifications.history')" @click="showNotifications = false" class="text-[11px] font-extrabold text-brand-gold-dark hover:text-brand-gold transition uppercase tracking-wider">
                                        View All Notifications &rarr;
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center gap-2 text-sm font-medium text-white md:text-gray-600 hover:text-brand-gold md:hover:text-brand-blue transition duration-150 ease-in-out focus:outline-none">
                                    <span>{{ $page.props.auth.user.name }}</span>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')" class="text-gray-750 hover:bg-gray-50 font-medium">
                                    Edit Profile
                                </DropdownLink>
                                <div class="border-t border-gray-100 my-1"></div>
                                <DropdownLink :href="route('logout')" method="post" as="a" class="text-red-600 font-medium">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mt-6 rounded shadow-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                {{ $page.props.flash.success }}
            </div>
            
            <div v-if="$page.props.flash.error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mt-6 rounded shadow-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                {{ $page.props.flash.error }}
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 relative min-w-0">
                <div class="max-w-full">
                    <!-- Mobile Page Header Card -->
                    <div v-if="$slots.header" class="md:hidden mb-6 bg-white p-4 rounded-xl border border-gray-150 shadow-sm text-gray-900 font-semibold text-lg">
                        <slot name="header" />
                    </div>
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/inertia-vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/inertia-vue3';
import axios from 'axios';

const page = usePage();
const sidebarOpen = ref(false);
const sidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') === 'true');

const notifications = ref([]);
const unreadCount = ref(0);
const unreadMessagesCount = ref(0);
const showNotifications = ref(false);

const toggleNotificationDropdown = () => {
    showNotifications.value = !showNotifications.value;
    if (showNotifications.value) {
        fetchNotifications();
    }
};

const fetchNotifications = () => {
    axios.get(route('notifications.index')).then(res => {
        notifications.value = res.data.notifications;
        unreadCount.value = res.data.unread_count;
    }).catch(err => console.error("Error fetching notifications", err));

    const roles = page.props.value.auth.roles || [];
    const isInstructor = page.props.value.auth.is_instructor || false;
    const isStudent = roles.includes('student');

    // All non-student users can access chat
    if (!isStudent) {
        axios.get(route('chat.unread-count')).then(res => {
            unreadMessagesCount.value = res.data.unread_messages_count;
        }).catch(err => console.error("Error fetching unread chat count", err));
    }
};

const markRead = (id) => {
    axios.post(route('notifications.read', id)).then(() => {
        fetchNotifications();
    }).catch(err => console.error(err));
};

const markAllRead = () => {
    axios.post(route('notifications.read-all')).then(() => {
        fetchNotifications();
    }).catch(err => console.error(err));
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

let pollInterval = null;

onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 10000); // 10s polling
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebarCollapsed', sidebarCollapsed.value);
};

const can = (permission) => {
    return page.props.value.auth.permissions.includes(permission) || page.props.value.auth.permissions.includes('manage system');
};

const navClass = (routeName) => {
    const isActive = route().current(routeName);
    return [
        'flex items-center rounded-xl transition-all duration-200 font-medium',
        sidebarCollapsed.value ? 'md:justify-center md:p-3 px-4 py-3' : 'px-4 py-3',
        isActive ? 'bg-brand-blue-light text-brand-gold shadow-md' : 'text-gray-300 hover:bg-brand-blue-mid hover:text-white hover:shadow'
    ];
};
</script>
