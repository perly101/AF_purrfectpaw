import React from 'react';
import { View, Platform, StyleSheet } from 'react-native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useNavigation, useFocusEffect } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import ClinicHomeScreen from '../screens/ClinicHomeScreen';
import ClinicAppointmentsScreen from '../screens/ClinicAppointmentsScreen';
import ClinicGalleryScreen from '../screens/ClinicGalleryScreen';
import ClinicSettingsScreen from '../screens/ClinicSettingsScreen';
import ClinicCalendarScreen from '../screens/ClinicCalendarScreen';
import ClinicNotificationsScreen from '../screens/ClinicNotificationsScreen';
import NotificationBadge from '../components/NotificationBadge';
import FloatingActionMenu from '../components/FloatingActionMenu';

// Define our main colors
const PINK = '#FF9EB1';

const Tab = createBottomTabNavigator();

export default function ClinicTabs() {
  const insets = useSafeAreaInsets();

  // 🧱 Keep Android system navigation bar background WHITE and stable
  useFocusEffect(
    React.useCallback(() => {
      if (Platform.OS === 'android') {
        const NavigationBar = require('expo-navigation-bar');

        const setWhiteNavBar = async () => {
          try {
            if (NavigationBar?.setBackgroundColorAsync) {
              await NavigationBar.setBackgroundColorAsync('#FFFFFF'); // white
            }
            if (NavigationBar?.setButtonStyleAsync) {
              await NavigationBar.setButtonStyleAsync('dark'); // dark icons
            }
          } catch (e) {
            console.log('NavigationBar API not available');
          }
        };

        setWhiteNavBar();
      }
    }, [])
  );

  return (
    <Tab.Navigator
      tabBar={() => null} // Hide the default tab bar completely
      screenOptions={{ headerShown: false }}
    >
      <Tab.Screen 
        name="ClinicHome" 
        options={{ tabBarLabel: 'Home' }}
      >
        {(props) => <ClinicHomeScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
      <Tab.Screen 
        name="ClinicAppointments" 
        options={{ tabBarLabel: 'Appointments' }}
      >
        {(props) => <ClinicAppointmentsScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
      <Tab.Screen 
        name="ClinicCalendar" 
        options={{ tabBarLabel: 'Calendar' }}
      >
        {(props) => <ClinicCalendarScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
      <Tab.Screen 
        name="ClinicGallery" 
        options={{ tabBarLabel: 'Gallery' }}
      >
        {(props) => <ClinicGalleryScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
      <Tab.Screen 
        name="ClinicSettings" 
        options={{ tabBarLabel: 'Settings' }}
      >
        {(props) => <ClinicSettingsScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
    </Tab.Navigator>
  );
}

// Wrapper components that include the FAM on each screen
function ClinicHomeScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  // Debug navigation object
  React.useEffect(() => {
    console.log('🏥 ClinicHomeScreenWithFAM navigation state:', navigation.getState?.());
    console.log('🏥 Navigation methods available:', Object.keys(navigation));
    console.log('🏥 Props navigation:', !!props.navigation);
    console.log('🏥 Hook navigation:', !!hookNavigation);
  }, [navigation, props.navigation, hookNavigation]);
  
  return (
    <View style={styles.screenContainer}>
      <ClinicHomeScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'clinic-home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏥 Home button pressed - already on Home');
              // Already on home screen
            },
          },
          {
            id: 'clinic-appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Navigating to ClinicAppointments from Home');
              try {
                navigation.navigate('ClinicAppointments' as never);
                console.log('✅ Navigation command sent to ClinicAppointments');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-calendar',
            icon: 'calendar-clear-outline',
            label: 'Calendar',
            onPress: () => {
              console.log('📆 Navigating to ClinicCalendar from Home');
              try {
                navigation.navigate('ClinicCalendar' as never);
                console.log('✅ Navigation command sent to ClinicCalendar');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-gallery',
            icon: 'images-outline',
            label: 'Gallery',
            onPress: () => {
              console.log('🖼️ Navigating to ClinicGallery from Home');
              try {
                navigation.navigate('ClinicGallery' as never);
                console.log('✅ Navigation command sent to ClinicGallery');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-settings',
            icon: 'settings-outline',
            label: 'Settings',
            onPress: () => {
              console.log('⚙️ Navigating to ClinicSettings from Home');
              try {
                navigation.navigate('ClinicSettings' as never);
                console.log('✅ Navigation command sent to ClinicSettings');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
        ]}
      />
    </View>
  );
}

function ClinicAppointmentsScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  return (
    <View style={styles.screenContainer}>
      <ClinicAppointmentsScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'clinic-home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏥 Navigating to ClinicHome from Appointments');
              try {
                navigation.navigate('ClinicHome' as never);
                console.log('✅ Navigation command sent to ClinicHome');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Appointments button pressed - already on Appointments');
              // Already on appointments screen
            },
          },
          {
            id: 'clinic-calendar',
            icon: 'calendar-clear-outline',
            label: 'Calendar',
            onPress: () => {
              console.log('📆 Navigating to ClinicCalendar from Appointments');
              try {
                navigation.navigate('ClinicCalendar' as never);
                console.log('✅ Navigation command sent to ClinicCalendar');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-gallery',
            icon: 'images-outline',
            label: 'Gallery',
            onPress: () => {
              console.log('🖼️ Navigating to ClinicGallery from Appointments');
              try {
                navigation.navigate('ClinicGallery' as never);
                console.log('✅ Navigation command sent to ClinicGallery');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-settings',
            icon: 'settings-outline',
            label: 'Settings',
            onPress: () => {
              console.log('⚙️ Navigating to ClinicSettings from Appointments');
              try {
                navigation.navigate('ClinicSettings' as never);
                console.log('✅ Navigation command sent to ClinicSettings');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
        ]}
      />
    </View>
  );
}

function ClinicCalendarScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  return (
    <View style={styles.screenContainer}>
      <ClinicCalendarScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'clinic-home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏥 Navigating to ClinicHome from Calendar');
              try {
                navigation.navigate('ClinicHome' as never);
                console.log('✅ Navigation command sent to ClinicHome');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Navigating to ClinicAppointments from Calendar');
              try {
                navigation.navigate('ClinicAppointments' as never);
                console.log('✅ Navigation command sent to ClinicAppointments');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-calendar',
            icon: 'calendar-clear-outline',
            label: 'Calendar',
            onPress: () => {
              console.log('📆 Calendar button pressed - already on Calendar');
              // Already on calendar screen
            },
          },
          {
            id: 'clinic-gallery',
            icon: 'images-outline',
            label: 'Gallery',
            onPress: () => {
              console.log('🖼️ Navigating to ClinicGallery from Calendar');
              try {
                navigation.navigate('ClinicGallery' as never);
                console.log('✅ Navigation command sent to ClinicGallery');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-settings',
            icon: 'settings-outline',
            label: 'Settings',
            onPress: () => {
              console.log('⚙️ Navigating to ClinicSettings from Calendar');
              try {
                navigation.navigate('ClinicSettings' as never);
                console.log('✅ Navigation command sent to ClinicSettings');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
        ]}
      />
    </View>
  );
}

function ClinicGalleryScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  return (
    <View style={styles.screenContainer}>
      <ClinicGalleryScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'clinic-home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏥 Navigating to ClinicHome from Gallery');
              try {
                navigation.navigate('ClinicHome' as never);
                console.log('✅ Navigation command sent to ClinicHome');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Navigating to ClinicAppointments from Gallery');
              try {
                navigation.navigate('ClinicAppointments' as never);
                console.log('✅ Navigation command sent to ClinicAppointments');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-calendar',
            icon: 'calendar-clear-outline',
            label: 'Calendar',
            onPress: () => {
              console.log('📆 Navigating to ClinicCalendar from Gallery');
              try {
                navigation.navigate('ClinicCalendar' as never);
                console.log('✅ Navigation command sent to ClinicCalendar');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-gallery',
            icon: 'images-outline',
            label: 'Gallery',
            onPress: () => {
              console.log('🖼️ Gallery button pressed - already on Gallery');
              // Already on gallery screen
            },
          },
          {
            id: 'clinic-settings',
            icon: 'settings-outline',
            label: 'Settings',
            onPress: () => {
              console.log('⚙️ Navigating to ClinicSettings from Gallery');
              try {
                navigation.navigate('ClinicSettings' as never);
                console.log('✅ Navigation command sent to ClinicSettings');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
        ]}
      />
    </View>
  );
}

function ClinicSettingsScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  return (
    <View style={styles.screenContainer}>
      <ClinicSettingsScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'clinic-home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏥 Navigating to ClinicHome from Settings');
              try {
                navigation.navigate('ClinicHome' as never);
                console.log('✅ Navigation command sent to ClinicHome');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Navigating to ClinicAppointments from Settings');
              try {
                navigation.navigate('ClinicAppointments' as never);
                console.log('✅ Navigation command sent to ClinicAppointments');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-calendar',
            icon: 'calendar-clear-outline',
            label: 'Calendar',
            onPress: () => {
              console.log('📆 Navigating to ClinicCalendar from Settings');
              try {
                navigation.navigate('ClinicCalendar' as never);
                console.log('✅ Navigation command sent to ClinicCalendar');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-gallery',
            icon: 'images-outline',
            label: 'Gallery',
            onPress: () => {
              console.log('🖼️ Navigating to ClinicGallery from Settings');
              try {
                navigation.navigate('ClinicGallery' as never);
                console.log('✅ Navigation command sent to ClinicGallery');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'clinic-settings',
            icon: 'settings-outline',
            label: 'Settings',
            onPress: () => {
              console.log('⚙️ Settings button pressed - already on Settings');
              // Already on settings screen
            },
          },
        ]}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screenContainer: {
    flex: 1,
  },
}); 