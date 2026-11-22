// Small browser helpers for the Garage Rental pages: a booking calendar and an image viewer
(function () { // Keep every name inside this file so nothing clashes with other scripts
    'use strict'; // Stricter JavaScript that stops common mistakes

    const DAY_MS = 86400000; // Milliseconds in one day
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']; // Short month names
    const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']; // Full month names
    const WEEKDAYS = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']; // Column titles of the calendar

    function parseDate(text) { // Turns 'YYYY-MM-DD' into a date at midnight UTC, or null when it isn't a full date
        if (!/^\d{4}-\d{2}-\d{2}$/.test(text || '')) { // Not in the YYYY-MM-DD shape ...
            return null; // ... no date
        } // End of the shape check
        const parts = text.split('-').map(Number); // [year, month, day] as numbers
        return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2])); // JavaScript months start at 0
    } // End of parseDate()

    function isoDate(date) { // Turns a date into 'YYYY-MM-DD'
        return date.toISOString().slice(0, 10); // The first 10 characters of the ISO text
    } // End of isoDate()

    function addDays(date, days) { // A date plus a number of days
        return new Date(date.getTime() + days * DAY_MS); // UTC dates have no daylight saving jumps
    } // End of addDays()

    function daysBetween(from, to) { // Whole days from one date to another
        return Math.round((to.getTime() - from.getTime()) / DAY_MS); // Rounded to a whole number
    } // End of daysBetween()

    function daysInMonth(date) { // 28, 29, 30 or 31: the length of the month the date is in
        return new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth() + 1, 0)).getUTCDate(); // Day 0 of next month = last day of this month
    } // End of daysInMonth()

    function longDate(date) { // A date as '22 Nov 2026', the same format the server uses
        return date.getUTCDate() + ' ' + MONTHS[date.getUTCMonth()] + ' ' + date.getUTCFullYear(); // Day, short month, year
    } // End of longDate()

    class RangeCalendar { // One month at a time with a start and an end date marked, like a booking site
        constructor(element, options) { // element: where to draw; options: min, onPick, startLabel, endLabel
            this.element = element; // Box that holds the calendar
            this.min = options.min; // Earliest day that can be clicked
            this.onPick = options.onPick || null; // Function called with the clicked day (null = days can't be clicked)
            this.startLabel = options.startLabel || 'Start'; // Legend text for the first marked day
            this.endLabel = options.endLabel || 'End'; // Legend text for the last marked day
            this.betweenLabel = options.betweenLabel || 'Booked days'; // Legend text for the days in between
            this.start = null; // First marked day
            this.end = null; // Last marked day
            this.month = new Date(Date.UTC(this.min.getUTCFullYear(), this.min.getUTCMonth(), 1)); // Left month shown: the month of the first allowed day
            this.element.addEventListener('click', (event) => this.handleClick(event)); // One click handler for every button inside
            this.draw(); // Draw it for the first time
        } // End of constructor()

        setRange(start, end) { // Marks a new start and end, then draws again
            this.start = start; // New first day
            this.end = end; // New last day (may be null)
            this.draw(); // Show the change
        } // End of setRange()

        showMonthOf(date) { // Moves the calendar to the month of this date
            this.month = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), 1)); // First day of that month
            this.draw(); // Show the change
        } // End of showMonthOf()

        handleClick(event) { // Reacts to the arrows and to clicked days
            const button = event.target.closest('button'); // The button that was clicked, if any
            if (!button) { // Clicked outside a button ...
                return; // ... nothing to do
            } // End of the check
            event.preventDefault(); // Buttons inside a form must not send the form
            if (button.dataset.move) { // An arrow: move one month back or forward
                this.month = new Date(Date.UTC(this.month.getUTCFullYear(), this.month.getUTCMonth() + Number(button.dataset.move), 1)); // New left month
                this.draw(); // Show the change
            } else if (button.dataset.date && this.onPick) { // A day that may be picked
                this.onPick(parseDate(button.dataset.date)); // Tell the page which day was picked
            } // End of the button types
        } // End of handleClick()

        dayClass(day) { // CSS classes for one day: past, start, end or in between
            const classes = ['rc-day']; // Every day gets the base class
            const iso = isoDate(day); // Same day as text, easy to compare
            if (day < this.min) { // Before the first allowed day ...
                classes.push('rc-off'); // ... greyed out
            } // End of the past check
            if (this.start && iso === isoDate(this.start)) { // The first marked day
                classes.push('rc-start'); // Dark blue
            } // End of the start check
            if (this.end && iso === isoDate(this.end)) { // The last marked day
                classes.push('rc-end'); // Dark blue
            } // End of the end check
            if (this.start && this.end && day > this.start && day < this.end) { // A day between them
                classes.push('rc-between'); // Light blue
            } // End of the between check
            return classes.join(' '); // e.g. 'rc-day rc-between'
        } // End of dayClass()

        monthHtml(first) { // HTML for one month table
            let html = '<div class="rc-month">'; // Start of the month
            html += '<div class="rc-grid">' + WEEKDAYS.map((name) => '<span class="rc-head">' + name + '</span>').join(''); // Weekday titles
            for (let blank = 0; blank < first.getUTCDay(); blank++) { // Empty cells before day 1 (Sunday first)
                html += '<span></span>'; // One empty cell
            } // End of the empty cells
            for (let number = 1; number <= daysInMonth(first); number++) { // Every day of the month
                const day = new Date(Date.UTC(first.getUTCFullYear(), first.getUTCMonth(), number)); // This day as a date
                const canPick = this.onPick && day >= this.min; // Clickable only when picking is on and the day is allowed
                html += canPick // Clickable days are buttons, the rest plain text
                    ? '<button type="button" class="' + this.dayClass(day) + '" data-date="' + isoDate(day) + '">' + number + '</button>' // Button with its date
                    : '<span class="' + this.dayClass(day) + '">' + number + '</span>'; // Plain day
            } // End of the days
            return html + '</div></div>'; // Close the grid and the month
        } // End of monthHtml()

        draw() { // Rebuilds the whole calendar
            const canGoBack = this.month > new Date(Date.UTC(this.min.getUTCFullYear(), this.min.getUTCMonth(), 1)); // No going back past the first allowed month
            this.element.innerHTML = '<div class="rc-nav">' // Bar with the arrows and the month name
                + '<button type="button" class="btn btn-sm btn-outline-secondary" data-move="-1"' + (canGoBack ? '' : ' disabled') + ' aria-label="Previous month">&lsaquo;</button>' // Back arrow
                + '<span class="rc-title">' + MONTH_NAMES[this.month.getUTCMonth()] + ' ' + this.month.getUTCFullYear() + '</span>' // Month name between the arrows
                + '<button type="button" class="btn btn-sm btn-outline-secondary" data-move="1" aria-label="Next month">&rsaquo;</button></div>' // Forward arrow
                + this.monthHtml(this.month) // The month
                + '<div class="rc-legend"><span class="rc-key rc-start"></span> ' + this.startLabel + ' <span class="rc-key rc-between"></span> ' + this.betweenLabel + ' <span class="rc-key rc-end"></span> ' + this.endLabel + '</div>'; // Colour legend
        } // End of draw()
    } // End of RangeCalendar

    let viewerTurn = 0; // How far the image in the viewer is turned, in degrees

    function fitViewerImage() { // Sizes the image so it fits the viewer even when turned sideways
        const frame = document.getElementById('viewerFrame'); // Box around the image
        const image = document.getElementById('viewerImage'); // The image itself
        if (!frame || !image) { // The page has no viewer ...
            return; // ... nothing to size
        } // End of the check
        const sideways = Math.abs(viewerTurn % 180) === 90; // A quarter turn swaps width and height
        image.style.transform = 'rotate(' + viewerTurn + 'deg)'; // Turn the image
        image.style.maxWidth = (sideways ? frame.clientHeight : frame.clientWidth) + 'px'; // Width limit after turning
        image.style.maxHeight = (sideways ? frame.clientWidth : frame.clientHeight) + 'px'; // Height limit after turning
    } // End of fitViewerImage()

    document.addEventListener('click', (event) => { // One click handler for the whole page
        const opener = event.target.closest('[data-viewer-src]'); // A link or image that opens the viewer
        if (opener) { // It was one of those
            event.preventDefault(); // Don't follow the link
            viewerTurn = 0; // Start upright
            document.getElementById('viewerTitle').textContent = opener.dataset.viewerTitle || 'Image'; // Title on top
            document.getElementById('viewerCaption').textContent = opener.dataset.viewerCaption || ''; // Text under the image, e.g. the typed NIK
            document.getElementById('viewerOpen').href = opener.dataset.viewerSrc; // "Open full size" link
            const image = document.getElementById('viewerImage'); // The image element
            image.onload = fitViewerImage; // Size it once it has loaded
            image.src = opener.dataset.viewerSrc; // Load the picture
            bootstrap.Modal.getOrCreateInstance(document.getElementById('imageViewer')).show(); // Open the pop-up
            return; // Done
        } // End of the opener case
        const turn = event.target.closest('[data-viewer-turn]'); // A rotate button
        if (turn) { // It was a rotate button
            viewerTurn = Number(turn.dataset.viewerTurn) === 0 ? 0 : viewerTurn + Number(turn.dataset.viewerTurn); // 0 = back to upright, otherwise add -90 or 90
            fitViewerImage(); // Show the new angle
        } // End of the rotate case
    }); // End of the click handler

    document.addEventListener('shown.bs.modal', fitViewerImage); // Size the image again once the pop-up is fully open
    window.addEventListener('resize', fitViewerImage); // And when the window changes size

    document.addEventListener('DOMContentLoaded', () => { // When the page has loaded
        document.querySelectorAll('[data-open-on-load]').forEach((modal) => bootstrap.Modal.getOrCreateInstance(modal).show()); // Reopen a pop-up that came back with an error
    }); // End of the load handler

    window.GarageRental = { parseDate, isoDate, addDays, daysBetween, daysInMonth, longDate, RangeCalendar }; // Share these with the page scripts
})(); // Run the code above right away
