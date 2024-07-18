<?php

	class ContentManager extends AbstractModule {
		
		var $database = 'content_manager';
		
		
		
		function firstInstall() {
			$query = 'CREATE TABLE IF NOT EXISTS `'.$this->database.'` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`page` int(11) NOT NULL,
					`texte` text NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';
			mysql_query($query) OR DIE (mysql_error());
			
			$query = 'INSERT INTO `'.$this->database.'` (`id`, `page`, `texte`) VALUES
				(1, 1, "&lt;p style=&quot;text-align: center;&quot;&gt;&lt;a href=&quot;admin.php&quot;&gt;Connectez-vous&lt;/a&gt;&amp;nbsp;pour administrer cette page et l&#039;ensemble du site.&lt;/p&gt;&lt;p style=&quot;text-align: center;&quot;&gt;Une fois connect&amp;eacute;, changer vos identifiant et password de connexion.&lt;/p&gt;");';
			mysql_query($query) OR DIE (mysql_error());
		}
		
		
		
		function preProcessAdmin($page, $action) {
			switch($action)
			{
				case 'store':
					if (isset($_POST['elm1']))
					{
						$texte = htmlentities($_POST['elm1'], ENT_QUOTES);
						$buff = mysql_query('SELECT id FROM '.$this->database.' WHERE page="'.$page.'"');
						$donnees = mysql_fetch_array($buff);
						if(empty($donnees))
						{
							mysql_query('INSERT INTO '.$this->database.' (id, page, texte) VALUES("", "'.$page.'", "'.$texte.'")') OR DIE (mysql_error());
						}
						else
						{
							$id = $donnees['id'];
							mysql_query('UPDATE '.$this->database.' SET texte = "'.$texte.'" WHERE id = '.$id) OR DIE (mysql_error());
						}
					}
					break;
			}
		}
		
		
		
		function displayPage($page, $action) {
			$buff = mysql_query('SELECT texte FROM '.$this->database.' WHERE page="'.$page.'"');
			$donnees = mysql_fetch_array($buff);
			echo html_entity_decode($donnees['texte']);
		}
		
		
		
		function displayAdmin($page, $action) {
			$buff = mysql_query('SELECT texte FROM '.$this->database.' WHERE page="'.$page.'"');
			$donnees = mysql_fetch_array($buff);

?>
				<script type="text/javascript" src="tiny_mce/tiny_mce.js"></script>
				<script type="text/javascript">
tinyMCE.init({
	// General options
	language : "fr", 
	mode : "textareas",
	theme : "advanced",
	skin : "o2k7",
	plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,wordcount,internalimage",

	// Theme options
	theme_advanced_buttons1 : "newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
	theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
	theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,|,sub,sup,|,charmap,emotions,media,advhr,|,print,|,fullscreen,|,internalimage",
	theme_advanced_blockformats : "h1,h2,h3,h4,h5,h6",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	theme_advanced_styles : "Gauche=left;Droite=right",
	
	// Replace values for the template plugin
	template_replace_values : {}
});
				</script>
				<form method="post" action="?page=<?php echo $page; ?>&action=store" enctype="multipart/form-data" id="editeur" >
					<textarea id="elm1" name="elm1"><?php echo $donnees['texte']; ?></textarea>
					<input type="submit" />
					<input type ="reset" />
				</form>
<?php

		}
		
	}