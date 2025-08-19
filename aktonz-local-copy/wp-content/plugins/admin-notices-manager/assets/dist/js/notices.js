"use strict";

jQuery(function () {
  var ignore_selector = '.hidden, .hide-if-js, .update-message, [aria-hidden="true"], .anm-display-notice';

  if (anm_i18n.settings['css_selector'].length > 0) {
    ignore_selector += ', ' + anm_i18n.settings['css_selector'];
  }

  jQuery('#wpbody-content .wrap').find('div.updated, div.error, div.notice, #message').not(ignore_selector).css({
    'display': 'none'
  });
});

(function ($, window) {
  var AdminNoticesManager = {
    container: null,
    counter_link: null,
    migration_delay: 100,
    migration_interval: null,
    migration_start: 0,
    migration_limit: 5000,
    popup_delay: 50,
    popup_interval: null,
    popup_start: 0,
    popup_limit: 1000,
    removal_interval: null,
    system_messages: [],
    init: function init() {
      var _this3 = this;

      var _this = this;

      var category_wrappers = '<div id="anm-system-notices"></div><div id="anm-error-notices"></div><div id="anm-warning-notices"></div><div id="anm-success-notices"></div><div id="anm-information-notices"></div><div id="anm-misc-notices"></div>'; // Attach correct wrapper type

      if ('popup' == anm_i18n.settings.popup_style) {
        $('body').append('<div id="anm-container" style="display: none;">' + category_wrappers + '</div>');
        this.container = $('#anm-container');
      } else {
        var top_padding = 34; // WP admin bar

        if ($('.woocommerce-layout__header').length > 0) {
          // WooCommerce header
          top_padding += $('.woocommerce-layout__header').height();
        }

        $('body').append('<div id="anm-container-slide-in" style="background-color: ' + anm_i18n.settings.slide_in_background_colour + '; padding-top: ' + top_padding + 'px;"><div id="anm-slide-in-content">' + category_wrappers + '</div></div>');
        this.container = $('#anm-slide-in-content');
      }

      this.counter_link = $('#wp-admin-bar-anm_notification_count');
      this.initTriggers();
      this.migration_start = new Date().getTime();
      this.migration_interval = setInterval(function () {
        _this3.transferNotices();
      }, this.migration_delay);
      var timesRun = 0;
      var interval = setInterval(function () {
        timesRun += 1;

        if (timesRun === 3) {
          _this.CheckAndStoreNotices();
        }

        if (timesRun === 4) {
          clearInterval(interval);
        } //do whatever here..

      }, 150);
      var smCount = anm_i18n.system_messages.length;

      for (var i = 0; i < smCount; i++) {
        var systemMessage = anm_i18n.system_messages[i];
        this.system_messages.push(systemMessage.replace(/%[sdf]/g, ''));
      }
    },
    getCurrentCounterValue: function getCurrentCounterValue() {
      var counter_elm = $('.anm-notification-counter span.count');

      if (0 == counter_elm.length) {
        return 0;
      }

      return parseInt(counter_elm.html(), 10);
    },
    getNoticeType: function getNoticeType(noticeElm) {
      var jqNotice = $(noticeElm);

      if (jqNotice.hasClass('notice-system')) {
        return 'system';
      }

      if (jqNotice.hasClass('notice-error')) {
        return 'error';
      }

      if (jqNotice.hasClass('notice-info') || jqNotice.hasClass('notice-information')) {
        return 'information';
      }

      if (jqNotice.hasClass('notice-warning')) {
        return 'warning';
      }

      if (jqNotice.hasClass('notice-success') || jqNotice.hasClass('updated')) {
        return 'success';
      } // Return a default so its handled by ANM.


      return 'misc';
    },
    checkMigrationInterval: function checkMigrationInterval() {
      //	clear the interval after given time or when there are no notices left to move
      var now = new Date().getTime();
      var time_diff = now - this.migration_start;

      if (time_diff > this.migration_limit) {
        //	stop interval
        clearInterval(this.migration_interval);
        this.migration_interval = null;
        this.CheckAndStoreNotices(); // Some notices might be left if they are exempted.

        var wrapper = $('.anm-notices-wrapper');

        if (wrapper.children(this.getIgnoreSelector()).length > 0) {
          wrapper.children().not(this.getIgnoreSelector()).remove();
          wrapper.show();
        }
      }
    },
    getIgnoreSelector: function getIgnoreSelector() {
      var ignore_selector = '.hidden, .hide-if-js, .update-message, [aria-hidden="true"], .anm-display-notice';

      if (anm_i18n.settings['css_selector'].length > 0) {
        ignore_selector += ', ' + anm_i18n.settings['css_selector'];
      }

      return ignore_selector;
    },
    getIgnoreParentSelector: function getIgnoreParentSelector() {
      var ignore_selector = '#loco-content';
      return ignore_selector;
    },
    transferNotices: function transferNotices() {
      var _this4 = this;

      var notices = $('#wpwrap ').find('div.updated, div.error, div.notice, #message').not(this.getIgnoreSelector()); //	filter out the system notices

      notices.each(function (index, notice) {
        var smCount = _this4.system_messages.length;

        for (var i = 0; i < smCount; i++) {
          var systemMessage = _this4.system_messages[i];

          if (notice.innerHTML.indexOf(systemMessage) > 0) {
            $(notice).addClass('notice-system');
          }
        } // Check if this notice resides in a known selector we should ignore.


        if ($(notice).parent(_this4.getIgnoreParentSelector()).length || $(notice).parent().parent(_this4.getIgnoreParentSelector()).length) {
          notices.splice(index, 1);
        }
      });
      var notifications_count = 0;
      var _container = this.container;
      notices.each(function (index, notice) {
        var noticeType = _this4.getNoticeType(notice);

        var actionTypeKey = 'system' === noticeType ? 'wordpress_system_admin_notices' : noticeType + '_level_notices';
        var actionType = anm_i18n.settings[actionTypeKey];

        if ('hide' === actionType) {
          $(notice).remove();
        } else if ('popup-only' === actionType || noticeType == 'misc') {
          jQuery(notice).css({
            'display': 'block'
          }); //	detach notices from the original place and increase the counter

          var typeWrapper = $(_container).find('#anm-' + noticeType + '-notices');

          if (!jQuery(notice).find('p').length) {
            jQuery(notice).wrapInner('<p></p>');
          }

          $(notice).detach().addClass('notice').appendTo(typeWrapper);
          notifications_count++;
        } else {
          jQuery(notice).css({
            'display': 'block'
          }).addClass('leave-in-place');
        }
      }); //	number of notifications

      var count_to_show = notifications_count; //	increase counter if already exists

      if (0 < $('.anm-notification-counter').length) {
        count_to_show += this.getCurrentCounterValue();
      }

      this.updateCounterBubble(count_to_show);
      this.checkMigrationInterval();
    },
    updateCounterBubble: function updateCounterBubble(count) {
      count = this.container.find('.notice').length;

      if (0 < $('.anm-notification-counter').length) {
        var counter_elm = $('.anm-notification-counter span.count');
        counter_elm.html(count);
      } else {
        var title = anm_i18n.title;
        this.counter_link.find('a').html(title);
        var bubble_html = '<div class="anm-notification-counter' + ' wp-core-ui wp-ui-notification">' + '<span aria-hidden="true" class="count">' + count + '</span>' + '<span class="screen-reader-text">' + count + ' ' + title + '</span>' + '</div>';
        this.counter_link.attr('data-popup-title', title);
        this.counter_link.find('a').append(bubble_html);
        this.counter_link.addClass('has-data');
      }
    },
    adjustModalHeight: function adjustModalHeight() {
      $('#TB_ajaxContent').css({
        width: '100%',
        height: $('#TB_window').height() - $('#TB_title').outerHeight() - 22 + 'px',
        padding: '2px 0px 20px 0px'
      }); //	clear the interval after given time

      if (this.popup_interval) {
        var now = new Date().getTime();
        var time_diff = now - this.popup_start;

        if (time_diff > this.popup_limit) {
          clearInterval(this.popup_interval);
          this.popup_interval = null;
        }
      }
    },
    checkNoticeRemoval: function checkNoticeRemoval() {
      if (!$('#TB_ajaxContent').height()) {
        if (this.removal_interval) {
          clearInterval(this.removal_interval);
        }

        return;
      } //	if the popup is open, check if any notices have been removed and update the count accordingly


      var notices_present_count = $('#TB_ajaxContent').find('.notice').not(':hidden').length;
      var displayed_count = this.getCurrentCounterValue();

      if (displayed_count !== notices_present_count) {
        this.updateCounterBubble(notices_present_count);
      }
    },
    CheckAndStoreNotices: function CheckAndStoreNotices() {
      // Get the notices we currently hold.
      var notices = jQuery(this.container).find('.notice');
      var noticeArr = [];

      var _this = this;

      notices.each(function (index, notice) {
        jQuery(notice).find('.anm-notice-timestamp').remove();
        var noticeHTML = notice.outerHTML;
        noticeArr[index] = noticeHTML;
      });
      jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        url: anm_i18n.ajaxurl,
        data: {
          action: 'anm_log_notices',
          _wpnonce: anm_i18n.nonce,
          notices: noticeArr
        },
        complete: function complete(data) {
          _this.appendTimeDate(notices, data.responseJSON.data);

          $('.anm-notification-counter').addClass('display');
        }
      });
    },
    appendTimeDate: function appendTimeDate(notices, data) {
      var _this = this;

      notices.each(function (index, notice) {
        if (data[index] == 'do-not-display') {
          jQuery(notice).remove();

          var currentCount = _this.getCurrentCounterValue();

          var newCount = currentCount - 1;

          _this.updateCounterBubble(newCount);
        } else if (data[index][0] == 'display-notice') {
          jQuery(notice).addClass('anm-display-notice');
          jQuery(notice).insertAfter('.anm-notices-wrapper');
          var timeAndDate = '<div class="anm-notice-hide"><a href="#" data-hide-notice="' + data[index][1] + '">' + anm_i18n.hide_notice + '</a></div>';

          if (!jQuery(notice).find('.anm-notice-hide').length) {
            jQuery(timeAndDate).appendTo(notice);
          }

          var newCount = currentCount - 1;

          _this.updateCounterBubble(newCount);
        } else {
          var timeAndDate = '<div class="anm-notice-timestamp"><span class="anm-time">' + anm_i18n.date_time_preamble + data[index][1] + '</span><a href="#" data-hide-notice-forever="' + data[index][0] + '">' + anm_i18n.hide_notice_text + '</a> <a href="#" data-display-notice="' + data[index][0] + '">' + anm_i18n.display_notice + '</a></div>';

          if (!jQuery(notice).find('.anm-notice-timestamp').length) {
            jQuery(timeAndDate).appendTo(notice);
          }
        }
      });
    },
    initTriggers: function initTriggers() {
      var _this = this;

      this.counter_link.click(function () {
        if (_this.popup_interval) {
          clearInterval(_this.popup_interval);
          _this.popup_interval = null;
        }

        if (0 == _this.getCurrentCounterValue()) {
          return false;
        }

        if ('popup' == anm_i18n.settings.popup_style) {
          tb_show(_this.counter_link.attr('data-popup-title'), '#TB_inline?inlineId=anm-container');
        } else {
          $('#anm-container-slide-in').addClass('show');
        } //	start height adjustment using interval (there is no callback nor event to hook into)


        _this.popup_start = new Date().getTime();
        _this.popup_interval = setInterval(function () {
          _this.adjustModalHeight.call(_this);
        }, _this.popup_delay);

        if (_this.removal_interval) {
          clearInterval(_this.removal_interval);
        }

        _this.removal_interval = setInterval(function () {
          _this.checkNoticeRemoval.call(_this);
        }, _this.popup_delay);
        return false;
      });
      $(window).resize(function () {
        if ('popup' == anm_i18n.settings.popup_style) {
          //	adjust thick box modal height on window resize
          _this.adjustModalHeight.call(_this);
        }
      });

      if ('slide-in' == anm_i18n.settings.popup_style) {
        $(document).on('click', 'body *', function (e) {
          if ($(e.target).is('#anm-container-slide-in a')) {
            return;
          } else if (!$(e.target).is('#anm-container-slide-in')) {
            $('#anm-container-slide-in').removeClass('show');
          }
        });
      }

      jQuery(document).on('click', '[data-hide-notice-forever]', function (e) {
        e.preventDefault();
        var itemHash = jQuery(this).attr('data-hide-notice-forever');
        var itemToHide = jQuery(this).closest('.notice');
        var counter = $('.anm-notification-counter span.count').text();
        var _this2 = _this;
        jQuery.ajax({
          type: 'POST',
          dataType: 'json',
          url: anm_i18n.ajaxurl,
          data: {
            action: 'anm_hide_notice_forever',
            _wpnonce: anm_i18n.nonce,
            notice_hash: itemHash
          },
          complete: function complete(data) {
            itemToHide.slideUp(300).delay(300).remove();
            var newCount = counter - 1;

            _this2.updateCounterBubble(newCount);
          }
        });
      });
      jQuery(document).on('click', '[data-display-notice]', function (e) {
        e.preventDefault();
        var itemHash = jQuery(this).attr('data-display-notice');
        var itemToHide = jQuery(this).closest('.notice');
        var counter = $('.anm-notification-counter span.count').text();
        var _this2 = _this;
        jQuery.ajax({
          type: 'POST',
          dataType: 'json',
          url: anm_i18n.ajaxurl,
          data: {
            action: 'anm_display_notice',
            _wpnonce: anm_i18n.nonce,
            notice_hash: itemHash
          },
          complete: function complete(data) {
            jQuery(itemToHide).find('.anm-notice-timestamp').remove();
            jQuery(itemToHide).addClass('anm-display-notice');
            var timeAndDate = '<div class="anm-notice-hide"><a href="#" data-hide-notice="' + itemHash + '">' + anm_i18n.hide_notice + '</a></div>';

            if (!jQuery(itemToHide).find('.anm-notice-hide').length) {
              jQuery(timeAndDate).appendTo(itemToHide);
            }

            jQuery(itemToHide).insertAfter('.anm-notices-wrapper');
            itemToHide.slideDown();
            var newCount = counter - 1;

            _this2.updateCounterBubble(newCount);
          }
        });
      });
      jQuery(document).on('click', '[data-hide-notice]', function (e) {
        e.preventDefault();
        var itemHash = jQuery(this).attr('data-hide-notice');
        var itemToHide = jQuery(this).closest('.notice');
        var counter = $('.anm-notification-counter span.count').text();
        var _this2 = _this;
        jQuery.ajax({
          type: 'POST',
          dataType: 'json',
          url: anm_i18n.ajaxurl,
          data: {
            action: 'anm_hide_notice',
            _wpnonce: anm_i18n.nonce,
            notice_hash: itemHash
          },
          complete: function complete(data) {
            location.reload();
          }
        });
      });
    }
  };
  AdminNoticesManager.init();
})(jQuery, window);