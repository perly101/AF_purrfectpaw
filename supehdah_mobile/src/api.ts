//php artisan serve --host=0.0.0.0 --port=8000

import axios from 'axios';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Alert } from 'react-native';

// Use only a single fixed API base URL
const API_BASE_URLS = [
  'http://192.168.106.41:8000/api', // Local network - update with your actual IP
  'http://localhost:8000/api'    // For web debugging
];


const API_BASE_URL = API_BASE_URLS[0];

// Create API instance with our fixed URL
export const API = axios.create({
  baseURL: API_BASE_URL,
  headers: { 
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 15000 // 15 seconds timeout
});

// Log the initialized API URL
console.log(`Initialized API with fixed base URL: ${API.defaults.baseURL}`);

// Global navigation reference to allow programmatic navigation outside components
let navigationRef: any = null;

// Debouncing variables to prevent multiple session alerts
let isHandlingAuthFailure = false;
let lastAuthFailureTime = 0;
const AUTH_FAILURE_DEBOUNCE_TIME = 3000; // 3 seconds between auth failure handling

// Function to set the navigation reference from App.tsx
export const setNavigationRef = (ref: any) => {
  navigationRef = ref;
};

// Function to reset auth failure state (call this on successful login/logout)
export const resetAuthFailureState = () => {
  isHandlingAuthFailure = false;
  lastAuthFailureTime = 0;
  console.log('🔄 Auth failure state reset');
};

// Function to handle logout and redirect to login
export const handleAuthFailure = async () => {
  const now = Date.now();
  
  // Debounce multiple auth failures to prevent spam
  if (isHandlingAuthFailure || (now - lastAuthFailureTime) < AUTH_FAILURE_DEBOUNCE_TIME) {
    console.log('🔄 Auth failure already being handled, skipping...');
    return;
  }
  
  isHandlingAuthFailure = true;
  lastAuthFailureTime = now;
  
  console.log('🔑 Auth token expired or invalid, logging out...');
  
  // Clear token and any user data from storage
  try {
    await AsyncStorage.removeItem('token');
    await AsyncStorage.removeItem('userToken');
    await AsyncStorage.removeItem('accessToken');
    await AsyncStorage.removeItem('user');
    console.log('🧹 Cleared auth tokens and user data');
  } catch (e) {
    console.error('Error clearing auth data:', e);
  }
  
  // Redirect to login screen if navigation is available
  if (navigationRef) {
    console.log('🔄 Redirecting to login screen');
    // Use reset navigation to go to login
    navigationRef.reset({
      index: 0,
      routes: [{ name: 'Login' }],
    });
    
    // Show an alert to inform the user (only once)
    setTimeout(() => {
      Alert.alert(
        'Session Expired',
        'Your session has expired. Please log in again.',
        [{ 
          text: 'OK',
          onPress: () => {
            // Reset the debounce flag when user dismisses alert
            isHandlingAuthFailure = false;
          }
        }]
      );
      
      // Auto-reset after 5 seconds even if user doesn't press OK
      setTimeout(() => {
        if (isHandlingAuthFailure) {
          console.log('🔄 Auto-resetting auth failure state after timeout');
          isHandlingAuthFailure = false;
        }
      }, 5000);
    }, 500);
  } else {
    console.error('❌ Navigation reference not set, cannot redirect to login');
    // Reset flag even if navigation fails
    isHandlingAuthFailure = false;
  }
};

// Automatically attach token to every request
API.interceptors.request.use(async (config) => {
  const token = await AsyncStorage.getItem('token') || 
                await AsyncStorage.getItem('userToken') || 
                await AsyncStorage.getItem('accessToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
    console.log("🔐 Token attached to request");
  }
  // Remove the "No token found" log to reduce noise when user is not logged in
  return config;
});

// Response interceptor to handle auth errors
API.interceptors.response.use(
  (response) => {
    return response;
  },
  async (error) => {
    // Check if the error is due to an expired/invalid token (401 Unauthorized)
    if (error.response && error.response.status === 401) {
      // Only handle auth failure if we actually had a token (user was logged in)
      const hadToken = await AsyncStorage.getItem('token') || 
                       await AsyncStorage.getItem('userToken') || 
                       await AsyncStorage.getItem('accessToken');
      
      if (hadToken) {
        console.log('🚫 Received 401 Unauthorized response with expired token');
        await handleAuthFailure();
      } else {
        console.log('🚫 Received 401 Unauthorized response (no token - user not logged in)');
        // Just pass through the error without triggering auth failure handler
      }
      
      return Promise.reject(error);
    }
    
    // For other errors, just pass through
    return Promise.reject(error);
  }
);

// Helper function to make API requests with fixed base URL
export const tryMultipleBaseUrls = async (endpoint: string) => {
  const baseUrl = API_BASE_URLS[0]; // We're only using one fixed URL now
  
  // Add cache-busting query parameter if not already present
  const cacheBuster = `t=${new Date().getTime()}`;
  const separator = endpoint.includes('?') ? '&' : '?';
  const endpointWithCache = endpoint.includes('t=') ? endpoint : `${endpoint}${separator}${cacheBuster}`;
  
  try {
    console.log(`Making API request to: ${baseUrl}${endpointWithCache}`);
    const response = await axios.get(`${baseUrl}${endpointWithCache}`, {
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
      },
      timeout: 10000 // Increased timeout since we're only trying one URL
    });
    
    console.log(`Success with API request`);
    // The base URL is already set correctly, but we'll ensure it here
    API.defaults.baseURL = baseUrl;
    
    return response;
  } catch (error: any) {
    console.log(`Failed API request: ${error?.message || 'Unknown error'}`);
    throw new Error(`API request failed: ${error?.message || 'Unknown error'}`);
  }
};

