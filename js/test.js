$(document).ready(function() {
    var color = 'one';
    var counter = 0;
    $('.desc').hide();
    $('.hexagon').hover(
        function() {
            $(this).find('.desc').fadeIn('fast');
            counter++;
            if (counter === 0) {
                color = 'base';
            } else if (counter === 1) {
                color = 'one';
            } else if (counter === 2) {
                color = 'two';
            } else if (counter === 3) {
                color = 'three';
            } else if (counter >= 4){
                counter = 0 ;
                color = 'base';
            }
            $(this).find('.desc').addClass(color);
        },
        function() {
            $(this).find('.desc').fadeOut('fast', function() {
                $(this).removeClass(color);
            });
        });
});


/**
 * Porfolio isotope and filter
 */
let portfolionIsotope = document.querySelector('.portfolio-isotope');

if (portfolionIsotope) {

    let portfolioFilter = portfolionIsotope.getAttribute('data-portfolio-filter') ? portfolionIsotope.getAttribute('data-portfolio-filter') : '*';
    let portfolioLayout = portfolionIsotope.getAttribute('data-portfolio-layout') ? portfolionIsotope.getAttribute('data-portfolio-layout') : 'masonry';
    let portfolioSort = portfolionIsotope.getAttribute('data-portfolio-sort') ? portfolionIsotope.getAttribute('data-portfolio-sort') : 'original-order';

    window.addEventListener('load', () => {
        let portfolioIsotope = new Isotope(document.querySelector('.portfolio-container'), {
            itemSelector: '.portfolio-item',
            layoutMode: portfolioLayout,
            filter: portfolioFilter,
            sortBy: portfolioSort
        });

        let menuFilters = document.querySelectorAll('.portfolio-isotope .portfolio-flters li');
        menuFilters.forEach(function(el) {
            el.addEventListener('click', function() {
                document.querySelector('.portfolio-isotope .portfolio-flters .filter-active').classList.remove('filter-active');
                this.classList.add('filter-active');
                portfolioIsotope.arrange({
                    filter: this.getAttribute('data-filter')
                });
                if (typeof aos_init === 'function') {
                    aos_init();
                }
            }, false);
        });

    });

}
