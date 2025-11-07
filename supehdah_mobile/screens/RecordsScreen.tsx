import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  SafeAreaView,
  FlatList,
  RefreshControl,
  ActivityIndicator,
  TouchableOpacity,
  ScrollView,
} from 'react-native';
import { MaterialCommunityIcons, Ionicons } from '@expo/vector-icons';
import { API } from '../src/api';
import { ROUTES } from '../src/routes';
import { useNavigation } from '@react-navigation/native';
import AsyncStorage from '@react-native-async-storage/async-storage';

// Modern color palette
const COLORS = {
  primary: '#6C5CE7',
  primaryLight: '#A29BFE',
  secondary: '#FF9EB1',
  background: '#F8FAFC',
  surface: '#FFFFFF',
  surfaceElevated: '#FEFEFE',
  text: '#1A1D29',
  textSecondary: '#64748B',
  textMuted: '#94A3B8',
  border: '#E2E8F0',
  borderLight: '#F1F5F9',
  success: '#10B981',
  successBg: '#ECFDF5',
  warning: '#F59E0B',
  warningBg: '#FFFBEB',
  error: '#EF4444',
  errorBg: '#FEF2F2',
  info: '#3B82F6',
  infoBg: '#EFF6FF',
};

export default function RecordsScreen() {
  const navigation = useNavigation<any>();
  const [loading, setLoading] = React.useState(false);
  const [refreshing, setRefreshing] = React.useState(false);
  const [appointments, setAppointments] = React.useState<any[]>([]);

  const fetchAppointments = React.useCallback(async () => {
    setLoading(true);
    try {
      // Check if user is authenticated
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
                   
      if (!token) {
        console.log('👤 No authentication token found, cannot fetch appointments');
        setAppointments([]);
        return;
      }
      
      console.log('🔍 Fetching user appointments from:', ROUTES.PROFILE.APPOINTMENTS.LIST);
      console.log('🎫 Using authentication token for request');
      
      const response = await API.get(ROUTES.PROFILE.APPOINTMENTS.LIST);
      console.log('📋 Raw appointments response:', response.data);
      
      const payload = response.data?.appointments ?? response.data?.data ?? response.data;
      const list = Array.isArray(payload) ? payload : (payload.appointments ?? []);
      
      console.log('📊 Processed appointments list:', {
        totalCount: list.length,
        appointments: list.map((apt: any) => ({
          id: apt.id,
          owner_name: apt.owner_name,
          appointment_date: apt.appointment_date,
          status: apt.status
        }))
      });
      
      setAppointments(list || []);
    } catch (err: any) {
      console.error('❌ Failed to fetch user records:', err);
      console.error('Error details:', err.response?.data || err.message);
      setAppointments([]);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  React.useEffect(() => {
    fetchAppointments();
  }, [fetchAppointments]);

  const onRefresh = React.useCallback(() => {
    setRefreshing(true);
    fetchAppointments();
  }, [fetchAppointments]);

  const renderItem = ({ item, index }: { item: any; index: number }) => (
    <TouchableOpacity 
      style={[styles.appointmentCard, { marginTop: index === 0 ? 0 : 16 }]} 
      onPress={() => navigation.navigate('AppointmentDetails', { id: item.id })} 
      activeOpacity={0.95}
    >
      <View style={styles.cardHeader}>
        <View style={styles.serviceInfo}>
          <View style={[styles.serviceIcon, { backgroundColor: getStatusColor(item.status, 'light') }]}>
            <MaterialCommunityIcons 
              name={getServiceIcon(item.service ?? item.type)} 
              size={20} 
              color={getStatusColor(item.status)} 
            />
          </View>
          <View style={styles.serviceTitleContainer}>
            <Text style={styles.serviceTitle}>{item.service ?? item.type ?? 'Appointment'}</Text>
            <Text style={styles.appointmentId}>#{item.id}</Text>
          </View>
        </View>
        <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status, 'background') }]}>
          <View style={[styles.statusDot, { backgroundColor: getStatusColor(item.status) }]} />
          <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
            {(item.status ?? 'pending').charAt(0).toUpperCase() + (item.status ?? 'pending').slice(1)}
          </Text>
        </View>
      </View>

      <View style={styles.appointmentDetails}>
        <View style={styles.detailRow}>
          <MaterialCommunityIcons name="paw" size={16} color={COLORS.textMuted} />
          <Text style={styles.detailText}>{item.pet_name ?? item.patient_name ?? 'Pet name not specified'}</Text>
        </View>
        
        <View style={styles.detailRow}>
          <MaterialCommunityIcons name="calendar-clock" size={16} color={COLORS.textMuted} />
          <Text style={styles.detailText}>{formatDateTime(item.appointment_date, item.appointment_time)}</Text>
        </View>
        
        <View style={styles.detailRow}>
          <MaterialCommunityIcons name="hospital-building" size={16} color={COLORS.textMuted} />
          <Text style={styles.detailText}>
            {item.clinic_name ?? item.clinic ?? 'Clinic not specified'}
            {item.doctor?.name ? ` • Dr. ${item.doctor.name}` : ''}
          </Text>
        </View>
      </View>

      <View style={styles.cardFooter}>
        <Text style={styles.viewDetailsText}>Tap to view details</Text>
        <Ionicons name="chevron-forward" size={16} color={COLORS.textMuted} />
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      {/* Enhanced Header */}
      <View style={styles.headerContainer}>
        <View style={styles.headerContent}>
          <View style={styles.headerTitleRow}>
            <MaterialCommunityIcons name="file-document-multiple" size={28} color={COLORS.primary} />
            <Text style={styles.headerTitle}>Medical Records</Text>
          </View>
          <Text style={styles.headerSubtitle}>
            {appointments.length > 0 ? `${appointments.length} appointment${appointments.length === 1 ? '' : 's'} found` : 'Your appointment history'}
          </Text>
        </View>
        <View style={styles.headerDecoration} />
      </View>

      {loading && appointments.length === 0 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Loading your records...</Text>
        </View>
      ) : appointments.length === 0 ? (
        <ScrollView 
          contentContainerStyle={styles.emptyScrollContainer}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        >
          <View style={styles.emptyState}>
            <View style={styles.emptyIconContainer}>
              <MaterialCommunityIcons name="file-document-outline" size={64} color={COLORS.textMuted} />
            </View>
            <Text style={styles.emptyTitle}>No Records Found</Text>
            <Text style={styles.emptySubtitle}>
              Your appointment history will appear here once you start booking appointments.
            </Text>
            <TouchableOpacity style={styles.emptyActionButton} onPress={() => navigation.navigate('Appointments')}>
              <MaterialCommunityIcons name="calendar-plus" size={20} color={COLORS.surface} />
              <Text style={styles.emptyActionText}>Book Your First Appointment</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>
      ) : (
        <FlatList
          data={appointments}
          keyExtractor={(i) => `${i.id}`}
          renderItem={renderItem}
          contentContainerStyle={styles.listContainer}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
          showsVerticalScrollIndicator={false}
        />
      )}
    </SafeAreaView>
  );
}

