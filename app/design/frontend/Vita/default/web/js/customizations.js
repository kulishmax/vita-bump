require(['jquery'], function(jQuery) {
    jQuery(window).load(function() {
       jQuery('.carousel-wrapper .owl-carousel').owlCarousel({
            loop:true,
            margin:10,
            nav:true,
            responsive:{
                0:{
                    items:1
                },
                600:{
                    items:1
                },
                1000:{
                    items:1
                }
            }
        });

        jQuery('.carousel-testimonials .owl-carousel').owlCarousel({
            center:true,
            loop:true,
            margin:10,
            responsive:{
                0:{
                    items:1
                },
                950:{
                    items:2
                },
                1000:{
                    items:2
                },
                1380:{
                    items:2
                },
            }
        });

    });
});
