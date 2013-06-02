<?php
/**
Plugin Name: Dev Toolbox
Plugin Tag: dev, prod, development, production, svn
Description: <p>Every thing you need to efficiently develop a fresh plugin. </p><p>The different features is: </p><ul><li>Creation tool for creating a new fresh plugin without difficulties, </li><li>SVN client for uploading your plugin into Wordpress repository, </li><li>An interface to push plugins and data from your dev site to your production site (and vice versa). </li><li>Show all messages/errors/warning/notices raised by your plugins in the admin panel. </li><li>Automatic import of sent translations. </li></ul><p>This plugin is under GPL licence. </p>
Version: 1.0.3
Framework: SL_Framework
Author: SedLex
Author Email: sedlex@sedlex.fr
Framework Email: sedlex@sedlex.fr
Author URI: http://www.sedlex.fr/
Plugin URI: http://wordpress.org/plugins/dev-toolbox/
License: GPL3
*/

//Including the framework in order to make the plugin work


require_once('core.php') ; 

require_once('include/deprecated.class.php') ; 
require_once('include/svn.class.php') ; 
require_once('include/phpdoc.class.php') ; 
require_once('include/folder_diff.class.php') ; 

/** ====================================================================================================================================================
* This class has to be extended from the pluginSedLex class which is defined in the framework
*/
class dev_toolbox extends pluginSedLex {
	
	/** ====================================================================================================================================================
	* Plugin initialization
	* 
	* @return void
	*/
	static $instance = false;

	protected function _init() {
		global $wpdb ; 

		// Name of the plugin (Please modify)
		$this->pluginName = 'Dev Toolbox' ; 
		
		// The structure of the SQL table if needed (for instance, 'id_post mediumint(9) NOT NULL, short_url TEXT DEFAULT '', UNIQUE KEY id_post (id_post)') 
		$this->tableSQL = "" ; 
		// The name of the SQL table (Do no modify except if you know what you do)
		$this->table_name = $wpdb->prefix . "pluginSL_" . get_class() ; 

		//Initilisation of plugin variables if needed (Please modify)
		$this->your_var1 = 1 ; 
		$this->your_var2 = array() ; 
		$this->your_varn = "n" ; 

		//Configuration of callbacks, shortcode, ... (Please modify)
		// For instance, see 
		//	- add_shortcode (http://codex.wordpress.org/Function_Reference/add_shortcode)
		//	- add_action 
		//		- http://codex.wordpress.org/Function_Reference/add_action
		//		- http://codex.wordpress.org/Plugin_API/Action_Reference
		//	- add_filter 
		//		- http://codex.wordpress.org/Function_Reference/add_filter
		//		- http://codex.wordpress.org/Plugin_API/Filter_Reference
		// Be aware that the second argument should be of the form of array($this,"the_function")
		// For instance add_action( "wp_ajax_foo",  array($this,"bar")) : this function will call the method 'bar' when the ajax action 'foo' is called

		// We add an ajax call for SVN
		add_action('wp_ajax_svn_show_popup', array($this,'svn_show_popup')) ; 
		add_action('wp_ajax_svn_compare', array($this,'svn_compare')) ; 
		add_action('wp_ajax_svn_to_repo', array($this,'svn_to_repo')) ; 
		add_action('wp_ajax_svn_to_local', array($this,'svn_to_local')) ; 
		add_action('wp_ajax_svn_merge', array($this,'svn_merge')) ; 
		add_action('wp_ajax_svn_put_file_in_repo', array($this,'svn_put_file_in_repo')) ; 
		add_action('wp_ajax_svn_put_folder_in_repo', array($this,'svn_put_folder_in_repo')) ; 
		add_action('wp_ajax_svn_delete_in_repo', array($this,'svn_delete_in_repo')) ; 
		
		// We add an ajax call for Todo Change
		add_action('wp_ajax_saveTodo', array($this,'saveTodo')) ; 
		
		// We show the diff in file
		add_action('wp_ajax_showTextDiff', array($this,'showTextDiff')) ; 
		
		// We add an ajax call for the Readme changes
		add_action('wp_ajax_changeVersionReadme', array($this,'changeVersionReadme')) ; 
		add_action('wp_ajax_saveVersionReadme', array($this,'saveVersionReadme')) ; 
														
		// We add ajax call for enhancing the performance of the information page
		add_action('wp_ajax_pluginInfo', array($this,'pluginInfo')) ; 
		add_action('wp_ajax_coreInfo', array($this,'coreInfo')) ; 
		add_action('wp_ajax_coreUpdate', array($this,'coreUpdate')) ; 
	
		// deprecated
		add_action( 'deprecated_function_run',  array( 'deprecatedSL', 'log_function' ), 10, 3 );
		add_action( 'deprecated_file_included', array( 'deprecatedSL', 'log_file' ), 10, 4 );
		add_action( 'deprecated_argument_run',  array( 'deprecatedSL', 'log_argument' ), 10, 4 );
		add_action( 'doing_it_wrong_run',       array( 'deprecatedSL', 'log_wrong' ), 10, 3 );
		add_action( 'deprecated_hook_used',     array( 'deprecatedSL', 'log_hook' ), 10, 4 );
		add_action('admin_notices', array($this, 'admin_notice'));
		
		// Translation
		add_action('wp_ajax_seeTranslation', array($this,'seeTranslation')) ; 
		add_action('wp_ajax_deleteTranslation', array($this,'deleteTranslation')) ; 
		add_action('wp_ajax_mergeTranslationDifferences', array($this,'mergeTranslationDifferences')) ; 

		// Important variables initialisation (Do not modify)
		$this->path = __FILE__ ; 
		$this->pluginID = get_class() ; 
		
		// activation and deactivation functions (Do not modify)
		register_activation_hook(__FILE__, array($this,'install'));
		register_deactivation_hook(__FILE__, array($this,'deactivate'));
		register_uninstall_hook(__FILE__, array('dev_toolbox','uninstall_removedata'));
	}
	
	/** ====================================================================================================================================================
	* In order to display notices if any
	* This function is not supposed to be called from your plugin : it is a purely internal function 
	*  
	* @access private
	* @return void
	*/
	
	public function admin_notice () {
		deprecatedSL::show_front() ; 
	}

	/** ====================================================================================================================================================
	* In order to uninstall the plugin, few things are to be done ... 
	* (do not modify this function)
	* 
	* @return void
	*/
	
	static public function uninstall_removedata () {
		global $wpdb ;
		// DELETE OPTIONS
		delete_option('dev_toolbox'.'_options') ;
		if (is_multisite()) {
			delete_site_option('dev_toolbox'.'_options') ;
		}
		
		// DELETE SQL
		if (function_exists('is_multisite') && is_multisite()){
			$old_blog = $wpdb->blogid;
			$old_prefix = $wpdb->prefix ; 
			// Get all blog ids
			$blogids = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM ".$wpdb->blogs));
			foreach ($blogids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query("DROP TABLE ".str_replace($old_prefix, $wpdb->prefix, $wpdb->prefix . "pluginSL_" . 'dev_toolbox')) ; 
			}
			switch_to_blog($old_blog);
		} else {
			$wpdb->query("DROP TABLE ".$wpdb->prefix . "pluginSL_" . 'dev_toolbox' ) ; 
		}
	}
	
	/**====================================================================================================================================================
	* Function called when the plugin is activated
	* For instance, you can do stuff regarding the update of the format of the database if needed
	* If you do not need this function, you may delete it.
	*
	* @return void
	*/
	
	public function _update() {
		SL_Debug::log(get_class(), "Update the plugin." , 4) ; 
	}
	
	/**====================================================================================================================================================
	* Function called to return a number of notification of this plugin
	* This number will be displayed in the admin menu
	*
	* @return int the number of notifications available
	*/
	 
	public function _notify() {
		return 0 ; 
	}
	
	
	/** ====================================================================================================================================================
	* Init javascript for the public side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('dev_toolbox_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _public_js_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init css for the public side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _public_css_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init javascript for the admin side
	* If you want to load a script, please type :
	* 	<code>wp_enqueue_script( 'jsapi', 'https://www.google.com/jsapi');</code> or 
	*	<code>wp_enqueue_script('dev_toolbox_script', plugins_url('/script.js', __FILE__));</code>
	*	<code>$this->add_inline_js($js_text);</code>
	*	<code>$this->add_js($js_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_js_load() {	
		return ; 
	}
	
	/** ====================================================================================================================================================
	* Init css for the admin side
	* If you want to load a style sheet, please type :
	*	<code>$this->add_inline_css($css_text);</code>
	*	<code>$this->add_css($css_url_file);</code>
	*
	* @return void
	*/
	
	function _admin_css_load() {	
		return ; 
	}

	/** ====================================================================================================================================================
	* Called when the content is displayed
	*
	* @param string $content the content which will be displayed
	* @param string $type the type of the article (e.g. post, page, custom_type1, etc.)
	* @param boolean $excerpt if the display is performed during the loop
	* @return string the new content
	*/
	
	function _modify_content($content, $type, $excerpt) {	
		return $content; 
	}
		
	/** ====================================================================================================================================================
	* Add a button in the TinyMCE Editor
	*
	* To add a new button, copy the commented lines a plurality of times (and uncomment them)
	* 
	* @return array of buttons
	*/
	
	function add_tinymce_buttons() {
		$buttons = array() ; 
		//$buttons[] = array(__('title', $this->pluginID), '[tag]', '[/tag]', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename( __FILE__)).'img/img_button.png') ; 
		return $buttons ; 
	}
	
	/**====================================================================================================================================================
	* Function to instantiate the class and make it a singleton
	* This function is not supposed to be modified or called (the only call is declared at the end of this file)
	*
	* @return void
	*/
	
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/** ====================================================================================================================================================
	* Define the default option values of the plugin
	* This function is called when the $this->get_param function do not find any value fo the given option
	* Please note that the default return value will define the type of input form: if the default return value is a: 
	* 	- string, the input form will be an input text
	*	- integer, the input form will be an input text accepting only integer
	*	- string beggining with a '*', the input form will be a textarea
	* 	- boolean, the input form will be a checkbox 
	* 
	* @param string $option the name of the option
	* @return variant of the option
	*/
	public function get_default_option($option) {
		switch ($option) {
			// Alternative default return values (Please modify)
			case 'new_plugin' 		: return false 		; break ; 
			
			case 'svn_client' 		: return false		; break ; 
			case 'svn_login' 		: return ""		; break ; 
			case 'svn_pwd' 			: return "[password]"		; break ; 
			case 'svn_author' 	: return "" 		; break ; 
			
			case 'update_trans'	: return false		; break ; 
			case 'trans_server'	: return ""			; break ; 
			case 'trans_login'	: return ""			; break ; 
			case 'trans_pass'	: return "[password]"			; break ; 
			
			case 'deprecated' 		: return false		; break ; 
			case 'deprecated_only_sedlex' 		: return false		; break ; 
		}
		return null ;
	}

	/** ====================================================================================================================================================
	* The admin configuration page
	* This function will be called when you select the plugin in the admin backend 
	*
	* @return void
	*/
	
