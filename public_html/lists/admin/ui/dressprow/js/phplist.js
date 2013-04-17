
var menuArrowsrc = 'ui/dressprow/images/menuarrow.png';
var menuArrowActivesrc = 'ui/dressprow/images/menuarrow_active.png';

/* JS function to handle media queries */
window.matchMedia = window.matchMedia || (function(doc, undefined){
  
  var bool,
      docElem = doc.documentElement,
      refNode = docElem.firstElementChild || docElem.firstChild,
      // fakeBody required for <FF4 when executed in <head>
      fakeBody = doc.createElement('body'),
      div = doc.createElement('div');
  
  div.id = 'mq-test-1';
  div.style.cssText = "position:absolute;top:-100em";
  fakeBody.appendChild(div);
  
  return function(q){
    
    div.innerHTML = '&shy;<style media="'+q+'"> #mq-test-1 { width: 42px; }</style>';
    
    docElem.insertBefore(fakeBody, refNode);
    bool = div.offsetWidth == 42;
    docElem.removeChild(fakeBody);
    
    return { matches: bool, media: q };
  };
  
})(document);


/* JS to execute on loading document */
$(document).ready(function() {
	// adding add and even classes to table in dbcheck page
	$(".dbcheck tr.row:even").addClass("even");
	$(".dbcheck tr.row:odd").addClass("odd");
	// make the content collapsible
	$('.dbcheck table div.header').each(function(index) {
		$(this).click(function () {
		$(this).next("div.content").toggle("slow");
		});
	});
	
    // open/close div global help    
        $("#globalhelp").click(function(){        
    	    if(matchMedia('only screen and (max-width: 767px)').matches){ $("#menuTop").hide(); }
     	    $("#globalhelp .content").toggle();
	});
	
/* sliding menu for mobile screen */
	 $(window).bind("load resize", function(){
	    if(matchMedia('only screen and (max-width: 767px)').matches){
		    $("span#menu-button").show();
		    $("#menuTop").hide();
		    $("span#menu-button").toggle(function() {
            	$("#menuTop").show();
            	$("#globalhelp .content").hide();
			},function(){
			    $("#menuTop").hide();
			});
	  }
	  else{
		  $("span#menu-button").hide();
		  $("#menuTop").show();
	  }
	});
	
/* sub menus on mobile */
	 $(window).bind("load resize", function(){
    var org=[];	
    $("#menuTop>ul>li>a").each(function(index) { 
		org.push($(this).attr("href"));
		});
	  if(matchMedia('only screen and (max-width: 767px)').matches){
		$("#menuTop>ul>li>a").each(function(index) { 
		if($(this).parent('li').children('ul').length!=0)
		$(this).attr("href","javascript:void(0);").addClass("collaps");
		else
		$(this).addClass("nocollaps");
		});
		$("#menuTop>ul>li>a.collaps").each(function(index) {
		$(this).toggle(function() {
			$(this).parent().children("ul").addClass("visible");
			
			}, function() {
			$(this).parent().children("ul").removeClass("visible"); 
			});
			});
	  }
	  else{
		  $("#menuTop>ul>li>a").each(function(index) { $(this).attr("href",org[index]).addClass("collaps");});
	  }
	});

    // dropdown menu 1
    $('#webblertabs').each(function(){
        $(this).find('ul li').hide();
        $(this).find('ul li.current').show();
    });
    $('#webblertabs').hover(function(){
        $(this).find('ul li').slideDown();
    },
    function(){
        $(this).find('ul li').hide();
        $(this).find('ul li.current').show();
    });
    
    // dropdown menu 2
    $('.dropButton').hover(function(){
        $(this).find('.submenu').slideDown();
    },
    function(){
        $(this).find('.submenu').hide();
    });
});