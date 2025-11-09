﻿import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ActivityIndicator,
  TouchableOpacity,
  ScrollView,
  Alert,
  Platform,
  SafeAreaView,
  RefreshControl,
} from 'react-native';

import { Calendar } from 'react-native-calendars';
import { Ionicons } from '@expo/vector-icons';
import AsyncStorage from '@react-native-async-storage/async-storage';

import { getAvailabilityCalendarDates, getAvailabilityCalendarSlots } from '../src/calendarApi';
import { API } from '../src/api';
import { TimeSlot, SlotState, slotStyles } from '../src/types/calendar';
import { BOOKING_CONFIG } from '../src/config/bookingConfig';

import { NavigationProp, ParamListBase } from '@react-navigation/native';

// Philippines timezone offset (8 hours in milliseconds)
const PH_TIMEZONE_OFFSET = 8 * 60 * 60 * 1000;

type MarkedDates = {
  [date: string]: {
    selected?: boolean;
    selectedColor?: string;
    marked?: boolean;
    dotColor?: string;
  };
};

type ClinicCalendarScreenProps = {
  route: {
    params?: {
      clinicId?: number;
      clinicName?: string;
    };
  };
  navigation: NavigationProp<ParamListBase> & {
    navigate: (screen: string, params?: any) => void;
    goBack: () => void;
    addListener: (event: string, callback: () => void) => () => void;
    reset: (state: any) => void;
  };
};

