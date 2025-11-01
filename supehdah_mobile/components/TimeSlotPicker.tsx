import React, { useState, useEffect } from 'react';
import { 
  View, 
  Text, 
  StyleSheet, 
  TouchableOpacity, 
  ActivityIndicator,
  ScrollView,
  Alert
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { getAvailabilitySlots, tryMultipleBaseUrls, Slot } from '../src/api';
import { format } from 'date-fns';
import { BOOKING_CONFIG, SLOT_STATE_MESSAGES } from '../src/config/bookingConfig';

export interface SlotWithState extends Slot {
  state: 'available' | 'booked' | 'past' | 'closed';
  stateMessage?: string;
}

// Process raw slots to determine their current state
const processSlotStates = (
  rawSlots: Slot[], 
  selectedDate: Date, 
  localBookings: any[] = [], 
  breakTimes: any[] = []
): SlotWithState[] => {
  const now = new Date();
  const dayOfWeek = selectedDate.getDay();
  
  return rawSlots.map((slot) => {
    let state: 'available' | 'booked' | 'past' | 'closed' = 'available';
    
    // Create datetime for this slot using the selected date and slot start time
    const [hours, minutes] = slot.start.split(':');
    const slotDateTime = new Date(selectedDate);
    slotDateTime.setHours(parseInt(hours, 10), parseInt(minutes, 10), 0, 0);
    
    // Check if this slot is in the past
    if (slotDateTime.getTime() <= now.getTime()) {
      state = 'past';
    }
    // Check if the slot is marked as booked from server response
    else if ((slot as any).isBooked === true || (slot as any).status === 'booked') {
      state = 'booked';
    }
    // Check if the slot is marked as unavailable
    else if ((slot as any).available === false || (slot as any).is_available === false) {
      state = 'closed';
    }
    // Check if this slot conflicts with break times
    else if (breakTimes.some(breakTime => {
      if (!breakTime.is_active || !breakTime.days.includes(dayOfWeek)) {
        return false;
      }
      
      const breakStart = breakTime.start_time;
      const breakEnd = breakTime.end_time;
      const slotStart = slot.start;
      const slotEnd = slot.end;
      
      // Check if slot overlaps with break time
      return (slotStart >= breakStart && slotStart < breakEnd) || 
             (slotEnd > breakStart && slotEnd <= breakEnd) ||
             (slotStart <= breakStart && slotEnd >= breakEnd);
    })) {
      state = 'closed';
    }
    // Check if this slot is locally booked (immediate UI feedback)
    else if (localBookings.some(booking => booking.appointment_time === slot.start)) {
      state = 'booked';
    }
    
    return {
      ...slot,
      state,
      stateMessage: state === 'past' ? SLOT_STATE_MESSAGES.past : SLOT_STATE_MESSAGES[state]
    };
  });
};

interface TimeSlotPickerProps {
  clinicId: number;
  selectedDate: Date;
  onSelectSlot: (slot: Slot) => void;
  selectedSlot: Slot | null;
  breakTimes?: any[];
  clinicSettings?: any;
}

const TimeSlotPicker: React.FC<TimeSlotPickerProps> = ({
  clinicId,
  selectedDate,
  onSelectSlot,
  selectedSlot,
  breakTimes = [],
  clinicSettings = {}
}) => {
  const [slots, setSlots] = useState<SlotWithState[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);

  // Format date for API request: YYYY-MM-DD (according to README specs)
  const formattedDate = format(selectedDate, 'yyyy-MM-dd');
  
  // Validate date format to ensure it matches the expected format
  const isValidDateFormat = /^\d{4}-\d{2}-\d{2}$/.test(formattedDate);
  if (!isValidDateFormat) {
    console.warn(`TimeSlotPicker - Date format may be incorrect: ${formattedDate}, expected format: YYYY-MM-DD`);
  }
  
  console.log(`TimeSlotPicker formatted date: ${formattedDate}`);

  useEffect(() => {
    const fetchSlots = async () => {
      if (!clinicId) return;
      
      setLoading(true);
      setError(null);
      
      try {
        // Get local bookings for immediate UI feedback
        const localBookingsKey = `localBookings_${clinicId}_${formattedDate}`;
        const localBookingsStr = await AsyncStorage.getItem(localBookingsKey);
        const localBookings = localBookingsStr ? JSON.parse(localBookingsStr) : [];
        
        // First try the regular method
        try {
          console.log(`Attempting to fetch slots for clinic ${clinicId} on ${formattedDate}`);
          const response = await getAvailabilitySlots(clinicId, formattedDate);
          const processedSlots = processSlotStates(response.slots || [], selectedDate, localBookings, breakTimes);
          setSlots(processedSlots);
        } catch (initialError) {
          // If the regular method fails, try with multiple base URLs
          console.log(`Initial slot fetch failed, trying multiple base URLs`);
          const endpoint = `/clinics/${clinicId}/availability/slots/${formattedDate}`;
          
          try {
            const response = await tryMultipleBaseUrls(endpoint);
            const data = response?.data?.data || response?.data;
            
            if (data && data.slots) {
              console.log(`Success with alternate method! Found ${data.slots.length} slots`);
              const processedSlots = processSlotStates(data.slots, selectedDate, localBookings, breakTimes);
              setSlots(processedSlots);
            } else {
              throw new Error('No slots found in response');
            }
          } catch (fallbackError) {
            console.error('All fallback attempts failed:', fallbackError);
            setError('Unable to connect to the appointment server. Please check your connection and try again.');
            
            // Try fallback static data in development mode
            if (__DEV__) {
              const mockSlots: Slot[] = [
                { start: "09:00:00", end: "09:30:00", display_time: "9:00 AM - 9:30 AM" },
                { start: "09:30:00", end: "10:00:00", display_time: "9:30 AM - 10:00 AM" },
                { start: "14:00:00", end: "14:30:00", display_time: "2:00 PM - 2:30 PM" },
                { start: "14:30:00", end: "15:00:00", display_time: "2:30 PM - 3:00 PM" }
              ];
              const processedSlots = processSlotStates(mockSlots, selectedDate, localBookings, breakTimes);
              setSlots(processedSlots);
              setError('Using mock data (development mode)');
            }
          }
        }
      } catch (err: any) {
        console.error('Error fetching slots:', err?.message || err);
        setError('Failed to load available time slots. Please try again.');
      } finally {
        setLoading(false);
      }
    };

    fetchSlots();
  }, [clinicId, formattedDate]);

  // Function to format time slot for display (e.g., "14:30:00" -> "2:30 PM")
  // Ensures times are displayed in Philippines timezone (UTC+8)
  const formatTimeForDisplay = (timeString: string): string => {
    try {
      // Parse the time part from the timeString
      const [hours, minutes] = timeString.split(':');
      
      // Create a date object with these hours/minutes in Philippines timezone
      const date = new Date();
      date.setHours(parseInt(hours, 10), parseInt(minutes, 10), 0);
      const hoursNum = parseInt(hours, 10);
      const period = hoursNum >= 12 ? 'PM' : 'AM';
      const displayHours = hoursNum > 12 ? hoursNum - 12 : hoursNum === 0 ? 12 : hoursNum;
      return `${displayHours}:${minutes} ${period}`;
    } catch (e) {
      return timeString; // Return the original string if parsing fails
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#4A6FA5" />
        <Text style={styles.loadingText}>Loading available times...</Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.errorContainer}>
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity 
          style={styles.retryButton}
          onPress={async () => {
            setLoading(true);
            setError(null);
            
            // Get local bookings for immediate UI feedback
            const localBookingsKey = `localBookings_${clinicId}_${formattedDate}`;
            const localBookingsStr = await AsyncStorage.getItem(localBookingsKey);
            const localBookings = localBookingsStr ? JSON.parse(localBookingsStr) : [];
            
            // First try the main method
            getAvailabilitySlots(clinicId, formattedDate)
              .then(response => {
                const processedSlots = processSlotStates(response.slots || [], selectedDate, localBookings, breakTimes);
                setSlots(processedSlots);
              })
              .catch(err => {
                // If it fails, try with the multiple base URLs method
                console.log('Retry with fallback method');
                const endpoint = `/clinics/${clinicId}/availability/slots/${formattedDate}`;
                
                tryMultipleBaseUrls(endpoint)
                  .then(response => {
                    const data = response?.data?.data || response?.data;
                    if (data && data.slots) {
                      const processedSlots = processSlotStates(data.slots, selectedDate, localBookings, breakTimes);
                      setSlots(processedSlots);
                    } else {
                      throw new Error('No slots found in response');
                    }
                  })
                  .catch(fallbackErr => {
                    console.error('All retry methods failed:', fallbackErr);
                    setError('Unable to connect to the appointment server. Please check your connection and try again.');
                  })
                  .finally(() => setLoading(false));
              });
          }}
        >
          <Text style={styles.retryButtonText}>Retry</Text>
        </TouchableOpacity>
      </View>
    );
  }

  if (slots.length === 0) {
    return (
      <View style={styles.noSlotsContainer}>
        <Text style={styles.noSlotsText}>No available time slots for this date.</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Available Time Slots</Text>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.slotsContainer}>
        {slots.map((slot, index) => {
          const isSelected = selectedSlot && selectedSlot.start === slot.start;
          const isAvailable = slot.state === 'available';
          const slotColor = BOOKING_CONFIG.slotColors[slot.state];
          
          return (
            <TouchableOpacity
              key={index}
              style={[
                styles.slotButton,
                { backgroundColor: isSelected ? '#4A6FA5' : slotColor },
                !isAvailable && styles.disabledSlot
              ]}
              onPress={() => {
                if (!isAvailable) {
                  Alert.alert('Slot Unavailable', slot.stateMessage || SLOT_STATE_MESSAGES[slot.state]);
                  return;
                }
                onSelectSlot(slot);
              }}
              disabled={!isAvailable}
            >
              <Text style={[
                styles.slotText,
                { color: isSelected ? 'white' : (isAvailable ? '#333' : '#666') },
                !isAvailable && styles.disabledSlotText
              ]}>
                {slot.display_time || formatTimeForDisplay(slot.start)}
              </Text>
              {slot.state === 'past' && (
                <Text style={styles.slotSubText}>
                  Time has passed
                </Text>
              )}
              {slot.state === 'booked' && (
                <Text style={styles.slotSubText}>
                  Already booked
                </Text>
              )}
            </TouchableOpacity>
          );
        })}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    marginVertical: 16,
    padding: 10,
    backgroundColor: '#F8F9FA',
    borderRadius: 8,
  },
  title: {
    fontSize: 16,
    fontWeight: 'bold',
    marginBottom: 10,
    color: '#333',
  },
  slotsContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingVertical: 8,
  },
  slotButton: {
    backgroundColor: '#F0F0F0',
    paddingVertical: 10,
    paddingHorizontal: 15,
    marginRight: 10,
    marginBottom: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#DDE2E5',
    minWidth: 80,
    alignItems: 'center',
  },
  selectedSlotButton: {
    backgroundColor: '#4A6FA5',
    borderColor: '#4A6FA5',
  },
  disabledSlot: {
    opacity: 0.6,
    borderColor: '#ccc',
  },
  slotText: {
    fontSize: 14,
    color: '#333',
  },
  selectedSlotText: {
    color: 'white',
    fontWeight: 'bold',
  },
  disabledSlotText: {
    color: '#999',
  },
  slotSubText: {
    fontSize: 10,
    marginTop: 2,
    color: '#666',
    textAlign: 'center',
  },
  loadingContainer: {
    padding: 20,
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 10,
    color: '#666',
  },
  errorContainer: {
    padding: 20,
    alignItems: 'center',
  },
  errorText: {
    color: '#E74C3C',
    marginBottom: 10,
  },
  retryButton: {
    backgroundColor: '#4A6FA5',
    paddingVertical: 8,
    paddingHorizontal: 16,
    borderRadius: 6,
  },
  retryButtonText: {
    color: 'white',
    fontWeight: 'bold',
  },
  noSlotsContainer: {
    padding: 20,
    alignItems: 'center',
    backgroundColor: '#FFF9C4',
    borderRadius: 8,
  },
  noSlotsText: {
    color: '#F57F17',
  },
});

export default TimeSlotPicker;
