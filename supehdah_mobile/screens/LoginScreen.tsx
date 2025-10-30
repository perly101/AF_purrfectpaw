// screens/LoginScreen.tsx
import React, { useState, useRef } from 'react';
import {
  View,
  Text,
  TextInput,
  StyleSheet,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  ImageBackground,
  Image,
  KeyboardAvoidingView,
  Platform,
  Animated,
  ScrollView,
  Keyboard,
  Dimensions,
} from 'react-native';
import { API } from '../src/api';
import { OtpApi } from '../src/otpApi';
import { useNavigation, CommonActions } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import axios from 'axios';
import { RootStackParamList } from '../App'; // ensure this declares 'PersonalTabs' and 'Login'
import { useAuth } from '../src/contexts/AuthContext';

const PINK = '#FFC1CC';
const DARK = '#333';

export default function LoginScreen(): React.ReactElement {
  const [email, setEmail] = useState<string>('');
  const [password, setPassword] = useState<string>('');
  const [showPassword, setShowPassword] = useState<boolean>(false);
  const [loading, setLoading] = useState<boolean>(false);
  const [rememberMe, setRememberMe] = useState<boolean>(true);
  const [emailFocused, setEmailFocused] = useState<boolean>(false);
  const [passwordFocused, setPasswordFocused] = useState<boolean>(false);

  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const { signIn } = useAuth();

  React.useEffect(() => {
    // Check if the user has a pending OTP verification
    const checkVerificationStatus = async () => {
      const hasVerificationPending = await OtpApi.hasVerificationPending();
      
      if (hasVerificationPending) {
        // Get the user info to extract email
        try {
          const userResponse = await API.get('/user');
          const userEmail = userResponse.data.email;
          
          // Navigate to OTP verification screen if verification is pending
          Alert.alert(
            'Email Verification Required',
            'Please complete your email verification.',
            [
              {
                text: 'OK',
                onPress: () => {
                  navigation.navigate('OTPVerification', { email: userEmail });
                }
              }
            ]
          );
        } catch (error) {
          console.error('Error fetching user data:', error);
        }
      }
    };
    
    checkVerificationStatus();
  }, [navigation]);

  const handleLogin = async (): Promise<void> => {
    try {
      setLoading(true);

      const res = await API.post('/login', { email, password });

      // Get the token from the response
      const token = res.data?.token ?? res.data?.access_token ?? res.data?.data?.token;
      if (!token) throw new Error('No token returned from server');

      console.log('🔐 Successfully received auth token');

      // Extract user info directly from the login response if available
      let userInfo = res.data?.user;
      let isEmailVerified = userInfo?.email_verified_at !== null;
      let userEmail = userInfo?.email || email;
      
      // Only make the additional /user request if we don't have complete user info
      if (!userInfo || typeof userInfo.email_verified_at === 'undefined') {
        try {
          console.log('📥 Fetching additional user details...');
          // Need to set the token temporarily for this request
          API.defaults.headers = API.defaults.headers || {};
          API.defaults.headers.common = API.defaults.headers.common || {};
          API.defaults.headers.common['Authorization'] = `Bearer ${token}`;
          
          const userResponse = await API.get('/user');
          userInfo = userResponse.data;
          isEmailVerified = userInfo?.email_verified_at !== null;
          userEmail = userInfo?.email || email;
        } catch (userErr) {
          console.error('⚠️ Error fetching user details, using available info:', userErr);
          // Continue with what we have
        }
      }
      
      // Store the authentication data using the auth context
      await signIn(token, userInfo);
      
      // Check if user needs email verification
      if (!isEmailVerified) {
        console.log('📧 Email verification required');
        // Set verification pending flag
        await OtpApi.setVerificationPending();
        
        // Store the fact that we're in a verification flow
        await AsyncStorage.setItem('verification_flow', 'true');
        
        Alert.alert(
          'Email Verification Required',
          'Please verify your email address before proceeding.',
          [
            {
              text: 'OK',
              onPress: () => {
                navigation.navigate('OTPVerification', { email: userEmail });
              }
            }
          ]
        );
      } else {
        console.log('✅ Email already verified, proceeding to dashboard');
        // Clear any verification flow flag
        await AsyncStorage.removeItem('verification_flow');
        
        // Email is already verified, navigate to dashboard
        // Dispatch reset to the top-most navigator (or global nav ref) so RESET is handled
        try {
          // @ts-ignore
          const globalNav = (global as any).navigationRef;
          if (globalNav && typeof globalNav.dispatch === 'function') {
            globalNav.dispatch(CommonActions.reset({ index: 0, routes: [{ name: 'PersonalTabs' }] }));
          } else {
            let top: any = navigation as any;
            let p = navigation.getParent ? navigation.getParent() : null;
            while (p) {
              top = p;
              p = p.getParent ? p.getParent() : null;
            }

            (top ?? navigation).dispatch(CommonActions.reset({ index: 0, routes: [{ name: 'PersonalTabs' }] }));
          }
        } catch (e) {
          console.error('Failed to reset navigation to PersonalTabs after login, fallback navigate', e);
          (navigation as any).navigate('PersonalTabs');
        }
      }
    } catch (err: unknown) {
      // Clear any tokens since login failed
      await AsyncStorage.removeItem('token');
      await AsyncStorage.removeItem('userToken');
      await AsyncStorage.removeItem('accessToken');
      
      console.error('❌ Login failed:', err);
      
      if (axios.isAxiosError(err)) {
        // Check for specific error conditions
        if (err.response?.status === 401) {
          Alert.alert('Invalid Credentials', 'Please check your email and password and try again.');
        } else if (err.response?.status === 429) {
          Alert.alert('Too Many Attempts', 'Please wait a moment before trying again.');
        } else if (!err.response) {
          Alert.alert('Connection Error', 'Could not connect to the server. Please check your internet connection and try again.');
        } else {
          // Get error message from response
          const msg =
            err.response?.data?.message ??
            err.response?.data ??
            err.message ??
            'Login failed';
          Alert.alert('Login Error', typeof msg === 'string' ? msg : JSON.stringify(msg));
        }
      } else if (err instanceof Error) {
        Alert.alert('Error', err.message);
      } else {
        Alert.alert('Error', 'Something went wrong during login. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  const navigateToRegister = () => {
    navigation.navigate('Register');
  };

  // Reference for ScrollView to programmatically scroll when keyboard appears
  const scrollViewRef = useRef<ScrollView>(null);
  
  // Function to handle keyboard appearance and scroll to password field
  const handlePasswordFocus = () => {
    setPasswordFocused(true);
    // Give time for the keyboard to appear before scrolling
    setTimeout(() => {
      // Scroll more on iOS which has a larger keyboard
      const scrollPosition = Platform.OS === 'ios' ? 200 : 150;
      scrollViewRef.current?.scrollTo({ y: scrollPosition, animated: true });
    }, 150);
  };
  
  // Handle keyboard dismiss
  const handleScreenPress = () => {
    Keyboard.dismiss();
    // Reset scroll when keyboard is dismissed
    scrollViewRef.current?.scrollTo({ y: 0, animated: true });
  };
  
  return (
    <TouchableOpacity 
      style={styles.container} 
      activeOpacity={1} 
      onPress={handleScreenPress}
    >
      {/* Wave background at the top */}
      <View style={styles.topWave}>
        <Image 
          source={require('../assets/pic0.jpg')} 
          style={styles.wavePattern}
          resizeMode="cover"
        />
      </View>

      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 40 : 0}
        style={styles.formWrapper}
      >
        <ScrollView 
          ref={scrollViewRef}
          showsVerticalScrollIndicator={false}
          contentContainerStyle={styles.scrollViewContent}
          keyboardShouldPersistTaps="handled"
        >
          <View style={styles.headerContainer}>
            <View style={styles.logoContainer}>
              <Image 
                source={require('../assets/purrfectpaw_logo.png')} 
                style={styles.logoImage} 
              />
            </View>
            <Text style={styles.headerTitle}>Sign in</Text>
            <Text style={styles.headerSubtitle}>Welcome back to PurrfectPaw</Text>
          </View>

          <View style={styles.formContainer}>
            {/* Email Input */}
            <View style={styles.inputContainer}>
              <Text style={styles.inputLabel}>Email</Text>
              <View style={[
                styles.inputWrapper, 
                emailFocused && styles.inputWrapperFocused
              ]}>
                <TextInput
                  style={styles.input}
                  placeholder="example@mail.com"
                  placeholderTextColor="#BBBBBB"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  value={email}
                  onChangeText={setEmail}
                  onFocus={() => setEmailFocused(true)}
                  onBlur={() => setEmailFocused(false)}
                />
                {email.length > 0 && (
                  <Ionicons 
                    name="checkmark-circle" 
                    size={20} 
                    color="#4CAF50" 
                    style={styles.validIcon} 
                  />
                )}
              </View>
            </View>

            {/* Password Input */}
            <View style={styles.inputContainer}>
              <Text style={styles.inputLabel}>Password</Text>
              <View style={[
                styles.inputWrapper, 
                passwordFocused && styles.inputWrapperFocused
              ]}>
                <TextInput
                  style={styles.input}
                  placeholder="Enter your password"
                  placeholderTextColor="#BBBBBB"
                  secureTextEntry={!showPassword}
                  value={password}
                  onChangeText={setPassword}
                  onFocus={handlePasswordFocus}
                  onBlur={() => setPasswordFocused(false)}
                />
                <TouchableOpacity 
                  style={styles.eyeIcon} 
                  onPress={() => setShowPassword((v) => !v)}
                >
                  <Ionicons 
                    name={showPassword ? 'eye' : 'eye-off'} 
                    size={20} 
                    color={passwordFocused ? PINK : "#999"} 
                  />
                </TouchableOpacity>
              </View>
            </View>
            
            {/* Remember Me & Forgot Password row */}
            <View style={styles.optionsRow}>
              <TouchableOpacity 
                style={styles.rememberMeContainer}
                onPress={() => setRememberMe(!rememberMe)}
                activeOpacity={0.7}
              >
                <View style={styles.checkbox}>
                  {rememberMe && (
                    <Ionicons name="checkmark" size={14} color={PINK} />
                  )}
                </View>
                <Text style={styles.rememberMeText}>Remember Me</Text>
              </TouchableOpacity>
              
              <TouchableOpacity 
                onPress={() => navigation.navigate('ForgotPassword')}
                activeOpacity={0.6}
              >
                <Text style={styles.forgotPasswordText}>Forgot Password?</Text>
              </TouchableOpacity>
            </View>

            {/* Login Button */}
            <TouchableOpacity 
              style={styles.loginButton} 
              onPress={handleLogin} 
              disabled={loading}
            >
              {loading ? (
                <ActivityIndicator color="#FFFFFF" size="small" />
              ) : (
                <View style={styles.buttonContent}>
                  <Text style={styles.loginButtonText}>Login</Text>
                  <Ionicons name="arrow-forward" size={20} color="#FFFFFF" style={{marginLeft: 8}} />
                </View>
              )}
            </TouchableOpacity>
            
            {/* Register Link */}
            <View style={styles.registerContainer}>
              <Text style={styles.registerText}>Don't have an account? </Text>
              <TouchableOpacity onPress={navigateToRegister}>
                <Text style={styles.registerLink}>Sign up</Text>
              </TouchableOpacity>
            </View>

            {/* Extra padding at the bottom to ensure form is scrollable past keyboard */}
            <View style={styles.keyboardSpacer} />
          </View>
        </ScrollView>
      </KeyboardAvoidingView>

      {loading && (
        <View style={styles.loadingOverlay}>
          <ActivityIndicator size="large" color={PINK} />
        </View>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  // Top wave shape with background image
  topWave: {
    height: 240,
    width: '100%',
    backgroundColor: PINK, // Fallback color
    borderBottomLeftRadius: 40,
    borderBottomRightRadius: 40,
    position: 'absolute',
    top: 0,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 8,
  },
  wavePattern: {
    width: '100%',
    height: '100%',
    position: 'absolute',
    opacity: 0.95,
  },
  // Form wrapper
  formWrapper: {
    flex: 1,
    paddingHorizontal: 28,
    paddingTop: Platform.OS === 'ios' ? 200 : 180, // Space below wave, adjusted for platform
  },
  // Header styling
  headerContainer: {
    marginBottom: 30,
    alignItems: 'center',
  },
  logoContainer: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: '#FFFFFF',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  logoImage: {
    width: 65,
    height: 65,
    borderRadius: 32.5,
  },
  headerTitle: {
    fontSize: 34,
    fontWeight: 'bold',
    color: DARK,
    marginBottom: 8,
    letterSpacing: 0.5,
    textShadowColor: 'rgba(0,0,0,0.12)',
    textShadowOffset: {width: 1, height: 1},
    textShadowRadius: 4,
    textAlign: 'center',
  },
  headerSubtitle: {
    fontSize: 16,
    color: '#666',
    marginBottom: 12,
    textAlign: 'center',
  },
  // Form container
  formContainer: {
    width: '100%',
  },
  // Input styling
  inputContainer: {
    marginBottom: 22,
  },
  inputLabel: {
    fontSize: 15,
    fontWeight: '600',
    color: '#555',
    marginBottom: 8,
    paddingLeft: 4,
    letterSpacing: 0.3,
  },
  inputWrapper: {
    width: '100%',
    position: 'relative',
    borderWidth: 1.2,
    borderColor: '#E8E8E8',
    borderRadius: 12,
    backgroundColor: '#FFFFFF',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 1,
  },
  inputWrapperFocused: {
    borderColor: PINK,
    shadowColor: PINK,
    shadowOpacity: 0.1,
    shadowRadius: 5,
    shadowOffset: { width: 0, height: 3 },
    elevation: 3,
  },
  input: {
    width: '100%',
    paddingHorizontal: 18,
    paddingRight: 50,
    paddingVertical: 15,
    fontSize: 16,
    color: DARK,
  },
  eyeIcon: {
    position: 'absolute',
    right: 18,
    top: '50%',
    transform: [{ translateY: -10 }],
    zIndex: 1,
    padding: 5,  // Increased touch target
  },
  validIcon: {
    position: 'absolute',
    right: 18,
    top: '50%',
    transform: [{ translateY: -10 }],
    zIndex: 1,
  },
  // Remember me & forgot password row
  optionsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 28,
    marginTop: 6,
  },
  rememberMeContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  checkbox: {
    width: 20,
    height: 20,
    borderWidth: 1.5,
    borderColor: PINK,
    borderRadius: 5,
    marginRight: 10,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 193, 204, 0.1)',
  },
  rememberMeText: {
    fontSize: 14,
    color: '#555',
    fontWeight: '500',
  },
  forgotPasswordText: {
    fontSize: 14,
    color: PINK,
    fontWeight: '600',
    letterSpacing: 0.2,
  },
  // Login button
  loginButton: {
    backgroundColor: PINK,
    width: '100%',
    borderRadius: 14,
    paddingVertical: 16,
    alignItems: 'center',
    marginBottom: 24,
    shadowColor: PINK,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.35,
    shadowRadius: 12,
    elevation: 6,
  },
  loginButtonText: {
    color: '#FFFFFF',
    fontSize: 17,
    fontWeight: 'bold',
    letterSpacing: 0.5,
  },
  // Register link
  registerContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 20,
    paddingVertical: 10, // Bigger touch target
  },
  registerText: {
    color: '#555',
    fontSize: 15,
    letterSpacing: 0.2,
  },
  registerLink: {
    color: PINK,
    fontWeight: 'bold',
    fontSize: 15,
    letterSpacing: 0.2,
  },
  // Button content with icon
  buttonContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  // ScrollView content container
  scrollViewContent: {
    paddingBottom: Platform.OS === 'ios' ? 120 : 80, // Extra padding at bottom to ensure form is fully scrollable
  },
  // Extra spacing at the bottom to ensure keyboard doesn't cover content
  keyboardSpacer: {
    height: 50, // Extra space at bottom
  },
  // Loading overlay
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.25)',
    justifyContent: 'center',
    alignItems: 'center',
  },
});
