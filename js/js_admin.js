
/* =====================================================================================
*
*  Display a popup with all the information on which files has changed
*
*/

function showSvnPopup(md5, plugin, url, version1, version2) {
	jQuery("#wait_popup_"+md5).show();
	var arguments = {
		action: 'svn_show_popup', 
		url : url,
		version1 : version1,
		version2 : version2,
		plugin : plugin
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery('body').append(response);
		jQuery("#wait_popup_"+md5).hide();
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery('body').append("Error 500: The ajax request is retried");
			showSvnPopup(md5, plugin, url, version1, version2) ; 
		} else {
			jQuery('body').append("Error "+x.status+": No data retrieved");
		}
	});
}

/* =====================================================================================
*
*  Launch the execution of either the update or the checkout of the SVN repository
*
*/

function svn_to_repo(plugin, random, version) {
	
	jQuery("#confirm_to_svn1").hide() ; 
	jQuery("#confirm_to_svn2").hide() ; 
	
	jQuery('.toModify'+random).attr('disabled', true);
	jQuery('.toPut'+random).attr('disabled', true);
	jQuery('.toDelete'+random).attr('disabled', true);
	jQuery('.toPutFolder'+random).attr('disabled', true);
	jQuery('.toDeleteFolder'+random).attr('disabled', true);
	
	list = new Array() ; 	
	
	if (version!='') {
		list.push(new Array(version, 'create_branch')) ; 
	}

	tick = jQuery('.toModify'+random) ; 
	for (var i=0 ; i<tick.length ; i++) {
		if (tick.eq(i).attr('checked')=='checked') {
			list.push(new Array(tick.eq(i).val(), 'modify')) ; 
		}
	}
	tick = jQuery('.toPut'+random) ; 
	for (var i=0 ; i<tick.length ; i++) {
		if (tick.eq(i).attr('checked')=='checked') {
			list.push(new Array(tick.eq(i).val(), 'add')) ; 
		}
	}
	tick = jQuery('.toDelete'+random) ; 
	for (var i=0 ; i<tick.length ; i++) {
		if (tick.eq(i).attr('checked')=='checked') {
			list.push(new Array(tick.eq(i).val(), 'delete')) ; 
		}
	}
	tick = jQuery('.toPutFolder'+random) ; 
	for (var i=0 ; i<tick.length ; i++) {
		if (tick.eq(i).attr('checked')=='checked') {
			list.push(new Array(tick.eq(i).val(), 'add_folder')) ; 
		}
	}
	tick = jQuery('.toDeleteFolder'+random) ; 
	for (var i=0 ; i<tick.length ; i++) {
		if (tick.eq(i).attr('checked')=='checked') {
			list.push(new Array(tick.eq(i).val(), 'delete_folder')) ; 
		}
	}
	
	jQuery("#wait_svn1").show();
	
	arguments = {
		action: 'svn_to_repo', 
		plugin: plugin, 
		comment: jQuery("#svn_comment").val(), 
		files: list
	} 

	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_svn").hide();
		jQuery("#console_svn").html(response);
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery("#console_svn").html("Error 500: The ajax request is retried");
			svnExecute(sens, plugin, random)  ;
		} else {
			jQuery("#console_svn").html("Error "+x.status+": No data retrieved");
		}
	}); 
		
}

function repoToSvn(plugin) {
	jQuery("#wait_svn").show();
	jQuery("#svn_button").remove() ;
		
	var arguments = {
		action: 'repo_to_svn', 
		plugin : plugin, 
		comment : jQuery("#svn_comment").val()
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		if (response=="error")
			repoToSvn(plugin) ; 
		else 
			jQuery("#svn_div").html(response);
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery("#svn_div").html("Error 500: The ajax request is retried");
			repoToSvn(plugin) ; 
		} else {
			jQuery("#svn_div").html("Error "+x.status+": No data retrieved");
		}
	});    
}



/* =====================================================================================
*
*  Get the plugin Info
*
*/

function pluginInfo(id_div, url, plugin_name) {
	
	//POST the data and append the results to the results div
	rand = Math.floor(Math.random()*3000) ; 
	window.setTimeout(function() {
		var arguments = {
			action: 'pluginInfo', 
			plugin_name : plugin_name, 
			url : url
		} 
		
		jQuery('#'+id_div).show() ; 
		
		jQuery.post(ajaxurl, arguments, function(response) {
			if (response=="error")
				pluginInfo(id_div, url, plugin_name) ; 
			else 
				jQuery('#'+id_div).html(response);
		}).error(function(x,e) { 
			if (x.status==0){
				//Offline
			} else if (x.status==500){
				jQuery('#'+id_div).html("Error 500: The ajax request is retried");
				pluginInfo(id_div, url, plugin_name) ; 
			} else {
				jQuery('#'+id_div).html("Error "+x.status+": No data retrieved");
			}
		});
		
	}, rand) ; 
}

/* =====================================================================================
*
*  Show diff in file
*
*/

