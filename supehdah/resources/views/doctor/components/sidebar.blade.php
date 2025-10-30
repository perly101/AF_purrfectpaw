<!-- Professional Doctor Sidebar -->

@php
    use App\Models\Doctor;
    $doctor = $doctor ?? Doctor::where('user_id', auth()->id())->first();
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
        background-color: #1D4ED8;
    }
    input:focus + .toggle-label {
        outline: 2px solid rgba(37, 99, 235, 0.4);
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

<div class="w-64 h-screen bg-gradient-to-b from-[#111827] to-[#1E3A8A] text-gray-100 fixed top-0 left-0 shadow-xl flex flex-col overflow-hidden z-20">

    <!-- Doctor Logo & Name -->
    <div class="flex flex-col items-center justify-center px-4 py-5 border-b border-gray-800/40 relative">
        <!-- Decorative Background Element -->
        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-400/10 to-transparent rounded-bl-full"></div>
        
        <div class="flex items-center justify-center mb-2 relative">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-0.5 rounded-full shadow-lg">
                <div class="bg-gradient-to-b from-[#111827] to-[#1E3A8A] p-1.5 rounded-full">
                    @if($doctor->photo)
                        <img src="{{ asset('storage/doctor_photos/' . $doctor->photo) }}" alt="{{ $doctor->first_name }}" class="rounded-full object-cover shadow-inner" style="width: 46px; height: 46px;">
                    @else
                        <div class="rounded-full flex items-center justify-center bg-blue-900 shadow-inner" style="width: 46px; height: 46px;">
                            <span class="text-lg font-bold text-blue-300">{{ substr($doctor->first_name, 0, 1) . substr($doctor->last_name, 0, 1) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <h1 class="text-xl font-bold text-center leading-tight tracking-wide text-transparent bg-clip-text bg-gradient-to-r from-blue-300 to-indigo-300">
            Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
        </h1>
        <p class="text-xs text-blue-300/80 font-medium mt-1 tracking-wider uppercase">{{ $doctor->specialization }}</p>
    </div>

    <!-- Staff Profile -->
    <div class="px-4 py-4 border-b border-gray-800/40 flex items-center relative backdrop-blur-sm bg-gray-900/20">
        <div class="absolute -bottom-6 -right-6 w-12 h-12 bg-blue-400/5 rounded-full"></div>
        <div class="absolute -top-6 -left-6 w-12 h-12 bg-blue-400/5 rounded-full"></div>
        
        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 p-0.5 shadow-lg">
            <div class="bg-gray-900 rounded-full w-full h-full flex items-center justify-center">
                <span class="uppercase text-blue-300 font-bold text-sm">{{ auth()->user() ? substr(auth()->user()->first_name, 0, 1) : 'D' }}</span>
            </div>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium text-white">{{ auth()->user() ? auth()->user()->first_name . ' ' . auth()->user()->last_name : 'Doctor User' }}</p>
            
            <div class="flex items-center mt-0.5">
                <div class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse mr-1.5"></div>
                <p class="text-xs text-blue-300/80 tracking-wide">Medical Staff</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="mt-5 px-3 flex-1 overflow-y-auto">
        <div class="flex items-center px-4 mb-3">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-blue-600/30 to-transparent"></div>
            <p class="px-3 text-xs font-semibold text-blue-300/80 uppercase tracking-wider">Doctor Portal</p>
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-blue-600/30 to-transparent"></div>
        </div>
        <ul class="space-y-1 font-medium">
            <li>
                <a href="{{ route('doctor.dashboard') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ $currentRoute === 'doctor.dashboard' ? 'bg-gradient-to-r from-blue-600/50 to-blue-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ $currentRoute === 'doctor.dashboard' ? 'text-blue-300' : 'text-blue-400 group-hover:text-blue-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                        </svg>
                    </div>
                    <span class="font-medium">Dashboard</span>
                    @if($currentRoute === 'doctor.dashboard')
                        <span class="ml-auto bg-blue-500/20 text-blue-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>
            
            <li>
                <a href="{{ route('doctor.appointments.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ str_starts_with($currentRoute, 'doctor.appointments.') ? 'bg-gradient-to-r from-blue-600/50 to-blue-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ str_starts_with($currentRoute, 'doctor.appointments.') ? 'text-blue-300' : 'text-blue-400 group-hover:text-blue-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="font-medium">Appointments</span>
                    @if(str_starts_with($currentRoute, 'doctor.appointments.'))
                        <span class="ml-auto bg-blue-500/20 text-blue-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>
            
            <li>
                <a href="{{ route('doctor.patients.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ str_starts_with($currentRoute, 'doctor.patients.') ? 'bg-gradient-to-r from-blue-600/50 to-blue-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ str_starts_with($currentRoute, 'doctor.patients.') ? 'text-blue-300' : 'text-blue-400 group-hover:text-blue-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                        </svg>
                    </div>
                    <span class="font-medium">Patients</span>
                    @if(str_starts_with($currentRoute, 'doctor.patients.'))
                        <span class="ml-auto bg-blue-500/20 text-blue-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>

            <li>
                <a href="{{ route('doctor.profile.index') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg {{ str_starts_with($currentRoute, 'doctor.profile.') ? 'bg-gradient-to-r from-blue-600/50 to-blue-700/30 text-white shadow-sm' : 'hover:text-white' }} transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 {{ str_starts_with($currentRoute, 'doctor.profile.') ? 'text-blue-300' : 'text-blue-400 group-hover:text-blue-300' }} transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <span class="font-medium">Profile</span>
                    @if(str_starts_with($currentRoute, 'doctor.profile.'))
                        <span class="ml-auto bg-blue-500/20 text-blue-300 text-xs py-0.5 px-1.5 rounded-sm">Active</span>
                    @endif
                </a>
            </li>
            
            <li class="mt-6">
                <div class="flex items-center px-4 mb-3">
                    <div class="h-px flex-1 bg-gradient-to-r from-transparent via-blue-600/30 to-transparent"></div>
                    <p class="px-3 text-xs font-semibold text-blue-300/80 uppercase tracking-wider">System</p>
                    <div class="h-px flex-1 bg-gradient-to-r from-transparent via-blue-600/30 to-transparent"></div>
                </div>
            </li>
            
            <!-- <li>
                <a href="{{ route('dashboard') }}"
                   class="nav-item group flex items-center px-4 py-3 rounded-lg hover:text-white transition-all duration-200">
                    <div class="mr-3 flex items-center justify-center w-6 h-6 text-blue-400 group-hover:text-blue-300 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                    </div>
                    <span class="font-medium">Main Dashboard</span>
                </a>
            </li> -->
            
            <li>
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" 
                       class="nav-item group flex items-center px-4 py-3 rounded-lg w-full hover:text-white transition-all duration-200">
                        <div class="mr-3 flex items-center justify-center w-6 h-6 text-blue-400 group-hover:text-blue-300 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v1a1 1 0 102 0V9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="font-medium">Logout</span>
                    </button>
                </form>
            </li>
        </ul>
    </nav>
    
    <!-- Footer -->
    <div class="p-4 border-t border-gray-800/40">
        <div class="text-center text-xs text-gray-500">
            <p class="text-blue-400/60">© 2025 Supehdah - Doctor Portal</p>
            <p class="mt-1 text-blue-400/40">v1.2.0</p>
        </div>
    </div>
</div>