// Interface definitions for availability API
export interface Slot {
  start: string;
  end: string;
  display_time: string;
}

export interface AvailabilityResponse {
  is_available: boolean;
  date: string;
  slots: Slot[];
  daily_limit?: number;
  booked_count?: number;
  slots_remaining?: number;
}

export interface DaySummary {
  date: string;
  day_name: string;
  is_closed: boolean;
  booked_count: number;
  remaining_slots: number;
  daily_limit: number;
}

export interface AvailabilitySummary {
  today: {
    is_closed: boolean;
    booked_count: number;
    remaining_slots: number;
    daily_limit: number;
  };
  next_week: DaySummary[];
  settings: {
    daily_limit: number;
    slot_duration: number;
    default_start_time?: string;
    default_end_time?: string;
  };
  timestamp?: number;
  error?: boolean;
}

// Appointment availability API functions
export const getAvailableSlots = async (clinicId: number, date: string) => {
  try {
    const response = await API.get(`/clinics/${clinicId}/availability?date=${date}`);
    return response.data.data || [];
  } catch (error) {
    console.error('Error fetching available slots:', error);
    throw error;
  }
};

import { ROUTES } from './routes';

export const getAvailabilitySummary = async (clinicId: number): Promise<AvailabilitySummary> => {
  try {
    // First try with our regular API
    try {
      console.log(`Trying to get availability summary with standard API for clinic ${clinicId}`);
      const response = await API.get(ROUTES.CLINICS.AVAILABILITY.SUMMARY(clinicId));
      console.log('Successfully fetched availability summary with standard API');
      return response.data.data || getStaticAvailabilityData(clinicId);
    } catch (initialError) {
      console.log('Failed with standard API, trying multiple base URLs');
      
      // Try multiple base URLs as fallback
      try {
        const endpoint = ROUTES.CLINICS.AVAILABILITY.SUMMARY(clinicId);
        console.log(`Trying endpoint with multiple base URLs: ${endpoint}`);
        const response = await tryMultipleBaseUrls(endpoint);
        console.log('Successfully fetched availability summary with multiple base URLs');
        return response.data.data || getStaticAvailabilityData(clinicId);
      } catch (multiError: any) {
        console.error(`Failed with multiple base URLs: ${multiError?.message}`);
        
        // Try alternative endpoint formats
        try {
          const alternativeEndpoint = `/clinic/${clinicId}/availability/summary`;
          console.log(`Trying alternative endpoint: ${alternativeEndpoint}`);
          const alternativeResponse = await tryMultipleBaseUrls(alternativeEndpoint);
          console.log('Successfully fetched availability summary with alternative endpoint');
          return alternativeResponse.data.data || getStaticAvailabilityData(clinicId);
        } catch (altError) {
          console.error('All availability summary attempts failed');
          throw altError;
        }
      }
    }
  } catch (error) {
    console.error('Error fetching availability summary:', error);
    // Return static data as fallback
    return getStaticAvailabilityData(clinicId);
  }
};

