/**
 * Admin user view AJAX tabs
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  if (!window.RoomGateAjax) return;
  window.RoomGateAjax.initSwappableTabs({
    containerSelector: '[data-ajax-container="user-view"]',
    linkSelector: '[data-ajax-link="user-view"]',
    onLoaded: function () {
      document.dispatchEvent(new Event('user-view:loaded'));
    }
  });
});
