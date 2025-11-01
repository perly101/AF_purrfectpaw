import React, { useEffect, useState } from 'react';
import {
  View,
  Platform,
  StyleSheet,
  Text,
  Dimensions,
  TouchableOpacity,
  Animated,
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

// expo-navigation-bar is required dynamically at runtime using `require` with `// @ts-ignore`.
// If you need TypeScript types for this package, add a separate declaration file (for example:
//   types/expo-navigation-bar.d.ts
// with `declare module 'expo-navigation-bar';` or proper type definitions there).

// Define our main colors
const PINK = '#FF9EB1';
const DARK = '#333333';

const Tab = createBottomTabNavigator();

export default function PersonalTabs() {
  const insets = useSafeAreaInsets();
  const [notificationCount, setNotificationCount] = useState(0);
  const screenHeight = Dimensions.get('window').height;
  
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
            await NavigationBar.setBackgroundColorAsync(DARK);
          }
        } catch (e) {
          console.log('expo-navigation-bar not available or failed to hide nav bar', e);
        }
      })();
    }
  }, []);
  */
  
  // Custom tab bar icon with badge (kept minimal - rendering handled in custom TabBar)
  const renderTabBarIcon = (routeName: string, focused: boolean) => {
    switch (routeName) {
      case 'Home':
        return <Ionicons name={focused ? 'home' : 'home-outline'} size={22} color={focused ? PINK : '#ccc'} />;
      case 'Appointments':
        return <Ionicons name={focused ? 'calendar' : 'calendar-outline'} size={22} color={focused ? '#fff' : '#ccc'} />;
      case 'Notifications':
        return (
          <View style={styles.iconContainer}>
            <Ionicons name={focused ? 'notifications' : 'notifications-outline'} size={22} color={focused ? PINK : '#ccc'} />
            {notificationCount > 0 && (
              <View style={styles.badge}>
                <Text style={styles.badgeText}>{notificationCount}</Text>
              </View>
            )}
          </View>
        );
      case 'Settings':
        return <Ionicons name={focused ? 'settings' : 'settings-outline'} size={22} color={focused ? PINK : '#ccc'} />;
      default:
        return <Ionicons name={'home-outline'} size={22} color={focused ? PINK : '#ccc'} />;
    }
  };

  // Calculate safe bottom margin for Android devices with hardware navigation buttons
  const getBottomMargin = () => {
    if (Platform.OS === 'ios') return insets.bottom;
    
    // For Android: add extra padding if device likely has hardware navigation buttons
    if (hasHardwareButtons) {
      return 25; // Extra space for hardware buttons
    } else {
      return 15; // Standard padding for Android devices with gesture navigation
    }
  };

  return (
    <Tab.Navigator
      // Use a custom tab bar to match the pill-shaped design with centered floating button
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
        name="Notifications" 
        component={NotificationsScreen}
        options={{
          tabBarLabel: 'Notifications',
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
    backgroundColor: `${PINK}20`,
    borderRadius: 15,
  },
});

