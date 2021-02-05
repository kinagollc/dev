var translator;
var data_tables;
var ajax_request = {};
var timer = {};

jQuery.fn.exists = function(){return this.length>0;}

dump = function(data) {
	console.debug(data);
};

dump2 = function(data) {
	alert(JSON.stringify(data));	
};

$( document ).on( "keyup", ".numeric_only", function() {
  this.value = this.value.replace(/[^0-9\.]/g,'');
});	 

loader = function(is_loading){
	if(is_loading==1){		
		$(".content_wrap").loading({
			message : translator.get("loading"),
			zIndex: 999999999,
		});
	} else {
		$(".content_wrap").loading('stop');
	}
};

empty = function(data){	
	if (typeof data === "undefined" || data==null || data=="" || data=="null" || data=="undefined" ) {	
		return true;
	}
	return false;
};

t = function(words){
	return translator.get(words);
};

notify = function(message, alert_type ){
	
	if(empty(alert_type)){
		alert_type='success';
	}
	
	notify_icon = '';
	
	switch(alert_type)
	{
		case "success":
		notify_icon = 'fa fa-check-circle';
		break;
		
		case "danger":
		notify_icon = 'fa fa-bell-o';
		break;
	}
	
	$.notify({
		//icon: 'fa fa-check-circle',
		icon: notify_icon ,
		message: message,		
	},{
		type: alert_type ,		
		placement: {
		  from: "top",
		  align: "center"
	    },
	    animate:{
			enter: "animated fadeInUp",
			exit: "animated fadeOutDown"
		},
		delay : 1000*notify_delay,
		showProgressbar : true,		
		z_index: 9999999,
	});
};

jQuery(document).ready(function() {
		
	translator = $('body').translate({lang: lang , t: dict}); 	
	
	$('.menu_nav a').webuiPopover({
		trigger:'hover',
		placement:'right',
		animation :"pop"
	});	
	
	$( document ).on( "click", ".copy_text", function() {
		$(this).focus();
		$(this).select();
		document.execCommand('copy');
		notify( t("copy to clipboard") );			
	});
	
	$( document ).on( "click", ".show_password", function() {
		togle = $(this).data("togle");
		if(togle==1){
			$(this).text( translator.get("hide") );
			$(".show_password_field").attr("type","text");
			$(this).data("togle",2);			
		} else {
			$(this).text( translator.get("show") );
			$(".show_password_field").attr("type","password");
			$(this).data("togle",1);
		}
	});
	
	if ( $(".select2").exists() ){
		 $('.select2').select2({
		 	 placeholder: t('Select an option')		 	 
		 });
	}
	
	if ( $(".country_list_select2").exists() ){
		 $('.country_list_select2').select2({
		 	  placeholder: t("Select a country"),
			  templateResult: formatCountry,              
		 });
	}
	
	if( $("#upload_services_json").exists() ){
		init_file_upload('upload_services_json','merchantapp_services_account_json');		
	}
	
	 /*SEARCH CODE*/
   	$( document ).on( "click", ".a_search", function() {
   		$(this).hide();
   		$(".table_search_wrap .search_inner").show();
   		$("#search_fields").focus();
   	});
   	$( document ).on( "click", ".a_close", function() {   		   		
   		$(".table_search_wrap .search_inner").hide();
   		$(".a_search").show();
   		$("#search_fields").val('');
   		data_tables.destroy(); data_tables.clear(); initTable();
   	});
   	
   	$( document ).on( "click", ".refresh_datatables", function() {			
		$('.data_tables').DataTable().ajax.reload();
	});			
   	
   	$("#frm_table").validate({
   	    submitHandler: function(form) {
   	    	 data_tables.destroy();  
   	    	 data_tables.clear(); 
   	    	 initTable();   	    	 
		},
   	});		
   	/*SEARCH CODE*/
   	
   	if ( $("#table_list").exists() ) {
       initTable();
   }
   
   $( document ).on( "click", ".show_device_id", function() {   		   		
   		$(".device_details").html( $(this).data("id") );
   	});
   	
   	$( document ).on( "click", ".send_push", function() {
   		id = $(this).data("id");   		
   		$('#sendPushModal').modal('show');   		
   		setTimeout(function(){ 
   			$("#id").val( id );
   		}, 100);
   	});   	 
   	
   	$('#broadcastNewModal,#pageNewModal,#sendPushModal').on('show.bs.modal', function (e) {
   		dump('show.bs.modal');
   		
   		$("#page_id").val('');
   		
   		clear_forms("#frm_ajax");
   		var validator = $( "#frm_ajax" ).validate();
        validator.resetForm();        
   	});   	
   	
   	$("#frm_ajax").validate({
   	    submitHandler: function(form) {
   	    	 action = $("#frm_ajax").data("action");
   	    	 processAjax( action , $("#frm_ajax").serialize() , 'POST' );
		}
   	});
   	
   	$( document ).on( "click", ".show_error_details", function() {   		
   		$("#details_id").val( $(this).data("id") );
   	});   	
   	
   	$('#errorDetails').on('shown.bs.modal', function (e) {
   		details_id = $("#details_id").val();   	
   		processAjax("errorDetails","details_id="+ details_id  + "&current_page="+ current_page );   			
   	});
   	$('#errorDetails').on('show.bs.modal', function (e) {
   		$(".error_details").html('');
   	});
   	
   	$('#broadcastNewModal').on('show.bs.modal', function (e) {   		
   		$('.ajax_merchant_list').select2({
   	     language: {
	        searching: function() {
	            return t("Searching...");
	        },
	        noResults: function (params) {
		      return t("No results");
		    }
	     },
		  ajax: {
		  	delay: 250,
		    url: ajaxurl+"/merchant_list",
		    data: function (params) {
		      var query = {
		        search: params.term		       
		      }			     
		      return query;
		    }
		  }
		});
   	});  
   	
   	
   	if( $(".order_estimated_time").exists() ){
	   	$('[name=order_estimated_time]').tagify().on('add', function(e, tagName){
	        //console.log('added', tagName)
	    });
   	}
   	
   	if( $(".decline_reason_list").exists() ){
	   	$('[name=decline_reason_list]').tagify().on('add', function(e, tagName){
	        //console.log('added', tagName)
	    });
   	}
	
});
/*END DOCU*/

