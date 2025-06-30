jQuery(document).ready(function ($) {
    // Replace 'excluded_week_days' with your actual multiselect field ID
    var $excludedWeekDays = $('#excluded_week_days'),
        $groupFields = $('[id^="cmb2-metabox-yourprefix_"][id$="_group"]'); // Assuming your group field IDs have a common prefix and end with '_group'

    // Function to handle field state based on multiselect field value
    function handleFieldState() {
        var excludedValues = $excludedWeekDays.val();
        
        // Loop through each group field and enable/disable based on selected values
        $groupFields.each(function () {
            var $groupField = $(this),
                dayKey = $groupField.attr('id').split('_')[2]; // Extract day key from group field ID

            if (excludedValues && excludedValues.includes(dayKey)) {
                $groupField.find('.cmb-row').addClass('disabled');
                $groupField.find('input, select, textarea').prop('disabled', true);
            } else {
                $groupField.find('.cmb-row').removeClass('disabled');
                $groupField.find('input, select, textarea').prop('disabled', false);
            }
        });
    }

    // Initial state check on page load
    handleFieldState();

    // Bind change event to multiselect field
    $excludedWeekDays.on('change', handleFieldState);
});