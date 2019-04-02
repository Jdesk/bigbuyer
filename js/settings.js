$(window).ready(function(){
	var script = 'ajax/php/settings.php';
	var systemWidget = $('.widget-system');
	var selectors = {		
		templates : {			
		}
	};
	
	init();
	bind();
	
	function init(){
	}
	function bind(){
		systemWidget.find('a.submit').click(function(e){e.preventDefault(); save($(this));});		
	}	
	function save(btn){
		var form = btn.parents('form');
		if(!$.defined(form))return;
		
		btn.button('loading');
		$.post(script,form.serialize(),function(json){
			btn.button('reset');
			if(json.error) error(json.error);
			else{
				notify("Settings Saved!");
			}
		},"json");
	}
});