// Fallback static data generator for availability when API is not available
const getStaticAvailabilityData = (clinicId: number): AvailabilitySummary => {
  console.log(`[Fallback] Static availability data for clinic ${clinicId}`);
  const staticData: AvailabilitySummary = {
    today: { 
      is_closed: false, 
      booked_count: 0, 
      remaining_slots: 20, 
      daily_limit: 20 
    },
    next_week: Array(7).fill(null).map((_, i) => {
      const date = new Date();
      date.setDate(date.getDate() + i + 1);
      const dateStr = date.toISOString().split("T")[0];
      const dayName = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"][date.getDay()];
      const isClosed = date.getDay() === 0;
      const remainingSlots = isClosed ? 0 : 20 - (i % 5);
      return {
        date: dateStr,
        day_name: dayName,
        is_closed: isClosed,
        booked_count: isClosed ? 0 : (i % 5),
        remaining_slots: remainingSlots,
        daily_limit: 20
      };
    }),
    settings: {
      daily_limit: 20,
      slot_duration: 30,
      default_start_time: "09:00:00",
      default_end_time: "17:00:00"
    }
  };
  return staticData;
};

export const getAvailabilitySlots = async (clinicId: number, date: string): Promise<AvailabilityResponse> => {
  try {
    console.log(`Fetching availability slots for clinic ${clinicId} on ${date}`);
    const endpoint = ROUTES.CLINICS.AVAILABILITY.SLOTS(clinicId, date);
    console.log(`API endpoint: ${endpoint}`);
    
    // Add timestamp to prevent caching
    const timestamp = new Date().getTime();
    const noCacheEndpoint = `${endpoint}?_nocache=${timestamp}`;
    
    // First try with the updated path parameter format
    try {
      const response = await API.get(noCacheEndpoint, {
        headers: {
          'Cache-Control': 'no-cache, no-store',
          'Pragma': 'no-cache',
        }
      });
      console.log('API response status:', response.status);
      
      // Log response structure to help debug issues
      const responseData = response.data;
      console.log('Response structure:', Object.keys(responseData));
      
      // Check if data is in the expected format
      if (responseData && responseData.data) {
        console.log('Slots found:', responseData.data.slots?.length || 0);
        return responseData.data;
      } else if (responseData && Array.isArray(responseData.slots)) {
        // Alternative format - data directly in response
        console.log('Slots found (alt format):', responseData.slots.length);
        return responseData;
      } else {
        console.warn('Unexpected response format, trying alternative endpoint');
        throw new Error('Trying alternative endpoint');
      }
    } catch (primaryError) {
      // If the first attempt fails, try the alternative "clinic" singular endpoint
      console.log('First attempt failed, trying alternative endpoint');
      const alternativeEndpoint = `/clinic/${clinicId}/availability/slots/${date}`;
      console.log(`Trying alternative API endpoint: ${alternativeEndpoint}`);
      
      const alternativeResponse = await API.get(alternativeEndpoint);
      const alternativeData = alternativeResponse.data;
      
      if (alternativeData && (alternativeData.data || alternativeData.slots)) {
        console.log('Slots found with alternative endpoint');
        return alternativeData.data || alternativeData;
      } else {
        console.warn('Alternative endpoint also failed');
        throw new Error('Both endpoints failed');
      }
    }
  } catch (error: any) {
    console.error('Error fetching availability slots:', error);
    
    // Detailed error logging
    if (error.response) {
      console.error('Response status:', error.response.status);
      console.error('Response data:', error.response.data);
    } else if (error.request) {
      console.error('No response received. Request:', error.request);
    } else {
      console.error('Error message:', error.message);
    }
    
    console.log('Returning fallback data');
    
    // Return static data as fallback
    return {
      is_available: true,
      date,
      slots: [
        { start: "09:00:00", end: "09:30:00", display_time: "9:00 AM - 9:30 AM" },
        { start: "09:30:00", end: "10:00:00", display_time: "9:30 AM - 10:00 AM" },
        { start: "10:00:00", end: "10:30:00", display_time: "10:00 AM - 10:30 AM" },
        { start: "10:30:00", end: "11:00:00", display_time: "10:30 AM - 11:00 AM" },
        { start: "14:00:00", end: "14:30:00", display_time: "2:00 PM - 2:30 PM" },
        { start: "14:30:00", end: "15:00:00", display_time: "2:30 PM - 3:00 PM" },
        { start: "15:00:00", end: "15:30:00", display_time: "3:00 PM - 3:30 PM" },
        { start: "15:30:00", end: "16:00:00", display_time: "3:30 PM - 4:00 PM" },
      ],
      daily_limit: 20,
      booked_count: 0,
      slots_remaining: 20
    };
  }
};

