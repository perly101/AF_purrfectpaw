<!-- Professional Clinic Sidebar -->

@php
    use App\Models\ClinicInfo;
    $clinic = ClinicInfo::where('user_id', auth()->id())->first();
    $currentRoute = request()->route()->getName();
@endphp

<style>
    /* Toggle Switch Styles */
    .toggle-label {
        position: relative;
    }
    .toggle-label span {
        position: absolute;
        left: 0;
        top: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    input:checked + .toggle-label {
        background-color: #6366F1;
    }
    input:focus + .toggle-label {
        outline: 2px solid rgba(99, 102, 241, 0.4);
    }
    
    /* Navigation Item Animations */
    .nav-item {
        position: relative;
        transition: all 0.2s;
        overflow: hidden;
    }
    
    .nav-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 0;
        background-color: rgba(255, 255, 255, 0.1);
        transition: width 0.2s ease;
        border-radius: 0.5rem;
        z-index: -1;
    }
    
    .nav-item:hover::before {
        width: 100%;
    }
</style>

<div class="w-64 h-screen bg-gradient-to-b from-[#111827] to-[#030712] text-gray-100 fixed top-0 left-0 shadow-xl flex flex-col overflow-hidden z-20">

    <!-- Clinic Logo & Name -->
    <div class="flex flex-col items-center justify-center px-4 py-5 border-b border-gray-800/40 relative">
        <!-- Decorative Background Element -->
        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-indigo-400/10 to-transparent rounded-bl-full"></div>
        
        <div class="flex items-center justify-center mb-2 relative">
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 p-0.5 rounded-full shadow-lg">
                <div class="bg-gradient-to-b from-[#111827] to-[#030712] p-1.5 rounded-full">
                    <img src="{{ asset('storage/' . $clinic->profile_picture) }}" alt="Clinic Logo"
                        class="rounded-full object-cover shadow-inner" style="width: 46px; height: 46px;">
                </div>
            </div>
        </div>
        <h1 class="text-xl font-bold text-center leading-tight tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 to-purple-300">
            {{ $clinic ? $clinic->clinic_name : 'Clinic Dashboard' }}
        </h1>
        <p class="text-xs text-indigo-300/80 font-medium mt-1 tracking-wider uppercase">Healthcare Provider</p>
        
        <!-- Open/Closed Switch -->
        <div class="mt-4 flex items-center justify-center">
            <form id="clinicStatusForm" method="POST" action="{{ route('clinic.update.status') }}" class="flex items-center bg-gray-800/30 px-3 py-1.5 rounded-full shadow-inner">
                @csrf
                <span class="mr-2 text-xs font-medium {{ $clinic->is_open ? 'text-gray-400' : 'text-white' }}">Closed</span>
                <div class="relative inline-block w-12 align-middle select-none">
                    <input 
                        type="checkbox" 
                        name="is_open" 
                        id="clinic-status" 
                        class="opacity-0 absolute w-0 h-0"
                        {{ $clinic->is_open ? 'checked' : '' }}
                        onchange="document.getElementById('clinicStatusForm').submit()"
                    >
                    <label for="clinic-status" class="toggle-label block overflow-hidden h-5 rounded-full bg-gray-900/80 cursor-pointer border border-indigo-600/30 shadow-inner">
                        <span class="block h-5 w-5 rounded-full bg-gradient-to-b from-white to-gray-100 transform transition-transform duration-300 ease-in-out shadow {{ $clinic->is_open ? 'translate-x-7' : 'translate-x-0' }}"></span>
                    </label>
                </div>
                <span class="ml-2 text-xs font-medium {{ $clinic->is_open ? 'text-white' : 'text-gray-400' }}">Open</span>
            </form>
        </div>
    </div>

    <!-- Staff Profile -->
    <div class="px-4 py-4 border-b border-gray-800/40 flex items-center relative backdrop-blur-sm bg-gray-900/20">
        <div class="absolute -bottom-6 -right-6 w-12 h-12 bg-indigo-400/5 rounded-full"></div>
        <div class="absolute -top-6 -left-6 w-12 h-12 bg-indigo-400/5 rounded-full"></div>
        
        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 p-0.5 shadow-lg">
            <div class="bg-gray-900 rounded-full w-full h-full flex items-center justify-center">
                <span class="uppercase text-indigo-300 font-bold text-sm">{{ auth()->user() ? substr(auth()->user()->first_name, 0, 1) : 'C' }}</span>
            </div>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-white">{{ auth()->user() ? auth()->user()->first_name . ' ' . auth()->user()->last_name : 'Staff User' }}</p>
            <div class="flex items-center mt-0.5">
                <div class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse mr-1.5"></div>
                <p class="text-xs text-indigo-300/80 tracking-wide">Staff Member</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-5 px-3 flex-1 overflow-y-auto">
        <div class="flex items-center px-4 mb-3">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-indigo-600/30 to-transparent"></div>
            <p class="px-3 text-xs font-semibold text-indigo-300/80 uppercase tracking-wider">Clinic Management</p>
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-indigo-600/30 to-transparent"></div>
        </div>
        <ul class="space-y-1 font-medium">
            <li>
                <a href="{{ route('clinic.dashboard') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ $currentRoute === 'clinic.dashboard' ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ $currentRoute === 'clinic.dashboard' ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                        </svg>
                    </div>
                    <span class="font-medium">Dashboard</span>
                    @if($currentRoute === 'clinic.dashboard')
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>
            
            <li>
                <a href="{{ route('clinic.home') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ $currentRoute === 'clinic.home' ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ $currentRoute === 'clinic.home' ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                    </div>
                    <span class="font-medium">Homepage</span>
                    @if($currentRoute === 'clinic.home')
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>

            <li>
                <a href="{{ route('clinic.fields.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ $currentRoute === 'clinic.fields.index' ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ $currentRoute === 'clinic.fields.index' ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                        </svg>
                    </div>
                    <span class="font-medium">Appointment Form</span>
                    @if($currentRoute === 'clinic.fields.index')
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>

            <li>
                <a href="{{ route('clinic.appointments.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ $currentRoute === 'clinic.appointments.index' ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ $currentRoute === 'clinic.appointments.index' ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="font-medium">Appointments</span>
                    @if($currentRoute === 'clinic.appointments.index')
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>
<!--             
            <li>
                <a href="{{ route('clinic.appointments.archived') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ $currentRoute === 'clinic.appointments.archived' ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ $currentRoute === 'clinic.appointments.archived' ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4a3 3 0 00-3 3v6a3 3 0 003 3h10a3 3 0 003-3V7a3 3 0 00-3-3H5zm0 2a1 1 0 00-1 1v6a1 1 0 001 1h10a1 1 0 001-1V7a1 1 0 00-1-1H5z" clip-rule="evenodd" />
                            <path d="M11 9h2v2h-2V9zM7 9h2v2H7V9z" />
                        </svg>
                    </div>
                    <span class="font-medium">Archived</span>
                    @if($currentRoute === 'clinic.appointments.archived')
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li> -->
            


            <li>
                <a href="{{ route('clinic.doctors.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ str_starts_with($currentRoute, 'clinic.doctors.') ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ str_starts_with($currentRoute, 'clinic.doctors.') ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                    </div>
                    <span class="font-medium">Doctors</span>
                    @if(str_starts_with($currentRoute, 'clinic.doctors.'))
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>

            <!-- <li>
                <a href="{{ route('clinic.patients.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ str_starts_with($currentRoute, 'clinic.patients.') ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ str_starts_with($currentRoute, 'clinic.patients.') ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="font-medium">Patients</span>
                    @if(str_starts_with($currentRoute, 'clinic.patients.'))
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li> -->

            <li>
                <a href="{{ route('clinic.payments.summary') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ str_starts_with($currentRoute, 'clinic.payments.') ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ str_starts_with($currentRoute, 'clinic.payments.') ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <span class="font-medium">Payment Reports</span>
                    @if(str_starts_with($currentRoute, 'clinic.payments.'))
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>

            <li>
                <a href="{{ route('clinic.gallery.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ str_starts_with($currentRoute, 'clinic.gallery.') ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ str_starts_with($currentRoute, 'clinic.gallery.') ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="font-medium">Gallery</span>
                    @if(str_starts_with($currentRoute, 'clinic.gallery.'))
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>

            <div class="flex items-center px-4 mb-3 mt-6">
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-indigo-600/30 to-transparent"></div>
                <p class="px-3 text-xs font-semibold text-indigo-300/80 uppercase tracking-wider">Preferences</p>
                <div class="h-px flex-1 bg-gradient-to-r from-transparent via-indigo-600/30 to-transparent"></div>
            </div>

            <li>
                <a href="{{ route('clinic.settings.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ $currentRoute === 'clinic.settings.index' ? 'bg-gradient-to-r from-indigo-600/50 to-indigo-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ $currentRoute === 'clinic.settings.index' ? 'text-indigo-300' : 'text-indigo-400 group-hover:text-indigo-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="font-medium">Settings</span>
                    @if($currentRoute === 'clinic.settings.index')
                        <span class="ml-auto bg-indigo-500/20 text-indigo-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>
        </ul>
    </nav>

    <!-- Footer & Logout Button -->
    <div class="mt-auto">
        <div class="px-3 py-4">
            <div class="bg-gradient-to-r from-indigo-900/30 to-indigo-800/20 backdrop-blur-sm rounded-xl p-3 mb-4 border border-indigo-700/20 shadow-inner">
                <!-- Stylized Status Indicator -->
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <div class="relative">
                            <div class="p-1.5 bg-indigo-500/20 rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <span class="absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full {{ $clinic->is_open ? 'bg-indigo-500 animate-pulse' : 'bg-gray-400' }} border border-gray-900"></span>
                        </div>
                        <div class="ml-2">
                            <p class="text-xs font-semibold text-indigo-100">Clinic Status</p>
                            <p class="text-xs text-indigo-300">{{ $clinic->is_open ? 'Currently Open' : 'Currently Closed' }}</p>
                        </div>
                    </div>
                    <div class="text-right text-xs">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md {{ $clinic->is_open ? 'bg-indigo-500/20 text-indigo-300' : 'bg-gray-500/20 text-gray-300' }}">
                            {{ $clinic->is_open ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>
                <!-- Additional Info section removed to reduce crowding -->
            </div>
        </div>
        <div class="px-3 py-3 border-t border-indigo-800/40 relative">
            <!-- Decorative Element -->
            <div class="absolute -top-1 left-1/2 transform -translate-x-1/2 px-3 bg-gradient-to-b from-[#4338CA] to-[#312E81]">
                <div class="h-0.5 w-10 bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent"></div>
            </div>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-red-500/90 to-red-600/90 hover:from-red-600 hover:to-red-700 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md relative overflow-hidden group">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-red-500/20 to-transparent transform -skew-x-12 -translate-x-full group-hover:translate-x-0 transition-transform duration-300"></span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 relative z-10" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm7.707 3.293a1 1 0 010 1.414L9.414 9H17a1 1 0 110 2H9.414l1.293 1.293a1 1 0 01-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span class="relative z-10">Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>
