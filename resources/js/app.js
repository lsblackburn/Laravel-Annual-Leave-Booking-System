import AirDatepicker from 'air-datepicker';
import 'air-datepicker/air-datepicker.css';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

new AirDatepicker('#start_date', {
    selectedDates: new Date(),
    autoClose: true,
    dateFormat: 'dd-MM-yyyy',
    minDate: new Date(),
    locale: {
        days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
        daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
        daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
        months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
        monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        today: "Today",
        clear: "Clear",
        firstDay: 0
    },
});

new AirDatepicker('#end_date', {
    selectedDates: new Date(),
    autoClose: true,
    dateFormat: 'dd-MM-yyyy',
    minDate: new Date(),
    locale: {
        days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
        daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
        daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
        months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
        monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        today: "Today",
        clear: "Clear",
        firstDay: 0
    },
});

document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new Calendar(calendarEl, {
        plugins: [ dayGridPlugin ]
    });

    calendar.render();
});