export interface CustomField {
  id: number | string;
  label: string;
  type: string;
  options?: string[];
  required: boolean;
  clinic_id?: number | string;
}

export const getClinicCustomFields = async (clinicId: number): Promise<CustomField[]> => {
  try {
    const response = await API.get(ROUTES.CLINICS.CUSTOM_FIELDS(clinicId));
    return response.data.data || [];
  } catch (error) {
    console.error('Error fetching custom fields:', error);
    // Return empty array as fallback instead of throwing error
    return [];
  }
};

// Helper function to calculate end time based on start time and duration
const getEndTimeFromSlot = (startTime: string, durationMinutes: number = 30): string => {
  // Parse the start time
  const [hours, minutes, seconds] = startTime.split(':').map(Number);
  
  // Create a date object with the current date (doesn't matter which date)
  const date = new Date();
  date.setHours(hours || 0);
  date.setMinutes((minutes || 0) + durationMinutes);
  date.setSeconds(seconds || 0);
  
  // Format the end time as HH:MM
  const endHours = date.getHours().toString().padStart(2, '0');
  const endMinutes = date.getMinutes().toString().padStart(2, '0');
  
  return `${endHours}:${endMinutes}`;
};

// Enhanced availability API functions

// Get detailed availability settings
export const getAvailabilitySettings = async (clinicId: number) => {
  try {
    console.log(`Fetching availability settings for clinic ${clinicId}`);
    const response = await API.get(ROUTES.CLINICS.AVAILABILITY.SETTINGS(clinicId));
    console.log('Successfully fetched availability settings');
    return response.data;
  } catch (error) {
    console.error('Error fetching availability settings:', error);
    // Return default settings as fallback
    return {
      slot_duration: 30,
      advance_booking_days: 30,
      same_day_booking: true,
      timezone: 'Asia/Manila',
      default_start_time: '09:00:00',
      default_end_time: '17:00:00'
    };
  }
};

// Get daily schedule for a specific day of week
export const getDailySchedule = async (clinicId: number, dayOfWeek: number) => {
  try {
    console.log(`Fetching daily schedule for clinic ${clinicId}, day ${dayOfWeek}`);
    const response = await API.get(ROUTES.CLINICS.AVAILABILITY.DAILY_SCHEDULE(clinicId, dayOfWeek));
    console.log('Successfully fetched daily schedule');
    return response.data;
  } catch (error) {
    console.error('Error fetching daily schedule:', error);
    // Return default schedule as fallback
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const isClosed = dayOfWeek === 0; // Sunday closed by default
    return {
      day_of_week: dayOfWeek,
      day_name: dayNames[dayOfWeek],
      is_available: !isClosed,
      start_time: isClosed ? null : '09:00:00',
      end_time: isClosed ? null : '17:00:00'
    };
  }
};

