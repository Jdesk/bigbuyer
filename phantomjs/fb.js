var testindex = 0;
var wait = false;
var loadInProgress = false;//This is set to true when a page is still loading
 
/*********SETTINGS*********************/
phantom.injectJs('./jquery.min.js');
var webPage = require('webpage');
var page = webPage.create();
page.settings.userAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/44.0.2403.157 Safari/537.36';
page.settings.javascriptEnabled = true;
page.settings.loadImages = false;//Script is much faster with this field set to false
phantom.cookiesEnabled = true;
phantom.javascriptEnabled = true;

var fs = require('fs'); 
var server = 'http://199.119.163.62/AmazonBot/util/server';
var snapshotDir = '/var/www/html/AmazonBot/snapshots/';
var state = {};
var debug = {
	request: false,
	response: false,
	pause: false,
	msgs: true,
	nosleep: false,
};
var jq = {
	'ok': false,
	'retries': 0
};

var vWidth = 1080;
var vHeight = 1920; 
page.viewportSize = {
    width: vWidth ,
    height: vHeight 
};


var args = require('system').args;
try{
	var input = JSON.parse(args[1]);
}catch(e){ console.log(e); phantom.exit(); }
/*********SETTINGS END*****************/
 
console.log('All settings loaded, start with execution');
page.onConsoleMessage = function(msg) {
    console.log(msg);
};

var s = 0;
var sBase = page.evaluate(function () { return document.body.scrollHeight; });
page.scrollPosition = {
    top: sBase,
    left: 0
};
var p = 0;

