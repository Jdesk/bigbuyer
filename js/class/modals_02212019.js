var Modal = Class.create({
	init: function(opts) {
		var $this = this;
		
		this.options = {
			parent			: '',
			template		: '',
			templateData	: '',
			size			: '',
			title			: '',
			content			: '',
			'static'		: false,
			onReadySignal	: 'modalReady',	
			callback		: '',
			buttons			: new Array($('<button>').addClass('btn btn-default dialog-close').text('Close'))
		}						
		this.setOptions(opts);
		
		this.blocked = false;		
		this.create();
		this.bind();						
	},	
	setOptions: function(opts){
		if(!$.defined(opts)) return;
		var $this = this;
		$.each(opts, function(n,o){
			$this.options[n] = o;
		});
	},	
	bind: function(){
		var $this = this;
		this.modal.on('click','.dialog-close',function(e){e.preventDefault(); $this.close();});
	},
	create: function(){
		var $this = this;
		this.modal = $('<div class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">\
						<div class="modal-dialog">\
							<div class="modal-content">\
								<div class="modal-header">\
									<button type="button" class="close dialog-close" aria-hidden="true">&times;</button>\
									<h4 class="modal-title"></h4>\
								</div>\
								<div class="modal-body"></div>\
								<div class="modal-footer"></div>\
							</div>\
						</div>\
					</div>');									
		this.modal.find('.modal-title').html(this.options.title);
		if($.defined(this.options.size))
			this.modal.find('.modal-dialog').css('width',this.options.size);
		
		if($.defined(this.options.template)){
			this.loadTemplate(this.options.template);										
		}
		else{
			this.modal.find('.modal-body').html(this.options.content);	
			this.onReady();
		}
		
						
		$.each($this.options.buttons,function(n,el){
			el = $(this);
			//el.attr('type','button').addClass("btn btn-default");
			if($.defined(el.attr('bind-close'))) el.attr('data-dismiss','modal');
			$this.modal.find('.modal-footer').append(el);
		});		
		var params = {};
		if(this.options.static){
			params.backdrop = 'static';
		}						
		
		if(this.options.parent && $(this.options.parent).exists()){
			this.modal.appendTo($(this.options.parent));
		}
		this.modal.modal(params);
	},
	onReady: function(){
		elementsInit();
		$(window).trigger(this.options.onReadySignal);	
		if(typeof this.options.callback == 'function')
			this.options.callback();
	},
	loadTemplate: function(template){			
		var $this = this;
		var templateData = this.options.templateData;	
		this.loading();
		$.ajax({
			type: 'POST',
            url: template, 
            data: {'templateData': templateData},
            success: function(data) {
                source    = data;
                template  = Handlebars.compile(source);                
                if($.defined(templateData)){                	
                	$this.update(template(templateData));
                }
                else{
                	$this.update(template);
                } 				
				$this.onReady();				
            }               
        });		
	},
	update: function(content){
		this.modal.find('.modal-body').html(content);
	},
	loading: function(){
		this.update('loading...');
	},
	close: function(){
		var $this = this;
		if(this.blocked == true) return;
		
		this.modal.modal('hide');
		setTimeout(function(){ $this.modal.remove(); }, 1000);
	},
	block: function(){
		this.blocked = true;
		
		$.each(this.modal.find('.dialog-close'),function(){ $(this).addClass('disabled'); });
	},
	release: function(){
		this.blocked = false;
		
		$.each(this.modal.find('.dialog-close'),function(){ $(this).removeClass('disabled'); });
	}
});
