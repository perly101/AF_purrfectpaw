import React, { useEffect, useState, useRef } from 'react';
import {
  View,
  Platform,
  StyleSheet,
  Text,
  Dimensions,
  TouchableOpacity,
  Animated,
  PanResponder,
  Easing,
} from 'react-native';
import { AppState } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { EdgeInsets } from 'react-native-safe-area-context';
import HomeScreen from '../screens/HomeScreen';
import AppointmentsScreen from '../screens/AppointmentsScreen';
import SettingsScreen from '../screens/SettingsScreen';
import NotificationsScreen from '../screens/NotificationsScreen';
import { Ionicons } from '@expo/vector-icons';
import NotificationBadge from '../components/NotificationBadge';
import { LinearGradient } from 'expo-linear-gradient';
import { getResponsiveTabBarSizes, getResponsiveElevation } from '../src/utils/responsiveTabBar';

// expo-navigation-bar is required dynamically at runtime using `require` with `// @ts-ignore`.
// If you need TypeScript types for this package, add a separate declaration file (for example:
//   types/expo-navigation-bar.d.ts
// with `declare module 'expo-navigation-bar';` or proper type definitions there).

// Define colors for modern Android tab bar design
const ACTIVE_ORANGE = '#FF8C00';
const WHITE = '#FFFFFF';
const INACTIVE_GRAY = '#B0B0B0';
const TAB_BAR_BG = '#FFFFFF';
const SHADOW_COLOR = '#000000';

const Tab = createBottomTabNavigator();

// Haptic feedback helper
const triggerHapticFeedback = () => {
  try {
    const Haptics = require('expo-haptics');
    Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
  } catch {
    try {
      const { Vibration } = require('react-native');
      Vibration.vibrate(50);
    } catch {
      console.log('Haptic feedback not available');
    }
  }
};

export default function PersonalTabs() {
  const insets = useSafeAreaInsets();
  const [notificationCount, setNotificationCount] = useState(0);
  const screenHeight = Dimensions.get('window').height;
  
  // Get responsive sizes for tab bar
  const responsiveSizes = getResponsiveTabBarSizes();
  
  // Determine if device has hardware buttons (estimation)
  const hasHardwareButtons = Platform.OS === 'android' && !insets.bottom;
  
  // Simulate fetching notification count (replace with actual API call)
 // 🧱 Keep Android system navigation bar background WHITE and stable
// 🧱 Keep Android system navigation bar WHITE and stable
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


  // Note: Icon rendering is now handled by the renderIconFor function in CustomTabBar

  // Note: Bottom margin is now handled directly in the gradient tab bar

  return (
    <Tab.Navigator
      // Use a custom tab bar with 3 tabs total (Home, Appointments, Settings)
      tabBar={(props) => <CustomTabBar {...props} insets={insets} />}
      screenOptions={{ headerShown: false }}
    >
      <Tab.Screen 
        name="Home" 
        component={HomeScreen}
        options={{
          tabBarLabel: 'Home',
        }}
      />
      <Tab.Screen 
        name="Appointments" 
        component={AppointmentsScreen}
        options={{
          tabBarLabel: 'Appointments',
        }}
      />
      <Tab.Screen 
        name="Settings" 
        component={SettingsScreen}
        options={{
          tabBarLabel: 'Settings',
        }}
      />
    </Tab.Navigator>
  );
}

const styles = StyleSheet.create({
  iconContainer: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badge: {
    position: 'absolute',
    right: -5,
    top: -2,
    backgroundColor: '#FF4757',
    borderRadius: 10,
    minWidth: 18,
    height: 18,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: '#FFF',
  },
  badgeText: {
    color: '#FFF',
    fontSize: 10,
    fontWeight: 'bold',
    paddingHorizontal: 3,
  },
  tabBarButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  activeTab: {
    backgroundColor: 'rgba(10, 163, 255, 0.2)',
    borderRadius: 15,
  },
});