	public function configuration_page() {
		global $wpdb;
		global $blog_id ; 
		

		
		if (isset($_GET['download'])) {
			$this->getPluginZip($_GET['download'],$_GET['description'],$_GET['email'],$_GET['webAuth'],$_GET['nameAuth']) ; 
		}

			ob_start() ; 
				$params = new parametersSedLex($this, "tab-parameters") ; 
				$params->add_title(__('SVN Client',  $this->pluginID)) ; 
				$params->add_param ("svn_client", __('Activate the SVN client?',$this->pluginID), "", "", array('svn_login', 'svn_pwd', 'svn_author')) ; 
				$params->add_param ("svn_login", __('Wordpress Login:',$this->pluginID)) ; 
				$params->add_comment (sprintf(__('You should have an account on %s before. Then, the login will be the same!',$this->pluginID),"<a href='http://wordpress.org/'>Wordpress.org</a>")) ; 
				$params->add_param ("svn_pwd", __('Wordpress Password:',$this->pluginID)) ; 
				$params->add_param ("svn_author", __('Author Name that is displayed in your plugins:',$this->pluginID)) ; 

				$params->add_title(__('Creation of new plugins',  $this->pluginID)) ; 
				$params->add_param ("new_plugin", __('Enable the creation of new plugins?',$this->pluginID)) ; 

				$params->add_title(__('Automatic import of translations',  $this->pluginID)) ; 
				$params->add_param ("update_trans", __('Do you want to retrieve the translations files from your email when sent to you?',$this->pluginID), "", "", array('trans_login', 'trans_pass', 'trans_server')) ; 
				$params->add_param ("trans_server", __('IMAP Server:',$this->pluginID)) ; 
				$params->add_comment (sprintf(__('Should be something like %s',$this->pluginID), "<code>{imap.domain.fr:143}INBOX</code>")) ; 
				$params->add_param ("trans_login", __('IMAP Login:',$this->pluginID)) ; 
				$params->add_comment (__('This is useful if you want that the framework retrieve automatically the translations file from an IMAP mailbox',$this->pluginID)) ; 
				$params->add_param ("trans_pass", __('IMAP Password:',$this->pluginID)) ; 
			
				$params->add_title (__('Debug functions',$this->pluginID)) ; 
				$params->add_param ("deprecated", __('Look for deprecated methods/use and display all error/warning/notice in the front page (no message in the front page):',$this->pluginID), "", "", array("deprecated_only_sedlex")) ; 
				$params->add_param ("deprecated_only_sedlex", __('Show messages/errors only for your own plugins:',$this->pluginID)) ; 
				
				$params->flush() ; 
				
			$parameters = ob_get_clean() ; 		
				
		?>
		<div class="wrap">
			<div id="icon-themes" class="icon32"><br></div>
			<h2><?php echo $this->pluginName ?></h2>
		</div>
		<div style="padding:20px;">			
			<?php
			//===============================================================================================
			// After this comment, you may modify whatever you want
			?>
			<p><?php echo __("Developers, you will appreciate this plugin I believe: your work will be much easier.", $this->pluginID) ;?></p>
			<?php
			
			// We check rights
			$this->check_folder_rights( array(array(WP_CONTENT_DIR."/sedlex/test/", "rwx")) ) ;
			
			$tabs = new adminTabs() ; 
			
			if ($this->get_param('svn_client')) {
				ob_start() ; 				

				//======================================================================================
				//= Tab listing all the plugins
				//======================================================================================
		
				$tabs = new adminTabs() ; 
									
				ob_start() ; 
				
					echo "<p>".sprintf(__("Here is the plugin developed by %s.", $this->pluginID), "<code>".$this->get_param('svn_author')."</code>")."</p>" ; 
			
					$table = new adminTable() ; 
					
					$table->title(array(__("Plugin name", $this->pluginID), __("SVN Console", $this->pluginID))) ; 
					
					
					$plugins_all = get_plugins() ; 	
					$plugins_to_show = array() ; 
					
					foreach($plugins_all as $url => $data) {
						if ((strlen($this->get_param('svn_author'))!=0)&&(preg_match("/".$this->get_param('svn_author')."/i",$data['Author']))) {
							if (preg_match("@wordpress\.org\/plugins\/([^/]*)@",$data['PluginURI'],$match)) {
								ob_start() ; 
									$slug = $match[1] ; 
									
									if (is_plugin_active($url)) {
										?>
										<p><b><?php echo $data['Name'] ; ?></b></p>
										<p><a href='admin.php?page=<?php echo $url  ; ?>'><?php echo __('Settings', $this->pluginID) ; ?></a> | <?php echo Utils::byteSize(Utils::dirSize(dirname(WP_PLUGIN_DIR.'/'.$url ))) ;?></p>
									<?php
									} else {
									?>
										<p style='color:#CCCCCC'><b><?php echo $data['Name']." ".__("(Deactivated)", $this->pluginID); ?></b></p>
										<p><?php echo Utils::byteSize(Utils::dirSize(dirname(WP_PLUGIN_DIR.'/'.$url ))) ;?></p>
									<?php							
									}
									
									echo "<div id='infoPlugin_".md5($url)."' style='display:none;' ><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif'> ".__('Update plugin information...', $this->pluginID)."</div>" ; 
									?>
									<script>
										setTimeout("timePlugin<?php echo md5($url) ?>()", Math.floor(Math.random()*4000)); 
										function timePlugin<?php echo md5($url) ?>() {
											pluginInfo('infoPlugin_<?php echo md5($url) ; ?>', '<?php echo $url ; ?>', '<?php echo $slug ; ?>') ; 
										}
									</script>
									<?php
									
								$cel1 = new adminCell(ob_get_clean()) ; 
									
								
								ob_start() ; 
									echo "<div id='corePlugin_".md5($url)."' style='display:none;' ><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif'> ".__('Retrieving SVN information...', $this->pluginID)."</div>" ; 
									?>
									<script>
										setTimeout("timeCore<?php echo md5($url) ?>()", Math.floor(Math.random()*4000)+1000); 
										function timeCore<?php echo md5($url) ?>() {
											coreInfo('corePlugin_<?php echo md5($url) ?>', '<?php echo $url ?>', '<?php echo $slug?>') ; 
										}
									</script>
					
									<?php
									
								$cel2 = new adminCell( ob_get_clean() ) ; 

								$table->add_line(array($cel1, $cel2), '1') ; 
								
							} else {
								$cel1 = new adminCell("<p style='color:#AAAAAA'><b>".$data['Name']."</b></p>") ; 
								$cel2 = new adminCell("<p style='color:#AAAAAA'>".sprintf(__("This plugin is not hosted by Wordpress as the Plugin URI is %s", $this->pluginID), "<code>".$data['PluginURI']."</code>")."</p>") ; 
								$table->add_line(array($cel1, $cel2), '1') ; 
							}
						} else {
							$cel1 = new adminCell("<p style='color:#AAAAAA'><b>".$data['Name']."</b></p>") ; 
							$cel2 = new adminCell("<p style='color:#AAAAAA'>".sprintf(__("This plugin has been created by %s and not by you (i.e. %s)", $this->pluginID), "<code>".$data['Author']."</code>", "<code>".$this->get_param('svn_author')."</code>")."</p>") ; 
							$table->add_line(array($cel1, $cel2), '1') ; 
						}
					}
					
					echo $table->flush() ; 

				$tabs->add_tab(__('SVN client',  $this->pluginID), ob_get_clean()) ; 	
			}
			
			if ($this->get_param("update_trans")) {		
				ob_start() ; 	
					echo "<h2>".__('Import translations',  $this->pluginID)."</h2>" ; 
					$this->check_mail($this->get_param("trans_server"), $this->get_param("trans_login"), $this->get_param("trans_pass")) ; 
					echo "<h2>".__('Update POT',  $this->pluginID)."</h2>" ; 
					$this->update_all_pot_translations() ; 
					echo "<h2>".__('Errors in translations',  $this->pluginID)."</h2>" ; 
					$this->check_bug_translation() ; 
				$tabs->add_tab(__('New translations',  $this->pluginID), ob_get_clean()) ; 
			}
				
			if ($this->get_param('new_plugin')) {
				ob_start() ; 				
					
					
					echo "<p>".__("The following allows you to easily create a new plugin.",$this->pluginID)."</p>" ; 
					echo "<p>".__("Follow the instructions step by step.",$this->pluginID)."</p>" ; 
					
					echo "<h2>".__("Step 1 - Create a new plugin.",$this->pluginID)."</h2>" ; 
					
					$table = new adminTable() ;
					$table->title(array(__("Required field", $this->pluginID), __("Values", $this->pluginID)) ) ;
					$cel1 = new adminCell("<p>".__("Name of the plugin", $this->pluginID)."</p><p style='color:#999999'>".sprintf(__("For instance %s", $this->pluginID), '"<i>My wonderful first plugin</i>"')."</p>") ;
					$cel2 = new adminCell('<p><input type="text" name="namePlugin" id="namePlugin" onkeyup="if (value==\'\') {document.getElementById(\'downloadPlugin\').disabled=true; }else{document.getElementById(\'downloadPlugin\').disabled=false; }"/></p>') ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$cel1 = new adminCell("<p>".__("Description", $this->pluginID)."</p>") ;
					$cel2 = new adminCell('<p><input type="text" name="descPlugin" id="descPlugin" /></p>') ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$cel1 = new adminCell("<p>".__("Your name", $this->pluginID)."</p>") ;
					$cel2 = new adminCell('<p><input type="text" name="nameAuth" id="nameAuth" /></p>') ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$cel1 = new adminCell("<p>".__("Your email", $this->pluginID)."</p>") ;
					$cel2 = new adminCell('<p><input type="text" name="emailPlugin" id="emailPlugin" /></p>') ;
					$table->add_line(array($cel1, $cel2), '1') ;
					$cel1 = new adminCell("<p>".__("Your website", $this->pluginID)."</p>") ;
					$cel2 = new adminCell('<p><input type="text" name="webAuthPlugin" id="webAuthPlugin" /></p>') ;
					$table->add_line(array($cel1, $cel2), '1') ;
					echo $table->flush() ;

					echo '<p><input name="downloadPlugin" id="downloadPlugin" class="button-secondary action" value="Download" type="submit" disabled onclick="top.location.href=\'' ; 
					echo add_query_arg	( array	(
											'noheader' => 'true',
											'download' => "'+document.getElementById('namePlugin').value+'",
											'description' => "'+document.getElementById('descPlugin').value+'",
											'nameAuth' => "'+document.getElementById('nameAuth').value+'",
											'email' => "'+document.getElementById('emailPlugin').value+'",
											'webAuth' => "'+document.getElementById('webAuthPlugin').value+'"
												) 
										);
					echo '\'"></p>' ; 
	
					echo "<h2>".__("Step 2 - Install the new plugin.",$this->pluginID)."</h2>" ; 
					echo "<p>".sprintf(__("You just have to unzip the downloaded archive and copy the extracted folder by FTP in the following directory %s.",$this->pluginID), "<code>".WP_PLUGIN_DIR."</code>")."</p>" ; 
					echo "<p>".sprintf(__("Then, go to %s and activate the downloaded plugin.",$this->pluginID), "<a href='".admin_url()."plugins.php'>Plugins > Installed Plugins</a>")."</p>" ; 
					
					echo "<h2>".__("Step 3 - Understand and modify the plugin.",$this->pluginID)."</h2>" ; 
					echo "<p>".__("The global structure of the folder of the plugin is the following:",$this->pluginID)."</p>" ; 
					echo '<p><img class="aligncenter" src="'.WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)).'img/files_and_folders.png" width="800"/></p>' ; 
					echo "<p>".sprintf(__("The file %s should have been renamed according the name you give to the plugin.",$this->pluginID), "<code>my-plugin.php</code>")."</p>" ; 

					echo "<h3>".sprintf(__("The %s file.",$this->pluginID), "<code>my-plugin.php</code>")."</h3>" ; 
					echo "<p>".__("This file should be the master piece of your plugin: most part of your code should be written in it.",$this->pluginID)."</p>" ; 
					echo "<p>".__("You just have to open it and it is well explained.",$this->pluginID)."</p>" ; 
					echo "<p>".__("Nevertheless, programming a plugin is not magic. Thus it is better to have basic knowledge in:",$this->pluginID)."</p>" ; 
					echo "<ul>" ; 
					echo '<li><a href="http://www.php.net" target="_blank">PHP </a></li>' ; 
					echo '<li><a href="http://codex.wordpress.org/Plugins" target="_blank">WordPress&nbsp;</a></li>' ; 
					echo "</ul>" ; 

					echo "<h3>".sprintf(__("The %s folder.",$this->pluginID), "<code>css</code>")."</h3>" ; 
					echo "<p>".sprintf(__("%s is called on the front side of your blog (i.e. the public side).",$this->pluginID), "<code>css_front.css</code>")."</p>" ; 
					echo "<p>".sprintf(__("%s is called only on the back side of your blog (i.e. the admin side).",$this->pluginID), "<code>css_admin.css</code>")."</p>" ; 
					echo "<p>".__("They are standard CSS files, then you can put whatever CSS code you want in them.",$this->pluginID)."</p>" ; 

					echo "<h3>".sprintf(__("The %s folder.",$this->pluginID), "<code>js</code>")."</h3>" ; 
					echo "<p>".sprintf(__("%s is called on the front side of your blog (i.e. the public side).",$this->pluginID), "<code>js_front.js</code>")."</p>" ; 
					echo "<p>".sprintf(__("%s is called only on the back side of your blog (i.e. the admin side).",$this->pluginID), "<code>js_admin.js</code>")."</p>" ; 
					echo "<p>".__("They are standard JS files, then you can put whatever JS script you want in them.",$this->pluginID)."</p>" ; 


					?>
					

					<?php
					
					//======================================================================================
					//= Tab presenting the core documentation
					//======================================================================================
										
						$classes = array() ; 
						
						// On liste les fichiers includer par le fichier courant
						$fichier_master = dirname(__FILE__)."/core.php" ; 
						
						$lines = file($fichier_master) ;
						
						$rc = new phpDoc($this);
						foreach ($lines as $lineNumber => $lineContent) {	
							if (preg_match('/url\.[\'"](.*)[\'"]/',  trim($lineContent),$match)) {
								$chem = dirname(__FILE__)."/".$match[1] ;
								$rc->addFile($chem) ; 
							}
						}
						$rc->parse() ; 
						$rc->flush() ; 
					?>
										
					<?php
					
