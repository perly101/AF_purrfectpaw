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

// Define colors for pill-shaped tab bar design
const ACTIVE_BLUE = '#0AA3FF';
const WHITE = '#FFFFFF';
const INACTIVE_GRAY = '#9AA0A6';
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
  useEffect(() => {
    // You would normally fetch this from your API
    setNotificationCount(2);
  }, []);

  // NOTE: Hiding the Android system navigation bar was previously implemented
  // but the user requested that the system navigation buttons remain visible.
  // The runtime calls to `expo-navigation-bar` have been removed to preserve
  // the platform navigation UI. If you want to re-enable hiding later,
  // uncomment and adjust the code below.

  /*
  useEffect(() => {
    if (Platform.OS === 'android') {
      (async () => {
        try {
          // @ts-ignore
          const NavigationBar = require('expo-navigation-bar');
          if (NavigationBar && NavigationBar.setVisibilityAsync) {
            await NavigationBar.setVisibilityAsync('hidden');
          }
          if (NavigationBar && NavigationBar.setBackgroundColorAsync) {
            await NavigationBar.setBackgroundColorAsync('#2D3748');
          }
        } catch (e) {
          console.log('expo-navigation-bar not available or failed to hide nav bar', e);
        }
      })();
    }
  }, []);
  */
  
  // Note: Icon rendering is now handled by the renderIconFor function in CustomTabBar

  // Note: Bottom margin is now handled directly in the gradient tab bar

  return (
    <Tab.Navigator
      // Use a custom tab bar with Home in center position (3 tabs total)
      tabBar={(props) => <CustomTabBar {...props} insets={insets} />}
      screenOptions={{ headerShown: false }}
    >
      <Tab.Screen 
        name="Notifications" 
        component={NotificationsScreen}
        options={{
          tabBarLabel: 'Notifications',
        }}
      />
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

// --- Pill-shaped Tab Bar with Elevated Active Circle ---
function CustomTabBar({ state, descriptors, navigation, insets }: any) {
  // Get responsive sizes for this component
  const responsiveSizes = getResponsiveTabBarSizes();
  
  // Animation values for active tab circle
  const activeTabScale = useRef(new Animated.Value(1)).current;
  
  // Handle tab press with micro-interaction
  const handleTabPress = (routeName: string, index: number) => {
    triggerHapticFeedback();
    
    // 120ms spring animation for active tab circle
    Animated.sequence([
      Animated.timing(activeTabScale, {
        toValue: 1.06,
        duration: 60,
        useNativeDriver: true,
        easing: Easing.out(Easing.cubic),
      }),
      Animated.spring(activeTabScale, {
        toValue: 1,
        useNativeDriver: true,
        damping: 12,
        stiffness: 300,
        restSpeedThreshold: 0.01,
      }),
    ]).start();

    navigation.navigate(routeName);
  };

  const extraBottomPadding = insets?.bottom ?? 0;

  return (
    <View style={[
      pillStyles.container, 
      { 
        paddingBottom: extraBottomPadding, // Use full bottom padding
        paddingHorizontal: 0, // Remove horizontal padding so it extends to edges
        paddingTop: 0
      }
    ]}>
      {/* Tab bar card that extends to bottom and sides */}
      <View style={[
        pillStyles.pillCard,
        {
          borderTopLeftRadius: responsiveSizes.pillCardBorderRadius,
          borderTopRightRadius: responsiveSizes.pillCardBorderRadius,
          borderBottomLeftRadius: 0,
          borderBottomRightRadius: 0,
          shadowRadius: responsiveSizes.shadowRadius,
          shadowOffset: responsiveSizes.shadowOffset,
          elevation: getResponsiveElevation(8),
          flex: 1,
          paddingBottom: extraBottomPadding + 8 // Add extra bottom padding
        }
      ]}>
        <View style={[pillStyles.tabRow, { height: 60 }]}>
          {state.routes.map((route: any, index: number) => {
            const focused = state.index === index;
            const { options } = descriptors[route.key];
            const label = options.tabBarLabel || route.name;

            return (
              <TouchableOpacity
                key={route.key}
                style={[
                  pillStyles.tabItem,
                  {
                    minHeight: responsiveSizes.minTouchTarget,
                    minWidth: responsiveSizes.minTouchTarget,
                    paddingBottom: responsiveSizes.tabItemPaddingBottom
                  }
                ]}
                onPress={() => handleTabPress(route.name, index)}
                activeOpacity={0.7}
                accessibilityLabel={label}
                accessibilityRole="tab"
                accessibilityState={{ selected: focused }}
              >
                {/* Active tab: elevated blue circle */}
                {focused && (
                  <Animated.View 
                    style={[
                      pillStyles.activeCircle,
                      { 
                        width: responsiveSizes.activeCircleSize,
                        height: responsiveSizes.activeCircleSize,
                        borderRadius: responsiveSizes.activeCircleBorderRadius,
                        marginBottom: -Math.round(responsiveSizes.activeCircleSize * 0.05), // Minimal overlap
                        marginTop: 4, // Add top margin to push it down
                        shadowRadius: responsiveSizes.shadowRadius,
                        shadowOffset: responsiveSizes.shadowOffset,
                        elevation: getResponsiveElevation(12),
                        transform: [{ scale: activeTabScale }]
                      }
                    ]}
                  >
                    {renderIconFor(route.name, focused, responsiveSizes.iconSize)}
                  </Animated.View>
                )}
                
                {/* Inactive tab: simple icon */}
                {!focused && (
                  <View style={[
                    pillStyles.inactiveIcon,
                    {
                      width: responsiveSizes.inactiveIconSize,
                      height: responsiveSizes.inactiveIconSize,
                      marginBottom: responsiveSizes.inactiveIconMarginBottom
                    }
                  ]}>
                    {renderIconFor(route.name, focused, responsiveSizes.inactiveIconSize)}
                  </View>
                )}
                
                {/* Tab label */}
                <Text 
                  style={[
                    pillStyles.tabLabel,
                    {
                      fontSize: responsiveSizes.labelFontSize,
                      marginTop: responsiveSizes.labelMarginTop
                    },
                    focused ? pillStyles.activeLabelColor : pillStyles.inactiveLabelColor
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
        return focused ? 'home' : 'home-outline';
      case 'Appointments':
        return focused ? 'calendar' : 'calendar-outline';
      case 'Notifications':
        return focused ? 'notifications' : 'notifications-outline';
      case 'Settings':
        return focused ? 'settings' : 'settings-outline';
      default:
        return 'home-outline';
    }
  };

  const iconColor = focused ? WHITE : INACTIVE_GRAY;
  const responsiveIconSize = iconSize || getResponsiveTabBarSizes().iconSize;

  return (
    <Ionicons 
      name={getIconName()} 
      size={responsiveIconSize} 
      color={iconColor} 
    />
  );
}

// Pill-shaped tab bar styles (responsive values are applied dynamically)
const pillStyles = StyleSheet.create({
  container: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    zIndex: 10,
    height: 95, // Increased height for better fit
    justifyContent: 'flex-end',
    // Responsive values applied dynamically: paddingHorizontal, paddingTop
  },
  pillCard: {
    backgroundColor: TAB_BAR_BG,
    paddingHorizontal: 0, // Remove horizontal padding so it extends to edges
    paddingVertical: 12,
    shadowColor: SHADOW_COLOR,
    shadowOpacity: 0.1,
    marginHorizontal: 0,
    borderBottomLeftRadius: 0, // Remove bottom radius so it fits to bottom
    borderBottomRightRadius: 0,
    // Responsive values applied dynamically: borderRadius for top only, shadowRadius, shadowOffset, elevation
  },
  tabRow: {
    flexDirection: 'row',
    alignItems: 'center', // Changed from flex-end to center
    justifyContent: 'space-around',
    // height will be set dynamically
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'flex-end',
    // Responsive values applied dynamically: minHeight, minWidth, paddingBottom
  },
  activeCircle: {
    backgroundColor: ACTIVE_BLUE,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: SHADOW_COLOR,
    shadowOpacity: 0.2,
    // Responsive values applied dynamically: width, height, borderRadius, marginBottom, shadowRadius, shadowOffset, elevation
  },
  inactiveIcon: {
    alignItems: 'center',
    justifyContent: 'center',
    // Responsive values applied dynamically: width, height, marginBottom
  },
  tabLabel: {
    fontWeight: '500',
    textAlign: 'center',
    // Responsive values applied dynamically: fontSize, marginTop
  },
  activeLabelColor: {
    color: ACTIVE_BLUE,
  },
  inactiveLabelColor: {
    color: INACTIVE_GRAY,
  },
});