<x-app-layout>
    <!-- Doctor Notification System -->
    <div id="global-notification-container" class="fixed top-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <div id="global-popup-notification-container" class="fixed bottom-4 right-4 z-50 space-y-2" style="max-width: 350px;"></div>
    <audio id="global-notification-sound" src="{{ asset('sounds/noti.mp3') }}" preload="auto"></audio>
    
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex space-x-6">
            
            {{-- Sidebar --}}
            <div class="w-1/4">
                @include('doctor.components.sidebar')
            </div>
            
            {{-- Main Content --}}
            <div class="flex-1">
                <div class="bg-white shadow-xl rounded-lg p-8">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">Doctor Profile</h1>
                    
                    @if(session('success'))
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif
                    
                    {{-- Profile Info --}}
                    <div class="mb-8">
                        <div class="flex flex-col md:flex-row items-start md:items-center">
                            <div class="mr-8 mb-4 md:mb-0">
                                @if($doctor->photo)
                                    <img src="{{ asset('storage/doctor_photos/' . $doctor->photo) }}" alt="{{ $doctor->first_name }}" class="w-32 h-32 rounded-full object-cover">
                                @else
                                    <div class="w-32 h-32 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-3xl font-bold text-blue-600">{{ substr($doctor->first_name, 0, 1) . substr($doctor->last_name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold">Dr. {{ $doctor->first_name }} {{ $doctor->middle_name ? $doctor->middle_name . ' ' : '' }}{{ $doctor->last_name }}</h2>
                                <p class="text-blue-600 text-lg">{{ $doctor->specialization }}</p>
                                <p class="text-gray-500">{{ $doctor->experience_years }} {{ Str::plural('year', $doctor->experience_years) }} of experience</p>
                                <p class="text-gray-500">License: {{ $doctor->license_number }}</p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Tabs --}}
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex">
                            <button type="button" onclick="switchTab('profile')" id="profile-tab" class="tab-button border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                Profile Information
                            </button>
                            <button type="button" onclick="switchTab('password')" id="password-tab" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm ml-8">
                                Change Password
                            </button>
                        </nav>
                    </div>
                    
                    {{-- Profile Form --}}
                    <div id="profile-tab-content" class="tab-content">
                        <form action="{{ route('doctor.profile.update') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $doctor->first_name) }}" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('first_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="middle_name" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                                    <input type="text" id="middle_name" name="middle_name" value="{{ old('middle_name', $doctor->middle_name) }}" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                </div>
                                
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $doctor->last_name) }}" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('last_name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" id="email" name="email" value="{{ old('email', $doctor->email) }}" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('email')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number', $doctor->phone_number) }}" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('phone_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                                    <input type="text" id="specialization" name="specialization" value="{{ old('specialization', $doctor->specialization) }}" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('specialization')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="license_number" class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                                    <input type="text" id="license_number" name="license_number" value="{{ old('license_number', $doctor->license_number) }}" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('license_number')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="experience_years" class="block text-sm font-medium text-gray-700 mb-1">Years of Experience</label>
                                    <input type="number" id="experience_years" name="experience_years" value="{{ old('experience_years', $doctor->experience_years) }}" min="0" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('experience_years')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                                    <textarea id="bio" name="bio" rows="4" 
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">{{ old('bio', $doctor->bio) }}</textarea>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                                    <input type="file" id="photo" name="photo" accept="image/*" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('photo')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    @if($doctor->photo)
                                        <p class="mt-1 text-xs text-gray-500">Current photo: {{ $doctor->photo }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-150">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    {{-- Password Form --}}
                    <div id="password-tab-content" class="tab-content hidden">
                        <form action="{{ route('doctor.profile.update-password') }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="space-y-4 max-w-md">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('current_password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" id="password" name="password" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    @error('password')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-150">
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Show the selected tab content
            document.getElementById(tab + '-tab-content').classList.remove('hidden');
            
            // Update tab button styles
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            document.getElementById(tab + '-tab').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById(tab + '-tab').classList.add('border-blue-500', 'text-blue-600');
        }
        
        // If there are password errors, switch to password tab
        @if($errors->has('current_password') || $errors->has('password'))
            document.addEventListener('DOMContentLoaded', function() {
                switchTab('password');
            });
        @endif
    </script>

    <!-- Doctor Role Indicator for JS -->
    <div class="hidden" data-role="doctor"></div>

    <!-- Global Notification System Script -->
    <script src="{{ asset('js/global-notifications.js') }}"></script>
</x-app-layout>
