import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  SafeAreaView,
  ScrollView,
  TouchableOpacity,
  FlatList,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Calendar } from 'react-native-calendars';
import { API } from '../src/api';
import { ROUTES } from '../src/routes';
import { useNavigation } from '@react-navigation/native';

const PINK = '#FF9EB1';
const PURPLE = '#6C5CE7';
const WHITE = '#FFFFFF';
const DARK = '#1A1D29';
const LIGHT = '#F8FAFC';
const MUTED = '#64748B';
const SUCCESS = '#10B981';
const WARNING = '#F59E0B';
const GRADIENT_START = '#667EEA';
const GRADIENT_END = '#764BA2';

type Appointment = {
  id: string;
  pet_id: string;
  pet_name: string;
  service: string;
  appointment_date: string;
  appointment_time: string;
  status: string;
  clinic_name: string;
  clinic_id: string;
};

type Pet = {
  id: string;
  name: string;
  type: string;
  breed: string;
  age: number;
};

type UserStats = {
  total_appointments: number;
  upcoming_appointments: number;
  total_pets: number;
  completed_appointments: number;
  cancelled_appointments: number;
  user_since: string;
};

export default function HomeScreen() {
  const navigation = useNavigation<any>();

  const [loading, setLoading] = React.useState<boolean>(false);
  const [userName, setUserName] = React.useState<string | null>(null);
  const [loadingUser, setLoadingUser] = React.useState<boolean>(false);
  const [appointments, setAppointments] = React.useState<Appointment[]>([]);
  const [pets, setPets] = React.useState<Pet[]>([]);
  const [userStats, setUserStats] = React.useState<UserStats | null>(null);
  const [refreshing, setRefreshing] = React.useState<boolean>(false);

  const getTimeBasedGreeting = React.useCallback(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning,';
    if (hour < 18) return 'Good afternoon,';
    return 'Good evening,';
  }, []);

  const fetchUserData = React.useCallback(async () => {
    try {
      // Check if we have a token before attempting the request
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
                   
      if (!token) {
        console.log('No auth token available, skipping user data fetch');
        return;
      }
      
      setLoadingUser(true);
      const res = await API.get('/me');
      setUserName(res.data?.name ?? null);
    } catch (e: any) {
      // Only log detailed errors for non-auth issues to reduce console spam
      if (e?.response?.status !== 401 && e?.response?.status !== 429) {
        console.error('Error fetching user data:', e);
      }
    } finally {
      setLoadingUser(false);
    }
  }, []);

  const fetchAppointments = React.useCallback(async () => {
    try {
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
      
      if (!token) {
        console.log('No auth token available, skipping appointments fetch');
        return;
      }

      // Use the profile appointments route which returns appointments belonging to the
      // currently authenticated user. The API module already attaches token via interceptor.
      const response = await API.get(ROUTES.PROFILE.APPOINTMENTS.LIST);

      // Support multiple response shapes (data.appointments | data.data | data)
      const payload = response.data?.appointments ?? response.data?.data ?? response.data;

      if (payload) {
        const list = Array.isArray(payload) ? payload : (payload.appointments ?? []);
        // Get upcoming appointments only, limit to 3 for home screen
        const upcoming = list
          .filter((apt: any) => new Date(apt.appointment_date) >= new Date())
          .slice(0, 3);
        setAppointments(upcoming);
      } else {
        setAppointments([]);
      }
    } catch (e: any) {
      console.error('Failed to fetch appointments:', e);
      setAppointments([]);
    }
  }, []);

  const fetchPets = React.useCallback(async () => {
    try {
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
      
      if (!token) return;

      const response = await API.get('/pets', {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      if (response.data?.pets) {
        setPets(response.data.pets);
      }
    } catch (e: any) {
      console.error('Failed to fetch pets:', e);
      setPets([]);
    }
  }, []);

  const fetchUserStats = React.useCallback(async () => {
    try {
      const token = await AsyncStorage.getItem('token') || 
                   await AsyncStorage.getItem('userToken') || 
                   await AsyncStorage.getItem('accessToken');
      
      if (!token) {
        console.log('No token found for user stats');
        return;
      }

      console.log('Fetching user stats...');
      const response = await API.get('/user/stats');
      
      if (response.data) {
        console.log('User stats fetched successfully:', response.data);
        setUserStats(response.data);
      }
    } catch (e: any) {
      console.error('Failed to fetch user stats:', e);
      
      // Set default stats if API fails
      setUserStats({
        total_pets: 0,
        upcoming_appointments: 0,
        total_appointments: 0,
        completed_appointments: 0,
        cancelled_appointments: 0,
        user_since: new Date().toISOString().split('T')[0]
      });
    }
  }, []);

  const fetchAllData = React.useCallback(async () => {
    if (!refreshing) setLoading(true);

    await Promise.all([
      fetchUserData(), 
      fetchAppointments(), 
      fetchPets(), 
      fetchUserStats()
    ]);

    setLoading(false);
    setRefreshing(false);
  }, [refreshing, fetchUserData, fetchAppointments, fetchPets, fetchUserStats]);

  React.useEffect(() => {
    fetchAllData();
  }, []);

  const onRefresh = React.useCallback(() => {
    setRefreshing(true);
    fetchAllData();
  }, [fetchAllData]);

  // temp routes used: 'Appointments', 'Records' — replace with your route names as needed
  return (
    <SafeAreaView style={styles.container}>
      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
      >
        {/* Header (centered, time-based greeting) */}
        <View style={styles.header}>
          <Text style={styles.greeting}>{getTimeBasedGreeting()}</Text>
          <Text style={styles.username}>{userName ?? (loadingUser ? 'Loading...' : 'User')}</Text>
        </View>

        {/* Stats Cards */}
        {userStats && (
          <View style={styles.statsContainer}>
            <View style={styles.statCard}>
              <Text style={styles.statNumber}>{userStats.total_pets}</Text>
              <Text style={styles.statLabel}>Pets</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statNumber}>{userStats.upcoming_appointments}</Text>
              <Text style={styles.statLabel}>Upcoming</Text>
            </View>
            <View style={styles.statCard}>
              <Text style={styles.statNumber}>{userStats.total_appointments}</Text>
              <Text style={styles.statLabel}>Total Visits</Text>
            </View>
          </View>
        )}

        {/* Quick Actions with routes */}
        <View style={styles.quickActions}>
          <Action
            icon="calendar-plus"
            label="Book"
            color="#FFF1F3"
            iconColor={PINK}
            onPress={() => navigation.navigate('Appointments')}
          />
          <Action
            icon="hospital-building"
            label="Clinics"
            color="#EFF6FF"
            iconColor="#3B82F6"
            onPress={() => navigation.navigate('Appointments')}
          />
          <Action
            icon="file-document-multiple"
            label="Records"
            color="#F0FDF4"
            iconColor={SUCCESS}
            onPress={() => navigation.navigate('Records')}
          />
          {/* <Action
            icon="paw"
            label="My Pets"
            color="#FEF3C7"
            iconColor={WARNING}
            onPress={() => navigation.navigate('PetScreen')}
          /> */}
        </View>

        {/* Welcome Card */}
        <View style={styles.welcomeCard}>
          <View style={styles.welcomeLeft}>
            <Text style={styles.welcomeTitle}>Welcome to SuPehDah</Text>
            <Text style={styles.welcomeText}>
              Manage appointments, records, and connect with trusted clinics — all in one place.
            </Text>
            <TouchableOpacity style={styles.primaryBtn} onPress={() => navigation.navigate('Appointments')}>
              <Text style={styles.primaryBtnText}>Book Appointment</Text>
            </TouchableOpacity>
          </View>
          <View style={styles.welcomeGraphic}>
            <MaterialCommunityIcons name="dog-side" size={48} color={PINK} />
          </View>
        </View>

        {/* Appointments & Calendar */}
        <View style={styles.rowCards}>
          <View style={styles.cardSmall}>
            <View style={styles.sectionHeader}>
              <Text style={styles.cardTitle}>Upcoming</Text>
              <TouchableOpacity onPress={() => navigation.navigate('Appointments')}>
                <Text style={styles.seeAllText}>View All</Text>
              </TouchableOpacity>
            </View>
            {loading && appointments.length === 0 ? (
              <ActivityIndicator size="small" color={PURPLE} style={{ margin: 12 }} />
            ) : appointments.length > 0 ? (
              <FlatList
                data={appointments}
                keyExtractor={(i) => i.id}
                scrollEnabled={false}
                renderItem={({ item }) => (
                  <TouchableOpacity
                    style={styles.appItem}
                    onPress={() => navigation.navigate('AppointmentDetails', { id: item.id })}
                  >
                    <View style={styles.appInfo}>
                      <Text style={styles.appWhat}>{item.service}</Text>
                      <Text style={styles.appMeta}>
                        {item.pet_name} • {item.appointment_date} {item.appointment_time}
                      </Text>
                    </View>
                    <View style={styles.appStatus}>
                      <Text style={styles.appClinic}>{item.clinic_name}</Text>
                      <View style={[styles.statusBadge, { backgroundColor: item.status === 'confirmed' ? '#E8F5E8' : '#FFF3E0' }]}>
                        <Text style={[styles.statusText, { color: item.status === 'confirmed' ? '#2E7D2E' : '#E65100' }]}>
                          {item.status}
                        </Text>
                      </View>
                    </View>
                  </TouchableOpacity>
                )}
              />
            ) : (
              <View style={styles.emptyState}>
                <MaterialCommunityIcons name="calendar-blank" size={32} color={MUTED} />
                <Text style={styles.emptyText}>No appointments scheduled</Text>
                <TouchableOpacity style={styles.emptyAction} onPress={() => navigation.navigate('Appointments')}>
                  <Text style={styles.emptyActionText}>Book Now</Text>
                </TouchableOpacity>
              </View>
            )}
          </View>

          <View style={styles.cardLarge}>
            <Text style={styles.cardTitle}>Calendar</Text>
            <Calendar
              style={styles.calendar}
              theme={{
                backgroundColor: WHITE,
                calendarBackground: WHITE,
                textSectionTitleColor: DARK,
                selectedDayBackgroundColor: PURPLE,
                selectedDayTextColor: WHITE,
                todayTextColor: PINK,
                dayTextColor: DARK,
                arrowColor: PINK,
                monthTextColor: DARK,
                textMonthFontWeight: '700',
                textDayFontWeight: '500',
                textDayHeaderFontWeight: '600',
                textDayFontSize: 14,
                textMonthFontSize: 16,
                textDayHeaderFontSize: 12,
              }}
              markedDates={appointments.reduce((acc, app) => {
                if (app.appointment_date) {
                  acc[app.appointment_date] = { selected: true, selectedColor: PURPLE };
                }
                return acc;
              }, {} as any)}
            />
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

function Action({
  icon,
  label,
  color,
  iconColor,
  onPress,
}: {
  icon: string;
  label: string;
  color: string;
  iconColor: string;
  onPress?: () => void;
}) {
  return (
    <TouchableOpacity style={[styles.actionItem, { backgroundColor: color }]} onPress={onPress}>
      <MaterialCommunityIcons name={icon as any} size={22} color={iconColor} />
      <Text style={styles.actionLabel}>{label}</Text>
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  container: { 
    flex: 1, 
    backgroundColor: LIGHT 
  },
  content: { 
    padding: 20, 
    paddingBottom: 100 
  },
  
  // Header - Modern centered design
  header: { 
    alignItems: 'center', 
    justifyContent: 'center', 
    marginBottom: 24, 
    marginTop: 20,
    paddingVertical: 16
  },
  greeting: { 
    color: MUTED, 
    fontSize: 16, 
    fontWeight: '500',
    marginBottom: 4
  },
  username: { 
    color: DARK, 
    fontSize: 28, 
    fontWeight: '800',
    letterSpacing: 0.5
  },

  // Stats Cards
  statsContainer: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    marginBottom: 20,
    gap: 12
  },
  statCard: { 
    flex: 1, 
    backgroundColor: WHITE, 
    borderRadius: 16, 
    padding: 20, 
    alignItems: 'center',
    shadowColor: DARK,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 6
  },
  statNumber: { 
    fontSize: 24, 
    fontWeight: '800', 
    color: PURPLE,
    marginBottom: 4
  },
  statLabel: { 
    fontSize: 12, 
    fontWeight: '600', 
    color: MUTED,
    textTransform: 'uppercase',
    letterSpacing: 0.5
  },

  // Quick Actions - Modern grid
  quickActions: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    marginBottom: 24,
    gap: 8
  },
  actionItem: { 
    flex: 1, 
    padding: 16, 
    borderRadius: 16, 
    alignItems: 'center',
    marginHorizontal: 2,
    shadowColor: DARK,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 3
  },
  actionLabel: { 
    marginTop: 8, 
    fontSize: 12, 
    fontWeight: '600',
    color: DARK 
  },

  // Welcome Card - Modern gradient-like design
  welcomeCard: { 
    backgroundColor: WHITE, 
    borderRadius: 20, 
    padding: 20, 
    flexDirection: 'row', 
    alignItems: 'center', 
    marginBottom: 24,
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
    elevation: 8,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  welcomeLeft: { 
    flex: 1 
  },
  welcomeTitle: { 
    fontSize: 18, 
    fontWeight: '800', 
    color: DARK, 
    marginBottom: 8,
    letterSpacing: 0.3
  },
  welcomeText: { 
    color: MUTED, 
    fontSize: 14, 
    lineHeight: 20,
    marginBottom: 16,
    fontWeight: '500'
  },
  welcomeGraphic: { 
    width: 80, 
    height: 80, 
    borderRadius: 20, 
    backgroundColor: '#FFF1F3', 
    alignItems: 'center', 
    justifyContent: 'center',
    borderWidth: 2,
    borderColor: '#FFE4E6'
  },
  primaryBtn: { 
    backgroundColor: PURPLE, 
    paddingVertical: 12, 
    paddingHorizontal: 20, 
    borderRadius: 12, 
    alignSelf: 'flex-start',
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6
  },
  primaryBtnText: { 
    color: WHITE, 
    fontWeight: '700',
    fontSize: 14,
    letterSpacing: 0.3
  },

  // Cards Layout
  rowCards: { 
    flexDirection: 'column', 
    gap: 16 
  },
  cardSmall: { 
    backgroundColor: WHITE, 
    borderRadius: 20, 
    padding: 20, 
    marginBottom: 16,
    shadowColor: DARK,
    shadowOpacity: 0.06,
    shadowOffset: { width: 0, height: 6 },
    shadowRadius: 12,
    elevation: 4,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  cardLarge: { 
    backgroundColor: WHITE, 
    borderRadius: 20, 
    padding: 20,
    shadowColor: DARK,
    shadowOpacity: 0.06,
    shadowOffset: { width: 0, height: 6 },
    shadowRadius: 12,
    elevation: 4,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
  cardTitle: { 
    fontSize: 18, 
    fontWeight: '800', 
    color: DARK, 
    marginBottom: 16,
    letterSpacing: 0.3
  },

  // Section Header
  sectionHeader: { 
    flexDirection: 'row', 
    justifyContent: 'space-between', 
    alignItems: 'center', 
    marginBottom: 16 
  },
  seeAllText: { 
    fontSize: 14, 
    color: PURPLE, 
    fontWeight: '700',
    letterSpacing: 0.3
  },

  // Appointment Items - Modern cards
  appItem: { 
    backgroundColor: '#FAFBFC',
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: DARK,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2
  },
  appInfo: { 
    flex: 1,
    marginBottom: 8
  },
  appWhat: { 
    fontSize: 16, 
    fontWeight: '700', 
    color: DARK,
    marginBottom: 4,
    letterSpacing: 0.2
  },
  appMeta: { 
    fontSize: 13, 
    color: MUTED, 
    fontWeight: '500',
    letterSpacing: 0.2
  },
  appStatus: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center'
  },
  appClinic: { 
    fontSize: 13, 
    color: MUTED, 
    fontWeight: '600',
    flex: 1
  },
  statusBadge: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 12,
    marginLeft: 8
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
    textTransform: 'uppercase',
    letterSpacing: 0.5
  },

  // Empty State - Modern design
  emptyState: { 
    alignItems: 'center', 
    paddingVertical: 40,
    paddingHorizontal: 20
  },
  emptyText: { 
    fontSize: 16, 
    color: MUTED, 
    marginTop: 12, 
    marginBottom: 20,
    textAlign: 'center',
    fontWeight: '500',
    lineHeight: 22
  },
  emptyAction: { 
    backgroundColor: PURPLE, 
    paddingHorizontal: 24, 
    paddingVertical: 12, 
    borderRadius: 12,
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6
  },
  emptyActionText: { 
    color: WHITE, 
    fontSize: 14, 
    fontWeight: '700',
    letterSpacing: 0.3
  },

  // Calendar - Enhanced styling
  calendar: { 
    borderRadius: 16, 
    overflow: 'hidden', 
    marginTop: 8,
    backgroundColor: WHITE,
    borderWidth: 1,
    borderColor: '#F1F5F9'
  },
});
