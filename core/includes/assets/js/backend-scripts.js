/*------------------------ 
Backend related javascript
------------------------*/
const $ = jQuery;
let selectedRoomImages = customVars.selected_room_images

// console.log(selectedRoomImages);
$(window).on('load', () => {
    setExpirationCountdown();
    setRemoveImage();
    setMessageScheduleOptions();
    setRetreatsManagement();
    saveStripeConfigs();
    setRoomsDashboard();
    hideOrderItemMeta();
});

/* ORDER */
function setExpirationCountdown() {
    // Function to update countdown
    const updateCountdown = () => {
        let hoursElement = $('#expiration-countdown .hours');
        let minutesElement = $('#expiration-countdown .minutes');
        let secondsElement = $('#expiration-countdown .seconds');

        let hours = parseInt(hoursElement.text());
        let minutes = parseInt(minutesElement.text());
        let seconds = parseInt(secondsElement.text());

        // Check if all values are zero
        if (hours === 0 && minutes === 0 && seconds === 0) {
            // Countdown reached zero, you can handle this case as needed
            clearInterval(countdownInterval);
            return;
        }

        // Subtract one second
        if (seconds > 0) {
            seconds--;
        } else {
            if (minutes > 0) {
                minutes--;
                seconds = 59;
            } else {
                if (hours > 0) {
                    hours--;
                    minutes = 59;
                    seconds = 59;
                }
            }
        }

        // Update the HTML elements
        hoursElement.text(hours < 10 ? '0' + hours : hours);
        minutesElement.text(minutes < 10 ? '0' + minutes : minutes);
        secondsElement.text(seconds < 10 ? '0' + seconds : seconds);
    };

    // Call the updateCountdown function every second
    const countdownInterval = setInterval(updateCountdown, 1000);
}

function hideOrderItemMeta() {
    $('#woocommerce-order-items table.display_meta tr:has(th:contains("room_id"))').hide();
}

/* ROOMS CPT */
function uploadGalleryImages() {
    let mediaUploader;
    // Extend the wp.media object
    mediaUploader = wp.media.frames.file_frame = wp.media({
        //button_text set by wp_localize_script()
        title: 'Choose Images',
        library: {
            type: ['image']
        },
        multiple: 'add' //allowing for multiple image selection

    });
    mediaUploader.on('open', function () {

        // if there's a file ID
        if (selectedRoomImages.length) {
            // select the file ID to show it as selected in the Media Library Modal.
            selectedRoomImages.forEach(function (fileID) {
                mediaUploader.uploader.uploader.param('post_id', parseInt(fileID));
                var selection = mediaUploader.state().get('selection');
                selection.add(wp.media.attachment(fileID));
            });
        }
    });
    mediaUploader.on('select', function () {

        var attachments = mediaUploader.state().get('selection').map(

            function (attachment) {

                attachment.toJSON();
                return attachment;

            });
        //loop through the array and do things with each attachment

        $('#gallery-preview').empty();
        selectedRoomImages = [];
        let i;

        for (i = 0; i < attachments.length; ++i) {
            const imageID = attachments[i].id,
                imageUrl = attachments[i].attributes.url;
            //sample function 1: add image preview
            $('#gallery-preview').append(
                `<li class="gallery-item image-item-preview">
                        <button type="button" class="remove-image-button">&#215;</button> 
                        <img src="${imageUrl}" >
                        <input id="image-input-${imageID}" type="hidden" name="gallery[${imageID}]"  value="${imageID}" /> 
                    </li>`
            );
            selectedRoomImages.push(imageID);
            setRemoveImage();
            //sample function 2: add hidden input for each image
            // $('#gallery-preview').after(`<input id="image-input${attachments[i].id}" type="hidden" name="gallery[]"  value="${attachments[i].id}" /> `);

        }

    });

    mediaUploader.open();
}

function setRemoveImage() {
    $('.remove-image-button').click(function (e) {
        e.preventDefault();
        const siblingItems = $(this).closest('.gallery-item').siblings();
        selectedRoomImages = [];
        siblingItems.each(function () {
            const imageID = $(this).find('input').val();
            selectedRoomImages.push(imageID);
        });
        $(this).parent().remove();
    });
}

/* MESSAGES CPT */