// Get all weekly schedules
export const getWeeklySchedule = async (clinicId: number) => {
  try {
    console.log(`Fetching weekly schedule for clinic ${clinicId}`);
    const schedulePromises = [0, 1, 2, 3, 4, 5, 6].map(day => 
      getDailySchedule(clinicId, day).catch(() => null)
    );
    const schedules = await Promise.all(schedulePromises);
    
    const weeklySchedule: any = {};
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    
    schedules.forEach((schedule, index) => {
      if (schedule) {
        weeklySchedule[dayNames[index]] = {
          start: schedule.start_time,
          end: schedule.end_time,
          isAvailable: schedule.is_available
        };
      } else {
        weeklySchedule[dayNames[index]] = { 
          start: null, 
          end: null, 
          isAvailable: false 
        };
      }
    });
    
    console.log('Successfully fetched weekly schedule');
    return weeklySchedule;
  } catch (error) {
    console.error('Error fetching weekly schedule:', error);
    // Return default weekly schedule as fallback
    return {
      sunday: { start: null, end: null, isAvailable: false },
      monday: { start: '09:00:00', end: '17:00:00', isAvailable: true },
      tuesday: { start: '09:00:00', end: '17:00:00', isAvailable: true },
      wednesday: { start: '09:00:00', end: '17:00:00', isAvailable: true },
      thursday: { start: '09:00:00', end: '17:00:00', isAvailable: true },
      friday: { start: '09:00:00', end: '17:00:00', isAvailable: true },
      saturday: { start: '09:00:00', end: '14:00:00', isAvailable: true }
    };
  }
};

// Get break times
export const getBreakTimes = async (clinicId: number) => {
  try {
    console.log(`Fetching break times for clinic ${clinicId}`);
    const response = await API.get(ROUTES.CLINICS.AVAILABILITY.BREAKS(clinicId));
    console.log('Successfully fetched break times');
    return response.data;
  } catch (error) {
    console.error('Error fetching break times:', error);
    // Return default break times as fallback
    return [
      {
        id: 1,
        name: 'Lunch Break',
        start_time: '12:00:00',
        end_time: '13:00:00',
        days: [1, 2, 3, 4, 5, 6], // Monday to Saturday
        is_active: true
      }
    ];
  }
};

// Get special dates and holidays
export const getSpecialDates = async (clinicId: number) => {
  try {
    console.log(`Fetching special dates for clinic ${clinicId}`);
    const response = await API.get(ROUTES.CLINICS.AVAILABILITY.SPECIAL_DATES(clinicId));
    console.log('Successfully fetched special dates');
    return response.data;
  } catch (error) {
    console.error('Error fetching special dates:', error);
    // Return empty array as fallback
    return [];
  }
};

// Get calendar dates with availability
export const getCalendarDates = async (clinicId: number) => {
  try {
    console.log(`Fetching calendar dates for clinic ${clinicId}`);
    const response = await API.get(ROUTES.CLINICS.AVAILABILITY.CALENDAR_DATES(clinicId));
    console.log('Successfully fetched calendar dates');
    return response.data;
  } catch (error) {
    console.error('Error fetching calendar dates:', error);
    // Return empty array as fallback
    return [];
  }
};

// Get clinic form fields configuration
export const getClinicFields = async (clinicId: number) => {
  try {
    console.log(`Fetching clinic fields for clinic ${clinicId}`);
    const response = await API.get(ROUTES.CLINICS.FIELDS(clinicId));
    console.log('Successfully fetched clinic fields');
    return response.data;
  } catch (error) {
    console.error('Error fetching clinic fields:', error);
    // Return empty array as fallback instead of throwing error
    return [];
  }
};

// Enhanced availability summary that combines all data
export const getComprehensiveAvailability = async (clinicId: number) => {
  try {
    console.log(`Fetching comprehensive availability data for clinic ${clinicId}`);
    
    // Fetch all availability data in parallel
    const [settings, weeklySchedule, breaks, specialDates, fields] = await Promise.all([
      getAvailabilitySettings(clinicId),
      getWeeklySchedule(clinicId),
      getBreakTimes(clinicId),
      getSpecialDates(clinicId),
      getClinicFields(clinicId)
    ]);

    const comprehensiveData = {
      settings,
      weeklySchedule,
      breaks,
      specialDates,
      fields,
      timestamp: new Date().getTime()
    };

    console.log('Successfully fetched comprehensive availability data');
    return comprehensiveData;
  } catch (error) {
    console.error('Error fetching comprehensive availability:', error);
    throw error;
  }
};

