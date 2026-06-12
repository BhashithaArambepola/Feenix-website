(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 500); // Change 1 to 3000 (3 seconds in milliseconds)
    };

    spinner();
    
    
    // Initiate the wowjs
    new WOW().init();

    // AOS animations (CSS is loaded on several pages; without init, content stays hidden)
    (function initAosAnimations() {
        if (!document.querySelector('[data-aos]')) {
            return;
        }

        function startAos() {
            if (typeof AOS === 'undefined') {
                return;
            }

            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true,
                mirror: false
            });
        }

        if (typeof AOS !== 'undefined') {
            startAos();
            return;
        }

        var script = document.createElement('script');
        script.src = 'https://unpkg.com/aos@2.3.1/dist/aos.js';
        script.async = true;
        script.onload = startAos;
        document.body.appendChild(script);
    })();


    // Fixed Navbar
    $(window).scroll(function () {
        if ($(window).width() < 992) {
            if ($(this).scrollTop() > 45) {
                $('.fixed-top').addClass('bg-dark shadow');
            } else {
                $('.fixed-top').removeClass('bg-dark shadow');
            }
        } else {
            if ($(this).scrollTop() > 45) {
                $('.fixed-top').addClass('bg-dark shadow').css('top', -45);
                $('.fixed-top').removeClass('bg-transparent ').css('top', 0);
            } else {
                $('.fixed-top').removeClass('bg-dark shadow').css('top', 0);
                $('.fixed-top').addClass('bg-transparent ').css('top', 0);
            }
        }
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });

    // Project carousel
    $(".project-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        loop: true,
        center: true,
        dots: false,
        nav: true,
        navText : [
            '<i class="bi bi-chevron-left"></i>',
            '<i class="bi bi-chevron-right"></i>'
        ],
        responsive: {
            0:{
                items:2
            },
            576:{
                items:2
            },
            768:{
                items:3
            },
            992:{
                items:4
            },
            1200:{
                items:5
            }
        }
    });

    // WhatsApp Button
    function createWhatsAppButton() {
        const whatsappButton = document.createElement('a');
        whatsappButton.href = 'https://wa.me/94711509595';
        whatsappButton.className = 'whatsapp-button';
        whatsappButton.target = '_blank';
        whatsappButton.innerHTML = '<i class="bi bi-whatsapp"></i>';
        document.body.appendChild(whatsappButton);
    }

    // Initialize WhatsApp button
    createWhatsAppButton();

})(jQuery);