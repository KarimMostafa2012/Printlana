/* =================================== SCRIPT SEPARATOR-1 Dokan dashboard menu =================================== */
// Add active class for current page
jQuery(document).ready(function($) {
    if (window.location.href.indexOf('edit-account') > -1) {
        $('.dokan-dashboard-menu li.profile').addClass('active');
    }
});

/* =================================== SCRIPT SEPARATOR-2 Dokan dashboard menu style =================================== */
// Change css by screen size
document.addEventListener("DOMContentLoaded", function () {
  // function to set style
  function setStyle(selector, property, value) {
    const element = document.querySelector(selector);
    if (element) {
      element.style.setProperty(property, value, "important");
    }
  }

  const SCREEN_WIDTH = window.innerWidth;

  if (SCREEN_WIDTH <= 450) {
    setStyle(
      ".dokan-dashboard .dokan-dash-sidebar #dokan-navigation ul.dokan-dashboard-menu",
      "padding-top",
      "0px"
    );
  }

  if (SCREEN_WIDTH <= 1240) {
    const orderSelectors = [
      ".dokan-orders-content .dokan-orders-area .dokan-order-filter-serach .dokan-left",
      ".dokan-orders-content .dokan-orders-area .dokan-order-filter-serach .dokan-right"
    ];

    orderSelectors.forEach(selector => {
      setStyle(selector, "width", "100%");
    });
  }
});
