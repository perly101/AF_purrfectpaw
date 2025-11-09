import React from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput, TouchableOpacity, ActivityIndicator, Modal, FlatList, RefreshControl, Platform, Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Ionicons } from '@expo/vector-icons';
import { API } from '../src/api';
import DateTimePicker from '@react-native-community/datetimepicker';
import { format } from 'date-fns';

// Minimalist & Elegant Color Palette
const PURPLE = '#4F46E5';
const WHITE = '#FFFFFF';
const DARK = '#111827';
const LIGHT = '#FAFAFA';
const GRAY = '#6B7280';
const GRAY_LIGHT = '#F8FAFC';
const GRAY_BORDER = '#E5E7EB';
const SUCCESS = '#059669';
const ERROR = '#DC2626';

// Backend shape
type ClinicField = {
  id: number;
  label: string;
  type: 'text' | 'textarea' | 'select' | 'checkbox' | 'radio' | 'date' | 'time' | 'number';
  options?: string[] | null;
  required?: boolean;
};

// Time slot interface to match the calendar selection
type TimeSlot = {
  start: string;
  end: string;
  display_time: string;
};

// Component props to accept calendar selection
type ClinicAppointmentsScreenProps = {
  route?: {
    params?: {
      clinicId?: number;
      date?: string;      // Calendar selected date in YYYY-MM-DD format
      timeSlot?: TimeSlot; // Selected time slot from calendar
    };
  };
  navigation?: any;
};

type OptionModalState = {
  visible: boolean;
  fieldId: number | null;
  label: string;
  multiple: boolean;
  options: string[];
  selected: string[]; // keep as array; for single we use length 0/1
};

type DateTimePickerState = {
  visible: boolean;
  fieldId: number | null;
  mode: 'date' | 'time';
  value: Date;
};

