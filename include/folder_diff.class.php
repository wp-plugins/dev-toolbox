<?php
/*
Core SedLex Plugin
VersionInclude : 3.0
*/ 

/** =*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*=*
* This PHP class allows to compare two folder to find differences
*/
if (!class_exists("foldDiff")) {
	class foldDiff {
		
		var $folder = array() ; 
		var $folder_show = array() ; 
		var $rep1 = "" ; 
		var $rep2 = "" ; 
		
		/** ====================================================================================================================================================
		* Constructor
		* 
		* @access private
		* @return void
		*/
		function foldDiff($plugID) {
			$this->folder = array() ; 
			$this->folder_show = array() ; 
			$this->pluginID = $plugID ; 
		}
		
		/** ====================================================================================================================================================
		* Compute differences between the two folders
		* 
		* @param string $path1 the path of the first folder
		* @param string $path2 the path of the second folder
		* @param integer $niveau the level of recursion
		* @param boolean $racine true if it the the first level
		* @return void
		*/
		
		function diff( $path1, $path2 , $racine=true){
			
			$path1 = str_replace("//","/",$path1) ;
			$path2 = str_replace("//","/",$path2) ;

			if ($racine) {
				$this->rep1 = $path1."/" ; 
				$this->rep2 = $path2."/" ; 
			}
			
			$result = array() ; 
			
			// On liste les fichiers qui existe dans le $path1 mais pas dans le $path2
			if (is_dir($path1)) {
				$d1 = @scandir( $path1 );		
				foreach ($d1 as $file) {
					if (!preg_match("@^\..*@",$file)) {
						if ((!is_file($path2."/".$file))&&(!is_dir($path2."/".$file))) {
							if (is_file($path1."/".$file)) {
								if (!$this->isBinary($path1."/".$file)) {
									$result[] = array($file,2,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								} else {
									$result[] = array($file,2,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								}
							} else {
								//Recursive
								$sub_result = $this->diff($path1."/".$file, $path2."/".$file, false) ; 
								$result[] = array($file."/",2,"directory", str_replace($this->rep2,"",$path2."/".$file."/"), $sub_result) ; 
							}
						}
					}
				}
			}
			
			
			// On liste les fichiers qui existe dans le $path2 mais pas dans le $path1
			if (is_dir($path2)) {
				$d2 = @scandir( $path2 );		
				foreach ($d2 as $file) {
					if (!preg_match("@^\..*@",$file)) {
						if ((!is_file($path1."/".$file))&&(!is_dir($path1."/".$file))) {
							if (is_file($path2."/".$file)) {
								if (!$this->isBinary($path2."/".$file)) {
									 $result[] = array($file,1,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								} else {
									 $result[] = array($file,1,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
								}
							} else {
								//Recursive
								$sub_result = $this->diff($path1."/".$file, $path2."/".$file, false) ;  
								$result[] = array($file."/",1,"directory", str_replace($this->rep2,"",$path2."/".$file."/"), $sub_result) ; 
							}
						} else {
							if (is_file($path2."/".$file)) {
								// on regarde si les fichiers sont identiques
								if (sha1_file($path2."/".$file)==sha1_file($path1."/".$file)) {
									if (!$this->isBinary($path2."/".$file)) {
										 $result[] = array($file,0,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									} else {
										 $result[] = array($file,0,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									}
								} else {
									if (!$this->isBinary($path2."/".$file)) {
										 $result[] = array($file,3,"text_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									} else {
										 $result[] = array($file,3,"binary_file", str_replace($this->rep2,"",$path2."/".$file)) ; 
									}
								}
							} else {
								//Recursive
								$sub_result = $this->diff($path1."/".$file, $path2."/".$file, false) ;  
								$result[] = array($file."/",0,"directory", str_replace($this->rep2,"",$path2."/".$file."/"),$sub_result) ; 
							}
						}
					}
				}
			}
			if ($racine==true) {
				$this->folder = $result ; 
				return ;
			} else {
				return $result ; 
			}
			
		}
		
		/** ====================================================================================================================================================
		* Display the difference
		* 
		* @param boolean $withTick display ticks 
		* @param boolean $closeNotModifiedFolders close folders if their contents have not been modified
		* @param boolean $withRandom set to true if there are a plurality of rendering
		* @return string the random number that should be used to know which button has been clicked
		*/
		
		function render($closeNotModifiedFolders=true, $withTick=false, $withRandom=false) {
			$random = "" ; 
			if ($withRandom) {
				$random = sha1(microtime()) ; 
			}
		
			// On affiche les repertoires
			$rep_current = $this->folder ; 
			$prev_fold = "" ; 
			$reduire = "<script>\r\n" ; 
			$hasmodif = array() ; 
			$foldlist = array() ; 
			$niveau = 0 ; 
			
			if ($withTick) {
				echo "<p>" ; 
				echo "<input class='button-secondary action' onClick='allTick".$random."(true)' value='".__('Select all', $this->pluginID)."'>"  ; 
				echo "&nbsp; <input class='button-secondary action' onClick='allTick".$random."(false)' value='".__('Un-select all', $this->pluginID)."'>" ; 
				echo "</p>"  ; 
				?>
				<script>
				function allTick<?php echo $random ; ?>(val) {
					jQuery('.toDelete<?php echo $random ; ?>').attr('checked', val);
					jQuery('.toDeleteFolder<?php echo $random ; ?>').attr('checked', val);
					jQuery('.toPut<?php echo $random ; ?>').attr('checked', val);
					jQuery('.toPutFolder<?php echo $random ; ?>').attr('checked', val);
					jQuery('.toModify<?php echo $random ; ?>').attr('checked', val);
					return false ; 
				}
				</script>
				<?php
			}
			?>
			<script>
				function diffToggle<?php echo $random ; ?>(num) {
					
					jQuery.fn.fadeThenSlideToggle = function(speed, easing, callback) {
						if (this.is(":hidden")) {
							return this.slideDown(speed, easing).fadeTo(speed, 1, easing, callback);
						} else {
							return this.fadeTo(speed, 0, easing).slideUp(speed, easing, callback);
						}
					};
					
					jQuery("#diff_"+num).fadeThenSlideToggle(500);

					return false ; 
				}
			</script>
			<?php
			$result = array() ; 
			foreach ($this->folder as $item) {
				$result[] = $this->sub_render($item, $closeNotModifiedFolders, $withTick, $random) ; 
			}
			
			SLFramework_Treelist::render($result, true) ;  	
			
			if ($withTick) {
				echo "<p><input class='button-secondary action' onClick='allTick".$random."(true)' value='".__('Select all', $this->pluginID)."'>"  ; 
				echo "&nbsp; <input class='button-secondary action' onClick='allTick".$random."(false)' value='".__('Un-select all', $this->pluginID)."'></p>"  ; 
			}
			
			return $random ; 
		}
		
		/** ====================================================================================================================================================
		* Display the difference (sub-recursive function)
		* 
		* @access private
		* @param boolean $withTick display ticks 
		* @param boolean $closeNotModifiedFolders close folders if their contents have not been modified
		* @param boolean $random the random number used for a plurality of displaying
		* @return array to be used with SLFramework_Treelist class
		*/
		
		function sub_render($item, $closeNotModifiedFolders=true, $withTick=false, $random="") {
			$color = "" ; 
			$binary = "" ;
			$loupe = "" ; 
			$tick = "" ;
			$icone = "<img style='border:0px' src='".plugin_dir_url("/").'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."../img/default.png'/>\n" ; 
			
			// Test modification
			//----------------------
			if (($item[1]==1)) {
				$color = "color:red;text-decoration:line-through;" ; 
				if ($withTick)
					$tick = "<input class='toDelete".$random."' type='checkbox' name='toDelete".$random."' value='".$item[3]."' checked />" ; 
				if ($item[2]=="directory") 
					$tick = "<input class='toDeleteFolder".$random."' type='checkbox' name='toDeleteFolder".$random."' value='".$item[3]."' checked />" ; 
			}
			if (($item[1]==2)) {
				$color = "color:green;" ; 
				if ($withTick)
					$tick = "<input class='toPut".$random."' type='checkbox' name='toPut".$random."' value='".$item[3]."' checked >" ; 
				if ($item[2]=="directory") 
					$tick = "<input class='toPutFolder".$random."' type='checkbox' name='toPutFolder".$random."' value='".$item[3]."' checked />" ; 
			}
			if (($item[1]==3)) {
				$color = "color:blue;" ; 
				if ($withTick)
					$tick = "<input class='toModify".$random."' type='checkbox' name='toModify".$random."' value='".$item[3]."' checked >" ; 
			}

			//Test type of files
			//------------------------
			if ($item[2]=="binary_file") {
				$binary = "*" ; 
				$icone = "<img style='border:0px' src='".plugin_dir_url("/").'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."../img/binary.png'/>\n" ; 
			}	
			
			if (preg_match("/\.php$/i", $item[0]))
				$icone = "<img style='border:0px' src='".plugin_dir_url("/").'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."../img/php.png'/>\n" ; 
			
			if (preg_match("/\.(gif|png|jpg|jpeg)$/i", $item[0])) 
				$icone = "<img style='border:0px' src='".plugin_dir_url("/").'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."../img/img.png'/>\n" ; 

			if ($item[2]=="directory") {
				$icone = "<img style='border:0px' src='".plugin_dir_url("/").'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."../img/folder.png'/>\n" ;  ; 
			}
			
			//Test whether the text diff should be displayed
			//---------------------------------------------------
			$text_diff = "" ; 
			if ((($item[1]==3)||($item[1]==2)||($item[1]==1))&&($item[2]=="text_file")) {
				$loupe =  "<a href='#' onclick='diffToggle".$random."(\"".sha1($item[3].$random)."\") ; return false ; '>" ; 
				$loupe .= "<img style='border:0px' src='".plugin_dir_url("/").'/'.str_replace(basename(  __FILE__),"",plugin_basename( __FILE__))."../img/loupe.png'/>"  ; 
				$loupe .=  "</a>\n" ; 
				$text_diff = "<div id='diff_".sha1($item[3].$random)."' style='display:none;padding:0px;margin:0px;'>\n" ; 

					$text_diff .= "<div id='wait_diff_".sha1($item[3].$random)."' style='display:none;' ><img src='".plugin_dir_url("/").'/'.str_replace(basename(__FILE__),"",plugin_basename(__FILE__))."../core/img/ajax-loader.gif'> ".__('Retrieving the modification of this file...', $this->pluginID)."</div>" ; 
					ob_start() ; 
?>
					<script>
						setTimeout("showTextDiff<?php echo sha1($item[3].$random) ?>()", Math.floor(Math.random()*4000)); 
						function showTextDiff<?php echo sha1($item[3].$random) ?>() {
							showTextDiff('<?php echo sha1($item[3].$random) ; ?>', '<?php echo $this->rep1.$item[3] ; ?>', '<?php echo $this->rep2.$item[3] ; ?>') ; 
						}
					</script>

<?php
					$text_diff .= ob_get_clean() ; 
					

				$text_diff .="</div>\n" ; 
			}	
			
			// Construct the result array
			$result_text = $icone.$tick."<span style='".$color."'>".$item[0].$binary."</span> ".$loupe.$text_diff ; 
			$id = "id".sha1($result_text) ; 
			if (count($item)<=4) {
				return array($result_text, $id) ; 
			} else {
				$child_result = array() ; 
				$isModif = $this->isModifications($item[4]) ; 
				foreach ($item[4] as $i) {
					$child_result[] = $this->sub_render($i, $closeNotModifiedFolders, $withTick, $random) ; 
				}
				return array($result_text, $id, $child_result, $isModif) ; 
			}
		}

		/** ====================================================================================================================================================
		* Test if there is a modification in children
		* 
		* @param array $children the children node
		* @access private
		* @return boolean true if there is a modification
		*/
		
		function isModifications($children) {
			foreach ($children as $c) {
				if ($c[1]!=0)
					return true ; 
				if ($c[2]=="directory") {
					$resu = $this->isModifications($c[4]) ;
					if ($resu)
						return true ;  
				}
			}
			return false ; 
		}
		/** ====================================================================================================================================================
		* Test if a file is binary
		* 
		* @param string $file path to the file to test
		* @access private
		* @return void
		*/
		
		function isBinary($file) {
			if (file_exists($file)) {
				if (!is_file($file)) return 0;
				if (preg_match("/\.(gif|png|jpg|jpeg)$/i", trim($file))) return 1 ; 

				$fh = fopen($file, "r");
				$blk = fread($fh, 512);
				fclose($fh);
				clearstatcache();

				return (0 or substr_count($blk, "^ -~")/512 > 0.3 or substr_count($blk, "^\r\n")/512 > 0.3 or substr_count($blk, "\x00") > 0);
			}
			return 0;
		} 	
	}
}

?>