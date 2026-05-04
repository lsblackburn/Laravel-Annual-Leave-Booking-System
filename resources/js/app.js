import AirDatepicker from 'air-datepicker';
import 'air-datepicker/air-datepicker.css';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const leaveDatepickerLocale = {
    days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
    daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
    daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"],
    months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
    monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    today: "Today",
    clear: "Clear",
    firstDay: 0
};

function leaveDateFromInputValue(value) {
    const parts = value.match(/^(\d{2})-(\d{2})-(\d{4})$/);

    if (!parts) {
        return new Date();
    }

    return new Date(Number(parts[3]), Number(parts[2]) - 1, Number(parts[1]));
}

function initialiseLeaveDatepicker(selector) {
    const input = document.querySelector(selector);

    if (!input) {
        return;
    }

    new AirDatepicker(input, {
        selectedDates: [leaveDateFromInputValue(input.value)],
        autoClose: true,
        dateFormat: 'dd-MM-yyyy',
        minDate: new Date(),
        locale: leaveDatepickerLocale,
    });
}

initialiseLeaveDatepicker('#start_date');
initialiseLeaveDatepicker('#end_date');

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('dashboard-calendar');

    if (!calendarEl) return;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin],

        initialView: 'dayGridWeek',
        height: 'auto',
        headerToolbar: false,
        firstDay: 1,

        events: '/leave-requests/calendar-events',

        eventDisplay: 'block',

        editable: false,
        selectable: false,
        navLinks: false,
    });

    calendar.render();
});

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    if (!calendarEl) return;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin],
        height: 'auto',
        firstDay: 1,

        events: '/leave-requests/calendar-events',

        eventDisplay: 'block',

    });

    calendar.render();
});