export default function ClinicAppointmentsScreen({ route, navigation }: ClinicAppointmentsScreenProps) {
  // Create ref for scrolling
  const scrollViewRef = React.useRef<ScrollView>(null);
  
  // Get parameters from route if available (from calendar screen)
  const routeParams = route?.params || {};
  const calendarDate = routeParams.date;
  const calendarTimeSlot = routeParams.timeSlot;
  const routeClinicId = routeParams.clinicId;
  
  const [clinicId, setClinicId] = React.useState<number | null>(routeClinicId || null);
  const [ownerName, setOwnerName] = React.useState<string>('');
  const [ownerPhone, setOwnerPhone] = React.useState<string>('');
  const [ownerEmail, setOwnerEmail] = React.useState<string>('');

  const [fields, setFields] = React.useState<ClinicField[]>([]);
  const [values, setValues] = React.useState<Record<string, any>>({});
  const [loading, setLoading] = React.useState<boolean>(false);
  const [submitting, setSubmitting] = React.useState<boolean>(false);
  const [error, setError] = React.useState<string | null>(null);
  const [refreshing, setRefreshing] = React.useState<boolean>(false);

  const [optionModal, setOptionModal] = React.useState<OptionModalState>({
    visible: false,
    fieldId: null,
    label: '',
    multiple: false,
    options: [],
    selected: [],
  });

  const [dateTimePicker, setDateTimePicker] = React.useState<DateTimePickerState>({
    visible: false,
    fieldId: null,
    mode: 'date',
    value: new Date(),
  });

  React.useEffect(() => {
    (async () => {
      try {
        // If we don't have a clinic ID from route params, try to get it from storage
        if (!routeClinicId) {
          const stored = await AsyncStorage.getItem('selectedClinic');
          const parsed = stored ? JSON.parse(stored) : null;
          setClinicId(parsed?.id ?? null);
        }
      } catch (error) {
        console.error('Error getting clinic ID from storage:', error);
      }
      
      // Moved user data fetching to separate useEffect below
    })();
  }, [routeClinicId]);
  
  // Handle date and time slot from calendar if provided
    // Fetch user data from API
  React.useEffect(() => {
    const fetchUserData = async () => {
      let token: string | null = null;
      try {
        // Check if user has a token before attempting to fetch user data
        token = await AsyncStorage.getItem('token') || 
                     await AsyncStorage.getItem('userToken') || 
                     await AsyncStorage.getItem('accessToken');
        
        if (!token) {
          console.log('No auth token available, skipping user data fetch in ClinicAppointments');
          return;
        }
        
        // Get user data from API
        const response = await API.get('/me');
        let userData = response.data;
        console.log('Raw API response:', response);
        console.log('User data received:', userData);
        
        // Check if the user data might be nested under a 'data' property (common in Laravel API responses)
        if (userData?.data && typeof userData.data === 'object') {
          userData = userData.data;
          console.log('Using nested data property:', userData);
        }
        
        // Log each field to diagnose issues
        console.log('Name field:', userData?.name);
        console.log('Phone field values:', userData?.phone_number, userData?.phone, userData?.mobile);
        console.log('Email field:', userData?.email);
        
        // Populate the form fields with user data - try different field names
        if (userData) {
          // Name - try different possible field names
          if (userData.name) {
            setOwnerName(userData.name);
            console.log('Set owner name:', userData.name);
          } else if (userData.full_name) {
            setOwnerName(userData.full_name);
            console.log('Set owner name from full_name:', userData.full_name);
          }
          
          // Phone - try different possible field names
          if (userData.phone_number) {
            setOwnerPhone(userData.phone_number);
            console.log('Set owner phone from phone_number:', userData.phone_number);
          } else if (userData.phone) {
            setOwnerPhone(userData.phone);
            console.log('Set owner phone from phone:', userData.phone);
          } else if (userData.mobile) {
            setOwnerPhone(userData.mobile);
            console.log('Set owner phone from mobile:', userData.mobile);
          } else if (userData.contact) {
            setOwnerPhone(userData.contact);
            console.log('Set owner phone from contact:', userData.contact);
          }
          
          // Email - try different possible field names
          if (userData.email) {
            setOwnerEmail(userData.email);
            console.log('Set owner email:', userData.email);
          } else if (userData.email_address) {
            setOwnerEmail(userData.email_address);
            console.log('Set owner email from email_address:', userData.email_address);
          }
        }
      } catch (error: any) {
        // Only log actual errors, not missing token cases
        if (error?.response?.status !== 401 || token) {
          console.error('Error fetching user data:', error);
        }
      }
    };
    
    fetchUserData();
  }, []);

  React.useEffect(() => {
    if (calendarDate || calendarTimeSlot) {
      console.log('Received calendar selections:', { calendarDate, calendarTimeSlot });
      
      // Update values with calendar date if provided
      if (calendarDate) {/* Lines 120-124 omitted */}
      
      // Update values with calendar time if provided
      if (calendarTimeSlot) {/* Lines 128-149 omitted */}
      
      // Highlight the appointment form to draw attention to it
      // This makes it clear that they need to complete the form after selecting a date/time
      setTimeout(() => {/* Lines 154-168 omitted */}, 500);
    }
  }, [calendarDate, calendarTimeSlot]);

  const fetchFields = React.useCallback(async (id: number) => {
    try {
      setLoading(true);
      setError(null);
      console.log(`Fetching form fields for clinic ID: ${id}`);
      
      const res = await API.get(`/clinics/${id}/fields`);
      const data: ClinicField[] = res.data?.data ?? res.data ?? [];
      
      console.log(`Received ${data.length} form fields from the server`);
      
      // Sort required fields first
      const sortedData = [...data].sort((a, b) => {
        if (a.required && !b.required) return -1;
        if (!a.required && b.required) return 1;
        return 0;
      });
      
      setFields(Array.isArray(sortedData) ? sortedData : []);
      
      // initialize defaults only for missing keys
      setValues((prev) => {
        const next = { ...prev } as Record<string, any>;
        (sortedData || []).forEach((f) => {
          const key = `f_${f.id}`;
          if (next[key] === undefined) {
            next[key] = f.type === 'checkbox' ? [] : '';
          }
        });
        return next;
      });
    } catch (e: any) {
      console.error('Error fetching fields:', e);
      setError(e?.response?.data?.message || e?.message || 'Failed to load form fields');
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => {
    if (clinicId != null) fetchFields(clinicId);
  }, [clinicId, fetchFields]);

  const onRefresh = React.useCallback(async () => {
    if (clinicId == null) return;
    try {
      setRefreshing(true);
      await fetchFields(clinicId);
    } finally {
      setRefreshing(false);
    }
  }, [clinicId, fetchFields]);

  const openOptionModal = (field: ClinicField) => {
    const key = `f_${field.id}`;
    const current = values[key];
    setOptionModal({
      visible: true,
      fieldId: field.id,
      label: field.label,
      multiple: field.type === 'checkbox',
      options: (field.options || []) as string[],
      selected: Array.isArray(current) ? current : (current ? [current] : []),
    });
  };

  const openDateTimePicker = (field: ClinicField) => {
    const key = `f_${field.id}`;
    const currentValue = values[key];
    let initialDate = new Date();
    
    if (currentValue) {
      try {
        if (field.type === 'date') {
          // Try to parse existing date value
          const [year, month, day] = currentValue.split('-').map(Number);
          if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
            initialDate = new Date(year, month - 1, day);
          }
        } else if (field.type === 'time') {
          // Try to parse existing time value
          const [hours, minutes] = currentValue.split(':').map(Number);
          if (!isNaN(hours) && !isNaN(minutes)) {
            initialDate.setHours(hours, minutes, 0);
          }
        }
      } catch (e) {
        console.error('Error parsing date/time:', e);
      }
    }

    setDateTimePicker({
      visible: true,
      fieldId: field.id,
      mode: field.type as 'date' | 'time',
      value: initialDate,
    });
  };

  const handleDateTimeChange = (event: any, selectedDate?: Date) => {
    const { fieldId, mode } = dateTimePicker;
    
    // On Android, dismissing the picker passes null for selectedDate
    if (!selectedDate) {
      if (Platform.OS === 'android') {
        setDateTimePicker(prev => ({ ...prev, visible: false }));
      }
      return;
    }

    let formattedValue = '';
    if (mode === 'date') {
      formattedValue = format(selectedDate, 'yyyy-MM-dd');
    } else {
      formattedValue = format(selectedDate, 'HH:mm');
    }

    if (fieldId) {
      const key = `f_${fieldId}`;
      setValues(prev => ({
        ...prev,
        [key]: formattedValue
      }));
    }

    // On iOS the picker stays open, on Android it closes immediately
    if (Platform.OS === 'android') {
      setDateTimePicker(prev => ({ ...prev, visible: false }));
    } else {
      setDateTimePicker(prev => ({ ...prev, value: selectedDate }));
    }
  };

  const commitOptionSelection = () => {
    if (optionModal.fieldId == null) return;
    const key = `f_${optionModal.fieldId}`;
    const value = optionModal.multiple ? optionModal.selected : (optionModal.selected[0] || '');
    setValues((prev) => ({ ...prev, [key]: value }));
    setOptionModal({ ...optionModal, visible: false });
  };

  const validate = (): string | null => {
    if (!ownerName.trim()) return 'Owner name is required';
    if (!ownerPhone.trim()) return 'Owner phone is required';
    for (const f of fields) {
      if (f.required) {
        const v = values[`f_${f.id}`];
        const isEmptyArray = Array.isArray(v) && v.length === 0;
        if (v === undefined || v === '' || isEmptyArray) {
          return `${f.label} is required`;
        }
      }
    }
    return null;
  };

  const handleSubmit = async () => {
    const problem = validate();
    if (problem) {
      setError(problem);
      return;
    }
    if (clinicId == null) return;

    try {
      setSubmitting(true);
      setError(null);
      
      // Get the selected date and time - prioritize values from state which may include calendar selections
      const selectedDate = values.appointment_date || calendarDate || new Date().toISOString().split('T')[0];
      const selectedTime = values.appointment_time || calendarTimeSlot?.start || '10:00';
      
      console.log('DEBUG APPOINTMENT TIME:', {
        'values.appointment_time': values.appointment_time,
        'calendarTimeSlot?.start': calendarTimeSlot?.start,
        'finalSelectedTime': selectedTime
      });
      
      console.log('Submitting appointment with date:', selectedDate, 'and time:', selectedTime);
      
      const payload = {
        owner_name: ownerName.trim(),
        owner_phone: ownerPhone.trim(),
        owner_email: ownerEmail.trim(),
        appointment_date: selectedDate,
        appointment_time: selectedTime,
        responses: fields.map((f) => ({ field_id: f.id, value: values[`f_${f.id}`] })),
      };
      
      // Send the appointment request
      const res = await API.post(`/clinics/${clinicId}/appointments`, payload);
      
      // Force a refresh for this date in the calendar screen for all users
      try {
        // Mark this date/time as booked in local storage using the correct format
        const localBookingsKey = `localBookings_${clinicId}_${selectedDate}`;
        
        // Get existing local bookings or create empty array
        const existingBookingsStr = await AsyncStorage.getItem(localBookingsKey);
        const existingBookings = existingBookingsStr ? JSON.parse(existingBookingsStr) : [];
        
        // Add this booking to the array
        const newBooking = {
          appointment_time: selectedTime,
          appointment_date: selectedDate,
          owner_name: ownerName.trim(),
          booked_at: new Date().toISOString()
        };
        
        // Check if this booking already exists to avoid duplicates
        const bookingExists = existingBookings.some((booking: any) => 
          booking.appointment_time === selectedTime && booking.appointment_date === selectedDate
        );
        
        if (!bookingExists) {
          existingBookings.push(newBooking);
          await AsyncStorage.setItem(localBookingsKey, JSON.stringify(existingBookings));
        }
        
        // Set refresh markers to force calendar to refresh
        const refreshKey = `refresh_calendar_${clinicId}_${selectedDate}`;
        await AsyncStorage.setItem(refreshKey, new Date().toISOString());
        
        // Clear cache to force fresh data fetch
        await AsyncStorage.removeItem(`slots_cache_${clinicId}_${selectedDate}`);
        
        console.log('✅ BOOKING SAVED LOCALLY:', newBooking);
        console.log('📱 Storage key used:', localBookingsKey);
        
        // Verify it was saved
        const verification = await AsyncStorage.getItem(localBookingsKey);
        console.log('🔍 Verification - saved data:', verification);
        
      } catch (e) {
        console.log('❌ Error setting refresh markers', e);
      }
      
      // Show success alert
      Alert.alert(
        "Booking Successful",
        "Your appointment has been booked successfully!",
        [
          { 
            text: "OK", 
            onPress: () => {
              // If we came from the calendar, navigate back
              if (calendarDate || calendarTimeSlot) {
                // Clear any cached data for this date before navigating back
                try {
                  AsyncStorage.removeItem(`slots_cache_${clinicId}_${selectedDate}`);
                } catch (e) {
                  console.log('Error clearing cache', e);
                }
                navigation?.goBack();
              } else {
                // Otherwise just clear the form
                setValues((prev) => {
                  const cleared: Record<string, any> = { ...prev };
                  cleared.appointment_date = '';
                  cleared.appointment_time = '';
                  fields.forEach((f) => {
                    const key = `f_${f.id}`;
                    cleared[key] = f.type === 'checkbox' ? [] : '';
                  });
                  return cleared;
                });
                
                // Show success message in the banner
                setError('Appointment booked successfully!');
              }
            }
          }
        ]
      );
    } catch (e: any) {
      setError(e?.response?.data?.message || e?.message || 'Failed to submit appointment');
    } finally {
      setSubmitting(false);
    }
  };

  const renderField = (f: ClinicField) => {
    const key = `f_${f.id}`;
    const val = values[key];

    if (f.type === 'textarea') {
      return (
        <View key={key} style={styles.inputGroup}>
          <Text style={styles.label}>{f.label}{f.required ? ' *' : ''}</Text>
          <TextInput
            style={[styles.input, styles.textarea]}
            multiline
            numberOfLines={4}
            value={val}
            onChangeText={(t) => setValues((p) => ({ ...p, [key]: t }))}
            placeholder={`Enter ${f.label.toLowerCase()}`}
            placeholderTextColor="#888"
          />
        </View>
      );
    }

    if (f.type === 'select' || f.type === 'radio' || f.type === 'checkbox') {
      const display = Array.isArray(val) ? (val.length ? `${val.length} selected` : 'Select') : (val || 'Select');
      return (
        <View key={key} style={styles.inputGroup}>
          <Text style={styles.label}>{f.label}{f.required ? ' *' : ''}</Text>
          <TouchableOpacity style={styles.selector} onPress={() => openOptionModal(f)}>
            <Text style={styles.selectorText}>{display}</Text>
            <Ionicons name="chevron-down" size={18} color="#666" />
          </TouchableOpacity>
        </View>
      );
    }

    // Date picker
    if (f.type === 'date') {
      let displayValue = val || 'Select date';
      if (val) {
        try {
          // If it's already in YYYY-MM-DD format, make it more readable
          const [year, month, day] = val.split('-');
          if (year && month && day) {
            const date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
            displayValue = format(date, 'MMMM d, yyyy');
          }
        } catch (e) {
          console.error('Error formatting date:', e);
        }
      }

      return (
        <View key={key} style={styles.inputGroup}>
          <Text style={styles.label}>{f.label}{f.required ? ' *' : ''}</Text>
          <TouchableOpacity 
            style={styles.selector} 
            onPress={() => openDateTimePicker(f)}
          >
            <Text style={styles.selectorText}>{displayValue}</Text>
            <Ionicons name="calendar" size={18} color="#666" />
          </TouchableOpacity>
        </View>
      );
    }

    // Time picker
    if (f.type === 'time') {
      let displayValue = val || 'Select time';
      if (val) {
        try {
          // If it's in HH:MM format, make it more readable
          const [hours, minutes] = val.split(':');
          if (hours && minutes) {
            const date = new Date();
            date.setHours(parseInt(hours), parseInt(minutes), 0);
            displayValue = format(date, 'h:mm a'); // 12-hour format with AM/PM
          }
        } catch (e) {
          console.error('Error formatting time:', e);
        }
      }

      return (
        <View key={key} style={styles.inputGroup}>
          <Text style={styles.label}>{f.label}{f.required ? ' *' : ''}</Text>
          <TouchableOpacity 
            style={styles.selector} 
            onPress={() => openDateTimePicker(f)}
          >
            <Text style={styles.selectorText}>{displayValue}</Text>
            <Ionicons name="time" size={18} color="#666" />
          </TouchableOpacity>
        </View>
      );
    }

    // number/text
    const keyboardType = f.type === 'number' ? 'numeric' : 'default';
    return (
      <View key={key} style={styles.inputGroup}>
        <Text style={styles.label}>{f.label}{f.required ? ' *' : ''}</Text>
        <TextInput
          style={styles.input}
          value={String(val ?? '')}
          onChangeText={(t) => setValues((p) => ({ ...p, [key]: t }))}
          placeholder={`Enter ${f.label.toLowerCase()}`}
          placeholderTextColor="#888"
          keyboardType={keyboardType as any}
        />
      </View>
    );
  };

  return (
    <View style={styles.container}>
      {/* Uniform Header */}
      <View style={styles.header}>
        <View style={styles.headerContent}>
          <TouchableOpacity 
            style={styles.backButton} 
            onPress={() => navigation?.goBack?.()}
          >
            <Ionicons name="arrow-back" size={20} color="#6B7280" />
          </TouchableOpacity>
          <View style={styles.headerTextWrap}>
            <Text style={styles.headerTitle}>Book Appointment</Text>
            <Text style={styles.headerSubtitle}>Schedule your visit</Text>
          </View>
        </View>
      </View>

      <ScrollView 
        ref={scrollViewRef}
        style={styles.scrollContainer} 
        contentContainerStyle={styles.content} 
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={PURPLE} />}>

      {/* Calendar selection info */}
      {(calendarDate || calendarTimeSlot) && (
        <View style={[styles.banner, styles.bannerCalendar]}>
          <View style={styles.calendarBannerContent}>
            <Ionicons name="calendar-outline" size={24} color={PURPLE} />
            <View style={styles.calendarBannerDetails}>
              <Text style={styles.calendarBannerTitle}>Selected Appointment Time</Text>
              <Text style={styles.calendarBannerText}>
                {calendarDate ? `Date: ${format(new Date(calendarDate), 'MMMM d, yyyy')}` : ''}
              </Text>
              {calendarTimeSlot && (
                <Text style={styles.calendarBannerText}>
                  Time: {calendarTimeSlot.display_time}
                </Text>
              )}
              <Text style={styles.calendarBannerNote}>
                This time slot is reserved for you while completing this form
              </Text>
              <TouchableOpacity 
                style={styles.changeDateButton}
                onPress={() => navigation?.navigate('ClinicCalendar', { clinicId })}
              >
                <Text style={styles.changeDateButtonText}>Change Date/Time</Text>
                <Ionicons name="calendar" size={16} color={PURPLE} />
              </TouchableOpacity>
            </View>
          </View>
        </View>
      )}

      {/* Error / status banner */}
      {error && (
        <View style={[styles.banner, error.includes('successfully') ? styles.bannerSuccess : styles.bannerError]}>
          <Text style={styles.bannerText}>{error}</Text>
        </View>
      )}

      {/* Owner info */}
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Ionicons name="person-circle-outline" size={26} color={PURPLE} />
          <Text style={styles.cardTitle}>Owner Information</Text>
        </View>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Owner Name *</Text>
          <TextInput style={styles.input} value={ownerName} onChangeText={setOwnerName} placeholder="Enter your name" placeholderTextColor="#888" />
        </View>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Owner Phone *</Text>
          <TextInput style={styles.input} value={ownerPhone} onChangeText={setOwnerPhone} placeholder="e.g. 09xxxxxxxxx" placeholderTextColor="#888" keyboardType="phone-pad" />
        </View>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Owner Email</Text>
          <TextInput style={styles.input} value={ownerEmail} onChangeText={setOwnerEmail} placeholder="Enter your email" placeholderTextColor="#888" keyboardType="email-address" autoCapitalize="none" />
        </View>
      </View>

      {/* Dynamic fields */}
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <Ionicons name="clipboard-outline" size={24} color={PURPLE} />
          <Text style={styles.cardTitle}>Appointment Details</Text>
        </View>
        {loading ? (
          <View style={{ paddingVertical: 16 }}>
            <ActivityIndicator color={PURPLE} />
          </View>
        ) : (
          <View>
            {fields.map(renderField)}
            {fields.length === 0 && (
              <Text style={{ color: '#666', fontSize: 14 }}>No fields configured for this clinic.</Text>
            )}
          </View>
        )}
      </View>

      <TouchableOpacity style={styles.submitButton} onPress={handleSubmit} disabled={submitting || loading || !clinicId}>
        {submitting ? <ActivityIndicator color={DARK} /> : <Text style={styles.submitText}>Book Appointment</Text>}
      </TouchableOpacity>

      {/* Options Modal */}
      <Modal visible={optionModal.visible} transparent animationType="fade" onRequestClose={() => setOptionModal((s) => ({ ...s, visible: false }))}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{optionModal.label}</Text>
              <TouchableOpacity onPress={() => setOptionModal((s) => ({ ...s, visible: false }))}>
                <Ionicons name="close" size={22} color={DARK} />
              </TouchableOpacity>
            </View>
            <FlatList
              data={optionModal.options}
              keyExtractor={(opt) => opt}
              renderItem={({ item }) => {
                const selected = optionModal.selected.includes(item);
                return (
                  <TouchableOpacity
                    style={[styles.optionRow, selected && styles.optionRowSelected]}
                    onPress={() => {
                      setOptionModal((s) => {
                        if (s.multiple) {
                          const exists = s.selected.includes(item);
                          const next = exists ? s.selected.filter((x) => x !== item) : [...s.selected, item];
                          return { ...s, selected: next };
                        }
                        return { ...s, selected: [item] };
                      });
                    }}
                  >
                    <Text style={[styles.optionText, selected && styles.optionTextSelected]}>{item}</Text>
                    {selected && <Ionicons name="checkmark" size={20} color={PURPLE} />}
                  </TouchableOpacity>
                );
              }}
            />
            <TouchableOpacity style={styles.modalSave} onPress={commitOptionSelection}>
              <Text style={styles.modalSaveText}>Done</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>

      {/* DateTimePicker for iOS and Android */}
      {dateTimePicker.visible && (
        <>
          {Platform.OS === 'ios' && (
            <Modal visible={true} transparent animationType="slide">
              <View style={styles.modalOverlay}>
                <View style={[styles.modalCard, { padding: 0 }]}>
                  <View style={[styles.modalHeader, { padding: 14 }]}>
                    <Text style={styles.modalTitle}>
                      Select {dateTimePicker.mode === 'date' ? 'Date' : 'Time'}
                    </Text>
                    <TouchableOpacity onPress={() => setDateTimePicker(prev => ({ ...prev, visible: false }))}>
                      <Ionicons name="close" size={22} color={DARK} />
                    </TouchableOpacity>
                  </View>
                  
                  <DateTimePicker
                    value={dateTimePicker.value}
                    mode={dateTimePicker.mode}
                    display="spinner"
                    onChange={handleDateTimeChange}
                    style={{ width: '100%', height: 200 }}
                  />
                  
                  <TouchableOpacity 
                    style={styles.modalSave}
                    onPress={() => {
                      // For iOS we need to manually close the modal and apply the date
                      handleDateTimeChange({ type: 'set' }, dateTimePicker.value);
                      setDateTimePicker(prev => ({ ...prev, visible: false }));
                    }}
                  >
                    <Text style={styles.modalSaveText}>Done</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </Modal>
          )}
          
          {/* For Android, the picker appears as a dialog automatically */}
          {Platform.OS === 'android' && (
            <DateTimePicker
              value={dateTimePicker.value}
              mode={dateTimePicker.mode}
              is24Hour={true}
              onChange={handleDateTimeChange}
            />
          )}
        </>
      )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: WHITE },
  // Uniform Header Styles
  header: { 
    backgroundColor: WHITE,
    paddingTop: 50,
    paddingBottom: 24,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: GRAY_BORDER,
    flexDirection: 'row', 
    alignItems: 'center'
  },
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  backButton: { 
    marginRight: 12, 
    width: 36,
    height: 36,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: GRAY_LIGHT
  },
  headerTextWrap: { flex: 1 },
  headerTitle: { 
    color: DARK, 
    fontSize: 18, 
    fontWeight: '500',
    letterSpacing: -0.2
  },
  headerSubtitle: { 
    color: GRAY, 
    fontSize: 12, 
    marginTop: 2,
    fontWeight: '400'
  },
  scrollContainer: { flex: 1 },
  content: { paddingHorizontal: 24, paddingBottom: 80, paddingTop: 8 },

  banner: { 
    paddingVertical: 12, 
    paddingHorizontal: 16, 
    borderRadius: 6, 
    marginBottom: 24,
    borderWidth: 1
  },
  bannerError: { 
    backgroundColor: WHITE, 
    borderColor: ERROR
  },
  bannerSuccess: { 
    backgroundColor: WHITE, 
    borderColor: SUCCESS
  },
  bannerCalendar: { 
    backgroundColor: WHITE, 
    borderColor: PURPLE
  },
  bannerText: { 
    color: DARK, 
    textAlign: 'center', 
    fontSize: 15,
    fontWeight: '500',
    lineHeight: 22
  },
  calendarBannerContent: { flexDirection: 'row', alignItems: 'flex-start' },
  calendarBannerDetails: { marginLeft: 16, flex: 1 },
  calendarBannerTitle: { 
    fontWeight: '700', 
    fontSize: 18, 
    color: DARK, 
    marginBottom: 8,
    letterSpacing: 0.3
  },
  calendarBannerText: { 
    color: DARK, 
    fontSize: 15, 
    marginBottom: 4,
    fontWeight: '500'
  },
  calendarBannerNote: { 
    color: GRAY, 
    fontSize: 13, 
    fontStyle: 'italic', 
    marginTop: 8,
    lineHeight: 18
  },
  changeDateButton: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    justifyContent: 'center', 
    marginTop: 12, 
    paddingVertical: 10,
    paddingHorizontal: 16, 
    backgroundColor: WHITE, 
    borderRadius: 12, 
    borderWidth: 2, 
    borderColor: PURPLE,
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2
  },
  changeDateButtonText: { 
    color: PURPLE, 
    marginRight: 8, 
    fontSize: 14, 
    fontWeight: '600',
    letterSpacing: 0.3
  },

  card: { 
    backgroundColor: WHITE, 
    borderRadius: 8, 
    paddingVertical: 32, 
    paddingHorizontal: 24, 
    marginBottom: 24, 
    borderWidth: 1,
    borderColor: GRAY_BORDER
  },
  cardHeader: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    marginBottom: 24,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: GRAY_BORDER
  },
  cardTitle: { 
    fontSize: 18, 
    fontWeight: '500', 
    color: DARK, 
    marginLeft: 8,
    letterSpacing: -0.2
  },

  inputGroup: { marginBottom: 24 },
  label: { 
    fontSize: 14, 
    color: GRAY, 
    marginBottom: 8, 
    fontWeight: '500',
    letterSpacing: 0.1
  },
  input: { 
    backgroundColor: WHITE, 
    borderRadius: 6, 
    borderWidth: 1, 
    borderColor: GRAY_BORDER, 
    paddingHorizontal: 16, 
    paddingVertical: 14, 
    fontSize: 16,
    color: DARK,
    fontWeight: '400'
  },
  textarea: { 
    minHeight: 100, 
    textAlignVertical: 'top',
    paddingTop: 14
  },

  selector: { 
    backgroundColor: WHITE, 
    borderRadius: 6, 
    borderWidth: 1, 
    borderColor: GRAY_BORDER, 
    paddingHorizontal: 16, 
    paddingVertical: 14, 
    flexDirection: 'row', 
    alignItems: 'center', 
    justifyContent: 'space-between'
  },
  selectorText: { 
    fontSize: 16, 
    color: DARK,
    fontWeight: '400'
  },

  submitButton: { 
    backgroundColor: PURPLE, 
    paddingVertical: 16, 
    borderRadius: 6, 
    alignItems: 'center', 
    justifyContent: 'center', 
    marginTop: 32
  },
  submitText: { 
    color: WHITE, 
    fontSize: 16, 
    fontWeight: '500',
    letterSpacing: 0.2
  },

  modalOverlay: { 
    flex: 1, 
    backgroundColor: 'rgba(0,0,0,0.4)', 
    justifyContent: 'center', 
    alignItems: 'center', 
    padding: 24 
  },
  modalCard: { 
    backgroundColor: WHITE, 
    width: '90%', 
    maxHeight: '70%', 
    borderRadius: 8, 
    paddingVertical: 32,
    paddingHorizontal: 24,
    borderWidth: 1,
    borderColor: GRAY_BORDER
  },
  modalHeader: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    justifyContent: 'space-between', 
    marginBottom: 24,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: GRAY_BORDER
  },
  modalTitle: { 
    fontSize: 18, 
    fontWeight: '500', 
    color: DARK,
    letterSpacing: -0.1
  },
  optionRow: { 
    flexDirection: 'row', 
    alignItems: 'center', 
    justifyContent: 'space-between', 
    paddingVertical: 12, 
    paddingHorizontal: 0,
    borderBottomWidth: 1, 
    borderBottomColor: GRAY_BORDER
  },
  optionRowSelected: { 
    backgroundColor: GRAY_LIGHT
  },
  optionText: { 
    fontSize: 16, 
    color: DARK,
    fontWeight: '400'
  },
  optionTextSelected: { 
    color: PURPLE, 
    fontWeight: '500' 
  },
  modalSave: { 
    backgroundColor: PURPLE, 
    paddingVertical: 14, 
    alignItems: 'center', 
    borderRadius: 6, 
    marginTop: 24
  },
  modalSaveText: { 
    color: WHITE, 
    fontWeight: '500',
    fontSize: 16,
    letterSpacing: 0.1
  },
});