// --- Custom Tab Bar and helpers ---
function CustomTabBar({ state, descriptors, navigation, insets }: any) {
  const SCREEN_WIDTH = Dimensions.get('window').width;
  // Make the pill full width (edge-to-edge) with no horizontal gaps
    const PILL_WIDTH = SCREEN_WIDTH;
    const PILL_LEFT = 0;
  const centerButtonSize = 64;
  const slotWidth = PILL_WIDTH / state.routes.length;
  const PILL_HEIGHT = 64;

  // animated value moves the circle relative to PILL_LEFT
  const initialOffset = slotWidth * state.index + slotWidth / 2 - centerButtonSize / 2;
  const translateX = React.useRef(new Animated.Value(initialOffset)).current;

  const activeRouteName = state.routes[state.index].name;

  // animate when index changes
  React.useEffect(() => {
    const toValue = slotWidth * state.index + slotWidth / 2 - centerButtonSize / 2;
    Animated.spring(translateX, {
      toValue,
      useNativeDriver: true,
      damping: 12,
      stiffness: 100,
      mass: 0.8,
    }).start();
  }, [state.index, slotWidth, translateX]);

  // Anchor the whole bar to the very bottom to avoid jumps when insets change on resume.
  // We'll respect system safe-area by adding extra bottom padding inside the pill instead
  const extraBottomPadding = insets?.bottom ?? 0;

  return (
    <View style={[customStyles.container, { bottom: 0 }]} pointerEvents="box-none">
      <View style={[customStyles.pillBackground, { paddingBottom: extraBottomPadding }]} />

      {/* Center floating button (Animated) - positioned relative to pill left */}
      <Animated.View
        style={[
          customStyles.centerButtonWrapper,
          { left: PILL_LEFT, transform: [{ translateX }], bottom: PILL_HEIGHT / 2 - Math.max(0, extraBottomPadding / 2) },
        ]}
        pointerEvents="box-none"
      >
        <TouchableOpacity
          activeOpacity={0.95}
          style={[customStyles.centerButton, state.index === 1 ? customStyles.centerButtonActive : {}]}
          onPress={() => {
            // navigate to the current center target (Appointments) when tapped
            const index = 1;
            navigation.navigate(state.routes[index].name);
          }}
        >
          {renderIconFor(activeRouteName, true, /* inCircle */ true)}
        </TouchableOpacity>
      </Animated.View>

      <View style={customStyles.tabRow}>
        {state.routes.map((route: any, index: number) => {
          const focused = state.index === index;
          const { options } = descriptors[route.key];

          return (
            <TouchableOpacity
              key={route.key}
              accessibilityRole="button"
              accessibilityState={focused ? { selected: true } : {}}
              onPress={() => navigation.navigate(route.name)}
              style={[customStyles.tabButton, focused && { opacity: 0 }]}
              activeOpacity={0.85}
            >
              <View style={customStyles.tabInner}>
                {renderIconFor(route.name, focused, /* inCircle */ false)}
                <Text style={[customStyles.tabLabel, focused && { color: PINK }]} numberOfLines={1}>
                  {options.tabBarLabel ?? route.name}
                </Text>
              </View>
            </TouchableOpacity>
          );
        })}
      </View>
    </View>
  );
}

function renderIconFor(routeName: string, focused: boolean, inCircle: boolean = false) {
  // inCircle: when true, icon is rendered inside the pink circle and should be white
  const circleColor = '#fff';
  const activeColor = inCircle ? circleColor : PINK;
  const inactiveColor = inCircle ? circleColor : '#666';

  switch (routeName) {
    case 'Home':
      return <Ionicons name={focused ? 'home' : 'home-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
    case 'Appointments':
      // center handled by floating button: show white icon inside circle or colored icon in tab
      return <Ionicons name={focused ? 'calendar' : 'calendar-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
    case 'Notifications':
      return (
        <View style={styles.iconContainer}>
          <Ionicons name={focused ? 'notifications' : 'notifications-outline'} size={22} color={focused ? activeColor : inactiveColor} />
        </View>
      );
    case 'Settings':
      return <Ionicons name={focused ? 'settings' : 'settings-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
    default:
      return <Ionicons name={'home-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
  }
}

const customStyles = StyleSheet.create({
  container: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    alignItems: 'center',
    zIndex: 10,
  },
  pillBackground: {
    width: '100%',
    height: 64,
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
    borderBottomLeftRadius: 0,
    borderBottomRightRadius: 0,
    borderTopWidth: 1,
    borderTopColor: '#EEE',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 6,
  },
  centerButtonWrapper: {
    position: 'absolute',
    bottom: 24,
    width: 68,
    height: 68,
    alignItems: 'center',
    justifyContent: 'center',
    zIndex: 20,
  },
  centerButton: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: PINK,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 12,
  },
  centerButtonActive: {
    transform: [{ scale: 1.02 }],
    borderWidth: 3,
    borderColor: '#fff',
  },
  tabRow: {
    position: 'absolute',
    bottom: 12,
    left: 0,
    right: 0,
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
    paddingHorizontal: 16,
    marginHorizontal: 0,
    zIndex: 15,
  },
  tabButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabInner: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  tabLabel: {
    color: '#222',
    fontSize: 11,
    marginTop: 4,
    fontWeight: '600',
  },
});