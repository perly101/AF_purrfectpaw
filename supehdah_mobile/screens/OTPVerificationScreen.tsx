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
  ImageBackground,
  Image,
} from 'react-native';
import { API } from '../src/api';
import { OtpApi } from '../src/otpApi';
import { useNavigation, CommonActions } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { useAuth } from '../src/contexts/AuthContext';
import AsyncStorage from '@react-native-async-storage/async-storage';

const PINK = '#FFC1CC';
const DARK = '#333';

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
      console.log('Verifying OTP:', otp);
      const response = await OtpApi.verifyOtp(otp);
      console.log('Verify OTP response:', response);
      
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
      if (error.response && error.response.data) {
        if (error.response.data.errors && error.response.data.errors.otp) {
          errorMessage = error.response.data.errors.otp[0];
        } else if (error.response.data.message) {
          errorMessage = error.response.data.message;
        }
      }
      Alert.alert('Error', errorMessage);
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
    <ImageBackground source={require('../assets/pic4.jpg')} style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        style={styles.container}
      >
        <View style={styles.formContainer}>
          <View style={styles.logoContainer}>
            <Image source={require('../assets/purrfectpaw_logo.png')} style={styles.logo} />
          </View>
          
          <Text style={styles.title}>Verify Your Email</Text>
          <Text style={styles.subtitle}>
            We've sent a 6-digit verification code to{'\n'}
            {route.params?.email || 'your email address'}
          </Text>
          
          <View style={styles.otpContainer}>
            {Array(6).fill(0).map((_, index) => (
              <TextInput
                key={index}
                ref={(ref) => { inputRefs.current[index] = ref }}
                style={styles.otpInput}
                maxLength={1}
                keyboardType="number-pad"
                value={otpValues[index]}
                onChangeText={(text) => handleOtpChange(text, index)}
                onKeyPress={(e) => handleKeyPress(e, index)}
                autoFocus={index === 0}
              />
            ))}
          </View>
          
          <TouchableOpacity 
            style={styles.verifyButton} 
            onPress={verifyOtp}
            disabled={loading || otp.length !== 6}
          >
            {loading ? (
              <ActivityIndicator color="#000" />
            ) : (
              <Text style={styles.verifyButtonText}>Verify</Text>
            )}
          </TouchableOpacity>
          
          <View style={styles.resendContainer}>
            <Text style={styles.resendText}>Didn't receive the code? </Text>
            {canResend ? (
              <TouchableOpacity onPress={resendOtp} disabled={loading}>
                <Text style={styles.resendButton}>Resend Code</Text>
              </TouchableOpacity>
            ) : (
              <Text style={styles.countdownText}>Resend in {countdown}s</Text>
            )}
          </View>
        </View>
      </KeyboardAvoidingView>
      
      {loading && (
        <View style={styles.loadingOverlay}>
          <ActivityIndicator size="large" color={PINK} />
        </View>
      )}
    </ImageBackground>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    padding: 20,
  },
  formContainer: {
    backgroundColor: 'rgba(255,255,255,0.8)',
    borderRadius: 20,
    padding: 30,
    alignItems: 'center',
    elevation: 5,
  },
  logoContainer: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: PINK,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 20,
  },
  logo: {
    width: 85,
    height: 85,
    borderRadius: 30,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: DARK,
    marginBottom: 10,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: 14,
    color: '#555',
    marginBottom: 30,
    textAlign: 'center',
  },
  otpContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    width: '100%',
    marginBottom: 30,
  },
  otpInput: {
    width: 45,
    height: 55,
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 8,
    textAlign: 'center',
    fontSize: 24,
    backgroundColor: '#fff',
  },
  verifyButton: {
    backgroundColor: PINK,
    width: '100%',
    borderRadius: 30,
    paddingVertical: 14,
    alignItems: 'center',
    marginBottom: 20,
  },
  verifyButtonText: {
    color: '#000',
    fontSize: 16,
    fontWeight: 'bold',
  },
  resendContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  resendText: {
    color: DARK,
    fontSize: 14,
  },
  resendButton: {
    color: PINK,
    fontWeight: 'bold',
    fontSize: 14,
  },
  countdownText: {
    color: '#666',
    fontSize: 14,
  },
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },
});