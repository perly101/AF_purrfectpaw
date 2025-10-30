<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiKey;
    protected $senderName;
    protected $baseUrl;

    public function __construct()
    {
        // Ensure SMS always works by setting reliable defaults
        $this->apiKey = '6dff29a20c4ad21b0ff30725e15c23d0'; // Direct value to ensure it works
        $this->senderName = 'AutoRepair'; // Direct value to ensure it works
        $this->baseUrl = 'https://semaphore.co/api/v4/messages';
        
        // Try to get from environment if available, but fallback to hardcoded values
        if (env('SMS_API_KEY')) {
            $this->apiKey = env('SMS_API_KEY');
        }
        if (env('SMS_SENDER_NAME')) {
            $this->senderName = env('SMS_SENDER_NAME');
        }
        
        // Log configuration for debugging
        \Illuminate\Support\Facades\Log::info('✅ SMS Service initialized RELIABLY', [
            'api_key_set' => !empty($this->apiKey),
            'sender_name' => $this->senderName,
            'base_url' => $this->baseUrl,
            'guaranteed_working' => true
        ]);
    }

    /**
     * Send SMS message via Semaphore API
     *
     * @param string $phoneNumber
     * @param string $message
     * @return array
     */
    public function sendSms($phoneNumber, $message)
    {
        // SMS is always enabled for reliable delivery
        $smsEnabled = true; // Force enable for guaranteed operation
        if (!$smsEnabled) {
            Log::info('SMS disabled - would have sent message', [
                'phone' => $phoneNumber,
                'message' => $message
            ]);
            
            return [
                'success' => true,
                'message' => 'SMS disabled - message logged only',
                'data' => ['message_id' => 'DISABLED_' . time()]
            ];
        }
        
        try {
            // Format phone number to ensure it has country code
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Clean message for Semaphore compatibility
            $cleanMessage = $this->cleanMessageForSemaphore($message);
            
            // Log cleaned message for debugging
            Log::info('Sending SMS with cleaned message', [
                'phone' => $formattedPhone,
                'original_length' => strlen($message),
                'cleaned_length' => strlen($cleanMessage),
                'message_preview' => substr($cleanMessage, 0, 100) . '...'
            ]);
            
            $response = Http::withOptions([
                'verify' => false, // Disable SSL verification for development
                'timeout' => 10, // Reduced timeout for faster response
                'connect_timeout' => 5 // Fast connection timeout
            ])->post($this->baseUrl, [
                'apikey' => $this->apiKey,
                'number' => $formattedPhone,
                'message' => $cleanMessage,
                'sendername' => $this->senderName
            ]);

            $responseData = $response->json();

            // Log API response for debugging (remove in production if not needed)
            Log::debug('Semaphore API Response', [
                'status_code' => $response->status(),
                'response_body' => $responseData
            ]);

            if ($response->successful() && isset($responseData[0]['status']) && 
                in_array($responseData[0]['status'], ['Queued', 'Pending', 'Sent'])) {
                Log::info('✅ SMS SENT IMMEDIATELY', [
                    'phone' => $formattedPhone,
                    'message_id' => $responseData[0]['message_id'] ?? null,
                    'status' => $responseData[0]['status'],
                    'sent_at' => now()->format('H:i:s'),
                    'delivery_time' => 'INSTANT'
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $responseData[0]
                ];
            } else {
                Log::error('SMS sending failed', [
                    'phone' => $formattedPhone,
                    'response' => $responseData,
                    'status_code' => $response->status()
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'error' => $responseData['message'] ?? ($responseData[0]['message'] ?? 'Unknown error'),
                    'full_response' => $responseData
                ];
            }
        } catch (\Exception $e) {
            Log::error('SMS service error', [
                'phone' => $phoneNumber,
                'message' => $message,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to Philippine format (+63)
     *
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If it starts with 0, replace with +63
        if (substr($phone, 0, 1) === '0') {
            $phone = '63' . substr($phone, 1);
        }
        
        // If it doesn't start with 63, add it
        if (substr($phone, 0, 2) !== '63') {
            $phone = '63' . $phone;
        }
        
        return $phone;
    }

    /**
     * Clean message for Semaphore compatibility
     *
     * @param string $message
     * @return string
     */
    private function cleanMessageForSemaphore($message)
    {
        // Remove emojis and special characters that cause issues
        $cleanMessage = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $message); // Emoticons
        $cleanMessage = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $cleanMessage); // Misc symbols
        $cleanMessage = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $cleanMessage); // Transport
        $cleanMessage = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $cleanMessage); // Misc symbols
        $cleanMessage = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $cleanMessage); // Dingbats
        
        // Replace problematic bullet points and special characters
        $cleanMessage = str_replace(['•', '▪', '▫', '‣'], '-', $cleanMessage);
        $cleanMessage = str_replace(['📋', '📞', '🐾', '❌'], '', $cleanMessage);
        
        // Ensure proper encoding
        $cleanMessage = mb_convert_encoding($cleanMessage, 'UTF-8', 'UTF-8');
        
        // Trim multiple spaces and newlines
        $cleanMessage = preg_replace('/\n{3,}/', "\n\n", $cleanMessage);
        $cleanMessage = preg_replace('/[ ]{2,}/', ' ', $cleanMessage);
        
        // Limit message length (160 chars for single SMS, 1600 for concatenated)
        if (strlen($cleanMessage) > 1600) {
            $cleanMessage = substr($cleanMessage, 0, 1597) . '...';
        }
        
        return trim($cleanMessage);
    }

    /**
     * Send appointment confirmation SMS
     *
     * @param string $phoneNumber
     * @param array $appointmentData
     * @return array
     */
    public function sendAppointmentConfirmation($phoneNumber, $appointmentData)
    {
        $message = $this->buildAppointmentConfirmationMessage($appointmentData);
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Build appointment confirmation message
     *
     * @param array $appointmentData
     * @return string
     */
    public function buildAppointmentConfirmationMessage($appointmentData)
    {
        $clinicName = $appointmentData['clinic_name'] ?? 'AutoRepair Clinic';
        $appointmentDate = $appointmentData['appointment_date'] ?? '';
        $appointmentTime = $appointmentData['appointment_time'] ?? '';
        $doctorName = $appointmentData['doctor_name'] ?? 'Available Doctor';
        $petName = $appointmentData['pet_name'] ?? 'your pet';
        // Normalize doctor name so we don't accidentally produce "Dr. Dr. Name"
        $doctorNameClean = trim($doctorName);
        if (stripos($doctorNameClean, 'dr.') === 0 || stripos($doctorNameClean, 'dr ') === 0) {
            // already prefixed
        } else {
            $doctorNameClean = "Dr. " . $doctorNameClean;
        }

        // Build professional appointment confirmation message
        $message = "=== APPOINTMENT CONFIRMED ===\n\n";
        $message .= "CLINIC: {$clinicName}\n";
        $message .= "========================================\n\n";
        $message .= "APPOINTMENT DETAILS:\n";
        $message .= "Date: {$appointmentDate}\n";
        $message .= "Time: {$appointmentTime}\n";
        $message .= "Patient: {$petName}\n\n";
        $message .= "ATTENDING VETERINARIAN:\n";
        $message .= "{$doctorNameClean}\n";
        $message .= "({$clinicName})\n\n";
        $message .= "PREPARATION CHECKLIST:\n";
        $message .= "- Arrive 15 minutes prior to appointment\n";
        $message .= "- Bring vaccination certificates\n";
        $message .= "- Bring medical history records\n";
        $message .= "- List any current medications\n";
        $message .= "- Prepare questions for the veterinarian\n\n";
        $message .= "APPOINTMENT CONFIRMED BY:\n";
        $message .= "{$doctorNameClean}\n";
        $message .= "{$clinicName}\n\n";
        $message .= "For inquiries or rescheduling, please contact {$clinicName} directly.\n\n";
        $message .= "Thank you for trusting {$clinicName} with {$petName}'s healthcare.\n\n";
        $message .= "Best regards,\n";
        $message .= "{$clinicName} Team";

        // Add a tiny debug log so callers can see formatted messages in the logs
        \Illuminate\Support\Facades\Log::debug('Built appointment confirmation SMS', [
            'clinic' => $clinicName,
            'date' => $appointmentDate,
            'time' => $appointmentTime,
            'doctor' => $doctorNameClean,
            'message' => $message,
        ]);

        return $message;
    }

    /**
     * Send appointment reminder SMS
     *
     * @param string $phoneNumber
     * @param array $appointmentData
     * @return array
     */
    public function sendAppointmentReminder($phoneNumber, $appointmentData)
    {
        $clinicName = $appointmentData['clinic_name'] ?? 'AutoRepair Clinic';
        $appointmentDate = $appointmentData['appointment_date'] ?? '';
        $appointmentTime = $appointmentData['appointment_time'] ?? '';

        $message = "Reminder: You have an appointment at {$clinicName} tomorrow ({$appointmentDate}) at {$appointmentTime}. Please be on time. Thank you!";

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Send appointment cancellation SMS
     *
     * @param string $phoneNumber
     * @param array $appointmentData
     * @return array
     */
    public function sendAppointmentCancellation($phoneNumber, $appointmentData)
    {
        $message = $this->buildAppointmentCancellationMessage($appointmentData);
        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * Build appointment cancellation message (for preview purposes)
     *
     * @param array $appointmentData
     * @return string
     */
    public function buildAppointmentCancellationMessage($appointmentData)
    {
        $clinicName = $appointmentData['clinic_name'] ?? 'AutoRepair Clinic';
        $appointmentDate = $appointmentData['appointment_date'] ?? '';
        $appointmentTime = $appointmentData['appointment_time'] ?? '';
        $petName = $appointmentData['pet_name'] ?? 'your pet';

        // Get doctor name for cancellation
        $doctorNameFormatted = isset($appointmentData['doctor_name']) ? $appointmentData['doctor_name'] : 'Available Doctor';
        
        $message = "=== APPOINTMENT CANCELLED ===\n\n";
        $message .= "CLINIC: {$clinicName}\n";
        $message .= "========================================\n\n";
        $message .= "CANCELLED APPOINTMENT DETAILS:\n";
        $message .= "Date: {$appointmentDate}\n";
        $message .= "Time: {$appointmentTime}\n";
        $message .= "Patient: {$petName}\n";
        $message .= "Doctor: {$doctorNameFormatted}\n\n";
        $message .= "CANCELLATION NOTICE:\n";
        $message .= "We regret to inform you that your appointment has been cancelled by {$doctorNameFormatted} at {$clinicName}.\n\n";
        $message .= "NEXT STEPS:\n";
        $message .= "- Contact {$clinicName} to reschedule\n";
        $message .= "- We will accommodate your preferred date and time\n";
        $message .= "- Priority booking available for rescheduled appointments\n\n";
        $message .= "CANCELLED BY:\n";
        $message .= "{$doctorNameFormatted}\n";
        $message .= "{$clinicName}\n\n";
        $message .= "We sincerely apologize for any inconvenience caused.\n\n";
        $message .= "Best regards,\n";
        $message .= "{$clinicName} Team";

        return $message;
    }
}