// Helper Functions
function getServiceIcon(service: string = '') {
  const serviceType = service.toLowerCase();
  if (serviceType.includes('check') || serviceType.includes('exam')) return 'stethoscope';
  if (serviceType.includes('vaccin')) return 'needle';
  if (serviceType.includes('dental') || serviceType.includes('teeth')) return 'tooth';
  if (serviceType.includes('surgery') || serviceType.includes('operation')) return 'medical-bag';
  if (serviceType.includes('grooming') || serviceType.includes('bath')) return 'content-cut';
  if (serviceType.includes('emergency')) return 'ambulance';
  return 'medical-bag';
}

const styles = StyleSheet.create({
  container: { 
    flex: 1, 
    backgroundColor: COLORS.background 
  },
  
  // Enhanced Header Styles
  headerContainer: {
    backgroundColor: COLORS.surface,
    paddingTop: 20,
    paddingHorizontal: 24,
    paddingBottom: 24,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.borderLight,
    position: 'relative',
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 3,
  },
  headerContent: {
    zIndex: 1,
  },
  headerTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: COLORS.text,
    marginLeft: 12,
    letterSpacing: 0.3,
  },
  headerSubtitle: {
    fontSize: 15,
    color: COLORS.textSecondary,
    fontWeight: '500',
    marginLeft: 40,
  },
  headerDecoration: {
    position: 'absolute',
    top: 0,
    right: -20,
    width: 100,
    height: 100,
    backgroundColor: COLORS.primary,
    opacity: 0.05,
    borderRadius: 50,
  },

  // Loading State
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: COLORS.textSecondary,
    fontWeight: '500',
  },

  // List Container
  listContainer: {
    padding: 20,
    paddingBottom: 100,
  },

  // Modern Appointment Card
  appointmentCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 4,
  },

  // Card Header
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 16,
  },
  serviceInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  serviceIcon: {
    width: 44,
    height: 44,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 12,
  },
  serviceTitleContainer: {
    flex: 1,
  },
  serviceTitle: {
    fontSize: 17,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 2,
    letterSpacing: 0.2,
  },
  appointmentId: {
    fontSize: 12,
    color: COLORS.textMuted,
    fontWeight: '600',
  },

  // Status Badge
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    marginLeft: 12,
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 6,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '700',
    letterSpacing: 0.3,
  },

  // Appointment Details
  appointmentDetails: {
    marginBottom: 16,
  },
  detailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  detailText: {
    fontSize: 14,
    color: COLORS.textSecondary,
    fontWeight: '500',
    marginLeft: 10,
    flex: 1,
    lineHeight: 20,
  },

  // Card Footer
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: COLORS.borderLight,
  },
  viewDetailsText: {
    fontSize: 13,
    color: COLORS.primary,
    fontWeight: '600',
  },

  // Enhanced Empty State
  emptyScrollContainer: {
    flexGrow: 1,
  },
  emptyState: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
    paddingTop: 80,
  },
  emptyIconContainer: {
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: COLORS.borderLight,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 8,
    textAlign: 'center',
  },
  emptySubtitle: {
    fontSize: 15,
    color: COLORS.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 32,
    paddingHorizontal: 20,
  },
  emptyActionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.primary,
    paddingHorizontal: 24,
    paddingVertical: 14,
    borderRadius: 16,
    shadowColor: COLORS.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6,
  },
  emptyActionText: {
    color: COLORS.surface,
    fontSize: 15,
    fontWeight: '700',
    marginLeft: 8,
    letterSpacing: 0.3,
  },
});

function getStatusColor(status: string | undefined, mode: 'default' | 'background' | 'light' = 'default') {
  const s = (status || '').toLowerCase();
  
  const colorMap = {
    confirmed: {
      default: COLORS.success,
      background: COLORS.successBg,
      light: '#F0FDF4',
    },
    completed: {
      default: COLORS.info,
      background: COLORS.infoBg,
      light: '#F8FAFC',
    },
    cancelled: {
      default: COLORS.error,
      background: COLORS.errorBg,
      light: '#FEF7F7',
    },
    pending: {
      default: COLORS.warning,
      background: COLORS.warningBg,
      light: '#FFFCF0',
    },
  };

  const statusColors = colorMap[s as keyof typeof colorMap] || colorMap.pending;
  return statusColors[mode];
}

function formatDateTime(date?: string, time?: string) {
  if (!date) return '';
  let t = time || '';
  // remove seconds if present
  if (t && t.length === 8) t = t.slice(0,5);
  return `${date}${t ? ' • ' + t : ''}`;
}
