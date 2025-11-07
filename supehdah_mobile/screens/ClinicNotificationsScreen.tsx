import React, { useState, useEffect, useCallback } from 'react';
import { 
  View, 
  Text, 
  StyleSheet, 
  FlatList, 
  TouchableOpacity, 
  RefreshControl,
  Alert
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useFocusEffect } from '@react-navigation/native';
import { format } from 'date-fns';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API } from '../src/api';

// Define Notification interface
interface Notification {
  id: number;
  type: string;
  title: string;
  body: string;
  read_at: string | null;
  created_at: string;
  updated_at: string;
  notifiable_type: string;
  notifiable_id: number;
  data?: any;
}

const ClinicNotificationsScreen: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [refreshing, setRefreshing] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const token = await AsyncStorage.getItem('userToken');
      if (!token) {
        console.log('No token found, user not logged in');
        setNotifications([]);
        return;
      }
      
      const response = await API.get('/clinic/notifications');
      setNotifications(response.data.notifications || []);
    } catch (err: any) {
      // Only show error for actual failures, not auth issues
      if (err?.response?.status !== 401) {
        console.error('Failed to fetch notifications:', err);
        setError('Failed to load notifications');
      } else {
        setNotifications([]);
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const markAsRead = async (id: number) => {
    try {
      await API.post(`/clinic/notifications/${id}/mark-read`);
      
      setNotifications(currentNotifications => 
        currentNotifications.map(notification => 
          notification.id === id ? { ...notification, read_at: new Date().toISOString() } : notification
        )
      );
    } catch (err) {
      console.error('Failed to mark notification as read:', err);
      Alert.alert('Error', 'Failed to mark notification as read');
    }
  };

  const deleteNotification = async (id: number) => {
    try {
      await API.delete(`/clinic/notifications/${id}`);
      
      setNotifications(currentNotifications => 
        currentNotifications.filter(notification => notification.id !== id)
      );
    } catch (err) {
      console.error('Failed to delete notification:', err);
      Alert.alert('Error', 'Failed to delete notification');
    }
  };

  const handleDelete = (id: number) => {
    Alert.alert(
      'Delete Notification',
      'Are you sure you want to delete this notification?',
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Delete', onPress: () => deleteNotification(id), style: 'destructive' }
      ]
    );
  };

  const onRefresh = () => {
    setRefreshing(true);
    fetchNotifications();
  };

  // Fetch notifications when the screen comes into focus
  useFocusEffect(
    useCallback(() => {
      fetchNotifications();
    }, [])
  );

  // Initial fetch
  useEffect(() => {
    fetchNotifications();
  }, []);

  const renderNotificationItem = ({ item }: { item: Notification }) => {
    const formattedDate = item.created_at ? 
      format(new Date(item.created_at), 'MMM d, yyyy h:mm a') : 
      'Unknown date';

    return (
      <TouchableOpacity 
        style={[styles.notificationItem, !item.read_at && styles.unreadItem]}
        onPress={() => markAsRead(item.id)}
      >
        <View style={styles.notificationHeader}>
          <View style={styles.iconContainer}>
            {getNotificationIcon(item.type)}
          </View>
          <View style={styles.notificationContent}>
            <Text style={styles.notificationTitle}>{item.title}</Text>
            <Text style={styles.notificationBody}>{item.body}</Text>
            <Text style={styles.notificationTime}>{formattedDate}</Text>
          </View>
          <TouchableOpacity 
            style={styles.deleteButton} 
            onPress={() => handleDelete(item.id)}
          >
            <Ionicons name="trash-outline" size={18} color="#FF6B6B" />
          </TouchableOpacity>
        </View>
      </TouchableOpacity>
    );
  };

  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'clinic_new_appointment':
        return <Ionicons name="calendar-outline" size={24} color="#4A6FA5" />;
      case 'clinic_appointment_completed':
        return <Ionicons name="checkmark-circle-outline" size={24} color="#4CAF50" />;
      default:
        return <Ionicons name="notifications-outline" size={24} color="#FFC1CC" />;
    }
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Notifications</Text>
      </View>
      
      {loading && notifications.length === 0 ? (
        <View style={styles.centerMessage}>
          <Text>Loading notifications...</Text>
        </View>
      ) : error ? (
        <View style={styles.centerMessage}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity style={styles.retryButton} onPress={fetchNotifications}>
            <Text style={styles.retryButtonText}>Retry</Text>
          </TouchableOpacity>
        </View>
      ) : notifications.length === 0 ? (
        <View style={styles.centerMessage}>
          <Ionicons name="notifications-off-outline" size={64} color="#ccc" />
          <Text style={styles.noNotificationsText}>No notifications yet</Text>
        </View>
      ) : (
        <FlatList
          data={notifications}
          renderItem={renderNotificationItem}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContainer}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F5F5F5',
  },
  header: {
    backgroundColor: '#FFC1CC',
    paddingTop: 60,
    paddingBottom: 15,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#E0E0E0',
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#333',
  },
  listContainer: {
    paddingVertical: 10,
    paddingHorizontal: 15,
  },
  notificationItem: {
    backgroundColor: 'white',
    borderRadius: 10,
    padding: 15,
    marginBottom: 10,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.1,
    shadowRadius: 2,
  },
  unreadItem: {
    borderLeftWidth: 5,
    borderLeftColor: '#FFC1CC',
    backgroundColor: '#FAFAFA',
  },
  notificationHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  iconContainer: {
    marginRight: 10,
    backgroundColor: '#F0F0F0',
    borderRadius: 20,
    padding: 8,
  },
  notificationContent: {
    flex: 1,
  },
  notificationTitle: {
    fontWeight: 'bold',
    fontSize: 16,
    marginBottom: 3,
    color: '#333',
  },
  notificationBody: {
    fontSize: 14,
    color: '#666',
    marginBottom: 5,
  },
  notificationTime: {
    fontSize: 12,
    color: '#999',
  },
  deleteButton: {
    padding: 10,
  },
  centerMessage: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  noNotificationsText: {
    fontSize: 16,
    color: '#999',
    marginTop: 10,
  },
  errorText: {
    color: '#FF6B6B',
    marginBottom: 15,
  },
  retryButton: {
    backgroundColor: '#FFC1CC',
    paddingVertical: 8,
    paddingHorizontal: 15,
    borderRadius: 5,
  },
  retryButtonText: {
    color: '#333',
    fontWeight: 'bold',
  },
});

export default ClinicNotificationsScreen;