import React from 'react';
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
      // Use the same custom pill-shaped tab bar as PersonalTabs
      tabBar={(props) => <CustomTabBar {...props} insets={insets} />}
      screenOptions={{ headerShown: false }}
    >
      <Tab.Screen name="ClinicHome" component={ClinicHomeScreen} options={{ tabBarLabel: 'Home' }} />
      <Tab.Screen name="ClinicAppointments" component={ClinicAppointmentsScreen} options={{ tabBarLabel: 'Appointments' }} />
      <Tab.Screen name="ClinicCalendar" component={ClinicCalendarScreen} options={{ tabBarLabel: 'Availability' }} />
      <Tab.Screen name="ClinicGallery" component={ClinicGalleryScreen} options={{ tabBarLabel: 'Gallery' }} />
      {/* <Tab.Screen name="ClinicNotifications" component={ClinicNotificationsScreen} options={{ tabBarLabel: 'Notifications' }} /> */}
      <Tab.Screen name="ClinicSettings" component={ClinicSettingsScreen} options={{ tabBarLabel: 'Settings' }} />
    </Tab.Navigator>
  );
}

// --- Custom Tab Bar and helpers (adapted from PersonalTabs) ---
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

function CustomTabBar({ state, descriptors, navigation, insets }: any) {
  const SCREEN_WIDTH = Dimensions.get('window').width;
  const PILL_WIDTH = SCREEN_WIDTH;
  const PILL_LEFT = 0;
  const centerButtonSize = 64;
  const slotWidth = PILL_WIDTH / state.routes.length;
  const PILL_HEIGHT = 64;

  // animated value moves the circle relative to PILL_LEFT
  const initialOffset = slotWidth * state.index + slotWidth / 2 - centerButtonSize / 2;
  const translateX = React.useRef(new Animated.Value(initialOffset)).current;

  const activeRouteName = state.routes[state.index].name;

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

  const extraBottomPadding = insets?.bottom ?? 0;

  return (
    <View style={[customStyles.container, { bottom: 0 }]} pointerEvents="box-none">
      <View style={[customStyles.pillBackground, { paddingBottom: extraBottomPadding }]} />

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
            const index = 1; // ClinicAppointments is the center
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
  const circleColor = '#fff';
  const activeColor = inCircle ? circleColor : PINK;
  const inactiveColor = inCircle ? circleColor : '#666';

  switch (routeName) {
    case 'ClinicHome':
      return <Ionicons name={focused ? 'home' : 'home-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
    case 'ClinicAppointments':
      return <Ionicons name={focused ? 'calendar' : 'calendar-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
    case 'ClinicCalendar':
      return <Ionicons name={focused ? 'time' : 'time-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
    case 'ClinicGallery':
      return <Ionicons name={focused ? 'images' : 'images-outline'} size={22} color={focused ? activeColor : inactiveColor} />;
    case 'ClinicNotifications':
      return (
        <View style={styles.iconContainer}>
          <Ionicons name={focused ? 'notifications' : 'notifications-outline'} size={22} color={focused ? activeColor : inactiveColor} />
        </View>
      );
    case 'ClinicSettings':
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