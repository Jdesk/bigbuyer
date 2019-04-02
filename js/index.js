$(document).ready(function() {
	var selectors = {
			selectInput			: '.select-input',
			selectInputSelect	: 'select',
			selectInputValue	: '.select-input-value',
			selectInputCancel	: '.select-input-cancel',						
	};
	
	init();
	bind();
	
	function init(){
		noconflict();	
		toastrInit();		
		elementsInit();		
	}
	function bind(){
		$('body').on('change',selectors.selectInput+' '+selectors.selectInputSelect,function(){ updateSelectInput($(this)); });
		$('body').on('click',selectors.selectInput+' '+selectors.selectInputCancel,function(){ resetSelectInput($(this)); });
		$('.password-reset-a').click(function(){ showPasswordReset(); });		
	}
	function noconflict(){
	}	
	
	function showPasswordReset(){
		$('.login-container').hide();
		$('.reset-password-container').fadeIn();
	}
	function resetSelectInput(btn){
		var c = btn.closest(selectors.selectInput);
		var inputContainer = c.find(selectors.selectInputValue);
		var input = inputContainer.find('input');
		var select = c.find(selectors.selectInputSelect);
		
		input.val('');
		select.val('');
		inputContainer.addClass('hidden');
		select.removeClass('hidden');
	}
	function updateSelectInput(select){
		var c = select.closest(selectors.selectInput);
		var inputContainer = c.find(selectors.selectInputValue);
		var input = inputContainer.find('input');						
		var val = select.val();
		input.val('');
		inputContainer.addClass('hidden');
		if($.defined(val)){
			if(val == 'new'){
				select.addClass('hidden');
				inputContainer.removeClass('hidden');
			
			}
			else{				
				input.val(val);
			}
		}	
	}
});


/////////******************* Handlebars
Handlebars.registerHelper('formatTimeAgo', function(value) {	
	var s = moment(value).local().format("MM/DD/YYYY hh:mm A");	
	if(s == 'Invalid date')return '';
	
	try{		
		//value = moment(moment(value).format("YYYY/MM/DD HH:mm:ss")+' EST').fromNow();				Remember to switch this back when daylight savings happens.
		//value = moment(moment(value).format("YYYY/MM/DD HH:mm:ss")+' EDT').fromNow();		
		value = moment(moment(value).format("YYYY/MM/DD HH:mm:ss")+' PST').fromNow();
	}catch(e){ alert(e); }
    return value;
});
Handlebars.registerHelper('formatCurrency', function(value) {
	value = parseFloat(value);
	if(isNaN(value))value=0;
    return value.format(2);
});
Handlebars.registerHelper('formatNumber', function(value) {
	value = parseFloat(value);
	if(isNaN(value))value=0;
    return value.format(0);
});
Handlebars.registerHelper('ifvalue', function (conditional, options) {
	if (options.hash.value === conditional) { return options.fn(this) } 
	else { return options.inverse(this); }
});
//better conditional if statement (xIf)
Handlebars.registerHelper('xIf', function (lvalue, operator, rvalue, options) {
    var operators, result;

    if (arguments.length < 3) {
        throw new Error("Handlerbars Helper 'compare' needs 2 parameters");
    }

    if (options === undefined) {
        options = rvalue;
        rvalue = operator;
        operator = "===";
    }

    operators = {
        '==': function (l, r) { return l == r; },
        '===': function (l, r) { return l === r; },
        '!=': function (l, r) { return l != r; },
        '!==': function (l, r) { return l !== r; },
        '<': function (l, r) { return l < r; },
        '>': function (l, r) { return l > r; },
        '<=': function (l, r) { return l <= r; },
        '>=': function (l, r) { return l >= r; },
        'typeof': function (l, r) { return typeof l == r; }
    };

    if (!operators[operator]) {
        throw new Error("'xIf' doesn't know the operator " + operator);
    }

    result = operators[operator](lvalue, rvalue);

    if (result) {
        return options.fn(this);
    } else {
        return options.inverse(this);
    }
});

/////////******************* General
jQuery.fn.loading = function(clear){
	if(clear==false){
		$(this).find('.loader').remove();
		$(this).removeClass('loading');
	}
	else{
		if($(this).hasClass('loading'))return;
		$(this).append($('<div>').addClass('loader').append($('<i></i>').addClass('fa fa-spinner fa-spin')))
		$(this).addClass('loading');	
	}	 
}

