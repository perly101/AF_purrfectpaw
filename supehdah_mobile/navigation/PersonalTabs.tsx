import React, { useEffect, useState, useRef } from 'react';
import {
  View,
  Platform,
  StyleSheet,
} from 'react-native';
import { AppState } from 'react-native';
import { useFocusEffect, useNavigation } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import HomeScreen from '../screens/HomeScreen';
import AppointmentsScreen from '../screens/AppointmentsScreen';
import SettingsScreen from '../screens/SettingsScreen';
import FloatingActionMenu from '../components/FloatingActionMenu';

const Tab = createBottomTabNavigator();

export default function PersonalTabs() {
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
            console.log('Failed to set navigation bar color', e);
          }
        };

        // Set immediately when screen focused
        setWhiteNavBar();

        // Reapply when returning from background
        const sub = AppState.addEventListener('change', (state) => {
          if (state === 'active') setWhiteNavBar();
        });

        return () => sub.remove();
      }
    }, [])
  );

  return (
    <Tab.Navigator
      tabBar={() => null} // Hide the default tab bar completely
      screenOptions={{ headerShown: false }}
    >
      <Tab.Screen 
        name="Home" 
        options={{ tabBarLabel: 'Home' }}
      >
        {(props) => <HomeScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
      <Tab.Screen 
        name="Appointments" 
        options={{ tabBarLabel: 'Appointments' }}
      >
        {(props) => <AppointmentsScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
      <Tab.Screen 
        name="Settings" 
        options={{ tabBarLabel: 'Settings' }}
      >
        {(props) => <SettingsScreenWithFAM {...props} navigation={props.navigation} />}
      </Tab.Screen>
    </Tab.Navigator>
  );
}

// Wrapper components that include the FAM on each screen
function HomeScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  // Debug navigation object
  React.useEffect(() => {
    console.log('🏠 HomeScreenWithFAM navigation state:', navigation.getState?.());
    console.log('🏠 Navigation methods available:', Object.keys(navigation));
    console.log('🏠 Props navigation:', !!props.navigation);
    console.log('🏠 Hook navigation:', !!hookNavigation);
  }, [navigation, props.navigation, hookNavigation]);
  
  return (
    <View style={styles.screenContainer}>
      <HomeScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏠 Home button pressed - already on Home');
              // Already on home screen, maybe refresh or scroll to top
            },
          },
          {
            id: 'appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Navigating to Appointments from Home');
              try {
                navigation.navigate('Appointments' as never);
                console.log('✅ Navigation command sent to Appointments');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'settings',
            icon: 'settings-outline',
            label: 'Settings',
            onPress: () => {
              console.log('⚙️ Navigating to Settings from Home');
              try {
                navigation.navigate('Settings' as never);
                console.log('✅ Navigation command sent to Settings');
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

function AppointmentsScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  return (
    <View style={styles.screenContainer}>
      <AppointmentsScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏠 Navigating to Home from Appointments');
              try {
                navigation.navigate('Home' as never);
                console.log('✅ Navigation command sent to Home');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Appointments button pressed - already on Appointments');
              // Already on appointments screen
            },
          },
          {
            id: 'settings',
            icon: 'settings-outline',
            label: 'Settings',
            onPress: () => {
              console.log('⚙️ Navigating to Settings from Appointments');
              try {
                navigation.navigate('Settings' as never);
                console.log('✅ Navigation command sent to Settings');
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

function SettingsScreenWithFAM(props: any) {
  const hookNavigation = useNavigation();
  const navigation = props.navigation || hookNavigation;
  
  return (
    <View style={styles.screenContainer}>
      <SettingsScreen {...props} />
      <FloatingActionMenu
        actions={[
          {
            id: 'home',
            icon: 'home-outline',
            label: 'Home',
            onPress: () => {
              console.log('🏠 Navigating to Home from Settings');
              try {
                navigation.navigate('Home' as never);
                console.log('✅ Navigation command sent to Home');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'appointments',
            icon: 'calendar-outline',
            label: 'Appointments',
            onPress: () => {
              console.log('📅 Navigating to Appointments from Settings');
              try {
                navigation.navigate('Appointments' as never);
                console.log('✅ Navigation command sent to Appointments');
              } catch (error) {
                console.error('❌ Navigation failed:', error);
              }
            },
          },
          {
            id: 'settings',
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