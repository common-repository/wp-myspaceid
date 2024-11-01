/* jQuery for hide/show bottom bar */
msJQuery(document).ready(function($) {
	/* Toggle to show/hide bottom bar */
	$("#msid-arrowtoggle").toggle(
		function(){
			$(this).removeClass("msid-rightarrows").addClass("msid-leftarrows");
			$("#msid-formarea").hide();
		},
		function(){
			$(this).removeClass("msid-leftarrows").addClass("msid-rightarrows");
			$("#msid-formarea").show();
		}
	);

	/* This finds all div's that are not the last bottom bar div, and wraps a master wrapper div for fixed bottom positioning */
	$("body > div:not(#msid-bottombar)").wrapAll("<div id='msid-wrapper'></div>");
});
