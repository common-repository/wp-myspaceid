var msJQuery = jQuery.noConflict(true);

msJQuery(document).ready(function($) {
  $("#btnupdatestatus").click(function() {
	$("#btnupdatestatus").val("updating...");
	args = MySpaceID.Util.urlencode($("#txtstatus").val()) + '&'
			 + MySpaceID.Util.urlencode($("#selmood").val());
	$.get("/index.php", { myspace_update: MySpaceID.Util.urlencode(args) },
	  function(data, textStatus) {
		$("#btnupdatestatus").val("updated!");
		setTimeout(function() {
					 $("#btnupdatestatus").val("update");
				   }, 3000);
	  });
	return false;
  });
});

function delayTimer(delay){
  var timer;
  return function(fn){
	timer=clearTimeout(timer);
	if(fn)
	  timer=setTimeout(function(){
						 fn();
					   },delay);
	return timer;
  }
}