clear_forms = function(ele) {	
    $(ele).find(':input').each(function() {						    	
        switch(this.type) {
            case 'password':            
            case 'text':
            case 'textarea':
                $(this).val('');
                break;
            case 'checkbox':
            case 'radio':
                this.checked = false;            
            
        }
   });   
}

function formatCountry (country) {
  if (!country.id) { return country.text; }
  var $country = $(
    '<span class="flag-icon flag-icon-'+ country.id.toLowerCase() +' flag-icon-squared"></span>' +
    '<span class="flag-text">'+ country.text+"</span>"
  );
  return $country;
};
	
var getTimeNow = function(){
	var d = new Date();
    var n = d.getTime(); 
    return n;
};	

addValidationRequest = function(){
	var params='';		
	params+="&YII_CSRF_TOKEN="+YII_CSRF_TOKEN;
	return params;
}
	
/*mycall*/
var processAjax = function(action , data , single_call, silent, method ){
	
	timenow = getTimeNow();
	if(!empty(single_call)){			
		var timenow = single_call;
	}	
	
	if(empty(method)){
		method="POST";
		data+=addValidationRequest();		
	} 
		
	ajax_request[timenow] = $.ajax({
	  url: ajaxurl+"/"+action,
	  method: method,
	  data: data ,
	  dataType: "json",
	  timeout: 20000,
	  crossDomain: true,
	  beforeSend: function( xhr ) {   
	  	 if ((typeof silent !== "undefined") && (silent !== null)) {	  	    
	  	 } else {
	  	 	loader(1); 
	  	 }
         if(ajax_request[timenow] != null) {	
         	dump("request aborted");     
         	ajax_request[timenow].abort();
            clearTimeout( timer[timenow] );
         } else {         	
         	timer[timenow] = setTimeout(function() {				
				ajax_request[timenow].abort();
				notify( t('Request taking lot of time. Please try again') );
	        }, 20000); 
         }
      }
    });
    
    ajax_request[timenow].done(function( data ) {
	     dump('done');	
	     next_action='';     
	     if(!empty(data.details.next_action)){
	        next_action = data.details.next_action;
	     }
	     if (data.code==1){
	     	switch(next_action){
	     		case "close_send_push_modal":
	     		  $('#sendPushModal').modal('hide')   
	     		  notify(data.msg,'success');
	     		break;
	     		
	     		case "close_broadcast_modal":
	     		  $('#broadcastNewModal').modal('hide')   
	     		  notify(data.msg,'success');
	     		  $('.data_tables').DataTable().ajax.reload();
	     		break;
	     		
	     		case "set_push_status":
	     		  $(".error_details").html( data.msg );
	     		break;
	     		
	     		default:
	     		  notify(data.msg,'success');
	     		break;
	     	}
	     } else {
	     	notify(data.msg,'danger');
	     }
	});    	
	/*end done*/
	
	/*ALWAYS*/
    ajax_request[timenow].always(function() {
    	loader(2);
        dump("ajax always");
        ajax_request[timenow]=null;  
        clearTimeout(timer[timenow]);
    });
    
    /*FAIL*/
    ajax_request[timenow].fail(function( jqXHR, textStatus ) {    	
    	clearTimeout(timer[timenow]);
        notify( t("Failed") + ": " + textStatus ,'danger' );        
    });  
    
};
/*end mycall*/

