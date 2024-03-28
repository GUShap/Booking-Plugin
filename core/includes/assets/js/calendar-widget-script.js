const $ = jQuery;
const isMobile = customVars.is_mobile ? true : false,
    isSingle = customVars.is_single ? true : false,
    ajaxUrl = customVars.ajax_url;
$(window).on('load', () => {
    setMonthsSlider();
    setRetreatInfoPopup();
});

function setMonthsSlider() {
    const $retreatsContentContainer = $('.retreats-calendar-container'),
        $retreatsContentWrapper = $('.retreats-calendar-container .retreats-calendar-content'),
        $allMonths = $('.retreats-calendar-container .month-wrapper'),
        $lastMonth = $('.month-wrapper.last'),
        $firstMonth = $('.month-wrapper.first'),
        $arrowRight = $('.arrows-container .arrow-right-container button'),
        $arrowLeft = $('.arrows-container .arrow-left-container button'),
        $allBtns = $('.arrows-container  .calendar-arrow-button');
    let inTransition = false;

    const getTranslateX = (element) => {
        return element.css('transform') != 'none'
            ? element.css('transform').split(",")[4].trim()
            : 0;
    }
    const isElementInView = (element, leftOffset = 0, rightOffset = $retreatsContentContainer.width()) => {
        const rect = element.getBoundingClientRect();
        return rect.x >= leftOffset && rect.x < rightOffset;
    }
    const isFirstMonthInView = () => {
        return isSingle
            ? isElementInView($firstMonth[0], $retreatsContentContainer.offset().left, $retreatsContentContainer.offset().left + $retreatsContentContainer.width())
            : isElementInView($firstMonth[0]);
    }
    const isLastMonthInView = () => {
        return isSingle
            ? isElementInView($lastMonth[0], $retreatsContentContainer.offset().left, $retreatsContentContainer.offset().left + $retreatsContentContainer.width())
            : isElementInView($lastMonth[0]);
    }
    const setArrowsVisibility = () => {
        const minMonthQuantity = isSingle ? 1 : 2;
        if ($allMonths.length <= minMonthQuantity) {
            $arrowLeft.hide();
            $arrowRight.hide();
            return;
        }
        isFirstMonthInView()
            ? $arrowLeft.fadeOut(400)
            : $arrowLeft.fadeIn(400);

        isLastMonthInView()
            ? $arrowRight.fadeOut(400)
            : $arrowRight.fadeIn(400);

    }

    $arrowRight.on('click', function () {
        if (inTransition) return;
        const distance = isSingle
            ? '100%'
            : '50% - 10px';
        const translateXValue = getTranslateX($retreatsContentWrapper);
        const newTranslateX = `calc(${translateXValue}px - ${distance})`;

        if (!isLastMonthInView()) {
            $retreatsContentWrapper.css('transform', 'translateX(' + newTranslateX + ')');
        }
    });
    $arrowLeft.on('click', function () {
        if (inTransition) return;
        const distance = isSingle
            ? '100%'
            : '50% + 10px';
        const translateXValue = getTranslateX($retreatsContentWrapper);
        const newTranslateX = `calc(${translateXValue}px + ${distance})`;

        if (!isFirstMonthInView()) {
            $retreatsContentWrapper.css('transform', 'translateX(' + newTranslateX + ')');
        }
    });
    $allBtns.on('click', function () {
        inTransition = true;
        setTimeout(() => {
            setArrowsVisibility();
            inTransition = false
        }, 340);
    });
    setArrowsVisibility();
}

function setRetreatInfoPopup() {
    const $tripDates = $('.retreats-calendar-content .day-wrapper[data-trip="true"]');
    $tripDates.each(function (){
        const isDeparture = $(this).data('departure');
        if(!isDeparture){            
            const retreatId = $(this).prev('.day-wrapper').data('retreat-id');
            $(this).attr('data-retreat-id', retreatId);
            $(this).data('retreat-id', retreatId);
        }
    });

    $tripDates.on('click', function () {
        const $retreatsInfo = $('.retreat-info-wrapper');
        const $clonedRetreatInfo = $retreatsInfo.filter('.clone');
        const tripId = $(this).data('retreat-id');
        const tripDepartureDate = $(this).data('departure')
            ? $(this).data('date')
            : $(this).prevAll('.day-wrapper[data-departure="true"]').first().data('date')

        if ($clonedRetreatInfo.data('id') === tripId && tripDepartureDate == $clonedRetreatInfo.data('departure')) return;

        const $monthWrapper = $(this).closest('.month-wrapper');
        const $selectedRetreats = $retreatsInfo.filter(function () { return $(this).data('id') === tripId })
        const $selectedRetreatClone = $selectedRetreats.first().clone();
        const $retreatInfoLink = $selectedRetreatClone.find('a.retreat-link');
        const $lastTripDate = $(this).hasClass('last')
            ? $(this)
            : $(this).nextAll('.last').first();
        const $infoTargetLocation = $lastTripDate.nextAll('.day-wrapper[data-day="sunday"]').first();
        const setRetreatInfoRender = () => {
            const currentUrl = $retreatInfoLink.prop('href');
            const newUrl = addQueryParams(currentUrl, { 'departure_date': tripDepartureDate });
            
            $infoTargetLocation.length
            ? $infoTargetLocation.before($selectedRetreatClone)
            : $monthWrapper.find('.dates-wrapper').append($selectedRetreatClone);

            $selectedRetreatClone.addClass('clone').attr({
                'data-selected': true,
                'data-departure': tripDepartureDate
            });
            $retreatInfoLink.prop('href', newUrl);
            $selectedRetreatClone.slideDown(300);
        }

        if ($clonedRetreatInfo.length) {
            $clonedRetreatInfo.slideUp({
                duration: 300,
                complete: () => {
                    $clonedRetreatInfo.remove();
                    setRetreatInfoRender();
                }
            });
        } else setRetreatInfoRender();
    });
}

function formatDateToYYYYMMDD(dateString) {
    const date = new Date(dateString);

    // Check if the date is valid
    if (isNaN(date.getTime())) {
        return 'Invalid date';
    }

    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');

    return year + '-' + month + '-' + day;
}


function addQueryParams(url, params) {
    const queryString = $.param(params);
    return url + (url.indexOf('?') === -1 ? '?' : '&') + queryString;
}