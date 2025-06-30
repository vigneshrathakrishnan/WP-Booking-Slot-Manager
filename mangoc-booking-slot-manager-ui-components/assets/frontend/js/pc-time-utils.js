export function isOneHourAhead(timeStr,dateStr){
    // Parse the input time string
    const [time, modifier] = timeStr.split(' ') ;
    let [hours, minutes] = time.split(":").map(Number);

    // Convert time from 12 hrs format to 24 hrs
    if ( modifier.toLowerCase() === 'pm' && hours !== 12 ) {
        hours += 12;
    } else if ( modifier.toLowerString === 'am' && hours === 12 ) {
        hours = 0;
    }

    // Ensure hours and minutes are two digits
    hours = String(hours).padStart(2, '0');
    minutes = String(minutes).padStart(2, '0');

    if ( !isCurrentDate(hours+":"+minutes+":"+"00",dateStr) ) return true;

    const givenTime = new Date();
    givenTime.setHours(hours, minutes, 0, 0);

    // Get the current date and time
    const now = new Date();
    const oneHourAhead = new Date(now.getTime() + 60 * 60 * 1000);

    // Compare the given time with one hour ahead
    return givenTime > oneHourAhead;
}

// function isCurrentDate(dateStr){

//     try {
//         if ( typeof dateStr === 'undefined' ){
//             return false;
//         }
//         let inputDate = new Date(dateStr);
//         let currentDate = new Date();

//         // Normalize both dates to the same format (YYYY-MM-DD)
//         let inputDateString = inputDate.toISOString().split('T')[0];
//         let currentDateString = currentDate.toISOString().split('T')[0];

//         return inputDateString === currentDateString;
//     } catch (error){
//         console.error(error);
//         console.error("date value: " + dateStr );
//     }
// }

function isCurrentDate(timeStr,dateStr) {
    try {
        if (typeof dateStr === 'undefined' || typeof timeStr === 'undefined') {
            return false;
        }
        
        // Combine date and time strings into a single ISO 8601 string
        let inputDateTimeStr = dateStr + 'T' + timeStr;

        let inputDate = new Date(inputDateTimeStr);
        let currentDate = new Date();

        // Get local date components
        let inputDateString = inputDate.getFullYear() + '-' + 
                              String(inputDate.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(inputDate.getDate()).padStart(2, '0');
                              
        let currentDateString = currentDate.getFullYear() + '-' + 
                                String(currentDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(currentDate.getDate()).padStart(2, '0');

        // console.log(inputDateString);
        // console.log(currentDateString);


        return inputDateString === currentDateString ;
    } catch (error) {
        console.error(error);
        console.error("date value: " + dateStr + ", time value: " + timeStr);
        return false;
    }
}