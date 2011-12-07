(function($) {

	BJLL.timeout              = parseInt(BJLL.timeout);
	BJLL.offset               = parseInt(BJLL.offset);
	BJLL.speed                = parseInt(BJLL.speed);
	BJLL.ignoreHiddenImages   = parseInt(BJLL.ignoreHiddenImages);
	
	if (!BJLL.effect.length) BJLL.effect = null;

	$('img.lazy').jail(BJLL);
})(jQuery);