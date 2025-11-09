import React from 'react';
import { 
  View, 
  Text, 
  StyleSheet, 
  ImageBackground, 
  Image, 
  ScrollView, 
  ActivityIndicator, 
  RefreshControl, 
  FlatList,
  SafeAreaView,
  TouchableOpacity,
  Dimensions
} from 'react-native';
import { MaterialCommunityIcons, Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API } from '../src/api';

// Modern color palette - consistent with other screens
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

const { width } = Dimensions.get('window');

type Homepage = {
  hero_title?: string | null;
  hero_subtitle?: string | null;
  hero_image?: string | null; // path or url
  about_text?: string | null;
  announcement_title?: string | null;
  announcement_body?: string | null;
  announcement_image?: string | null; // path or url
};

type Service = {
  id: number;
  name: string;
  description?: string | null;
  price?: number | string | null;
  image_path?: string | null;
  image_url?: string | null;
  is_active?: boolean;
};

export default function ClinicHomeScreen() {
  const [clinicId, setClinicId] = React.useState<number | null>(null);
  const [clinicName, setClinicName] = React.useState<string>('Clinic');
  const [homepage, setHomepage] = React.useState<Homepage | null>(null);
  const [services, setServices] = React.useState<Service[]>([]);
  const [loading, setLoading] = React.useState<boolean>(false);
  const [refreshing, setRefreshing] = React.useState<boolean>(false);

  // Base URL (without /api suffix)
  const hostBase = React.useMemo(() => {
    const base = API.defaults.baseURL || '';
    return base.replace(/\/$/, '').replace(/\/(api)?$/, '');
  }, []);

  const toAbsoluteUrl = (maybePath?: string | null): string | undefined => {
    if (!maybePath) return undefined;
    if (/^https?:\/\//i.test(maybePath)) return maybePath;
    const path = maybePath.startsWith('/') ? maybePath : `/storage/${maybePath}`;
    return `${hostBase}${path}`;
  };

  React.useEffect(() => {
    (async () => {
      try {
        const stored = await AsyncStorage.getItem('selectedClinic');
        const parsed = stored ? JSON.parse(stored) : null;
        setClinicId(parsed?.id ?? null);
        setClinicName(parsed?.clinic_name || 'Clinic');
      } catch {}
    })();
  }, []);

  const fetchHomepage = React.useCallback(async (id: number) => {
    try {
      if (!refreshing) setLoading(true);
      // Expecting backend endpoint to return { homepage: {...}, services: [...] }
      const res = await API.get(`/clinics/${id}/homepage`);
      const data = res.data || {};
      setHomepage(data.homepage || data);
      setServices(Array.isArray(data.services) ? data.services : (data.services?.data ?? []));
    } catch (e) {
      // keep UI calm
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [refreshing]);

  React.useEffect(() => {
    if (clinicId != null) fetchHomepage(clinicId);
  }, [clinicId, fetchHomepage]);

  const onRefresh = React.useCallback(() => {
    if (clinicId == null) return;
    setRefreshing(true);
    fetchHomepage(clinicId);
  }, [clinicId, fetchHomepage]);

  const heroUri = toAbsoluteUrl(homepage?.hero_image);
  const annImageUri = toAbsoluteUrl(homepage?.announcement_image);

  const getServiceIcon = (serviceName: string) => {
    const name = serviceName.toLowerCase();
    if (name.includes('check') || name.includes('exam')) return 'stethoscope';
    if (name.includes('vaccin')) return 'needle';
    if (name.includes('dental') || name.includes('teeth')) return 'tooth';
    if (name.includes('surgery') || name.includes('operation')) return 'medical-bag';
    if (name.includes('grooming') || name.includes('bath')) return 'content-cut';
    if (name.includes('emergency')) return 'ambulance';
    if (name.includes('consultation')) return 'doctor';
    return 'medical-bag';
  };

  const renderService = ({ item, index }: { item: Service; index: number }) => {
    const uri = toAbsoluteUrl(item.image_url) || toAbsoluteUrl(item.image_path);
    const isLastRow = Math.floor(index / 2) === Math.floor((services.length - 1) / 2);
    
    return (
      <TouchableOpacity 
        style={[styles.serviceCard, { marginBottom: isLastRow ? 0 : 16 }]} 
        activeOpacity={0.8}
      >
        <View style={styles.serviceImageContainer}>
          {uri ? (
            <Image source={{ uri }} style={styles.serviceImage} resizeMode="cover" />
          ) : (
            <View style={styles.servicePlaceholder}>
              <MaterialCommunityIcons 
                name={getServiceIcon(item.name) as any} 
                size={32} 
                color={COLORS.primary} 
              />
            </View>
          )}
          {item.is_active === false && (
            <View style={styles.inactiveOverlay}>
              <MaterialCommunityIcons name="pause-circle" size={20} color={COLORS.error} />
            </View>
          )}
        </View>
        
        <View style={styles.serviceContent}>
          <Text style={styles.serviceName} numberOfLines={1}>{item.name}</Text>
          {!!item.description && (
            <Text style={styles.serviceDescription} numberOfLines={2}>{item.description}</Text>
          )}
          
          <View style={styles.serviceMeta}>
            {item.price != null && (
              <View style={styles.priceContainer}>
                <MaterialCommunityIcons name="currency-php" size={14} color={COLORS.success} />
                <Text style={styles.servicePrice}>
                  {Number(item.price).toFixed(2)}
                </Text>
              </View>
            )}
            {item.is_active === false && (
              <View style={styles.statusBadge}>
                <Text style={styles.statusText}>Inactive</Text>
              </View>
            )}
          </View>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Enhanced Header */}
      <View style={styles.header}>
        <View style={styles.headerContent}>
          <View style={styles.clinicInfo}>
            <MaterialCommunityIcons name="hospital-building" size={20} color="#4F46E5" />
            <View style={styles.clinicDetails}>
              <Text style={styles.clinicName}>{clinicName}</Text>
              <Text style={styles.clinicSubtitle}>Veterinary Clinic</Text>
            </View>
          </View>
          <TouchableOpacity style={styles.headerAction}>
            <MaterialCommunityIcons name="bell-outline" size={20} color="#6B7280" />
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView 
        style={styles.scrollView} 
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl 
            refreshing={refreshing} 
            onRefresh={onRefresh} 
            tintColor={COLORS.primary}
            colors={[COLORS.primary]}
          />
        }
        showsVerticalScrollIndicator={false}
      >
        {/* Hero Section */}
        <View style={styles.heroContainer}>
          {heroUri ? (
            <ImageBackground source={{ uri: heroUri }} style={styles.heroBackground} imageStyle={styles.heroImageStyle}>
              <View style={styles.heroOverlay}>
                <View style={styles.heroContent}>
                  <Text style={styles.heroTitle}>{homepage?.hero_title || `Welcome to ${clinicName}`}</Text>
                  {!!homepage?.hero_subtitle && (
                    <Text style={styles.heroSubtitle}>{homepage?.hero_subtitle}</Text>
                  )}
                </View>
              </View>
            </ImageBackground>
          ) : (
            <View style={styles.heroFallback}>
              <View style={styles.heroGradient}>
                <Text style={styles.heroTitle}>{homepage?.hero_title || `Welcome to ${clinicName}`}</Text>
                {!!homepage?.hero_subtitle && (
                  <Text style={styles.heroSubtitle}>{homepage?.hero_subtitle}</Text>
                )}
              </View>
            </View>
          )}
        </View>

        {/* Loading State */}
        {loading && !homepage && (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color={COLORS.primary} />
            <Text style={styles.loadingText}>Loading clinic information...</Text>
          </View>
        )}

        {/* Announcement Card */}
        {(homepage?.announcement_title || homepage?.announcement_body || annImageUri) && (
          <View style={styles.announcementCard}>
            <View style={styles.cardHeader}>
              <MaterialCommunityIcons name="bullhorn" size={22} color={COLORS.warning} />
              <Text style={styles.cardTitle}>{homepage?.announcement_title || 'Announcement'}</Text>
            </View>
            <View style={styles.announcementContent}>
              {annImageUri && (
                <View style={styles.announcementImageContainer}>
                  <Image source={{ uri: annImageUri }} style={styles.announcementImage} resizeMode="cover" />
                </View>
              )}
              <View style={styles.announcementText}>
                <Text style={styles.announcementBody}>
                  {homepage?.announcement_body || 'No announcements at this time.'}
                </Text>
              </View>
            </View>
          </View>
        )}

        {/* About Section */}
        <View style={styles.infoCard}>
          <View style={styles.cardHeader}>
            <MaterialCommunityIcons name="information" size={22} color={COLORS.info} />
            <Text style={styles.cardTitle}>About {clinicName}</Text>
          </View>
          <Text style={styles.aboutText}>
            {homepage?.about_text || 'Dedicated to providing exceptional veterinary care for your beloved pets. Our experienced team is committed to ensuring the health and happiness of your furry family members.'}
          </Text>
        </View>

        {/* Services Section */}
        <View style={styles.servicesCard}>
          <View style={styles.cardHeader}>
            <MaterialCommunityIcons name="medical-bag" size={22} color={COLORS.success} />
            <Text style={styles.cardTitle}>Our Services</Text>
            <Text style={styles.serviceCount}>({services.length})</Text>
          </View>
          
          {services.length === 0 ? (
            <View style={styles.emptyServices}>
              <MaterialCommunityIcons name="medical-bag" size={48} color={COLORS.textMuted} />
              <Text style={styles.emptyServicesText}>No services available yet</Text>
              <Text style={styles.emptyServicesSubtext}>Services will be displayed here once added</Text>
            </View>
          ) : (
            <View style={styles.servicesGrid}>
              {services.map((item, index) => renderService({ item, index }))}
            </View>
          )}
        </View>

        {/* Bottom Spacing */}
        <View style={styles.bottomSpacing} />
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },

  // Minimalist Header
  header: {
    backgroundColor: '#FFFFFF',
    paddingTop: 50,
    paddingBottom: 24,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  clinicInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  clinicDetails: {
    marginLeft: 8,
    flex: 1,
  },
  clinicName: {
    fontSize: 18,
    fontWeight: '500',
    color: '#111827',
    letterSpacing: -0.2,
  },
  clinicSubtitle: {
    fontSize: 12,
    color: '#6B7280',
    fontWeight: '400',
    marginTop: 2,
  },
  headerAction: {
    width: 36,
    height: 36,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F9FAFB',
  },

  // ScrollView
  scrollView: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 32,
  },

  // Hero Section
  heroContainer: {
    paddingHorizontal: 20,
    paddingTop: 20,
    marginBottom: 24,
  },
  heroBackground: {
    height: 200,
    borderRadius: 24,
    overflow: 'hidden',
    justifyContent: 'flex-end',
  },
  heroImageStyle: {
    borderRadius: 24,
  },
  heroOverlay: {
    backgroundColor: 'rgba(0,0,0,0.4)',
    borderRadius: 24,
  },
  heroContent: {
    padding: 24,
  },
  heroFallback: {
    height: 200,
    borderRadius: 24,
    overflow: 'hidden',
    justifyContent: 'center',
    alignItems: 'center',
  },
  heroGradient: {
    backgroundColor: COLORS.primary,
    width: '100%',
    height: '100%',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 24,
    borderRadius: 24,
  },
  heroTitle: {
    color: COLORS.surface,
    fontSize: 24,
    fontWeight: '800',
    textAlign: 'center',
    letterSpacing: 0.5,
    marginBottom: 8,
  },
  heroSubtitle: {
    color: 'rgba(255,255,255,0.9)',
    fontSize: 16,
    fontWeight: '500',
    textAlign: 'center',
    lineHeight: 24,
  },

  // Loading State
  loadingContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 40,
    marginHorizontal: 20,
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: COLORS.textSecondary,
    fontWeight: '500',
  },

  // Card Styles
  announcementCard: {
    backgroundColor: COLORS.surface,
    marginHorizontal: 20,
    marginBottom: 20,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 4,
  },
  infoCard: {
    backgroundColor: COLORS.surface,
    marginHorizontal: 20,
    marginBottom: 20,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 4,
  },
  servicesCard: {
    backgroundColor: COLORS.surface,
    marginHorizontal: 20,
    marginBottom: 20,
    borderRadius: 20,
    padding: 20,
    borderWidth: 1,
    borderColor: COLORS.borderLight,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 4,
  },

  // Card Headers
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: COLORS.text,
    marginLeft: 12,
    flex: 1,
    letterSpacing: 0.3,
  },
  serviceCount: {
    fontSize: 14,
    color: COLORS.textMuted,
    fontWeight: '600',
  },

  // Announcement Content
  announcementContent: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  announcementImageContainer: {
    marginRight: 16,
  },
  announcementImage: {
    width: 80,
    height: 80,
    borderRadius: 16,
    backgroundColor: COLORS.borderLight,
  },
  announcementText: {
    flex: 1,
  },
  announcementBody: {
    fontSize: 15,
    color: COLORS.textSecondary,
    lineHeight: 22,
    fontWeight: '500',
  },

  // About Text
  aboutText: {
    fontSize: 15,
    color: COLORS.textSecondary,
    lineHeight: 24,
    fontWeight: '500',
  },

  // Services Grid
  servicesGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  serviceCard: {
    backgroundColor: COLORS.surfaceElevated,
    borderRadius: 16,
    width: (width - 80) / 2,
    borderWidth: 1,
    borderColor: COLORS.border,
    shadowColor: COLORS.text,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 3,
  },
  serviceImageContainer: {
    position: 'relative',
  },
  serviceImage: {
    width: '100%',
    height: 100,
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
  },
  servicePlaceholder: {
    width: '100%',
    height: 100,
    backgroundColor: COLORS.primaryLight + '15',
    alignItems: 'center',
    justifyContent: 'center',
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
  },
  inactiveOverlay: {
    position: 'absolute',
    top: 8,
    right: 8,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: COLORS.errorBg,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: COLORS.error,
  },
  serviceContent: {
    padding: 16,
  },
  serviceName: {
    fontSize: 16,
    fontWeight: '700',
    color: COLORS.text,
    marginBottom: 8,
    letterSpacing: 0.2,
  },
  serviceDescription: {
    fontSize: 13,
    color: COLORS.textSecondary,
    lineHeight: 18,
    marginBottom: 12,
    fontWeight: '500',
  },
  serviceMeta: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  priceContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.successBg,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  servicePrice: {
    fontSize: 14,
    fontWeight: '700',
    color: COLORS.success,
    marginLeft: 2,
  },
  statusBadge: {
    backgroundColor: COLORS.errorBg,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
    color: COLORS.error,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },

  // Empty Services State
  emptyServices: {
    alignItems: 'center',
    paddingVertical: 40,
  },
  emptyServicesText: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.textSecondary,
    marginTop: 16,
    marginBottom: 8,
  },
  emptyServicesSubtext: {
    fontSize: 14,
    color: COLORS.textMuted,
    textAlign: 'center',
    fontWeight: '500',
  },

  // Bottom Spacing
  bottomSpacing: {
    height: 32,
  },

  // Legacy styles for compatibility
  bg: { flex: 1, backgroundColor: COLORS.background },
  loadingWrap: { alignItems: 'center', justifyContent: 'center', paddingVertical: 20 },
  card: { backgroundColor: COLORS.surface, marginHorizontal: 16, marginTop: 12, borderRadius: 16, padding: 14, shadowColor: COLORS.primary, shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.07, shadowRadius: 12, elevation: 3 },
  sectionTitle: { color: COLORS.primary, fontWeight: 'bold', fontSize: 16, marginBottom: 6 },
  hero: { height: 180, borderRadius: 18, overflow: 'hidden', justifyContent: 'flex-end' },
  heroWrap: { paddingHorizontal: 16, paddingTop: 16, marginTop: 40 },
  annRow: { flexDirection: 'row', alignItems: 'flex-start', gap: 12 },
  annImage: { width: 76, height: 76, borderRadius: 12, backgroundColor: '#f2f2f2', marginRight: 10 },
  annPlaceholder: { backgroundColor: '#f6e7f0' },
  annBody: { color: COLORS.text, marginTop: 2 },
  serviceBody: { padding: 10 },
  serviceDesc: { color: '#666', fontSize: 12, marginTop: 4 },
  serviceTag: { marginTop: 6, alignSelf: 'flex-start', backgroundColor: '#f1f1f1', color: '#666', fontSize: 10, paddingHorizontal: 6, paddingVertical: 2, borderRadius: 8 },
}); 