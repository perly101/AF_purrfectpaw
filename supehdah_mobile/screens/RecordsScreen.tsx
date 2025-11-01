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
} from 'react-native';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { API } from '../src/api';
import { ROUTES } from '../src/routes';
import { useNavigation } from '@react-navigation/native';

export default function RecordsScreen() {
  const navigation = useNavigation<any>();
  const [loading, setLoading] = React.useState(false);
  const [refreshing, setRefreshing] = React.useState(false);
  const [appointments, setAppointments] = React.useState<any[]>([]);

  const fetchAppointments = React.useCallback(async () => {
    setLoading(true);
    try {
      const response = await API.get(ROUTES.PROFILE.APPOINTMENTS.LIST);
      const payload = response.data?.appointments ?? response.data?.data ?? response.data;
      const list = Array.isArray(payload) ? payload : (payload.appointments ?? []);
      setAppointments(list || []);
    } catch (err) {
      console.error('Failed to fetch records:', err);
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

  const renderItem = ({ item }: { item: any }) => (
    <TouchableOpacity style={styles.itemCard} onPress={() => navigation.navigate('AppointmentDetails', { id: item.id })} activeOpacity={0.9}>
      <View style={[styles.statusAccent, { backgroundColor: getStatusColor(item.status) }]} />
      <View style={styles.cardContent}>
        <View style={styles.cardHeader}>
          <Text style={styles.title}>{item.service ?? item.type ?? 'Appointment'}</Text>
          <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status, true) }]}> 
            <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>{(item.status ?? '').toString().toUpperCase()}</Text>
          </View>
        </View>

        <Text style={styles.meta}>{item.pet_name ?? item.patient_name ?? ''}</Text>
        <Text style={styles.meta}>{formatDateTime(item.appointment_date, item.appointment_time)}</Text>
        <Text style={styles.clinic}>{item.clinic_name ?? item.clinic ?? ''}{item.doctor?.name ? ` • Dr. ${item.doctor.name}` : ''}</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.headerRow}>
        <Text style={styles.header}>Records</Text>
      </View>

      {loading && appointments.length === 0 ? (
        <ActivityIndicator size="large" color="#6C5CE7" style={{ marginTop: 40 }} />
      ) : appointments.length === 0 ? (
        <View style={styles.emptyState}>
          <MaterialCommunityIcons name="file-document" size={48} color="#64748B" />
          <Text style={styles.emptyText}>No past appointments found.</Text>
        </View>
      ) : (
        <FlatList
          data={appointments}
          keyExtractor={(i) => `${i.id}`}
          renderItem={renderItem}
          contentContainerStyle={{ padding: 16 }}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FB' },
  headerRow: { padding: 20, borderBottomWidth: 1, borderBottomColor: '#EDF2F7', backgroundColor: '#FFF' },
  header: { fontSize: 22, fontWeight: '800', color: '#0F172A' },
  itemCard: { flexDirection: 'row', backgroundColor: '#FFFFFF', borderRadius: 14, marginBottom: 14, overflow: 'hidden', elevation: 2, shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, borderWidth: 1, borderColor: '#EFF2F7' },
  statusAccent: { width: 6 },
  cardContent: { flex: 1, padding: 14 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 },
  title: { fontSize: 16, fontWeight: '800', color: '#0F172A' },
  meta: { fontSize: 13, color: '#475569', marginBottom: 4 },
  clinic: { fontSize: 13, color: '#475569', fontWeight: '600', marginTop: 6 },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  statusText: { fontSize: 11, fontWeight: '800', textTransform: 'uppercase' },
  emptyState: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 20 },
  emptyText: { marginTop: 12, fontSize: 16, color: '#64748B' },
});

// Helpers
function getStatusColor(status: string | undefined, bg: boolean = false) {
  const s = (status || '').toLowerCase();
  switch (s) {
    case 'confirmed':
      return bg ? '#D1FAE5' : '#10B981';
    case 'completed':
      return bg ? '#DBEAFE' : '#3B82F6';
    case 'cancelled':
      return bg ? '#FEE2E2' : '#EF4444';
    case 'pending':
    default:
      return bg ? '#FEF3C7' : '#F59E0B';
  }
}

function formatDateTime(date?: string, time?: string) {
  if (!date) return '';
  let t = time || '';
  // remove seconds if present
  if (t && t.length === 8) t = t.slice(0,5);
  return `${date}${t ? ' • ' + t : ''}`;
}
