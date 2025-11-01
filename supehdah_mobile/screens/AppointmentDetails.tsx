import React, { useEffect, useState } from 'react';
import { View, Text, SafeAreaView, StyleSheet, ActivityIndicator, ScrollView } from 'react-native';
import { API } from '../src/api';
import { ROUTES } from '../src/routes';
import { useRoute } from '@react-navigation/native';

export default function AppointmentDetails() {
  const route = useRoute<any>();
  const { id } = route.params || {};
  const [loading, setLoading] = useState(true);
  const [data, setData] = useState<any | null>(null);

  useEffect(() => {
    const fetchDetails = async () => {
      try {
        setLoading(true);
        const res = await API.get(ROUTES.PROFILE.APPOINTMENTS.DETAILS(id));
        const payload = res.data?.data ?? res.data;
        setData(payload);
      } catch (e) {
        console.error('Failed to fetch appointment details', e);
      } finally {
        setLoading(false);
      }
    };

    if (id) fetchDetails();
  }, [id]);

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <ActivityIndicator size="large" color="#6C5CE7" style={{ marginTop: 40 }} />
      </SafeAreaView>
    );
  }

  if (!data) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.empty}>
          <Text style={styles.emptyText}>Appointment not found or you don't have access to this appointment.</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView contentContainerStyle={{ padding: 16 }}>
        <Text style={styles.title}>Appointment Details</Text>

        <View style={styles.card}>
          <View style={{ flex: 1 }}>
            <Text style={styles.cardTitle}>{data.service ?? data.type ?? 'Appointment'}</Text>
            <Text style={styles.cardMeta}>{data.clinic_name ?? data.clinic ?? ''}</Text>
            <Text style={styles.cardMeta}>{formatDateTime(data.appointment_date, data.appointment_time)}</Text>
          </View>
          <View style={{ alignItems: 'flex-end', marginLeft: 12 }}>
            <View style={[styles.statusBadge, { backgroundColor: getStatusColor(data.status, true) }]}>
              <Text style={[styles.statusText, { color: getStatusColor(data.status) }]}>{(data.status ?? '').toString().toUpperCase()}</Text>
            </View>
            {data.completed_at ? <Text style={styles.smallMeta}>Completed: {formatTimestamp(data.completed_at)}</Text> : null}
            {!data.completed_at && data.updated_at ? <Text style={styles.smallMeta}>Last updated: {formatTimestamp(data.updated_at)}</Text> : null}
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Owner</Text>
          <Text style={styles.sectionValue}>{data.owner_name} • {data.owner_phone}</Text>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Doctor</Text>
          <Text style={styles.sectionValue}>{data.doctor?.name ?? 'Not assigned'}</Text>
          {data.doctor?.phone ? <Text style={styles.sectionValue}>Phone: {data.doctor.phone}</Text> : null}
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Responses</Text>
          {Array.isArray(data.responses) && data.responses.length > 0 ? (
            data.responses.map((r: any, idx: number) => (
              <View key={idx} style={styles.responseItem}>
                <Text style={styles.responseLabel}>{r.field ?? r.label ?? `Field ${idx + 1}`}</Text>
                <Text style={styles.responseValue}>{typeof r.value === 'object' ? JSON.stringify(r.value) : String(r.value)}</Text>
              </View>
            ))
          ) : (
            <Text style={styles.emptyText}>No additional responses recorded.</Text>
          )}
        </View>

      </ScrollView>
    </SafeAreaView>
  );
}

function formatDateTime(date?: string, time?: string) {
  if (!date) return '';
  let t = time || '';
  if (t && t.length === 8) t = t.slice(0,5);
  return `${date}${t ? ' • ' + t : ''}`;
}

function formatTimestamp(ts?: string) {
  if (!ts) return '';
  try {
    const d = new Date(ts);
    if (isNaN(d.getTime())) return ts;
    return d.toLocaleString();
  } catch (e) {
    return ts;
  }
}

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

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F5F7FB' },
  title: { fontSize: 20, fontWeight: '800', color: '#0F172A', marginBottom: 12 },
  card: { backgroundColor: '#fff', borderRadius: 12, padding: 16, flexDirection: 'row', alignItems: 'center', marginBottom: 14, elevation: 2, shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 6 },
  cardTitle: { fontSize: 18, fontWeight: '800', color: '#0F172A' },
  cardMeta: { color: '#475569', marginTop: 6 },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 999 },
  statusText: { fontWeight: '800', textTransform: 'uppercase', fontSize: 12 },
  smallMeta: { color: '#64748B', marginTop: 8, fontSize: 12 },
  section: { backgroundColor: '#fff', borderRadius: 12, padding: 16, marginBottom: 14 },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: '#0F172A' },
  sectionValue: { color: '#475569', marginTop: 8 },
  responseItem: { marginTop: 12 },
  responseLabel: { fontWeight: '700', color: '#0F172A' },
  responseValue: { color: '#475569', marginTop: 6 },
  empty: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 20 },
  emptyText: { marginTop: 12, fontSize: 16, color: '#64748B' },
});
