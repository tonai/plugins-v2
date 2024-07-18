<?php

	class DatabaseManager {
	
		var $file = 'admin/config.xml';
		var $config;
		var $replacement = array(
			'A' => '/&Agrave;|&Aacute;|&Acirc;|&Atilde;|&Auml;|&Aring;/',
			'a' => '/&agrave;|&aacute;|&acirc;|&atilde;|&auml;|&aring;/',
			'C' => '/&Ccedil;/',
			'c' => '/&ccedil;/',
			'E' => '/&Egrave;|&Eacute;|&Ecirc;|&Euml;/',
			'e' => '/&egrave;|&eacute;|&ecirc;|&euml;/',
			'I' => '/&Igrave;|&Iacute;|&Icirc;|&Iuml;/',
			'i' => '/&igrave;|&iacute;|&icirc;|&iuml;/',
			'N' => '/&Ntilde;/',
			'n' => '/&ntilde;/',
			'O' => '/&Ograve;|&Oacute;|&Ocirc;|&Otilde;|&Ouml;/',
			'o' => '/&ograve;|&oacute;|&ocirc;|&otilde;|&ouml;/',
			'U' => '/&Ugrave;|&Uacute;|&Ucirc;|&Uuml;/',
			'u' => '/&ugrave;|&uacute;|&ucirc;|&uuml;/',
			'Y' => '/&Yacute;/',
			'y' => '/&yacute;|&yuml;/',
			'_' => '/\s/'
		);
		
		var $hostname;
		var $database;
		var $user;
		var $password;
		
		
		
		function BaseDeDonnees() {
		}
		
		
		
		function isInstalled() {
			if (file_exists($this->file))
			{
				$this->config = simplexml_load_file($this->file);
				if (!empty($this->config->hostname)
				    && !empty($this->config->database)
					&& !empty($this->config->user)
					&& isset($this->config->password))
				{
					$this->init();
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		
		function install() {
			require_once("include/EnteteHTML.php");
			$enteteHTML = new EnteteHTML();
			$enteteHTML->display('Installation');

?>
	<body>
		<div id="page">
			<div id="right">
				<div id="header">
				</div>
			</div>
			<div id="body">
<?php

			$action = ((isset($_GET['action']))? $_GET['action']: false);
			switch ($action)
			{
				case 'install':
					if (!empty($_POST['hostname'])
					    && !empty($_POST['database'])
						&& !empty($_POST['user'])
						&& isset($_POST['password']))
					{
						if (empty($this->config))
						{
							$file = fopen($this->file, 'w');
							fwrite($file, '<?xml version="1.0" encoding="ISO-8859-1"?><bdd></bdd>'); 
							fclose($file);
							$this->config = simplexml_load_file($this->file);
						}
						
						$this->config->addChild('hostname', $_POST['hostname']);
						$this->config->addChild('database', $this->sanitize($_POST['database']));
						$this->config->addChild('user', $this->sanitize($_POST['user']));
						$this->config->addChild('password', $_POST['password']);
						$file = fopen($this->file, 'w');
						fwrite($file, $this->config->asXML()); 
						fclose($file);
						
						
						$this->init();
						$this->connexion();
						$path='lib';
						$dossier=opendir($path);
						while ($file=readdir($dossier))
						{
							if ($file!='.' && $file!='..')
							{
								$filename = substr($file, 0, strrpos($file, '.'));
								require_once($path.'/'.$filename.'.php');
								$lib = new $filename();
								$method = 'firstInstall';
								if (method_exists($lib, $method))
								{
									$lib->$method();
								}
							}
						}
						closedir($dossier);
						$this->deconnexion();
					}
					header('Location: index.php');
					break;
				
				default:

?>
				<form action="?action=install" method="post">
				<fieldset class="center">
					<legend>installation</legend>
					<p>
						<label for="hostname">hostname : </label>
						<input type="text" id="hostname" name="hostname">
					</p>
					<p>
						<label for="database">database : </label>
						<input type="text" id="database" name="database"><br>
					</p>
					<p>
						<label for="user">user : </label>
						<input type="text" id="user" name="user">
					</p>
					<p>
						<label for="password">password : </label>
						<input type="password" id="password" name="password"><br>
					</p>
					<p>
						<input type="submit" name="submit">
					</p>
				</fieldset>
			</form>
<?php

					break;
			}

?>
			</div>
		</div>
	</body>
</html>
<?php

		}
		
		
		
		function init() {
			$this->hostname = $this->config->hostname;
			$this->database = $this->config->database;
			$this->user = $this->config->user;
			$this->password = $this->config->password;
		}
		
		
		
		function connexion() {
			if ( empty($this->config) )
			{
				$this->config = simplexml_load_file($this->file);
				$this->init();
			}
			mysql_connect($this->hostname, $this->user, $this->password) or die (mysql_error());
			mysql_select_db($this->database) or die (mysql_error());
		}
		
		
		
		function deconnexion() {
			mysql_close();
		}
		
		
		
		function sanitize($name) {
			$name = preg_replace($this->replacement, array_keys($this->replacement), htmlentities($name, ENT_NOQUOTES));
			$name = preg_replace('#[^\w-_]#', '', $name);
			$name = preg_replace('#_{2,}#', '_', $name);
			return $name;
		}
		
	}