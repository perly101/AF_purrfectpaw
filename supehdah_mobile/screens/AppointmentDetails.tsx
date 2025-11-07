import React, { useEffect, useState } from 'react';
import { View, Text, SafeAreaView, StyleSheet, ActivityIndicator, ScrollView, TouchableOpacity } from 'react-native';
import { MaterialCommunityIcons, Ionicons } from '@expo/vector-icons';
import { API } from '../src/api';
import { ROUTES } from '../src/routes';
import { useRoute, useNavigation } from '@react-navigation/native';

// Modern color palette - consistent with RecordsScreen
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

export default function AppointmentDetails() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
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
        {/* Enhanced Header */}
        <View style={styles.headerContainer}>
          <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
            <Ionicons name="chevron-back" size={24} color={COLORS.text} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Appointment Details</Text>
          <View style={styles.headerSpacer} />
        </View>
        
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Loading appointment details...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (!data) {
    return (
      <SafeAreaView style={styles.container}>
        {/* Header */}
        <View style={styles.headerContainer}>
          <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
            <Ionicons name="chevron-back" size={24} color={COLORS.text} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Appointment Details</Text>
          <View style={styles.headerSpacer} />
        </View>
        
        <View style={styles.errorContainer}>
          <View style={styles.errorIconContainer}>
            <MaterialCommunityIcons name="alert-circle-outline" size={64} color={COLORS.error} />
          </View>
          <Text style={styles.errorTitle}>Appointment Not Found</Text>
          <Text style={styles.errorText}>
            This appointment doesn't exist or you don't have permission to view it.
          </Text>
          <TouchableOpacity style={styles.errorButton} onPress={() => navigation.goBack()}>
            <Text style={styles.errorButtonText}>Go Back</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      {/* Enhanced Header */}
      <View style={styles.headerContainer}>
        <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
          <Ionicons name="chevron-back" size={24} color={COLORS.text} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Appointment Details</Text>
        <TouchableOpacity style={styles.headerAction}>
          <MaterialCommunityIcons name="share-variant" size={22} color={COLORS.textMuted} />
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
        {/* Hero Card - Main Appointment Info */}
        <View style={styles.heroCard}>
          <View style={styles.heroHeader}>
            <View style={[styles.serviceIcon, { backgroundColor: getStatusColor(data.status, 'light') }]}>
              <MaterialCommunityIcons 
                name={getServiceIcon(data.service ?? data.type)} 
                size={28} 
                color={getStatusColor(data.status)} 
              />
            </View>
            <View style={styles.heroInfo}>
              <Text style={styles.heroTitle}>{data.service ?? data.type ?? 'Appointment'}</Text>
              <Text style={styles.heroSubtitle}>#{data.id}</Text>
            </View>
            <View style={[styles.statusBadge, { backgroundColor: getStatusColor(data.status, 'background') }]}>
              <View style={[styles.statusDot, { backgroundColor: getStatusColor(data.status) }]} />
              <Text style={[styles.statusText, { color: getStatusColor(data.status) }]}>
                {(data.status ?? 'pending').charAt(0).toUpperCase() + (data.status ?? 'pending').slice(1)}
              </Text>
            </View>
          </View>

          <View style={styles.heroDetails}>
            <View style={styles.heroDetailRow}>
              <MaterialCommunityIcons name="calendar-clock" size={20} color={COLORS.primary} />
              <Text style={styles.heroDetailText}>{formatDateTime(data.appointment_date, data.appointment_time)}</Text>
            </View>
            <View style={styles.heroDetailRow}>
              <MaterialCommunityIcons name="hospital-building" size={20} color={COLORS.primary} />
              <Text style={styles.heroDetailText}>{data.clinic_name ?? data.clinic ?? 'Clinic not specified'}</Text>
            </View>
          </View>

          {/* Timestamps */}
          {(data.completed_at || data.updated_at) && (
            <View style={styles.timestampContainer}>
              {data.completed_at && (
                <View style={styles.timestampRow}>
                  <MaterialCommunityIcons name="check-circle" size={16} color={COLORS.success} />
                  <Text style={styles.timestampText}>Completed: {formatTimestamp(data.completed_at)}</Text>
                </View>
              )}
              {!data.completed_at && data.updated_at && (
                <View style={styles.timestampRow}>
                  <MaterialCommunityIcons name="clock-outline" size={16} color={COLORS.textMuted} />
                  <Text style={styles.timestampText}>Last updated: {formatTimestamp(data.updated_at)}</Text>
                </View>
              )}
            </View>
          )}
        </View>

        {/* Owner Information */}
        <View style={styles.infoCard}>
          <View style={styles.cardTitleRow}>
            <MaterialCommunityIcons name="account" size={24} color={COLORS.primary} />
            <Text style={styles.cardTitle}>Pet Owner</Text>
          </View>
          <View style={styles.infoRow}>
            <MaterialCommunityIcons name="account-outline" size={18} color={COLORS.textMuted} />
            <Text style={styles.infoText}>{data.owner_name || 'Name not provided'}</Text>
          </View>
          <View style={styles.infoRow}>
            <MaterialCommunityIcons name="phone-outline" size={18} color={COLORS.textMuted} />
            <Text style={styles.infoText}>{data.owner_phone || 'Phone not provided'}</Text>
          </View>
        </View>

        {/* Pet Information */}
        {data.pet_name && (
          <View style={styles.infoCard}>
            <View style={styles.cardTitleRow}>
              <MaterialCommunityIcons name="paw" size={24} color={COLORS.secondary} />
              <Text style={styles.cardTitle}>Pet Information</Text>
            </View>
            <View style={styles.infoRow}>
              <MaterialCommunityIcons name="tag-outline" size={18} color={COLORS.textMuted} />
              <Text style={styles.infoText}>{data.pet_name}</Text>
            </View>
          </View>
        )}

        {/* Doctor Information */}
        <View style={styles.infoCard}>
          <View style={styles.cardTitleRow}>
            <MaterialCommunityIcons name="doctor" size={24} color={COLORS.info} />
            <Text style={styles.cardTitle}>Attending Veterinarian</Text>
          </View>
          <View style={styles.infoRow}>
            <MaterialCommunityIcons name="account-tie" size={18} color={COLORS.textMuted} />
            <Text style={styles.infoText}>Dr. {data.doctor?.name ?? 'Not assigned yet'}</Text>
          </View>
          {data.doctor?.phone && (
            <View style={styles.infoRow}>
              <MaterialCommunityIcons name="phone-outline" size={18} color={COLORS.textMuted} />
              <Text style={styles.infoText}>{data.doctor.phone}</Text>
            </View>
          )}
        </View>

        {/* Consultation Notes */}
        <View style={styles.infoCard}>
          <View style={styles.cardTitleRow}>
            <MaterialCommunityIcons name="note-text" size={24} color={COLORS.info} />
            <Text style={styles.cardTitle}>Consultation Notes</Text>
          </View>
          {data.consultation_notes ? (
            <View style={styles.consultationNotesContainer}>
              <Text style={styles.consultationNotesText}>{data.consultation_notes}</Text>
            </View>
          ) : (
            <View style={styles.noDataContainer}>
              <MaterialCommunityIcons name="note-outline" size={20} color={COLORS.textMuted} />
              <Text style={styles.noDataText}>No consultation notes recorded</Text>
            </View>
          )}
        </View>

        {/* Additional Responses */}
        <View style={styles.infoCard}>
          <View style={styles.cardTitleRow}>
            <MaterialCommunityIcons name="clipboard-list" size={24} color={COLORS.warning} />
            <Text style={styles.cardTitle}>Additional Information</Text>
          </View>
          {Array.isArray(data.responses) && data.responses.length > 0 ? (
            data.responses.map((r: any, idx: number) => (
              <View key={idx} style={styles.responseItem}>
                <Text style={styles.responseLabel}>{r.field ?? r.label ?? `Field ${idx + 1}`}</Text>
                <Text style={styles.responseValue}>
                  {typeof r.value === 'object' ? JSON.stringify(r.value) : String(r.value)}
                </Text>
              </View>
            ))
          ) : (
            <View style={styles.noDataContainer}>
              <MaterialCommunityIcons name="information-outline" size={20} color={COLORS.textMuted} />
              <Text style={styles.noDataText}>No additional information recorded</Text>
            </View>
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

const styles = StyleSheet.create({
  container: { 
    flex: 1, 
    backgroundColor: COLORS.background 
  },

  // Enhanced Header
  headerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingVertical: 16,
    backgroundColor: COLORS.surface,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 3,
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: COLORS.borderLight,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text,
    flex: 1,
    textAlign: 'center',
    marginHorizontal: 16,
  },
  headerAction: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  headerSpacer: {
    width: 40,
  },

  // Loading & Error States
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
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  errorIconContainer: {
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: COLORS.errorBg,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 24,
  },
  errorTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 8,
    textAlign: 'center',
  },
  errorText: {
    fontSize: 15,
    color: COLORS.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
    marginBottom: 32,
  },
  errorButton: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 16,
  },
  errorButtonText: {
    color: COLORS.surface,
    fontSize: 15,
    fontWeight: '700',
  },

  // ScrollView
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 20,
    paddingBottom: 100,
  },

  // Hero Card
  heroCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 24,
    padding: 24,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.12,
    shadowRadius: 16,
    elevation: 8,
  },
  heroHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 20,
  },
  serviceIcon: {
    width: 56,
    height: 56,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 16,
  },
  heroInfo: {
    flex: 1,
    justifyContent: 'center',
  },
  heroTitle: {
    fontSize: 20,
    fontWeight: '800',
    color: COLORS.text,
    marginBottom: 4,
    letterSpacing: 0.3,
  },
  heroSubtitle: {
    fontSize: 14,
    color: COLORS.textMuted,
    fontWeight: '600',
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 20,
    marginLeft: 12,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 8,
  },
  statusText: {
    fontSize: 13,
    fontWeight: '700',
    letterSpacing: 0.3,
  },

  // Hero Details
  heroDetails: {
    marginBottom: 16,
  },
  heroDetailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  heroDetailText: {
    fontSize: 16,
    color: COLORS.textSecondary,
    fontWeight: '600',
    marginLeft: 12,
    flex: 1,
  },

  // Timestamps
  timestampContainer: {
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: COLORS.borderLight,
  },
  timestampRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  timestampText: {
    fontSize: 13,
    color: COLORS.textMuted,
    marginLeft: 8,
    fontWeight: '500',
  },

  // Information Cards
  infoCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 20,
    padding: 20,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.06,
    shadowRadius: 12,
    elevation: 3,
  },
  cardTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text,
    marginLeft: 12,
    letterSpacing: 0.2,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  infoText: {
    fontSize: 15,
    color: COLORS.textSecondary,
    fontWeight: '500',
    marginLeft: 12,
    flex: 1,
    lineHeight: 22,
  },

  // Response Items
  responseItem: {
    backgroundColor: COLORS.borderLight,
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
  },
  responseLabel: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 8,
    letterSpacing: 0.2,
  },
  responseValue: {
    fontSize: 14,
    color: COLORS.textSecondary,
    lineHeight: 20,
    fontWeight: '500',
  },

  // Consultation Notes
  consultationNotesContainer: {
    backgroundColor: COLORS.infoBg,
    borderRadius: 12,
    padding: 16,
    borderLeftWidth: 4,
    borderLeftColor: COLORS.info,
  },
  consultationNotesText: {
    fontSize: 15,
    color: COLORS.text,
    lineHeight: 24,
    fontWeight: '500',
  },

  // No Data State
  noDataContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
  },
  noDataText: {
    fontSize: 15,
    color: COLORS.textMuted,
    fontStyle: 'italic',
    marginLeft: 10,
  },

  // Legacy styles for compatibility
  title: { fontSize: 20, fontWeight: '800', color: COLORS.text, marginBottom: 12 },
  card: { backgroundColor: COLORS.surface, borderRadius: 12, padding: 16, flexDirection: 'row', alignItems: 'center', marginBottom: 14, elevation: 2, shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 6 },
  cardMeta: { color: COLORS.textSecondary, marginTop: 6 },
  smallMeta: { color: COLORS.textMuted, marginTop: 8, fontSize: 12 },
  section: { backgroundColor: COLORS.surface, borderRadius: 12, padding: 16, marginBottom: 14 },
  sectionTitle: { fontSize: 16, fontWeight: '800', color: COLORS.text },
  sectionValue: { color: COLORS.textSecondary, marginTop: 8 },
  empty: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 20 },
  emptyText: { marginTop: 12, fontSize: 16, color: COLORS.textMuted },
});
