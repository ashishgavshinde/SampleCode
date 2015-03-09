// JavaScript Document
var isPageTitleExists = true;
var isUrlKeyExists = false;
$(document).ready(function(){
	//messages hide after some time
	setTimeout(function () {
		$(".alert").fadeOut();
	},3000);

//$('#page_name').alphanumeric({allow:"-"});
$('#url_key').alphanumeric({allow:"-"});
 
$('#page_name').focus();

	jQuery('#cmspage').validate(
	{
		rules:{
			page_name:{
				required:true
			},
			url_key:{
				required:true
			},
			status:{
				required:true
			}
			
		},
		messages:{
			page_name:{
				required:'Please enter page name'
			},
			url_key:{
				required:'Please enter page url'
			},
			status:{
				required:'Please select status'
			}
		}
	});

	$("#page_name").blur(function(){
		var page_id = $('#page_id').val();
		var pageUrl = '';
		var pageName = $(this).val();
		pageName=encodeURIComponent(pageName);
		var parameter = '';
			if(page_id!='' && page_id!=undefined){
				parameter = 'pId='+page_id+'&pageTitle='+pageName;
			}else{
				parameter = 'pageTitle='+pageName;
			}
			jQuery.ajax({
				type:'get',
				data: parameter,
				url:baseUrl+'/cmspage/checkunique',
				success:function(responseText)
				{
					if(responseText==1){
						$('#pageNameExists').show();
						isPageTitleExists = false;
						$('#submitbutton').attr('disabled','disabled');
					}else{
						$('#pageNameExists').hide();
						isPageTitleExists = true;
						setPageUrl();
						$('#url_key').trigger('blur');
						$('#submitbutton').removeAttr('disabled');
					}
				}
			});
			
	});


//to reset page exist error message when user changes text of field
	$("#page_name").focus(function(){
		$('#pageNameExists').hide();
	});

//function for check url_key already exist or not
	$('#url_key').blur(function(){
		string = $('#url_key').val();
		if(string!=''){
			string = string.replace(/\s+/g, '-');
			string = string.toLowerCase();
			$('#url_key').val(string);
		}
		var page_id = $('#page_id').val();
		
		var pageUrl = '';
		var flag = '';
		if(page_id){
			parameter = 'urlKey='+$('#url_key').val()+'&pId='+page_id;
		}else{
			parameter = 'urlKey='+$('#url_key').val();
		}
		jQuery.ajax({
			type:'get',
			data: parameter,
			url:baseUrl+'/cmspage/checkurlkey',
			success:function(responseText)
			{
				if(responseText==1){
					$('#urlKeyExists').show();
					isUrlKeyExists =  false;
					
				}else{
					$('#urlKeyExists').hide();
					isUrlKeyExists =  true;
				}
			}
		});
	});
	
	$("#cmspage").submit(function(){
		
		var format=true;
		
	//$("#page_content_error").show();
	var content = tinyMCE.get('content').getContent();
			if(content == "" || content == null){
				$("#page_content_error").show();
				format=false;
			}
			else{
				$("#page_content_error").hide();
				format=true;
			}
			if($("#cmspage").valid()){
				if(format){
				$("#page_content_error").hide();
					var page_id = $('#page_id').val();
					var pageName = $('#page_name').val();
					pageName = encodeURIComponent(pageName);
					var parameter = '';
					if(page_id!='' && page_id!=undefined){
						parameter = 'pId='+page_id+'&pageTitle='+pageName;
					}else{
						parameter = 'pageTitle='+pageName;
					}
					jQuery.ajax({
						type:'get',
						data: parameter,
						url:baseUrl+'/cmspage/checkunique',
						success:function(responseText)
						{
							if(responseText==1){
								$('#pageNameExists').show();
								isPageTitleExists = false;
							}else{
								$('#pageNameExists').hide();
								isPageTitleExists = true;

								//Code for check url key
									string = $('#url_key').val();
									if(string!=''){
										string = string.replace(/\s+/g, '-');
										string = string.toLowerCase();
										$('#url_key').val(string);
									}
									var page_id = $('#page_id').val();
									var pageUrl = '';
									var flag = '';
									jQuery.ajax({
										type:'get',
										data:'urlKey='+$('#url_key').val(),
										url:baseUrl+'/cmspage/checkurlkey',
										success:function(responseText)
										{
											if(responseText==1){
												$('#urlKeyExists').show();
												isUrlKeyExists =  false;
												
											}else{
												$('#urlKeyExists').hide();
											}
										}
									});

							}
						}
					});
				}else{
					return false;
				}
			}
	});
});

//Function for Add CMS form

function isFormValid(){
	if(isPageTitleExists && isUrlKeyExists){
	return true;
	}else{
	return false;
	}
}
function setPageUrl(){
	var pageTitle = $("#page_name").val();

	var string = $.trim(pageTitle);
	string = string.replace(/[^a-zA-Z0-9]/g,'-');
	string = string.replace(/-+/g, '-');

	var lastchar = string.slice(-1);
	if(lastchar=='-'){
		string = string.slice(0,-1);	
	}
	string = string.toLowerCase();
	$('#url_key').val(string);
}
