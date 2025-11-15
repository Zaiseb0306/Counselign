<?php

namespace App\Services;

class CounselorEmailTemplates
{
    /**
     * Create HTML email body for appointment approval notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return string The HTML email body
     */
    public static function createApprovalEmailBody(array $appointmentData, array $counselorData): string
    {
        $counselorName = $counselorData['name'] ?? 'Your Counselor';
        $counselorEmail = $counselorData['email'] ?? '';
        $counselorId = $counselorData['counselor_id'] ?? '';
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .appointment-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #28a745; }
                .counselor-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #007bff; }
                .success-info { background-color: #d4edda; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #28a745; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .label { font-weight: bold; color: #28a745; }
                .value { margin-left: 10px; }
                .success { color: #155724; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>✅ Appointment Approved</h2>
                    <p>Counselign - The USTP Guidance Counseling Sanctuary</p>
                </div>
                
                <div class='content'>
                    <p>Dear Student,</p>
                    
                    <p>Great news! Your appointment has been approved by your counselor. Please review the details below:</p>
                    
                    <div class='success-info'>
                        <h3>🎉 Approval Confirmation</h3>
                        <p class='success'>Your appointment has been approved and is now confirmed!</p>
                        <p>Please arrive on time for your scheduled appointment.</p>
                    </div>
                    
                    <div class='appointment-details'>
                        <h3>📋 Approved Appointment Details</h3>
                        <p><span class='label'>Date:</span><span class='value'>{$appointmentDate}</span></p>
                        <p><span class='label'>Time:</span><span class='value'>{$appointmentTime}</span></p>
                        <p><span class='label'>Consultation Type:</span><span class='value'>{$consultationType}</span></p>
                        <p><span class='label'>Purpose:</span><span class='value'>{$purpose}</span></p>
                        <p><span class='label'>Description:</span><span class='value'>{$description}</span></p>
                    </div>
                    
                    <div class='counselor-info'>
                        <h3>👨‍⚕️ Counselor Information</h3>
                        <p><span class='label'>Name:</span><span class='value'>{$counselorName}</span></p>
                        <p><span class='label'>Email:</span><span class='value'>{$counselorEmail}</span></p>
                        <p><span class='label'>Counselor ID:</span><span class='value'>{$counselorId}</span></p>
                    </div>
                    
                    <p>We look forward to seeing you at your scheduled appointment.</p>
                    
                    <p>Best regards,<br>Counselign System</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Create plain text email body for appointment approval notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return string The plain text email body
     */
    public static function createApprovalEmailTextBody(array $appointmentData, array $counselorData): string
    {
        $counselorName = $counselorData['name'] ?? 'Your Counselor';
        $counselorEmail = $counselorData['email'] ?? '';
        $counselorId = $counselorData['counselor_id'] ?? '';
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';

        return "APPOINTMENT APPROVAL NOTIFICATION\n\n" .
               "Dear Student,\n\n" .
               "Great news! Your appointment has been approved by your counselor. Please review the details below:\n\n" .
               "APPROVAL CONFIRMATION:\n" .
               "Your appointment has been approved and is now confirmed!\n" .
               "Please arrive on time for your scheduled appointment.\n\n" .
               "APPROVED APPOINTMENT DETAILS:\n" .
               "Date: {$appointmentDate}\n" .
               "Time: {$appointmentTime}\n" .
               "Consultation Type: {$consultationType}\n" .
               "Purpose: {$purpose}\n" .
               "Description: {$description}\n\n" .
               "COUNSELOR INFORMATION:\n" .
               "Name: {$counselorName}\n" .
               "Email: {$counselorEmail}\n" .
               "Counselor ID: {$counselorId}\n\n" .
               "We look forward to seeing you at your scheduled appointment.\n\n" .
               "Best regards,\n" .
               "Counselign System\n\n" .
               "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n" .
               "Please do not reply to this email.";
    }

    /**
     * Create HTML email body for appointment rejection notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return string The HTML email body
     */
    public static function createRejectionEmailBody(array $appointmentData, array $counselorData): string
    {
        $counselorName = $counselorData['name'] ?? 'Your Counselor';
        $counselorEmail = $counselorData['email'] ?? '';
        $counselorId = $counselorData['counselor_id'] ?? '';
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';
        $rejectionReason = $appointmentData['reason'] ?? 'No reason provided.';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .appointment-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #dc3545; }
                .counselor-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #007bff; }
                .rejection-info { background-color: #f8d7da; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #dc3545; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .label { font-weight: bold; color: #dc3545; }
                .value { margin-left: 10px; }
                .rejection { color: #721c24; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>❌ Appointment Rejected</h2>
                    <p>Counselign - The USTP Guidance Counseling Sanctuary</p>
                </div>
                
                <div class='content'>
                    <p>Dear Student,</p>
                    
                    <p>We regret to inform you that your appointment has been rejected by your counselor. Please review the details below:</p>
                    
                    <div class='rejection-info'>
                        <h3>⚠️ Rejection Details</h3>
                        <p><span class='label'>Rejection Reason:</span><span class='value'>{$rejectionReason}</span></p>
                        <p class='rejection'>Please consider scheduling a new appointment with different details.</p>
                    </div>
                    
                    <div class='appointment-details'>
                        <h3>📋 Rejected Appointment Details</h3>
                        <p><span class='label'>Date:</span><span class='value'>{$appointmentDate}</span></p>
                        <p><span class='label'>Time:</span><span class='value'>{$appointmentTime}</span></p>
                        <p><span class='label'>Consultation Type:</span><span class='value'>{$consultationType}</span></p>
                        <p><span class='label'>Purpose:</span><span class='value'>{$purpose}</span></p>
                        <p><span class='label'>Description:</span><span class='value'>{$description}</span></p>
                    </div>
                    
                    <div class='counselor-info'>
                        <h3>👨‍⚕️ Counselor Information</h3>
                        <p><span class='label'>Name:</span><span class='value'>{$counselorName}</span></p>
                        <p><span class='label'>Email:</span><span class='value'>{$counselorEmail}</span></p>
                        <p><span class='label'>Counselor ID:</span><span class='value'>{$counselorId}</span></p>
                    </div>
                    
                    <p>You may schedule a new appointment through the system.</p>
                    
                    <p>Best regards,<br>Counselign System</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Create plain text email body for appointment rejection notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return string The plain text email body
     */
    public static function createRejectionEmailTextBody(array $appointmentData, array $counselorData): string
    {
        $counselorName = $counselorData['name'] ?? 'Your Counselor';
        $counselorEmail = $counselorData['email'] ?? '';
        $counselorId = $counselorData['counselor_id'] ?? '';
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';
        $rejectionReason = $appointmentData['reason'] ?? 'No reason provided.';

        return "APPOINTMENT REJECTION NOTIFICATION\n\n" .
               "Dear Student,\n\n" .
               "We regret to inform you that your appointment has been rejected by your counselor. Please review the details below:\n\n" .
               "REJECTION DETAILS:\n" .
               "Rejection Reason: {$rejectionReason}\n" .
               "Please consider scheduling a new appointment with different details.\n\n" .
               "REJECTED APPOINTMENT DETAILS:\n" .
               "Date: {$appointmentDate}\n" .
               "Time: {$appointmentTime}\n" .
               "Consultation Type: {$consultationType}\n" .
               "Purpose: {$purpose}\n" .
               "Description: {$description}\n\n" .
               "COUNSELOR INFORMATION:\n" .
               "Name: {$counselorName}\n" .
               "Email: {$counselorEmail}\n" .
               "Counselor ID: {$counselorId}\n\n" .
               "You may schedule a new appointment through the system.\n\n" .
               "Best regards,\n" .
               "Counselign System\n\n" .
               "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n" .
               "Please do not reply to this email.";
    }

    /**
     * Create HTML email body for counselor cancellation notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return string The HTML email body
     */
    public static function createCounselorCancellationEmailBody(array $appointmentData, array $counselorData): string
    {
        $counselorName = $counselorData['name'] ?? 'Your Counselor';
        $counselorEmail = $counselorData['email'] ?? '';
        $counselorId = $counselorData['counselor_id'] ?? '';
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';
        $cancellationReason = $appointmentData['reason'] ?? 'No reason provided.';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #6c757d; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background-color: #f9f9f9; padding: 20px; border-radius: 0 0 8px 8px; }
                .appointment-details { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #6c757d; }
                .counselor-info { background-color: white; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #007bff; }
                .cancellation-info { background-color: #e2e3e5; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #6c757d; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .label { font-weight: bold; color: #6c757d; }
                .value { margin-left: 10px; }
                .cancellation { color: #495057; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🚫 Appointment Cancelled by Counselor</h2>
                    <p>Counselign - The USTP Guidance Counseling Sanctuary</p>
                </div>
                
                <div class='content'>
                    <p>Dear Student,</p>
                    
                    <p>We regret to inform you that your appointment has been cancelled by your counselor. Please review the details below:</p>
                    
                    <div class='cancellation-info'>
                        <h3>⚠️ Cancellation Details</h3>
                        <p><span class='label'>Cancellation Reason:</span><span class='value'>{$cancellationReason}</span></p>
                        <p class='cancellation'>You may schedule a new appointment through the system.</p>
                    </div>
                    
                    <div class='appointment-details'>
                        <h3>📋 Cancelled Appointment Details</h3>
                        <p><span class='label'>Date:</span><span class='value'>{$appointmentDate}</span></p>
                        <p><span class='label'>Time:</span><span class='value'>{$appointmentTime}</span></p>
                        <p><span class='label'>Consultation Type:</span><span class='value'>{$consultationType}</span></p>
                        <p><span class='label'>Purpose:</span><span class='value'>{$purpose}</span></p>
                        <p><span class='label'>Description:</span><span class='value'>{$description}</span></p>
                    </div>
                    
                    <div class='counselor-info'>
                        <h3>👨‍⚕️ Counselor Information</h3>
                        <p><span class='label'>Name:</span><span class='value'>{$counselorName}</span></p>
                        <p><span class='label'>Email:</span><span class='value'>{$counselorEmail}</span></p>
                        <p><span class='label'>Counselor ID:</span><span class='value'>{$counselorId}</span></p>
                    </div>
                    
                    <p>We apologize for any inconvenience caused.</p>
                    
                    <p>Best regards,<br>Counselign System</p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Create plain text email body for counselor cancellation notification
     * 
     * @param array $appointmentData The appointment data
     * @param array $counselorData The counselor details
     * @return string The plain text email body
     */
    public static function createCounselorCancellationEmailTextBody(array $appointmentData, array $counselorData): string
    {
        $counselorName = $counselorData['name'] ?? 'Your Counselor';
        $counselorEmail = $counselorData['email'] ?? '';
        $counselorId = $counselorData['counselor_id'] ?? '';
        
        $appointmentDate = date('F j, Y', strtotime($appointmentData['preferred_date']));
        $appointmentTime = $appointmentData['preferred_time'];
        $consultationType = $appointmentData['consultation_type'];
        $purpose = $appointmentData['purpose'];
        $description = $appointmentData['description'] ?? 'No additional description provided.';
        $cancellationReason = $appointmentData['reason'] ?? 'No reason provided.';

        return "APPOINTMENT CANCELLATION NOTIFICATION\n\n" .
               "Dear Student,\n\n" .
               "We regret to inform you that your appointment has been cancelled by your counselor. Please review the details below:\n\n" .
               "CANCELLATION DETAILS:\n" .
               "Cancellation Reason: {$cancellationReason}\n" .
               "You may schedule a new appointment through the system.\n\n" .
               "CANCELLED APPOINTMENT DETAILS:\n" .
               "Date: {$appointmentDate}\n" .
               "Time: {$appointmentTime}\n" .
               "Consultation Type: {$consultationType}\n" .
               "Purpose: {$purpose}\n" .
               "Description: {$description}\n\n" .
               "COUNSELOR INFORMATION:\n" .
               "Name: {$counselorName}\n" .
               "Email: {$counselorEmail}\n" .
               "Counselor ID: {$counselorId}\n\n" .
               "We apologize for any inconvenience caused.\n\n" .
               "Best regards,\n" .
               "Counselign System\n\n" .
               "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n" .
               "Please do not reply to this email.";
    }

    /**
     * Create follow-up created email body
     */
    public static function createFollowUpCreatedEmailBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = htmlspecialchars($followUpData['consultation_type']);
        $description = htmlspecialchars($followUpData['description'] ?? 'No additional description provided.');
        $reason = htmlspecialchars($followUpData['reason'] ?? 'No reason provided.');
        $counselorName = htmlspecialchars($counselorData['name']);
        $counselorEmail = htmlspecialchars($counselorData['email']);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>New Follow-up Session Created</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #17a2b8; color: white; padding: 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .logo { height: 40px; margin-bottom: 10px; }
                .content { padding: 30px; }
                .details-card { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .details-card h3 { margin-top: 0; color: #333; font-size: 18px; }
                .icon-blue { color: #17a2b8; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h2>Counselign - The USTP Guidance Counseling Sanctuary</h2>
                    <h3>New Follow-up Session Created!</h3>
                </div>
                <div class="content">
                    <p>Dear Student,</p>
                    <p>Your counselor has created a new follow-up session for you. Please review the details below:</p>
                    <div class="details-card">
                        <h3><i class="fas fa-calendar-plus icon-blue"></i> Follow-up Session Details</h3>
                        <p><strong>Date:</strong> {$followUpDate}</p>
                        <p><strong>Time:</strong> {$followUpTime}</p>
                        <p><strong>Consultation Type:</strong> {$consultationType}</p>
                        <p><strong>Description:</strong> {$description}</p>
                        <p><strong>Reason for Follow-up:</strong> {$reason}</p>
                    </div>
                    <div class="details-card">
                        <h3><i class="fas fa-user-tie icon-blue"></i> Counselor Information</h3>
                        <p><strong>Counselor:</strong> {$counselorName}</p>
                        <p><strong>Email:</strong> {$counselorEmail}</p>
                    </div>
                    <p>Please make sure to attend your follow-up session on time. If you need to reschedule or have any questions, please contact your counselor.</p>
                    <p>Best regards,</p>
                    <p>The Counselign Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Create follow-up edited email body
     */
    public static function createFollowUpEditedEmailBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = htmlspecialchars($followUpData['consultation_type']);
        $description = htmlspecialchars($followUpData['description'] ?? 'No additional description provided.');
        $reason = htmlspecialchars($followUpData['reason'] ?? 'No reason provided.');
        $counselorName = htmlspecialchars($counselorData['name']);
        $counselorEmail = htmlspecialchars($counselorData['email']);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Follow-up Session Updated</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #ffc107; color: white; padding: 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .logo { height: 40px; margin-bottom: 10px; }
                .content { padding: 30px; }
                .details-card { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .details-card h3 { margin-top: 0; color: #333; font-size: 18px; }
                .icon-yellow { color: #ffc107; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h2>Counselign - The USTP Guidance Counseling Sanctuary</h2>
                    <h3>Follow-up Session Updated!</h3>
                </div>
                <div class="content">
                    <p>Dear Student,</p>
                    <p>Your counselor has updated your follow-up session. Please review the updated details below:</p>
                    <div class="details-card">
                        <h3><i class="fas fa-edit icon-yellow"></i> Updated Follow-up Session Details</h3>
                        <p><strong>Date:</strong> {$followUpDate}</p>
                        <p><strong>Time:</strong> {$followUpTime}</p>
                        <p><strong>Consultation Type:</strong> {$consultationType}</p>
                        <p><strong>Description:</strong> {$description}</p>
                        <p><strong>Reason for Follow-up:</strong> {$reason}</p>
                    </div>
                    <div class="details-card">
                        <h3><i class="fas fa-user-tie icon-blue"></i> Counselor Information</h3>
                        <p><strong>Counselor:</strong> {$counselorName}</p>
                        <p><strong>Email:</strong> {$counselorEmail}</p>
                    </div>
                    <p>Please note the updated details for your follow-up session. If you have any questions, please contact your counselor.</p>
                    <p>Best regards,</p>
                    <p>The Counselign Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Create follow-up completed email body
     */
    public static function createFollowUpCompletedEmailBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = htmlspecialchars($followUpData['consultation_type']);
        $description = htmlspecialchars($followUpData['description'] ?? 'No additional description provided.');
        $counselorName = htmlspecialchars($counselorData['name']);
        $counselorEmail = htmlspecialchars($counselorData['email']);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Follow-up Session Completed</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .logo { height: 40px; margin-bottom: 10px; }
                .content { padding: 30px; }
                .details-card { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .details-card h3 { margin-top: 0; color: #333; font-size: 18px; }
                .icon-green { color: #28a745; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h2>Counselign - The USTP Guidance Counseling Sanctuary</h2>
                    <h3>Follow-up Session Completed!</h3>
                </div>
                <div class="content">
                    <p>Dear Student,</p>
                    <p>Your follow-up session has been marked as completed by your counselor. Here are the details:</p>
                    <div class="details-card">
                        <h3><i class="fas fa-check-circle icon-green"></i> Completed Follow-up Session</h3>
                        <p><strong>Date:</strong> {$followUpDate}</p>
                        <p><strong>Time:</strong> {$followUpTime}</p>
                        <p><strong>Consultation Type:</strong> {$consultationType}</p>
                        <p><strong>Description:</strong> {$description}</p>
                    </div>
                    <div class="details-card">
                        <h3><i class="fas fa-user-tie icon-blue"></i> Counselor Information</h3>
                        <p><strong>Counselor:</strong> {$counselorName}</p>
                        <p><strong>Email:</strong> {$counselorEmail}</p>
                    </div>
                    <p>Thank you for participating in your follow-up session. If you need further assistance, please don't hesitate to contact your counselor.</p>
                    <p>Best regards,</p>
                    <p>The Counselign Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Create follow-up cancelled email body
     */
    public static function createFollowUpCancelledEmailBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = htmlspecialchars($followUpData['consultation_type']);
        $description = htmlspecialchars($followUpData['description'] ?? 'No additional description provided.');
        $reason = htmlspecialchars($followUpData['reason'] ?? 'No reason provided.');
        $counselorName = htmlspecialchars($counselorData['name']);
        $counselorEmail = htmlspecialchars($counselorData['email']);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Follow-up Session Cancelled</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .email-container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
                .header h2 { margin: 0; font-size: 24px; }
                .logo { height: 40px; margin-bottom: 10px; }
                .content { padding: 30px; }
                .details-card { background-color: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; margin: 20px 0; }
                .details-card h3 { margin-top: 0; color: #333; font-size: 18px; }
                .icon-red { color: #dc3545; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h2>Counselign - The USTP Guidance Counseling Sanctuary</h2>
                    <h3>Follow-up Session Cancelled</h3>
                </div>
                <div class="content">
                    <p>Dear Student,</p>
                    <p>Your follow-up session has been cancelled by your counselor. Here are the details:</p>
                    <div class="details-card">
                        <h3><i class="fas fa-ban icon-red"></i> Cancelled Follow-up Session</h3>
                        <p><strong>Date:</strong> {$followUpDate}</p>
                        <p><strong>Time:</strong> {$followUpTime}</p>
                        <p><strong>Consultation Type:</strong> {$consultationType}</p>
                        <p><strong>Description:</strong> {$description}</p>
                        <p><strong>Cancellation Reason:</strong> {$reason}</p>
                    </div>
                    <div class="details-card">
                        <h3><i class="fas fa-user-tie icon-blue"></i> Counselor Information</h3>
                        <p><strong>Counselor:</strong> {$counselorName}</p>
                        <p><strong>Email:</strong> {$counselorEmail}</p>
                    </div>
                    <p>If you have any questions about this cancellation or need to schedule a new follow-up session, please contact your counselor.</p>
                    <p>Best regards,</p>
                    <p>The Counselign Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Create follow-up created email text body
     */
    public static function createFollowUpCreatedEmailTextBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = $followUpData['consultation_type'];
        $description = $followUpData['description'] ?? 'No additional description provided.';
        $reason = $followUpData['reason'] ?? 'No reason provided.';
        $counselorName = $counselorData['name'];
        $counselorEmail = $counselorData['email'];

        $textBody = "NEW FOLLOW-UP SESSION CREATED\n\n";
        $textBody .= "Dear Student,\n\n";
        $textBody .= "Your counselor has created a new follow-up session for you. Please review the details below:\n\n";
        $textBody .= "FOLLOW-UP SESSION DETAILS:\n";
        $textBody .= "Date: {$followUpDate}\n";
        $textBody .= "Time: {$followUpTime}\n";
        $textBody .= "Consultation Type: {$consultationType}\n";
        $textBody .= "Description: {$description}\n";
        $textBody .= "Reason for Follow-up: {$reason}\n\n";
        $textBody .= "COUNSELOR INFORMATION:\n";
        $textBody .= "Counselor: {$counselorName}\n";
        $textBody .= "Email: {$counselorEmail}\n\n";
        $textBody .= "Please make sure to attend your follow-up session on time. If you need to reschedule or have any questions, please contact your counselor.\n\n";
        $textBody .= "Best regards,\n";
        $textBody .= "The Counselign Team\n\n";
        $textBody .= "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n";
        $textBody .= "Please do not reply to this email.";

        return $textBody;
    }

    /**
     * Create follow-up edited email text body
     */
    public static function createFollowUpEditedEmailTextBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = $followUpData['consultation_type'];
        $description = $followUpData['description'] ?? 'No additional description provided.';
        $reason = $followUpData['reason'] ?? 'No reason provided.';
        $counselorName = $counselorData['name'];
        $counselorEmail = $counselorData['email'];

        $textBody = "FOLLOW-UP SESSION UPDATED\n\n";
        $textBody .= "Dear Student,\n\n";
        $textBody .= "Your counselor has updated your follow-up session. Please review the updated details below:\n\n";
        $textBody .= "UPDATED FOLLOW-UP SESSION DETAILS:\n";
        $textBody .= "Date: {$followUpDate}\n";
        $textBody .= "Time: {$followUpTime}\n";
        $textBody .= "Consultation Type: {$consultationType}\n";
        $textBody .= "Description: {$description}\n";
        $textBody .= "Reason for Follow-up: {$reason}\n\n";
        $textBody .= "COUNSELOR INFORMATION:\n";
        $textBody .= "Counselor: {$counselorName}\n";
        $textBody .= "Email: {$counselorEmail}\n\n";
        $textBody .= "Please note the updated details for your follow-up session. If you have any questions, please contact your counselor.\n\n";
        $textBody .= "Best regards,\n";
        $textBody .= "The Counselign Team\n\n";
        $textBody .= "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n";
        $textBody .= "Please do not reply to this email.";

        return $textBody;
    }

    /**
     * Create follow-up completed email text body
     */
    public static function createFollowUpCompletedEmailTextBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = $followUpData['consultation_type'];
        $description = $followUpData['description'] ?? 'No additional description provided.';
        $counselorName = $counselorData['name'];
        $counselorEmail = $counselorData['email'];

        $textBody = "FOLLOW-UP SESSION COMPLETED\n\n";
        $textBody .= "Dear Student,\n\n";
        $textBody .= "Your follow-up session has been marked as completed by your counselor. Here are the details:\n\n";
        $textBody .= "COMPLETED FOLLOW-UP SESSION:\n";
        $textBody .= "Date: {$followUpDate}\n";
        $textBody .= "Time: {$followUpTime}\n";
        $textBody .= "Consultation Type: {$consultationType}\n";
        $textBody .= "Description: {$description}\n\n";
        $textBody .= "COUNSELOR INFORMATION:\n";
        $textBody .= "Counselor: {$counselorName}\n";
        $textBody .= "Email: {$counselorEmail}\n\n";
        $textBody .= "Thank you for participating in your follow-up session. If you need further assistance, please don't hesitate to contact your counselor.\n\n";
        $textBody .= "Best regards,\n";
        $textBody .= "The Counselign Team\n\n";
        $textBody .= "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n";
        $textBody .= "Please do not reply to this email.";

        return $textBody;
    }

    /**
     * Create follow-up cancelled email text body
     */
    public static function createFollowUpCancelledEmailTextBody(array $followUpData, array $counselorData): string
    {
        $followUpDate = date('F j, Y', strtotime($followUpData['preferred_date']));
        $followUpTime = htmlspecialchars($followUpData['preferred_time']);
        $consultationType = $followUpData['consultation_type'];
        $description = $followUpData['description'] ?? 'No additional description provided.';
        $reason = $followUpData['reason'] ?? 'No reason provided.';
        $counselorName = $counselorData['name'];
        $counselorEmail = $counselorData['email'];

        $textBody = "FOLLOW-UP SESSION CANCELLED\n\n";
        $textBody .= "Dear Student,\n\n";
        $textBody .= "Your follow-up session has been cancelled by your counselor. Here are the details:\n\n";
        $textBody .= "CANCELLED FOLLOW-UP SESSION:\n";
        $textBody .= "Date: {$followUpDate}\n";
        $textBody .= "Time: {$followUpTime}\n";
        $textBody .= "Consultation Type: {$consultationType}\n";
        $textBody .= "Description: {$description}\n";
        $textBody .= "Cancellation Reason: {$reason}\n\n";
        $textBody .= "COUNSELOR INFORMATION:\n";
        $textBody .= "Counselor: {$counselorName}\n";
        $textBody .= "Email: {$counselorEmail}\n\n";
        $textBody .= "If you have any questions about this cancellation or need to schedule a new follow-up session, please contact your counselor.\n\n";
        $textBody .= "Best regards,\n";
        $textBody .= "The Counselign Team\n\n";
        $textBody .= "This is an automated notification from Counselign - The USTP Guidance Counseling Sanctuary.\n";
        $textBody .= "Please do not reply to this email.";

        return $textBody;
    }
}
