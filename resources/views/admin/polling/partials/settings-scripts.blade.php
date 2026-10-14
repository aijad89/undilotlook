{{--
    The date and time inputs are two fields; the column is one. Combining them
    at submit is the only script settings-fields needs — both dates are
    `required` on the visible inputs, so the browser stops a blank one before
    any of this runs.
--}}
<script>
document.addEventListener('DOMContentLoaded', function() {

    function combineDatetime(dateId, timeId, hiddenId) {
        const d = document.getElementById(dateId)?.value;
        const t = document.getElementById(timeId)?.value || '00:00';
        const hidden = document.getElementById(hiddenId);
        if (hidden) hidden.value = d ? (d + 'T' + t) : '';
    }

    document.getElementById('pollingForm')?.addEventListener('submit', function() {
        combineDatetime('start_date_d', 'start_date_t', 'polling_start_date');
        combineDatetime('deadline_d', 'deadline_t', 'polling_deadline');
    });

});
</script>
