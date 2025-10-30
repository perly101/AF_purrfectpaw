<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\User;
use App\Models\Clinic;
use App\Models\ClinicInfo;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send notification to a doctor when a new patient is assigned
     * 
     * @param \App\Models\User $doctor The doctor receiving the notification
     * @param \App\Models\User $patient The patient being assigned
     * @return \App\Models\Notification|null
     */
    public function notifyDoctorPatientAssigned(User $doctor, User $patient)
    {
        // Ensure the user is a doctor
        if ($doctor->role !== 'doctor') {
            Log::warning('Attempted to send doctor notification to non-doctor user', ['doctor_id' => $doctor->id]);
            return null;
        }

        $data = [
            'patient_id' => $patient->id,
            'patient_name' => $patient->name,
            'message' => "New patient {$patient->name} has been assigned to you"
        ];

        return $this->createNotification(
            'doctor_assigned_patient',
            $doctor,
            $data,
            $doctor->device_token ?? null
        );
    }

    /**
     * Send notification to a clinic when a new appointment is created
     * 
     * @param \App\Models\Clinic|\App\Models\ClinicInfo $clinic The clinic receiving the notification
     * @param \App\Models\Appointment $appointment The new appointment
     * @return \App\Models\Notification|null
     */
    public function notifyClinicNewAppointment($clinic, $appointment)
    {
        try {
            // Directly use ClinicInfo for notifications, as we've added the notifications relationship to it
            if ($clinic instanceof ClinicInfo) {
                // We can now use ClinicInfo directly
                Log::info('Creating notification for ClinicInfo', [
                    'clinic_id' => $clinic->id,
                    'user_id' => $clinic->user_id,
                    'appointment_id' => $appointment->id
                ]);
            } else if (!($clinic instanceof ClinicInfo)) {
                Log::error('Invalid clinic type provided to notification service', [
                    'type' => get_class($clinic)
                ]);
                return null;
            }
            
            // Use owner_name if patient relation doesn't exist or patient has no name
            $patientName = $appointment->owner_name ?? 'A patient';
            
            $data = [
                'title' => 'New Appointment',
                'body' => "{$patientName} has booked a new appointment",
                'appointment_id' => $appointment->id,
                'patient_name' => $patientName,
                'appointment_date' => $appointment->appointment_date ?? $appointment->created_at
            ];
            
            if (isset($appointment->appointment_date)) {
                $data['body'] .= " on " . date('F j, Y', strtotime($appointment->appointment_date));
            }
            
            // Get clinic admins device tokens if available
            $deviceToken = $this->getClinicAdminDeviceToken($clinic);
            
            Log::info('Preparing to create notification', [
                'type' => 'clinic_new_appointment',
                'clinic_id' => $clinic->id,
                'has_device_token' => !empty($deviceToken)
            ]);
            
            $notification = $this->createNotification(
                'clinic_new_appointment',
                $clinic,
                $data,
                $deviceToken
            );
            
            if ($notification) {
                Log::info('Successfully created appointment notification', [
                    'notification_id' => $notification->id,
                    'clinic_id' => $clinic->id
                ]);
            } else {
                Log::error('Failed to create appointment notification', [
                    'clinic_id' => $clinic->id,
                    'appointment_id' => $appointment->id
                ]);
            }
            
            return $notification;
        } catch (\Exception $e) {
            Log::error('Error creating appointment notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'appointment_id' => $appointment->id ?? 'unknown'
            ]);
            return null;
        }
    }

    /**
     * Send notification to a doctor when an appointment is assigned
     * 
     * @param \App\Models\Doctor $doctor The doctor receiving the notification
     * @param \App\Models\Appointment $appointment The appointment being assigned
     * @return \App\Models\Notification|null
     */
    public function notifyDoctorAppointmentAssigned($doctor, $appointment)
    {
        try {
            Log::info('Creating notification for doctor appointment assignment', [
                'doctor_id' => $doctor->id,
                'appointment_id' => $appointment->id
            ]);
            
            // Use owner_name if patient relation doesn't exist or patient has no name
            $patientName = $appointment->owner_name ?? 'A patient';
            
            // Get clinic info
            $clinicName = 'Clinic';
            if ($appointment->clinic) {
                $clinicName = $appointment->clinic->clinic_name ?? $appointment->clinic->name ?? 'Clinic';
            }
            
            $data = [
                'title' => 'New Appointment Assignment',
                'body' => "You have been assigned a new appointment with {$patientName} at {$clinicName}",
                'appointment_id' => $appointment->id,
                'patient_name' => $patientName,
                'clinic_name' => $clinicName,
                'appointment_date' => $appointment->appointment_date ?? $appointment->created_at,
                'appointment_time' => $appointment->appointment_time ?? 'TBD'
            ];
            
            if (isset($appointment->appointment_date)) {
                $data['body'] .= " on " . date('F j, Y', strtotime($appointment->appointment_date));
                if (isset($appointment->appointment_time)) {
                    $data['body'] .= " at " . date('g:i A', strtotime($appointment->appointment_time));
                }
            }
            
            // Get doctor's device token if available (from associated user)
            $deviceToken = null;
            if ($doctor->user) {
                $deviceToken = $doctor->user->device_token;
            }
            
            Log::info('Preparing to create doctor notification', [
                'type' => 'doctor_appointment_assigned',
                'doctor_id' => $doctor->id,
                'has_device_token' => !empty($deviceToken)
            ]);
            
            $notification = $this->createNotification(
                'doctor_appointment_assigned',
                $doctor,
                $data,
                $deviceToken
            );
            
            if ($notification) {
                Log::info('Successfully created doctor appointment notification', [
                    'notification_id' => $notification->id,
                    'doctor_id' => $doctor->id
                ]);
            } else {
                Log::error('Failed to create doctor appointment notification', [
                    'doctor_id' => $doctor->id,
                    'appointment_id' => $appointment->id
                ]);
            }
            
            return $notification;
        } catch (\Exception $e) {
            Log::error('Error creating doctor appointment notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'doctor_id' => $doctor->id ?? 'unknown',
                'appointment_id' => $appointment->id ?? 'unknown'
            ]);
            return null;
        }
    }

    /**
     * Send notification to a clinic when an appointment is marked as completed
     * 
     * @param \App\Models\Clinic|\App\Models\ClinicInfo $clinic The clinic receiving the notification
     * @param \App\Models\Appointment $appointment The completed appointment
     * @return \App\Models\Notification|null
     */
    public function notifyClinicAppointmentCompleted($clinic, $appointment)
    {
        // Handle the case where we might get a ClinicInfo instead of Clinic
        if ($clinic instanceof ClinicInfo) {
            $clinicModel = Clinic::where('email', $clinic->email)->first();
            if (!$clinicModel) {
                // If no matching Clinic model is found, create a notification entity for ClinicInfo
                // Note: This is a fallback solution
                $clinicNotifiableId = $clinic->id;
                $clinicNotifiableType = ClinicInfo::class;
            } else {
                $clinic = $clinicModel;
            }
        }
        
        // Use doctor relation or doctor info from appointment
        $doctorName = "A doctor";
        if ($appointment->doctor && $appointment->doctor->user) {
            $doctorUser = $appointment->doctor->user;
            $doctorName = $doctorUser->first_name . ' ' . $doctorUser->last_name;
        }
        
        // Use owner_name if patient relation doesn't exist
        $patientName = $appointment->owner_name ?? 'A patient';
        
        $data = [
            'appointment_id' => $appointment->id,
            'doctor_name' => $doctorName,
            'patient_name' => $patientName,
            'message' => "Dr. {$doctorName} has completed the appointment with {$patientName}"
        ];

        // Get clinic admins device tokens if available
        $deviceToken = $this->getClinicAdminDeviceToken($clinic);

        return $this->createNotification(
            'clinic_appointment_completed',
            $clinic,
            $data,
            $deviceToken
        );
    }

    /**
     * Create and store a notification
     * 
     * @param string $type The notification type
     * @param \Illuminate\Database\Eloquent\Model $notifiable The notifiable entity (User or Clinic)
     * @param array $data Additional data for the notification
     * @param string|null $deviceToken The FCM device token for push notifications
     * @return \App\Models\Notification|null
     */
    protected function createNotification($type, $notifiable, array $data, $deviceToken = null)
    {
        try {
            // First log what we're trying to do
            Log::info('Starting notification creation', [
                'type' => $type,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id
            ]);
            
            // Verify the notifiable has the relationship method
            if (!method_exists($notifiable, 'notifications')) {
                Log::error('Notifiable does not have notifications relationship', [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id
                ]);
                return null;
            }
            
            $notification = new Notification([
                'type' => $type,
                'data' => $data,
                'device_token' => $deviceToken
            ]);

            // Save the notification
            $result = $notifiable->notifications()->save($notification);
            
            if (!$result) {
                Log::error('Failed to save notification to database', [
                    'type' => $type,
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id
                ]);
                return null;
            }
            
            Log::info('Notification created successfully', [
                'id' => $notification->id,
                'type' => $type
            ]);

            // Here we would send the push notification if we have a device token
            // This will be implemented in the next step with Firebase integration
            if ($deviceToken) {
                $this->sendPushNotification($notification);
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'type' => $type,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? 'unknown'
            ]);
            
            return null;
        }
    }

    /**
     * Get the device token for the clinic admin(s)
     * 
     * @param \App\Models\Clinic|\App\Models\ClinicInfo $clinic
     * @return string|null
     */
    protected function getClinicAdminDeviceToken($clinic)
    {
        try {
            // Handle different clinic model types
            if ($clinic instanceof ClinicInfo) {
                // For ClinicInfo, get the owner of the clinic
                $admin = User::where('id', $clinic->user_id)
                    ->where('role', 'clinic')  // clinic users are the admins of their clinics
                    ->first();
                    
                if ($admin) {
                    Log::info('Found clinic admin user', [
                        'user_id' => $admin->id,
                        'has_device_token' => !empty($admin->device_token)
                    ]);
                } else {
                    Log::warning('No admin user found for clinic', [
                        'clinic_id' => $clinic->id,
                        'user_id' => $clinic->user_id
                    ]);
                    
                    // Let's try to get any user with role 'clinic' as fallback
                    $admin = User::where('role', 'clinic')->first();
                    if ($admin) {
                        Log::info('Using fallback admin user', ['user_id' => $admin->id]);
                    }
                }
            } elseif ($clinic instanceof Clinic) {
                // For Clinic model
                $admin = User::where('id', $clinic->user_id)
                    ->first();
            } else {
                Log::error('Invalid clinic type provided', ['type' => get_class($clinic)]);
                return null;
            }
            
            if (!$admin) {
                Log::warning('No admin found for clinic notification');
                return null;
            }
            
            // Even if we don't have a device token, return an empty string so the notification
            // will still be created and stored in the database for the dashboard
            return $admin->device_token ?? '';
            
        } catch (\Exception $e) {
            Log::error('Error getting clinic admin device token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Send a push notification via Firebase Cloud Messaging
     * 
     * @param \App\Models\Notification $notification
     * @return bool
     */
    protected function sendPushNotification(Notification $notification)
    {
        if (empty($notification->device_token)) {
            Log::info('No device token available for notification', ['id' => $notification->id]);
            return false;
        }
        
        try {
            $firebaseService = app(FirebaseService::class);
            
            $data = $notification->data;
            
            // Prepare the notification payload based on notification type
            switch ($notification->type) {
                case 'doctor_assigned_patient':
                    $title = 'New Patient Assigned';
                    $message = $data['message'] ?? 'A new patient has been assigned to you';
                    break;
                case 'doctor_appointment_assigned':
                    $title = 'New Appointment Assignment';
                    $message = $data['body'] ?? 'You have been assigned a new appointment';
                    break;
                case 'clinic_new_appointment':
                    $title = 'New Appointment';
                    $message = $data['message'] ?? 'A new appointment has been created';
                    break;
                case 'clinic_appointment_completed':
                    $title = 'Appointment Completed';
                    $message = $data['message'] ?? 'An appointment has been marked as completed';
                    break;
                default:
                    $title = 'New Notification';
                    $message = $data['message'] ?? 'You have a new notification';
            }
            
            $notificationData = array_merge($data, [
                'title' => $title,
                'message' => $message,
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'created_at' => $notification->created_at->format('Y-m-d H:i:s')
            ]);
            
            return $firebaseService->sendPushNotification(
                $notification->device_token,
                $notificationData
            );
        } catch (Exception $e) {
            Log::error('Failed to send push notification', [
                'error' => $e->getMessage(),
                'notification_id' => $notification->id
            ]);
            
            return false;
        }
    }
}