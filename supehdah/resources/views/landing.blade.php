@extends('layouts.app')

@section('title', 'PurrfectPaw - Veterinary Practice Management System')

@section('content')
<div class="min-h-screen bg-white flex flex-col">
    <!-- Main Navigation - Professional and clean with business focus -->
    <nav class="sticky top-0 z-40 bg-white shadow-md backdrop-filter backdrop-blur-lg bg-opacity-95">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <img src="{{ asset('images/newlogo.png') }}" alt="PurrfectPaw" class="h-10 mr-2">
                <span class="text-gray-800 text-2xl font-bold">PurrfectPaw</span>
            </div>
            <div class="hidden lg:flex items-center space-x-8">
                <a href="#about" class="text-gray-700 hover:text-purple-500 transition font-medium relative group">
                    About Us
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-400 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a href="#benefits" class="text-gray-700 hover:text-purple-500 transition font-medium relative group">
                    Benefits
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-400 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a href="#features" class="text-gray-700 hover:text-purple-500 transition font-medium relative group">
                    Features
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-400 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a href="#pricing" class="text-gray-700 hover:text-purple-500 transition font-medium relative group">
                    Pricing
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-400 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a href="#testimonials" class="text-gray-700 hover:text-purple-500 transition font-medium relative group">
                    Testimonials
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-purple-400 transition-all duration-300 group-hover:w-full"></span>
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('login') }}" class="hidden md:inline-block text-purple-600 font-semibold hover:text-purple-700 transition">Log In</a>
                <a href="{{ route('register') }}" class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:from-purple-600 hover:to-indigo-700 transition shadow-md">Join Now</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section - Professional business focused hero with background image -->
    <div id="about" class="relative py-16 overflow-hidden" style="background-image: url('{{ asset('images/pic2.jpg') }}'); background-size: cover; background-position: center;">
        <!-- Background overlay for better text readability -->
        <div class="absolute inset-0 bg-blue-900/40"></div>
        
        <!-- Social Media Links (Left Side) -->
        <div class="fixed left-0 top-1/3 z-50 flex flex-col space-y-4 px-4 py-6 bg-white/30 backdrop-blur-sm rounded-r-lg shadow-lg social-panel">
            <a href="https://www.facebook.com/purrfectpaw" data-social="facebook" target="_blank" rel="noopener noreferrer" class="text-black-800 hover:text-indigo-900 transition-all transform hover:scale-125" title="Follow us on Facebook">
                <i class="fab fa-facebook-f text-xl"></i>
            </a>
            <a href="https://twitter.com/purrfectpaw" data-social="twitter" target="_blank" rel="noopener noreferrer" class="text-black-800 hover:text-indigo-900 transition-all transform hover:scale-125" title="Follow us on Twitter">
                <i class="fab fa-twitter text-xl"></i>
            </a>
            <a href="https://www.instagram.com/purrfectpaw" data-social="instagram" target="_blank" rel="noopener noreferrer" class="text-black-800 hover:text-indigo-900 transition-all transform hover:scale-125" title="Follow us on Instagram">
                <i class="fab fa-instagram text-xl"></i>
            </a>
            <a href="https://www.youtube.com/channel/purrfectpaw" data-social="youtube" target="_blank" rel="noopener noreferrer" class="text-black-800 hover:text-indigo-900 transition-all transform hover:scale-125" title="Subscribe to our YouTube channel">
                <i class="fab fa-youtube text-xl"></i>
            </a>
            <a href="mailto:purrf3ctpaw@gmail.com" data-social="email" class="text-black-800 hover:text-indigo-900 transition-all transform hover:scale-125" title="Email us">
                <i class="fas fa-envelope text-xl"></i>
            </a>
        </div>
        
        <!-- Wavy background -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <svg class="absolute bottom-0 left-0 right-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="#ffffff" fill-opacity="1" d="M0,96L30,112C60,128,120,160,180,181.3C240,203,300,213,360,202.7C420,192,480,160,540,160C600,160,660,192,720,192C780,192,840,160,900,165.3C960,171,1020,213,1080,218.7C1140,224,1200,192,1260,181.3C1320,171,1380,181,1410,186.7L1440,192L1440,320L1410,320C1380,320,1320,320,1260,320C1200,320,1140,320,1080,320C1020,320,960,320,900,320C840,320,780,320,720,320C660,320,600,320,540,320C480,320,420,320,360,320C300,320,240,320,180,320C120,320,60,320,30,320L0,320Z"></path>
            </svg>
            
            <!-- Paw print decorations -->
            <div class="absolute top-10 left-[10%] text-white/40 text-5xl">
                <i class="fas fa-paw transform rotate-12"></i>
            </div>
            <div class="absolute top-40 right-[15%] text-white/40 text-4xl">
                <i class="fas fa-paw transform -rotate-15"></i>
            </div>
        </div>
        
        <div class="container mx-auto px-6 pt-12 pb-32 relative z-10">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="max-w-2xl">
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-8 leading-tight">We Provide Better Care For Your Pets</h1>
                    <p class="text-white/90 text-lg md:text-xl mb-10 mx-auto">
                        Give your clients the convenience of online booking, manage appointments effortlessly, and grow your veterinary practice with our all-in-one platform.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" class="bg-white text-blue-500 px-8 py-4 rounded-md font-semibold hover:bg-gray-100 transition shadow-md flex items-center justify-center">
                            <i class="fas fa-clinic-medical mr-2"></i> Book Now
                        </a>
                        <a href="#demo-request" class="bg-transparent text-white border-2 border-white px-8 py-4 rounded-md font-semibold hover:bg-white/10 transition flex items-center justify-center">
                            <i class="fas fa-phone-alt mr-2"></i> Need Help? +1230001
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Pagination dots -->
            <div class="flex justify-center mt-12 space-x-2">
                <span class="w-3 h-3 bg-white rounded-full"></span>
                <span class="w-3 h-3 bg-white/50 rounded-full"></span>
                <span class="w-3 h-3 bg-white/50 rounded-full"></span>
                <span class="w-3 h-3 bg-white/50 rounded-full"></span>
            </div>
        </div>
    </div>

    <!-- Key Benefits for Clinics - Business focused -->
    <div id="benefits" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <span class="inline-block px-3 py-1 bg-purple-100 text-purple-600 rounded-full text-sm font-semibold mb-3">WHY CHOOSE US</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Why Veterinary Clinics Choose PurrfectPaw</h2>
                <div class="w-24 h-1 bg-purple-400 mx-auto mt-4 rounded-full"></div>
                <p class="text-gray-600 mt-6 max-w-2xl mx-auto">Modernize your practice with our comprehensive clinic management solution</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-xl p-8 border border-gray-100 shadow-md hover:shadow-xl transition duration-300 text-center feature-card">
                    <div class="inline-flex items-center justify-center p-5 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-full mb-6">
                        <i class="fas fa-calendar-check text-purple-500 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">24/7 Online Booking</h3>
                    <p class="text-gray-600">Give your clients the convenience of booking appointments anytime, reducing phone calls and administrative overhead.</p>
                </div>
                
                <div class="bg-white rounded-xl p-8 border border-gray-100 shadow-md hover:shadow-xl transition duration-300 text-center feature-card">
                    <div class="inline-flex items-center justify-center p-5 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-full mb-6">
                        <i class="fas fa-users text-purple-500 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Expanded Client Base</h3>
                    <p class="text-gray-600">Join our network of trusted clinics and attract new patients through our platform's marketplace and referral system.</p>
                </div>
                
                <div class="bg-white rounded-xl p-8 border border-gray-100 shadow-md hover:shadow-xl transition duration-300 text-center feature-card">
                    <div class="inline-flex items-center justify-center p-5 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-full mb-6">
                        <i class="fas fa-chart-line text-indigo-500 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Reduced No-Shows</h3>
                    <p class="text-gray-600">Automated reminders and confirmation systems ensure clients remember their appointments, maximizing your schedule utilization.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- How It Works - Step by Step Process for Clinics -->
    <div id="how-it-works" class="py-20 relative" style="background-color: #F0F0FF;">
        <div class="absolute top-0 left-0 w-full h-24 bg-white" style="clip-path: ellipse(70% 100% at center top);"></div>
        <div class="container mx-auto px-6 relative z-10">
            <div class="text-center mb-16">
                <span class="inline-block px-3 py-1 bg-indigo-100 text-indigo-600 rounded-full text-sm font-semibold mb-3">GET STARTED</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Get Your Clinic Online in Minutes</h2>
                <div class="w-24 h-1 bg-indigo-400 mx-auto mt-4 rounded-full"></div>
                <p class="text-gray-600 mt-6 max-w-2xl mx-auto">Join our network in three simple steps</p>
            </div>
            
            <div class="flex flex-col md:flex-row items-center justify-center space-y-12 md:space-y-0 md:space-x-6">
                <div class="flex flex-col items-center max-w-xs text-center transform transition-all duration-300 hover:scale-105">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-500 text-white rounded-full flex items-center justify-center text-2xl font-bold mb-6 shadow-lg">
                        <span class="relative z-10">1</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Register Your Clinic</h3>
                    <p class="text-gray-600">Create your clinic profile with services, hours, and practitioner information.</p>
                </div>
                
                <div class="hidden md:flex flex-col items-center justify-center w-24">
                    <div class="h-1.5 bg-gradient-to-r from-purple-400 to-indigo-400 w-full rounded-full relative">
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-indigo-400 rounded-full animate-pulse"></div>
                    </div>
                </div>
                
                <div class="flex flex-col items-center max-w-xs text-center transform transition-all duration-300 hover:scale-105">
                    <div class="w-20 h-20 bg-gradient-to-br from-indigo-400 to-indigo-500 text-white rounded-full flex items-center justify-center text-2xl font-bold mb-6 shadow-lg">
                        <span class="relative z-10">2</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Configure Settings</h3>
                    <p class="text-gray-600">Set up your availability, scheduling preferences, and appointment types.</p>
                </div>
                
                <div class="hidden md:flex flex-col items-center justify-center w-24">
                    <div class="h-1.5 bg-gradient-to-r from-indigo-400 to-purple-400 w-full rounded-full relative">
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-purple-400 rounded-full animate-pulse"></div>
                    </div>
                </div>
                
                <div class="flex flex-col items-center max-w-xs text-center transform transition-all duration-300 hover:scale-105">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-400 to-purple-500 text-white rounded-full flex items-center justify-center text-2xl font-bold mb-6 shadow-lg">
                        <span class="relative z-10">3</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Start Accepting Bookings</h3>
                    <p class="text-gray-600">Your clinic is now visible to pet owners who can book appointments online.</p>
                </div>
            </div>
            
            <div class="flex justify-center mt-16">
                <a href="{{ route('register') }}" class="bg-gradient-to-r from-indigo-500 to-purple-500 text-white px-8 py-3.5 rounded-lg font-semibold hover:from-indigo-600 hover:to-purple-600 transition shadow-lg flex items-center">
                    <i class="fas fa-clinic-medical mr-2"></i> Join Our Network Today
                </a>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 w-full h-24 bg-purple-50" style="clip-path: ellipse(70% 100% at center bottom);"></div>
    </div>

    <!-- Features Section - Modern elegant style for clinics -->
    <div id="features" class="py-20 bg-gradient-to-b from-white to-purple-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <span class="inline-block px-3 py-1 bg-indigo-100 text-indigo-600 rounded-full text-sm font-semibold mb-3">POWERFUL TOOLS</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Platform Features</h2>
                <div class="w-24 h-1 bg-indigo-400 mx-auto mt-4 rounded-full"></div>
                <p class="text-gray-600 mt-6 max-w-2xl mx-auto">Powerful tools to modernize and grow your veterinary practice</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <!-- Predefined feature cards with clinic-focused features -->
                <div class="bg-white hover:bg-gradient-to-br hover:from-purple-50 hover:to-indigo-50 border border-gray-100 rounded-xl transition duration-300 p-6 flex flex-col items-center shadow-sm hover:shadow-md feature-card">
                    <div class="mb-4 text-purple-500 bg-purple-100 p-4 rounded-full">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                    <h3 class="text-center text-gray-800 font-semibold text-lg mb-2">Online Scheduling</h3>
                    <p class="text-gray-600 text-sm text-center">24/7 online booking portal with customizable appointment types and durations.</p>
                </div>
                
                <div class="bg-white hover:bg-gradient-to-br hover:from-purple-50 hover:to-indigo-50 border border-gray-100 rounded-xl transition duration-300 p-6 flex flex-col items-center shadow-sm hover:shadow-md feature-card">
                    <div class="mb-4 text-indigo-500 bg-indigo-100 p-4 rounded-full">
                        <i class="fas fa-bell text-2xl"></i>
                    </div>
                    <h3 class="text-center text-gray-800 font-semibold text-lg mb-2">Automated Reminders</h3>
                    <p class="text-gray-600 text-sm text-center">Reduce no-shows with automatic email and SMS appointment reminders.</p>
                </div>
                
                <div class="bg-white hover:bg-gradient-to-br hover:from-purple-50 hover:to-indigo-50 border border-gray-100 rounded-xl transition duration-300 p-6 flex flex-col items-center shadow-sm hover:shadow-md feature-card">
                    <div class="mb-4 text-purple-500 bg-purple-100 p-4 rounded-full">
                        <i class="fas fa-file-medical text-2xl"></i>
                    </div>
                    <h3 class="text-center text-gray-800 font-semibold text-lg mb-2">Medical Records</h3>
                    <p class="text-gray-600 text-sm text-center">Digital patient records with treatment history and medication tracking.</p>
                </div>
                
                <div class="bg-white hover:bg-gradient-to-br hover:from-purple-50 hover:to-indigo-50 border border-gray-100 rounded-xl transition duration-300 p-6 flex flex-col items-center shadow-sm hover:shadow-md feature-card">
                    <div class="mb-4 text-indigo-500 bg-indigo-100 p-4 rounded-full">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <h3 class="text-center text-gray-800 font-semibold text-lg mb-2">Business Analytics</h3>
                    <p class="text-gray-600 text-sm text-center">Powerful reporting tools to track practice performance and growth.</p>
                </div>
                
                <div class="bg-white hover:bg-gradient-to-br hover:from-purple-50 hover:to-indigo-50 border border-gray-100 rounded-xl transition duration-300 p-6 flex flex-col items-center shadow-sm hover:shadow-md feature-card">
                    <div class="mb-4 text-purple-500 bg-purple-100 p-4 rounded-full">
                        <i class="fas fa-comments text-2xl"></i>
                    </div>
                    <h3 class="text-center text-gray-800 font-semibold text-lg mb-2">Client Communication</h3>
                    <p class="text-gray-600 text-sm text-center">Secure messaging system between clinic staff and pet owners.</p>
                </div>
                
                <div class="bg-white hover:bg-gradient-to-br hover:from-purple-50 hover:to-indigo-50 border border-gray-100 rounded-xl transition duration-300 p-6 flex flex-col items-center shadow-sm hover:shadow-md feature-card">
                    <div class="mb-4 text-indigo-500 bg-indigo-100 p-4 rounded-full">
                        <i class="fas fa-credit-card text-2xl"></i>
                    </div>
                    <h3 class="text-center text-gray-800 font-semibold text-lg mb-2">Payment Processing</h3>
                    <p class="text-gray-600 text-sm text-center">Collect deposits and process payments securely online.</p>
                </div>
                
                <div class="bg-white hover:bg-gradient-to-br hover:from-purple-50 hover:to-indigo-50 border border-gray-100 rounded-xl transition duration-300 p-6 flex flex-col items-center shadow-sm hover:shadow-md feature-card">
                    <div class="mb-4 text-purple-500 bg-purple-100 p-4 rounded-full">
                        <i class="fas fa-mobile-alt text-2xl"></i>
                    </div>
                    <h3 class="text-center text-gray-800 font-semibold text-lg mb-2">Mobile App</h3>
                    <p class="text-gray-600 text-sm text-center">Manage your clinic on the go with our dedicated mobile application.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing Section - Business focused pricing plans -->
    <div id="pricing" class="py-16" style="background-color: #F5F5FF;">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800">Simple, Transparent Pricing</h2>
                <p class="text-gray-600 mt-2">One plan, two convenient billing options for your veterinary practice</p>
            </div>
            
            <!-- Billing Toggle -->
            <div class="flex justify-center mb-8">
                <div class="bg-white rounded-lg p-1 inline-flex shadow-sm">
                    <button id="monthly-billing" class="px-4 py-2 rounded-md font-medium text-purple-700 bg-purple-100 focus:outline-none cursor-pointer">
                        Monthly Billing
                    </button>
                    <button id="yearly-billing" class="px-4 py-2 rounded-md font-medium text-gray-700 hover:bg-gray-100 focus:outline-none transition cursor-pointer">
                        Annual Billing <span class="text-green-600 text-xs font-bold">Save ₱20,000</span>
                    </button>
                </div>
            </div>
            
            <div class="flex justify-center">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl">
                    <!-- Monthly Subscription Plan -->
                    <div id="monthly-plan" class="bg-white rounded-lg overflow-hidden shadow-md border-2 border-purple-400 transform scale-105 z-10 transition hover:shadow-lg">
                    <div class="p-1 bg-purple-400 text-white text-center text-sm font-semibold">
                        STANDARD PLAN
                    </div>
                    <div class="p-6 bg-purple-50 border-b border-purple-100">
                        <h3 class="text-2xl font-bold text-gray-800">Monthly</h3>
                        <div class="mt-4 flex items-baseline">
                            <span class="text-4xl font-extrabold text-gray-900">₱10,000</span>
                            <span class="ml-1 text-xl font-semibold text-gray-500">/month</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">Perfect for practices of all sizes</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Full access to all features</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Unlimited appointments</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Unlimited practitioners</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Email & SMS reminders</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Advanced analytics & reporting</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Client communication tools</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Priority support</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="{{ route('clinic.register.select-plan', ['plan' => 'monthly']) }}" class="block w-full bg-purple-500 text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-purple-600 transition">
                                Get Started Monthly
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Yearly Subscription Plan -->
                <div id="yearly-plan" class="bg-white rounded-lg overflow-hidden shadow-sm border border-purple-100 transition hover:shadow-md hidden">
                    <div class="p-1 bg-green-400 text-white text-center text-sm font-semibold">
                        SAVE ₱20,000 (16.7%)
                    </div>
                    <div class="p-6 bg-gray-50 border-b border-gray-100">
                        <h3 class="text-2xl font-bold text-gray-800">Annual</h3>
                        <div class="mt-4 flex items-baseline">
                            <span class="text-4xl font-extrabold text-gray-900">₱120,000</span>
                            <span class="ml-1 text-xl font-semibold text-gray-500">/year</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">₱10,000/month billed annually</p>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Everything in monthly plan</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Save ₱20,000 per year</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Priority implementation</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Dedicated account manager</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Free staff training sessions</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Custom integrations</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                <span class="text-gray-700">Quarterly business reviews</span>
                            </li>
                        </ul>
                        <div class="mt-8">
                            <a href="{{ route('clinic.register.select-plan', ['plan' => 'yearly']) }}" class="block w-full bg-purple-500 text-white text-center px-4 py-3 rounded-md font-semibold hover:bg-purple-600 transition">
                                Get Started Annually
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Need Help Card -->
                <div class="bg-white rounded-lg overflow-hidden shadow-sm border border-purple-100 transition hover:shadow-lg">
                    <div class="p-6 bg-gradient-to-br from-indigo-50 to-purple-50 border-b border-gray-100">
                        <h3 class="text-2xl font-bold text-gray-800">Need Help?</h3>
                        <div class="mt-4">
                            <p class="text-gray-600">Not sure which option is best for your clinic?</p>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 mb-6">Our team can help you choose the right plan and answer any questions you have about our veterinary practice management system.</p>
                        <div class="space-y-4">
                            <div class="flex items-center p-3 hover:bg-purple-50 rounded-lg transition">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-phone-alt text-purple-500"></i>
                                </div>
                                <span class="text-gray-700">+63 (2) 8888-7777</span>
                            </div>
                            <div class="flex items-center p-3 hover:bg-purple-50 rounded-lg transition">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-envelope text-purple-500"></i>
                                </div>
                                <span class="text-gray-700">purrf3ctpaw@gmail.com</span>
                            </div>
                            <div class="flex items-center p-3 hover:bg-purple-50 rounded-lg transition">
                                <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-calendar-check text-purple-500"></i>
                                </div>
                                <span class="text-gray-700">Book a demo</span>
                            </div>
                        </div>
                        <div class="mt-8">
                            <a href="#demo-request" class="block w-full bg-gradient-to-r from-purple-400 to-indigo-400 text-white text-center px-4 py-3 rounded-lg font-semibold hover:from-purple-500 hover:to-indigo-500 transition shadow-md">
                                Contact Sales
                            </a>
                        </div>
                    </div>
                </div>
                </div>
            </div>
            
            <!-- Inline JavaScript for the pricing toggle functionality -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const monthlyBtn = document.getElementById('monthly-billing');
                    const yearlyBtn = document.getElementById('yearly-billing');
                    const monthlyPlan = document.getElementById('monthly-plan');
                    const yearlyPlan = document.getElementById('yearly-plan');
                    
                    // Set initial state
                    function setMonthly() {
                        monthlyBtn.classList.add('bg-purple-100', 'text-purple-700');
                        yearlyBtn.classList.remove('bg-purple-100', 'text-purple-700');
                        monthlyPlan.classList.remove('hidden');
                        yearlyPlan.classList.add('hidden');
                        monthlyPlan.classList.add('transform', 'scale-105', 'z-10', 'border-2', 'border-purple-400', 'shadow-md');
                        yearlyPlan.classList.remove('transform', 'scale-105', 'z-10', 'border-2', 'border-purple-400', 'shadow-md');
                    }
                    
                    function setYearly() {
                        yearlyBtn.classList.add('bg-purple-100', 'text-purple-700');
                        monthlyBtn.classList.remove('bg-purple-100', 'text-purple-700');
                        yearlyPlan.classList.remove('hidden');
                        monthlyPlan.classList.add('hidden');
                        yearlyPlan.classList.add('transform', 'scale-105', 'z-10', 'border-2', 'border-purple-400', 'shadow-md');
                        monthlyPlan.classList.remove('transform', 'scale-105', 'z-10', 'border-2', 'border-purple-400', 'shadow-md');
                    }
                    
                    // Set monthly as default
                    setMonthly();
                    
                    // Add click event listeners
                    monthlyBtn.addEventListener('click', setMonthly);
                    yearlyBtn.addEventListener('click', setYearly);
                });
            </script>
            
            <!-- FAQ Section -->
            <div class="mt-16">
                <div class="text-center mb-8">
                    <h3 class="text-2xl font-bold text-gray-800">Frequently Asked Questions</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h4 class="font-bold text-purple-600 mb-2">What payment methods do you accept?</h4>
                        <p class="text-gray-700">We accept all major credit cards, PayPal, and bank transfers. For annual plans, we also accept checks and direct deposits.</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h4 class="font-bold text-purple-600 mb-2">Can I switch between monthly and annual billing?</h4>
                        <p class="text-gray-700">Yes, you can switch from monthly to annual billing at any time to take advantage of our discounted rate. When switching from annual to monthly, the change will take effect after your current annual subscription ends.</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h4 class="font-bold text-purple-600 mb-2">Do you offer a free trial?</h4>
                        <p class="text-gray-700">Yes, we offer a 14-day free trial so you can experience all the features before committing to a subscription. No credit card required.</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow-sm">
                        <h4 class="font-bold text-purple-600 mb-2">Is my data secure?</h4>
                        <p class="text-gray-700">Absolutely. We use industry-standard encryption and security practices to protect your clinic and patient data.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments & Ratings Section - Public feedback -->
    <div id="comments" class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Share Your Feedback</h2>
                <p class="text-gray-600 mt-2">Tell us what you think about PurrfectPaw. Rate us and leave a comment — we read every submission.</p>
            </div>

            <div class="max-w-3xl mx-auto mb-8">
                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-50 border-l-4 border-green-400 text-green-700 text-sm">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border-l-4 border-red-400 text-red-700 text-sm">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('site.comments.store') }}" method="POST" class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="name" placeholder="Your name (optional)" value="{{ old('name') }}" class="border border-gray-300 rounded-lg p-2 w-full text-sm">
                        <input type="email" name="email" placeholder="Email (optional)" value="{{ old('email') }}" class="border border-gray-300 rounded-lg p-2 w-full text-sm">
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm text-gray-700 mb-2">Your rating</label>
                        <div id="star-rating" class="flex items-center space-x-2">
                            <input type="hidden" name="rating" id="rating-input" value="{{ old('rating', 5) }}">
                            @for($i=1;$i<=5;$i++)
                                <button type="button" class="star-btn text-xl text-gray-300 hover:text-yellow-500" data-value="{{ $i }}">
                                    <i class="fas fa-star"></i>
                                </button>
                            @endfor
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm text-gray-700 mb-2">Comment</label>
                        <textarea name="comment" rows="4" class="border border-gray-300 rounded-lg p-2 w-full text-sm" placeholder="Write your feedback here...">{{ old('comment') }}</textarea>
                    </div>

                    <div class="mt-4 text-right">
                        <button type="submit" class="bg-purple-600 text-white px-5 py-2 rounded-lg font-semibold hover:bg-purple-700 transition">Submit Feedback</button>
                    </div>
                </form>
            </div>

            <div class="max-w-5xl mx-auto">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent feedback</h3>
                @php $comments = \App\Models\SiteComment::latest()->take(10)->get(); @endphp
                @if($comments->isEmpty())
                    <p class="text-gray-500">No feedback yet — be the first to share your thoughts.</p>
                @else
                    <div class="space-y-4">
                        @foreach($comments as $c)
                            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-semibold">{{ $c->name ? strtoupper(substr($c->name,0,1)) : 'U' }}</div>
                                            <div>
                                                <div class="text-sm font-semibold text-gray-800">{{ $c->name ?? 'Anonymous' }}</div>
                                                <div class="text-xs text-gray-500">{{ optional($c->created_at)->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-yellow-500">
                                            @for($s=1;$s<=5;$s++)
                                                @if($s <= $c->rating)
                                                    <i class="fas fa-star"></i>
                                                @else
                                                    <i class="far fa-star text-gray-300"></i>
                                                @endif
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                @if($c->comment)
                                    <p class="mt-3 text-gray-700">{{ $c->comment }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Testimonials Section - Clinic Testimonials -->
    <div id="testimonials" class="py-16 bg-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800">What Veterinary Professionals Say</h2>
                <p class="text-gray-600 mt-2">Success stories from clinics using our platform</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="rounded-lg p-6 shadow-sm hover:shadow-md transition" style="background-color: #F0E6FF;">
                    <div class="text-purple-400 flex mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-700 mb-6 italic">"Since implementing PurrfectPaw, our no-show rate has decreased by 35%. The automated reminders are a game-changer for our practice efficiency."</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-200 rounded-full flex items-center justify-center text-purple-700 font-bold">D</div>
                        <div class="ml-3">
                            <p class="font-semibold text-gray-800">Dr. Michael R.</p>
                            <p class="text-gray-500 text-sm">Veterinary Clinic Owner</p>
                        </div>
                    </div>
                </div>
                
                <div class="rounded-lg p-6 shadow-sm hover:shadow-md transition" style="background-color: #E6E6FA;">
                    <div class="text-purple-400 flex mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-700 mb-6 italic">"Our reception staff is spending 70% less time on the phone managing appointments. This has allowed us to focus more on in-clinic patient care and service."</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-indigo-200 rounded-full flex items-center justify-center text-indigo-700 font-bold">S</div>
                        <div class="ml-3">
                            <p class="font-semibold text-gray-800">Sarah T.</p>
                            <p class="text-gray-500 text-sm">Practice Manager</p>
                        </div>
                    </div>
                </div>
                
                <div class="rounded-lg p-6 shadow-sm hover:shadow-md transition" style="background-color: #E0E6FF;">
                    <div class="text-purple-400 flex mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="text-gray-700 mb-6 italic">"The analytics dashboard has given us insights into our busiest times and most popular services, helping us optimize staffing and increase revenue by 22% in six months."</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center text-blue-700 font-bold">J</div>
                        <div class="ml-3">
                            <p class="font-semibold text-gray-800">Dr. Jennifer K.</p>
                            <p class="text-gray-500 text-sm">Multi-Location Practice Director</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section with pastel colors - Clinic focused -->
    <div id="demo-request" class="py-16" style="background: linear-gradient(to right, #E0E6FF, #F0E6FF);">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Ready to Modernize Your Veterinary Practice?</h2>
            <p class="text-gray-700 text-lg mb-8 max-w-2xl mx-auto">
                Join hundreds of successful veterinary clinics that have streamlined operations and increased revenue with PurrfectPaw.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="bg-purple-500 text-white px-8 py-3 rounded-md font-semibold hover:bg-purple-600 transition shadow-md">
                    Register Your Clinic
                </a>
                <a href="#pricing" class="bg-transparent text-purple-700 border-2 border-purple-400 px-8 py-3 rounded-md font-semibold hover:bg-purple-50 transition">
                    See Pricing Plans
                </a>
            </div>
        </div>
    </div>

    <!-- Footer - Professional Business Focus -->
    <footer class="bg-indigo-900 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <img src="{{ asset('images/pet-logo.png') }}" alt="PurrfectPaw" class="h-10 mr-2">
                        <span class="text-small font-bold">PurrfectPaw</span>
                    </div>
                    <p class="text-indigo-200">Comprehensive practice management platform for modern veterinary clinics.</p>
                    <div class="flex space-x-4 mt-6">
                        <a href="https://www.facebook.com" target="_blank" class="text-black-300 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://www.twitter.com" target="_blank" class="text-black-300 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.instagram.com" target="_blank" class="text-black-300 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://www.linkedin.com" target="_blank" class="text-black-300 hover:text-white transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="https://www.youtube.com" target="_blank" class="text-black-300 hover:text-white transition">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-4 text-purple-200">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#about" class="text-indigo-200 hover:text-white transition">About Us</a></li>
                        <li><a href="#benefits" class="text-indigo-200 hover:text-white transition">Benefits</a></li>
                        <li><a href="#features" class="text-indigo-200 hover:text-white transition">Features</a></li>
                        <li><a href="#pricing" class="text-indigo-200 hover:text-white transition">Pricing</a></li>
                        <li><a href="#testimonials" class="text-indigo-200 hover:text-white transition">Testimonials</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-4 text-purple-200">Resources</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-indigo-200 hover:text-white transition">Practice Management Blog</a></li>
                        <li><a href="#" class="text-indigo-200 hover:text-white transition">Integration Partners</a></li>
                        <li><a href="#" class="text-indigo-200 hover:text-white transition">Case Studies</a></li>
                        <li><a href="#" class="text-indigo-200 hover:text-white transition">FAQs</a></li>
                        <li><a href="#" class="text-indigo-200 hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="#" class="text-indigo-200 hover:text-white transition">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-4 text-purple-200">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-envelope text-purple-400 mt-1 mr-2"></i>
                            <span class="text-indigo-200">purrf3ctpaw@gmail.com</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone-alt text-purple-400 mt-1 mr-2"></i>
                            <span class="text-indigo-200">+63 (2) 8888-7777</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-headset text-purple-400 mt-1 mr-2"></i>
                            <span class="text-indigo-200">Support: +63 (2) 8888-7777</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-purple-400 mt-1 mr-2"></i>
                            <span class="text-indigo-200">123 Business Plaza, Suite 500<br>Manila, Philippines</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-indigo-800 mt-12 pt-8 text-center">
                <p class="text-indigo-300">&copy; {{ date('Y') }} PurrfectPaw. All rights reserved.</p>
            </div>
        </div>
    </footer>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
<style>
    /* Smooth scrolling */
    html {
        scroll-behavior: smooth;
    }
    
    /* Focus style for accessibility */
    a:focus, button:focus {
        outline: 2px solid #0d9488;
        outline-offset: 2px;
    }
    
    /* Hero image enhancements */
    .hero-image-container {
        position: relative;
        transition: transform 0.3s ease;
    }
    
    .hero-image-container:hover {
        transform: translateY(-5px);
    }
    
    /* Improved card hover effects */
    .feature-card {
        transition: all 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.1), 0 10px 10px -5px rgba(124, 58, 237, 0.04);
    }
    
    /* Modern section transitions */
    .section-transition {
        position: relative;
        overflow: hidden;
    }
    
    /* Custom styling for pricing toggle */
    .pricing-toggle {
        position: relative;
        display: inline-flex;
        border-radius: 9999px;
        background-color: #f3f4f6;
        padding: 0.25rem;
        transition: all 0.3s ease;
    }
    
    .pricing-toggle-option {
        position: relative;
        z-index: 10;
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .pricing-toggle-option.active {
        color: white;
    }
    
    .pricing-toggle-slider {
        position: absolute;
        left: 0.25rem;
        top: 0.25rem;
        height: calc(100% - 0.5rem);
        border-radius: 9999px;
        background-color: #8b5cf6;
        transition: all 0.3s ease;
    }
    
    /* Social media links side panel */
    .social-panel {
        transition: all 0.3s ease;
        position: fixed;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
    }
    
    .social-panel a {
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
    }
    
    .social-panel:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .social-panel a:hover i.fa-facebook-f {
        color: #1877f2;
    }
    
    .social-panel a:hover i.fa-twitter {
        color: #1da1f2;
    }
    
    .social-panel a:hover i.fa-instagram {
        color: #e1306c;
    }
    
    .social-panel a:hover i.fa-youtube {
        color: #ff0000;
    }
    
    .social-panel a:hover i.fa-envelope {
        color: #ffd700;
    }
    
    /* Floating paw animations */
    @keyframes float {
        0% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-10px) rotate(5deg); }
        100% { transform: translateY(0px) rotate(0deg); }
    }
    
    .floating-paw {
        animation: float 6s ease-in-out infinite;
    }
    
    .floating-paw:nth-child(2) {
        animation-delay: 1s;
    }
    
    .floating-paw:nth-child(3) {
        animation-delay: 2s;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/social-links.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const stars = document.querySelectorAll('#star-rating .star-btn');
        const ratingInput = document.getElementById('rating-input');

        function setStars(value) {
            stars.forEach((btn) => {
                const val = parseInt(btn.getAttribute('data-value'));
                if (val <= value) {
                    btn.classList.remove('text-gray-300');
                    btn.classList.add('text-yellow-400');
                } else {
                    btn.classList.remove('text-yellow-400');
                    btn.classList.add('text-gray-300');
                }
            });
        }

        // initialize
        if (ratingInput) {
            setStars(parseInt(ratingInput.value || 5));
        }

        stars.forEach((btn) => {
            btn.addEventListener('click', function () {
                const v = parseInt(this.getAttribute('data-value'));
                ratingInput.value = v;
                setStars(v);
            });
        });
    });
</script>
@endpush
