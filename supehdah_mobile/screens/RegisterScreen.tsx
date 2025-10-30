// screens/RegisterScreen.tsx
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
  ScrollView,
  Modal,
  Animated,
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
import { RootStackParamList } from '../App';
import DateTimePicker from '@react-native-community/datetimepicker';

const PINK = '#FFC1CC';
const DARK = '#333';

export default function RegisterScreen(): React.ReactElement {
  // Form data state
  const [firstName, setFirstName] = useState<string>('');
  const [middleName, setMiddleName] = useState<string>('');
  const [lastName, setLastName] = useState<string>('');
  const [email, setEmail] = useState<string>('');
  const [phoneNumber, setPhoneNumber] = useState<string>('');
  const [gender, setGender] = useState<string>('');
  const [birthday, setBirthday] = useState<Date | null>(null);
  const [showDatePicker, setShowDatePicker] = useState<boolean>(false);
  const [password, setPassword] = useState<string>('');
  const [passwordConfirmation, setPasswordConfirmation] = useState<string>('');
  const [showPassword, setShowPassword] = useState<boolean>(false);
  const [showPasswordConfirmation, setShowPasswordConfirmation] = useState<boolean>(false);
  const [loading, setLoading] = useState<boolean>(false);
  const [genderModalVisible, setGenderModalVisible] = useState<boolean>(false);
  
  // UI state for focus and animations
  const [firstNameFocused, setFirstNameFocused] = useState<boolean>(false);
  const [lastNameFocused, setLastNameFocused] = useState<boolean>(false);
  const [emailFocused, setEmailFocused] = useState<boolean>(false);
  const [phoneFocused, setPhoneFocused] = useState<boolean>(false);
  const [passwordFocused, setPasswordFocused] = useState<boolean>(false);
  const [confirmPasswordFocused, setConfirmPasswordFocused] = useState<boolean>(false);
  
  // Animation value for screen entry
  const fadeAnim = useRef(new Animated.Value(0)).current;
  const slideAnim = useRef(new Animated.Value(30)).current;
  
  // Reference for scroll view to handle keyboard
  const scrollViewRef = useRef<ScrollView>(null);

  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  
  // Animation effect when component mounts
  React.useEffect(() => {
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 1,
        duration: 600,
        useNativeDriver: true
      }),
      Animated.timing(slideAnim, {
        toValue: 0,
        duration: 600,
        useNativeDriver: true
      })
    ]).start();
  }, [fadeAnim, slideAnim]);
  
  // Function to handle keyboard appearance and scroll to active input
  const handleInputFocus = (inputType: string, position: number) => {
    switch(inputType) {
      case 'firstName':
        setFirstNameFocused(true);
        break;
      case 'lastName':
        setLastNameFocused(true);
        break;
      case 'email':
        setEmailFocused(true);
        break;
      case 'phone':
        setPhoneFocused(true);
        break;
      case 'password':
        setPasswordFocused(true);
        break;
      case 'confirmPassword':
        setConfirmPasswordFocused(true);
        break;
    }
    
    // Scroll to position of the focused input
    setTimeout(() => {
      scrollViewRef.current?.scrollTo({ y: position, animated: true });
    }, 100);
  };
  
  // Handle keyboard dismiss
  const handleScreenPress = () => {
    Keyboard.dismiss();
  };

  const handleRegister = async (): Promise<void> => {
    if (!firstName || !lastName || !email || !gender || !birthday || !password || !passwordConfirmation) {
      Alert.alert('Error', 'Required fields are missing');
      return;
    }

    if (password !== passwordConfirmation) {
      Alert.alert('Error', 'Passwords do not match');
      return;
    }

    try {
      setLoading(true);
      
      // Format birthday to YYYY-MM-DD if available
      const formattedBirthday = birthday ? 
        birthday.toISOString().split('T')[0] : null;
      
      const res = await API.post('/register', {
        first_name: firstName,
        middle_name: middleName || null,
        last_name: lastName,
        email: email,
        phone_number: phoneNumber || null,
        gender: gender || null,
        birthday: formattedBirthday,
        password: password,
        password_confirmation: passwordConfirmation
      });

      // After successful registration, automatically login the user
      const loginRes = await API.post('/login', { email, password });
      
      // Get token from response
      const token = loginRes.data?.token ?? loginRes.data?.access_token ?? loginRes.data?.data?.token;
      if (!token) throw new Error('No token returned from server');

      await AsyncStorage.setItem('token', token);

      // Set authorization header for future requests
      API.defaults.headers = API.defaults.headers || {};
      API.defaults.headers.common = API.defaults.headers.common || {};
      API.defaults.headers.common['Authorization'] = `Bearer ${token}`;

      // Check if the user's email is verified
      const userInfo = loginRes.data?.user;
      const isEmailVerified = userInfo?.email_verified_at !== null;
      
      if (isEmailVerified) {
        // Email is already verified, navigate to dashboard
        Alert.alert('Success', 'Registration successful!');
        // Dispatch reset to parent (root) navigator if available to ensure the action is handled
        // After registration + verified email, send user to the Login page so they can sign in.
        const parent = navigation.getParent();
        (parent ?? navigation).dispatch(
          CommonActions.reset({
            index: 0,
            routes: [{ name: 'Login' }],
          })
        );
      } else {
        // Email needs verification, navigate to OTP verification screen
        if (res.data?.otp_sent === false) {
          // OTP wasn't automatically sent, notify user
          Alert.alert(
            'Almost there!', 
            'Registration successful! Please request a verification code to verify your email.',
            [
              {
                text: 'OK',
                onPress: async () => {
                  // Set verification pending flag
                  await OtpApi.setVerificationPending();
                  
                  // Navigate to OTP verification screen where user will need to manually resend
                  navigation.navigate('OTPVerification', { email });
                }
              }
            ]
          );
        } else {
          // OTP was sent automatically
          Alert.alert(
            'Success', 
            'Registration successful! We have sent a verification code to your email.',
            [
              {
                text: 'OK',
                onPress: async () => {
                  // Set verification pending flag
                  await OtpApi.setVerificationPending();
                  
                  // Navigate to OTP verification screen
                  navigation.navigate('OTPVerification', { email });
                }
              }
            ]
          );
        }
      }
    } catch (err: unknown) {
      if (axios.isAxiosError(err)) {
        const errorData = err.response?.data;
        let errorMessage = 'Registration failed';

        if (errorData?.errors) {
          // Laravel validation errors
          const errors = Object.values(errorData.errors).flat();
          errorMessage = errors.join('\n');
        } else if (errorData?.message) {
          errorMessage = errorData.message;
        }

        Alert.alert('Error', errorMessage);
      } else if (err instanceof Error) {
        Alert.alert('Error', err.message);
      } else {
        Alert.alert('Error', 'Something went wrong');
      }
    } finally {
      setLoading(false);
    }
  };

  const navigateToLogin = () => {
    // Animate out before navigating
    Animated.parallel([
      Animated.timing(fadeAnim, {
        toValue: 0,
        duration: 300,
        useNativeDriver: true
      }),
      Animated.timing(slideAnim, {
        toValue: -30,
        duration: 300,
        useNativeDriver: true
      })
    ]).start(() => {
      navigation.navigate('Login');
    });
  };

  return (
    <TouchableOpacity 
      activeOpacity={1} 
      style={styles.container}
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
          <Animated.View 
            style={[
              styles.formContainer,
              { opacity: fadeAnim, transform: [{ translateY: slideAnim }] }
            ]}
          >
            <View style={styles.headerContainer}>
              <View style={styles.logoContainer}>
                <Image source={require('../assets/purrfectpaw_logo.png')} style={styles.logoImage} />
              </View>
              <Text style={styles.headerTitle}>Create Accounts</Text>
              <Text style={styles.headerSubtitle}>Join PurrfectPaw today</Text>
            </View>

            {/* First Name Input */}
            <View style={styles.inputWrapper}>
              <TextInput
                style={styles.inputField}
                placeholder="First Name *"
                autoCapitalize="words"
                value={firstName}
                onChangeText={setFirstName}
              />
            </View>

            {/* Middle Name Input */}
            <View style={styles.inputWrapper}>
              <TextInput
                style={styles.inputField}
                placeholder="Middle Name (optional)"
                autoCapitalize="words"
                value={middleName}
                onChangeText={setMiddleName}
              />
            </View>

            {/* Last Name Input */}
            <View style={styles.inputWrapper}>
              <TextInput
                style={styles.inputField}
                placeholder="Last Name *"
                autoCapitalize="words"
                value={lastName}
                onChangeText={setLastName}
              />
            </View>

            {/* Email Input */}
            <View style={styles.inputWrapper}>
              <TextInput
                style={styles.inputField}
                placeholder="Email *"
                keyboardType="email-address"
                autoCapitalize="none"
                value={email}
                onChangeText={setEmail}
              />
            </View>

            {/* Phone Number Input */}
            <View style={styles.inputWrapper}>
              <TextInput
                style={styles.inputField}
                placeholder="Phone Number"
                keyboardType="phone-pad"
                value={phoneNumber}
                onChangeText={setPhoneNumber}
              />
            </View>

            {/* Gender Selection */}
            <TouchableOpacity 
              style={styles.genderButton} 
              onPress={() => setGenderModalVisible(true)}
            >
              <Text style={[styles.genderText, {color: gender ? DARK : '#a0a0a0'}]}>
                {gender ? 
                 (gender === 'female' ? 'Female' : 
                  gender === 'male' ? 'Male' : 
                  'Prefer not to say') : 
                 'Select Gender *'}
              </Text>
              <Text>▼</Text>
            </TouchableOpacity>

            {/* Birthday Selector */}
            <TouchableOpacity 
              style={styles.datePickerButton}
              onPress={() => setShowDatePicker(true)}
            >
              <Text style={[styles.dateText, {color: birthday ? DARK : '#a0a0a0'}]}>
                {birthday ? birthday.toLocaleDateString() : 'Select Birthday *'}
              </Text>
              <Text>📅</Text>
            </TouchableOpacity>

            {/* Password Input */}
            <View style={styles.inputWrapper}>
              <TextInput
                style={styles.inputField}
                placeholder="Password *"
                secureTextEntry={!showPassword}
                value={password}
                onChangeText={setPassword}
              />
              <TouchableOpacity style={styles.eyeIcon} onPress={() => setShowPassword((v) => !v)}>
                <Ionicons name={showPassword ? 'eye' : 'eye-off'} size={24} color="#666" />
              </TouchableOpacity>
            </View>

            {/* Confirm Password Input */}
            <View style={styles.inputWrapper}>
              <TextInput
                style={styles.inputField}
                placeholder="Confirm Password"
                secureTextEntry={!showPasswordConfirmation}
                value={passwordConfirmation}
                onChangeText={setPasswordConfirmation}
              />
              <TouchableOpacity style={styles.eyeIcon} onPress={() => setShowPasswordConfirmation((v) => !v)}>
                <Ionicons name={showPasswordConfirmation ? 'eye' : 'eye-off'} size={24} color="#666" />
              </TouchableOpacity>
            </View>

            {/* Register Button */}
            <TouchableOpacity style={styles.primaryButton} onPress={handleRegister} disabled={loading}>
              {loading ? <ActivityIndicator color="#000" /> : <Text style={styles.primaryButtonText}>Register</Text>}
            </TouchableOpacity>

            {/* Login Link */}
            <View style={styles.loginLinkContainer}>
              <Text style={styles.loginText}>Already registered? </Text>
              <TouchableOpacity onPress={navigateToLogin}>
                <Text style={styles.loginLink}>Login here</Text>
              </TouchableOpacity>
            </View>
          </Animated.View>
        </ScrollView>
      </KeyboardAvoidingView>

      {loading && (
        <View style={styles.loadingOverlay}>
          <ActivityIndicator size="large" color={PINK} />
        </View>
      )}

      {/* Gender Selection Modal */}
      <Modal
        animationType="slide"
        transparent={true}
        visible={genderModalVisible}
        onRequestClose={() => setGenderModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Select Gender</Text>
            
            <TouchableOpacity 
              style={styles.modalOption}
              onPress={() => {
                setGender('female');
                setGenderModalVisible(false);
              }}
            >
              <Text style={styles.modalOptionText}>Female</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.modalOption}
              onPress={() => {
                setGender('male');
                setGenderModalVisible(false);
              }}
            >
              <Text style={styles.modalOptionText}>Male</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={styles.modalOption}
              onPress={() => {
                setGender('prefer_not_say');
                setGenderModalVisible(false);
              }}
            >
              <Text style={styles.modalOptionText}>Prefer not to say</Text>
            </TouchableOpacity>
            
            <TouchableOpacity 
              style={[styles.modalOption, {backgroundColor: PINK}]}
              onPress={() => setGenderModalVisible(false)}
            >
              <Text style={[styles.modalOptionText, {fontWeight: 'bold'}]}>Cancel</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* Date Picker for Birthday */}
      {showDatePicker && (
        <DateTimePicker
          value={birthday || new Date()}
          mode="date"
          display="default"
          onChange={(event, selectedDate) => {
            setShowDatePicker(false);
            if (selectedDate && event.type !== 'dismissed') {
              setBirthday(selectedDate);
            }
          }}
          maximumDate={new Date()} // Cannot select future dates
        />
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
    paddingTop: Platform.OS === 'ios' ? 200 : 180, // Space below wave
  },
  // Scroll view content
  scrollViewContent: {
    paddingBottom: Platform.OS === 'ios' ? 120 : 80, // Extra padding at bottom to ensure form is fully scrollable
  },
  // Form container
  formContainer: {
    width: '100%',
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
    marginBottom: 20,
    textAlign: 'center',
  },
  // Input styling
  inputContainer: {
    marginBottom: 16,
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
    marginBottom: 16,
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
    borderWidth: 1.2,
    borderColor: '#E8E8E8',
    borderRadius: 12,
    backgroundColor: '#FFFFFF',
    marginBottom: 16,
  },
  inputField: {
    width: '100%',
    paddingHorizontal: 18,
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
    padding: 5, // Increased touch target
  },
  validIcon: {
    position: 'absolute',
    right: 18,
    top: '50%',
    transform: [{ translateY: -10 }],
    zIndex: 1,
  },
  // Register Button
  primaryButton: {
    backgroundColor: PINK,
    width: '100%',
    borderRadius: 16,
    paddingVertical: 18,
    alignItems: 'center',
    marginBottom: 24,
    marginTop: 10,
    shadowColor: PINK,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.4,
    shadowRadius: 15,
    elevation: 8,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.2)',
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontSize: 17,
    fontWeight: 'bold',
    letterSpacing: 0.5,
  },
  // Button content with icon
  buttonContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  // Login Link
  loginLinkContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    marginTop: 20,
    paddingVertical: 10,
  },
  loginText: {
    color: '#555',
    fontSize: 15,
    letterSpacing: 0.2,
  },
  loginLink: {
    color: PINK,
    fontWeight: 'bold',
    fontSize: 15,
    letterSpacing: 0.2,
  },
  // Loading overlay
  loadingOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.25)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  // Extra spacing
  keyboardSpacer: {
    height: 50,
  },
  // Modal styles
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContent: {
    width: '100%',
    backgroundColor: '#fff',
    borderRadius: 15,
    padding: 20,
    elevation: 5,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: DARK,
    marginBottom: 15,
    textAlign: 'center',
  },
  modalOption: {
    width: '100%',
    padding: 15,
    borderRadius: 10,
    marginBottom: 10,
    backgroundColor: '#f0f0f0',
  },
  modalOptionText: {
    fontSize: 16,
    textAlign: 'center',
    color: DARK,
  },
  // Date picker button
  datePickerButton: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    paddingHorizontal: 18,
    paddingVertical: 15,
    width: '100%',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
    borderWidth: 1.2,
    borderColor: '#E8E8E8',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 1,
  },
  dateText: {
    fontSize: 16,
    color: DARK,
  },
  // Gender button
  genderButton: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    paddingHorizontal: 18,
    paddingVertical: 15,
    width: '100%',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
    borderWidth: 1.2,
    borderColor: '#E8E8E8',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 3,
    elevation: 1,
  },
  genderText: {
    fontSize: 16,
    color: DARK,
  },
  rowContainer: {
    flexDirection: 'row',
    width: '100%',
    justifyContent: 'space-between',
  },
  halfWidth: {
    width: '48%', // leaving a little space between them
  },
});
