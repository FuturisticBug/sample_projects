
	$(document).ready(function() {
				
        //bootstrap Tooltip
		$('[data-toggle="tooltip"]').tooltip();
		
		// onOff btn checked
		$('.bullet .onOff input:checkbox').change(function(){
		    if($(this).is(":checked")) {
		        $(this).parents('.bullet').addClass('checked');
		    } else {
		        $(this).parents('.bullet').removeClass('checked');
		    }
		});
        
         $('.testimonialTextSlider').slick({
          slidesToShow: 1,
          slidesToScroll: 1,
          arrows: false,
          fade: true,
          asNavFor: '.testimonialThumb'
        });
        $('.testimonialThumb').slick({
          slidesToShow: 4,
          slidesToScroll: 1,
          asNavFor: '.testimonialTextSlider',
//          dots: true,
          centerMode: true,
          arrows: false,
          focusOnSelect: true
        });
		
	});
	
	//header shrink
	$(function(){
		var shrinkHeader = 2;
		$(window).scroll(function() {
			var scroll = getCurrentScroll();
			if ( scroll >= shrinkHeader ) {
				$('.shrinkHeader').addClass('shrinked');
			} else {
				$('.shrinkHeader').removeClass('shrinked');
			}
		});
		function getCurrentScroll() {
		    return window.pageYOffset || document.documentElement.scrollTop;
	    }
	});
		
	 //windowHeight
	function windowHeight() {
		var wh = $(window).height();
		$('.winHeight').css({height:wh})
	}
	
	
	// same height multiple block
	function sameHeight(group) {
	    tallest = 0;
	    group.each(function() {
	        thisHeight = $(this).outerHeight();
	        if(thisHeight > tallest) {
	            tallest = thisHeight;
	        }
	    });
	    //group.outerHeight(tallest);
	    group.css({minHeight: tallest});
	}
	
	// Footer Fixed
	function footerarea_css() {
		var window_height_for_footer = parseInt($(window).height());
		var document_height_for_footer = parseInt($('html body').outerHeight(true));
		if(document_height_for_footer < window_height_for_footer) {
			$('.footer').css('position', 'fixed').css('display', 'block').css('bottom', '0').css('left', '0').css('right', '0');
		} else {
			$('.footer').css('position', 'relative').css('display', 'block');
		}
	}
	// custom function init
	$(window).resize(function () {
		footerarea_css();
		windowHeight();
		sameHeight($(".sameHeight"));
		sameHeight($(".post-blog"));
	});
	$(window).load(function() {
		footerarea_css();
		windowHeight();
		sameHeight($(".sameHeight"));
		sameHeight($(".post-blog"));
	});