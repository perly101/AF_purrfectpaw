import React from 'react';
import { View, Platform } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import ClinicHomeScreen from '../screens/ClinicHomeScreen';
import ClinicAppointmentsScreen from '../screens/ClinicAppointmentsScreen';
import ClinicGalleryScreen from '../screens/ClinicGalleryScreen';
import ClinicSettingsScreen from '../screens/ClinicSettingsScreen';
import ClinicCalendarScreen from '../screens/ClinicCalendarScreen';
import ClinicNotificationsScreen from '../screens/ClinicNotificationsScreen';
import NotificationBadge from '../components/NotificationBadge';

// Define our main colors
const PINK = '#FF9EB1';

const Tab = createBottomTabNavigator();

export default function ClinicTabs() {
  const insets = useSafeAreaInsets();
  
  return (
    <Tab.Navigator
      screenOptions={{
        tabBarActiveTintColor: PINK,
        tabBarInactiveTintColor: '#888',
        tabBarShowLabel: true,
        tabBarStyle: {
          height: 60 + (Platform.OS === 'ios' ? insets.bottom : 0),
          paddingBottom: Platform.OS === 'ios' ? insets.bottom : 10,
          paddingTop: 10,
          backgroundColor: '#FFFFFF',
          borderTopWidth: 0,
          borderTopColor: 'transparent',
          elevation: 15,
          shadowColor: '#000',
          shadowOffset: { width: 0, height: -3 },
          shadowOpacity: 0.15,
          shadowRadius: 8,
          borderTopLeftRadius: 20,
          borderTopRightRadius: 20,
        },
        headerShown: false,
        tabBarItemStyle: {
          paddingVertical: 5,
        },
        tabBarLabelStyle: {
          fontWeight: '500',
          fontSize: 12,
        },
      }}
    >
      <Tab.Screen name="ClinicHome" component={ClinicHomeScreen} />
      <Tab.Screen 
        name="ClinicAppointments" 
        component={ClinicAppointmentsScreen}
        options={{
          // Using an empty string instead of null for the badge
          // The badge will be updated when an appointment is selected
        }}
      />
      <Tab.Screen 
        name="ClinicCalendar" 
        component={ClinicCalendarScreen}
        options={{ 
          title: 'Availability'
        }}
      />
      <Tab.Screen name="ClinicGallery" component={ClinicGalleryScreen} />
      {/* <Tab.Screen name="ClinicNotifications" component={ClinicNotificationsScreen} /> */}
      <Tab.Screen name="ClinicSettings" component={ClinicSettingsScreen} />
    </Tab.Navigator>
  );
} 