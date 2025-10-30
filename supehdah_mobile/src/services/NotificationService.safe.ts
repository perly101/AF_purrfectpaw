import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API } from '../api';

// Define notification data type
interface NotificationData {
  type?: string;
  appointment_id?: string | number;
  message?: string;
  [key: string]: any;
}

// Define listener type
type NotificationSubscription = {
  remove: () => void;
};

// Declare global navigation
declare global {
  var navigationRef: any;
}

// Check if running in Expo Go
const isExpoGo = (): boolean => {
  try {
    // @ts-ignore
    return !!global.__expo?.AppLoader;
  } catch {
    return false;
  }
};

/**
 * Safe notification service that disables expo-notifications in Expo Go
 * but keeps all other functionality intact
 */
export class NotificationService {
  // Static property to store listeners
  static listeners: {
    foregroundSubscription?: NotificationSubscription;
    responseSubscription?: NotificationSubscription;
  } | null = null;

  /**
   * Initialize notifications and set up event listeners
   * Safe for Expo Go - will skip notification setup but keep other functionality
   * @returns Promise<void>
   */
  static async initialize(): Promise<void> {
    try {
      console.log('🔔 NotificationService: Initializing...');
      
      if (isExpoGo()) {
        console.log('🚫 NotificationService: Expo Go detected - skipping notification setup');
        return;
      }

      // Only import and setup notifications if NOT in Expo Go
      const Notifications = await import('expo-notifications');
      
      // Configure notification behavior
      Notifications.setNotificationHandler({
        handleNotification: async () => ({
          shouldShowAlert: true,
          shouldPlaySound: true,
          shouldSetBadge: true,
          shouldShowBanner: true,
          shouldShowList: true,
        }),
      });

      // Request permissions
      await this.requestPermissions();
      
      // Set up listeners
      this.setupNotificationListeners();
      
      console.log('✅ NotificationService: Initialized successfully');
    } catch (error) {
      console.log('🚫 NotificationService: Skipped due to error (likely Expo Go):', error);
    }
  }

  /**
   * Request notification permissions
   * Safe for Expo Go - will skip if notifications not available
   */
  static async requestPermissions(): Promise<boolean> {
    try {
      if (isExpoGo()) {
        console.log('🚫 NotificationService: Skipping permissions in Expo Go');
        return false;
      }

      const Notifications = await import('expo-notifications');
      const { status: existingStatus } = await Notifications.getPermissionsAsync();
      
      let finalStatus = existingStatus;
      
      if (existingStatus !== 'granted') {
        const { status } = await Notifications.requestPermissionsAsync();
        finalStatus = status;
      }
      
      if (finalStatus !== 'granted') {
        console.log('🚫 NotificationService: Permission not granted');
        return false;
      }
      
      console.log('✅ NotificationService: Permissions granted');
      return true;
    } catch (error) {
      console.log('🚫 NotificationService: Permission request failed:', error);
      return false;
    }
  }

  /**
   * Set up notification listeners
   * Safe for Expo Go - will skip if notifications not available
   */
  static async setupNotificationListeners(): Promise<void> {
    try {
      if (isExpoGo()) {
        console.log('🚫 NotificationService: Skipping listeners in Expo Go');
        return;
      }

      const Notifications = await import('expo-notifications');
      
      // Clear existing listeners
      this.cleanup();

      // Listen for notifications received while the app is foregrounded
      const foregroundSubscription = Notifications.addNotificationReceivedListener((notification) => {
        console.log('🔔 Foreground notification received:', notification);
        this.handleForegroundNotification(notification.request.content.data as NotificationData);
      });

      // Listen for user interactions with notifications
      const responseSubscription = Notifications.addNotificationResponseReceivedListener((response) => {
        console.log('🔔 Notification response received:', response);
        this.handleNotificationResponse(response.notification.request.content.data as NotificationData);
      });

      // Store listeners for cleanup
      this.listeners = {
        foregroundSubscription,
        responseSubscription,
      };

      console.log('✅ NotificationService: Listeners set up');
    } catch (error) {
      console.log('🚫 NotificationService: Listener setup failed:', error);
    }
  }

  /**
   * Handle notification received in foreground
   */
  static handleForegroundNotification(data: NotificationData): void {
    try {
      console.log('🔔 Handling foreground notification:', data);
      // Handle the notification data here
      // You can show in-app alerts or update UI
    } catch (error) {
      console.log('❌ Error handling foreground notification:', error);
    }
  }

