// Import polyfills first, before any other imports
import './src/navigationPolyfills';

import React, { useRef } from 'react';
import { NavigationContainer, DefaultTheme } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { screenTransitionOptions } from './src/utils/pageTransitions';
import LoginScreen from './screens/LoginScreen';
import PersonalTabs from './navigation/PersonalTabs';
import EditProfileScreen from './screens/EditProfileScreen';
import ClinicTabs from './navigation/ClinicTabs';
import RegisterScreen from './screens/RegisterScreen';
import BookAppointmentScreen from './screens/BookAppointmentScreen';
import OTPVerificationScreen from './screens/OTPVerificationScreen';
import ForgotPasswordScreen from './screens/ForgotPasswordScreen';
import { setNavigationRef } from './src/api';
import { NotificationService } from './src/services/NotificationService';
import { AuthProvider, useAuth } from './src/contexts/AuthContext';
// Import renamed component
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
    date?: string;      // Date in YYYY-MM-DD format from calendar
    timeSlot?: {        // Selected time slot from calendar
      start: string;
      end: string;
      display_time: string;
    };
  };
  Records: undefined;
  AppointmentDetails: { id: number | string };
  // ConnectionTest removed from main flow
};

const Stack = createNativeStackNavigator<RootStackParamList>();

export default function App() {
  // Create a navigation reference to be used for authentication redirects
  const navigationRef = useRef(null);

  // Initialize notifications and handle errors
  React.useEffect(() => {
    // Initialize notification service
    NotificationService.initialize().catch(error => {
      console.log('Failed to initialize notifications:', error);
    });
    
    const errorHandler = (error: any) => {
      console.log('Global error caught:', error);
    };
    
    // Handle global errors
    // @ts-ignore - ErrorUtils exists in React Native but TypeScript doesn't know about it
    if (global.ErrorUtils) {
      // @ts-ignore - ErrorUtils exists in React Native but TypeScript doesn't know about it
      global.ErrorUtils.setGlobalHandler(errorHandler);
    }
    
    // Clean up on component unmount
    return () => {
      NotificationService.cleanUp();
    };
  }, []);

  return (
    <AuthProvider>
      <AppNavigator navigationRef={navigationRef} />
    </AuthProvider>
  );
}

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
        // Set the navigation reference in our API module for auth redirects
        if (ref) {
          try {
            // @ts-ignore - TypeScript doesn't know about the custom setNavigationRef function
            navigationRef.current = ref;
            setNavigationRef(ref);
            
            // Make navigationRef available globally for notifications
            // @ts-ignore - TypeScript doesn't know about global
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
          ...screenTransitionOptions.stackTransition,
          presentation: 'card',
          animationTypeForReplace: 'push',
        }}
      >
        {user ? (
          // Authenticated routes
          <>
            <Stack.Screen name="PersonalTabs" component={PersonalTabs} />
            {/* Records: full appointment history for current user */}
            <Stack.Screen name="Records" component={require('./screens/RecordsScreen').default} />
            {/* Appointment details for user's appointments */}
            <Stack.Screen name="AppointmentDetails" component={require('./screens/AppointmentDetails').default} />
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
              options={screenTransitionOptions.fadeTransition}
            />
            <Stack.Screen 
              name="Register" 
              component={RegisterScreen}
              options={screenTransitionOptions.stackTransition}
            />
            <Stack.Screen 
              name="ForgotPassword" 
              component={ForgotPasswordScreen}
              options={screenTransitionOptions.modalTransition}
            />
          </>
        )}
        {/* Shared routes (available in both authenticated and unauthenticated states) */}
        <Stack.Screen 
          name="OTPVerification" 
          component={OTPVerificationScreen}
          options={screenTransitionOptions.fadeTransition}
        />
      </Stack.Navigator>
    </NavigationContainer>
  );
}


// import React, { useState } from 'react';
// import { NavigationContainer } from '@react-navigation/native';
// import { createNativeStackNavigator } from '@react-navigation/native-stack';
// import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
// import LoginScreen from './supehdah/screens/LoginScreen';
// import HomeScreen from './supehdah/screens/HomeScreen';
// // ...import other screens...

// const Stack = createNativeStackNavigator();
// const Tab = createBottomTabNavigator();

// function PersonalTabs() {
//   return (
//     <Tab.Navigator>
//       <Tab.Screen name="Home" component={HomeScreen} />
//       <Tab.Screen name="Appointments" component={AppointmentsScreen} />
//       <Tab.Screen name="Settings" component={SettingsScreen} />
//     </Tab.Navigator>
//   );
// }

// function ClinicTabs() {
//   return (
//     <Tab.Navigator>
//       <Tab.Screen name="ClinicHome" component={ClinicHomeScreen} />
//       <Tab.Screen name="ClinicAppointments" component={ClinicAppointmentsScreen} />
//       <Tab.Screen name="Gallery" component={GalleryScreen} />
//       <Tab.Screen name="Settings" component={ClinicSettingsScreen} />
//     </Tab.Navigator>
//   );
// }

// export default function App() {
//   const [mode, setMode] = useState<'personal' | 'clinic'>('personal');
//   // ...manage selectedClinic state...

//   return (
//     <NavigationContainer>
//       <Stack.Navigator screenOptions={{ headerShown: false }}>
//         <Stack.Screen name="Login" component={LoginScreen} />
//         {mode === 'personal' ? (
//           <Stack.Screen name="PersonalTabs" component={PersonalTabs} />
//         ) : (
//           <Stack.Screen name="ClinicTabs" component={ClinicTabs} />
//         )}
//       </Stack.Navigator>
//     </NavigationContainer>
//   );
// }