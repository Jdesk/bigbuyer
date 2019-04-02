$(document).ready(function () {
    var script = "ajax/php/accounts.php";
    var selectors = {
        container			: ".page-accounts",
		
        item				: '.account-item',
        create				: '.create-a',                
        accounts			: '.accounts-container',
        searchFrom			: '.search-form',
        search				: '.search-a',
        clear				: '.clear-a',
        edit				: '.edit-a',
                
        remove				: '.remove-a',
        update				: '.update-a',
        
        templateDownload	: '.download-accounts-a',
        
        upload				: '.fileupload',
		uploading			: '.uploading',
		
		selectAll			: '.select-all',
		bulkRemove			: '.bulk-remove-a',
        
        templates: { 
        	edit			: 'ajax/template/accounts/_edit.phtml',
        	account			: '#template-account',					
        },
	}    
    var container = $(selectors.container);    
    var modal;

    init();
    bind();

    function init() {	    	
    	search();
    }

    function bind() {
		container.find(selectors.create).click(function(e){ e.preventDefault(); create($(this)); });					
		container.find(selectors.search).click(function(e){ e.preventDefault(); search($(this)); });
		container.on('click',selectors.clear, function(e){ e.preventDefault(); clear($(this)); });
		container.on('click',selectors.remove,function(e){ e.preventDefault(); remove($(this)); });		
		container.on('click',selectors.update,function(e){ e.preventDefault(); update($(this)); });
		container.on('click',selectors.edit,function(e){ e.preventDefault(); edit($(this)); });	
		
		container.on('click',selectors.selectAll, function(e){ selectAll($(this)); });		
		container.on('click',selectors.bulkRemove, function(e){ e.preventDefault(); bulkRemove($(this)); });
		
		container.on('change.bs.fileinput',selectors.upload, function(){ importAccounts(); });
		container.on('click',selectors.templateDownload,function(e){e.preventDefault(); downloadTemplate($(this)); });
		
    }
    function bulkRemove(btn){
    	var ids = getSelectedIds();
    	if(ids.length<=0){ $.error("Nothing selected!"); return; }
    	if(!confirm("Are you sure you want to remove the selected accounts?"))return;
    	
    	btn.button('loading');
		$.post(script,{action: 'removeAccounts', ids: ids}, function(json){
			btn.button('reset');
			if(json.error){ $.error(json.error); }
			else{
				search();
			}
		},"json");
    }
    function getSelectedIds(){
    	var ids = new Array();
		
		$.each(container.find(selectors.item), function(){
			var el = $(this);
			
			if(el.find('input[type="checkbox"]').is(':checked')) ids.push(el.attr('data-id'));
		});
		
		return ids;
    }
    function selectAll(input){
		var items = container.find(selectors.item).find('input[type="checkbox"]');
		if(input.is(':checked')){
			items.prop('checked',true);
		}
		else{
			items.prop('checked',false);
		}
	}
    function downloadTemplate(){
		container.append($('<iframe>').addClass('hidden').attr('src',script+'?action=downloadTemplate'));
	}
	function importAccounts(){				
		var form = container.find(selectors.upload);
		var data = new FormData();
		data.append('file', form.find('input[type="file"]')[0].files[0]);
		data.append('action', 'importAccounts');
		form.find('.btn').hide();
		form.find(selectors.uploading).show();
			
		$.ajax({
			url: script,
			data: data,
			processData: false,
			contentType: false,
			dataType: "json",
			type: 'POST',
			success: function(json) {
				form.find('.btn').show();
				form.find(selectors.uploading).hide();
				
				if(json.error){ $.error(json.error); }
				else{ window.location.reload(); }								
			}
		});		
	}
    function create(btn){		
    	save(0,btn);
	}
    function edit(btn){
    	var item = btn.closest(selectors.item);
		var id = item.attr('data-id');
		
    	save(id,btn);
    }
    function save(id,btn){ 
    	if(isNaN(id))id = 0;
    	
    	var title = 'Edit Account';
    	if(id==0){ title = 'Add Account'; }
    	
		modal = new Modal({
			parent: container,
			static: true,
			title: title,
			size: '80%',
			template: selectors.templates.edit,	
			templateData: {id: id},
			buttons: new Array(				
				$('<button>').addClass('btn btn-default dialog-close').text('Cancel'),
				$('<button>').addClass('btn btn-primary').text('Save').click(function(){ 
					var form = modal.modal.find('form');
					var mbtn = $(this);
			    	
					if(!form.valid()){ $.error("Some fields have invalid or missing values"); return; }
					
					
			    	modal.block();
			    	mbtn.button('loading');
			    	$.post(script,form.serialize(),function(json){
			    		modal.release();
			    		mbtn.button('reset');
			    		if(json.error) $.error(json.error);
			    		else{
			    			modal.close();
			    			if(id>0){ reloadItem(id); }
			    			else{ search(); }
			    		}
			    	},"json"); 
				})
			)
		});
    }

    function update(btn){    	    	
    	var id = btn.closest(selectors.item).attr('data-id');
    	btn.button('loading');
    	    	
    	$.post(script,{action: 'updateAccount', id: id},function(json){
    		btn.button('loading');
    		if(json.error){ $.error(json.error); }
    		else{
    			reloadItem(id);
    		}    		
    	},"json")    
    }
	function reloadItem(id){
    	var item = container.find(selectors.item).filter(function(){ return $(this).attr('data-id')==id; })
    	if(item.length<=0){ $.error("Item not found!"); return; }
    	
    	$.post(script,{action: 'getAccount', id: id},function(json){
    		if(json.error){ $.error(json.error); }
    		else{
    			var html = Handlebars.compile(container.find(selectors.templates.account).html());
    			item.replaceWith(html(json.item));
    		}    		
    	},"json");
    }
    function clear(btn){
    	if(!confirm("Are you sure you want to remove current accounts?"))return;
    	btn.button('loading');
    	$.post(script,{action: 'clearData'},function(json){
    		window.location.reload();    		
    	},"json");
    }
    function search() {
    	var params = {};    	
    	params.pageLength = 10;
    	
        var itemTemplate = container.find(selectors.templates.account).html();
        var c = container.find(selectors.accounts);
        var form = container.find(selectors.searchFrom);
        
        ajaxDataTableInit(c,form,itemTemplate,script,params);        
    }
    function remove(btn){
    	if(!confirm("Are you sure you want to delete this account?"))return;
    	   
    	btn.button('loading');
    	var id = btn.closest('tr').attr('data-id');
    	$.post(script,{action: 'removeAccount', id:id},function(json){
    		btn.button('reset');
    		if(json.error){ $.error(json.error); }
    		else{
    			btn.closest('tr').fadeOut(function(){ $(this).remove(); });
    		}
    		
    	},"json");
    }
		
});