/**********DEFINE STEPS THAT FANTOM SHOULD DO***********************/
steps = [          
    function init(){
    	//var pip= input.proxy.split(':');    	
    	//phantom.setProxy(pip[0],pip[1]);
    	//page.open('http://icanhazip.com/');    	
    	//page.settings.userAgent = input.useragent;    	
    	//page.open('http://www.whoishostingthis.com/tools/user-agent/');
    },
    function(){
    	//https://www.facebook.com/marketplace/directory/US/
    	
    	//page.open('https://www.facebook.com/marketplace/nyc/vehicles/');
    	page.open('https://www.facebook.com/marketplace/la/search/?query=porsche&categoryID=vehicles&radiusKM=161&vertical=C2C&sort=BEST_MATCH');
    },
    function(){
    	addJquery();
    },
    function(){
    	pause(5);
    },
    function countItems(){
    	if(p>10){
    		goToStep('doneScrolling');
    		return;
    	}
    	var count = page.evaluate(function(){ return $('._7yc._3ogd').length; });
    	console.log("Ads Count: "+count);
    },
    function scroll(){     
    	var sBase2 = page.evaluate(function () { return document.body.scrollHeight; });
    	console.log(sBase2);
    	console.log("Scrolling...");  
    	page.scrollPosition = {
               top: sBase2,
               left: 0
    	};
    	p++;
    	/*    	    	
        var sBase2 = page.evaluate(function () { return document.body.scrollHeight; });
        if (sBase2 != sBase) {
            sBase = sBase2;
        }
        
        console.log("Scrolling to: "+s);
        page.scrollPosition = {
            top: s,
            left: 0
        };
        page.viewportSize = {width: vWidth, height: s};
        s += Math.min(sBase/20,400);  
        p++;
        */
    },
    function(){
    	pause(1);
    },
    function(){
    	goToStep('countItems');
    },
    function doneScrolling(){
    	var count = page.evaluate(function(){ return $('._7yc._3ogd').length; });    		
    	
    },
    function(){
    	save();
    },
    function(){
    	phantom.exit();
    },


    
    function visit(){        
        page.open("https://amazon.com", function(status){
			
		});
    },
    
    
    
    
    //Start Jquery Inject
    function jq(){
    	jq = {
    		'ok': false,
    		'retries': 0
    	};
    	addJquery();    	
    },
    function jqWait1(){
    	pause(1);
    },
    function checkJq(){    	    	    	
    	var type = page.evaluate(function(q){ return typeof $; });
        if(type == 'undefined'){ jq.ok = false; }
        else{ jq.ok = true; }
        jq.retries++;
        
        if(!jq.ok && jq.retries < 20){
        	goToStep('jqWait1');
        }
    },
    function(){
    	if(!jq.ok){
    		save();
    	}
    },
    function(){
    	if(!jq.ok){
    		updateDB({
    			'snapshot': input.id+'.png',
    			'Error': 'Unable to inject JQuery 1',
    			'status': 'Error'
    		});
    	}
    },  
    function(){
    	if(!jq.ok){
    		phantom.exit();
    	}
    }, 
    //END Jquery Inject
    
    
	function fillSearchInput(){        
		page.evaluate(function(q){ $("#twotabsearchtextbox").val(q); },input.search_term);		
    },
    function wait(){
    	pause(getRandomFloat(2,10));
    },
    function search(){
    	page.evaluate(function(){ $('.nav-search-submit input').click(); });
    	state.page=0;
    },
    
    
    
    //Start Jquery Inject
    function pagejq(){
    	jq = {
    		'ok': false,
    		'retries': 0
    	};
    	addJquery();    	
    },
    function pagejqWait(){
    	pause(1);
    },
    function checkJq(){    	    	    	
    	var type = page.evaluate(function(q){ return typeof $; });
        if(type == 'undefined'){ jq.ok = false; }
        else{ jq.ok = true; }
        jq.retries++;
        
        if(!jq.ok && jq.retries < 20){
        	goToStep('pagejqWait');
        }
    },
    function(){
    	if(!jq.ok){
    		save();
    	}
    },
    function(){
    	if(!jq.ok){
    		updateDB({
    			'snapshot': input.id+'.png',
    			'Error': 'Unable to inject JQuery 2',
    			'status': 'Error'
    		});
    	}
    },  
    function(){
    	if(!jq.ok){
    		phantom.exit();
    	}
    }, 
    //END Jquery Inject
    
    
    function(){    	
    	save();    	
    },
    function parse(){
    	console.log("Checking Page: "+state.page);
    	findProduct();     	
    	if(state.index<0 || state.id.length<=0){ state.found=false; }
    	else{ state.found=true; }
    },
    function(){
    	console.log("FOUND "+state.found);
    	if(!state.found && state.page<10){    		    		
    		page.evaluate(function(){ $('#pagnNextString').click(); });    		
    	}
    },
    function (){
    	if(!state.found && state.page<10){
    		state.page++;
    		goToStep('pagejq');    		
    	}
    },
    

    function(){
    	if(!state.found){
    		save();
    	}
    },
    function(){
    	if(!state.found){
    		updateDB({
    			'snapshot': input.id+'.png',
    			'Error': 'Product not found',
    			'status': 'Error'
    		});
    	}
    },   
    function(){
    	if(!state.found){
    		phantom.exit();
    	}
    },   
    function wait(){
    	pause(getRandomFloat(2,60));
    },
    function goToProduct(){
    	console.log("Profuct Index: "+state.index+", ID: "+state.id);
    	page.evaluate(function(id){
    		console.log('#'+id);
    		//$('#result_'+index+' a').click();
    		document.querySelector('#'+id+' a').click();
    	},state.id);      	
    },
    function wait(){
    	pause(getRandomFloat(10,60));
    },
    function cart(){
    	page.evaluate(function(){
    		document.querySelector('#add-to-cart-button').click();    		
    	});
    },
    function wait(){
    	pause(getRandomFloat(2,30));
    },
    function snapshot(){
    	save();
    },
    function update(){
    	updateDB({
    		'snapshot': input.id+'.png',
    		'status': 'Done'
    	});
    },
    function done(){
    	
    }
];
/**********END STEPS THAT FANTOM SHOULD DO***********************/

//Execute steps one by one
interval = setInterval(executeRequestsStepByStep,50);
 
