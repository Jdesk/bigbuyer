$(document).ready(function(){
	var script = 'ajax/php/giftcards.php';	
	var selectors = {		
		container			: '.page-giftcards',
		create				: '.create-a',			
		item				: '.card-item',		
		cards				: '.cards-container',
		searchFrom			: '.search-form',
	    search				: '.search-a',
		
		actions	: {
			edit			: '.edit-a',
			remove			: '.remove-a',		
			balance			: '.balance-a',
			check			: '.check-a',
		},	
		
		additional			: '.additional-card-a',
		cardInfo			: '.card-info-container',
		ordersContainer		: '.card-orders-container',
		orderAdd			: '.card-order-add-a',
		orderRemove			: '.card-order-remove-a',
		cardUsedAmount		: '.card-used-amount',
		
		templateDownload	: '.download-gc-a',
		
		upload				: '.fileupload',
		uploading			: '.uploading',
		
		
		
		bulkBalance			: '.bulk-balance-a',
		bulkBalanceCancel	: '.bulk-balance-cancel-a',		
				
		templates : {
			card				: '#template-card',
			create				: 'ajax/template/giftcards/_create.phtml',
			balance				: 'ajax/template/giftcards/_bulk_balance.phtml',
			
			order				: '#template-card-order',
			orderAdd			: 'ajax/template/giftcards/_order_add.phtml',						
		}		
		
	}	
	var container = $(selectors.container);
	var bulkModal, orderModal, importModal, modal;
	
	init();
	bind();
	
	function init(){
		search();
	}
	function bind(){
		container.find(selectors.search).click(function(e){ e.preventDefault(); search($(this)); });
		
		container.on('change.bs.fileinput',selectors.upload, function(){ importCards(); });
				
		container.find(selectors.create).click(function(e){e.preventDefault(); createCard();});							
		container.on('click',selectors.actions.check,function(e){e.preventDefault(); checkBalance($(this));});
		container.on('click',selectors.actions.balance,function(e){e.preventDefault(); updateBalance($(this));});
		container.on('click',selectors.actions.edit,function(e){e.preventDefault(); editCard($(this));});
		container.on('click',selectors.actions.remove,function(e){e.preventDefault(); removeCard($(this));});
					
		container.on('click',selectors.orderAdd,function(e){ e.preventDefault(); addCardOrderModal($(this)); });
		container.on('click',selectors.orderRemove,function(e){ e.preventDefault(); removeCardOrder($(this)); });		
					
		container.find(selectors.bulkBalance).click(function(e){e.preventDefault(); bulkBalanceDialog($(this));});
		container.on('click',selectors.bulkBalanceCancel,function(e){e.preventDefault(); bulkBalanceCancel($(this)); });	
		
		container.on('click',selectors.templateDownload,function(e){e.preventDefault(); downloadTemplate($(this)); });
	}
	function downloadTemplate(){
		container.append($('<iframe>').addClass('hidden').attr('src',script+'?action=downloadTemplate'));
	}
	function importCards(){				
		var form = container.find(selectors.upload);
		var data = new FormData();
		data.append('file', form.find('input[type="file"]')[0].files[0]);
		data.append('action', 'importCards');
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
	function search() {
    	var params = {};    	
    	params.pageLength = 10;
    	
        var itemTemplate = container.find(selectors.templates.card).html();
        var c = container.find(selectors.cards);
        var form = container.find(selectors.searchFrom);
        
        ajaxDataTableInit(c,form,itemTemplate,script,params);        
    }
	function bulkBalanceCancel(btn){
		var item = btn.closest('tr');
		var id = item.attr('data-id');
		
		btn.button('loading');
		$.post(script,{action: 'bulkBalanceCancel', id: id},function(json){
			btn.button('reset');
			if(json.error) $.error(json.error);
			else{
				btn.fadeOut(function(){ btn.remove(); });
			}
		},"json");
	}
	function bulkBalanceDialog(){
		modal = new Modal({
			parent: container,
			static: true,
			title: 'Bulk Balance Check',
			template: selectors.templates.balance,
			buttons: new Array(
				$('<button>').addClass('btn btn-success').text('Check Balances').click(function(){ bulkBalanceCheck($(this)); }),
				$('<button>').addClass('btn btn-default dialog-close').text('Cancel')
			)
		});	
	}
	function bulkBalanceCheck(btn){
		var form = modal.modal.find('form');
		
		modal.block();
		btn.button('loading');
		$.post(script,form.serialize(),function(json){
			modal.release();
			btn.button('reset');
			if(json.error){ $.error(json.error); }
			else{ window.location.reload(); }
		},"json");
		
	}
	
	function checkBalance(btn){
		var number = modal.modal.find('input[name="number"]').val();
		var pin = modal.modal.find('input[name="pin"]').val();
		var store = modal.modal.find('input[name="store"]').val();
		
		btn.button('loading');
		$.post(script,{action: 'checkBalance', number: number, pin: pin, store: store},function(json){
			btn.button('reset');
			if(json.error)$.error(json.error);
			else{				
				 modal.modal.find('input[name="balance"]').val(json.balance);
				 modal.modal.find('input[name="last_checked"]').val(json.checked);
			}
		},"json");
	}
	function updateBalance(btn){
		var id = btn.attr('data-id');
		
		btn.button('loading');
		$.post(script,{action: 'updateBalance', id:id},function(json){
			btn.button('reset');
			if(json.error)$.error(json.error);
			else{
				if($.defined(modal) && modal.modal.is(':visible'))
					modal.refresh();
				else
					window.location.reload();
			}
		},"json");
	}	
	
	function createCard(card){
		if(!$.defined(card))card={};
		card.id=0;
		modal = new Modal({
			parent: container,
			static: true,
			title: 'Add New Card',
			template: selectors.templates.create,
			templateData: {card: card},
			callback: function(){ loadCardOrders(); },
			buttons: new Array($('<button>').addClass('btn btn-success').text('Create').click(function(e){e.preventDefault(); saveCard($(this));}),
							   $('<button>').addClass('btn btn-default dialog-close').text('Cancel'))
		});	
	}
	function loadCardOrders(){
		var c = container.find(selectors.ordersContainer);
		c.html('Loading...');
		
		$.post(script,{action: 'getCardOrders', id: c.attr('data-id')},function(json){
			if(json.error){ c.html('ERROR: '+json.error); }
			else{
				var template = Handlebars.compile(container.find(selectors.templates.order).html());
				c.html(template(json));
				container.find(selectors.cardUsedAmount).html('$'+json.used);				
			}
		},"json");
	}
	function removeCardOrder(btn){
		if(!confirm("Are you sure you want to remove this charge?"))return false;
		var id = btn.closest('tr').attr('data-id');
		$.post(script,{action: 'cardOrderRemove',id: id}, function(json){
			if(json.error) $.error(json.error);
			else{
				loadCardOrders();
			}
		},"json");
	}
	function addCardOrderModal(btn){
		var id = container.find(selectors.ordersContainer).attr('data-id');
		orderModal = new Modal({
			parent: container,
			static: true,
			title: 'Add New Charge',
			template: selectors.templates.orderAdd,
			templateData: {id: id},
			buttons: new Array(												
				$('<button>').addClass('btn btn-success').text('Add').click(function(){ addCardOrder($(this)); }),
				$('<button>').addClass('btn btn-default dialog-close').text('Done').click(function(){ window.location.reload(); })
			)
		});	
	}
	function addCardOrder(btn){
		var form = orderModal.modal.find('form');
		
		orderModal.block();
		btn.button('loading');
		$.post(script,form.serialize(),function(json){
			orderModal.release();
			btn.button('reset');
		
			if(json.error)$.error(json.error);
			else{
				orderModal.close();
				loadCardOrders();
			}
		},"json");
	}
	function editCard(btn){
		var id = btn.parents(selectors.item).attr('data-id');
		if(!$.defined(id)) {error('Card not found!'); return;} 
		
		modal = new Modal({
			parent: container,
			static: true,
			title: 'Edit Card Information',
			template: selectors.templates.create,
			templateData: { id: id },
			callback: function(){ loadCardOrders(id); },
			buttons: new Array($('<button>').addClass('btn btn-success').text('Save').click(function(e){e.preventDefault(); saveCard($(this),id);}),
							   $('<button>').addClass('btn btn-default dialog-close').text('Cancel'))
		});	
	}
	function saveCard(btn,id){	
		var form = modal.modal.find('form');		
		if(!form.valid()) return;
		
		btn.button('loading');
		modal.block();
		$.post(script,form.serialize(),function(json){			
			btn.button('reset');
			modal.release();
			if(json.error){				
				error(json.error);
			}
			else{	
				modal.close();
				reloadItem(id);
			}
		},"json");	
	}
	function reloadItem(id){
    	var item = container.find(selectors.item).filter(function(){ return $(this).attr('data-id')==id; })
    	if(item.length<=0){ $.error("Item not found!"); return; }
    	
    	$.post(script,{action: 'loadCard', id: id},function(json){
    		if(json.error){ $.error(json.error); }
    		else{
    			var html = Handlebars.compile(container.find(selectors.templates.card).html());
    			item.replaceWith(html(json.item));
    		}    		
    	},"json");
    }
	function removeCard(btn){	
		if(!confirm("Are you sure you want to delete this Gift Card?"))return;
		var item = btn.parents(selectors.item);
		var id = item.attr('data-id');
		if(!$.defined(id)) {error('Gift Card not found!'); return;} 		
		
		btn.button('loading');		
		$.post(script,{action: 'removeCard', id: id},function(json){						
			if(json.error){ error(json.error); }
			else{ item.fadeOut(); }
		},"json");	
	}
});