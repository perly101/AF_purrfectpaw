import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  Image,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Alert,
  StatusBar,
  Dimensions,
  TextInput,
} from 'react-native';
import { Ionicons, MaterialIcons, FontAwesome5 } from '@expo/vector-icons';
import { API } from '../src/api';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation } from '@react-navigation/native';
import { NativeStackNavigationProp } from '@react-navigation/native-stack';
import { RootStackParamList } from '../App';
import { LinearGradient } from 'expo-linear-gradient';

// Palette matched to Home/Settings
const PINK = '#FF6B8A';
const PURPLE = '#6C5CE7';
const WHITE = '#FFFFFF';
const DARK = '#2E2E36';
const LIGHT = '#F6F7FB';
const GRAY = '#9DA3B4';
const SHADOW = 'rgba(0,0,0,0.06)';
const SCREEN_WIDTH = Dimensions.get('window').width;

// Status colors
const OPEN_GREEN = '#27AE60';
const CLOSED_RED = '#E74C3C';

type Clinic = {
  id: number;
  clinic_name: string;
  profile_picture?: string | null;
  logo?: string | null;
  image_url?: string | null;
  address?: string | null;
  contact_number?: string | null;
  is_open: boolean;
};

export default function AppointmentsScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
  const [clinics, setClinics] = React.useState<Clinic[]>([]);
  const [filteredClinics, setFilteredClinics] = React.useState<Clinic[]>([]);
  const [loading, setLoading] = React.useState<boolean>(false);
  const [refreshing, setRefreshing] = React.useState<boolean>(false);
  const [selectedClinicId, setSelectedClinicId] = React.useState<number | null>(null);
  const [searchQuery, setSearchQuery] = React.useState<string>('');
  const [showSearchInput, setShowSearchInput] = React.useState<boolean>(false);

  // Base URL (without /api suffix)
  const hostBase = React.useMemo(() => {
    const base = (API.defaults && API.defaults.baseURL) || '';
    return base.replace(/\/$/, '').replace(/\/(api)?$/, '');
  }, []);

  // Convert Laravel paths into absolute URLs
  const toAbsoluteUrl = (maybePath?: string | null): string | undefined => {
    if (!maybePath) return undefined;
    if (/^https?:\/\//i.test(maybePath)) return maybePath;
    const path = maybePath.startsWith('/') ? maybePath : `/storage/${maybePath}`;
    return `${hostBase}${path}`;
  };

  // Function to fetch the clinic status directly
  const fetchClinicStatus = async (clinicId: number) => {
    try {
      const res = await API.get(`/clinic-status/${clinicId}`);
      return res.data?.is_open === true;
    } catch (e) {
      console.error(`Error fetching status for clinic ${clinicId}:`, e);
      return false;
    }
  };

  const fetchClinics = React.useCallback(async () => {
    try {
      if (!refreshing) setLoading(true);
      const res = await API.get('/clinics');
      const data = res.data?.data ?? res.data ?? [];

      let processedClinics = Array.isArray(data)
        ? data.map((clinic: any) => ({
            ...clinic,
            is_open: false, // default while we fetch statuses
          }))
        : [];

      setClinics(processedClinics);

      if (processedClinics.length > 0) {
        const updatedClinics = [...processedClinics];
        const statusPromises = updatedClinics.map(async (clinic, index) => {
          try {
            const isOpen = await fetchClinicStatus(clinic.id);
            updatedClinics[index] = { ...clinic, is_open: isOpen };
          } catch (e) {
            console.error(`Error updating status for clinic ${clinic.id}:`, e);
          }
        });

        await Promise.all(statusPromises);
        setClinics(updatedClinics);
        setFilteredClinics(updatedClinics); // Initialize filtered clinics
      }
    } catch (e) {
      console.error('Error fetching clinics:', e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [refreshing]);

  React.useEffect(() => {
    fetchClinics();
  }, [fetchClinics]);

  React.useEffect(() => {
    (async () => {
      try {
        const stored = await AsyncStorage.getItem('selectedClinic');
        if (stored) {
          const parsed = JSON.parse(stored);
          if (parsed?.id) setSelectedClinicId(Number(parsed.id));
        }
      } catch {}
    })();
  }, []);

  // Search filtering function
  const filterClinics = React.useCallback((query: string) => {
    if (!query.trim()) {
      setFilteredClinics(clinics);
      return;
    }

    const lowercaseQuery = query.toLowerCase().trim();
    const filtered = clinics.filter((clinic) => {
      const nameMatch = clinic.clinic_name?.toLowerCase().includes(lowercaseQuery);
      const addressMatch = clinic.address?.toLowerCase().includes(lowercaseQuery);
      const phoneMatch = clinic.contact_number?.includes(query.trim());
      
      return nameMatch || addressMatch || phoneMatch;
    });

    setFilteredClinics(filtered);
  }, [clinics]);

  // Effect to filter clinics when search query or clinics change
  React.useEffect(() => {
    filterClinics(searchQuery);
  }, [searchQuery, filterClinics]);

  const onRefresh = React.useCallback(() => {
    setRefreshing(true);
    fetchClinics();
  }, [fetchClinics]);

  const handleSelectClinic = (clinic: Clinic) => {
    Alert.alert(
      'Select Clinic',
      `Would you like to access ${clinic.clinic_name}?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Continue',
          style: 'default',
          onPress: async () => {
            try {
              const minimal = {
                id: clinic.id,
                clinic_name: clinic.clinic_name,
                image_url:
                  toAbsoluteUrl(clinic.image_url) ||
                  toAbsoluteUrl(clinic.logo) ||
                  toAbsoluteUrl(clinic.profile_picture) ||
                  null,
                address: clinic.address ?? null,
                contact_number: clinic.contact_number ?? null,
                is_open: clinic.is_open,
              };

              await AsyncStorage.setItem('selectedClinic', JSON.stringify(minimal));
              setSelectedClinicId(clinic.id);

              navigation.reset({
                index: 0,
                routes: [{ name: 'ClinicTabs' }],
              });
            } catch (error) {
              Alert.alert('Error', 'There was a problem selecting this clinic. Please try again.', [{ text: 'OK' }]);
            }
          },
        },
      ],
      { cancelable: true }
    );
  };

  const renderClinicCard = ({ item }: { item: Clinic }) => {
    const remoteUri =
      toAbsoluteUrl(item.image_url) || toAbsoluteUrl(item.logo) || toAbsoluteUrl(item.profile_picture);

    const isSelected = selectedClinicId === item.id;
    const isOpen = item.is_open;

    return (
      <TouchableOpacity
        style={[styles.card, isSelected && styles.cardSelected]}
        activeOpacity={0.85}
        onPress={() => handleSelectClinic(item)}
      >
        <View style={styles.cardImageContainer}>
          {remoteUri ? (
            <Image source={{ uri: remoteUri }} style={styles.cardImage} resizeMode="cover" />
          ) : (
            <View style={[styles.cardImage, styles.placeholderImage]}>
              <FontAwesome5 name="hospital-user" size={28} color={GRAY} />
            </View>
          )}

          {/* subtle overlay for selected */}
          {isSelected && (
            <View style={styles.selectedOverlay}>
              <Ionicons name="checkmark-circle" size={22} color={WHITE} />
            </View>
          )}
        </View>

        <View style={styles.cardContent}>
          <Text style={styles.cardTitle} numberOfLines={1}>
            {item.clinic_name}
          </Text>

          {!!item.address && (
            <View style={styles.addressContainer}>
              <MaterialIcons name="location-on" size={14} color={PURPLE} />
              <Text style={styles.cardSubtitle} numberOfLines={1}>
                {item.address}
              </Text>
            </View>
          )}

          {!!item.contact_number && (
            <View style={styles.contactContainer}>
              <MaterialIcons name="phone" size={12} color={PINK} />
              <Text style={styles.contactText} numberOfLines={1}>
                {item.contact_number}
              </Text>
            </View>
          )}

          <View style={styles.cardFooter}>
            <View style={[styles.cardBadge, isOpen ? styles.openBadge : styles.closedBadge]}>
              <View style={[styles.statusIndicator, isOpen ? styles.openIndicator : styles.closedIndicator]} />
              <Text style={[styles.cardBadgeText, isOpen ? styles.openText : styles.closedText]}>
                {isOpen ? 'Open Now' : 'Closed'}
              </Text>
            </View>

            <TouchableOpacity 
              style={styles.selectButton}
              onPress={() => handleSelectClinic(item)}
              activeOpacity={0.7}
            >
              <Text style={styles.selectButtonText}>Select Clinic</Text>
              <MaterialIcons name="arrow-forward" size={16} color={WHITE} />
            </TouchableOpacity>
          </View>
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <View style={styles.container}>
      <StatusBar backgroundColor={LIGHT} barStyle="dark-content" />

      <LinearGradient colors={[WHITE, LIGHT]} style={styles.headerContainer}>
        <View style={styles.headerContent}>
          <Text style={styles.header}>Available Clinics</Text>
          <Text style={styles.subheader}>Choose a clinic to access services and book appointments</Text>
        </View>

        <TouchableOpacity 
          style={styles.searchBar}
          onPress={() => setShowSearchInput(true)}
          activeOpacity={0.8}
        >
          <MaterialIcons name="search" size={20} color={PURPLE} />
          {showSearchInput ? (
            <TextInput
              style={styles.searchInput}
              placeholder="Find clinics by name or location"
              placeholderTextColor={GRAY}
              value={searchQuery}
              onChangeText={setSearchQuery}
              autoFocus={true}
              onBlur={() => {
                if (!searchQuery.trim()) {
                  setShowSearchInput(false);
                }
              }}
              returnKeyType="search"
              clearButtonMode="while-editing"
            />
          ) : (
            <Text style={styles.searchPlaceholder}>Find clinics by name or location</Text>
          )}
          {searchQuery ? (
            <TouchableOpacity 
              onPress={() => {
                setSearchQuery('');
                setShowSearchInput(false);
              }}
              style={styles.clearButton}
            >
              <MaterialIcons name="close" size={18} color={GRAY} />
            </TouchableOpacity>
          ) : (
            <MaterialIcons name="tune" size={18} color={GRAY} />
          )}
        </TouchableOpacity>
      </LinearGradient>

      {loading && clinics.length === 0 ? (
        <View style={styles.loadingWrap}>
          <ActivityIndicator size="large" color={PURPLE} />
          <Text style={styles.loadingText}>Loading available clinics...</Text>
        </View>
      ) : (
        <FlatList
          data={filteredClinics}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          columnWrapperStyle={filteredClinics.length % 2 !== 0 ? null : styles.row}
          contentContainerStyle={styles.listContent}
          renderItem={renderClinicCard}
          ListHeaderComponent={
            <View style={styles.listHeader}>
              <View style={styles.statsContainer}>
                <View style={styles.statItem}>
                  <Text style={styles.statValue}>{filteredClinics.length}</Text>
                  <Text style={styles.statLabel}>{searchQuery ? 'Found' : 'Clinics'}</Text>
                </View>
                <View style={styles.statDivider} />
                <View style={styles.statItem}>
                  <Text style={styles.statValue}>{selectedClinicId ? '1' : '0'}</Text>
                  <Text style={styles.statLabel}>Selected</Text>
                </View>
              </View>
            </View>
          }
          ListEmptyComponent={
            <View style={styles.emptyWrap}>
              <FontAwesome5 
                name={searchQuery ? "search" : "hospital-alt"} 
                size={46} 
                color={GRAY} 
              />
              <Text style={styles.emptyTitle}>
                {searchQuery ? 'No Clinics Found' : 'No Clinics Available'}
              </Text>
              <Text style={styles.emptyText}>
                {searchQuery 
                  ? `No clinics match "${searchQuery}". Try a different search term.`
                  : 'We couldn\'t find any clinics at the moment.'
                }
              </Text>
              {searchQuery ? (
                <TouchableOpacity 
                  style={styles.refreshButton} 
                  onPress={() => {
                    setSearchQuery('');
                    setShowSearchInput(false);
                  }} 
                  activeOpacity={0.8}
                >
                  <Text style={styles.refreshButtonText}>Clear Search</Text>
                </TouchableOpacity>
              ) : (
                <TouchableOpacity style={styles.refreshButton} onPress={onRefresh} activeOpacity={0.8}>
                  <Text style={styles.refreshButtonText}>Refresh</Text>
                </TouchableOpacity>
              )}
            </View>
          }
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={PURPLE} colors={[PURPLE, PINK]} />}
          showsVerticalScrollIndicator={false}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: LIGHT,
  },

  headerContainer: {
    paddingTop: 20,
    paddingBottom: 20,
    paddingHorizontal: 24,
    borderBottomLeftRadius: 20,
    borderBottomRightRadius: 20,
    marginBottom: 12,
    shadowColor: 'rgba(0,0,0,0.08)',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.12,
    shadowRadius: 12,
    elevation: 6,
  },
  headerContent: {
    marginTop: 20,
    marginBottom: 16,
  },
  header: {
    fontSize: 28,
    fontWeight: '800',
    color: DARK,
    marginBottom: 6,
    letterSpacing: 0.5,
  },
  subheader: {
    fontSize: 14,
    color: GRAY,
    lineHeight: 20,
    fontWeight: '500',
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: WHITE,
    borderRadius: 16,
    paddingHorizontal: 16,
    paddingVertical: 12,
    marginTop: 4,
    shadowColor: 'rgba(108, 92, 231, 0.15)',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 4,
    borderWidth: 1,
    borderColor: 'rgba(108, 92, 231, 0.1)',
  },
  searchPlaceholder: {
    flex: 1,
    marginLeft: 12,
    fontSize: 14,
    color: GRAY,
    fontWeight: '500',
  },
  searchInput: {
    flex: 1,
    marginLeft: 12,
    fontSize: 14,
    color: DARK,
    fontWeight: '500',
    paddingVertical: 0, // Remove default padding on iOS
  },
  clearButton: {
    padding: 4,
    borderRadius: 12,
    backgroundColor: 'rgba(0,0,0,0.05)',
  },

  listHeader: {
    paddingHorizontal: 12,
    marginBottom: 16,
  },
  statsContainer: {
    flexDirection: 'row',
    backgroundColor: WHITE,
    borderRadius: 16,
    paddingVertical: 16,
    marginHorizontal: 4,
    shadowColor: 'rgba(0,0,0,0.08)',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 4,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.03)',
  },
  statItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  statValue: {
    fontSize: 22,
    fontWeight: '800',
    color: PURPLE,
    marginBottom: 6,
    letterSpacing: 0.3,
  },
  statLabel: {
    fontSize: 12,
    color: GRAY,
    textTransform: 'uppercase',
    letterSpacing: 1,
    fontWeight: '600',
  },
  statDivider: {
    width: 1,
    height: '70%',
    backgroundColor: 'rgba(0,0,0,0.08)',
    alignSelf: 'center',
  },

  listContent: {
    paddingHorizontal: 15,
    paddingBottom: 28,
    paddingTop: 6,
  },
  row: {
    justifyContent: 'space-between',
    marginBottom: 14,
  },

  loadingWrap: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    marginTop: 12,
    color: DARK,
    fontSize: 15,
  },

  emptyWrap: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 80,
    paddingHorizontal: 28,
  },
  emptyTitle: {
    marginTop: 12,
    fontSize: 18,
    fontWeight: '700',
    color: DARK,
  },
  emptyText: {
    marginTop: 8,
    fontSize: 14,
    color: GRAY,
    textAlign: 'center',
    lineHeight: 20,
  },
  refreshButton: {
    marginTop: 18,
    paddingVertical: 10,
    paddingHorizontal: 22,
    backgroundColor: PURPLE,
    borderRadius: 999,
    shadowColor: SHADOW,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.12,
    shadowRadius: 4,
    elevation: 3,
  },
  refreshButtonText: {
    color: WHITE,
    fontWeight: '700',
    fontSize: 14,
  },

  // Card
  card: {
    backgroundColor: WHITE,
    width: '48%',
    borderRadius: 16,
    marginBottom: 12,
    shadowColor: 'rgba(0,0,0,0.08)',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.12,
    shadowRadius: 12,
    elevation: 6,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.02)',
  },
  cardSelected: {
    borderWidth: 2,
    borderColor: PURPLE,
    shadowColor: PURPLE,
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 8,
    transform: [{ scale: 1.02 }],
  },

  cardImageContainer: {
    position: 'relative',
    backgroundColor: '#f8f9fb',
  },
  cardImage: {
    width: '100%',
    height: 120,
    backgroundColor: '#f2f4f7',
  },
  placeholderImage: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(108, 92, 231, 0.05)',
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(0,0,0,0.04)',
  },
  selectedOverlay: {
    position: 'absolute',
    top: 10,
    right: 10,
    backgroundColor: PURPLE,
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.25,
    shadowRadius: 4,
    elevation: 4,
    borderWidth: 2,
    borderColor: WHITE,
  },

  cardContent: {
    padding: 16,
  },
  cardTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: DARK,
    marginBottom: 8,
    letterSpacing: 0.2,
  },
  addressContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  cardSubtitle: {
    fontSize: 12,
    color: GRAY,
    marginLeft: 6,
    flex: 1,
  },
  contactContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  contactText: {
    fontSize: 12,
    color: GRAY,
    marginLeft: 6,
  },

  cardFooter: {
    flexDirection: 'column',
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.06)',
  },
  cardBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
    marginBottom: 10,
  },
  statusIndicator: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 6,
  },
  openBadge: {
    backgroundColor: 'rgba(39,174,96,0.1)',
    borderWidth: 1,
    borderColor: 'rgba(39,174,96,0.2)',
  },
  closedBadge: {
    backgroundColor: 'rgba(231,76,60,0.1)',
    borderWidth: 1,
    borderColor: 'rgba(231,76,60,0.2)',
  },
  openIndicator: {
    backgroundColor: OPEN_GREEN,
  },
  closedIndicator: {
    backgroundColor: CLOSED_RED,
  },
  cardBadgeText: {
    fontSize: 12,
    fontWeight: '600',
    letterSpacing: 0.3,
  },
  openText: {
    color: OPEN_GREEN,
  },
  closedText: {
    color: CLOSED_RED,
  },

  selectButton: {
    backgroundColor: PURPLE,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    paddingHorizontal: 16,
    borderRadius: 12,
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 4,
    elevation: 3,
  },
  selectButtonText: {
    fontSize: 13,
    fontWeight: '700',
    color: WHITE,
    marginRight: 6,
    letterSpacing: 0.3,
  },
});
