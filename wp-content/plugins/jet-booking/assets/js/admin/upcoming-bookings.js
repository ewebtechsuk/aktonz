(function ($, upcomingBookingsData) {

    'use strict';

    window.JetABAFUnits = new Vue({
        el: '#jet_abaf_upcoming_bookings',
        template: '#jet-abaf-upcoming-bookings',
        data: {
            bookings: upcomingBookingsData.bookings,
            bookingsLink: upcomingBookingsData.bookings_link
        },
        methods: {
            handleDelete: function (id, index) {
                if (window.confirm("Are you sure? Deleted booking can't be restored.")) {
                    const self = this;

                    wp.apiFetch({
                        method: 'delete',
                        path: upcomingBookingsData.api.delete_booking + id + '/',
                    }).then(function (response) {
                        if (!response.success) {
                            alert(response.data);
                        }

                        self.bookings.splice(index, 1);
                    }).catch(function (e) {
                        alert(e.message);
                    });
                }
            },
            getOrderLink: function (id) {
                return upcomingBookingsData.edit_link.replace(/\%id\%/, id);
            },
            getDetailsLink: function (id) {
                return this.bookingsLink + '&booking-details=' + id;
            },
        }
    });

})(jQuery, window.JetABAFUpcomingBookingsData);