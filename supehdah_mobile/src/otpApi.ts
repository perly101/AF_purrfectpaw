import { API } from './api';
import AsyncStorage from '@react-native-async-storage/async-storage';

/**
 * OTP Verification API Methods
 */
export const OtpApi = {
  /**
   * Verify OTP code
   * @param otp - The 6-digit OTP code to verify
   * @returns Promise with verification result
   */
  verifyOtp: async (otp: string) => {
    try {
      // Check if we have a token first
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
      
      console.log('🔐 OtpApi verifyOtp - Token check:', {
        hasToken: !!token,
        tokenLength: token?.length || 0,
        tokenPreview: token ? `${token.substring(0, 10)}...` : 'none'
      });
                   
      if (!token) {
        throw new Error('No authentication token available');
      }
      
      console.log('📤 Sending OTP verification request:', { otp });
      const response = await API.post('/verify-otp', { otp });
      console.log('📥 OTP verification response:', response.data);
      return response.data;
    } catch (error: any) {
      // Add more context to the error for better debugging
      if (error.response?.status === 429) {
        console.warn('Rate limited when verifying OTP');
        throw new Error('Please wait a moment before trying again');
      } else if (error.response?.status === 401) {
        console.error('Authentication failed when verifying OTP');
        throw new Error('Authentication required. Please log in again.');
      } else {
        console.error('Error verifying OTP:', error);
        throw error;
      }
    }
  },

  /**
   * Resend OTP verification code
   * @returns Promise with resend result
   */
  resendOtp: async () => {
    try {
      // Check if we have a token first
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
                   
      if (!token) {
        throw new Error('No authentication token available');
      }
      
      const response = await API.post('/resend-otp');
      return response.data;
    } catch (error: any) {
      // Add more context to the error for better debugging
      if (error.response?.status === 429) {
        console.warn('Rate limited when resending OTP - please wait before trying again');
        throw new Error('Please wait a moment before requesting another code');
      } else if (error.response?.status === 401) {
        console.error('Authentication failed when resending OTP');
        throw new Error('Authentication required. Please log in again.');
      } else {
        console.error('Error resending OTP:', error);
        throw error;
      }
    }
  },

  /**
   * Check email verification status of the current user
   * @returns Promise with boolean indicating verification status
   */
  checkEmailVerified: async () => {
    try {
      const response = await API.get('/user/email-verified');
      return response.data.verified;
    } catch (error) {
      console.error('Error checking email verification status:', error);
      return false;
    }
  },

  /**
   * Store a flag indicating that the user has initiated the OTP verification flow
   * This can be used to redirect users back to the OTP screen if they close the app
   * before completing verification
   */
  setVerificationPending: async () => {
    try {
      await AsyncStorage.setItem('otp_verification_pending', 'true');
    } catch (error) {
      console.error('Error setting verification pending flag:', error);
    }
  },

  /**
   * Check if the user has a pending OTP verification
   * @returns Promise with boolean indicating pending verification status
   */
  hasVerificationPending: async () => {
    try {
      const pending = await AsyncStorage.getItem('otp_verification_pending');
      return pending === 'true';
    } catch (error) {
      console.error('Error checking verification pending status:', error);
      return false;
    }
  },

  /**
   * Clear the verification pending flag after successful verification
   */
  clearVerificationPending: async () => {
    try {
      await AsyncStorage.removeItem('otp_verification_pending');
    } catch (error) {
      console.error('Error clearing verification pending flag:', error);
    }
  }
};