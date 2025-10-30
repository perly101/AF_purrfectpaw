// Import polyfills first, before any other imports
import './src/navigationPolyfills';

import React, { useRef } from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import LoginScreen from './screens/LoginScreen';
import PersonalTabs from './navigation/PersonalTabs';
import EditProfileScreen from './screens/EditProfileScreen';
import ClinicTabs from './navigation/ClinicTabs';
import RegisterScreen from './screens/RegisterScreen';
import BookAppointmentScreen from './screens/BookAppointmentScreen';
import OTPVerificationScreen from './screens/OTPVerificationScreen';
import ForgotPasswordScreen from './screens/ForgotPasswordScreen';
import { setNavigationRef } from './src/api';
import { AuthProvider, useAuth } from './src/contexts/AuthContext';
import AppSplashScreen from './screens/SplashScreen';

export type RootStackParamList = {
  Login: undefined;
  PersonalTabs: undefined;
  EditProfile: undefined;
  Register: undefined;
  ClinicTabs: undefined;
  OTPVerification: {
    email?: string;
  };
  ForgotPassword: undefined;
  BookAppointment: { 
    clinicId: number; 
    clinicName?: string;
    date?: string;
    timeSlot?: {
      start: string;
      end: string;
      display_time: string;
    };
  };
};

const Stack = createNativeStackNavigator<RootStackParamList>();

// Separate component for navigation to access auth context
const AppNavigator: React.FC<{ navigationRef: React.MutableRefObject<any> }> = ({ navigationRef }) => {
  const { user, loading } = useAuth();

  // Show splash screen while checking for authentication
  if (loading) {
    return <AppSplashScreen />;
  }

  return (
    <NavigationContainer 
      ref={(ref) => {
        if (ref) {
          try {
            navigationRef.current = ref;
            setNavigationRef(ref);
            // @ts-ignore
            global.navigationRef = ref;
          } catch (error) {
            console.log('Error setting navigation ref:', error);
          }
        }
      }}
    >
      <Stack.Navigator 
        initialRouteName={user ? "PersonalTabs" : "Login"} 
        screenOptions={{ 
          headerShown: false,
          // Removed complex animation options that might cause casting issues
        }}
      >
        {user ? (
          // Authenticated routes
          <>
            <Stack.Screen name="PersonalTabs" component={PersonalTabs} />
            <Stack.Screen name="EditProfile" component={EditProfileScreen} />
            <Stack.Screen name="ClinicTabs" component={ClinicTabs} />
            <Stack.Screen name="BookAppointment" component={BookAppointmentScreen} />
          </>
        ) : (
          // Unauthenticated routes
          <>
            <Stack.Screen 
              name="Login" 
              component={LoginScreen}
            />
            <Stack.Screen 
              name="Register" 
              component={RegisterScreen}
            />
            <Stack.Screen 
              name="ForgotPassword" 
              component={ForgotPasswordScreen}
            />
            <Stack.Screen 
              name="OTPVerification" 
              component={OTPVerificationScreen}
            />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
};

export default function App() {
  const navigationRef = useRef(null);

  return (
    <AuthProvider>
      <AppNavigator navigationRef={navigationRef} />
    </AuthProvider>
  );
}