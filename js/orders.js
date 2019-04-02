$(document).ready(function () {
    var script = "ajax/php/orders.php";
    var selectors = {
        container					: ".page-orders",
		
        item						: '.order-item',
        create						: '.create-a',                
        orders						: '.orders-container',
        searchFrom					: '.search-form',
        search						: '.search-a',  
        status						: '.status-a',
        cards						: '.cards-a',
        details						: '.details-a',
        remove						: '.remove-a',
        previewHtml					: '.preview-html-a',
        retry						: '.retry-a',
        
        createProductAdd			: '.create-product-add-a',                
        createProductRemove			: '.create-product-remove-a',        
        createProductsContainer		: '.create-products-container',
        ceateProductItem			: '.create-product-item',
        createReview				: '.create-review-container',
        
        summary						: '.summary-container',
                                
        templates: { 
        	details					: '#template-order-details',
        	create					: 'ajax/template/orders/_create.phtml',
        	createProduct			: '#template-create-product-item',
        	createReview			: '#template-create-review',
        	createResults			: 'ajax/template/orders/_create_res.phtml',
        	order					: '#template-order',
        	cards					: 'ajax/template/orders/_cards.phtml',
        	summary					: '#template-summary',        
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
		container.find(selectors.create).click(function(e){ e.preventDefault(); createOrder($(this)); });					
		container.find(selectors.search).click(function(e){ e.preventDefault(); search($(this)); });
		
		container.on('click',selectors.createProductAdd,function(e){ e.preventDefault(); addProduct($(this)); })
		container.on('click',selectors.createProductRemove,function(e){ e.preventDefault(); createProductRemove($(this)); })
		
		container.on('click',selectors.status,function(e){ e.preventDefault(); status($(this)); })
		container.on('click',selectors.cards,function(e){ e.preventDefault(); cardsModal($(this)); })		
		container.on('click',selectors.details,function(e){ e.preventDefault(); detailsModal($(this)); })
		container.on('click',selectors.remove,function(e){ e.preventDefault(); remove($(this)); })
		container.on('click',selectors.previewHtml,function(e){ e.preventDefault(); previewHtml($(this)); })
		container.on('click',selectors.retry,function(e){ e.preventDefault(); retry($(this)); })
    } 
    function retry(btn){
    	var item = btn.closest(selectors.item);
    	var id = item.attr('data-id');
    	if(!confirm("Are you sure you want to retry this order?"))return;
    	
    	btn.button('loading');
    	$.post(script,{action:'retryOrder', id: id},function(json){
    		btn.button('reset');
    		if(json.error){ $.error(json.error); }
    		else{
    			reloadItem(id);
    		}
    	},"json");
    }
    function previewHtml(btn){
    	var item = btn.closest(selectors.item);
    	var id = item.attr('data-id');

    	modal = new Modal({
			parent: container,
			static: true,
			title: "View HTML",
			size: '80%',
			content: 'loading...',
			templateData: {id: id},
			callback: function(){
				setTimeout(function(){
					modal.update('<iframe style="width: 100%; height: 100%; min-height: 500px;"></iframe>');										
					$.post(script,{action:'getErrorHtml', id: id},function(html){
						var doc = modal.modal.find('iframe').contents();			        
				        doc.find('body').html(html);
				        //modal.update();
			    	});					
				},200)							
			},
			buttons: new Array(
				$('<button>').addClass('btn btn-default').text('Done').click(function(){ modal.close(); })
			)
		});       
    }
    function remove(btn){
    	var item = btn.closest(selectors.item);
    	var id = item.attr('data-id');
    	if(!confirm("Are you sure you want to remove this order?"))return;
    	
    	btn.button('loading');
    	$.post(script,{action:'removeOrder', id: id},function(json){
    		btn.button('reset');
    		if(json.error){ $.error(json.error); }
    		else{
    			item.fadeOut(function(){ item.remove() });
    		}
    	},"json");
    	
    }
    function detailsModal(btn){
    	var id = btn.closest(selectors.item).attr('data-id');
    	
    	modal = new Modal({
			parent: container,
			static: true,
			title: "View Order Details",
			size: '80%',
			content: '<div>Loading...</div>',
			templateData: {id: id},
			callback: function(){ setTimeout(function(){ reloadOrderDetails(id); },500); },
			buttons: new Array(
				$('<button>').addClass('btn btn-default').text('Done').click(function(){ modal.close(); })
			)
		});	
    }
    function reloadOrderDetails(id){
    	var html = Handlebars.compile(container.find(selectors.templates.details).html());
    	    	    	    	
    	modal.update('Loading...');
    	$.post(script,{action: 'getOrderDetails', id: id},function(json){
    		if(json.error) c.html(json.error);
    		else{
    			modal.update(html(json.item));
    		}
    	},"json");
    	
    }
    function cardsModal(btn){
    	var id = btn.closest(selectors.item).attr('data-id');
    	
    	modal = new Modal({
			parent: container,
			static: true,
			title: "View Order Giftcards",
			size: '50%',
			template: selectors.templates.cards,	
			templateData: {id: id},
			buttons: new Array(
						$('<button>').addClass('btn btn-default').text('Done').click(function(){ modal.close(); })
					)
		});	
    }
    function status(btn){
    	var id = btn.closest(selectors.item).attr('data-id');
    	
    	btn.button('loading');
    	$.post(script,{action: 'updateStatus', id: id},function(json){
    		btn.button('reset');
    		if(json.error){
    			$.error(json.error);
    		}
    		else{
    			reloadItem(id);
    		}
    	},"json");
    }
    function createOrder(btn){
    	modal = new Modal({
			parent: container,
			static: true,
			title: "Create Order",
			size: '80%',
			template: selectors.templates.create,
			callback: function(){ initCreateWizard(); },
			buttons: new Array()
		});	
    }
    function initCreateWizard(){
    	var form = modal.modal.find('form');
    	form.steps({
            bodyTag: "fieldset",
            onStepChanging: function (event, currentIndex, newIndex){
            	console.log(newIndex);
                if (currentIndex > newIndex)return true;                                        
                if (newIndex === 1 && form.find(selectors.ceateProductItem).length <= 0){
                	alert("Add at least 1 product");
                	return false;
                } 
                
                if(newIndex === 3){
                	generateOrderReview();
                }
                
                // Clean up if user went backward before
                if (currentIndex < newIndex){
                    $(".body:eq(" + newIndex + ") label.error", form).remove();
                    $(".body:eq(" + newIndex + ") .error", form).removeClass("error");
                }                
                form.validate().settings.ignore = ":disabled,:hidden";
                return form.valid();
            },
            onStepChanged: function (event, currentIndex, priorIndex){              
            },
            onFinishing: function (event, currentIndex){
                form.validate().settings.ignore = ":disabled";
                return form.valid();
            },
            onFinished: function (event, currentIndex){       
            	if(currentIndex != 3){ alert("Please review your order first!"); return; }
            	if(confirm("Are you sure you want to create these orders?"))create();
            },
            onCanceled: function (event, currentIndex){  
            	if(confirm("Are you sure you want to cancel?"))modal.close();
            }
        }).validate({
        		errorPlacement: function (error, element){element.before(error);},
        		rules: {}
        });
    }    
    function addProduct(btn){    	    	
    	obj = {
    		uid: generateUUID()
    	}
    	var c = container.find(selectors.createProductsContainer);
    	var html = Handlebars.compile(container.find(selectors.templates.createProduct).html());
    	c.append(html(obj));
    }
    function createProductRemove(btn){
    	var item = btn.closest(selectors.ceateProductItem);
    	item.fadeOut(function(){ item.remove() });
    } 
    function generateOrderReview(){
    	var c = container.find(selectors.createReview);
    	c.html('Generating Order Details...');
    	
    	var data = modal.modal.find('form').serializeObject();
    	data.action = 'reviewOrder';
    	
    	$.post(script,data,function(json){
    		if(json.error) c.html(json.error);
    		else{
    			var html = Handlebars.compile(container.find(selectors.templates.createReview).html());
    			c.html(html(json.stats));
    			dataTableInit(c.find('.dataTable'));
    		}
    	},"json");
    }
    function create(){
    	var btn = $('[href="#finish"]');
    	
    	var data = modal.modal.find('form').serializeObject();
    	data.action = 'createOrder';
    	
    	modal.block();
    	btn.button('loading');
    	$.post(script,data,function(json){
    		btn.button('reset');
    		modal.release();
    		if(json.error){ $.error(json.error); }
    		else{
    			modal.close();
    			    		
    			modal = new Modal({
    				parent: container,
    				static: true,
    				title: "Summary",    				
    				template: selectors.templates.createResults,  
    				templateData: json,
    				buttons: new Array(
    					$('<button>').addClass('btn btn-default').text('Done').click(function(){ modal.close(); search(); })
    				)
    			});	
    			
    		}
    	},"json");
    }
    function reloadItem(id){
    	var item = container.find(selectors.item).filter(function(){ return $(this).attr('data-id')==id; })
    	if(item.length<=0){ $.error("Item not found!"); return; }
    	
    	$.post(script,{action: 'loadOrder', id: id},function(json){
    		if(json.error){ $.error(json.error); }
    		else{
    			var html = Handlebars.compile(container.find(selectors.templates.order).html());
    			item.replaceWith(html(json.item));
    		}    		
    	},"json");
    }
    function search() {
    	var params = {};    	
    	params.pageLength = 10;
    	
        var itemTemplate = container.find(selectors.templates.order).html();
        var c = container.find(selectors.orders);
        var form = container.find(selectors.searchFrom);
        
        ajaxDataTableInit(c,form,itemTemplate,script,params,function(res){ renderSummary(res); });        
    }    
    function renderSummary(data){
    	var c = container.find(selectors.summary);
    	var html = Handlebars.compile(container.find(selectors.templates.summary).html());
    	
    	c.html(html(data.summary));
    }
});