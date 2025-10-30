import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API } from '../api';
import Constants from 'expo-constants';

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

/**
 * Configure notification behavior
 */
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
    shouldShowBanner: true,
    shouldShowList: true,
  }),
});

/**
 * Notification service to handle all notification-related functionality
 */
export class NotificationService {
  // Static property to store listeners
  static listeners: {
    foregroundSubscription?: NotificationSubscription;
    responseSubscription?: NotificationSubscription;
  } | null = null;
  /**
   * Initialize notifications and set up event listeners
   * @returns Promise<void>
   */
  static async initialize() {
    try {
      // Check permissions first
      const permissionResult = await this.requestPermissions();
      if (!permissionResult.granted) {
        console.log('Notification permission not granted');
        return;
      }

      // Get push token
      const token = await this.getNotificationToken();
      if (token) {
        console.log('Push token:', token);
        await this.savePushToken(token);
      }

      // Set notification event listeners
      this.setNotificationListeners();

    } catch (error) {
      console.error('Error initializing notifications:', error);
    }
  }

  /**
   * Request notification permissions
   * @returns Promise<Notifications.NotificationPermissionsStatus>
   */
  static async requestPermissions() {
    const { status: existingStatus } = await Notifications.getPermissionsAsync();
    let finalStatus = existingStatus;

    // Only ask if permissions have not already been determined
    if (existingStatus !== 'granted') {
      const { status } = await Notifications.requestPermissionsAsync();
      finalStatus = status;
    }

    // Return the permissions status
    return { granted: finalStatus === 'granted' };
  }

  /**
   * Get Expo push notification token
   * @returns Promise<string | undefined>
   */
  static async getNotificationToken() {
    // Check if the app is running in Expo environment
    if (!Constants.expoConfig) {
      console.log('Not running in Expo environment, skipping push token');
      return undefined;
    }

    // Get push token
    try {
      const { data: token } = await Notifications.getExpoPushTokenAsync({
        projectId: Constants.expoConfig.extra?.eas?.projectId,
      });
      return token;
    } catch (error) {
      console.error('Error getting push token:', error);
      return undefined;
    }
  }

  /**
   * Save push token to server
   * @param token Push token to save
   */
  static async savePushToken(token: string) {
    try {
      // Save to local storage first
      await AsyncStorage.setItem('pushToken', token);

      // Only send to server if user is authenticated
      const authToken = await AsyncStorage.getItem('token');
      if (authToken) {
        await API.post('/device-token', { device_token: token });
        console.log('Push token saved to server');
      } else {
        console.log('User not authenticated, token saved locally only');
      }
    } catch (error) {
      console.error('Error saving push token:', error);
    }
  }

  /**
   * Set up notification listeners
   */
  static setNotificationListeners() {
    // When a notification is received while the app is in the foreground
    const foregroundSubscription = Notifications.addNotificationReceivedListener(notification => {
      console.log('Notification received in foreground:', notification);
      // You can show a custom in-app notification here if needed
    });

    // When a user taps on a notification (app in background or closed)
    const responseSubscription = Notifications.addNotificationResponseReceivedListener(response => {
      console.log('Notification response received:', response);
      const { notification } = response;
      const data = notification.request.content.data as NotificationData;
      
      // Handle notification tap based on the notification type
      this.handleNotificationTap(data);
    });

    // Store the listeners so they can be cleaned up if needed
    this.listeners = {
      foregroundSubscription,
      responseSubscription,
    };
  }

  /**
   * Handle notification tap based on notification type
   * @param data Notification data
   */
  static handleNotificationTap(data: NotificationData) {
    // Extract navigation reference from global API
    const navigationRef = global.navigationRef;
    if (!navigationRef) {
      console.log('Navigation ref not available, cannot navigate');
      return;
    }

    try {
      // Handle different notification types
      const notificationType = data.type;
      const appointmentId = data.appointment_id;
      
      switch (notificationType) {
        case 'doctor_assigned_patient':
          navigationRef.navigate('DoctorPatients');
          break;
          
        case 'clinic_new_appointment':
          if (appointmentId) {
            navigationRef.navigate('ClinicAppointmentDetails', { id: appointmentId });
          } else {
            navigationRef.navigate('ClinicAppointments');
          }
          break;
          
        case 'clinic_appointment_completed':
          if (appointmentId) {
            navigationRef.navigate('ClinicAppointmentDetails', { id: appointmentId });
          } else {
            navigationRef.navigate('ClinicAppointments');
          }
          break;
          
        default:
          // For unknown notification types, navigate to notifications screen
          navigationRef.navigate('Notifications');
      }
    } catch (error) {
      console.error('Error handling notification tap:', error);
    }
  }

  /**
   * Get all notifications from the server
   * @returns Promise<Array<Notification>>
   */
  static async fetchNotifications() {
    try {
      const response = await API.get('/notifications');
      return response.data.data;
    } catch (error) {
      console.error('Error fetching notifications:', error);
      return [];
    }
  }

  /**
   * Mark a notification as read
   * @param id Notification ID
   */
  static async markAsRead(id: number | string) {
    try {
      await API.post(`/notifications/${id}/read`);
      return true;
    } catch (error) {
      console.error('Error marking notification as read:', error);
      return false;
    }
  }

  /**
   * Mark all notifications as read
   */
  static async markAllAsRead() {
    try {
      await API.post('/notifications/read-all');
      return true;
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
      return false;
    }
  }

  /**
   * Delete a notification
   * @param id Notification ID
   */
  static async deleteNotification(id: number | string) {
    try {
      await API.delete(`/notifications/${id}`);
      return true;
    } catch (error) {
      console.error('Error deleting notification:', error);
      return false;
    }
  }

  /**
   * Get unread notification count
   * @returns Promise<number>
   */
  static async getUnreadCount() {
    try {
      const response = await API.get('/notifications/unread-count');
      return response.data.count;
    } catch (error) {
      console.error('Error getting unread notification count:', error);
      return 0;
    }
  }

  /**
   * Clean up notification listeners
   */
  static cleanUp() {
    if (this.listeners?.foregroundSubscription) {
      this.listeners.foregroundSubscription.remove();
    }
    
    if (this.listeners?.responseSubscription) {
      this.listeners.responseSubscription.remove();
    }
    
    this.listeners = null;
  }
}