function showTextDiff(md5, file1, file2) {
	
	//POST the data and append the results to the results div
	
	var arguments = {
		action: 'showTextDiff', 
		file1 : file1, 
		file2 : file2
	} 
	
	jQuery('#wait_diff_'+md5).show() ; 
	
	jQuery.post(ajaxurl, arguments, function(response) {
		if (response=="error")
			showDiff(md5, file1, file2) ; 
		else 
			jQuery('#diff_'+md5).html(response);
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery('#diff_'+md5).html("Error 500: The ajax request is retried");
			showDiff(md5, file1, file2) ; 
		} else {
			jQuery('#diff_'+md5).html("Error "+x.status+": No data retrieved");
		}
	});
		
}

/* =====================================================================================
*
*  Get the core Info
*
*/

function coreInfo(id_div, url, plugin_name) {
	
	//POST the data and append the results to the results div
	rand = Math.floor(Math.random()*3000) ; 
	window.setTimeout(function() {
		var arguments = {
			action: 'coreInfo', 
			plugin_name : plugin_name, 
			url : url
		} 
		
		jQuery('#'+id_div).show() ; 
		
		jQuery.post(ajaxurl, arguments, function(response) {
			if (response=="error")
				coreInfo(id_div, url, plugin_name) ; 
			else 
				jQuery('#'+id_div).html(response);
		}).error(function(x,e) { 
			if (x.status==0){
				//Offline
			} else if (x.status==500){
				jQuery('#corePlugin_'+md5).html("Error 500: The ajax request is retried");
				coreInfo(id_div, url, plugin_name) ; 
			} else {
				jQuery('#'+id_div).html("Error "+x.status+": No data retrieved");
			}
		});
		
	}, rand) ; 
}

/* =====================================================================================
*
*  Update the core
*
*/

function coreUpdate(id_div, id_div2, url, plugin_name) {
	
	var arguments = {
		action: 'coreUpdate', 
		plugin_name : plugin_name, 
		url : url
	} 
	
	jQuery('#'+id_div).show() ; 
	
	jQuery.post(ajaxurl, arguments, function(response) {
		if (response=="error")
			coreUpdate(id_div, url, plugin_name) ; 
		else 
			jQuery('#'+id_div2).html(response);
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery('#'+id_div).html("Error 500: The ajax request is retried");
			coreUpdate(id_div, url, plugin_name) ; 
		} else {
			jQuery('#'+id_div).html("Error "+x.status+": No data retrieved");
		}
	});
	
	return false ; 
}


/* =====================================================================================
*
*  Change the version of the plugin
*
*/

function changeVersionReadme(md5, url, plugin) {
	
	jQuery("#wait_changeVersionReadme_"+md5).show();
	
	var arguments = {
		action: 'changeVersionReadme', 
		url: url, 
		plugin : plugin
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery('body').append(response);
		jQuery("#wait_changeVersionReadme_"+md5).hide();
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery('body').append("Error 500: The ajax request is retried");
			changeVersionReadme(md5, url, plugin) ; 
		} else {
			jQuery('body').append("Error "+x.status+": No data retrieved");
		}
	});
}


/* =====================================================================================
*
*  Save the version and the readme txt
*
*/

function saveVersionReadme(plugin, url) {
	jQuery("#wait_save").show();
	readmetext = jQuery("#ReadmeModify").val() ; 
	versiontext = jQuery("#versionNumberModify").val() ; 
	var arguments = {
		action: 'saveVersionReadme', 
		readme : readmetext, 
		url : url,
		plugin : plugin,
		version : versiontext
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery('#readmeVersion').html(response);
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery('#readmeVersion').html("Error 500: The ajax request is retried");
			saveVersionReadme(plugin) ;
		} else {
			jQuery('#readmeVersion').html("Error "+x.status+": No data retrieved");
		}
	});
}

/* =====================================================================================
*
*  Save todoList
*
*/

function saveTodo(md5, plugin) {
	jQuery("#wait_savetodo_"+md5).show();
	jQuery("#savedtodo_"+md5).hide();
	textTodo = jQuery("#txt_savetodo_"+md5).val() ; 
	
	var arguments = {
		action: 'saveTodo', 
		textTodo: textTodo, 
		plugin : plugin
	} 
	//POST the data and append the results to the results div
	jQuery.post(ajaxurl, arguments, function(response) {
		jQuery("#wait_savetodo_"+md5).hide();
		if (response!="ok") {
			jQuery("#errortodo_"+md5).html(response);
		} else {
			jQuery("#savedtodo_"+md5).show();
			jQuery("#errortodo_"+md5).html("");
		}
	}).error(function(x,e) { 
		if (x.status==0){
			//Offline
		} else if (x.status==500){
			jQuery("#errortodo_"+md5).html("Error 500: The ajax request is retried");
			saveTodo(md5, plugin) ;  
		} else {
			jQuery("#errortodo_"+md5).html("Error "+x.status+": No data retrieved");
		}
	});
}

