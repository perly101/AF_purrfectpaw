import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  TextInput,
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import { format } from 'date-fns';
import TimeSlotPicker from '../components/TimeSlotPicker';
import { 
  bookAppointment, 
  getAvailabilitySlots, 
  getComprehensiveAvailability,
  getClinicFields,
  API, 
  Slot, 
  CustomField, 
  AppointmentBookingData 
} from '../src/api';
import { ROUTES } from '../src/routes';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { useNavigation } from '@react-navigation/native';
import { BOOKING_CONFIG } from '../src/config/bookingConfig';

// Colors
const PRIMARY = '#4A6FA5';
const SECONDARY = '#6C5CE7';
const BACKGROUND = '#F8F9FA';
const TEXT_PRIMARY = '#333333';
const TEXT_SECONDARY = '#666666';
const BORDER = '#DDE2E5';

type AppointmentFormProps = {
  route: {
    params: {
      clinicId: number;
      clinicName?: string;
      date?: string;      // Date from calendar in YYYY-MM-DD format
      timeSlot?: {        // Time slot from calendar
        start: string;    // Format: "HH:MM:SS"
        end: string;      // Format: "HH:MM:SS"
        display_time: string;
      };
    };
  };
};

const BookAppointmentScreen = ({ route }: AppointmentFormProps) => {
  const navigation = useNavigation();
  const { clinicId, clinicName, date: calendarDate, timeSlot: calendarTimeSlot } = route.params;
  
  // Form state
  const [ownerName, setOwnerName] = useState('');
  const [ownerPhone, setOwnerPhone] = useState('');
  
  // Initialize date from calendar if provided, otherwise use current date
  // Always use Philippines timezone (UTC+8)
  const [selectedDate, setSelectedDate] = useState(() => {
    if (calendarDate) {
      // Parse the YYYY-MM-DD format to create a Date object
      // Ensure we're using the correct local time in Philippines
      const dateObj = new Date(calendarDate);
      // Add timezone offset adjustment for Philippines (UTC+8)
      return dateObj;
    }
    // For current date, get Philippines time
    const now = new Date();
    return now;
  });
  
  // Initialize time slot from calendar if provided
  const [selectedSlot, setSelectedSlot] = useState<Slot | null>(() => {
    if (calendarTimeSlot) {
      return calendarTimeSlot as Slot;
    }
    return null;
  });
  
  // Add effect to log when screen loads with pre-selected date and time
  useEffect(() => {
    if (calendarDate && calendarTimeSlot) {
      console.log(`BookAppointmentScreen loaded with pre-selected date: ${calendarDate}`);
      console.log(`Pre-selected time slot:`, JSON.stringify(calendarTimeSlot));
      
      // Set alert to let user know we're using pre-selected values from calendar
      Alert.alert(
        "Appointment Information",
        `You've selected ${format(new Date(calendarDate), 'MMMM d, yyyy')} at ${calendarTimeSlot.display_time}. Please complete the form to book your appointment.`,
        [{ text: "OK" }]
      );
    } else {
      console.log('BookAppointmentScreen loaded without pre-selected date/time');
      // If we don't have a pre-selected date/time, we'll show calendar and slot pickers
    }
  }, [calendarDate, calendarTimeSlot]);
  
  const [showDatePicker, setShowDatePicker] = useState(false);
  
  // Form fields state
  const [fields, setFields] = useState<any[]>([]);
  const [fieldValues, setFieldValues] = useState<Record<string, any>>({});
  
  // Custom fields from set_appointment form
  const [petName, setPetName] = useState('');
  const [breed, setBreed] = useState('');
  const [petType, setPetType] = useState('');
  const [treatment, setTreatment] = useState('');
  const [petOptions, setPetOptions] = useState<string[]>([]);
  const [treatmentOptions, setTreatmentOptions] = useState<string[]>([]);
  
  // UI state
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  
  // Comprehensive availability data
  const [availabilityData, setAvailabilityData] = useState<any>(null);
  const [clinicSettings, setClinicSettings] = useState<any>(null);
  const [weeklySchedule, setWeeklySchedule] = useState<any>(null);
  const [breakTimes, setBreakTimes] = useState<any[]>([]);
  const [specialDates, setSpecialDates] = useState<any[]>([]);
  
  // Load user info and clinic fields on component mount
  useEffect(() => {
    const loadUserInfo = async () => {
      try {
        const userData = await AsyncStorage.getItem('userData');
        if (userData) {
          const parsedData = JSON.parse(userData);
          setOwnerName(parsedData.name || '');
          setOwnerPhone(parsedData.phone || '');
        }
      } catch (error) {
        console.error('Error loading user data:', error);
      }
    };
    
    const fetchFields = async () => {
      setLoading(true);
      try {
        // Get comprehensive availability data including settings, schedules, breaks, special dates, and fields
        console.log(`Fetching comprehensive availability data for clinic ${clinicId}`);
        const comprehensiveData = await getComprehensiveAvailability(clinicId);
        
        console.log('Comprehensive availability data:', JSON.stringify(comprehensiveData, null, 2));
        
        // Store comprehensive data
        setAvailabilityData(comprehensiveData);
        setClinicSettings(comprehensiveData.settings || {});
        setWeeklySchedule(comprehensiveData.weeklySchedule || {});
        setBreakTimes(comprehensiveData.breaks || []);
        setSpecialDates(comprehensiveData.specialDates || []);
        
        // Use fields from comprehensive data
        const fieldsData = comprehensiveData.fields || [];
        console.log('Clinic fields from comprehensive data:', JSON.stringify(fieldsData));
        setFields(fieldsData);
        
        // Initialize field values
        const initialValues: Record<string, any> = {};
        if (Array.isArray(fieldsData)) {
          fieldsData.forEach((field: any) => {
            initialValues[`field_${field.id}`] = field.type === 'checkbox' ? [] : '';
          });
        } else {
          console.warn('Fields data is not an array:', fieldsData);
        }
        setFieldValues(initialValues);
        
        // Extract pet type and treatment options from fields
        // These are special custom fields that should be handled separately
        if (Array.isArray(fieldsData)) {
          const petTypeField = fieldsData.find((f: CustomField) => 
            f.label?.toLowerCase().includes('pet type') || 
            (f as any).name?.toLowerCase().includes('pet type')
          );
          
          const treatmentField = fieldsData.find((f: CustomField) => 
            f.label?.toLowerCase().includes('treatment') || 
            (f as any).name?.toLowerCase().includes('treatment')
          );
          
          // Handle pet type options
          const petFieldOptions = petTypeField?.options || [];
          if (petFieldOptions.length > 0) {
            setPetOptions(petFieldOptions);
            setPetType(petFieldOptions[0]);
          } else {
            // Fallback pet types if none found in API
            const defaultPetTypes = ['Cat', 'Dog', 'Bird', 'Rabbit', 'Other'];
            setPetOptions(defaultPetTypes);
            setPetType(defaultPetTypes[0]);
          }
          
          // Handle treatment options
          const treatmentFieldOptions = treatmentField?.options || [];
          if (treatmentFieldOptions.length > 0) {
            setTreatmentOptions(treatmentFieldOptions);
            setTreatment(treatmentFieldOptions[0]);
          } else {
            // Fallback treatment types if none found in API
            const defaultTreatments = ['Check-up', 'Vaccination', 'Injury', 'Illness', 'Surgery', 'Other'];
            setTreatmentOptions(defaultTreatments);
            setTreatment(defaultTreatments[0]);
          }
        }
      } catch (error) {
        console.error('Error fetching fields:', error);
        Alert.alert('Error', 'Failed to load appointment form fields.');
      } finally {
        setLoading(false);
      }
    };
    
    loadUserInfo();
    fetchFields();
  }, [clinicId]);
  
  // Check if a date is available based on clinic settings
  const isDateAvailable = (date: Date): { available: boolean; reason?: string } => {
    if (!weeklySchedule || !clinicSettings) {
      return { available: true }; // Default to available if no data
    }
    
    const dayOfWeek = date.getDay();
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const dayName = dayNames[dayOfWeek];
    const daySchedule = weeklySchedule[dayName];
    
    // Check if the day is closed
    if (!daySchedule || !daySchedule.isAvailable) {
      return { available: false, reason: `${dayName.charAt(0).toUpperCase() + dayName.slice(1)} is not available` };
    }
    
    // Check special dates (holidays/closures)
    const dateStr = format(date, 'yyyy-MM-dd');
    const specialDate = specialDates.find(sd => sd.date === dateStr);
    if (specialDate && !specialDate.is_available) {
      return { available: false, reason: specialDate.name || 'Special closure date' };
    }
    
    // Check advance booking limits
    const today = new Date();
    const diffTime = date.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (clinicSettings.advance_booking_days && diffDays > clinicSettings.advance_booking_days) {
      return { available: false, reason: `Cannot book more than ${clinicSettings.advance_booking_days} days in advance` };
    }
    
    // Check same day booking restrictions
    if (!clinicSettings.same_day_booking && diffDays === 0) {
      return { available: false, reason: 'Same day booking is not allowed' };
    }
    
    return { available: true };
  };

  const onDateChange = (event: any, selectedDate?: Date) => {
    setShowDatePicker(Platform.OS === 'ios');
    if (selectedDate) {
      // Check clinic-specific availability first
      const dateAvailability = isDateAvailable(selectedDate);
      if (!dateAvailability.available) {
        Alert.alert('Date Not Available', dateAvailability.reason || 'This date is not available for appointments.', [{ text: 'OK' }]);
        return;
      }
      
      // Check if same-day booking restriction is enabled (legacy config)
      if (BOOKING_CONFIG.samedayOnly) {
        const today = new Date();
        const selectedDateOnly = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());
        const todayOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        
        // If user selects a different date than today
        if (selectedDateOnly.getTime() !== todayOnly.getTime()) {
          Alert.alert('Date Selection', BOOKING_CONFIG.samedayMessage, [{ text: 'OK' }]);
          return; // Don't update the selected date
        }
      }
      
      setSelectedDate(selectedDate);
      // Reset selected time slot when date changes
      setSelectedSlot(null);
    }
  };
  
  const handleFieldChange = (fieldId: number, value: any) => {
    setFieldValues(prev => ({
      ...prev,
      [`field_${fieldId}`]: value
    }));
  };
  
  const validateForm = () => {
    if (!ownerName.trim()) {
      Alert.alert('Missing Information', 'Please enter your name.');
      return false;
    }
    
    if (!ownerPhone.trim()) {
      Alert.alert('Missing Information', 'Please enter your phone number.');
      return false;
    }
    
    if (!selectedSlot || !selectedSlot.start) {
      Alert.alert('Missing Information', 'Please select an appointment time.');
      return false;
    }
    
    // Only validate custom pet fields if they're shown in the form
    // If pet fields are displayed in the form UI, validate them
    if (petOptions.length > 0) {
      if (!petType) {
        Alert.alert('Missing Information', 'Please select a pet type.');
        return false;
      }
      
      if (!petName.trim()) {
        Alert.alert('Missing Information', 'Please enter your pet\'s name.');
        return false;
      }
      
      if (!breed.trim()) {
        Alert.alert('Missing Information', 'Please enter your pet\'s breed.');
        return false;
      }
    }
    
    if (treatmentOptions.length > 0 && !treatment) {
      Alert.alert('Missing Information', 'Please select a treatment.');
      return false;
    }
    
    // Validate required custom fields
    for (const field of fields) {
      if (field.required && !fieldValues[`field_${field.id}`]) {
        Alert.alert('Missing Information', `Please fill in the ${field.label} field.`);
        return false;
      }
    }
    
    return true;
  };
  
  const handleSubmit = async () => {
    if (!validateForm()) return;
    
    setSubmitting(true);
    
    console.log('Form values before submission:');
    console.log('Owner Name:', ownerName);
    console.log('Owner Phone:', ownerPhone);
    console.log('Date:', format(selectedDate, 'yyyy-MM-dd'));
    console.log('Time Slot:', selectedSlot ? selectedSlot.display_time : 'None');
    console.log('Field Values:', fieldValues);
    
    // Create responses array with all form field values
    let responses = Object.keys(fieldValues).map(key => {
      const fieldId = key.replace('field_', '');
      return {
        field_id: fieldId,
        value: fieldValues[key]
      };
    });
    
    // Add pet-related fields to the responses if they're filled in
    // First find if fields for pet information already exist in the custom fields
    const petNameField = fields.find(f => f.label?.toLowerCase().includes('pet name'));
    const petTypeField = fields.find(f => f.label?.toLowerCase().includes('pet type'));
    const breedField = fields.find(f => f.label?.toLowerCase().includes('breed'));
    const treatmentField = fields.find(f => f.label?.toLowerCase().includes('treatment'));
    
    // If pet fields are filled but not already in custom fields, add them
    if (petName && !petNameField) {
      // Create a special field for pet name - normally these would come from the backend
      responses.push({
        field_id: 'pet_name',
        value: petName.trim()
      });
    }
    
    if (petType && !petTypeField) {
      responses.push({
        field_id: 'pet_type',
        value: petType.trim()
      });
    }
    
    if (breed && !breedField) {
      responses.push({
        field_id: 'breed',
        value: breed.trim()
      });
    }
    
    if (treatment && !treatmentField) {
      responses.push({
        field_id: 'treatment',
        value: treatment.trim()
      });
    }
    
    // Make sure all strings are properly trimmed and correctly formatted according to README specs
    // Dates should be in YYYY-MM-DD format, Times in HH:MM:SS format
    const formattedDate = format(selectedDate, 'yyyy-MM-dd');
    
    // Ensure time is in HH:MM:SS format as required
    let formattedTime = selectedSlot!.start; // This should be in 24-hour format like "09:00"
    if (!formattedTime.includes(':')) {
      formattedTime = `${formattedTime}:00:00`;
    } else if (formattedTime.split(':').length === 2) {
      formattedTime = `${formattedTime}:00`;
    }
    
    console.log('Preparing appointment with formatted date/time:', {
      date: formattedDate,
      time: formattedTime,
      originalSlot: selectedSlot
    });
    
    const appointmentData: AppointmentBookingData = {
      owner_name: ownerName.trim(),
      owner_phone: ownerPhone.trim(),
      appointment_date: formattedDate,
      appointment_time: formattedTime,
      display_time: selectedSlot!.display_time, // Include the display time
      responses: responses
    };
    
    try {
      // Display a loading indicator
      setSubmitting(true);
      
      // Book the appointment
      const response = await bookAppointment(clinicId, appointmentData);
      
      // Clear form data
      setSelectedSlot(null);
      
      // Store the appointment ID for reference
      if (response.appointment_id) {
        console.log(`Booking successful! Appointment ID: ${response.appointment_id}`);
        
        // Immediately invalidate all cached availability data to force fresh data
        try {
          const dateKey = format(selectedDate, 'yyyy-MM-dd');
          
          // Clear multiple cache keys to ensure fresh data
          await AsyncStorage.multiRemove([
            `availabilitySlots_${clinicId}_${dateKey}`,
            `slots_cache_${clinicId}_${dateKey}`,
            `localBookings_${clinicId}_${dateKey}`,
            `refresh_calendar_${clinicId}_${dateKey}`
          ]);
          
          // Create a special reference to force calendar to refresh this appointment
          await AsyncStorage.setItem(`refresh_calendar_${clinicId}_${dateKey}`, 
            JSON.stringify({
              timestamp: Date.now(),
              appointmentId: response.appointment_id,
              slot: formattedTime,
              action: 'book'
            })
          );
          
          // Also store locally booked slots for immediate UI updates
          const localBookingsKey = `localBookings_${clinicId}_${dateKey}`;
          const existingBookings = await AsyncStorage.getItem(localBookingsKey);
          const bookings = existingBookings ? JSON.parse(existingBookings) : [];
          bookings.push({
            appointment_id: response.appointment_id,
            appointment_time: formattedTime,
            timestamp: Date.now()
          });
          await AsyncStorage.setItem(localBookingsKey, JSON.stringify(bookings));
          
          console.log('Cleared availability cache and stored local booking to force refresh');
        } catch (e) {
          console.log('Failed to clear cache:', e);
        }
      }
      
      // Show success message
      Alert.alert(
        'Success',
        'Your appointment has been booked successfully! The selected time slot is now unavailable for other bookings.',
        [{ 
          text: 'OK', 
          onPress: () => {
            // Force refresh availability data before going back
            navigation.goBack();
          }
        }]
      );
    } catch (error: any) {
      console.error('Error booking appointment:', error);
      console.error('Request data:', JSON.stringify(appointmentData, null, 2));
      
      // Full detailed error logging
      if (error.response) {
        // The request was made and the server responded with a status code
        // that falls out of the range of 2xx
        console.error('Error response data:', JSON.stringify(error.response.data, null, 2));
        console.error('Error response status:', error.response.status);
        console.error('Error response headers:', error.response.headers);
      } else if (error.request) {
        // The request was made but no response was received
        console.error('Error request (no response):', error.request);
      } else {
        // Something happened in setting up the request that triggered an Error
        console.error('Error message:', error.message);
      }
      
      let errorMessage = 'There was an error booking your appointment. Please try again.';
      let detailedMessage = '';
      
      if (error.response?.data?.errors) {
        // Format validation errors for display
        const validationErrors = error.response.data.errors;
        errorMessage = 'Validation Error';
        detailedMessage = Object.keys(validationErrors)
          .map(key => `${key}: ${validationErrors[key].join(', ')}`)
          .join('\n');
      } else if (error.response?.data?.debug_info) {
        errorMessage = error.response.data.message || 'Validation Error';
        detailedMessage = `Debug Info: ${JSON.stringify(error.response.data.debug_info)}`;
      } else if (error.response?.data?.message) {
        errorMessage = error.response.data.message;
      } else if (!error.response) {
        errorMessage = 'Network Error';
        detailedMessage = 'Could not connect to server. Check your internet connection and server status.';
      }
      
      Alert.alert(
        errorMessage,
        detailedMessage || 'Please check your input and try again.'
      );
    } finally {
      setSubmitting(false);
    }
  };
  
  const renderFieldInput = (field: any) => {
    const { id, label, type, options = [], required } = field;
    const fieldId = `field_${id}`;
    const value = fieldValues[fieldId] || '';
    
    switch (type) {
      case 'text':
      case 'number':
        return (
          <View style={styles.fieldContainer} key={id}>
            <Text style={styles.fieldLabel}>
              {label} {required && <Text style={styles.required}>*</Text>}
            </Text>
            <TextInput
              style={styles.textInput}
              value={value}
              onChangeText={(text) => handleFieldChange(id, text)}
              keyboardType={type === 'number' ? 'numeric' : 'default'}
              placeholder={`Enter ${label.toLowerCase()}`}
            />
          </View>
        );
        
      case 'textarea':
        return (
          <View style={styles.fieldContainer} key={id}>
            <Text style={styles.fieldLabel}>
              {label} {required && <Text style={styles.required}>*</Text>}
            </Text>
            <TextInput
              style={[styles.textInput, styles.textArea]}
              value={value}
              onChangeText={(text) => handleFieldChange(id, text)}
              placeholder={`Enter ${label.toLowerCase()}`}
              multiline
              numberOfLines={4}
            />
          </View>
        );
        
      case 'select':
        return (
          <View style={styles.fieldContainer} key={id}>
            <Text style={styles.fieldLabel}>
              {label} {required && <Text style={styles.required}>*</Text>}
            </Text>
            <View style={styles.selectContainer}>
              {options.map((option: string) => (
                <TouchableOpacity
                  key={option}
                  style={[
                    styles.selectOption,
                    value === option && styles.selectedOption
                  ]}
                  onPress={() => handleFieldChange(id, option)}
                >
                  <Text style={[
                    styles.optionText,
                    value === option && styles.selectedOptionText
                  ]}>
                    {option}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        );
        
      case 'checkbox':
        const selectedOptions = Array.isArray(value) ? value : [];
        return (
          <View style={styles.fieldContainer} key={id}>
            <Text style={styles.fieldLabel}>
              {label} {required && <Text style={styles.required}>*</Text>}
            </Text>
            <View style={styles.checkboxContainer}>
              {options.map((option: string) => (
                <TouchableOpacity
                  key={option}
                  style={styles.checkboxRow}
                  onPress={() => {
                    const newValue = [...selectedOptions];
                    const optionIndex = newValue.indexOf(option);
                    if (optionIndex === -1) {
                      newValue.push(option);
                    } else {
                      newValue.splice(optionIndex, 1);
                    }
                    handleFieldChange(id, newValue);
                  }}
                >
                  <View style={[
                    styles.checkbox,
                    selectedOptions.includes(option) && styles.checkboxChecked
                  ]}>
                    {selectedOptions.includes(option) && (
                      <Ionicons name="checkmark" size={16} color="white" />
                    )}
                  </View>
                  <Text style={styles.checkboxLabel}>{option}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>
        );
        
      default:
        return null;
    }
  };
  
  if (loading) {
    return (
      <View style={[styles.container, styles.centered]}>
        <ActivityIndicator size="large" color={PRIMARY} />
        <Text style={styles.loadingText}>Loading appointment form...</Text>
      </View>
    );
  }
  
  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
            <Ionicons name="arrow-back" size={24} color={TEXT_PRIMARY} />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Book an Appointment</Text>
        </View>
        
        <View style={styles.clinicInfo}>
          <Text style={styles.clinicName}>{clinicName}</Text>
        </View>
        
        <View style={styles.formSection}>
          <Text style={styles.sectionTitle}>Contact Information</Text>
          
          <View style={styles.fieldContainer}>
            <Text style={styles.fieldLabel}>Your Name <Text style={styles.required}>*</Text></Text>
            <TextInput
              style={styles.textInput}
              value={ownerName}
              onChangeText={setOwnerName}
              placeholder="Enter your full name"
            />
          </View>
          
          <View style={styles.fieldContainer}>
            <Text style={styles.fieldLabel}>Phone Number <Text style={styles.required}>*</Text></Text>
            <TextInput
              style={styles.textInput}
              value={ownerPhone}
              onChangeText={setOwnerPhone}
              keyboardType="phone-pad"
              placeholder="Enter your phone number"
            />
          </View>
        </View>
        
        {/* Only show Pet Information section if any pet options exist */}
        {petOptions.length > 0 && (
          <View style={styles.formSection}>
            <Text style={styles.sectionTitle}>Pet Information</Text>
            
            <View style={styles.fieldContainer}>
              <Text style={styles.fieldLabel}>Pet Type <Text style={styles.required}>*</Text></Text>
              <View style={styles.selectContainer}>
                {petOptions.map((option: string) => (
                  <TouchableOpacity
                    key={option}
                    style={[
                      styles.selectOption,
                      petType === option && styles.selectedOption
                    ]}
                    onPress={() => setPetType(option)}
                  >
                    <Text style={[
                      styles.optionText,
                      petType === option && styles.selectedOptionText
                    ]}>
                      {option}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
            
            <View style={styles.fieldContainer}>
              <Text style={styles.fieldLabel}>Pet Name <Text style={styles.required}>*</Text></Text>
              <TextInput
                style={styles.textInput}
                value={petName}
                onChangeText={setPetName}
                placeholder="Enter your pet's name"
              />
            </View>
            
            <View style={styles.fieldContainer}>
              <Text style={styles.fieldLabel}>Breed <Text style={styles.required}>*</Text></Text>
              <TextInput
                style={styles.textInput}
                value={breed}
                onChangeText={setBreed}
                placeholder="Enter your pet's breed"
              />
            </View>
            
            {treatmentOptions.length > 0 && (
              <View style={styles.fieldContainer}>
                <Text style={styles.fieldLabel}>Treatment <Text style={styles.required}>*</Text></Text>
                <View style={styles.selectContainer}>
                  {treatmentOptions.map((option: string) => (
                    <TouchableOpacity
                      key={option}
                      style={[
                        styles.selectOption,
                        treatment === option && styles.selectedOption
                      ]}
                      onPress={() => setTreatment(option)}
                    >
                      <Text style={[
                        styles.optionText,
                        treatment === option && styles.selectedOptionText
                      ]}>
                        {option}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>
            )}
          </View>
        )}
        
        {/* Clinic Availability Information */}
        {weeklySchedule && (
          <View style={styles.formSection}>
            <Text style={styles.sectionTitle}>Clinic Hours & Information</Text>
            
            {/* Weekly Schedule */}
            <View style={styles.infoContainer}>
              <Text style={styles.infoLabel}>Weekly Schedule:</Text>
              {Object.entries(weeklySchedule).map(([day, schedule]: [string, any]) => (
                <View key={day} style={styles.scheduleRow}>
                  <Text style={styles.dayName}>
                    {day.charAt(0).toUpperCase() + day.slice(1)}:
                  </Text>
                  <Text style={styles.scheduleTime}>
                    {schedule.isAvailable && schedule.start && schedule.end 
                      ? `${schedule.start} - ${schedule.end}`
                      : 'Closed'
                    }
                  </Text>
                </View>
              ))}
            </View>
            
            {/* Break Times */}
            {breakTimes.length > 0 && (
              <View style={styles.infoContainer}>
                <Text style={styles.infoLabel}>Break Times:</Text>
                {breakTimes.map((breakTime, index) => (
                  <View key={index} style={styles.scheduleRow}>
                    <Text style={styles.breakName}>
                      {breakTime.name}:
                    </Text>
                    <Text style={styles.scheduleTime}>
                      {breakTime.start_time} - {breakTime.end_time}
                    </Text>
                  </View>
                ))}
              </View>
            )}
            
            {/* Special Dates/Holidays */}
            {specialDates.length > 0 && (
              <View style={styles.infoContainer}>
                <Text style={styles.infoLabel}>Special Dates:</Text>
                {specialDates.slice(0, 3).map((specialDate, index) => (
                  <View key={index} style={styles.scheduleRow}>
                    <Text style={styles.dayName}>
                      {format(new Date(specialDate.date), 'MMM dd, yyyy')}:
                    </Text>
                    <Text style={[styles.scheduleTime, !specialDate.is_available && styles.closedText]}>
                      {specialDate.is_available ? 'Open' : (specialDate.name || 'Closed')}
                    </Text>
                  </View>
                ))}
              </View>
            )}
            
            {/* Booking Settings */}
            {clinicSettings && (
              <View style={styles.infoContainer}>
                <Text style={styles.infoLabel}>Booking Information:</Text>
                <Text style={styles.infoText}>
                  • Appointment duration: {clinicSettings.slot_duration || 30} minutes
                </Text>
                {clinicSettings.advance_booking_days && (
                  <Text style={styles.infoText}>
                    • Book up to {clinicSettings.advance_booking_days} days in advance
                  </Text>
                )}
                {!clinicSettings.same_day_booking && (
                  <Text style={styles.infoText}>
                    • Same day booking not available
                  </Text>
                )}
              </View>
            )}
          </View>
        )}

        <View style={styles.formSection}>
          <Text style={styles.sectionTitle}>Appointment Details</Text>
          
          <View style={styles.fieldContainer}>
            <Text style={styles.fieldLabel}>Select Date <Text style={styles.required}>*</Text></Text>
            <TouchableOpacity 
              style={styles.datePickerButton}
              onPress={() => setShowDatePicker(true)}
            >
              <Text style={styles.dateText}>{format(selectedDate, 'MMMM dd, yyyy')}</Text>
              <Ionicons name="calendar" size={24} color={PRIMARY} />
            </TouchableOpacity>
            
            {showDatePicker && (
              <DateTimePicker
                value={selectedDate}
                mode="date"
                display="default"
                onChange={onDateChange}
                minimumDate={BOOKING_CONFIG.samedayOnly ? new Date() : new Date()}
                maximumDate={BOOKING_CONFIG.samedayOnly ? new Date() : undefined}
              />
            )}
          </View>
          
          {/* Time Slot Selector Component */}
          <TimeSlotPicker
            clinicId={clinicId}
            selectedDate={selectedDate}
            onSelectSlot={setSelectedSlot}
            selectedSlot={selectedSlot}
            breakTimes={breakTimes}
            clinicSettings={clinicSettings}
          />
        </View>
        
        {fields.length > 0 && (
          <View style={styles.formSection}>
            <Text style={styles.sectionTitle}>Additional Information</Text>
            {fields.map(renderFieldInput)}
          </View>
        )}
        
        <TouchableOpacity 
          style={[styles.submitButton, submitting && styles.submitButtonDisabled]}
          onPress={handleSubmit}
          disabled={submitting}
        >
          {submitting ? (
            <ActivityIndicator color="white" size="small" />
          ) : (
            <Text style={styles.submitButtonText}>Book Appointment</Text>
          )}
        </TouchableOpacity>
      </ScrollView>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: BACKGROUND,
  },
  centered: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 24,
  },
  backButton: {
    padding: 8,
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: TEXT_PRIMARY,
    marginLeft: 8,
  },
  clinicInfo: {
    marginBottom: 24,
    padding: 16,
    backgroundColor: 'white',
    borderRadius: 8,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
  },
  clinicName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: TEXT_PRIMARY,
  },
  formSection: {
    marginBottom: 24,
    backgroundColor: 'white',
    borderRadius: 8,
    padding: 16,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: TEXT_PRIMARY,
    marginBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: BORDER,
    paddingBottom: 8,
  },
  fieldContainer: {
    marginBottom: 16,
  },
  fieldLabel: {
    fontSize: 14,
    color: TEXT_SECONDARY,
    marginBottom: 8,
  },
  required: {
    color: 'red',
  },
  textInput: {
    borderWidth: 1,
    borderColor: BORDER,
    borderRadius: 6,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
  },
  textArea: {
    height: 100,
    textAlignVertical: 'top',
  },
  datePickerButton: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: BORDER,
    borderRadius: 6,
    paddingHorizontal: 12,
    paddingVertical: 12,
  },
  dateText: {
    fontSize: 16,
    color: TEXT_PRIMARY,
  },
  selectContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
  selectOption: {
    borderWidth: 1,
    borderColor: BORDER,
    borderRadius: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginRight: 8,
    marginBottom: 8,
  },
  selectedOption: {
    backgroundColor: PRIMARY,
    borderColor: PRIMARY,
  },
  optionText: {
    color: TEXT_PRIMARY,
  },
  selectedOptionText: {
    color: 'white',
  },
  checkboxContainer: {
    marginTop: 4,
  },
  checkboxRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderWidth: 1,
    borderColor: BORDER,
    borderRadius: 4,
    justifyContent: 'center',
    alignItems: 'center',
  },
  checkboxChecked: {
    backgroundColor: PRIMARY,
    borderColor: PRIMARY,
  },
  checkboxLabel: {
    marginLeft: 8,
    fontSize: 16,
    color: TEXT_PRIMARY,
  },
  submitButton: {
    backgroundColor: PRIMARY,
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 8,
  },
  submitButtonDisabled: {
    backgroundColor: '#A0AEC0',
  },
  submitButtonText: {
    color: 'white',
    fontSize: 16,
    fontWeight: 'bold',
  },
  loadingText: {
    marginTop: 16,
    fontSize: 16,
    color: TEXT_SECONDARY,
  },
  // Clinic Information Styles
  infoContainer: {
    marginTop: 10,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: '#E5E7EB',
  },
  infoLabel: {
    fontSize: 16,
    fontWeight: 'bold',
    color: TEXT_PRIMARY,
    marginBottom: 8,
  },
  scheduleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 4,
  },
  dayName: {
    fontSize: 14,
    fontWeight: '500',
    color: TEXT_PRIMARY,
    flex: 1,
  },
  breakName: {
    fontSize: 14,
    fontWeight: '500',
    color: TEXT_SECONDARY,
    flex: 1,
  },
  scheduleTime: {
    fontSize: 14,
    color: TEXT_SECONDARY,
    textAlign: 'right',
  },
  closedText: {
    color: '#EF4444',
    fontWeight: '500',
  },
  infoText: {
    fontSize: 14,
    color: TEXT_SECONDARY,
    marginBottom: 4,
    lineHeight: 20,
  },
});

export default BookAppointmentScreen;
