(function($) {
  "use strict"; // Start of use strict

  // Toggle the side navigation
  $("#sidebarToggle, #sidebarToggleTop").on('click', function(e) {
    $("body").toggleClass("sidebar-toggled");
    $(".sidebar").toggleClass("toggled");
    if ($(".sidebar").hasClass("toggled")) {
      $('.sidebar .collapse').collapse('hide');
    };
    $('.nav-item .collapse').css('top','0px');
    $(window).trigger('resize');
  });

  // Close any open menu accordions when window is resized below 768px
  $(window).resize(function() {
    if ($(window).width() < 768) {
      $('.sidebar .collapse').collapse('hide');
    };
  });

  // Prevent the content wrapper from scrolling when the fixed side navigation hovered over
  $('body.fixed-nav .sidebar').on('mousewheel DOMMouseScroll wheel', function(e) {
    if ($(window).width() > 768) {
      var e0 = e.originalEvent,
        delta = e0.wheelDelta || -e0.detail;
      this.scrollTop += (delta < 0 ? 1 : -1) * 30;
      e.preventDefault();
    }
  });

  // Scroll to top button appear
  $('#content-wrapper').on('scroll', function() {
    var scrollDistance = $(this).scrollTop();
    if (scrollDistance > 100) {
      $('.scroll-to-top').fadeIn();
    } else {
      $('.scroll-to-top').fadeOut();
    }
  });

  // Smooth scrolling using jQuery easing
  $(document).on('click', 'a.scroll-to-top', function(e) {
    var $anchor = $(this);
    $('#content-wrapper').stop().animate({
      scrollTop: ($($anchor.attr('href')).offset().top)
    }, 1000, 'easeInOutExpo');
    e.preventDefault();
  });

  $(document).on('click', '.nav-link', function(e) {
    var target = $(this).attr('data-target');
    if($('#accordionSidebar').hasClass('toggled') || ($(window).width() < 768)) {
      var top = $(this).offset().top;
      $(target).css('top',top+'px');
      //e.preventDefault();
    } else {
      $(target).css('top','0px');
    }
  });

  $(document).on('click', function(e) {
    if($('#accordionSidebar').hasClass('toggled') || ($(window).width() < 768) && e.target.id!='sidebarToggle') {
      $('.nav-item .collapse').removeClass('show');
    }
  });

})(jQuery); // End of use strict
