/**
 * API Route definitions
 * This file centralizes all API endpoint paths used in the application
 */

// Base API routes
export const ROUTES = {
  // Authentication routes
  AUTH: {
    LOGIN: '/auth/login',
    REGISTER: '/auth/register',
    LOGOUT: '/auth/logout',
    REFRESH: '/auth/refresh',
    VERIFY: '/auth/verify',
    FORGOT_PASSWORD: '/auth/forgot-password',
    RESET_PASSWORD: '/auth/reset-password'
  },
  
  // Clinic routes
  CLINICS: {
    BASE: '/clinics',
    LIST: '/clinics',
    DETAILS: (id: number | string) => `/clinics/${id}`,
    AVAILABILITY: {
      SUMMARY: (id: number | string) => `/clinics/${id}/availability/summary`,
      SLOTS: (id: number | string, date: string) => `/clinics/${id}/availability/slots/${date}`,
      SETTINGS: (id: number | string) => `/clinics/${id}/availability/settings`,
      DAILY_SCHEDULE: (id: number | string, dayOfWeek: number) => `/clinics/${id}/availability/daily-schedule/${dayOfWeek}`,
      BREAKS: (id: number | string) => `/clinics/${id}/availability/breaks`,
      SPECIAL_DATES: (id: number | string) => `/clinics/${id}/availability/special-dates`,
      CALENDAR_DATES: (id: number | string) => `/clinics/${id}/availability/dates`
    },
    APPOINTMENTS: {
      CREATE: (id: number | string) => `/clinics/${id}/appointments`,
      LIST: (id: number | string) => `/clinics/${id}/appointments`,
      DETAILS: (clinicId: number | string, appointmentId: number | string) => 
        `/clinics/${clinicId}/appointments/${appointmentId}`,
      CANCEL: (clinicId: number | string, appointmentId: number | string) => 
        `/clinics/${clinicId}/appointments/${appointmentId}/cancel`
    },
    CUSTOM_FIELDS: (id: number | string) => `/clinics/${id}/custom-fields`,
    FIELDS: (id: number | string) => `/clinics/${id}/fields`
  },
  
  // User profile routes
  PROFILE: {
    GET: '/profile',
    UPDATE: '/profile',
    PETS: {
      LIST: '/profile/pets',
      CREATE: '/profile/pets',
      DETAILS: (id: number | string) => `/profile/pets/${id}`,
      UPDATE: (id: number | string) => `/profile/pets/${id}`,
      DELETE: (id: number | string) => `/profile/pets/${id}`
    },
    APPOINTMENTS: {
      LIST: '/profile/appointments',
      DETAILS: (id: number | string) => `/profile/appointments/${id}`,
      CANCEL: (id: number | string) => `/profile/appointments/${id}/cancel`
    }
  }
};
