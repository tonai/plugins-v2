<?php

	class EnteteHTML {
		var $title = 'Plugin\'s';
		var $charset = 'charset=iso-8859-1';
		var $auteur = 'Tony CABAYE';
		var $description = 'site de l\'association Plugin\'s, groupe de musiciens pop-rock dans le Nord (59)';
		var $keywords = 'Plugin\'s, Jean-Pierre, Fred, Hervé, Bruno, Bernard, musique, nord, lille, groupe, band, cover, pop, rock';
		var $path = 'style/';
		var $css = 'style.css';
		
		
		
		function display($title, $modules = array(), $currentModule =null) {

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
	<head>
		<title><?php echo $this->title.(!empty($title)? ' - '.htmlentities($title): ''); ?></title>
		<meta http-equiv="Content-Type" content="text/html; <?php echo $this->charset; ?>" />
		<meta name="author" content="<?php echo $this->auteur; ?>" />
		<meta name="description" content="<?php echo $this->description; ?>" />
		<meta name="keywords" content="<?php echo $this->keywords; ?>" />
		<link rel="stylesheet" media="screen" type="text/css" title="Style" href="<?php echo $this->path.$this->css; ?>" />
<?php

			$path='script';
			$dossier=opendir($path);
			$jsFiles = array();
			while ($file=readdir($dossier))
			{
				if ($file!='.' && $file!='..')
				{
					$extension=substr($file, strrpos($file, '.')+1);
					if ($extension=='js')
					{
						$name = substr($file, 0, strrpos($file, '.'));
						$jsFiles[$name] = $file;
					}
				}
			}
			closedir($dossier);
			
			foreach($modules as $module)
			{
				if ( (isset($jsFiles[$module]) && $module!=$currentModule) || $_SESSION['connect'] )
				{
					unset($jsFiles[$module]);
				}
			}
			
			foreach ($jsFiles as $file)
			{
				echo '<script type="text/javascript" src="'.$path.'/'.$file.'" ></script>';
			}

?>
	</head>
<?php

		}
	}