function uploadFileForMail() {

    let mediaUploader;

    // If the uploader object has already been created, reopen the dialog
    if (mediaUploader) {
        mediaUploader.open();
        return;
    }

    // Create the media uploader
    mediaUploader = wp.media.frames.file_frame = wp.media({
        title: 'Choose File',
        button: {
            text: 'Choose File'
        },
        multiple: false  // Set to true if you want to allow multiple file uploads
    });

    mediaUploader.on('open', function () {
        // if there's a file ID
        if ($('input#attachment-id').val()) {
            // select the file ID to show it as selected in the Media Library Modal.
            mediaUploader.uploader.uploader.param('post_id', parseInt($('input#attachment-id').val()));
            const selection = mediaUploader.state().get('selection');
            selection.add(wp.media.attachment($('input#attachment-id').val()));
        }
    });
    // When a file is selected, grab the ID and use it to get the URL
    mediaUploader.on('select', function () {
        const attachment = mediaUploader.state().get('selection').first().toJSON();

        // Use attachment.id to get the attachment ID
        const attachment_id = attachment.id;

        // Get the URL using the attachment ID
        const attachment_url = wp.media.attachment(attachment_id).get('url');
        $('button#upload-file-button').text('Change File');
        $('input#attachment-id').val(attachment_id);
        $('input#attachment-url').val(attachment_url);
        $('p#attachment-name').text(attachment.filename);
        // You can now use attachment_url as needed
        // alert('File selected: ' + attachment_url);
    });

    // Open the uploader dialog
    mediaUploader.open();
}

function setMessageScheduleOptions() {
    const $optionsContainer = $('#retreat-message-options'),
        $scheduleOptions = $optionsContainer.find('.timing-wrapper');

    const setScheduleOption = ($option) => {
        const $activateCheckbox = $option.find('input[role="activate"]'),
            $daysInput = $option.find('input[role="days"]'),
            $timeInput = $option.find('input[role="time"]');

        $daysInput.prop('required', $activateCheckbox.is(':checked'));
        $timeInput.prop('required', $activateCheckbox.is(':checked'));
    }
    $scheduleOptions.each(function () {
        const $activateCheckbox = $scheduleOptions.find('input[role="activate"]');
        setScheduleOption($(this));

        $activateCheckbox.on('change', () => {
            setScheduleOption($(this));
        });
    });
}

/* MANAGE RETREATS */
function setRetreatsManagement() {
    const $retreatsManage = $('#retreats-management'),
        $retreats = $retreatsManage.find('.single-retreat'),
        $departureDates = $retreatsManage.find('.departure-date'),
        $rooms = $retreatsManage.find('ul.rooms-list > li'),
        $guests = $retreatsManage.find('ul.guests-list>li');

    $retreats.each(function (idx) {
        const $heading = $(this).find('.retreat-heading'),
            $content = $(this).find('.retreat-content');

        const currentTopOffset = $heading.offset().top,
            offsetAdjust = ($heading.height() + 15) * idx

        $heading.offset({ 'top': currentTopOffset + offsetAdjust });
        $heading.on('click', (idx, heading) => {
            $(this).attr('data-selected', 'true');
            $(this).siblings().attr('data-selected', 'false');
        });
    });

    $departureDates.each(function () {
        const $heading = $(this).find('.heading'),
            $content = $(this).find('.content');

        $heading.on('click', (e) => {
            const isSelected = $(this).attr('data-selected') == 'true';
            $(this).attr('data-selected', isSelected ? 'false' : 'true');
            $content.slideToggle(300);
            $(this).siblings().find('.content').slideUp(300);
            $(this).siblings().attr('data-selected', 'false');
            $content.offset({ left: $(this).closest('.retreat-content').offset().left + 15 });
        });
    });

    $rooms.on('click', function () {
        let isSelected = $(this).attr('data-selected') == 'true';
        const $siblings = $(this).siblings();
        $(this).find('.room-content').slideToggle(300);
        $(this).attr('data-selected', isSelected ? 'false' : 'true');
        isSelected = !isSelected;

        $siblings.each(function () {
            $(this).attr('data-selected', 'false')
            $(this).find('.room-content').slideUp(300);
            setRoomItemStyle($(this), false)
        });

        if (isSelected) {
            const $content = $(this).find('.room-content');

            $content.offset({ left: $(this).closest('.retreat-content').offset().left + 30 });
            $content.css({
                width: `calc(${$(this).closest('.content').width() / $(this).width() * 100}% - 13px)`,
                // left:$(this).closest('.retreat-content').offset().left + 'px',
            });
        }

        setRoomItemStyle($(this), isSelected);
    });

    $rooms.on('mouseover', function () {
        setRoomItemStyle($(this), true)
    });

    $rooms.on('mouseleave', function () {
        if ($(this).attr('data-selected') == 'true') return;
        setRoomItemStyle($(this), false)
    });
    setGuestsList($guests);
    setManualRoomBooking($rooms);
}

function setRoomItemStyle($roomItem, isSelected) {
    const $roomHeading = $roomItem.find('.room-heading');
    const color = $roomItem.data('color');

    $roomHeading.css({
        'background-color': isSelected ? color : '',
        'color': isSelected ? '#fff' : '',
        'text-shadow': isSelected ? '1px 1px 1px #000' : 'none'
    });
}

function setManualRoomBooking($rooms) {
    $rooms.each(function () {
        const $manualRoomBookingBtn = $(this).find('.manual-booking-btn'),
            $manualRoomBookingForm = $(this).find('form.book-room-form');

        $manualRoomBookingBtn.on('click', function (e) {
            e.stopPropagation();
            $manualRoomBookingForm.attr('data-active', 'true');
        });
        $manualRoomBookingForm.on('click', function (e) {
            e.stopPropagation();
        });
    });
}

