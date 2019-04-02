$(document).ready(function(){
	var script = 'ajax/php/team.php';	
	var selectors = {
		container				: '.page-agents',
		create					: '.create-a',
		createAccount			: '.account-a',
		item					: 'tr.agent-item',
		viewAll					: '.inactive-a',
		pageList				: '.page-list',
		selectAll				: '.permissions-select-all',
		deselectAll				: '.permissions-deselect-all',
		actions	: {
			edit				: '.edit-a',
			remove				: '.remove-a',
			loginInfo			: '.login-info-a',
		},
		templates : {
			create				: 'ajax/template/team/_create.phtml',
			message				: 'ajax/template/team/_message.phtml'
		}
	}
	var container = $(selectors.container);
	var modal;
	
	init();
	bind();
	
	function init(){		
	}
	function bind(){
		container.find(selectors.viewAll).click(function(e){e.preventDefault(); viewAll($(this));});
		container.find(selectors.create).click(function(e){e.preventDefault(); create($(this));});
		container.find(selectors.createAccount).click(function(e){e.preventDefault(); createAccount($(this));});		
		container.on('click',selectors.actions.edit, function(e){e.preventDefault(); edit($(this));});		
		container.on('click',selectors.actions.remove, function(e){e.preventDefault(); remove($(this));});		
		container.on('click',selectors.actions.loginInfo, function(e){e.preventDefault(); loginInfo($(this));});
		
		container.on('click',selectors.pageList+' input',function(){ updatePageList($(this)); });		
		container.on('click',selectors.selectAll,function(){ toggleAllPages($(this),true); });
		container.on('click',selectors.deselectAll,function(){ toggleAllPages($(this),false); });
		
		
	}
	function remove(btn){
		if(!confirm("Are you sure you want to remove this user? This cannot be undone."))return;
		var item = btn.closest(selectors.item);
		var id = item.attr('data-id');
		
		$.post(script,{action: 'removeUser', id: id},function(json){
			if(json.error){ $.error(json.error); }
			else{
				item.fadeOut(function(){ item.remove(); });
			}
		},"json");
	}
	function toggleAllPages(btn,status){
		if(!$.defined(status))status=false;
				
		container.find(selectors.pageList+' input').prop('checked',status);
	}
	function updatePageList(btn){	
		var checked = btn.is(':checked');
		
		var li = btn.closest('li');
		var ul = btn.closest('ul');
		
		var parent = ul.siblings('li>input:eq(0)');
		var children = li.find('ul>li>input');		
		var siblings = ul.find('>li>input');
		
		if(checked){			
			parent.prop('checked',true);
		}
		else{ 	
			var uncheckParent = true;
			$.each(siblings,function(){
				var el = $(this);
				if(el.is(':checked')) uncheckParent = false;
			});
			if(uncheckParent)parent.prop('checked',false);
		}
		
		if($.defined(children)){
			$.each(children,function(){
				var el = $(this);
				if(checked){					
					el.prop('checked',true);
				}
				else{ 					
					el.prop('checked',false);
				}
			});
		}
	}
	function viewAll(btn){
		container.find('.disabled').hide().removeClass('hidden').fadeIn();
		return;
		
		if(btn.hasClass('on')){
			btn.html(btn.data('text-on')).addClass('on');
			container.find('.disabled').hide().removeClass('hidden').fadeIn();
		}
		else{
			btn.html(btn.data('text-off')).removeClass('on');
			container.find('.disabled').hide();
		}
		
	}
	function createAccount(){
		modal = new Modal({
			parent: container,
			static: true,
			title: 'Create New Account',
			template: selectors.templates.create,
			templateData: {'accountOwner':1},
			buttons: new Array($('<button>').addClass('btn btn-success').text('Create').click(function(e){e.preventDefault(); save($(this));}),
							   $('<button>').addClass('btn btn-default dialog-close').text('Cancel'))
		});	
	}
	function create(){
		modal = new Modal({
			parent: container,
			static: true,
			title: 'Create New Agent',
			template: selectors.templates.create,
			templateData: {'accountOwner':0},
			buttons: new Array($('<button>').addClass('btn btn-success').text('Create').click(function(e){e.preventDefault(); save($(this));}),
							   $('<button>').addClass('btn btn-default dialog-close').text('Cancel'))
		});	
	}
	function edit(btn){
		var id = btn.parents(selectors.item).attr('data-id');
		if(!$.defined(id)) {error('Agent not found!'); return;} 
		
		modal = new Modal({
			parent: container,
			static: true,
			title: 'Edit Staff Member Information',
			template: selectors.templates.create,
			templateData: { id: id },
			buttons: new Array($('<button>').addClass('btn btn-success').text('Save').click(function(e){e.preventDefault(); save($(this));}),
							   $('<button>').addClass('btn btn-default dialog-close').text('Cancel'))
		});	
	}
	function loginInfo(btn){
		var id = btn.parents(selectors.item).attr('data-id');
		if(!$.defined(id)) {error('Agent not found!'); return;} 
		
		modal = new Modal({
			parent: container,
			static: true,
			title: 'Email Login Information',
			template: selectors.templates.message,
			templateData: { id: id },
			buttons: new Array($('<button>').addClass('btn btn-success').text('Send').click(function(e){e.preventDefault(); send($(this));}),
							   $('<button>').addClass('btn btn-default dialog-close').text('Cancel'))
		});	
	}
	function send(btn){	
		var form = modal.modal.find('form');		
		if(!form.valid()) return;
		
		btn.button('loading');
		modal.block();
		$.post(script,form.serialize(),function(json){
			modal.release();
			if(json.error){
				btn.button('reset');				
				error(json.error);
			}
			else{
				notify('Message sent');
				modal.close();
			}
		},"json");
		
	}
	function save(btn){	
		var form = modal.modal.find('form');		
		if(!form.valid()) return;
		
		btn.button('loading');
		modal.block();
		$.post(script,form.serialize(),function(json){			
			if(json.error){
				btn.button('reset');
				modal.release();
				error(json.error);
			}
			else{				
				window.location.reload();
			}
		},"json");
		
	}

});