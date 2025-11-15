/**
 * Time Formatting Utility for Calendar Modals
 * Converts 24-hour format to 12-hour format with AM/PM indicators
 */

/**
 * Format a single time string for display
 * Handles both 12-hour format (with AM/PM) and 24-hour format time strings
 * @param {string} time - Time string in HH:MM format (e.g., "14:30") or 12-hour format (e.g., "2:30 PM")
 * @returns {string} - Formatted time with AM/PM (e.g., "2:30 PM")
 */
function formatTimeForDisplay(time) {
    if (!time) return '';
    
    // Check if already in 12-hour format (contains AM/PM)
    if (time.includes('AM') || time.includes('PM')) {
        return time;
    }
    
    // Convert from 24-hour format to 12-hour format
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}

/**
 * Format time slots for display in calendar modals
 * Handles various time slot formats and converts to 12-hour format
 * @param {Array|string} timeSlots - Array of time slots or single time string
 * @returns {string} - Formatted time slots for display
 */
function formatTimeSlotsForDisplay(timeSlots) {
    if (!timeSlots || (Array.isArray(timeSlots) && timeSlots.length === 0)) {
        return 'All Day';
    }

    // Handle single time string
    if (typeof timeSlots === 'string') {
        timeSlots = [timeSlots];
    }

    const formattedSlots = timeSlots.map(slot => {
        // Handle different time slot formats
        if (slot.includes('-')) {
            // Handle time range format (e.g., "09:00-17:00", "7:30-10 AM", "1:00 PM-4:00 PM")
            const parts = slot.split('-');
            if (parts.length === 2) {
                const startTime = parts[0].trim();
                const endTime = parts[1].trim();
                
                // Check if both times already have AM/PM (12-hour format from database)
                if ((startTime.includes('AM') || startTime.includes('PM')) && 
                    (endTime.includes('AM') || endTime.includes('PM'))) {
                    // Format: "1:00 PM-4:00 PM" or "9:00 AM-11:30 AM" - already in 12-hour format, format with space
                    return `${formatTimeForDisplay(startTime)} - ${formatTimeForDisplay(endTime)}`;
                } else if (endTime.includes('AM') || endTime.includes('PM')) {
                    // Format: "7:30-10 AM" - convert start to 12-hour format
                    const startFormatted = formatTimeForDisplay(startTime);
                    const endFormatted = formatTimeForDisplay(endTime);
                    return `${startFormatted} - ${endFormatted}`;
                } else {
                    // Format: "09:00-17:00" - both times need formatting to 12-hour
                    const startFormatted = formatTimeForDisplay(startTime);
                    const endFormatted = formatTimeForDisplay(endTime);
                    return `${startFormatted} - ${endFormatted}`;
                }
            }
        } else if (slot.includes(',')) {
            // Handle comma-separated times (e.g., "09:00,10:00,11:00")
            const times = slot.split(',').map(t => formatTimeForDisplay(t.trim()));
            return times.join(', ');
        } else {
            // Handle single time format (e.g., "09:00")
            return formatTimeForDisplay(slot);
        }
        
        return slot; // Fallback for unrecognized formats
    });

    // Join multiple time slots with bullet points
    return formattedSlots.join(' • ');
}

/**
 * Format time slots for badge display in calendar modals
 * Handles both 12-hour format (with AM/PM) and 24-hour format time slots
 * @param {Array|string} timeSlots - Array of time slots or single time string
 * @returns {Array} - Array of formatted time strings for individual badges
 */
function formatTimeSlotsForBadges(timeSlots) {
    if (!timeSlots || (Array.isArray(timeSlots) && timeSlots.length === 0)) {
        return ['All Day'];
    }

    // Handle single time string
    if (typeof timeSlots === 'string') {
        timeSlots = [timeSlots];
    }

    return timeSlots.map(slot => {
        // Handle different time slot formats
        if (slot.includes('-')) {
            // Handle time range format (e.g., "9:00 AM-11:30 AM" or "09:00-17:00")
            const parts = slot.split('-');
            if (parts.length === 2) {
                const startTime = parts[0].trim();
                const endTime = parts[1].trim();
                
                // Check if both times already have AM/PM (12-hour format from database)
                if ((startTime.includes('AM') || startTime.includes('PM')) && 
                    (endTime.includes('AM') || endTime.includes('PM'))) {
                    // Format: "9:00 AM-11:30 AM" - already in 12-hour format, return as-is
                    return `${startTime} - ${endTime}`;
                } else if (endTime.includes('AM') || endTime.includes('PM')) {
                    // Format: "7:30-10 AM" - convert start to 12-hour format
                    const startFormatted = formatTimeForDisplay(startTime);
                    const endFormatted = formatTimeForDisplay(endTime.replace(/AM|PM/g, '').trim());
                    return `${startFormatted} - ${endFormatted}`;
                } else {
                    // Format: "09:00-17:00" - both times need formatting to 12-hour
                    const startFormatted = formatTimeForDisplay(startTime);
                    const endFormatted = formatTimeForDisplay(endTime);
                    return `${startFormatted} - ${endFormatted}`;
                }
            }
        } else if (slot.includes(',')) {
            // Handle comma-separated times (e.g., "9:00 AM,10:00 AM,11:00 AM" or "09:00,10:00,11:00")
            const times = slot.split(',').map(t => {
                const trimmed = t.trim();
                // Check if already in 12-hour format
                if (trimmed.includes('AM') || trimmed.includes('PM')) {
                    return trimmed;
                } else {
                    return formatTimeForDisplay(trimmed);
                }
            });
            return times;
        } else {
            // Handle single time format (e.g., "9:00 AM" or "09:00")
            // Check if already in 12-hour format
            if (slot.includes('AM') || slot.includes('PM')) {
                return slot;
            } else {
                return formatTimeForDisplay(slot);
            }
        }
        
        return slot; // Fallback for unrecognized formats
    });
}