function setGuestsList($guests) {
    const guestsOrderEmailAjax = (action, order_id) => {
        $.ajax({
            url: customVars.ajaxUrl,
            type: 'POST',
            data: {
                action: 'send_order_emails',
                order_id,
                email_action: action
            },
            success: function (response) {
                console.log(response);
            }
        });
    }
    $guests.each(function () {
        const $expandingItemsHeading = $(this).find('.expanding-item-heading'),
            $orderEmailsBtns = $(this).find('.order-emails button');

        $expandingItemsHeading.on('click', function (e) {
            e.stopPropagation();

            $(this).closest('.expanding-wrapper').toggleClass('expanded');
            $(this).next().slideToggle(300);
        });

        $orderEmailsBtns.on('click', function (e) {
            e.stopPropagation();
            const action = $(this).data('action'),
                order = $(this).data('order');
            guestsOrderEmailAjax(action, order);
            // console.log(action, order);
        })
    });
}

/* ROOMS DASHBOARD */
function setRoomsDashboard() {
    const $roomsDashboard = $('#rooms-stats'),
        $rooms = $roomsDashboard.find('ul.rooms-list> li'),
        $retreats = $rooms.find('.retreats-revenue-wrapper'),
        $dates = $retreats.find('.date-wrapper'),
        $navButtons = $retreats.find('.arrows-container button');

    $rooms.on('click', function () {
        let isSelected = $(this).attr('data-selected') == 'true';
        const $siblings = $(this).siblings();
        $(this).find('.room-content').slideToggle(300);
        $(this).attr('data-selected', isSelected ? 'false' : 'true');
        isSelected = !isSelected;

        $siblings.each(function () {
            $(this).attr('data-selected', 'false')
            $(this).find('.room-content').slideUp(300);
            setRoomItemStyle($(this), false)
        });

        setRoomItemStyle($(this), isSelected);
    });

    $rooms.on('mouseover', function () {
        setRoomItemStyle($(this), true)
    });

    $rooms.on('mouseleave', function () {
        if ($(this).attr('data-selected') == 'true') return;
        setRoomItemStyle($(this), false)
    });

    $rooms.each(function () {
        const $payments = $(this).find('.single-payment-wrapper');
        $payments.on('click', function (e) {
            e.stopPropagation();
            const $content = $(this).find('.content');
            $(this).toggleClass('expanded');
            $content.slideToggle(300);
        });

    })

    $navButtons.on('click', function (e) {
        e.stopPropagation();
        const isNext = $(this).hasClass('next-arrow');
        const $retreatsContent = $(this).closest('.arrows-container').siblings('.retreats-content'),
            $retreatItems = $retreatsContent.find('.retreat-wrapper'),
            $currentRetreat = $retreatsContent.find('.retreat-wrapper[data-current="true"]'),
            $nextRetreat = isNext ? $currentRetreat.next() : $currentRetreat.prev();


        const currentOffsetX = $retreatsContent.css('transform') == 'none' ? 0 : +$retreatsContent.css('transform').split(',')[4],// check value of tranform(translateX), if null set to 0
            translateX = isNext ? currentOffsetX - ($retreatItems.outerWidth() + 20) : currentOffsetX + ($retreatItems.outerWidth() + 20);

        $retreatsContent.css('transform', `translateX(${translateX}px)`);
        $currentRetreat.attr('data-current', 'false');
        $nextRetreat.attr('data-current', 'true');
    });

    $dates.on('click', function (e) {
        e.stopPropagation();
        const $content = $(this).find('.content');
        $(this).toggleClass('active');
        $content.slideToggle(300);
    });
}

/* CONFIGS */
function saveStripeConfigs() {
    const $stripeConfigsForm = $('#retreat-options-form');
    $stripeConfigsForm.on('submit', function (e) {

        e.preventDefault();
        // const data = $(this).serialize();
        $.ajax({
            url: customVars.ajaxUrl,
            type: 'POST',
            data: {
                action: 'update_booking_options',
                second_participant_price: $('#second_participant_price').val(),
                redirect_after_atc_id: $('#redirect_after_atc').val(),
                deposit_per_cent: $('#deposit_per_cent').val(),
                deposit_info_page_id: $('#deposit_info_page').val(),
                days_before_deposit_disabled: $('#days_before_deposit_disabled').val(),
                days_after_departure_to_archive_date: $('#days_after_departure_to_archive_date').val(),
                stripe_test_publishable_key: $('#stripe_test_publishable_key').val(),
                stripe_test_secret_key: $('#stripe_test_secret_key').val(),
                stripe_live_publishable_key: $('#stripe_live_publishable_key').val(),
                stripe_live_secret_key: $('#stripe_live_secret_key').val(),
                is_live_mode: $('#switch-stripe-live-mode').is(':checked') ? 1 : '',
            },
            success: function (response) {
                console.log(response);
            }
        });
    });
}