const ClinicCalendarScreen = ({ route, navigation }: ClinicCalendarScreenProps) => {
  const { clinicId = 1, clinicName = 'Clinic' } = route.params || {};

  const [markedDates, setMarkedDates] = useState<MarkedDates>({});
  const [selectedDate, setSelectedDate] = useState<string | null>(null);
  const [timeSlots, setTimeSlots] = useState<TimeSlot[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isLoadingSlots, setIsLoadingSlots] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [availabilityInfo, setAvailabilityInfo] = useState<{
    totalSlots: number;
    availableSlots: number;
    bookedSlots: number;
  }>({ totalSlots: 0, availableSlots: 0, bookedSlots: 0 });
  const [isClinicClosed, setIsClinicClosed] = useState<boolean>(false);

  // Helper to get Manila time (Date object)
  const getManilaNow = () => {
    const now = new Date();
    const utc = now.getTime() + now.getTimezoneOffset() * 60000;
    return new Date(utc + PH_TIMEZONE_OFFSET);
  };

  const checkTokenValid = async () => {
    const token = (await AsyncStorage.getItem('token')) ||
      (await AsyncStorage.getItem('userToken')) ||
      (await AsyncStorage.getItem('accessToken'));

    if (!token) {
      console.log('❌ No authentication token found');
      navigation.reset({ index: 0, routes: [{ name: 'Login' }] });
      Alert.alert('Authentication Required', 'Please log in to continue.', [{ text: 'OK' }]);
      return false;
    }
    return true;
  };

  // Fetch available dates for the calendar
  const fetchAvailableDates = async () => {
    try {
      setIsLoading(true);
      setError(null);

      const response: any = await getAvailabilityCalendarDates(clinicId);
      const availableDates: string[] = response?.dates || [];
      const closedDates: string[] = response?.closed_dates || [];

      const marked: MarkedDates = {};

      availableDates.forEach((date) => {
        marked[date] = { marked: true, dotColor: '#34D399' };
      });

      closedDates.forEach((date) => {
        marked[date] = { marked: true, dotColor: '#EF4444' };
      });

      // If same-day booking is enabled, always ensure today is marked as available
      if (BOOKING_CONFIG.samedayOnly) {
        const today = new Date();
        const todayStr = today.getFullYear() + '-' + 
          String(today.getMonth() + 1).padStart(2, '0') + '-' + 
          String(today.getDate()).padStart(2, '0'); // YYYY-MM-DD
        
        // Only mark today as available if it's not explicitly closed
        if (!closedDates.includes(todayStr)) {
          marked[todayStr] = { marked: true, dotColor: '#34D399' };
        }
      }

      setMarkedDates(marked);
    } catch (err) {
      console.error('Error fetching available dates:', err);
      setError('Failed to load available dates. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  // Fetch time slots for a specific date (dateParam is YYYY-MM-DD)
  const fetchTimeSlots = async (dateParam: string) => {
    try {
      if (!dateParam) throw new Error('No date provided to fetchTimeSlots');

      setIsLoadingSlots(true);

      const tokenValid = await checkTokenValid();
      if (!tokenValid) throw new Error('Token validation failed');

      // Clear any cached markers for this date
      try {
        await AsyncStorage.removeItem(`slots_cache_${clinicId}_${dateParam}`);
      } catch (e) {
        console.log('Error clearing cache:', e);
      }

      // Optional: try to get booking count first
      try {
        const bookingCountUrl = `/clinics/${clinicId}/appointments/count?date=${dateParam}&t=${Date.now()}`;
        const countResponse = await API.get(bookingCountUrl);
        if (countResponse?.data?.count !== undefined) {
          setAvailabilityInfo((prev) => ({ ...prev, bookedSlots: countResponse.data.count }));
        }
      } catch (e) {
        // ignore
      }

      const cacheBuster = Date.now();
      const response: any = await getAvailabilityCalendarSlots(clinicId, dateParam + `?cb=${cacheBuster}`);

      // Check if clinic is closed on this day
      if (response?.data?.is_available === false || response?.is_available === false) {
        console.log('Clinic is closed on this day:', response?.data?.message || response?.message);
        setTimeSlots([]);
        setAvailabilityInfo({ totalSlots: 0, availableSlots: 0, bookedSlots: 0 });
        setIsClinicClosed(true);
        return;
      }

      setIsClinicClosed(false);

      const slotsData: TimeSlot[] = response?.slots || [];

      // Try to detect booked slots from different sources
      let appointmentsCount = 0;
      let bookedSlots: string[] = [];

      try {
        const possibleUrls = [
          `/clinics/${clinicId}/appointments/count?date=${dateParam}&t=${Date.now()}`,
          `/clinics/${clinicId}/appointments?date=${dateParam}&t=${Date.now()}`,
          `/clinic/${clinicId}/appointments?date=${dateParam}&t=${Date.now()}`,
          `/admin/clinics/${clinicId}/appointments?date=${dateParam}&t=${Date.now()}`,
          `/admin/appointments?clinic_id=${clinicId}&date=${dateParam}&t=${Date.now()}`,
          `/clinics/${clinicId}/appointments?t=${Date.now()}`,
        ];

        for (const url of possibleUrls) {
          try {
            const res = await API.get(url);
            if (res?.data?.count !== undefined) {
              appointmentsCount = res.data.count;
              break;
            }

            let appointments = null;
            if (res?.data?.appointments) appointments = res.data.appointments;
            else if (Array.isArray(res?.data)) appointments = res.data;
            else if (res?.data?.data && Array.isArray(res.data.data)) appointments = res.data.data;

            if (appointments && appointments.length > 0) {
              // Filter for our date when necessary
              if (!url.includes('date=')) {
                appointments = appointments.filter((appt: any) => {
                  return appt.appointment_date === dateParam || appt.date === dateParam || (appt.appointment_time && appt.appointment_time.startsWith(dateParam));
                });
              }

              if (appointments.length > 0) {
                appointmentsCount = appointments.length;
                appointments.forEach((appt: any) => {
                  const timeField = appt.appointment_time || appt.time || appt.start_time;
                  if (timeField) bookedSlots.push(timeField);
                });
                break;
              }
            }
          } catch (e) {
            // continue
          }
        }
      } catch (e) {
        console.log('Failed to fetch appointments list, falling back to slot data');
      }

      const bookedSlotsFromStatus = slotsData.filter((s) => s.isBooked === true || s.status === 'booked').length;

      // Inspect slot-level booking fields too
      let bookingFieldsCount = 0;
      slotsData.forEach((slot) => {
        if (
          (slot as any).bookings > 0 ||
          (slot as any).appointment_count > 0 ||
          (slot as any).booked_count > 0 ||
          slot.isBooked === true ||
          slot.status === 'booked' ||
          (slot as any).availability === false ||
          (slot as any).available === false
        ) {
          bookingFieldsCount++;
        }
      });

      const localBookingsKey = `localBookings_${clinicId}_${dateParam}`;
      const localBookingsStr = await AsyncStorage.getItem(localBookingsKey);
      const localBookings = localBookingsStr ? JSON.parse(localBookingsStr) : [];
      
      console.log('LOCAL BOOKINGS CHECK:', {
        key: localBookingsKey,
        data: localBookings,
        count: localBookings.length
      });
      
      // Add local booking times to booked slots for immediate UI feedback
      localBookings.forEach((booking: any) => {
        if (booking.appointment_time && !bookedSlots.includes(booking.appointment_time)) {
          bookedSlots.push(booking.appointment_time);
          console.log('Added local booking:', booking.appointment_time);
        }
      });

      const possibleCounts = [
        appointmentsCount,
        bookedSlotsFromStatus,
        bookingFieldsCount,
        bookedSlots.length,
        localBookings.length,
        response?.bookedSlots || 0,
      ].filter((n) => n > 0);

      const finalBookedCount = possibleCounts.length > 0 ? Math.max(...possibleCounts) : 0;
      
      console.log('DEBUG BOOKING COUNTS:', {
        appointmentsCount,
        bookedSlotsFromStatus,
        bookingFieldsCount,
        'bookedSlots.length': bookedSlots.length,
        'localBookings.length': localBookings.length,
        'response?.bookedSlots': response?.bookedSlots,
        possibleCounts,
        finalBookedCount,
        bookedSlots
      });

      // Mark specific booked slots in the slot array if we found times
      let updatedSlots = slotsData;
      if (bookedSlots.length > 0) {
        console.log('Checking booked slots against available slots:', {
          bookedSlots,
          availableSlotStartTimes: slotsData.map(s => s.start)
        });
        
        updatedSlots = slotsData.map((slot) => {
          const isSlotBooked = bookedSlots.some((bookedTime) => {
            if (!bookedTime || !slot.start) return false;
            
            // Normalize both times to HH:MM format for comparison
            const normalizeTime = (time: string) => {
              return time.split(':').slice(0, 2).join(':');
            };
            
            const normalizedBookedTime = normalizeTime(bookedTime);
            const normalizedSlotTime = normalizeTime(slot.start);
            
            const matches = normalizedBookedTime === normalizedSlotTime;
            if (matches) {
              console.log('Found matching booked slot:', {
                bookedTime,
                slotStart: slot.start,
                normalizedBookedTime,
                normalizedSlotTime
              });
            }
            return matches;
          });
          const updatedSlot = { ...slot, isBooked: isSlotBooked || slot.isBooked, status: isSlotBooked ? 'booked' : slot.status };
          if (isSlotBooked) {
            console.log('MARKED SLOT AS BOOKED:', updatedSlot);
          }
          return updatedSlot;
        });
        
        console.log('UPDATED SLOTS AFTER BOOKING LOGIC:', {
          originalSlotCount: slotsData.length,
          updatedSlotCount: updatedSlots.length,
          slotsMarkedAsBooked: updatedSlots.filter(s => s.isBooked).length,
          bookedSlotDetails: updatedSlots.filter(s => s.isBooked).map(s => ({ start: s.start, isBooked: s.isBooked }))
        });
      } else {
        console.log('NO BOOKED SLOTS TO PROCESS - bookedSlots array is empty');
      }

      // Process slot states relative to Manila time
      const nowManila = getManilaNow();

      const processedSlots = updatedSlots.map((slot) => {
        let state: SlotState = 'available';

        try {
          // Parse slot time more reliably
          let slotDateTime: Date;
          
          // Try different parsing methods for slot.start (HH:MM or HH:MM:SS format)
          if (slot.start.includes(':')) {
            // Create date string in Manila timezone
            const timeStr = slot.start.length === 5 ? `${slot.start}:00` : slot.start; // Add seconds if missing
            slotDateTime = new Date(`${dateParam}T${timeStr}+08:00`); // Explicitly set Manila timezone
            
            // Fallback: if parsing fails, try local time interpretation
            if (isNaN(slotDateTime.getTime())) {
              slotDateTime = new Date(`${dateParam} ${slot.start}`);
              if (!isNaN(slotDateTime.getTime())) {
                // Convert local time to Manila time for comparison
                const utc = slotDateTime.getTime() + slotDateTime.getTimezoneOffset() * 60000;
                slotDateTime = new Date(utc + PH_TIMEZONE_OFFSET);
              }
            }
          } else {
            // If no colon, assume invalid format
            throw new Error('Invalid time format');
          }

          // Check if slot time has passed
          if (!isNaN(slotDateTime.getTime())) {
            // Add buffer for slot duration (default 30 minutes)
            const slotEndTime = new Date(slotDateTime.getTime() + ((slot.duration || 30) * 60000));
            
            // Compare slot end time with current Manila time
            if (slotEndTime.getTime() <= nowManila.getTime()) {
              state = 'past';
            }
            
            console.log('TIME CHECK:', {
              slotStart: slot.start,
              slotDateTime: slotDateTime.toISOString(),
              slotEndTime: slotEndTime.toISOString(),
              nowManila: nowManila.toISOString(),
              isPast: state === 'past',
              timeDiff: slotEndTime.getTime() - nowManila.getTime()
            });
          }
        } catch (e) {
          console.warn('Time parsing failed for slot:', slot.start, e);
          // If parsing fails, assume it's available to avoid blocking valid slots
        }

        // Apply other state logic only if not past
        if (state !== 'past') {
          if (slot.isBooked || slot.status === 'booked') {
            state = 'booked';
          } else if ((slot as any).availability === false || (slot as any).available === false) {
            state = 'closed';
          }
        }

        return {
          ...slot,
          state,
          stateMessage: state === 'past' ? 'Time already has passed.' : undefined,
        } as TimeSlot;
      });

      const actuallyAvailableSlots = processedSlots.filter((s) => s.state === 'available').length;

      // SHOW ALL SLOTS including booked ones so user can see what's been booked
      const visibleSlots = processedSlots;
      
      console.log('SLOT FILTERING DEBUG:', {
        totalProcessed: processedSlots.length,
        bookedCount: processedSlots.filter(s => s.state === 'booked').length,
        visibleAfterFilter: visibleSlots.length,
        finalBookedCount,
        bookedSlotDetails: processedSlots.filter(s => s.state === 'booked').map(s => ({ start: s.start, isBooked: s.isBooked, status: s.status })),
        allSlotStates: processedSlots.map(s => ({ start: s.start, state: s.state, isBooked: s.isBooked }))
      });

      setTimeSlots(visibleSlots); // Show only non-booked slots
      setAvailabilityInfo({ totalSlots: processedSlots.length, availableSlots: actuallyAvailableSlots, bookedSlots: finalBookedCount });
    } catch (err: any) {
      console.error('Error fetching time slots:', err);
      setTimeSlots([]);
      Alert.alert('Error', 'Failed to load available time slots. Please check your connection and try again.');
    } finally {
      setIsLoadingSlots(false);
    }
  };

  const handleDateSelect = (day: any) => {
    const dateString = day.dateString;

    // SAME-DAY ONLY BOOKING RESTRICTION - Always enforced
    const today = new Date();
    const todayStr = today.getFullYear() + '-' + 
      String(today.getMonth() + 1).padStart(2, '0') + '-' + 
      String(today.getDate()).padStart(2, '0'); // YYYY-MM-DD

    if (dateString !== todayStr) {
      Alert.alert('Same-Day Booking Only', 'Bookings are allowed for today only.', [{ text: 'OK' }]);
      return;
    }

    if (!markedDates[dateString]) {
      Alert.alert('Date Unavailable', 'Sorry, this date is not available for booking. Please select a date with a colored dot.');
      return;
    }

    if (markedDates[dateString]?.dotColor === '#EF4444') {
      Alert.alert('Clinic Closed', 'Sorry, the clinic is closed on this date. Please select a date with a green dot.');
      return;
    }

    setSelectedDate(dateString);

    // Update markedDates to show selected state
    const updatedMarkedDates = { ...markedDates };
    Object.keys(updatedMarkedDates).forEach((d) => {
      if (updatedMarkedDates[d].selected) updatedMarkedDates[d] = { ...updatedMarkedDates[d], selected: false };
    });

    updatedMarkedDates[dateString] = { ...updatedMarkedDates[dateString], selected: true, selectedColor: '#2563EB' };
    setMarkedDates(updatedMarkedDates);

    fetchTimeSlots(dateString);

    // Short auto refresh to catch recent bookings
    setTimeout(() => {
      if (dateString === selectedDate) fetchTimeSlots(dateString);
    }, 5000);
  };

  const handleTimeSlotSelect = (slot: TimeSlot) => {
    if (!selectedDate) return;
    navigation.navigate('ClinicAppointments', { clinicId, clinicName, date: selectedDate, timeSlot: slot });
  };

  const onRefresh = async () => {
    setRefreshing(true);
    try {
      const tokenValid = await checkTokenValid();
      if (!tokenValid) return;

      if (selectedDate) await AsyncStorage.removeItem(`slots_cache_${clinicId}_${selectedDate}`);

      await fetchAvailableDates();
      if (selectedDate) await fetchTimeSlots(selectedDate);
    } catch (e) {
      console.error('Error during refresh:', e);
    } finally {
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchAvailableDates();
  }, [clinicId]);

  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', async () => {
      const tokenValid = await checkTokenValid();
      if (!tokenValid) return;

      // Check for fresh booking markers when returning to calendar
      let hasFreshBooking = false;
      try {
        if (selectedDate) {
          const refreshKey = `refresh_calendar_${clinicId}_${selectedDate}`;
          const refreshMarker = await AsyncStorage.getItem(refreshKey);
          if (refreshMarker) {
            hasFreshBooking = true;
            // Remove the marker after detecting it
            await AsyncStorage.removeItem(refreshKey);
            console.log('Detected fresh booking, will force refresh');
          }
        }
      } catch (e) {
        console.log('Error checking refresh markers:', e);
      }

      // Clear ALL caches aggressively when returning to calendar
      try {
        const keys = await AsyncStorage.getAllKeys();
        const cacheKeys = keys.filter(key => 
          key.includes('slots_cache') || 
          key.includes('availability_cache') ||
          key.includes(`clinic_${clinicId}`)
        );
        if (cacheKeys.length > 0) {
          await AsyncStorage.multiRemove(cacheKeys);
          console.log('Cleared cache keys:', cacheKeys);
        }
      } catch (e) {
        console.log('Cache clearing error:', e);
      }

      await fetchAvailableDates();
      if (selectedDate) {
        // Force fresh data with multiple attempts, especially if there's a fresh booking
        fetchTimeSlots(selectedDate);
        setTimeout(() => fetchTimeSlots(selectedDate), 1000);
        setTimeout(() => fetchTimeSlots(selectedDate), 3000);
        
        // Extra refreshes if there was a fresh booking
        if (hasFreshBooking) {
          setTimeout(() => fetchTimeSlots(selectedDate), 5000);
          setTimeout(() => fetchTimeSlots(selectedDate), 8000);
        }
      }
    });

    return unsubscribe;
  }, [navigation, selectedDate, clinicId]);

  // Debugging info
  useEffect(() => {
    if (timeSlots.length > 0) {
      const actualBookedSlots = timeSlots.filter((s) => s.isBooked || s.status === 'booked').length;
      if (actualBookedSlots !== availabilityInfo.bookedSlots) {
        console.warn(`Booking count mismatch: UI shows ${availabilityInfo.bookedSlots}, but ${actualBookedSlots} slots are marked as booked`);
      }
    }
  }, [timeSlots, availabilityInfo.bookedSlots]);

  // Configurable slot colors - can be fetched from API in future
  const slotColors = {
    available: '#28a745',  // Green
    booked: '#dc3545',     // Red  
    past: '#6c757d',       // Gray
    closed: '#ffc107'      // Yellow
  };

  // Slot styling configuration
  const slotStyles = {
    available: {
      backgroundColor: slotColors.available,
      textColor: '#ffffff',
      iconColor: '#ffffff'
    },
    booked: {
      backgroundColor: slotColors.booked,
      textColor: '#ffffff', 
      iconColor: '#ffffff'
    },
    past: {
      backgroundColor: slotColors.past,
      textColor: '#ffffff',
      iconColor: '#ffffff'
    },
    closed: {
      backgroundColor: slotColors.closed,
      textColor: '#000000',
      iconColor: '#000000'
    }
  };

  const getSlotIcon = (state?: SlotState) => {
    switch (state) {
      case 'available':
        return 'checkmark-circle-outline' as const;
      case 'booked':
        return 'close-circle-outline' as const;
      case 'past':
        return 'time-outline' as const;
      case 'closed':
        return 'ban-outline' as const;
      default:
        return 'help-circle-outline' as const;
    }
  };

  const getSlotColor = (state?: SlotState) => {
    switch (state) {
      case 'available':
        return slotColors.available;
      case 'booked':
        return slotColors.booked;
      case 'past':
        return slotColors.past;
      case 'closed':
        return slotColors.closed;
      default:
        return '#6c757d';
    }
  };

  const getSlotMessage = (slot: TimeSlot): string => {
    if (slot.stateMessage) return slot.stateMessage;
    switch (slot.state) {
      case 'available':
        return 'Available for booking';
      case 'booked':
        return 'Already booked';
      case 'past':
        return 'Time already has passed.';
      case 'closed':
        return 'Clinic closed';
      default:
        return 'Unavailable';
    }
  };

  const formatDateDisplay = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' } as const;
    return `${date.toLocaleDateString('en-US', options).replace(/\d{1,2}, /, '').replace(', ', ', ')}`;
  };

  return (
    <SafeAreaView style={styles.safe}>
      <ScrollView style={styles.container} refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}>
        <View style={styles.header}>
          <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
            <Ionicons name="chevron-back" size={20} color="#6B7280" />
          </TouchableOpacity>
          <View style={styles.headerTextWrap}>
            <Text style={styles.headerClinic}>{clinicName}</Text>
            <Text style={styles.headerSubtitle}>Choose a date — spots update in real time</Text>
          </View>
        </View>

        {isLoading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color="#4f46e5" />
            <Text style={styles.loadingText}>Loading calendar...</Text>
          </View>
        ) : error ? (
          <View style={styles.errorContainer}>
            <Text style={styles.errorText}>{error}</Text>
            <TouchableOpacity style={styles.retryButton} onPress={fetchAvailableDates}>
              <Text style={styles.retryButtonText}>Retry</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <View style={styles.calendarCard}>
            <Calendar markedDates={markedDates} onDayPress={handleDateSelect} hideExtraDays enableSwipeMonths theme={{ selectedDayBackgroundColor: '#2563EB', todayTextColor: '#2563EB', arrowColor: '#4f46e5', dotColor: '#34D399', textDayFontWeight: '600', textMonthFontWeight: '700', textDayHeaderFontWeight: '600', monthTextColor: '#111827' }} style={styles.calendar} />

            <View style={styles.legendContainer}>
              <View style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: '#34D399' }]} />
                <Text style={styles.legendText}>Available</Text>
              </View>
              <View style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: '#2563EB' }]} />
                <Text style={styles.legendText}>Selected</Text>
              </View>
              <View style={styles.legendItem}>
                <View style={[styles.legendDot, { backgroundColor: '#EF4444' }]} />
                <Text style={styles.legendText}>Closed</Text>
              </View>
            </View>
          </View>
        )}

        {selectedDate && (
          <View style={styles.timeSlotsContainer}>
            <View style={styles.dateRow}>
              <Text style={styles.dateTitle}>{formatDateDisplay(selectedDate)}</Text>
              <View style={styles.badges}>
                {(() => {
                  const isSlotAvailable = (slot: TimeSlot) => !slot.isBooked && slot.status !== 'booked' && (slot as any).available !== false && (slot as any).availability !== false;
                  const availableCount = timeSlots.filter(isSlotAvailable).length;
                  const hasAvailableSlots = availableCount > 0;

                  return (
                    <View style={[styles.badge, hasAvailableSlots && styles.badgeAvailable]}>
                      <Text style={[styles.badgeNumber, hasAvailableSlots && styles.badgeAvailableText]}>{timeSlots.length > 0 ? availableCount : availabilityInfo.availableSlots}</Text>
                      <Text style={[styles.badgeLabel, hasAvailableSlots && styles.badgeAvailableLabelText]}>open</Text>
                    </View>
                  );
                })()}

                <View style={[styles.badge, styles.badgeMuted, (availabilityInfo.bookedSlots > 0 || timeSlots.some((slot) => slot.isBooked || slot.status === 'booked')) && styles.badgeActive]}>
                  <Text style={[styles.badgeNumber, (availabilityInfo.bookedSlots > 0 || timeSlots.some((slot) => slot.isBooked || slot.status === 'booked')) && styles.badgeActiveText]}>{timeSlots.length > 0 ? timeSlots.filter((slot) => slot.isBooked || slot.status === 'booked').length : availabilityInfo.bookedSlots}</Text>
                  <Text style={[styles.badgeLabel, (availabilityInfo.bookedSlots > 0 || timeSlots.some((slot) => slot.isBooked || slot.status === 'booked')) && styles.badgeActiveLabelText]}>booked</Text>
                </View>
                

              </View>
            </View>

            {isLoadingSlots ? (
              <View style={styles.loadingSlotsContainer}>
                <ActivityIndicator size="small" color="#4f46e5" />
                <Text style={styles.loadingText}>Loading available slots...</Text>
              </View>
            ) : timeSlots.length === 0 ? (
              <View style={styles.noSlotsContainer}>
                <Ionicons 
                  name={isClinicClosed ? "business-outline" : "calendar-outline"} 
                  size={48} 
                  color={isClinicClosed ? "#dc3545" : "#6c757d"} 
                  style={{marginBottom: 16}}
                />
                <Text style={[styles.noSlotsText, {color: isClinicClosed ? "#dc3545" : "#6c757d"}]}>
                  {isClinicClosed ? "Clinic is closed on this day" : "No available time slots for this date"}
                </Text>
                {!isClinicClosed && (
                  <Text style={styles.noSlotsSubText}>Please try again later or select another date.</Text>
                )}
              </View>
            ) : (
              <View style={styles.slotsList}>
                {timeSlots.map((slot, index) => {
                  const isDisabled = slot.state !== 'available';
                  const styleConfig = slotStyles[slot.state || 'closed'];

                  return (
                    <TouchableOpacity 
                      key={index} 
                      style={[
                        styles.slotItem, 
                        { 
                          backgroundColor: styleConfig.backgroundColor,
                          borderLeftColor: styleConfig.backgroundColor,
                          opacity: isDisabled ? 0.8 : 1 
                        }
                      ]} 
                      onPress={() => !isDisabled && handleTimeSlotSelect(slot)} 
                      disabled={isDisabled} 
                      activeOpacity={0.85}
                    >
                      <View style={[
                        styles.slotLeft, 
                        { backgroundColor: 'rgba(255,255,255,0.2)' }
                      ]}>
                        <Ionicons name={getSlotIcon(slot.state)} size={18} color={styleConfig.iconColor} />
                      </View>

                      <View style={styles.slotMiddle}>
                        <Text style={[styles.slotText, { color: styleConfig.textColor }]}>{slot.display_time}</Text>
                        <Text style={[styles.slotSubText, { color: styleConfig.textColor, opacity: 0.8 }]}>{getSlotMessage(slot)}</Text>
                      </View>

                      <View style={styles.slotRight}>
                        {!isDisabled ? (
                          <View style={[styles.chevronCircle, { backgroundColor: 'rgba(255,255,255,0.2)' }]}>
                            <Ionicons name="chevron-forward" size={18} color={styleConfig.textColor} />
                          </View>
                        ) : (
                          <Ionicons name={slot.state === 'past' ? 'time' : 'lock-closed'} size={18} color={styleConfig.textColor} />
                        )}
                      </View>
                    </TouchableOpacity>
                  );
                })}
              </View>
            )}
          </View>
        )}

        <View style={styles.infoContainer}>
          <Text style={styles.infoTitle}>Booking Information</Text>
          <View style={styles.infoItem}>
            <Ionicons name="information-circle-outline" size={20} color="#4f46e5" />
            <Text style={styles.infoText}>Green dots show dates that currently have open appointment slots.</Text>
          </View>
          <View style={styles.infoItem}>
            <Ionicons name="calendar-outline" size={20} color="#4f46e5" />
            <Text style={styles.infoText}>Select a date and then pick a time slot to proceed.</Text>
          </View>
          <View style={styles.infoItem}>
            <Ionicons name="time-outline" size={20} color="#4f46e5" />
            <Text style={styles.infoText}>Booked slots are shown as "Already booked" and are disabled.</Text>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: '#F3F4F6' },
  container: { flex: 1, backgroundColor: 'transparent' },
  header: { 
    backgroundColor: '#FFFFFF',
    paddingTop: 50,
    paddingBottom: 24,
    paddingHorizontal: 20,
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
    flexDirection: 'row', 
    alignItems: 'center'
  },
  backButton: { 
    marginRight: 12, 
    width: 36,
    height: 36,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#F9FAFB'
  },
  headerTextWrap: { flex: 1 },
  headerClinic: { 
    color: '#111827', 
    fontSize: 18, 
    fontWeight: '500',
    letterSpacing: -0.2
  },
  headerSubtitle: { 
    color: '#6B7280', 
    fontSize: 12, 
    marginTop: 2,
    fontWeight: '400'
  },
  calendarCard: { backgroundColor: '#ffffff', marginHorizontal: 16, borderRadius: 12, padding: 10, marginBottom: 14, ...Platform.select({ ios: { shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.06, shadowRadius: 8 }, android: { elevation: 3 } }) },
  calendar: { borderRadius: 8 },
  legendContainer: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 12, paddingTop: 6, borderTopWidth: 1, borderTopColor: '#F3F4F6' },
  legendItem: { flexDirection: 'row', alignItems: 'center', flex: 1, justifyContent: 'center' },
  legendDot: { width: 12, height: 12, borderRadius: 8, marginRight: 8 },
  legendText: { fontSize: 12, color: '#374151' },
  loadingContainer: { padding: 24, alignItems: 'center', justifyContent: 'center' },
  loadingText: { marginTop: 10, color: '#6B7280', fontSize: 13 },
  errorContainer: { padding: 20, alignItems: 'center' },
  errorText: { color: '#EF4444', fontSize: 14, marginBottom: 10, textAlign: 'center' },
  retryButton: { backgroundColor: '#111827', paddingVertical: 8, paddingHorizontal: 16, borderRadius: 8 },
  retryButtonText: { color: '#ffffff', fontSize: 14 },
  timeSlotsContainer: { backgroundColor: '#ffffff', padding: 16, marginHorizontal: 16, borderRadius: 12, marginBottom: 14, ...Platform.select({ ios: { shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.05, shadowRadius: 8 }, android: { elevation: 3 } }) },
  dateRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16 },
  dateTitle: { fontSize: 18, fontWeight: '700', color: '#111827', flex: 1 },
  badges: { flexDirection: 'row', marginLeft: 12 },
  badge: { backgroundColor: '#EFF6FF', paddingHorizontal: 20, paddingVertical: 6, borderRadius: 20, marginLeft: 8, alignItems: 'center', justifyContent: 'center', minWidth: 60 },
  badgeMuted: { backgroundColor: '#FEE2E2' },
  badgeActive: { backgroundColor: '#DC2626', borderWidth: 0, borderRadius: 20, ...Platform.select({ ios: { shadowColor: '#000', shadowOffset: { width: 0, height: 3 }, shadowOpacity: 0.4, shadowRadius: 5 }, android: { elevation: 5 } }) },
  badgeNumber: { fontWeight: '700', fontSize: 16, color: '#0f172a' },
  badgeActiveText: { color: '#ffffff', fontWeight: '800', fontSize: 18 },
  badgeAvailable: { backgroundColor: '#10B981', borderWidth: 0, borderRadius: 20, ...Platform.select({ ios: { shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.3, shadowRadius: 3 }, android: { elevation: 4 } }) },
  badgeAvailableText: { color: '#ffffff', fontWeight: '700', fontSize: 16 },
  badgeLabel: { fontSize: 10, color: '#374151', marginTop: 2 },
  badgeActiveLabelText: { color: '#ffffff', fontWeight: '500' },
  badgeAvailableLabelText: { color: '#ffffff', fontWeight: '500' },
  loadingSlotsContainer: { padding: 20, alignItems: 'center' },
  noSlotsText: { color: '#6B7280', fontSize: 14, textAlign: 'center', fontStyle: 'italic', padding: 18 },
  noSlotsContainer: { alignItems: 'center', justifyContent: 'center', padding: 32 },
  noSlotsSubText: { color: '#9CA3AF', fontSize: 12, textAlign: 'center', marginTop: 8 },
  slotsList: { marginTop: 12 },
  slotItem: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, paddingHorizontal: 10, backgroundColor: '#F8FAFF', borderRadius: 12, marginBottom: 10, borderLeftWidth: 4, borderLeftColor: '#4f46e5' },
  slotLeft: { width: 40, height: 40, borderRadius: 10, backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#E6E9F2', alignItems: 'center', justifyContent: 'center', marginRight: 12 },
  bookedLeft: { backgroundColor: '#EF4444', borderColor: '#EF4444' },
  slotMiddle: { flex: 1 },
  slotText: { fontSize: 16, color: '#0f172a', fontWeight: '600' },
  slotSubText: { fontSize: 12, color: '#6B7280', marginTop: 2 },
  slotRight: { marginLeft: 12, alignItems: 'center', justifyContent: 'center' },
  chevronCircle: { width: 36, height: 36, borderRadius: 18, backgroundColor: '#4f46e5', alignItems: 'center', justifyContent: 'center' },
  bookedSlotItem: { borderLeftColor: '#EF4444', backgroundColor: '#FFF1F2', opacity: 0.95 },
  bookedSlotText: { color: '#9B1C1C' },
  infoContainer: { backgroundColor: '#ffffff', padding: 16, marginHorizontal: 16, borderRadius: 12, marginBottom: 28, ...Platform.select({ ios: { shadowColor: '#000', shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.04, shadowRadius: 8 }, android: { elevation: 2 } }) },
  infoTitle: { fontSize: 16, fontWeight: '700', color: '#111827', marginBottom: 12 },
  infoItem: { flexDirection: 'row', alignItems: 'center', marginBottom: 12 },
  infoText: { fontSize: 13, color: '#6B7280', marginLeft: 10, flex: 1 },
});

export default ClinicCalendarScreen;