jQuery.fn.exists = function(){return (this.length>0);}
//jQuery.defined = function(v) {return typeof v == 'number' || (typeof v != 'undefined' && v != null && (v.length > 0 || ( typeof v == 'object' && Object.keys(v).length>0 )));}
jQuery.defined = function(v) {return typeof v == "number" || (typeof v == "boolean" && v == true) || (typeof v != 'undefined' && v != null && (v.length > 0 || ( typeof v == 'object' && Object.keys(v).length>0 )));}
jQuery.modal = function(title,content,buttons){ 
	var options = {'title':title,'content':content,'static':true};
	if($.defined(buttons)) options.buttons = buttons;
	return new Modal(options); 
}
jQuery.dialog = function(title,content,buttons){ 
	var options = {'title':title,'content':content};
	if($.defined(buttons)) options.buttons = buttons;
	return new Modal(options); 
}
jQuery.error = function(msg,title){ error(msg,title); };
jQuery.notify = function(msg,title){ notify(msg,title); };

$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};
function error(description,title){
	if(!$.defined(title)){ title = 'An error occured!'; }
	if(!$.defined(description)){ description = ''; }
	//$.gritter.add({title: title,text: description,class_name: 'gritter-bootstrap gritter-danger'});
	toastr.error(title,description);
}
function notify(description,title){
	if(!$.defined(title)){ title = ''; }
	if(!$.defined(description)){ description = ''; }	
	//$.gritter.add({title: title,text: description,class_name: 'gritter-bootstrap gritter-success'});
	toastr.success(title,description);	
}
function errorInline(msg,container){
	if(!$.defined(container)){ error(msg); return; } 
	var el = '<div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Error!</strong> '+msg+'</div>';
	container.append(el);
}
function fillForm(form,data){
	$.each(data,function(k,v){		
		var el = form.find('[name="'+k+'"]');
		var type = el.attr('type');
		if(el){
			if(type == 'radio' || type == 'checkbox'){
				$.each(el,function(){ if($(this).val() == v){ $(this).attr('checked','checked'); } }); 
			}
			else{ el.val(v); }						
		}
	});
}
function showFormErrors(errors,form){
	if(typeof form == 'undefined'){ alert(JSON.stringify(errors)); return; }
	$.each(errors,function(k,v){		
		var group = form.find('[name="'+k+'"]').parents('.form-group');		
		if(group){
			group.addClass('has-error');		
			group.prepend($('<div>').addClass('form-error alert alert-danger').text(v));
		}
	});
}
function clearFormErrors(form){
	form.find('.has-error').removeClass('has-error');
	form.find('.form-error').remove();
}
function loading(obj){
	if(obj)
		obj.append($('<div>').addClass('loading').fadeIn());
}
function done(obj){
	if(obj && obj.find('.loading'))
		obj.find('.loading').fadeOut(function(){ obj.find('.loading').remove(); });
}
function generateUUID() {
    var d = new Date().getTime();
    var uuid = 'xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = (d + Math.random()*16)%16 | 0;
        d = Math.floor(d/16);
        return (c=='x' ? r : (r&0x7|0x8)).toString(16);
    });
    return uuid;
};
function json2table(obj,table,level){
	if(typeof table == 'undefined' || table.length==0){ table = $('<table>').addClass('table-bordered'); }
	if(typeof level == 'undefined'){ level = 3; }
	level++;		
	$.each(obj, function(k, item) {		
		if(typeof(item) == 'object'){
			var row = $('<tr>');
			row.append($('<td colspan="2">').addClass('active text-center').append($('<h'+level+'>').text(k)));
			table.append(row);
			json2table(item,table,level);			
		}
		else{
			var row = $('<tr>');
			row.append($('<td>').text(k));
			row.append($('<td>').text(item));
			table.append(row);
		}		
	});
	return table;
}
Number.prototype.format = function(n, x) {
    var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
    return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
};