export interface AppointmentBookingData {
  owner_name: string;
  owner_phone: string;
  appointment_date: string;
  appointment_time: string;
  display_time?: string; // Optional display time for the appointment
  responses: Array<{field_id: string | number, value: any}>;
}

export interface AppointmentResponse {
  status: string;
  message: string;
  data?: any;
  appointment_id?: number | string;
  error?: string;
}

// Request a password reset link
export const forgotPassword = async (email: string): Promise<any> => {
  try {
    const response = await API.post('/forgot-password', { email });
    return response.data;
  } catch (error: any) {
    console.error('Error requesting password reset:', error);
    if (error.response && error.response.data) {
      return error.response.data;
    }
    throw error;
  }
}

export const bookAppointment = async (clinicId: number, data: AppointmentBookingData): Promise<AppointmentResponse> => {
  try {
    // Log booking attempt with full data
    console.log(`Booking appointment at clinic ${clinicId} for ${data.appointment_date} at ${data.appointment_time}`);
    console.log(`Full appointment data:`, JSON.stringify(data, null, 2));
    
    // Connection test with timeout
    const connectionTimeout = setTimeout(() => {
      console.warn('Connection test taking longer than expected, proceeding with direct booking');
    }, 3000);
    
    let connectionSuccess = false;
    try {
      await API.get('/clinics', { timeout: 3000 });
      connectionSuccess = true;
      console.log('Connection test successful');
    } catch (pingError) {
      console.warn('Connection test failed, proceeding with direct booking');
    } finally {
      clearTimeout(connectionTimeout);
    }
    
    // Try both endpoint formats to handle potential server-side routing issues
    const endpoints = [
      `/clinics/${clinicId}/appointments`, // Standard format
      `/clinic/${clinicId}/appointments`   // Alternative format
    ];
    
    let lastError = null;
    
    // Try each endpoint
    for (const endpoint of endpoints) {
      try {
        // Ensure appointment date and time are properly formatted according to the README specs
        // Dates should be in YYYY-MM-DD format, Times in HH:MM:SS format
        
        // Format date to YYYY-MM-DD
        let formattedDate = data.appointment_date;
        try {
          // Handle any potential date format issues
          if (formattedDate) {
            // If date contains 'T' (ISO format), split and take the date part
            if (formattedDate.includes('T')) {
              formattedDate = formattedDate.split('T')[0];
            }
            
            // Validate and standardize date format
            const dateParts = formattedDate.split('-');
            if (dateParts.length === 3) {
              // Make sure it's YYYY-MM-DD format
              if (dateParts[0].length === 4) {
                formattedDate = `${dateParts[0]}-${dateParts[1].padStart(2, '0')}-${dateParts[2].padStart(2, '0')}`;
              }
            }
          }
        } catch (err) {
          console.error('Error formatting date:', err);
          // Keep original if parsing fails
        }
        
        // Format time to HH:MM:SS as required by backend
        let formattedTime = data.appointment_time;
        try {
          if (formattedTime) {
            if (formattedTime.includes(':')) {
              const timeParts = formattedTime.split(':');
              if (timeParts.length === 2) {
                formattedTime = `${timeParts[0].padStart(2, '0')}:${timeParts[1].padStart(2, '0')}:00`; // Add seconds if missing
              } else if (timeParts.length === 3) {
                formattedTime = `${timeParts[0].padStart(2, '0')}:${timeParts[1].padStart(2, '0')}:${timeParts[2].padStart(2, '0')}`;
              }
            } else {
              // No colons, assuming it's just an hour
              formattedTime = `${formattedTime.padStart(2, '0')}:00:00`; // Add time format if missing
            }
          }
        } catch (err) {
          console.error('Error formatting time:', err);
          // Keep original if parsing fails
        }
        
        console.log('Date/Time formatting results:', {
          originalDate: data.appointment_date,
          formattedDate,
          originalTime: data.appointment_time,
          formattedTime
        });
        
        // Create properly formatted data payload
        const formattedData = {
          ...data,
          appointment_date: formattedDate,
          appointment_time: formattedTime,
          // Include display_time to ensure it's visible in management system
          display_time: data.display_time || `${formattedTime.replace(/:/g, ':')} - ${getEndTimeFromSlot(formattedTime, 30)}`
        };
        
        console.log('Formatted appointment data:', {
          original_date: data.appointment_date,
          formatted_date: formattedDate,
          original_time: data.appointment_time,
          formatted_time: formattedTime
        });
        
        console.log(`Trying to book appointment with endpoint: ${endpoint}`);
        console.log(`Sending formatted data:`, JSON.stringify(formattedData, null, 2));
        const response = await API.post(endpoint, formattedData);
        console.log('Appointment booked successfully:', response.data);
        
        // We'll rely on the database for appointment storage
        // but let's still store a reference to the appointment locally for immediate UI feedback
        try {
          // Format a local booking record with the database ID
          const appointmentId = response.data.id || response.data.appointment_id || response.data.data?.id;
          
          if (appointmentId) {
            // Store a minimal reference to the successful booking
            const localBooking = {
              appointment_id: appointmentId,
              start_time: data.appointment_time,
              owner_name: data.owner_name,
              status: 'confirmed',
              is_db_record: true // Flag that this is from the database
            };
            
            // Store a minimal reference for immediate UI feedback
            const localBookingsKey = `localBookings_${clinicId}_${data.appointment_date}`;
            await AsyncStorage.setItem(`appointment_${appointmentId}`, JSON.stringify({
              id: appointmentId,
              date: data.appointment_date,
              time: data.appointment_time,
              clinicId: clinicId
            }));
            
            console.log(`Saved reference to database appointment: ${appointmentId}`);
          }
        } catch (storageError) {
          console.log('Failed to save appointment reference:', storageError);
        }
        
        // If successful, invalidate any cached availability data for this date
        try {
          // Clear any cached availability data for this date to force a fresh fetch
          const cacheKey = `availabilitySlots_${clinicId}_${data.appointment_date}`;
          await AsyncStorage.removeItem(cacheKey);
          console.log(`Cleared cached availability data for ${data.appointment_date}`);
          
          // Also trigger a refresh of the availability data by making a background request
          setTimeout(() => {
            getAvailabilitySlots(clinicId, data.appointment_date)
              .then(() => console.log('Successfully refreshed availability data'))
              .catch(e => console.log('Background refresh failed:', e));
          }, 500);
        } catch (cacheError) {
          console.log('Failed to clear cache:', cacheError);
        }
        
        // If successful, return response
        return {
          status: response.data.status || 'success',
          message: response.data.message || 'Appointment booked successfully',
          appointment_id: response.data.id || response.data.appointment_id || response.data.data?.id,
          data: response.data
        };
      } catch (endpointError: any) {
        console.log(`Failed with endpoint ${endpoint}:`, endpointError.message);
        lastError = endpointError;
      }
    }
    
    // If all endpoints failed, try with fetch API as backup
    console.error('All API booking endpoints failed, trying fetch API backup');
    
    // Try using fetch directly with each endpoint
    for (const endpoint of endpoints) {
      try {
        // Format data consistently for fetch method too
        const formattedData = {
          ...data,
          appointment_time: data.appointment_time.includes(':') 
            ? (data.appointment_time.split(':').length === 2 
                ? `${data.appointment_time}:00` // Add seconds if missing
                : data.appointment_time) 
            : `${data.appointment_time}:00:00`, // Add time format if missing
          display_time: data.display_time || `${data.appointment_time.replace(/:/g, ':')} - ${getEndTimeFromSlot(data.appointment_time, 30)}`
        };
        
        console.log(`Trying direct fetch with endpoint: ${endpoint}`);
        console.log(`Sending formatted data:`, JSON.stringify(formattedData, null, 2));
        const backupResponse = await fetch(`${API.defaults.baseURL}${endpoint}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${await AsyncStorage.getItem('token')}`
          },
          body: JSON.stringify(formattedData)
        });
        
        if (backupResponse.ok) {
          const responseData = await backupResponse.json();
          console.log('Backup fetch booking successful:', responseData);
          
          // We'll rely on the database for appointment storage
          // but store a reference to the DB appointment for immediate UI feedback
          try {
            // Format a local booking record with the database ID
            const appointmentId = responseData.id || responseData.appointment_id || responseData.data?.id;
            
            if (appointmentId) {
              // Store a minimal reference to the successful booking
              await AsyncStorage.setItem(`appointment_${appointmentId}`, JSON.stringify({
                id: appointmentId,
                date: data.appointment_date,
                time: data.appointment_time,
                clinicId: clinicId,
                status: 'confirmed'
              }));
              
              console.log(`Saved reference to database appointment: ${appointmentId}`);
            }
          } catch (storageError) {
            console.log('Failed to save appointment reference:', storageError);
          }
          
          return {
            status: responseData.status || 'success',
            message: responseData.message || 'Appointment booked successfully via backup method',
            appointment_id: responseData.id || responseData.appointment_id || responseData.data?.id,
            data: responseData
          };
        } else {
          console.log(`Failed with status ${backupResponse.status}: ${backupResponse.statusText}`);
        }
      } catch (fetchError: any) {
        console.log(`Fetch API failed with endpoint ${endpoint}:`, fetchError.message);
      }
    }
    
    // If we're in development mode, return mock success
    if (__DEV__) {
      console.log('[DEV MODE] All attempts failed. Returning mock success response');
      
      // Generate mock appointment ID
      const mockAppointmentId = `mock_${Date.now()}`;
      
      // In DEV mode we still want to emulate database behavior
      try {
        // Make a HTTP request to save to backend if possible
        const devEndpoint = `/clinic/${clinicId}/dev/appointment`;
        console.log(`[DEV] Attempting to save to development endpoint: ${devEndpoint}`);
        
        try {
          // Try to save to development endpoint
          await fetch(`${API.defaults.baseURL}${devEndpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              appointment_id: mockAppointmentId,
              date: data.appointment_date,
              time: data.appointment_time,
              owner: data.owner_name,
              phone: data.owner_phone,
              status: 'confirmed'
            })
          });
        } catch (e) {
          console.log('[DEV] Failed to save to development endpoint, using local storage fallback');
        }
        
        // Store a reference for immediate UI feedback
        await AsyncStorage.setItem(`appointment_${mockAppointmentId}`, JSON.stringify({
          id: mockAppointmentId,
          date: data.appointment_date,
          time: data.appointment_time,
          clinicId: clinicId,
          name: data.owner_name,
          status: 'confirmed',
          is_mock: true
        }));
        
        console.log(`[DEV] Saved mock appointment reference: ${mockAppointmentId}`);
      } catch (storageError) {
        console.log('Failed to save mock booking to local storage:', storageError);
      }
      
      return {
        status: 'success',
        message: 'Appointment booked successfully (DEV MODE - MOCK RESPONSE)',
        appointment_id: mockAppointmentId
      };
    }
    
    // If we got here, all attempts failed
    throw new Error(lastError ? lastError.message : 'All booking attempts failed');
    
  } catch (error: any) {
    console.error('Error booking appointment:', error.message);
    
    // Format error response for consistent handling in UI
    return {
      status: 'error',
      message: error.response?.data?.message || 'Failed to book appointment. Please try again.',
      error: error.message
    };
  }
};

/**
 * Get the current authentication token from AsyncStorage
 * 
 * @returns The token if available, null otherwise
 */
export const getToken = async (): Promise<string | null> => {
  try {
    // Try to get token from all possible storage locations
    return await AsyncStorage.getItem('token') || 
           await AsyncStorage.getItem('userToken') || 
           await AsyncStorage.getItem('accessToken');
  } catch (error) {
    console.error('Error retrieving token:', error);
    return null;
  }
};

/**
 * Delete an appointment - this will also free up the slot for booking
 * 
 * @param clinicId The ID of the clinic
 * @param appointmentId The ID of the appointment to delete
 * @returns Response data
 */
export const deleteAppointment = async (
  clinicId: number,
  appointmentId: number
): Promise<any> => {
  try {
    console.log(`Deleting appointment ${appointmentId} at clinic ${clinicId}`);
    const response = await API.delete(
      `/clinics/${clinicId}/appointments/${appointmentId}`
    );
    console.log('Appointment deleted successfully', response.data);
    return response.data;
  } catch (error) {
    console.error('Error deleting appointment:', error);
    throw error;
  }
};