  /**
   * Handle notification tap/response
   */
  static handleNotificationResponse(data: NotificationData): void {
    try {
      console.log('🔔 Handling notification response:', data);
      
      // Navigate based on notification type
      if (data.type === 'appointment' && data.appointment_id) {
        this.navigateToAppointment(data.appointment_id);
      }
    } catch (error) {
      console.log('❌ Error handling notification response:', error);
    }
  }

  /**
   * Navigate to appointment details
   */
  static navigateToAppointment(appointmentId: string | number): void {
    try {
      if (global.navigationRef) {
        // Navigate to appointment details
        global.navigationRef.navigate('AppointmentDetails', { id: appointmentId });
      }
    } catch (error) {
      console.log('❌ Navigation error:', error);
    }
  }

  /**
   * Register device for push notifications
   * Safe for Expo Go - will skip registration
   */
  static async registerForPushNotifications(): Promise<string | null> {
    try {
      if (isExpoGo()) {
        console.log('🚫 NotificationService: Skipping push registration in Expo Go');
        return null;
      }

      const Notifications = await import('expo-notifications');
      
      // Check if permissions are granted
      const hasPermissions = await this.requestPermissions();
      if (!hasPermissions) {
        console.log('🚫 Push notifications: No permissions');
        return null;
      }

      // Get push token
      const Constants = await import('expo-constants');
      const token = await Notifications.getExpoPushTokenAsync({
        projectId: Constants.default.expoConfig?.extra?.eas?.projectId,
      });

      console.log('📱 Push token obtained:', token.data);
      
      // Send token to your server
      await this.sendTokenToServer(token.data);
      
      return token.data;
    } catch (error) {
      console.log('❌ Error registering for push notifications:', error);
      return null;
    }
  }

  /**
   * Send push token to server
   */
  static async sendTokenToServer(token: string): Promise<void> {
    try {
      await API.post('/user/push-token', { token });
      console.log('✅ Push token sent to server');
    } catch (error) {
      console.log('❌ Error sending push token to server:', error);
    }
  }

  /**
   * Schedule a local notification
   * Safe for Expo Go - will skip scheduling
   */
  static async scheduleLocalNotification(
    title: string,
    body: string,
    data: NotificationData = {},
    trigger?: any
  ): Promise<string | null> {
    try {
      if (isExpoGo()) {
        console.log('🚫 NotificationService: Skipping local notification in Expo Go');
        return null;
      }

      const Notifications = await import('expo-notifications');
      
      const identifier = await Notifications.scheduleNotificationAsync({
        content: {
          title,
          body,
          data,
        },
        trigger: trigger || null, // null means immediate
      });

      console.log('✅ Local notification scheduled:', identifier);
      return identifier;
    } catch (error) {
      console.log('❌ Error scheduling local notification:', error);
      return null;
    }
  }

  /**
   * Cancel a scheduled notification
   * Safe for Expo Go - will skip cancellation
   */
  static async cancelNotification(identifier: string): Promise<void> {
    try {
      if (isExpoGo()) {
        console.log('🚫 NotificationService: Skipping notification cancel in Expo Go');
        return;
      }

      const Notifications = await import('expo-notifications');
      await Notifications.cancelScheduledNotificationAsync(identifier);
      console.log('✅ Notification cancelled:', identifier);
    } catch (error) {
      console.log('❌ Error cancelling notification:', error);
    }
  }

  /**
   * Get badge count
   * Safe for Expo Go - will return 0
   */
  static async getBadgeCount(): Promise<number> {
    try {
      if (isExpoGo()) {
        return 0;
      }

      const Notifications = await import('expo-notifications');
      return await Notifications.getBadgeCountAsync();
    } catch (error) {
      console.log('❌ Error getting badge count:', error);
      return 0;
    }
  }

  /**
   * Set badge count
   * Safe for Expo Go - will skip setting
   */
  static async setBadgeCount(count: number): Promise<void> {
    try {
      if (isExpoGo()) {
        console.log('🚫 NotificationService: Skipping badge count in Expo Go');
        return;
      }

      const Notifications = await import('expo-notifications');
      await Notifications.setBadgeCountAsync(count);
      console.log('✅ Badge count set:', count);
    } catch (error) {
      console.log('❌ Error setting badge count:', error);
    }
  }

  /**
   * Clean up listeners
   */
  static cleanup(): void {
    try {
      if (this.listeners) {
        this.listeners.foregroundSubscription?.remove();
        this.listeners.responseSubscription?.remove();
        this.listeners = null;
        console.log('✅ NotificationService: Listeners cleaned up');
      }
    } catch (error) {
      console.log('❌ Error cleaning up listeners:', error);
    }
  }
}