//////////////////////////////////////////////// INIT functions
function toastrInit(){
	toastr.options = {
			  "closeButton": true,
			  "debug": false,
			  "progressBar": true,
			  "positionClass": "toast-top-right",
			  "onclick": null,
			  "showDuration": "400",
			  "hideDuration": "1000",
			  "timeOut": "7000",
			  "extendedTimeOut": "1000",
			  "showEasing": "swing",
			  "hideEasing": "linear",
			  "showMethod": "fadeIn",
			  "hideMethod": "fadeOut"
	};
}
function elementsInit(){
			
	$('input:not(".has-enter-init"),select:not(".has-enter-init"),textarea:not(".has-enter-init")').keypress(function (e) {
		if (e.which == 13) {
			var el = $(this);
			
			el.addClass('has-enter-init');						
			var obj = el.parents('.modal-dialog');
			if(obj.length<=0)obj = el.closest('form');
						
			if(obj.length==1){
				obj.find('.enter').click();
				return false;
			}			
		}
	});	
	
	
	
	if (typeof Switchery != 'undefined' && $('.js-switch:not(".has-init")').length){
		$.each($('.js-switch:not(".has-init")'),function(){
			var el = $(this); 
			var color = el.attr('data-color');
			if(!$.defined(color)) color = '#1AB394'
			el.addClass('has-init'); 			
			new Switchery(el[0], { color: color }); 
		})		
	}
			
			
	if (typeof $.fn.daterangepicker != 'undefined' && $('input.daterangepicker:not(".has-init")').length){
		console.log($('input.daterangepicker:not(".has-init")'));
		$.each($('input.daterangepicker:not(".has-init")'),function(){
			var el = $(this);
			var type = el.attr('data-type');					
			var parent = el.parents('.modal');
		
			
			var options = {};
			switch(type){
				case 'single':
					options = {
						parentEl: parent,
						singleDatePicker: true,
						showDropdowns: true,
					};
					break;
				default:
					options = {
							parentEl: parent,
							startDate: moment().subtract(7, 'days'),
							endDate: moment(),
							ranges: {
								'Today': [moment(), moment()],
								'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
								'Last 7 Days': [moment().subtract(6, 'days'), moment()],
								'Last 30 Days': [moment().subtract(29, 'days'), moment()],
								'This Month': [moment().startOf('month'), moment().endOf('month')],
								'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
							}
						};
					break;
					
			}
			el.addClass('has-init').daterangepicker(options);			
		});				
	}
	

	
	if (typeof $.fn.datepicker != 'undefined' && $('.date-picker:not(".has-init")').length){
		$('.date-picker:not(".has-init")').addClass('has-init').datepicker({
			todayBtn: "linked",
			keyboardNavigation: false,
			forceParse: false,
			calendarWeeks: true,
			autoclose: true
		});	
	}
	
	if (typeof $.fn.easyZoom != 'undefined' && $('.img-zoom:not(".has-init")').length){
		//$('.img-zoom:not(".has-init")').zoom({url: $(this).attr('data-zoom')}).addClass('has-init');		
		$('.img-zoom:not(".has-init")').easyZoom().addClass('has-init');
	}
	
	
	if (typeof $.fn.tooltip != 'undefined' && $('[data-toggle="tooltip"]').length){
		$('[data-toggle="tooltip"]').tooltip({html: true});	
	}
	
	if (typeof $.fn.steps != 'undefined' && $('.steps:not(.has-init)').length){
		var opts = new Array('enableCancelButton','enableFinishButton');
		$.each($('.steps:not(.has-init)'),function(){			
			var options = {};
			var el = $(this);
                        
			$.each(opts,function(n,o){ 
				var v = el.attr('data-'+o.toLocaleLowerCase());					
				if($.defined(v)){	
					if(v=="false")v=false;
					if(v=="true")v=true;
					options[o] = v; 
				}
			});					
			el.addClass('has-init').steps(options);
		});		
	}	 
	
	if (typeof $.fn.summernote != 'undefined' && $('.summernote:not(.has-init)').length){
		$('.summernote:not(.has-init)').each(function () {
		    var editor = $(this);

		    editor.summernote({
		        onkeyup: function (e) { editor.val($(this).code()); editor.change(); },
		        change: function (e) { editor.val($(this).code()); editor.change(); },
		    }).addClass('has-init');
		});
	}
	 
	if (typeof $.fn.chosen != 'undefined'){
		var chosenSelectConfig = {
	            '.chosen-select': {},
	            '.chosen-select-deselect': {allow_single_deselect: true},
	            '.chosen-select-no-single': {disable_search_threshold: 10},
	            '.chosen-select-no-results': {no_results_text: 'Oops, nothing found!'},
	            '.chosen-select-width': {width: "100%"}
		};
		for (var selector in chosenSelectConfig) {
			$.each($(selector+':not(.has-init)'),function(){
				var el = $(this);
				el.chosen(chosenSelectConfig[selector]);
				el.addClass('has-init');

				try{
					if(el.attr('data-keep-open') == "1"){
						var ch = el.chosen().data("chosen");
						var _fn = ch.result_select;
						ch.result_select = function(evt) {    
							evt["metaKey"] = true;
							evt["ctrlKey"] = true;
							ch.result_highlight.addClass("result-selected");
							return _fn.call(ch, evt);
						};
					}
				}catch(e){}

			});
		}			
	}
	
	multiselectInit();
	dataTableInit();
}