// --- Modern Android Tab Bar with Orange Active Indicators ---
function CustomTabBar({ state, descriptors, navigation, insets }: any) {
  // Animation values for active tab indicator
  const activeTabScale = useRef(new Animated.Value(1)).current;
  
  // Handle tab press with micro-interaction
  const handleTabPress = (routeName: string, index: number) => {
    triggerHapticFeedback();
    
    // Subtle scale animation for active indicator
    Animated.sequence([
      Animated.timing(activeTabScale, {
        toValue: 1.05,
        duration: 100,
        useNativeDriver: true,
        easing: Easing.out(Easing.cubic),
      }),
      Animated.spring(activeTabScale, {
        toValue: 1,
        useNativeDriver: true,
        damping: 15,
        stiffness: 300,
      }),
    ]).start();

    navigation.navigate(routeName);
  };

  const extraBottomPadding = insets?.bottom ?? 0;

  return (
    <View style={[
      androidStyles.container, 
      { 
        paddingBottom: Platform.OS === 'android' ? extraBottomPadding + 0 : insets.bottom,
 // Space above Android nav buttons
        marginBottom: 0,
      }
    ]}>
      {/* Clean white tab bar */}
      <View style={[
        androidStyles.tabBarCard,
        {
          marginBottom: 0, // Small gap above system nav buttons
        }
      ]}>
        <View style={androidStyles.tabRow}>
          {state.routes.map((route: any, index: number) => {
            const focused = state.index === index;
            const { options } = descriptors[route.key];
            const label = options.tabBarLabel || route.name;

            return (
              <TouchableOpacity
                key={route.key}
                style={androidStyles.tabItem}
                onPress={() => handleTabPress(route.name, index)}
                activeOpacity={0.6}
                accessibilityLabel={label}
                accessibilityRole="tab"
                accessibilityState={{ selected: focused }}
              >
                <View style={androidStyles.iconContainer}>
                  {renderIconFor(route.name, focused, 24)}
                  
                  {/* Orange indicator line for active tab */}
                  {focused && (
                    <Animated.View 
                      style={[
                        androidStyles.activeIndicator,
                        { transform: [{ scale: activeTabScale }] }
                      ]} 
                    />
                  )}
                </View>
                
                {/* Tab label */}
                <Text 
                  style={[
                    androidStyles.tabLabel,
                    focused ? androidStyles.activeLabelColor : androidStyles.inactiveLabelColor
                  ]}
                >
                  {label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </View>
    </View>
  );
}

function renderIconFor(routeName: string, focused: boolean, iconSize?: number) {
  const getIconName = () => {
    switch (routeName) {
      case 'Home':
        return 'home-outline';
      case 'Appointments':
        return 'calendar-outline';
      case 'Settings':
        return 'settings-outline';
      default:
        return 'home-outline';
    }
  };

  const iconColor = focused ? ACTIVE_ORANGE : INACTIVE_GRAY;
  const responsiveIconSize = iconSize || 24;

  return (
    <Ionicons 
      name={getIconName()} 
      size={responsiveIconSize} 
      color={iconColor} 
    />
  );
}

// Modern Android tab bar styles
const androidStyles = StyleSheet.create({
  container: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    zIndex: 10,
    justifyContent: 'flex-end',
  },
  tabBarCard: {
    backgroundColor: TAB_BAR_BG,
    paddingHorizontal: 20,
    paddingVertical: 2,
    paddingTop: 8,
    marginHorizontal: 0,
    borderTopRightRadius: 20,
    borderTopLeftRadius: 20,
    shadowColor: SHADOW_COLOR,
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 4,
  },
  tabRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-around',
    height: 48,
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 48,
    minWidth: 48,
  },
  iconContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    marginBottom: 4,
  },
  activeIndicator: {
    position: 'absolute',
    bottom: -8,
    width: 24,
    height: 3,
    backgroundColor: ACTIVE_ORANGE,
    borderRadius: 2,
  },
  tabLabel: {
    fontSize: 11,
    fontWeight: '500',
    textAlign: 'center',
    marginTop: 4,
  },
  activeLabelColor: {
    color: ACTIVE_ORANGE,
  },
  inactiveLabelColor: {
    color: INACTIVE_GRAY,
  },
});