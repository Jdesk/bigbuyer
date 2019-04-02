$(window).ready(function () {	
    $.each($('.widget-alerts'), function () {
        new Alerts($(this));
    });
});

var Alerts = Class.create({
    init: function (el, opts) {
        var $this = this;

        this.options = {
            script					: 'ajax/php/widgets/alerts.php',
			listLimit				: 5,            
        }
		this.selectors= { 
			list					: '.list',
			listItem				: '.list-item',
			listCount				: '.list-count',
			listRead				: '.list-read-a',
			
			fullBtn					: '.full-a',
			fullContainer			: '.full-container',
			fullMore				: '.full-more-a',
			fullMove				: '.full-move-a',
			fullRemove				: '.full-remove-a',
			fullEvent				: '.event-container',
			fullEventName			: '.event-name',
			fullEventDate			: '.event-date',
			fullItem				: '.full-item',
			fullRemoveAll			: '.full-remove-all-a',		
			
			
			searchForm				: '.search-form',
			searchTriggers			: '.search-form input,.search-form select',
		},
		this.templates= {                
			listItem				: '#template-list-item',
			listError				: '#template-list-error',
			listEmpty				: '#template-list-empty',
			
			full					: 'ajax/template/widgets/alerts/full.phtml',			
			fullItem				: '#template-full-item',
			fullError				: '#template-full-error',
			fullEmpty				: '#template-full-empty',
		}
        $this.setOptions(opts);

        $this.widget = el;
        if (!$.defined(this.widget)) return;

		$this.list 			= this.widget.find(this.selectors.list);
        $this.alerts		= {}; 
        $this.modal			= null;
		$this.ap			= {};
        $this.bind();
		$this.updateList();
		$this.notify();
    },
    setOptions: function (opts) {
        if (!$.defined(opts)) return;
        var $this = this;
        $.each(opts, function (n, o) { $this.options[n] = o; });
    },
    bind: function () {
        var $this = this;  
						
		this.widget.on('click',$this.selectors.listRead,function(e){e.preventDefault(); e.stopPropagation(); $this.readListItem($(this)); })
		
		this.widget.on('click',$this.selectors.fullBtn,function(e){e.preventDefault(); e.stopPropagation(); $this.viewFull($(this)); })
		this.widget.on('click',$this.selectors.fullMore,function(e){e.preventDefault(); e.stopPropagation(); $this.viewMore($(this)); })
		this.widget.on('click',$this.selectors.fullMove,function(e){e.preventDefault(); e.stopPropagation(); $this.toggleFullMove($(this)); })
		this.widget.on('click',$this.selectors.fullRemove,function(e){e.preventDefault(); e.stopPropagation(); $this.removeFullItem($(this)); })		
		this.widget.on('click',$this.selectors.fullRemoveAll,function(e){e.preventDefault(); e.stopPropagation(); $this.removeFullAll($(this)); })				
		this.widget.on('click',$this.selectors.fullEvent,function(e){e.preventDefault(); e.stopPropagation(); $this.searchEvent($(this)); })		
				
		this.widget.on('change',$this.selectors.searchTriggers,function(e){ $this.search(); })				
    },
	notify: function(){
		var $this = this;
		$.post(this.options.script,{action: 'getNotifications'}, function(json){
			setTimeout(function(){ $this.notify(); },5000);
			if(json.items){
				$.each(json.items,function(n,el){					
					$this.showNotification(el.description,el.title);					
				});
				$this.updateCount();
				$this.updateList();  	
			}
		},"json");
	},
	showNotification: function(msg,title){
		var $this = this;
		
		toastr.options = {
			closeButton: true,            
			progressBar: true,			
			positionClass: 'toast-bottom-right'
		};		
		toastr.options.onclick = function () {
			$this.viewFull();
		};
        toastr.warning(msg, title);
	},
	readListItem: function(btn){
		var $this = this;
		var item = btn.closest(this.selectors.listItem);
		var id = item.attr('data-id');
		
		item.fadeOut();
		item.prev('li').fadeOut();
		$.post(this.options.script,{action:'read',id:id},function(json){
			if(json.error){ $.error(json.error); }
			else{ $this.updateCount(); }
		},"json");
	},
	updateList: function(){		
		var $this = this;		
		if($this.ap.updateList)return;
				
		this.updateCount();
		$this.list.html('Loading...');
		
		$this.ap.updateList = true;
		$.post($this.options.script,{action: 'getList', limit: $this.options.listLimit},function(json){
			$this.ap.updateList = false;
			if(json.error){
				var html = Handlebars.compile($this.widget.find($this.templates.listError).html());
				$this.list.html(html());
			}
			else if(!json.items || json.items.length<=0){
				var html = Handlebars.compile($this.widget.find($this.templates.listEmpty).html());
				$this.list.html(html());
			}
			else{				
				$this.list.html('');
				var html = Handlebars.compile($this.widget.find($this.templates.listItem).html());
				$this.list.html(html(json));
			}
		},"json");
	},
	updateCount: function(){
		var $this = this;	
		if($this.ap.updateCount)return;
		
		$this.ap.updateCount = true;
		$.post($this.options.script,{action: 'getCount'},function(json){
			$this.ap.updateCount = false;
			if(json.error){				
				$this.widget.find($this.selectors.listCount).text('!')
			}			
			else{
				$this.widget.find($this.selectors.listCount).text(json.count);				
			}
		},"json");
	},
	
	viewFull: function(){
		var $this = this;
		
		this.modal = new Modal({
			parent: $this.widget,
			static: true,			
			title: 'System Alerts',
			template: $this.templates.full,	
			callback: function(){ $this.search(); },
			buttons: new Array(				
				$('<button>').addClass('btn btn-default dialog-close').text('Done')				
			)
		});
	},
	toggleFullMove: function(btn){
		btn.toggleClass('btn-success').toggleClass('btn-default').toggleClass('on');
		
		if(btn.hasClass('on')){		
			$('.modal-backdrop').fadeOut('fast');
			this.modal.modal.draggable();			
			//this.modal.find('.modal-body').resizable();						
		}
		else{
			$('.modal-backdrop').show();
			this.modal.modal.draggable('destroy');			
		}
	},
	viewMore: function(){
		this.search(false);
	},			
	search: function(reset){
		var $this = this;
		var form = this.widget.find(this.selectors.searchForm);		
		var more = this.widget.find(this.selectors.fullMore);
		if(typeof reset == 'undefined')reset = true;
		
		console.log(reset);
					
		var start = 0;			
		if(reset){			
			more.show();
		}
		else{
			start = parseInt(form.find('[name="page"]').val());
			start++;						
		}
		form.find('[name="page"]').val(start);		
		console.log(start);
									
		var c = this.widget.find(this.selectors.fullContainer);
		more.button('loading');
		c.loading(true);
		$.post(this.options.script,form.serialize(),function(json){
			more.button('reset');
			c.loading(false);
			if(json.error){
				var html = Handlebars.compile($this.widget.find($this.templates.fullError).html());
				c.html(html());
			}
			else if(!json.items || json.items.length<=0){
				if(start > 0){
					more.fadeOut();
				}
				else{
					var html = Handlebars.compile($this.widget.find($this.templates.fullEmpty).html());
					c.html(html());
				}
			}
			else{				
				if(start == 0)c.html('');
				var html = Handlebars.compile($this.widget.find($this.templates.fullItem).html());
				
				$.each(json.items,function(n,el){
					c.append(html(el));
				});				
			}
		},"json");
	},
	searchEvent: function(obj){
		var data = {
			name: obj.find(this.selectors.fullEventName).text(),
			date: obj.find(this.selectors.fullEventDate).text()
		};
		
		$(window).trigger('searchEvent',data);
	},
	removeFullItem: function(btn){
		if(!confirm("Are you sure you want to remove the alert?"))return;
		
		var $this = this;
		var item = btn.closest(this.selectors.fullItem);
		var id = item.attr('data-id');
		
		btn.button('loading');
		$.post(this.options.script,{action:'remove',id:id},function(json){			
			if(json.error){ $.error(json.error); }
			else{ 
				item.fadeOut(function(){ item.remove(); });		
				$this.updateCount(); 
				$this.updateList(); 
			}
		},"json");
	},
	removeFullAll: function(btn){
		if(!confirm("Are you sure you want to remove all alerts?"))return;
		
		var $this = this;
		var item = btn.closest(this.selectors.fullItem);
		var id = item.attr('data-id');
		
		btn.button('loading');
		$.post(this.options.script,{action:'removeAll'},function(json){			
			btn.button('reset');
			if(json.error){ $.error(json.error); }
			else{ 
				$this.modal.close();
				$this.updateCount();
				$this.updateList();  				
			}
		},"json");
	}
});