				$tabs->add_tab(__('Create a new plugin',  $this->pluginID), ob_get_clean()) ; 	
			}


				
			$tabs->add_tab(__('Parameters',  $this->pluginID), $parameters , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_param.png") ; 	
			
			$frmk = new coreSLframework() ;  
			if (((is_multisite())&&($blog_id == 1))||(!is_multisite())||($frmk->get_param('global_allow_translation_by_blogs'))) {
				ob_start() ; 
					$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
					$trans = new translationSL($this->pluginID, $plugin) ; 
					$trans->enable_translation() ; 
				$tabs->add_tab(__('Manage translations',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_trad.png") ; 	
			}

			ob_start() ; 
				$plugin = str_replace("/","",str_replace(basename(__FILE__),"",plugin_basename( __FILE__))) ; 
				$trans = new feedbackSL($plugin, $this->pluginID) ; 
				$trans->enable_feedback() ; 
			$tabs->add_tab(__('Give feedback',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_mail.png") ; 	
			
			ob_start() ; 
				// A list of plugin slug to be excluded
				$exlude = array('wp-pirate-search') ; 
				// Replace sedLex by your own author name
				$trans = new otherPlugins("sedLex", $exlude) ; 
				$trans->list_plugins() ; 
			$tabs->add_tab(__('Other plugins',  $this->pluginID), ob_get_clean() , WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/tab_plug.png") ; 	
			
			echo $tabs->flush() ; 
			
			// Before this comment, you may modify whatever you want
			//===============================================================================================
			?>
			<?php echo $this->signature ; ?>
		</div>
		<?php
	}
	
	/** ====================================================================================================================================================
	* This function update the readme.txt in order to insert the hash of the version
	* Normally the hash will be added in the FAQ
	* 
	* @access private
	* @param string $path the path of the plugin
	* @return string hash of the plugin
	*/
	
	static function update_hash_plugin($path)  {

		$hash_plugin = Utils::md5_rec($path, array('readme.txt', 'core', 'core.php', 'core.class.php')) ; // Par contre je conserve le core.nfo 
		
		// we recreate the readme.txt
		if (is_file($path."/readme.txt")) {
			$lines = file( $path."/readme.txt" , FILE_IGNORE_NEW_LINES );
			$i = 0 ; 
			$toberecreated = false ;  
			$found = false ; 
			$result = array() ; 
			$toomuch = 0 ; 
			for ($i=0; $i<count($lines); $i++) {
				// We convert if UTF-8
				if (seems_utf8($lines[$i])) {
					$lines[$i] = utf8_encode($lines[$i]) ; 
				}
			
				// Do we found any line with InfoVersion ?
				if (preg_match("/InfoVersion:/", $lines[$i])) {
					$found = true ; 
					if (strpos($lines[$i],$hash_plugin)===false) {
						$toomuch ++ ; 
						$lines[$i]="" ;   
						$toberecreated = true ; 
					}
				}
				if (strlen(trim($lines[$i]))>0) {
					$toomuch = 0 ;  
				} else {
					$toomuch ++ ; 
				}
				// We do not add multiple blank lines (i.e. more than 2)
				if ($toomuch<2) {
					$result[] = $lines[$i]  ; 
				}
			}
		} else {
			$toberecreated = true ; 
			$result = array() ; 
		}
		
		if (($toberecreated)||(!$found)) {
			file_put_contents( $path."/readme.txt", implode( "\r\n", $result )."\r\n \r\n"."InfoVersion:".$hash_plugin, LOCK_EX ) ; 
		}
		
		return $hash_plugin ; 
	}

	/** ====================================================================================================================================================
	* Callback to get plugin Info
	* 
	* @access private
	* @return void
	*/
	function pluginInfo() {
		// get the arguments
		$plugin_name = $_POST['plugin_name'] ;
		$url = $_POST['url'] ;
		
		// $action: query_plugins, plugin_information or hot_tags
		// $req is an object
		
		$action = "plugin_information" ; 
		
		$req = new stdClass();
		$req->slug = $plugin_name;
		$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
		if ( is_wp_error($request) ) {
			echo  "error";
			die() ; 
		} else {
			$res = unserialize($request['body']);
			if ( ! $res ) {
				echo  "<p>".__('This plugin does not seem to be hosted on the wordpress repository.', $this->pluginID )."</p>";
			} else {
				echo "<p>".sprintf(__('The Wordpress page: %s', $this->pluginID),"<a href='http://wordpress.org/plugins/$plugin_name'>http://wordpress.org/plugins/$plugin_name</a>")."</p>" ; 
				echo "<p>".sprintf(__('The SVN page: %s', $this->pluginID),"<a href='http://svn.wp-plugins.org/$plugin_name'>http://svn.wp-plugins.org/$plugin_name</a>")."</p>" ; 
				$lastUpdate = date_i18n(get_option('date_format') , strtotime($res->last_updated)) ; 
				echo  "<p>".__('Last update:', $this->pluginID )." ".$lastUpdate."</p>";
				echo  "<div class='inline'>".sprintf(__('Rating: %s', $this->pluginID ), $res->rating)." &nbsp; &nbsp; </div> " ; 
				echo "<div class='star-holder inline'>" ; 
				echo "<div class='star star-rating' style='width: ".floor($res->rating)."px'></div>" ; 
				echo "</div> " ; 
				echo " <div class='inline'> &nbsp; (".sprintf(__("by %s persons", $this->pluginID ),$res->num_ratings).")</div>";
				echo "<br class='clearBoth' />" ; 
				echo  "<p>".sprintf(__('Number of download: %s', $this->pluginID ),$res->downloaded)."</p>";
			}
		}
		die() ; 
	}	
	
	/** ====================================================================================================================================================
	* Check core version of the plugin
	* 
	* @access private
	* @param string $path path of the plugin
	* @return void
	*/
	
	static function checkCoreOfThePlugin($path)  {
		$resultat = "" ; 
					
		// We compute the hash of the core folder
		$md5 = Utils::md5_rec(dirname($path).'/core/', array('SL_framework.pot', 'data')) ; 
		if (is_file(dirname($path).'/core.php'))
			$md5 .= file_get_contents(dirname($path).'/core.php') ; 
		if (is_file(dirname($path).'/core.class.php'))
			$md5 .= file_get_contents(dirname($path).'/core.class.php') ; 
			
		$md5 = md5($md5) ; 
		
		$to_be_updated = false ; 
		if (is_file(dirname($path).'/core.nfo')) {
			$info = file_get_contents(dirname($path).'/core.nfo') ; 
			$info = explode("#", $info) ; 
			if ($md5 != $info[0]) {
				if (is_file(dirname($path).'/core.nfo')) {
					unlink(dirname($path).'/core.nfo') ; 
				}
				$to_be_updated = true ; 
			}
			$date = $info[1] ; 
		} else {
			$to_be_updated = true ; 
		}
		
		// we update the info
		if ($to_be_updated) {
			$date = date("YmdHis") ; 
			file_put_contents(dirname($path).'/core.nfo', $md5."#".$date) ; 
		}
		
		return $md5."#".$date ; 
	} 
	
	/** ====================================================================================================================================================
	* Callback to get plugin Info
	* 
	* @access private
	* @return void
	*/
	function coreInfo() {
		
		// get the arguments
		$plugin_name = $_POST['plugin_name'] ;
		$url = $_POST['url'] ;
	
		$info_core = dev_toolbox::checkCoreOfThePlugin(dirname(WP_PLUGIN_DIR.'/'.$url )."/core.php") ; 
		$current_fingerprint_core_used = dev_toolbox::checkCoreOfThePlugin(SL_FRAMEWORK_DIR."/core.php") ; 
		
		$hash_plugin = dev_toolbox::update_hash_plugin(dirname(WP_PLUGIN_DIR."/".$url)) ; 
		
		$info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR."/".$url);

		$toBeDone = false ; 
		$styleDone = 'color:#666666;font-size:75% ; color:grey;' ; 
		$styleComment = 'color:#666666;font-size:75% ; color:grey; text-align:right;' ; 
		$styleError = 'color:#660000;font-size:95% ; color:grey; font-weight:bold ;' ; 
		$styleToDo = 'color:#666666;font-size:110%; font-weight:bold ; color:grey;' ; 
		
		$toBePrint = "" ; 
		
		// 0) Recuperartion de la version sur wordpress
		
		$action = "plugin_information" ; 
		
		$req = new stdClass();
		$req->slug = $plugin_name;
		$request = wp_remote_post('http://api.wordpress.org/plugins/info/1.0/', array( 'body' => array('action' => $action, 'request' => serialize($req))) );
		if ( is_wp_error($request) ) {
			echo  "error";
			die() ;
		} else {
			$res = unserialize($request['body']);
			$version_on_wordpress = "" ; 
			if ( ! $res ) {
				$response = wp_remote_get( 'http://svn.wp-plugins.org/'.$plugin_name.'/trunk/'  );
				if( is_wp_error( $response ) ) {
					echo  "error";	
					die() ;
				} else {
					if ( 404 == $response['response']['code'] ) {
						$version_on_wordpress = 0 ; 
					}
				}
			} else {
				$version_on_wordpress = $res->version ; 
			}
		}
		
		// 0) Recuperation fichier

		$response = wp_remote_get( 'http://svn.wp-plugins.org/'.$plugin_name.'/trunk/readme.txt' );
		if( is_wp_error( $response ) ) {
			echo  "error";	
			die() ;
		} else {
			if ( 200 == $response['response']['code'] ) {
				$readme_remote = $response['body'];
			} else if ( 404 == $response['response']['code'] ) {
				echo "<div class='updated fade'><p>".sprintf(__('The file %s cannot be found on the server. You have (probably) not commit this plugin yet.', $this->pluginID), '<code>http://svn.wp-plugins.org/'.$plugin_name.'/trunk/readme.txt</code>')."</p></div>" ; 
				$readme_remote = "" ; 
			} else {
				echo  "error";	
				die() ;				
			}
		}
		
		$readme_local = @file_get_contents(WP_PLUGIN_DIR."/".$plugin_name.'/readme.txt' ) ;
		
		// 1) Mise a jour framework
		
		if ($current_fingerprint_core_used != $info_core) {
			$toBePrint .= "<div id='coreUpdate_".md5($url)."' style='display:none;' ><img src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif'> ".__('Update the core of the framework...', $this->pluginID)."</div>" ; 
			$toBePrint .= "<p style='".$styleToDo."'>" ; 
			$toBeDone = true ; 
			$toBePrint .= "<a href='#' onclick='coreUpdate(\"coreUpdate_".md5($url)."\", \"corePlugin_".md5($url)."\", \"".$url."\" , \"".$plugin_name."\") ; return false ; '>";
			$toBePrint .= sprintf(__('1) Update with the core with %s ', $this->pluginID), str_replace(WP_PLUGIN_DIR, "", SL_FRAMEWORK_DIR)) ; 
			$toBePrint .= "</a>" ; 
			$toBePrint .= "<img id='wait_corePlugin_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
			$toBePrint .= "</p>" ;  
		} else {
			$toBePrint .= "<p style='".$styleDone."'>" ; 
			$toBePrint .= __('1) The core of the plugin is up-to-date', $this->pluginID) ; 
			$toBePrint .= "</p>" ; 				
		}
		
		// 2) Update the readme.txt and the version
		
		if ($readme_remote == $readme_local) {
			$toBePrint .= "<p style='".$styleDone."'>" ; 
			$toBePrint .= "<a href='#' onClick='changeVersionReadme(\"".md5($url)."\", \"".$url."\", \"".$plugin_name."\"); return false;'>" ; 
			$toBePrint .= sprintf(__("2) Modify the readme.txt (the version is %s)", $this->pluginID), $info['Version']) ;
			$toBePrint .= "</a>" ; 
			$toBePrint .= "<img id='wait_changeVersionReadme_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
			$toBePrint .= "</p>" ; 	
		} else {
			if ((!$toBeDone) && ($version_on_wordpress == $info['Version'])) {
				$toBePrint .=  "<p style='".$styleToDo."'>" ; 
				$toBeDone = true ; 
				$toBePrint .= "<a href='#' onClick='changeVersionReadme(\"".md5($url)."\", \"".$url."\", \"".$plugin_name."\"); return false;'>" ; 
				$toBePrint .= sprintf(__("2) Modify the readme.txt (the version is %s)", $this->pluginID), $info['Version']) ;
					$toBePrint .= "</a>" ; 
				$toBePrint .= "<img id='wait_changeVersionReadme_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
				$toBePrint .= "</p>" ; 	
			} else {
				if ($version_on_wordpress == $info['Version']) {
					$toBePrint .=  "<p style='".$styleDone."'>" ; 	
					$toBePrint .= "<a href='#' onClick='changeVersionReadme(\"".md5($url)."\", \"".$url."\", \"".$plugin_name."\"); return false;'>" ; 
					$toBePrint .= sprintf(__("2) Modify the readme.txt (the version is %s)", $this->pluginID), $info['Version']) ;
					$toBePrint .= "</a>" ; 
					$toBePrint .= "<img id='wait_changeVersionReadme_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
					$toBePrint .= "</p>" ; 						
				} else {
					$toBePrint .=  "<p style='".$styleDone."'>" ; 	
					$toBePrint .= "<a href='#' onClick='changeVersionReadme(\"".md5($url)."\", \"".$url."\", \"".$plugin_name."\"); return false;'>" ; 
					$toBePrint .= sprintf(__("2) Modify the readme.txt (the local version is %s whereas the Wordpress version is %s)", $this->pluginID), $info['Version'], $version_on_wordpress) ;
					$toBePrint .= "</a>" ; 
					$toBePrint .= "<img id='wait_changeVersionReadme_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
					$toBePrint .= "</p>" ; 	
				}
			}	
		} 
		
		// 3) SVN update
		
		if ($version_on_wordpress == $info['Version']) {
			$toBePrint .=  "<p style='".$styleDone."'>" ; 	
			$toBePrint .= " <a href='#' onClick='showSvnPopup(\"".md5($url)."\", \"".$plugin_name."\", \"".$url."\", \"".$version_on_wordpress."\", \"".$info['Version']."\"); return false;'>" ;
			$toBePrint .= sprintf(__("3) Update the SVN repository (without modifying the version)", $this->pluginID), $info['Version']) ;
			$toBePrint .=  "</a>" ;
			$toBePrint .= "<img id='wait_popup_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
			$toBePrint .=  "</p>" ;
		} else {
			if ((!$toBeDone)) {
				$toBePrint .=  "<p style='".$styleToDo."'>" ; 
				$toBeDone = true ; 
			} else {
				$toBePrint .=  "<p style='".$styleDone."'>" ; 		
			}
			$toBePrint .= " <a href='#' onClick='showSvnPopup(\"".md5($url)."\", \"".$plugin_name."\", \"".$url."\", \"".$version_on_wordpress."\", \"".$info['Version']."\"); return false;'>" ;
			$toBePrint .= sprintf(__("3) Update the SVN repository (and release a new version %s)", $this->pluginID), $info['Version']) ;
			$toBePrint .=  "</a>" ;
			$toBePrint .= "<img id='wait_popup_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
			$toBePrint .=  "</p>" ;			
		}
		
		$toBePrint .=  "<p style='".$styleComment."'><a href='#' onclick='coreInfo(\"corePlugin_".md5($url)."\", \"".$url."\", \"".$plugin_name."\"); return false ; '>".__('Refresh', $this->pluginID)."</a></p>" ; 

		// Display the TODO zone for developers
		$content = "" ; 
		if (is_file(WP_PLUGIN_DIR."/".$plugin_name."/todo.txt")) {
			$content = @file_get_contents(WP_PLUGIN_DIR."/".$plugin_name."/todo.txt") ; 
		}
		$toBePrint .=  "<p><textarea id='txt_savetodo_".md5($url)."' style='font:80% courier; width:100%' rows='5'>".stripslashes(htmlentities(utf8_decode($content), ENT_QUOTES, "UTF-8"))."</textarea></p>" ; 
		$toBePrint .=  "<p><input onclick='saveTodo(\"".md5($url)."\", \"".$plugin_name."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Save Todo List', $this->pluginID)."' />" ; 
		$toBePrint .= "<img id='wait_savetodo_".md5($url)."' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'>" ; 
		$toBePrint .= "<span id='savedtodo_".md5($url)."' style='display:none;'>".__("Todo list saved!", $this->pluginID)."</span>" ; 
		$toBePrint .= "<span id='errortodo_".md5($url)."'></span>" ; 
		$toBePrint .= "</p>" ; 

		echo $toBePrint  ; 

		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback to get plugin Info
	* 
	* @access private
	* @return void
	*/
	
	function coreUpdate() {
		// get the arguments
		$plugin_name = $_POST['plugin_name'] ;
		$url = $_POST['url'] ;

		
		$path_from_update = SL_FRAMEWORK_DIR ;
		dev_toolbox::checkCoreOfThePlugin($path_from_update."/core.php") ; 
		
		$path_to_update = WP_PLUGIN_DIR."/".$plugin_name ;
		
		Utils::rm_rec($path_to_update."/core/") ; 
		Utils::rm_rec($path_to_update."/core.php") ; 
		Utils::rm_rec($path_to_update."/core.class.php") ; 
		Utils::rm_rec($path_to_update."/core.nfo") ; 
		
		Utils::copy_rec($path_from_update."/core/", $path_to_update."/core/") ; 
		Utils::copy_rec($path_from_update."/core.php", $path_to_update."/core.php") ; 
		Utils::copy_rec($path_from_update."/core.class.php", $path_to_update."/core.class.php") ; 
		Utils::copy_rec($path_from_update."/core.nfo", $path_to_update."/core.nfo") ; 
		
		$this->coreInfo() ; 
		
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback to saving todo changes
	* 
	* @access private
	* @return void
	*/
	
	function saveTodo() {
		// get the arguments
		$plugin = $_POST['plugin'] ;
		$todo = $_POST['textTodo'] ;
		
		if (file_put_contents(WP_PLUGIN_DIR."/".$plugin."/todo.txt", utf8_encode($todo))!==FALSE) {
			echo "ok" ; 
		} else {
			echo "problem" ; 
		}
		
		die() ; 
	}		
	
	/** ====================================================================================================================================================
	* Callback for changing the version in the main php file
	* 
	* @access private
	* @return void
	*/		
	
	function changeVersionReadme() {
		// get the arguments
		$plugin = $_POST['plugin'];
		$url = $_POST['url'];
		
		$info = pluginSedlex::get_plugins_data(WP_PLUGIN_DIR."/".$url ) ; 
		list($descr1, $descr2) = explode("</p>",$info['Description'],2) ; 

		$title = sprintf(__('Change the plugin version for %s', $this->pluginID),'<em>'.$plugin.'</em>') ;
		
		$version = $info['Version'] ; 
		if ($version!="") {
			$entete = "<div id='readmeVersion'><h3>".__('Version number', $this->pluginID)."</h3>" ; 
			$entete .= "<p>".sprintf(__('The current version of the plugin %s is %s.', $this->pluginID), "<code>".$url ."</code>", $version)."</p>" ; 
			$entete .= "<p>".__('Please modify the version:', $this->pluginID)." <input type='text' size='7' name='versionNumberModify' id='versionNumberModify' value='".$version."'></p>" ; 
			$entete .= "<h3>".__('Readme file', $this->pluginID)."</h3>" ; 
			$entete .= "<p>".sprintf(__('The current content of %s is:', $this->pluginID), "<code>".$plugin."/readme.txt</code>")."</p>" ; 

			// We look now at the readme.txt
			$readme = strip_tags(@file_get_contents(WP_PLUGIN_DIR."/".$plugin."/readme.txt")) ; 
			
			// We detect the current version
			global $wp_version;
			preg_match("/^(\d+)\.(\d+)(\.\d+|)/", $wp_version, $hits);
			$root_tagged_version = $hits[1].'.'.$hits[2];
			$tagged_version = $root_tagged_version;
			if (!empty($hits[3])) $tagged_version .= $hits[3];

			// We construct the default text 
			$default_text = "=== ".$info['Plugin_Name']." ===\n" ; 
			$default_text .= "\n" ; 
			$default_text .= "Author: ".$info['Author']."\n" ; 
			$default_text .= "Contributors: ".$info['Author']."\n" ; 
			$default_text .= "Author URI: ".$info['Author_URI']."\n" ; 
			$default_text .= "Plugin URI: ".$info['Plugin_URI']."\n" ; 
			$default_text .= "Tags: ".$info['Plugin_Tag']."\n" ; 
			$default_text .= "Requires at least: 3.0\n" ; 
			$default_text .= "Tested up to: ".$tagged_version."\n" ; 
			$default_text .= "Stable tag: trunk\n" ; 
			$default_text .= "\n" ; 
			$default_text .= strip_tags($descr1)."\n" ; 
			$default_text .= "\n" ; 
			$default_text .= "== Description ==\n" ; 
			$default_text .= "\n" ; 
			// Change the description form
			$descr2 = str_replace("<li>", "* ", $descr2 ) ; 
			$descr2 = str_replace("<b>", "*", $descr2 ) ; 
			$descr2 = str_replace("</b>", "*", $descr2 ) ; 
			$descr2 = str_replace("</li>", "\n", $descr2 ) ; 
			$descr2 = str_replace("</ul>", "\n", $descr2 ) ; 
			$descr2 = str_replace("</p>", "\n\n", $descr2 ) ; 
			$default_text .= strip_tags($descr1)."\n\n".strip_tags($descr2);
			$default_text .= "= Multisite - Wordpress MU =" ; 
			if (preg_match("/= Multisite - Wordpress MU =(.*)= Localization =/s", $readme, $match)) {
				$default_text .= $match[1] ; 
			} else {
				$default_text .= "\n\n" ; 
			}
			$default_text .= "= Localization =\n" ; 
			$default_text .= "\n" ; 
			$list_langue = translationSL::list_languages($plugin) ; 
			foreach ($list_langue as $l) {
				$default_text .= "* ".$l."\n" ; 
			}
			$default_text .= "\n" ; 
			$default_text .= "= Features of the framework =\n" ; 
			$default_text .= "\n" ; 
			if (is_file(dirname(__FILE__)."/core/data/framework.info")) 
				$default_text .= @file_get_contents(dirname(__FILE__)."/core/data/framework.info"); 
			$default_text .= "\n" ; 
			$default_text .= "\n" ; 
			$default_text .= "== Installation ==\n" ; 
			$default_text .= "\n" ; 
			$default_text .= "1. Upload this folder ".$plugin." to your plugin directory (for instance '/wp-content/plugins/')\n" ; 
			$default_text .= "2. Activate the plugin through the 'Plugins' menu in WordPress\n" ; 
			$default_text .= "3. Navigate to the 'SL plugins' box\n" ; 
			$default_text .= "4. All plugins developed with the SL core will be listed in this box\n" ; 
			$default_text .= "5. Enjoy !\n" ; 
			$default_text .= "\n" ; 
			$default_text .= "== Screenshots ==\n" ; 
			$default_text .= "\n" ; 
			// We look for the screenshots
			if (preg_match("/== Screenshots ==(.*)== Changelog ==/s", $readme, $match)) {
				$screen = explode("\n", $match[1]) ; 
				for ($i=1; $i<20 ; $i++) {
					if ( (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.png')) ||  (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.gif')) ||  (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.jpg')) ||  (is_file(WP_PLUGIN_DIR."/".$plugin.'/screenshot-'.$i.'.bmp')) ) {
						$found = false ; 
						foreach($screen as $s) {
							if (preg_match("/^".$i."[.]/s", $s)) {
								$found = true ; 
								$default_text .= $s."\n" ; 
							}
							

						}
						if (!$found) {
							$default_text .= $i.". (empty)\n" ; 
						}
					}
				}
			}
			$default_text .= "\n" ; 
			$default_text .= "== Changelog ==" ; 
			// We copy what the readmefile contains
			if (preg_match("/== Changelog ==(.*)== Frequently Asked Questions ==/s", $readme, $match)) {
				$default_text .= $match[1] ; 
			}
			$default_text .= "== Frequently Asked Questions ==" ; 
			// We copy what the readmefile contains
			if (preg_match("/== Frequently Asked Questions ==(.*)InfoVersion/s", $readme, $match)) {
				$default_text .= $match[1] ; 
			}
			// We recopy the infoVersion ligne

			$default_text .= "InfoVersion:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\n" ; 

			$content = "<p><textarea id='ReadmeModify' rows='".(count(explode("\n", $readme))+1)."' cols='100%'>".$readme."</textarea></p>" ; 
			
			$default_text = "<p><textarea id='ReadmePropose' rows='".(count(explode("\n", $readme))+1)."' cols='100%'>".$default_text."</textarea></p>" ; 
			
			$table = new adminTable() ;
			$table->title(array(__("The current text", $this->pluginID), __("The proposed text", $this->pluginID)) ) ;
			$cel1 = new adminCell($content) ;
			$cel2 = new adminCell($default_text) ;
			$table->add_line(array($cel1, $cel2), '1') ;
			$content = $entete.$table->flush() ;
			
			$content .= "<p id='svn_button'><input onclick='saveVersionReadme(\"".$plugin."\", \"".$url."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Save these data', $this->pluginID)."' /><img id='wait_save' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p></div>" ;  
		
		} else {
			$content = "<div class='error fade'><p>".sprintf(__('There is a problem with the header of %s. It appears that there is no Version header.', $this->pluginID), "<code>".$plugin."/".$plugin.".php</code>")."</p></div>"; 
		}
		
		$current_fingerprint_core_used = dev_toolbox::checkCoreOfThePlugin(SL_FRAMEWORK_DIR."/core.php") ; 
					 ; 

		$popup = new popupAdmin($title, $content, "", "jQuery('#corePlugin_".md5($url)."').html('"."<p>".__("Update of the SVN information", $this->pluginID)." <img src=\"".WP_PLUGIN_URL."/".str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif\"></p>"."'); coreInfo('corePlugin_".md5($url)."', '".$url."', '".$plugin."');") ; 
		$popup->render() ; 
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback saving the readme text
	* 
	* @access private
	* @return void
	*/		
	
	function saveVersionReadme() {
		// get the arguments
		$plugin = $_POST['plugin'];
		$url = $_POST['url'];
		$readme = $_POST['readme'];
		$version = $_POST['version'];
		
		// We clean the readme before saving it
		$readme = str_replace("\\'", "'", $readme) ; 
		$readme = str_replace('\\"', '"', $readme) ; 
		$readme = str_replace('<', '&lt;', $readme) ; 
		$readme = str_replace('>', '&gt;', $readme) ; 
		
		// We save the readme
		@file_put_contents(WP_PLUGIN_DIR."/".$plugin."/readme.txt", $readme) ; 
		
		// We save the version
		$lines = @file(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php") ; 
		$save = "" ; 
		foreach ($lines as $l) {
			if (preg_match("/^Version:(.*)$/i", $l, $match)) {
				$save .= "Version: ".$version."\r\n" ; 
			} else {
				$save .= $l ; 
			}
		}
		@file_put_contents(WP_PLUGIN_DIR."/".$plugin."/".$plugin.".php", $save) ; 
		
		echo "<div class='updated fade'><p>".__('The data has been saved. You may close this window.', $this->pluginID)."</p></div>"; 
		echo "<script>disablePopup();</script>" ; 
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* This function returns the plugin zip  
	* 
	* @access private
	* @param string $name the name of the plugin
	* @return void
	*/
	
	private function getPluginZip($name, $description, $email, $webAuth, $nameAuth)  {
		$name = preg_replace("/[^a-zA-Z ]/", "", trim($name)) ; 
		$folder_name = strtolower(str_replace(" ", "-", $name)) ; 
		$id_name = strtolower(str_replace(" ", "_", $name)) ; 
		
		$plugin_dir = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__ ),"",plugin_basename( __FILE__ )) ; 
		
		if ($folder_name!="") {
			// Create the temp folder
			$path = WP_CONTENT_DIR."/sedlex/new_plugins_zip/".$folder_name ; 
			if (!is_dir($path)) {
				mkdir("$path", 0755, true) ; 
			}
			
			// Copy static files
			Utils::copy_rec($plugin_dir.'/core/templates/css',$path.'/css') ; 
			Utils::copy_rec($plugin_dir.'/core/templates/js',$path.'/js') ; 
			Utils::copy_rec($plugin_dir.'/core/templates/img',$path.'/img') ; 
			Utils::copy_rec($plugin_dir.'/core/templates/lang',$path.'/lang') ; 
			Utils::copy_rec($plugin_dir.'/core',$path.'/core') ; 
			Utils::copy_rec($plugin_dir.'/core.php',$path."/core.php") ; 
			Utils::copy_rec($plugin_dir.'/core.class.php',$path."/core.class.php") ; 
			Utils::copy_rec($plugin_dir.'/core.nfo',$path."/core.nfo") ; 
			
			// Copy the dynamic files
			$content = file_get_contents($plugin_dir.'/core/templates/my-plugin.php') ; 
			$content = str_replace("youremail@yourdomain.com", $email, $content ) ; 
			$content = str_replace("http://www.yourdomain.com/", $webAuth, $content ) ; 
			$content = str_replace("The name of the author", $nameAuth, $content ) ; 
			$content = str_replace("The description of the plugin on this line.", $description, $content ) ; 
			$content = str_replace("My Plugin", $name, $content ) ; 
			$content = str_replace("my_plugin", $id_name, $content ) ; 
			$content = str_replace("my-plugin", $folder_name, $content ) ; 
			file_put_contents($path."/".$folder_name.".php", $content);

			$content = file_get_contents($plugin_dir.'/core/templates/readme.txt') ; 
			$content = str_replace("youremail@yourdomain.com", $email, $content ) ; 
			$content = str_replace("http://www.yourdomain.com", $webAuth, $content ) ; 
			$content = str_replace("Your name", $nameAuth, $content ) ; 
			$content = str_replace("The short description", $description, $content ) ; 
			$content = str_replace("The long description", $description, $content ) ; 
			$content = str_replace("My Plugin", $name, $content ) ; 
			$content = str_replace("my_plugin", $id_name, $content ) ; 
			$content = str_replace("my-plugin", $folder_name, $content ) ; 
			file_put_contents($path."/readme.txt", $content);

			// Zip the folder
			$file = WP_CONTENT_DIR."/sedlex/new_plugins_zip/".$folder_name.".zip" ; 
			$zip = new PclZip($file) ; 
			$remove = WP_CONTENT_DIR."/sedlex/new_plugins_zip/" ;
			$result = $zip->create($path, PCLZIP_OPT_REMOVE_PATH, $remove); 
			if ($result == 0) {
				die("Error : ".$zip->errorInfo(true));
			}
			
			// Stream the file to the client
			header("Content-Type: application/zip");
			header("Content-Length: " . @filesize($file));
			header("Content-Disposition: attachment; filename=\"".$folder_name.".zip\"");
			readfile($file);
			
			// We stop everything
			unlink($file); 
			Utils::rm_rec($path) ; 
			die() ; 
		}
	}
	
	/** ====================================================================================================================================================
	* Callback for displaying the SVN popup
	* 
	* @access private
	* @return void
	*/		
	
	function svn_show_popup() {
	
		// get the arguments
		$url = $_POST['url'];
		$plugin = $_POST['plugin'];
		$version1 = $_POST['version1'];
		$version2 = $_POST['version2'];
		
		$title = sprintf(__('SVN client for %s', $this->pluginID),'<em>'.$plugin.'</em>') ;
		
		ob_start() ; 
		
			// SVN preparation
			$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
			Utils::rm_rec($local_cache."/".$plugin) ;
			$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->get_param('svn_login'), $this->get_param('svn_pwd') ) ; 
				
			$revision = $svn->getRevision("/".$plugin."/trunk", true) ;
			$vcc = $svn->getVCC("/".$plugin."/trunk", true) ;
			$revision = $revision['revision'] ; 
			$vcc = $vcc['vcc'] ; 
			
			$res = $svn->getAllFiles("/".$plugin."/trunk", $vcc, $revision, $local_cache."/".$plugin, true) ; 
			SL_Debug::log(get_class(), "Got all files from "."/".$plugin."/trunk", 4) ; 
			echo "<div id='svn_div'>" ; 
			
			// On met a jour le cache local !
			echo "<h3>".__('Updating the local cache', $this->pluginID)."</h3>" ; 

			echo "<div class='console' id='svn_console'>\n" ; 
			$i=0 ; 
			foreach ($res['info'] as $inf) {
				$i++ ; 
				if ($inf['folder']) {
					if ($inf['ok']) {
						echo $i.". ".$inf['url']." <span style='color:#669900'>OK</span> (".__('folder created', $this->pluginID).")<br/>" ; 
					} else {
						echo $i.". ".$inf['url']." <span style='color:#CC0000'>KO</span> (".__('folder creation has failed !', $this->pluginID).")<br/>" ; 						
					}
				} else {
					if ($inf['ok']) {
						echo $i.". ".$inf['url']." <span style='color:#669900'>OK</span> (".sprintf(__("%s bytes transfered", $this->pluginID), $inf['size']).")<br/>" ; 
					} else {
						echo $i.". ".$inf['url']." <span style='color:#CC0000'>KO</span><br/>" ; 						
					}						
				}
			}
			
			if ($res['isOK']) {
				echo "</div>\n" ; 
				echo "<script>\n" ; 	
				$randDisplay = rand(0,100000) ; 
				echo "jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);\r\n" ; 
				
				echo "function displayTheDiff".$randDisplay."() { 
					var arguments = {
						action: 'svn_compare', 
						version1 : '".$version1."',
						version2 : '".$version2."',
						plugin : '".$plugin."'
					}; 
					jQuery.post(ajaxurl, arguments, function(response) { 
						jQuery(\"#innerPopupForm\").html(response); 
					}).error(function(x,e) { 
						if (x.status==0){
							//Offline
						} else if (x.status==500){
							jQuery(\"#innerPopupForm\").html(\"Error 500: The ajax request is retried\");
							displayTheDiff".$randDisplay."() ;
						} else {
							jQuery(\"#innerPopupForm\").html(\"Error \"+x.status+\": No data retrieved\");
						}
					});
				}
				displayTheDiff".$randDisplay."() ; \r\n" ; 
				echo "</script>\n" ; 	
			} else {
				echo __('An error occurred during the retrieval of files on the server ! Sorry ...', $this->pluginID)."<br/>\n" ; 
				$svn->printRawResult($res['raw_result']) ; 	
				echo "</div>\n" ; 					
			}
			echo "</div>\n" ; 	
			
		$content = ob_get_clean() ; 	
		
		$popup = new popupAdmin($title, $content, "", "jQuery('#corePlugin_".md5($url)."').html('"."<p>".__("Update of the SVN information", $this->pluginID)." <img src=\"".WP_PLUGIN_URL."/".str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif\"></p>"."'); coreInfo('corePlugin_".md5($url)."', '".$url."', '".$plugin."');") ; 
		$popup->render() ; 
		die() ; 
	}
	
	
	/** ====================================================================================================================================================
	* Callback for comparing a plugin with the repo cache
	* 
	* @access private
	* @return void
	*/		
	
	function svn_compare() {
		// get the arguments
		$plugin = $_POST['plugin'] ;
		$version1 = $_POST['version1'] ;
		$version2 = $_POST['version2'] ;
		
		$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
		
		$info_core = dev_toolbox::checkCoreOfThePlugin(WP_PLUGIN_DIR.'/'.$plugin ."/core.php") ; 
		$hash_plugin = dev_toolbox::update_hash_plugin(WP_PLUGIN_DIR."/".$plugin) ; 
		
		ob_start() ;
			echo "<h3>".__('Local to SVN repository', $this->pluginID)."</h3>" ; 
			echo "<p>".sprintf(__('Comparing %s with %s', $this->pluginID), "<em>".WP_PLUGIN_DIR."/".$plugin."/</em>", "<em>".$local_cache."/".$plugin."/"."</em>")."</p>" ; 
			
			$folddiff = new foldDiff($this->pluginID) ; 
			$result = $folddiff->diff(WP_PLUGIN_DIR."/".$plugin, $local_cache."/".$plugin) ; 
			$random = $folddiff->render(true, true, true) ; 
			$version = "test_branch" ; 
			
			// Confirmation asked
			echo "<div id='confirm_to_svn1'>" ; 
			echo "<h3>".__('Confirmation', $this->pluginID)."</h3>" ; 
			
			echo "<p>".__('Commit comment:', $this->pluginID)."</p>" ; 
			$comment = "" ; 
			// we recreate the readme.txt
			if (is_file(WP_PLUGIN_DIR."/".$plugin."/readme.txt")) {
				$lines = file( WP_PLUGIN_DIR."/".$plugin."/readme.txt" , FILE_IGNORE_NEW_LINES );
				$found = false ; 
				for ($i=0; $i<count($lines); $i++) {
					// We convert if UTF-8
					if (seems_utf8($lines[$i])) {
						$lines[$i] = utf8_encode($lines[$i]) ; 
					}
		
					if (($found)&&(preg_match("/= .* =/", $lines[$i]))) {
						break ;
					}
					
					if ($found) {
						$comment .= $lines[$i] ; 
					}
					if (preg_match("/= ".str_replace(".", "\.", $version2)." =/", $lines[$i])) {
						$found = true ;
					}
				}
			}
			
			echo "<p><textarea cols='70' rows='5' name='svn_comment' id='svn_comment'>".$comment."</textarea></p>\n" ;  
			if ($version1!=$version2) {
				echo "<p id='svn_button'><input onclick='svn_to_repo(\"".$plugin."\", \"$random\", \"".$version1."\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".sprintf(__('Create a new branch %s and then Update the SVN repository with version %s', $this->pluginID), $version1, $version2)."' /></p>" ;  
				echo "<p id='svn_button'><input onclick='svn_to_repo(\"".$plugin."\", \"$random\", \"\") ; return false ; ' type='submit' name='submit' class='button validButton' value='".__('Only update the SVN repository', $this->pluginID)."' /></p>" ;  
			} else {
				echo "<p id='svn_button'><input onclick='svn_to_repo(\"".$plugin."\", \"$random\", \"\") ; return false ; ' type='submit' name='submit' class='button-primary validButton' value='".__('Only update the SVN repository', $this->pluginID)."' /></p>" ;  			
			}
			echo "<script>jQuery('#innerPopupForm').animate({scrollTop: 0}, 10);</script>\r\n" ; 
			echo "</div>" ; 
			
			echo "<p><img id='wait_svn1' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ; 
			
			echo "<p>&nbsp;</p>" ; 
			echo "<p>&nbsp;</p>" ; 
						
			echo "<script>jQuery('#innerPopupForm').animate({scrollTop: 0}, 10);</script>\r\n" ; 
			echo "</div>" ; 
			
			echo "<p><img id='wait_svn2' src='".WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."core/img/ajax-loader.gif' style='display:none;'></p>" ; 

		
		echo "<div id='console_svn'></div>\r\n" ; 
					
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback for sending selected local file to repo
	* 
	* @access private
	* @return void
	*/		
	
	function svn_to_repo() {
		// get the arguments
		$plugin = $_POST['plugin'] ;
		$comment = $_POST['comment'] ;
		$files = $_POST['files'] ;

		echo "<div class='console' id='svn_console2'>\n" ; 
		echo __("Sending to the repository in progress...", $this->pluginID)."<br/>----------<br/>" ; 
					
		// SVN preparation
		$local_cache = WP_CONTENT_DIR."/sedlex/svn" ;
		$root = "/".$plugin."/trunk/" ; 
		$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->get_param('svn_login'), $this->get_param('svn_pwd') ) ; 
		
		// If we have to create a new branch we do it now !
		foreach ($files as $f) {
			if ($f[1]=='create_branch') {
				$result = $svn->prepareCommit("/".$plugin."/", "Add a tag for ".$f[0]." (i.e. saving this version)", true) ; 
				if ($result['isOK']) {
					// Add branch
					$res = $svn->createBranch(str_replace("trunk/", "trunk", str_replace("/ver/", "/bc/", $result['versionFolder']))."/trunk/", "http://svn.wp-plugins.org".$result['putFolder']."/tags/".$f[0], true) ; 
					if ($res['isOK']) {
						echo sprintf(__("Creation of a new branch %s", $this->pluginID), $f[0])." <span style='color:#669900'>OK</span>" ; 
						echo "<br/>----------<br/>" ; 
						$uuid = $result['uuid'] ;
						$activityFolder = $result['activityFolder'] ;
						// MERGE
						$res = $svn->merge("/".$plugin."/", $activityFolder.$uuid,  true) ; 
						if ($res['isOK']) {
							SL_Debug::log(get_class(), "The creation of the tag has been merged within the repository", 4) ; 
						} else {
							SL_Debug::log(get_class(), "The creation of the tag cannot be merged within the repository", 2) ; 
							echo " <span style='color:#CC0000'>".__("The commit has ended but there is an error!",$this->pluginID)."</span>" ; 
							echo $svn->printRawResult($res['raw_result']) ; 
							die() ; 
						}
					} else {
						echo sprintf(__("Creation of a new branch %s", $this->pluginID), $f[0])." <span style='color:#FF0000'>KO</span>" ; 
						echo 	"<br/>SVN header : <br/>" ; 
						print_r($res['svn_header']) ; 
						echo "<br/>" ; 
						echo $svn->printRawResult($res['raw_result']) ; 
						die() ; 
					}	
				} else {
					echo "<p style='color:#CC0000;'>" ; 
					print_r($result) ; 
					echo "</p>" ; 
					echo "</div>\n" ; 
					die() ;			
				}
			}
		}
			
		
		$result = $svn->prepareCommit($root, $comment, true) ; 
		if ($result['isOK']) {
			echo "</div>\n" ; 
			$nb_fichier = 0 ; 
			?>
			<script>
			
				var add_folder = new Array(<?php 
				$newf = array() ; 
				$first = true ; 
				foreach ($files as $f) {
					if ($f[1]=='add_folder') {
						$newf= array_merge($newf, array($f[0] => strlen($f[0])) ) ; 
						$nb_fichier ++ ; 
					}
				}
				arsort($newf) ; // We order reverse because the javascript will take the last one (i.e. pop() method)
				foreach ($newf as $f => $n) {
					if (!$first) 
						echo ", " ; 
					echo "'".$f."'" ; 
					$first = false ; 
				}
				?>) ; 
				var add_file= new Array(<?php 
				$first = true ; 
				foreach ($files as $f) {
					if ($f[1]=='add') {
						if (!$first) 
							echo ", " ; 
						echo "'".$f[0]."'" ; 
						$first = false ; 
						$nb_fichier ++ ; 
					}
				}
				?>) ; 
				var modify_file= new Array(<?php 
				$first = true ; 
				foreach ($files as $f) {
					if ($f[1]=='modify') {
						if (!$first) 
							echo ", " ; 
						echo "'".$f[0]."'" ; 
						$first = false ; 
						$nb_fichier ++ ; 
					}
				}
				?>) ; 
				var delete_file= new Array(<?php 
				$first = true ; 
				foreach ($files as $f) {
					if ($f[1]=='delete') {
						if (!$first) 
							echo ", " ; 
						echo "'".$f[0]."'" ; 
						$first = false ; 
						$nb_fichier ++ ; 
					}
				}
				?>) ; 
				var delete_folder = new Array(<?php 
				$first = true ; 
				$newf = array() ; 
				foreach ($files as $f) {
					if ($f[1]=='delete_folder') {
						$newf= array_merge($newf, array($f[0] => strlen($f[0])) ) ; 
						$nb_fichier ++ ; 
					}
				}
				asort($newf) ; // We order non-reverse because the javascript will take the last one (i.e. pop() method)
				foreach ($newf as $f => $n) {
					if (!$first) 
						echo ", " ; 
					echo "'".$f."'" ; 
					$first = false ; 
				}
				?>) ; 
				var nb_fichier = <?php echo $nb_fichier ; ?> ; 
				var num_fichier = 0 ; 
				function  executeSVNloop() {
					jQuery('#innerPopupForm').animate({scrollTop: jQuery('#innerPopupForm')[0].scrollHeight}, 10);
					
					// Condition de fin de recursion
					if ((add_folder.length==0)&&(add_file.length==0)&&(modify_file.length==0)&&(delete_file.length==0)&&(delete_folder.length==0)) {
						arguments = {
							action: 'svn_merge', 
							root : '<?php echo $root ; ?>', 
							uuid : '<?php echo $result['uuid'] ; ?>', 
							activityFolder : '<?php echo $result['activityFolder'] ; ?>' 
						}  
						//POST the data and append the results to the results div
						jQuery.post(ajaxurl, arguments, function(response) { 
							jQuery("#wait_svn1").hide();
							jQuery("#wait_svn2").hide();
							jQuery("#svn_console2").append('----------<br/>'+ response+'<br/>') ; 
						}) ; 
						return ; 
					}
					
					//Envoi et reccursion pour les repertoires ADD
					if (add_folder.length!=0) {
						var urlfolder = add_folder.pop();
						jQuery("#svn_console2").append("(A) "+(num_fichier+1)+". "+urlfolder+" (folder)");
						arguments = {
							action: 'svn_put_folder_in_repo', 
							urlfolder : "<?php echo $result['putFolder']."/" ?>"+urlfolder 
						} 
						jQuery.post(ajaxurl, arguments, function(response) { 
							num_fichier ++ ; 
							jQuery("#svn_console2").append(response+' ('+Math.floor(100*num_fichier/nb_fichier)+'%)<br/>');
							executeSVNloop() ; 
						}) ; 
					}
					
					//Envoi et reccursion pour les fichiers ADD
					else if (add_file.length!=0) {
						var file = add_file.pop();
						jQuery("#svn_console2").append("(A) "+(num_fichier+1)+". "+file+" ");
						arguments = {
							action: 'svn_put_file_in_repo', 
							file : "<?php echo WP_PLUGIN_DIR."/".$plugin."/" ; ?>"+file, 
							urldepot : "<?php echo $result['putFolder']."/" ?>"+file
						} 
						jQuery.post(ajaxurl, arguments, function(response) { 
							num_fichier ++ ; 
							jQuery("#svn_console2").append(response+' ('+Math.floor(100*num_fichier/nb_fichier)+'%)<br/>');
							executeSVNloop() ; 
						}) ; 
					}
					
					// fichier MODIF
					else if (modify_file.length!=0) {
						var file = modify_file.pop();
						jQuery("#svn_console2").append("(M) "+(num_fichier+1)+". "+file+" ");
						arguments = {
							action:'svn_put_file_in_repo',
							file : "<?php echo WP_PLUGIN_DIR."/".$plugin."/" ; ?>"+file, 
							urldepot : "<?php echo $result['putFolder']."/" ?>"+file
						} 
						jQuery.post(ajaxurl, arguments, function(response) { 
							num_fichier ++ ; 
							jQuery("#svn_console2").append(response+' ('+Math.floor(100*num_fichier/nb_fichier)+'%)<br/>');
							executeSVNloop() ; 
						}) ; 
					}
					
					// fichier DELETE
					else if (delete_file.length!=0) {
						var url = delete_file.pop();
						jQuery("#svn_console2").append("(D) "+(num_fichier+1)+". "+url+" ");
						arguments = {
							action: 'svn_delete_in_repo', 
							url : "<?php echo $result['putFolder']."/" ?>"+url 
						} 
						jQuery.post(ajaxurl, arguments, function(response) { 
							num_fichier ++ ; 
							jQuery("#svn_console2").append(response+' ('+Math.floor(100*num_fichier/nb_fichier)+'%)<br/>');
							executeSVNloop() ; 
						}) ; 
					}
					
					// folder DELETE
					else if (delete_folder.length!=0) {
						var url = delete_folder.pop();
						jQuery("#svn_console2").append("(D) "+(num_fichier+1)+". "+url+" (folder) ");
						arguments = {
							action: 'svn_delete_in_repo', 
							url : "<?php echo $result['putFolder']."/" ?>"+url 
						} 
						jQuery.post(ajaxurl, arguments, function(response) { 
							num_fichier ++ ; 
							jQuery("#svn_console2").append(response+' ('+Math.floor(100*num_fichier/nb_fichier)+'%)<br/>');
							executeSVNloop() ; 
						}) ; 
					}
				}
				executeSVNloop() ; 
			</script>
			<?php
			
		} else {
			echo "<p style='color:#CC0000;'>" ; 
				print_r($result) ; 
			echo "</p>" ; 
			echo "</div>" ; 
		}
		die () ; 
	}
	
	
	/** ====================================================================================================================================================
	* Callback for putting file into the repo
	* 
	* @access private
	* @return void
	*/		
	
	function svn_put_file_in_repo() {
		
		// get the arguments
		$file = $_POST['file'] ;
		$urldepot = $_POST['urldepot'] ;
		
		// SVN preparation
		$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
		$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->get_param('svn_login'), $this->get_param('svn_pwd') ) ; 
		
		// PUT the file
		$res = $svn->putFile($urldepot, $file , true) ; 
		if ($res['isOK']) {
			SL_Debug::log(get_class(), "The file ".$file." has been uploaded into the repository", 4) ; 
			echo " <span style='color:#669900'>OK</span>" ; 
		} else {
			SL_Debug::log(get_class(), "The file ".$file." cannot be uploaded into the repository", 2) ; 
			echo " <span style='color:#CC0000'>KO</span><br/>" ;
			echo 	"SVN header : <br/>" ; 
			print_r($res['svn_header']) ; 
			echo "<br/>" ; 
			echo $svn->printRawResult($res['raw_result']) ; 
		}
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback for putting folder into the repo
	* 
	* @access private
	* @return void
	*/		
	
	function svn_put_folder_in_repo() {
		// get the arguments
		$urlfolder = $_POST['urlfolder'] ;
		
		// SVN preparation
		$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
		$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->get_param('svn_login'), $this->get_param('svn_pwd') ) ; 
		
		// PUT the file
		$res = $svn->putFolder($urlfolder , true) ; 
		if ($res['isOK']) {
			SL_Debug::log(get_class(), "The folder ".$urlfolder." has been uploaded into the repository", 4) ; 
			echo " <span style='color:#669900'>OK</span>" ; 
		} else {
			SL_Debug::log(get_class(), "The folder ".$urlfolder." cannot be uploaded into the repository", 2) ; 
			echo " <span style='color:#CC0000'>KO</span><br/>" ; 
			echo $svn->printRawResult($res['raw_result']) ; 
		}
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback for deleting a folder or a file into the repo
	* 
	* @access private
	* @return void
	*/		
	
	function svn_delete_in_repo() {
		// get the arguments
		$url = $_POST['url'] ;
		
		// SVN preparation
		$local_cache = WP_CONTENT_DIR."/sedlex/svn" ; 
		$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->get_param('svn_login'), $this->get_param('svn_pwd') ) ; 
		
		// PUT the file
		$res = $svn->deleteFileFolder($url , true) ; 
		if ($res['isOK']) {
			SL_Debug::log(get_class(), "The file/folder ".$url." has been deleted from the repository", 4) ; 
			echo " <span style='color:#669900'>OK</span>" ; 
		} else {
			SL_Debug::log(get_class(), "The file/folder ".$url." cannot be deleted from the repository", 2) ; 
			echo " <span style='color:#CC0000'>KO</span><br/>" ; 
			echo $svn->printRawResult($res['raw_result']) ; 
		}
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback for merging the change in the repo
	* 
	* @access private
	* @return void
	*/		
	
	function svn_merge() {
		// get the arguments
		$root = $_POST['root'] ;
		$uuid = $_POST['uuid'] ;
		$activityFolder = $_POST['activityFolder'] ;
		
		// SVN preparation
		$svn = new svnAdmin("svn.wp-plugins.org", 80, $this->get_param('svn_login'), $this->get_param('svn_pwd') ) ; 
		
		// PUT the file
		$res = $svn->merge($root, $activityFolder.$uuid,  true) ; 
		if ($res['isOK']) {
			SL_Debug::log(get_class(), "The modification has been merged within the repository", 4) ; 
			echo " <span style='color:#669900'>".sprintf(__("The commit has ended [ %s ]... You should received an email quickly ! You may close the window or wait for the automatic closing.",$this->pluginID), $res['commit_info'])."</span>" ; 
			echo "<script>
				window.setTimeout('disablePopup()', 1000);
			</script>" ; 
		} else {
			SL_Debug::log(get_class(), "The modification cannot be merged within the repository", 2) ; 
			echo " <span style='color:#CC0000'>".__("The commit has ended but there is an error!",$this->pluginID)."</span>" ; 
			echo $svn->printRawResult($res['raw_result']) ; 
		}
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback for merging the change in the repo
	* 
	* @access private
	* @return void
	*/		
	
	function showTextDiff() {
		// get the arguments
		$file1 = $_POST['file1'] ;
		$file2 = $_POST['file2'] ;
		
		$text1 = "" ; 
		$text2 = "" ; 
		if (is_file($file1))
			$text1 = @file_get_contents($file1) ; 
		if (is_file($file2))
			$text2 = @file_get_contents($file2) ; 
		$textdiff = new textDiff() ; 
		$textdiff->diff($text2, $text1) ; 
		echo $textdiff->show_only_difference() ;
		die() ; 
	}

	/** ====================================================================================================================================================
	* Check an imap inbox folder to check if translations should be imported
	* 
	* @access private
	* @return void
	*/
	function update_all_pot_translations() {
		global $submenu ; 
	 		
		if (isset($submenu)) {
			foreach ($submenu['sedlex.php'] as $i => $ov) {
				$url = $ov[2] ; 
				$plugin_name = explode("/",$url) ;
				if (count($plugin_name)>=2) {
					$plug = $plugin_name[count($plugin_name)-2] ; 
				} else {
					$plug = "" ; 
				}
				$plugin_name[count($plugin_name)-1] = "lang" ; 
				$dir = WP_PLUGIN_DIR."/".implode("/", $plugin_name)."/" ; 
				if ($ov[2]=="sedlex.php") {
					$dir = SL_FRAMEWORK_DIR.'/core/lang/'; 
					$plug = __("Framework", $this->pluginID) ; 
				} 
				// We scan the folder for translations file
				if (is_dir($dir)) {
					$root = scandir($dir);
					foreach($root as $value) {
						if (preg_match("/(.*)[.]pot$/", $value, $match)) {
							// We update the langague file
							if ($match[1] !="SL_framework") {
								$domain = $match[1] ;
								translationSL::update_languages_plugin($domain,$plug) ; 
								echo "<p>".sprintf(__("Update the pot file for %s", $this->pluginID), "<code>$plug</code>")."</p>" ; 
							} else {
								translationSL::update_languages_framework() ; 
								echo "<p>".__("Update the pot file for the framework", $this->pluginID)."</p>" ; 
							}
						} 
					} 
				}
			}
		}
	}
	
	/** ====================================================================================================================================================
	* Check an imap inbox folder to check if translations should be imported
	* 
	* @access private
	* @return void
	*/
	function check_bug_translation() {
		global $submenu ; 
	
		// We identify which folders contains .tmp files 
		// and we identify the differences
		//---------------------------------------------------------
		
		$table = new adminTable() ;
		$table->title(array(__('Plugin', $this->pluginID), __('Language', $this->pluginID), __('Sentence', $this->pluginID), __('Translation', $this->pluginID) )) ;
		
		$nb_ligne = 0 ; 
 		
		if (isset($submenu)) {
			foreach ($submenu['sedlex.php'] as $i => $ov) {
				$url = $ov[2] ; 
				$plugin_name = explode("/",$url) ;
				if (count($plugin_name)>=2) {
					$plug = $plugin_name[count($plugin_name)-2] ; 
				} else {
					$plug = "" ; 
				}
				$plugin_name[count($plugin_name)-1] = "lang" ; 
				$dir = WP_PLUGIN_DIR."/".implode("/", $plugin_name)."/" ; 
				if ($ov[2]=="sedlex.php") {
					$dir = SL_FRAMEWORK_DIR.'/core/lang/'; 
					$plug = __("Framework", $this->pluginID) ; 
				} 
				// We scan the folder for translations file
				if (is_dir($dir)) {
					$root = scandir($dir);
					foreach($root as $value) {
						if (preg_match("/(.*)-(.*)[.]po$/", $value, $match)) {
							
							$content_po = file($dir.'/'.$value) ; 
							$lang = $match[2] ; 
							$domain = $match[1] ; 
							

		
							// We build an array with all the sentences for old po
							$po_array = array() ; 
							$msgid = "" ; 
							foreach ($content_po as $ligne_po) {
								if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
									$msgid = $match[1] ; 			
								} else if (preg_match("/^msgstr \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
									if (trim($match[1])!="") {
										$msgstr = trim($match[1]) ; 
										// We check the number of %s
										$count1 = substr_count($msgid, "%s") ; 
										$count2 = substr_count($msgstr, "%s") ;
										// If there is a mismatch
										if ($count1!=$count2) {
											$cel1 = new adminCell("<p>".$plug."</p>") ; 
											if ($ov[2]=="sedlex.php") {
												$framedir = explode("/", SL_FRAMEWORK_DIR) ; 
												$cel1->add_action(__('Modify',$this->pluginID), "modify_trans_dev('".$framedir[count($framedir)-1]."','".$domain."', '".$framedir[count($framedir)-1]."', '".$lang."')" ) ; 
											} else {
												$cel1->add_action(__('Modify',$this->pluginID), "modify_trans_dev('".$plug."','".$domain."', 'false', '".$lang."')" ) ; 
											}
											$cel2 = new adminCell("<p>".$lang."</p>") ; 
											$offset = 0 ; 
											for ($i=1 ; $i<=max($count1,$count2) ; $i++) {
												$offset = strpos($msgid, "%s", $offset);
												if ($offset!==FALSE) {
													if (($i<=$count1) && ($i<=$count2)) {
														$new_tag = "<span style='background-color:green'>%s</span>" ; 
														$msgid = substr($msgid, 0, $offset).$new_tag.substr($msgid, $offset+2) ; 
														$offset += strlen($new_tag)-2 ; 
													} else {
														$new_tag = "<span style='background-color:red '>%s</span>" ; 
														$msgid = substr($msgid, 0, $offset).$new_tag.substr($msgid, $offset+2) ; 
														$offset += strlen($new_tag)-2 ;
													}
												}
											}
											$offset = 0 ; 
											for ($i=1 ; $i<=max($count1,$count2) ; $i++) {
												$offset = strpos($msgstr, "%s", $offset);
												if ($offset!==FALSE) {
													if (($i<=$count1) && ($i<=$count2)) {
														$new_tag = "<span style='background-color:green'>%s</span>" ; 
														$msgstr = substr($msgstr, 0, $offset).$new_tag.substr($msgstr, $offset+2) ; 
														$offset += strlen($new_tag)-2 ; 
													} else {
														$new_tag = "<span style='background-color:red '>%s</span>" ; 
														$msgstr = substr($msgstr, 0, $offset).$new_tag.substr($msgstr, $offset+2) ; 
														$offset += strlen($new_tag)-2 ;
													}
												}
											}
											$cel3 = new adminCell("<p>".$msgid."</p>") ; 
											$cel4 = new adminCell("<p>".$msgstr."</p>") ; 
											$table->add_line(array($cel1, $cel2, $cel3, $cel4), '1') ; 
										}
									}
								}
							}
						}
					} 
				}
			}
		}
		echo $table->flush() ; 
		echo "<div id='zone_edit_dev'></div>" ; 

	}
	
	/** ====================================================================================================================================================
	* Check an imap inbox folder to check if translations should be imported
	* 
	* @access private
	* @return void
	*/
	function check_mail($server, $login, $password) {
		global $submenu ; 
				
		$imap = imap_open($server, $login, $password);
		
		$plugin_lang = array() ; 
				
		// We identify which mails have .po file in it 
		// and we import the file into the correct folders
		//---------------------------------------------------------
		
		$result = imap_search($imap, 'UNSEEN');
		$result_imported = "" ; 
		if ($result!==FALSE) {
			foreach ($result as $r) {
				$struct = imap_fetchstructure ( $imap , $r ) ; 
				if (isset($struct->parts)) {
					$num_part = 1 ; 
					foreach($struct->parts as $s) {
						if ((isset($s->disposition))&&($s->disposition == "attachment")) {
							if (preg_match("/po$/", $s->parameters[0]->value)) {
								if (preg_match("/(.*)-([a-z]{2}_[A-Z]{2})\.po/", $s->parameters[0]->value , $match)) {
									// We identify the $path
									$path_match = "" ;
									if ($match[1]!="SL_framework") {
										if (isset($submenu)) {
											foreach ($submenu['sedlex.php'] as $i => $ov) {
												$url = $ov[2] ; 
												$plugin_name = explode("/",$url) ;
												$plugin_name[count($plugin_name)-1] = "lang" ; 
												if (is_file(WP_PLUGIN_DIR."/".implode("/", $plugin_name)."/".$match[1].".pot")) {
													$path_match = WP_PLUGIN_DIR."/".implode("/", $plugin_name)."/" ; 
												}
											}
										}
									} else {
										$path_match = SL_FRAMEWORK_DIR.'/core/lang/'; 
									}
									// We know the path, now we write the file in the folder
									if ($path_match!="") {
										$att = imap_fetchbody($imap, $r, $num_part);
										if($s->encoding == 3) { // 3 = BASE64
											$att = base64_decode($att);
										}
										elseif($s->encoding == 4) { // 4 = QUOTED-PRINTABLE
											$att = quoted_printable_decode($att);
										}
										if ($att == "") {
											echo "<div class='error fade'>".sprintf(__('The file %s cannot be extracted from mail attachments',$this->pluginID),'<code>'.$match[0].'</code>')."</div>" ; 
											return ; 
										}
										$r = @file_put_contents($path_match.$match[0].".tmp".$r, $att) ; 
										if ($r===false) {
											echo "<div class='error fade'><p>".sprintf(__('The file %s cannot be written in the %s folder',$this->pluginID),'<code>'.$match[0].'</code>', '<code>'.$path_match.'</code>')."</p></div>" ; 
											return ; 
										}
										$result_imported .= "<p>".sprintf(__('The file %s has been imported in the %s folder',$this->pluginID),'<code>'.$match[0].".tmp".$r.'</code>', '<code>'.$path_match.'</code>')."</p>" ; 
									}
								}
							}
						}
						$num_part++ ; 
					}
				}
			}
		}
		if ($result_imported!="") {
			echo "<div class='updated fade'>".$result_imported."</div>" ; 
		}
		
		// We identify which folders contains .tmp files 
		// and we identify the differences
		//---------------------------------------------------------
		
		$table = new adminTable() ;
		$table->title(array(__('Plugin', $this->pluginID), __('Language', $this->pluginID), __('Information', $this->pluginID)) ) ;
		$table2 = new adminTable() ;
		$table2->title(array(__('Plugin', $this->pluginID), __('Language', $this->pluginID), __('Information', $this->pluginID)) ) ;
		
		$nb_ligne = 0 ; 
		$nb_ligne2 = 0 ; 
		
		if (isset($submenu)) {
			foreach ($submenu['sedlex.php'] as $i => $ov) {
				$url = $ov[2] ; 
				$plugin_name = explode("/",$url) ;
				$plugin_name[count($plugin_name)-1] = "lang" ; 
				$dir = WP_PLUGIN_DIR."/".implode("/", $plugin_name)."/" ; 
				if ($ov[2]=="sedlex.php") {
					$dir = SL_FRAMEWORK_DIR.'/core/lang/'; 
				}
				// We scan the folder for new translations file
				if (is_dir($dir)) {
					$root = scandir($dir);
					foreach($root as $value) {
						if (preg_match("/(.*)-(.*)[.]tmp([0-9]*)$/", $value, $match)) {
							$cible_file = $match[1]."-".$match[2] ; 
							if (!is_file($dir.$cible_file)) {
								// The sent translation is new and can be imported without difficulties 
								$info = translationSL::get_info(file($dir.$match[0]),file($dir.$match[1].".pot")) ; 
								if ($info['translated']!=0) {
									$cel1 = new adminCell("<p>".$match[1]." (n&deg;".$match[3].")</p>") ;
									$cel2 = new adminCell("<p>".str_replace(".po", "", $match[2])."</p>") ;
									$cel3 = new adminCell("<p>".(sprintf(__("%s sentences have been translated (i.e. %s).",$this->pluginID), "<b>".$info['translated']."/".$info['total']."</b>", "<b>".(floor($info['translated']/$info['total']*1000)/10)."%</b>"))."</p>") ;
									$cel1->add_action(__("Delete", $this->pluginID), "deleteTranslation('".$dir.$value."')") ; 
									$cel1->add_action(__("See the new translation file and merge",$this->pluginID) , "seeTranslation('".$dir.$value."', '".$dir.$cible_file."')") ; 
									$table->add_line(array($cel1, $cel2, $cel3), '1') ;
									$nb_ligne ++ ; 
								} else {
									@unlink($dir.$value) ; 
								}
							} else {
								// The sent translation is NOT new and it should be compare with the existing one before importing 
								$info = translationSL::compare_info(file($dir.$value),file($dir.$cible_file),file($dir.$match[1].".pot")) ; 
								$info2 = translationSL::get_info(file($dir.$cible_file),file($dir.$match[1].".pot")) ; 
								if (($info['modified']!=0) || ($info['new']!=0)) {
									$cel1 = new adminCell("<p>".$match[1]." (n&deg;".$match[3].")</p>") ;
									$cel2 = new adminCell("<p>".str_replace(".po", "", $match[2])."</p>") ;
									$cel3 = new adminCell("<p>".(sprintf(__("%s sentences have been newly translated and %s sentences have been modified (the old file has %s translated sentences).",$this->pluginID), "<b>".$info['new']."</b>", "<b>".$info['modified']."</b>", "<b>".$info2['translated']."/".$info2['total']."</b>"))."</p>") ;
									$cel1->add_action(__("Delete", $this->pluginID), "deleteTranslation('".$dir.$value."')") ; 
									if ($info['modified']!=0) {
										$cel1->add_action(__("See the modifications/new translations and Merge",$this->pluginID) , "seeTranslation('".$dir.$value."', '".$dir.$cible_file."')") ; 
									} else {
										$cel1->add_action(__("See the new translations and Merge",$this->pluginID) , "seeTranslation('".$dir.$value."', '".$dir.$cible_file."')") ; 
									}
									$table2->add_line(array($cel1, $cel2, $cel3), '1') ;
									$nb_ligne2 ++ ; 
								} else {
									@unlink($dir.$value) ; 
								}
							}
						}
					} 
				}
			}
		}
			
		if ($nb_ligne!=0) {
			echo $table->flush() ;
		} else {
			echo "<p>".__("No new translation.",$this->pluginID)."</p>" ; 		
		}
		if ($nb_ligne2!=0) {
			echo $table2->flush() ;
		} else {
			echo "<p>".__("No new update.",$this->pluginID)."</p>" ; 				
		}
		
		echo "<div id='console_trans'></div>" ; 
		
	}

	/** ====================================================================================================================================================
	* Callback for deleting a translation file
	* 
	* @access private
	* @return void
	*/
	
	function deleteTranslation() {
		$path1 = $_POST['path1'] ; 
		@unlink($path1) ; 	
		SL_Debug::log(get_class(), "Delete the translation ".$path1, 4) ; 
		die() ; 
	}
			
			
	/** ====================================================================================================================================================
	* Callback for seeing differnces bewteen translation files
	* 
	* @access private
	* @return void
	*/
	
	function seeTranslation() {
		$path1 = $_POST['path1'] ; 
		$path2 = $_POST['path2'] ; 
		
		$title = __('What are the differences between these two files?', $this->pluginID) ;
		
		$pathpot = preg_replace("/(.*)-(.*)[.]po*$/", "$1.pot" , $path2) ; 

		$content_pot = file($pathpot) ; 
		if (is_file($path2)) {
			$content_po2 = file($path2) ; 
		} else {
			$content_po2 = array() ; 
		}
		$content_po1 = file($path1) ; 
		
		echo "<span id='info_translation_merge'>" ; 
		ob_start() ;
			// We build an array with all the sentences for pot
			$pot_array = array() ; 
			$all_count = 0 ; 
			foreach ($content_pot as $ligne_pot) {
				if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_pot), $match)) {
					$pot_array[md5(trim($match[1]))] = trim($match[1]) ; 
					$all_count ++ ; 
				}
			}	

			// We build an array with all the sentences for old po
			$po2_array = array() ; 
			$msgid = "" ; 
			foreach ($content_po2 as $ligne_po) {
				if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
					$msgid = $match[1] ; 			
				} else if (preg_match("/^msgstr \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
					if (trim($match[1])!="") {
						$po2_array[md5(trim($msgid))] = array(trim($msgid),trim($match[1])) ; 
					}
				}
			}
			
			$table = new adminTable() ; 
			$table->title(array(__('Sentence to translate', $this->pluginID) , __('Old sentence', $this->pluginID), __('New sentence', $this->pluginID), __('To replace?', $this->pluginID)) ) ;
							
			// We build an array with all the sentences for new po
			$po1_array = array() ; 
			$msgid = "" ; 
			foreach ($content_po1 as $ligne_po) {
				if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
					$msgid = $match[1] ; 			
				} else if (preg_match("/^msgstr \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
					if (trim($match[1])!="") {
						$po1_array[md5(trim($msgid))] = array(trim($msgid),trim($match[1])) ; 
						if (isset($pot_array[md5(trim($msgid))])) {
						
							if (!isset($po2_array[md5(trim($msgid))][1]))
								$po2_array[md5(trim($msgid))][1] = "" ; 
							if (!isset($po1_array[md5(trim($msgid))][1]))
								$po1_array[md5(trim($msgid))][1] = "" ; 
								
							if ($po2_array[md5(trim($msgid))][1]!=$po1_array[md5(trim($msgid))][1]) {
								$cel1 = new adminCell("<p>".$msgid."</p>") ;
								$cel2 = new adminCell("<p>".$po2_array[md5(trim($msgid))][1]."</p>") ;
								$diff = new textDiff() ; 
								$diff->diff($po2_array[md5(trim($msgid))][1],$po1_array[md5(trim($msgid))][1]) ; 
								$cel3 = new adminCell("<p>".$diff->show_simple_difference()."</p>") ;
								$cel4 = new adminCell("<p><input type='CHECKBOX' name='new_".md5(trim($msgid))."' checked='yes' >Replace the old sentence with the new one?</input></p>") ;
								$table->add_line(array($cel1, $cel2, $cel3, $cel4), '1') ; 
							}
						}
					}
				}
			}
			echo $table->flush() ; 
			echo "<p><input type='submit' name='set' class='button-primary validButton' onclick='mergeTranslationDifferences(\"".$path1."\", \"".$path2."\");return false;' value='".__('Merge',$this->pluginID)."' />" ; 
			$x = WP_PLUGIN_URL.'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__)) ; 
			echo "<img id='wait_translation_merge' src='".$x."/img/ajax-loader.gif' style='display:none;'><p/>" ; 	
			echo "</span>" ; 
		
		$content = ob_get_clean() ; 	
							
		$popup = new popupAdmin($title, $content) ; 
		$popup->render() ; 
		die() ; 
	}
	
	/** ====================================================================================================================================================
	* Callback for importing a translation file with managing differences with the old one
	* 
	* @access private
	* @return void
	*/
	
	function mergeTranslationDifferences() {
		$md5 = $_POST['md5'] ; 
		$path1 = $_POST['path1'] ; 
		$path2 = $_POST['path2'] ; 
		
		$pathpot = preg_replace("/(.*)-(.*)[.]po*$/", "$1.pot" , $path2) ; 
		$lang = preg_replace("/(.*)-(.*)[.]po*$/", "$2" , $path2) ; 
		$content_pot = file($pathpot) ; 
		if (is_file($path2)) {
			$content_po2 = file($path2) ; 
		} else {
			$content_po2 = array() ; 
		}
		$content_po1 = file($path1) ; 
		
		$translators = array() ; 
		
		// We recreate a new po file according to the 2 files
		
		// We build an array with all the sentences for pot
		$pot_array = array() ; 
		$all_count = 0 ; 
		foreach ($content_pot as $ligne_pot) {
			if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_pot), $match)) {
				$pot_array[md5(trim($match[1]))] = trim($match[1]) ; 
				$all_count ++ ; 
			}
		}	

		// We build an array with all the sentences for old po
		$po2_array = array() ; 
		$msgid = "" ; 
		foreach ($content_po2 as $ligne_po) {
			if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
				$msgid = $match[1] ; 			
			} else if (preg_match("/^msgstr \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
				if (trim($match[1])!="") {
					$po2_array[md5(trim($msgid))] = array(trim($msgid),trim($match[1])) ; 
				}
			} else if (preg_match("/Last-Translator: (.*) \<(.*)\>/", trim($ligne_po), $match)) {
				$translators[md5(trim($match[0]))] = $match[0] ; 
			}
		}
						
		// We build an array with all the sentences for new po
		$po1_array = array() ; 
		$msgid = "" ; 
		foreach ($content_po1 as $ligne_po) {
			if (preg_match("/^msgid \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
				$msgid = $match[1] ; 			
			} else if (preg_match("/^msgstr \\\"(.*)\\\"$/", trim($ligne_po), $match)) {
				if (trim($match[1])!="") {
					$po1_array[md5(trim($msgid))] = array(trim($msgid),trim($match[1])) ; 
					if (isset($pot_array[md5(trim($msgid))])) {
						if (isset($po2_array[md5(trim($msgid))])) {
							if ($po2_array[md5(trim($msgid))][1]!=$po1_array[md5(trim($msgid))][1]) {
								// We check if we said that we want to replace it from the new file
								if (strpos($md5,"new_".md5(trim($msgid)))!==false) {
									$po2_array[md5(trim($msgid))] = array(trim($msgid),trim($match[1])) ;
								}
							}
						} else {
							$po2_array[md5(trim($msgid))] = array(trim($msgid),trim($match[1])) ;
						}
					}
				}
			} else if (preg_match("/Last-Translator: (.*) \<(.*)\>/", trim($ligne_po), $match)) {
				$translators[md5(trim($match[0]))] = $match[0] ; 
			}
		}
		
		// We create the po file
		
		require('core/translation.inc.php') ;
	
		$content = "" ; 
		$content .= "msgid \"\"\n" ; 
		$content .= "msgstr \"\"\n" ; 
		$content .= "\"Generated: SL Framework (http://www.sedlex.fr)\\n\"\n";
		$content .= "\"Project-Id-Version: \\n\"\n";
		$content .= "\"Report-Msgid-Bugs-To: \\n\"\n";
		$content .= "\"POT-Creation-Date: \\n\"\n";
		$content .= "\"PO-Revision-Date: ".date("c")."\\n\"\n";
		foreach ($translators as $translator) {
			$content .= "\"".$translator."\\n\"\n" ; 
		}		
		$content .= "\"Language-Team: \\n\"\n";
		$content .= "\"MIME-Version: 1.0\\n\"\n";
		$content .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
		$content .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
		$plurals = "" ; 
		foreach($countries as $code => $array) {
			if (array_search($lang, $array)!==false) {
				$plurals = $csp_l10n_plurals[$code] ; 
			}
		}
		$content .= "\"Plural-Forms: ".$plurals."\\n\"\n";
		$content .= "\"X-Poedit-Language: ".$code_locales[$lang]['lang']."\\n\"\n";
		$content .= "\"X-Poedit-Country: ".$code_locales[$lang]['country']."\\n\"\n";
		$content .= "\"X-Poedit-SourceCharset: utf-8\\n\"\n";
		$content .= "\"X-Poedit-KeywordsList: __;\\n\"\n";
		$content .= "\"X-Poedit-Basepath: \\n\"\n";
		$content .= "\"X-Poedit-Bookmarks: \\n\"\n";
		$content .= "\"X-Poedit-SearchPath-0: .\\n\"\n";
		$content .= "\"X-Textdomain-Support: yes\\n\"\n\n" ; 
		
		$hash = array() ; 
		foreach ($po2_array as $ligne) {
			$content .= 'msgid "'.$ligne[0].'"'."\n" ; 
			$content .= 'msgstr "'.$ligne[1].'"'."\n\n" ; 
			$hash[] = array('msgid' => $ligne[0], 'msgstr' => $ligne[1]) ; 
		}
		
		file_put_contents($path2,$content) ;
		
		SL_Debug::log(get_class(), "Write the mo file ".$path2, 4) ; 
		translationSL::phpmo_write_mo_file($hash,preg_replace("/(.*)[.]po/", "$1.mo", $path2)) ; 
		
		// We delete all cache file
		$path = preg_replace("/^(.*)\/([^\/]*)$/", "$1" , $path2) ; 
		
		$dir = @opendir(WP_CONTENT_DIR."/sedlex/translations"); 
		while(false !== ($item = readdir($dir))) {
			if ('.' == $item || '..' == $item)
				continue;
			if (preg_match("/\.html$/", $item, $h)) {
				@unlink (WP_CONTENT_DIR."/sedlex/translations/".$item);
			}
		}
		closedir($dir);
		
		// We force the update of the cache file for the given imported file
		ob_start() ; 
			$domain = preg_replace("/(.*)\/([^\/]*)-([^\/]*)[.]po$/", "$2" , $path2) ; 
			if ($domain!="SL_framework") {
				$plugin = preg_replace("/(.*)\/([^\/]*)\/lang\/(.*)po$/", "$2" , $path2) ; 
				translationSL::installed_languages_plugin($domain, $plugin) ; 
			} else {
				$plugin = preg_replace("/(.*)\/([^\/]*)\/core\/lang\/(.*)po$/", "$2" , $path2) ; 
				translationSL::installed_languages_framework($domain, $plugin) ; 
			}
		ob_get_clean() ; 
		
		echo "ok" ; 			
		die() ; 
	}



}

$dev_toolbox = dev_toolbox::getInstance();

?>