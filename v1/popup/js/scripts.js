
	jQuery(function ($) {
		$(".cvv-link").click(function () {
			$(this).parent().parent().siblings(".cvv-image").slideToggle();
		});
	});
	
	
	// anchor
	jQuery(function ($) {
		$('a[href^="#"]').click(function () {
			var target = $(this).attr("href");
			if ((target) && target != '#') {
				$("html, body").animate({ scrollTop: $(target).offset().top }, 1000);
			}
			return false;
		});
	});
  
  // end anchor


	//date 2
	jQuery(function($){
		var date = new Date();
		var shipdate = new Date(date.getFullYear(), date.getMonth(), date.getDate() + Number($("#lf-shipdate").attr("addDays")));
		
		var options = {
			year: "numeric",
			month: "long",
			day: "numeric"
		};
	
		$("#lf-shipdate").html(shipdate.toLocaleDateString($("#lf-shipdate").attr("data-locale"), options));
	});
  
  //date end

  // clock
	jQuery(function ($) {
		var spd = 100;
		var spdVal = 10;
		var cntDown = 10 * 60 * spdVal;
		setInterval(function () {
		var mn, sc, ms;
		cntDown--;
		if (cntDown < 0) {
			return false;
		}
		mn = Math.floor(cntDown / spdVal / 60);
		mn = mn < 10 ? "0" + mn : mn;
		sc = Math.floor((cntDown / spdVal) % 60);
		sc = sc < 10 ? "0" + sc : sc;
		ms = Math.floor(cntDown % spdVal);
		ms = ms < 10 ? "0" + ms : ms;
		var result = mn + ":" + sc;
		if (document.getElementById("time")) {
			document.getElementById("time").innerHTML = result;
		}
		}, spd);
	});
  // end clock




	// clock 2 minutes
	jQuery(function ($) {
		var spd = 100;
		var spdVal = 10;
		var cntDown = 2 * 60 * spdVal;
		setInterval(function () {
		var mn, sc, ms;
		cntDown--;
		if (cntDown < 0) {
		  	return false;
			}
		mn = Math.floor(cntDown / spdVal / 60);
		mn = mn < 10 ? "0" + mn : mn;
		sc = Math.floor((cntDown / spdVal) % 60);
		sc = sc < 10 ? "0" + sc : sc;
		ms = Math.floor(cntDown % spdVal);
		ms = ms < 10 ? "0" + ms : ms;
		var result = mn + ":" + sc;
		if (document.getElementById("timer")) {
		  	document.getElementById("timer").innerHTML = result;
			}
		}, spd);
	});
	
   
	//date
	jQuery(function ($) {
		var mydate = new Date();
		var montharray = [
			"January",
			"February",
			"March",
			"April",
			"May",
			"June",
			"July",
			"August",
			"September",
			"October",
			"November",
			"December",
		];
		$(".date-container").text(" " + montharray[mydate.getMonth()] + " " + mydate.getDate() + ", " + mydate.getFullYear());
	});
  //date end



	jQuery(function($) {
		var mydate = new Date();
		var month = mydate.getMonth() + 1;
		var dayarray = [
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday'];
		$('.date-container2').text(dayarray[mydate.getDay()] + ', ' + month + '/' + mydate.getDate() + '/' + mydate.getFullYear());
	});
  
  


//---------------------Timer Countdown------------------//
function Countdown(elem, seconds) {
  var that = {};

  that.elem = elem;
  that.seconds = seconds;
  that.totalTime = seconds * 100;
  that.usedTime = 0;
  that.startTime = +new Date();
  that.timer = null;

  that.count = function () {
    that.usedTime = Math.floor((+new Date() - that.startTime) / 10);

    var tt = that.totalTime - that.usedTime;
    if (tt <= 0) {
      that.elem.html('00:00.00');
      clearInterval(that.timer);
    } else {
      var mi = Math.floor(tt / (60 * 100));
      var ss = Math.floor((tt - mi * 60 * 100) / 100);
      var ms = tt - Math.floor(tt / 100) * 100;

      that.elem.html(that.fillZero(mi) + ":" + that.fillZero(ss) + "." + that.fillZero(ms));
    }
  };

  that.init = function () {
    if (that.timer) {
      clearInterval(that.timer);
      that.elem.html('00:00.00');
      that.totalTime = seconds * 100;
      that.usedTime = 0;
      that.startTime = +new Date();
      that.timer = null;
    }
  };

  that.start = function () {
    if (!that.timer) {
      that.timer = setInterval(that.count, 10);
    }
  };

  that.fillZero = function (num) {
    return num < 10 ? '0' + num : num;
  };

  return that;
}


jQuery(function ($) {
  var date = new Date();
  $(".container-year").text(date.getFullYear());
})