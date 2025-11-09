import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  Alert,
  StyleSheet,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRoute, useNavigation } from '@react-navigation/native';
import { Ionicons } from '@expo/vector-icons';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { API } from '../src/api';

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
  white: '#FFFFFF',
  divider: '#E5E7EB',
};

interface ReceiptData {
  id: number;
  receipt_number: string;
  appointment_id: number;
  patient_name: string;
  service_description: string;
  amount: number;
  payment_method: string;
  payment_date: string;
  notes?: string;
  processed_by?: string;
  doctor_name?: string;
  clinic: {
    name: string;
    address: string;
    contact_number: string;
    email: string;
  };
  appointment: {
    date: string;
    time: string;
    status: string;
    owner_phone: string;
  };
  service_details: Array<{
    field: string;
    value: string;
  }>;
  formatted: {
    amount_display: string;
    payment_date_display: string;
    appointment_date_display: string;
    appointment_time_display: string;
    payment_method_display: string;
  };
  has_receipt: boolean;
}

export default function AppointmentReceipt() {
  const route = useRoute<any>();
  const navigation = useNavigation<any>();
  const { appointmentId } = route.params || {};
  
  const [loading, setLoading] = useState(true);
  const [receipt, setReceipt] = useState<ReceiptData | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchReceipt();
  }, [appointmentId]);

  const fetchReceipt = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await API.get(`/profile/appointments/${appointmentId}/receipt`);
      
      if (response.data && response.data.data) {
        setReceipt(response.data.data);
      } else {
        setError('Receipt not found or payment is still pending.');
      }
    } catch (err: any) {
      console.error('Receipt fetch error:', err);
      if (err.response?.status === 404) {
        setError('Receipt not found. Payment may still be pending.');
      } else {
        setError('Failed to load receipt. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  const handleShare = () => {
    Alert.alert('Share Receipt', 'Sharing functionality will be available soon.');
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.headerContainer}>
          <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
            <Ionicons name="chevron-back" size={24} color={COLORS.text} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Payment Receipt</Text>
          <View style={styles.headerSpacer} />
        </View>
        
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={COLORS.primary} />
          <Text style={styles.loadingText}>Loading receipt...</Text>
        </View>
      </SafeAreaView>
    );
  }

  if (error || !receipt) {
    return (
      <SafeAreaView style={styles.container}>
        <View style={styles.headerContainer}>
          <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
            <Ionicons name="chevron-back" size={24} color={COLORS.text} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Payment Receipt</Text>
          <View style={styles.headerSpacer} />
        </View>
        
        <View style={styles.errorContainer}>
          <View style={styles.errorIconContainer}>
            <MaterialCommunityIcons name="receipt" size={64} color={COLORS.textMuted} />
          </View>
          <Text style={styles.errorTitle}>Receipt Not Available</Text>
          <Text style={styles.errorText}>
            {error || 'This appointment does not have a payment receipt yet.'}
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
      <View style={styles.headerContainer}>
        <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
          <Ionicons name="chevron-back" size={24} color={COLORS.text} />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Payment Receipt</Text>
        <TouchableOpacity style={styles.shareButton} onPress={handleShare}>
          <MaterialCommunityIcons name="share-variant" size={22} color={COLORS.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.scrollView} contentContainerStyle={styles.scrollContent}>
        {/* Receipt Header */}
        <View style={styles.receiptCard}>
          <View style={styles.receiptHeader}>
            <View style={styles.receiptIconContainer}>
              <MaterialCommunityIcons name="receipt" size={32} color={COLORS.primary} />
            </View>
            <View style={styles.receiptHeaderInfo}>
              <Text style={styles.receiptTitle}>Payment Receipt</Text>
              <Text style={styles.receiptNumber}>#{receipt.receipt_number}</Text>
            </View>
            <View style={styles.statusContainer}>
              <View style={[styles.statusBadge, { backgroundColor: COLORS.successBg }]}>
                <MaterialCommunityIcons name="check-circle" size={16} color={COLORS.success} />
                <Text style={[styles.statusText, { color: COLORS.success }]}>Paid</Text>
              </View>
            </View>
          </View>

          {/* Clinic Info */}
          <View style={styles.divider} />
          <View style={styles.clinicSection}>
            <Text style={styles.clinicName}>{receipt.clinic.name}</Text>
            <Text style={styles.clinicAddress}>{receipt.clinic.address}</Text>
            <Text style={styles.clinicContact}>
              {receipt.clinic.contact_number} • {receipt.clinic.email}
            </Text>
          </View>
        </View>

        {/* Payment Summary */}
        <View style={styles.summaryCard}>
          <Text style={styles.cardTitle}>Payment Summary</Text>
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Amount Paid</Text>
            <Text style={styles.summaryAmount}>{receipt.formatted.amount_display}</Text>
          </View>
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Payment Method</Text>
            <Text style={styles.summaryValue}>{receipt.formatted.payment_method_display}</Text>
          </View>
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Payment Date</Text>
            <Text style={styles.summaryValue}>{receipt.formatted.payment_date_display}</Text>
          </View>
          {receipt.processed_by && (
            <View style={styles.summaryRow}>
              <Text style={styles.summaryLabel}>Processed By</Text>
              <Text style={styles.summaryValue}>{receipt.processed_by}</Text>
            </View>
          )}
        </View>

        {/* Service Details */}
        <View style={styles.serviceCard}>
          <Text style={styles.cardTitle}>Service Details</Text>
          <View style={styles.serviceRow}>
            <Text style={styles.serviceLabel}>Patient Name</Text>
            <Text style={styles.serviceValue}>{receipt.patient_name}</Text>
          </View>
          <View style={styles.serviceRow}>
            <Text style={styles.serviceLabel}>Service</Text>
            <Text style={styles.serviceValue}>{receipt.service_description}</Text>
          </View>
          <View style={styles.serviceRow}>
            <Text style={styles.serviceLabel}>Appointment Date</Text>
            <Text style={styles.serviceValue}>
              {receipt.formatted.appointment_date_display} at {receipt.formatted.appointment_time_display}
            </Text>
          </View>
          {receipt.doctor_name && (
            <View style={styles.serviceRow}>
              <Text style={styles.serviceLabel}>Doctor</Text>
              <Text style={styles.serviceValue}>{receipt.doctor_name}</Text>
            </View>
          )}
        </View>

        {/* Service Form Details */}
        {receipt.service_details && receipt.service_details.length > 0 && (
          <View style={styles.detailsCard}>
            <Text style={styles.cardTitle}>Additional Information</Text>
            {receipt.service_details.map((detail, index) => (
              <View key={index} style={styles.detailRow}>
                <Text style={styles.detailLabel}>{detail.field}</Text>
                <Text style={styles.detailValue}>{detail.value}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Notes */}
        {receipt.notes && (
          <View style={styles.notesCard}>
            <Text style={styles.cardTitle}>Notes</Text>
            <Text style={styles.notesText}>{receipt.notes}</Text>
          </View>
        )}

        {/* Footer */}
        <View style={styles.footerCard}>
          <Text style={styles.footerText}>
            This is an official payment receipt for veterinary services.
          </Text>
          <Text style={styles.footerSubText}>
            Receipt generated on {receipt.formatted.payment_date_display}
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  headerContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: COLORS.surface,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 4,
    shadowOffset: { width: 0, height: 1 },
  },
  backButton: {
    padding: 8,
    borderRadius: 8,
    backgroundColor: COLORS.background,
  },
  shareButton: {
    padding: 8,
    borderRadius: 8,
    backgroundColor: COLORS.background,
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text,
    flex: 1,
    textAlign: 'center',
  },
  headerSpacer: {
    width: 40,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 16,
    color: COLORS.textMuted,
  },
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  errorIconContainer: {
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
    fontSize: 16,
    color: COLORS.textMuted,
    textAlign: 'center',
    lineHeight: 24,
    marginBottom: 24,
  },
  errorButton: {
    backgroundColor: COLORS.primary,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 8,
  },
  errorButtonText: {
    color: COLORS.white,
    fontSize: 16,
    fontWeight: '600',
  },
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
  },

  // Receipt Card Styles
  receiptCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    marginBottom: 16,
    elevation: 3,
    shadowColor: '#000',
    shadowOpacity: 0.08,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
  },
  receiptHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 20,
  },
  receiptIconContainer: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: COLORS.infoBg,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 16,
  },
  receiptHeaderInfo: {
    flex: 1,
  },
  receiptTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 4,
  },
  receiptNumber: {
    fontSize: 14,
    color: COLORS.textMuted,
    fontWeight: '500',
  },
  statusContainer: {
    alignItems: 'flex-end',
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
  },
  statusText: {
    marginLeft: 6,
    fontSize: 14,
    fontWeight: '600',
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.border,
    marginHorizontal: 20,
  },
  clinicSection: {
    padding: 20,
  },
  clinicName: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 4,
  },
  clinicAddress: {
    fontSize: 14,
    color: COLORS.textSecondary,
    marginBottom: 4,
  },
  clinicContact: {
    fontSize: 14,
    color: COLORS.textMuted,
  },

  // Card Styles
  summaryCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 },
  },
  serviceCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 },
  },
  detailsCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 },
  },
  notesCard: {
    backgroundColor: COLORS.surface,
    borderRadius: 16,
    padding: 20,
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 },
  },
  footerCard: {
    backgroundColor: COLORS.surfaceElevated,
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
    marginBottom: 20,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 16,
  },

  // Summary Rows
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  summaryLabel: {
    fontSize: 15,
    color: COLORS.textSecondary,
    fontWeight: '500',
  },
  summaryAmount: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.primary,
  },
  summaryValue: {
    fontSize: 15,
    color: COLORS.text,
    fontWeight: '600',
    textAlign: 'right',
    flex: 1,
    marginLeft: 16,
  },

  // Service Rows
  serviceRow: {
    marginBottom: 12,
  },
  serviceLabel: {
    fontSize: 14,
    color: COLORS.textMuted,
    fontWeight: '500',
    marginBottom: 4,
  },
  serviceValue: {
    fontSize: 15,
    color: COLORS.text,
    fontWeight: '600',
  },

  // Detail Rows
  detailRow: {
    marginBottom: 12,
  },
  detailLabel: {
    fontSize: 14,
    color: COLORS.textMuted,
    fontWeight: '500',
    marginBottom: 4,
  },
  detailValue: {
    fontSize: 15,
    color: COLORS.text,
    fontWeight: '500',
  },

  // Notes
  notesText: {
    fontSize: 15,
    color: COLORS.text,
    lineHeight: 22,
  },

  // Footer
  footerText: {
    fontSize: 14,
    color: COLORS.textMuted,
    textAlign: 'center',
    marginBottom: 6,
  },
  footerSubText: {
    fontSize: 12,
    color: COLORS.textMuted,
    textAlign: 'center',
  },
});