function ajaxDataTableInit(table,form,itemTemplate,script,params,callback){
	if(!$.defined(params))params = {};
	var data = form.serialize();	
	var el = table;
	var json = {};
			
	var options = {
		"destroy": true,
		"searching": false,
		"pageLength": 10,
		"processing": true,
		"serverSide": true,
		"filter": true,        
		"ajax": {
			"url": script+'?'+data,
			"dataSrc": function(res){ 		
				json = res;															
				return {}; 
			},
		},
		"initComplete": function(){ 			
			if(!json || json.length<0)return;
			$.each(json.data, function(n, el){
				var template = Handlebars.compile(itemTemplate);
				var html = template(el);
						
				table.row.add($(html));			
			});	
			
			console.log(json.page);
									
			table.destroy();			
			ajaxDataTableInit(el,form,itemTemplate,script,{
				'deferred': new Array(json.total,json.total),
				'page': parseInt(json.page),
				'pageLength': parseInt(json.length),
				'sort': json.sort,
				'sortDir': json.sortDir
			});

			if(callback) callback(json);
		}
	};
	
	var colopts = new Array('bSortable','bVisible','iDataSort','sType','sSortDataType','orderable');
	options['aoColumns'] = new Array();
	$.each(el.find('thead th'),function(){			
		var col = $(this);
		var header = {};
		
		$.each(colopts,function(n,o){ 
			var v = col.attr('data-'+o.toLocaleLowerCase());					
			if($.defined(v)){
				if(v=="false")v=false;
				if(v=="true")v=true;
				header[o] = v; 
			}
		});	 						
		options['aoColumns'].push(header);			
	});	
	
	if(params.deferred)options['deferLoading'] = params.deferred;
	if(params.page && $.defined(params.page))options['displayStart'] = params.page;
	if(params.pageLength && $.defined(params.pageLength))options['pageLength'] = params.pageLength;
	if(params.sort && $.defined(params.sort))options['order'] = [[params.sort,params.sortDir]];
	else options['order'] = [[0,"desc"]];
			
	table = el.DataTable(options);	
	el.addClass("init");
}


function multiselectInit(){
    if (typeof $.fn.multiselect != 'undefined' && $('.multiselect:not(.init)').length){
		var opts = new Array('buttonClass','dropRight','selectedClass','buttonWidth','buttonContainer','maxHeight','checkboxName','includeSelectAllOption','includeSelectAllIfMoreThan','selectAllText','selectAllValue','enableFiltering','enableCaseInsensitiveFiltering','filterPlaceholder','filterBehavior','preventInputChangeEvent','nonSelectedText','nSelectedText','numberDisplayed','disableIfEmpty');
		$.each($('select.multiselect:not(.init)'),function(){			
			var options = {};
			var el = $(this);
                        
			$.each(opts,function(n,o){ 
				var v = el.attr('data-'+o.toLocaleLowerCase());					
				if($.defined(v)){					
					options[o] = v; 
				}
			});			
			el.multiselect(options).addClass('init');
		});				
	}
}

function dataTableInit(el){
	$.fn.dataTableExt.sErrMode = 'throw';
	var tables = new Array(); 
	if(!$.defined(el))tables=$("table.dataTable:not(.init)");
	else tables.push(el);
	
	var opts = new Array('bLengthChange','bFilter','bInfo','pagingType','iDisplayLength');		
	var colopts = new Array('bSortable','bVisible','iDataSort','sType','sSortDataType');		
    $.each(tables, function(){
    	var options = {};
        el = $(this); 
        
        $.each(opts,function(n,o){ 
			var v = el.attr('data-'+o.toLocaleLowerCase());					
			if($.defined(v)){
				if(v=="false")v=false;
				if(v=="true")v=true;
				options[o] = v; 
			}
		});	 

		if(el.find("[data-default-sort]").exists()){
			var col = el.find("[data-default-sort]");			
			options['order'] = [[col.index(),col.attr('data-default-sort')]];
		}
		
		options['aoColumns'] = new Array();
		$.each(el.find('thead th'),function(){			
			var col = $(this);
			var header = {};
			
			$.each(colopts,function(n,o){ 
				var v = col.attr('data-'+o.toLocaleLowerCase());					
				if($.defined(v)){
					if(v=="false")v=false;
					if(v=="true")v=true;
					header[o] = v; 
				}
			});	 						
			options['aoColumns'].push(header);			
		});
				
		options['destroy'] = true;
		options['deferRender'] = true;
		options['lengthMenu'] = [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ];	
		
		if(el.attr('data-export') == 'true'){
			options['dom'] = 'Bfrtip';
			options['buttons'] = ['csv', 'print']; //['copy', 'csv', 'excel', 'pdf', 'print'];
		}
		
        el.addClass("init").dataTable(options);        
    });
}

function inputMasksInit(){
}