function executeRequestsStepByStep(){	
    if (loadInProgress == false && typeof steps[testindex] == "function" && !wait) {
    	console.log("step " + (testindex + 1) + ": " + functionName(steps[testindex]));
        steps[testindex]();
        testindex++;
    }
    if (typeof steps[testindex] != "function") {
        console.log("test complete!");
        phantom.exit();
    }
}
function functionName(fun) {
	var ret = fun.toString();
	ret = ret.substr('function '.length);
	ret = ret.substr(0, ret.indexOf('('));
	return ret;
}
function pause(secs){
	if(debug.nosleep){
		console.log("Sleeping is OFF");
		return;
	}
	console.log('Sleeping for '+secs+' seconds');
	wait = true;
	
	if(debug.pause){
		if(secs>0)
			setTimeout(function(){ pause(--secs); },1000);
		else
			wait=false;
	}
	else{
		setTimeout(function(){ wait=false },secs*1000);
	}
}
function addJquery(){	
	wait = true;
	page.injectJs('jquery.min.js');
	setTimeout(function(){wait = false;},500);
	
	/*
	page.evaluate(function(){
		javascript:(function(){function l(u,i){var d=document;if(!d.getElementById(i)){var s=d.createElement('script');s.src=u;s.id=i;d.body.appendChild(s);}}l('https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js','jquery')})();
	});
	*/
}
function save(){	
	page.render('test.png');
	var result = page.evaluate(function() {
		return document.querySelectorAll("html")[0].outerHTML;
	});
	result = page.content;
	fs.write('test.html',result,'w');
}
function updateDB(data){
	var dfd = $.Deferred();
	
	data['id'] = input.id;
		
	loadInProgress = true;
	var postdata = new Array();
	for(var k in data)postdata.push(k+'='+data[k]);	
	postdata = postdata.join('&');
		
	console.log("Updating DB");
	var page = webPage.create();
	page.open(server, 'post', postdata, function (status) {
		console.log(page.content);
		loadInProgress = false;
		dfd.resolve(true);
		console.log("Done updating DB");
	});
	return dfd.promise();
} 
function getRandomFloat(min, max) {
  return Math.random() * (max - min) + min;
}
function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min + 1) + min);
}
function findProduct(){
	var res = page.evaluate(function(target,debug){
		var index = -1;
		var id = '';
		var items = $('#resultsCol .s-result-item');
		$.each(items,function(n){
			if(debug.msgs)console.log(n);
			var el = $(this);    			
			if(el.find('.s-sponsored-list-header').length>0) return;
			
			var title = el.find('.s-access-title').text();
			if(debug.msgs)console.log(title);
			if(debug.msgs)console.log(target);
			if(debug.msgs)console.log("--------------");
			//if(title.trim() == target.trim()){    				
			if(target.trim().indexOf(title.trim().replace(/[^\x00-\x7F]$/,''))>=0 || title.trim() == target.trim()){
				index = n;
				id = el.attr('id'); 
				$('html, body').animate({ scrollTop: el.offset().top }, 2000);
				return false;
			}    			
		}); 
		return {index: index, id: id};
	},input.product,debug);
	
	state.index = res.index;
	state.id = res.id;	
}
function goToStep(name){
	for(x in steps){
		var funcName = functionName(steps[x]);		
		if(funcName == name){
			console.log("Going back to: Step "+x+", "+funcName);
			testindex = x-1;
			return false;
		}
	}
}

/**
 * These listeners are very important in order to phantom work properly. Using these listeners, we control loadInProgress marker which controls, weather a page is fully loaded.
 * Without this, we will get content of the page, even a page is not fully loaded.
 */
page.onResourceRequested = function(request) {
	if(debug.request)
		console.log('Request ' + JSON.stringify(request, undefined, 4));
};
page.onResourceReceived = function(response) {
	if(debug.response)
		console.log('Response ' + JSON.stringify(response, undefined, 4));
};
page.onLoadStarted = function() {
    loadInProgress = true;
    console.log('Loading started');
};
page.onLoadFinished = function() {
    loadInProgress = false;
    console.log('Loading finished');
};
page.onConsoleMessage = function(msg) {
    console.log(msg);
}
page.onError = function(msg, trace) {
	var msgStack = ['PHANTOM ERROR: ' + msg];
	if (trace && trace.length) {
	    msgStack.push('TRACE:');
	    trace.forEach(function(t) {
	      msgStack.push(' -> ' + (t.file || t.sourceURL) + ': ' + t.line + (t.function ? ' (in function ' + t.function +')' : ''));
	    });
	}
	console.error(msgStack.join('\n'));
	
	
	wait = true;
	$.when( 			
		updateDB({
			'snapshot': input.id+'.png',
			'Error': msgStack.join('\n'),
			'status': 'Error'
		})
	).done(function(){ phantom.exit(); });	
	
};