init_file_upload = function(id,field_name){
				
	uploader = new ss.SimpleUpload({
		 button: id ,
		 url: ajaxurl + "/uploadFile2?id="+id +"&field_name="+field_name ,		
		 name: 'uploadfile',			 	
		 responseType: 'json',			 
		 allowedExtensions: ['json'],			 
		 maxSize: image_limit_size,
		 onExtError: function(filename,extension ){
		 	loader(2);
		    notify(  translator.get("invalid_file_extension") ,'danger');
	     },
	     onSizeError: function (filename,fileSize){ 
	     	loader(2);
		    notify(  translator.get("invalid_file_size") ,'danger');
	     },    
		 onSubmit: function(filename, extension) {				 			 	
		 	loader(1);
		 },
		 onComplete: function(filename, response) {			 	 
		 	 loader(2);		 	 		 	 
		 	 if(response.code==1){		 	 			 	 
		 	 	parent = $("#"+id).parent();
		 	 	parent.after( response.details.input );		
		 	 	$("."+response.details.field_name).remove();		 	 	
		 	 } else {
		 	 	notify(response.msg,'danger');
		 	 }
		 }
	});
};

function initTable()
{		
	$.fn.dataTable.ext.errMode = 'none';
	var frm_table = $("#frm_table").serializeArray();	
	var extra_data = {};
	$.each(frm_table,function(i, v) {
        extra_data[v.name] = v.value;
    });
			
	data_tables = $('.data_tables').on('preXhr.dt', function ( e, settings, data ) {
        dump('loading');        
        $(".refresh_datatables").html( t("Loading...") + '&nbsp;<ion-icon name="refresh"></ion-icon>' );
     }).on('xhr.dt', function ( e, settings, json, xhr ) {
     	dump('done');     	
     	$(".refresh_datatables").html( t("Refresh") + '&nbsp;<ion-icon name="refresh"></ion-icon>' );
     	$(".dataTables_processing").hide();
     }).on( 'error.dt', function ( e, settings, techNote, message ) {
     	notify( "an error has occured" + ": " + message,'danger' );
     }).DataTable( {
     	"aaSorting": [[ 0, "DESC" ]],	
        "processing": true,
        "serverSide": true,
        "bFilter":false, 
         "dom": "<'row'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6'f>>" +
	                "<'row'<'col-sm-12'tr>>" +
	               "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        "pageLength": 10,                          
         "ajax": {
		    "url": ajaxurl+"/"+action_name,
		     "type": "POST",
		    "data": extra_data
		},
        language: {
	        url: ajaxurl+"/datable_localize"
	    }
     });
	   		
}