<?php
  require_once("lib/AbstractModule.php");

	class AdminManager extends AbstractModule {
		
		var $database = 'users';
		// var $sault = 'Plugins4ever';
    var $sault = '';
		
		var $loginError;
		var $passwordError;
		var $loginUpdate;
		var $passwordUpdate;
		var $error;
		


		function __construct($databaseManager) {
      $this->databaseManager = $databaseManager;
    }


		
		function firstInstall() {
			$query = 'CREATE TABLE IF NOT EXISTS `'.$this->database.'` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`login` varchar(50) NOT NULL,
					`password` varchar(50) NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
			
			$query = 'INSERT INTO `'.$this->database.'` (`id`, `login`, `password`) VALUES
				(1, "admin", "21232f297a57a5a743894a0e4a801fc3");';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
		}
		
		
		
		function preProcessPage($page, $action) {
			switch ($action)
			{
				case 'login':
					if (isset($_POST['login']))
					{
						$login=$_POST['login'];
						$password=md5($this->sault.$_POST['password']);
						$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, login, password FROM '.$this->database);
						while($donnees = mysqli_fetch_array($buff))
						{
							if ($login==$donnees['login'] && $password==$donnees['password'])
							{
								$_SESSION['connect']=1;
								header('Location: admin.php');
							}
						}
					}
					break;
			}
		}
		
		
		
		
		function preProcessAdmin($page, $action) {
			switch ($action)
			{
				case 'logout':
					$_SESSION['connect']=0;
					header('Location: admin.php');
					break;
					
				case 'changeId':
					if (isset($_POST['submit']))
					{
						$this->loginError = '';
						$this->passwordError = '';
						$this->loginUpdate = '';
						$this->passwordUpdate = '';
						$this->error = '';
						
						if ( !empty($_POST['oldId']) && !empty($_POST['oldPass']) )
						{
							$login=$_POST['oldId'];
							$password=md5($this->sault.$_POST['oldPass']);
							$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, login, password FROM '.$this->database);
							$id = 0;
							while ( $data = mysqli_fetch_array($buff) )
							{
								if ($login==$data['login'] && $password==$data['password'])
								{
									$id = $data['id'];
								}
							}
							
							if ( !empty($id) )
							{
								if ( !empty($_POST['newId']) )
								{
									if ( $_POST['newId']==$_POST['newId2'] )
									{
										$newId = mysqli_real_escape_string($_POST['newId']);
										mysqli_query($this->databaseManager->mysqli, 'UPDATE '.$this->database.' SET login = "'.$newId.'" WHERE id = '.$id) OR DIE (mysqli_error());
										$this->loginUpdate = 'Le changement d\'identifiant � bien �t� effectu�';
									}
									else
									{
										$this->loginError = 'Les identifiants doivent �tre identiques.';
									}
								}
								
								if ( !empty($_POST['newPass']) )
								{
									if ( !empty($_POST['newPass']) && $_POST['newPass']==$_POST['newPass2'] )
									{
										$newPass = md5($this->sault.$_POST['newPass']);
										mysqli_query($this->databaseManager->mysqli, 'UPDATE '.$this->database.' SET password = "'.$newPass.'" WHERE id = '.$id) OR DIE (mysqli_error());
										$this->passwordUpdate = 'Le changement de mot de passe � bien �t� effectu�.';
									}
									else
									{
										$this->passwordError = 'Les password doivent �tre identiques.';
									}
								}
							}
						}
						else
						{
							$this->error = 'Vous devez renseigner vos identifiants et password de connexion.';
						}
					}
					break;
			}
		}
		
		
		
		function displayAdmin($page, $action) {
			switch ($action)
			{
				case 'changeId':

?>
				<form method="post" action="?page=<?php echo $this->getName(); ?>&action=changeId">
					<fieldset class="center" >
						<legend>Rappel de l'identifiant et mot de passe actuel</legend>
						<p>
							Doit �tre rempli pour pouvoir proc�der au changement d'une quelconque donn�e.
						</p>
						<p>
							<label for="oldId"><strong>Identifiant de connexion :</strong></label><br/>
							<input type="text" name="oldId" id="oldId" class="inputText" />
						</p>
						<p>
							<label for="oldPass"><strong>Mot de passe :</strong></label><br/>
							<input type="password" name="oldPass" id="oldPass" class="inputText" />
						</p>
						<span class="error"><?php echo $this->error; ?></span>
					</fieldset>
					<fieldset class="center" >
						<legend>Changer l'identifiant</legend>
						<p>
							<label for="newId" >Nouvel identifiant : </label>
							<input type="text" name="newId" id="newId" class="inputText" value="<?php echo (isset($_POST['newId']) && empty($this->loginUpdate))? $_POST['newId']: ''; ?>" /><br/>
							<label for="newId2" >Nouvel identifiant : </label>
							<input type="text" name="newId2" id="newId2" class="inputText" value="<?php echo (isset($_POST['newId2']) && empty($this->loginUpdate))? $_POST['newId2']: ''; ?>" /><br/>
							<span class="error"><?php echo $this->loginError; ?></span>
						</p>
					</fieldset>
					<fieldset class="center" >
						<legend>Changer le mot de passe</legend>
						<p>
							<label for="newPass" >Nouveau mot de passe : </label>
							<input type="password" name="newPass" id="newPass" class="inputText" /><br/>
							<label for="newPass2" >Nouveau mot de passe : </label>
							<input type="password" name="newPass2" id="newPass2" class="inputText" /><br/>
							<span class="error"><?php echo $this->passwordError; ?></span>
						</p>
					</fieldset>
					<p>
						<input type="submit" name="submit" />
						<span class="error"><?php echo $this->loginUpdate; ?> <?php echo $this->passwordUpdate; ?></span>
					</p>
				</form>
<?php

					break;
			}
		}
		
		
		
		function displayPage($page, $action) {

?>
			<form method="post" action="?page=<?php echo $this->getName(); ?>&action=login">
				<fieldset class="center" >
					<legend>Administration</legend>
					<p>
						<label for="login">identifiez-vous : </label>
						<input type="text" name="login" id="login" />
					</p>
					<p>
						<label for="password">mot de passe : </label>
						<input type="password" name="password" id="password" /><br/>
					</p>
					<p>
						<input  type="submit" name="submit" />
					</p>
				</fieldset>
			</form>
<?php

		}
		
		
		
		function displayMenuAdmin($page = null, $menu = null) {
			if ($_SESSION['connect'])
			{

?>
			<li><a href="?page=<?php echo $this->getName(); ?>&action=logout" title="D�connexion" >D�connexion</a></li>
			<li><a href="?page=<?php echo $this->getName(); ?>&action=changeId" title="Changer identifiant et password" >Changer identifiant et password</a></li>
<?php

			}
			else
			{

?>
			<li><a href="?action=login" title="Connexion" >Connexion</a></li>
<?php

			}
		}
		
	}