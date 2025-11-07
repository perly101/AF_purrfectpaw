import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  TextInput,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  SafeAreaView,
  ScrollView,
} from 'react-native';
import { MaterialCommunityIcons, Ionicons } from '@expo/vector-icons';
import { API } from '../src/api';
import { OtpApi } from '../src/otpApi';
import { useNavigation, CommonActions } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useAuth } from '../src/contexts/AuthContext';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Modern color palette - consistent with other screens
const COLORS = {
  primary: '#6C5CE7',
  primaryLight: '#A29BFE',
  secondary: '#FF9EB1',
  background: '#F8FAFC',
  surface: '#FFFFFF',
  surfaceElevated: '#FEFEFE',
  text: '#1A1D29',
  textSecondary: '#64748B',
  textMuted: '#94A3B8',
  border: '#E2E8F0',
  borderLight: '#F1F5F9',
  success: '#10B981',
  successBg: '#ECFDF5',
  warning: '#F59E0B',
  warningBg: '#FFFBEB',
  error: '#EF4444',
  errorBg: '#FEF2F2',
  info: '#3B82F6',
  infoBg: '#EFF6FF',
};

type OTPVerificationScreenProps = {
  route: {
    params: {
      email?: string;
    }
  }
};

export default function OTPVerificationScreen({ route }: OTPVerificationScreenProps): React.ReactElement {
  const [otp, setOtp] = useState<string>('');
  const [loading, setLoading] = useState<boolean>(false);
  const [countdown, setCountdown] = useState<number>(30);
  const [canResend, setCanResend] = useState<boolean>(false);
  const navigation = useNavigation();
  const { updateUser, signIn } = useAuth();
  
  // Create refs for the input fields
  const inputRefs = useRef<Array<TextInput | null>>([null, null, null, null, null, null]);
  const [otpValues, setOtpValues] = useState<string[]>(['', '', '', '', '', '']);
  
  useEffect(() => {
    // Start countdown timer for resend button
    const timer = setInterval(() => {
      setCountdown(prevCountdown => {
        if (prevCountdown <= 1) {
          clearInterval(timer);
          setCanResend(true);
          return 0;
        }
        return prevCountdown - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, []);

  // Handle OTP input changes with auto-focus to next field
  const handleOtpChange = (text: string, index: number) => {
    // Only accept numbers
    if (!/^[0-9]*$/.test(text)) return;

    const newOtpValues = [...otpValues];
    newOtpValues[index] = text.slice(0, 1); // Only take the first character
    setOtpValues(newOtpValues);

    // Combine values for the full OTP
    setOtp(newOtpValues.join(''));

    // Auto-focus to next field if value exists
    if (text && index < 5) {
      inputRefs.current[index + 1]?.focus();
    }
  };

  // Handle backspace - move to previous input
  const handleKeyPress = (e: any, index: number) => {
    if (e.nativeEvent.key === 'Backspace' && !otpValues[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
  };

  const verifyOtp = async () => {
    if (otp.length !== 6) {
      Alert.alert('Error', 'Please enter a complete 6-digit OTP code');
      return;
    }

    setLoading(true);
    try {
      console.log('🔐 Starting OTP verification process');
      console.log('📝 OTP to verify:', otp);
      console.log('📧 Route params email:', route?.params?.email);
      
      // Check current authentication status
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
      console.log('🎫 Current token status:', !!token);
      
      const response = await OtpApi.verifyOtp(otp);
      console.log('✅ OTP verification successful:', response);
      
      // Update user context with verified user data and re-authenticate
      if (response.user) {
        console.log('Re-authenticating user with verified user data');
        
        // Get the current token
        const currentToken = await AsyncStorage.getItem('token') || 
                           await AsyncStorage.getItem('userToken') || 
                           await AsyncStorage.getItem('accessToken');
        
        if (currentToken) {
          // Use signIn to properly authenticate with the verified user data
          // This will trigger the navigation re-render with authenticated routes
          await signIn(currentToken, response.user);
          console.log('Successfully re-authenticated with verified user data');
        } else {
          // Fallback: just update the user
          updateUser(response.user);
        }
      }
      
      // Clear verification pending flag
      await OtpApi.clearVerificationPending();
      
      Alert.alert(
        'Success',
        'Email verified successfully! Welcome to PurrfectPaw!',
        [
          { 
            text: 'Continue', 
            onPress: () => {
              // The AuthContext should now show authenticated routes automatically
              console.log('OTP verification complete, AuthContext should show PersonalTabs');
              
              // Small delay to allow AuthContext state to update, then try manual navigation as fallback
              setTimeout(() => {
                try {
                  navigation.dispatch(
                    CommonActions.reset({
                      index: 0,
                      routes: [{ name: 'PersonalTabs' }]
                    })
                  );
                } catch (navError) {
                  console.log('Manual navigation failed, relying on AuthContext:', navError);
                  // If manual navigation fails, the AuthContext should handle it automatically
                }
              }, 500);
            }
          }
        ]
      );
    } catch (error: any) {
      console.error('OTP Verification error:', error);
      console.error('OTP Verification error response:', error.response?.data);
      
      let errorMessage = 'Verification failed. Please try again.';
      let showResendOption = false;
      
      if (error.response && error.response.data) {
        if (error.response.data.errors && error.response.data.errors.otp) {
          errorMessage = error.response.data.errors.otp[0];
        } else if (error.response.data.message) {
          errorMessage = error.response.data.message;
          
          // Check if this is a "no verification code found" error
          if (errorMessage.includes('No verification code found')) {
            showResendOption = true;
            // Check if a new OTP was automatically sent
            if (error.response.data.otp_resent) {
              errorMessage = 'Your verification code has expired. A new code has been sent to your email.';
              // Reset countdown
              setCountdown(30);
              setCanResend(false);
              // Restart timer
              const timer = setInterval(() => {
                setCountdown(prevCountdown => {
                  if (prevCountdown <= 1) {
                    clearInterval(timer);
                    setCanResend(true);
                    return 0;
                  }
                  return prevCountdown - 1;
                });
              }, 1000);
            }
          }
        }
      }
      
      if (showResendOption && !error.response?.data?.otp_resent) {
        Alert.alert(
          'Verification Code Not Found', 
          'Your verification code may have expired or not been generated. Would you like to request a new code?',
          [
            { text: 'Cancel', style: 'cancel' },
            { 
              text: 'Resend Code', 
              onPress: () => {
                if (canResend) {
                  resendOtp();
                }
              }
            }
          ]
        );
      } else {
        Alert.alert('Error', errorMessage);
      }
    } finally {
      setLoading(false);
    }
  };

  const resendOtp = async () => {
    if (!canResend) return;
    
    setLoading(true);
    try {
      console.log('Resending OTP...');
      const response = await OtpApi.resendOtp();
      console.log('Resend OTP response:', response);
      
      // Update OTP verification pending status
      await OtpApi.setVerificationPending();
      
      // Reset countdown timer
      setCountdown(30);
      setCanResend(false);
      
      // Reset timer
      const timer = setInterval(() => {
        setCountdown(prevCountdown => {
          if (prevCountdown <= 1) {
            clearInterval(timer);
            setCanResend(true);
            return 0;
          }
          return prevCountdown - 1;
        });
      }, 1000);
      
      Alert.alert('Success', 'OTP code has been resent to your email');
    } catch (error: any) {
      console.error('Resend OTP error:', error);
      console.error('Resend OTP error response:', error.response?.data);
      
      let errorMessage = 'Failed to resend OTP. Please try again.';
      if (error.response && error.response.data && error.response.data.message) {
        errorMessage = error.response.data.message;
      }
      Alert.alert('Error', errorMessage);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.keyboardContainer}
      >
        <ScrollView 
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          {/* Header Section */}
          <View style={styles.headerSection}>
            <View style={styles.iconContainer}>
              <MaterialCommunityIcons name="email-check" size={48} color={COLORS.primary} />
            </View>
            <Text style={styles.title}>Email Verification</Text>
            <Text style={styles.subtitle}>
              We've sent a 6-digit verification code to
            </Text>
            <Text style={styles.emailText}>
              {route.params?.email || 'your email address'}
            </Text>
            <Text style={styles.instructionText}>
              Please enter the code below to verify your account
            </Text>
          </View>

          {/* OTP Input Section */}
          <View style={styles.otpSection}>
            <Text style={styles.otpLabel}>Verification Code</Text>
            <View style={styles.otpContainer}>
              {Array(6).fill(0).map((_, index) => (
                <View key={index} style={styles.otpInputWrapper}>
                  <TextInput
                    ref={(ref) => { inputRefs.current[index] = ref }}
                    style={[
                      styles.otpInput,
                      otpValues[index] ? styles.otpInputFilled : {},
                      loading ? styles.otpInputDisabled : {}
                    ]}
                    maxLength={1}
                    keyboardType="number-pad"
                    value={otpValues[index]}
                    onChangeText={(text) => handleOtpChange(text, index)}
                    onKeyPress={(e) => handleKeyPress(e, index)}
                    autoFocus={index === 0}
                    editable={!loading}
                    textContentType="oneTimeCode"
                  />
                  {otpValues[index] && (
                    <View style={styles.inputCheckmark}>
                      <MaterialCommunityIcons name="check" size={16} color={COLORS.success} />
                    </View>
                  )}
                </View>
              ))}
            </View>
          </View>

          {/* Action Buttons */}
          <View style={styles.actionSection}>
            <TouchableOpacity 
              style={[
                styles.verifyButton,
                otp.length === 6 ? styles.verifyButtonActive : styles.verifyButtonDisabled
              ]} 
              onPress={verifyOtp}
              disabled={loading || otp.length !== 6}
              activeOpacity={0.8}
            >
              {loading ? (
                <View style={styles.buttonLoadingContent}>
                  <ActivityIndicator size="small" color={COLORS.surface} />
                  <Text style={styles.verifyButtonText}>Verifying...</Text>
                </View>
              ) : (
                <View style={styles.buttonContent}>
                  <MaterialCommunityIcons name="check-circle" size={20} color={COLORS.surface} />
                  <Text style={styles.verifyButtonText}>Verify Email</Text>
                </View>
              )}
            </TouchableOpacity>

            {/* Resend Section */}
            <View style={styles.resendSection}>
              <Text style={styles.resendText}>Didn't receive the code?</Text>
              {canResend ? (
                <TouchableOpacity 
                  style={styles.resendButton} 
                  onPress={resendOtp} 
                  disabled={loading}
                  activeOpacity={0.7}
                >
                  <MaterialCommunityIcons name="refresh" size={18} color={COLORS.primary} />
                  <Text style={styles.resendButtonText}>Resend Code</Text>
                </TouchableOpacity>
              ) : (
                <View style={styles.countdownContainer}>
                  <MaterialCommunityIcons name="timer-outline" size={18} color={COLORS.textMuted} />
                  <Text style={styles.countdownText}>Resend in {countdown}s</Text>
                </View>
              )}
            </View>
          </View>

          {/* Help Section */}
          <View style={styles.helpSection}>
            <View style={styles.helpItem}>
              <MaterialCommunityIcons name="information-outline" size={16} color={COLORS.info} />
              <Text style={styles.helpText}>Check your spam folder if you don't see the email</Text>
            </View>
            <View style={styles.helpItem}>
              <MaterialCommunityIcons name="clock-outline" size={16} color={COLORS.warning} />
              <Text style={styles.helpText}>The verification code expires in 10 minutes</Text>
            </View>
          </View>

        </ScrollView>
      </KeyboardAvoidingView>
      
      {/* Loading Overlay */}
      {loading && (
        <View style={styles.loadingOverlay}>
          <View style={styles.loadingCard}>
            <ActivityIndicator size="large" color={COLORS.primary} />
            <Text style={styles.loadingText}>Verifying your code...</Text>
          </View>
        </View>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  keyboardContainer: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 24,
    paddingVertical: 32,
  },

  // Header Section
  headerSection: {
    alignItems: 'center',
    marginBottom: 40,
    paddingTop: 20,
  },
  iconContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: COLORS.primaryLight + '20',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
    borderWidth: 2,
    borderColor: COLORS.primaryLight + '40',
  },
  title: {
    fontSize: 28,
    fontWeight: '800',
    color: COLORS.text,
    marginBottom: 12,
    textAlign: 'center',
    letterSpacing: 0.5,
  },
  subtitle: {
    fontSize: 16,
    color: COLORS.textSecondary,
    textAlign: 'center',
    fontWeight: '500',
    marginBottom: 8,
    lineHeight: 24,
  },
  emailText: {
    fontSize: 17,
    color: COLORS.primary,
    textAlign: 'center',
    fontWeight: '700',
    marginBottom: 16,
    letterSpacing: 0.3,
  },
  instructionText: {
    fontSize: 15,
    color: COLORS.textMuted,
    textAlign: 'center',
    fontWeight: '500',
    lineHeight: 22,
  },

  // OTP Input Section
  otpSection: {
    marginBottom: 40,
  },
  otpLabel: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 16,
    textAlign: 'center',
    letterSpacing: 0.3,
  },
  otpContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 8,
  },
  otpInputWrapper: {
    position: 'relative',
  },
  otpInput: {
    width: 50,
    height: 60,
    borderWidth: 2,
    borderColor: COLORS.border,
    borderRadius: 16,
    textAlign: 'center',
    fontSize: 24,
    fontWeight: '700',
    backgroundColor: COLORS.surface,
    color: COLORS.text,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  otpInputFilled: {
    borderColor: COLORS.primary,
    backgroundColor: COLORS.primaryLight + '10',
  },
  otpInputDisabled: {
    backgroundColor: COLORS.borderLight,
    borderColor: COLORS.borderLight,
    opacity: 0.7,
  },
  inputCheckmark: {
    position: 'absolute',
    top: -8,
    right: -8,
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: COLORS.successBg,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.success,
  },

  // Action Section
  actionSection: {
    marginBottom: 32,
  },
  verifyButton: {
    borderRadius: 16,
    paddingVertical: 16,
    paddingHorizontal: 24,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6,
  },
  verifyButtonActive: {
    backgroundColor: COLORS.primary,
  },
  verifyButtonDisabled: {
    backgroundColor: COLORS.borderLight,
    shadowOpacity: 0,
    elevation: 0,
  },
  buttonContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  buttonLoadingContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  verifyButtonText: {
    color: COLORS.surface,
    fontSize: 16,
    fontWeight: '700',
    letterSpacing: 0.3,
  },

  // Resend Section
  resendSection: {
    alignItems: 'center',
  },
  resendText: {
    fontSize: 15,
    color: COLORS.textSecondary,
    marginBottom: 12,
    fontWeight: '500',
  },
  resendButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.primaryLight + '15',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 12,
    gap: 8,
  },
  resendButtonText: {
    color: COLORS.primary,
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  countdownContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.borderLight,
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 12,
    gap: 8,
  },
  countdownText: {
    color: COLORS.textMuted,
    fontSize: 15,
    fontWeight: '600',
  },

  // Help Section
  helpSection: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    padding: 20,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  helpItem: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 12,
    gap: 12,
  },
  helpText: {
    flex: 1,
    fontSize: 14,
    color: COLORS.textSecondary,
    lineHeight: 20,
    fontWeight: '500',
  },

  // Loading Overlay
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.6)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 20,
    padding: 32,
    alignItems: 'center',
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 16,
    elevation: 10,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: COLORS.text,
    fontWeight: '600',
    textAlign: 'center',
  },

  // Legacy styles for backward compatibility
  formContainer: {
    backgroundColor: COLORS.surface,
    borderRadius: 20,
    padding: 30,
    alignItems: 'center',
  },
  logoContainer: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: COLORS.secondary,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
  },
  logo: {
    width: 85,
    height: 85,
    borderRadius: 30,
  },
});