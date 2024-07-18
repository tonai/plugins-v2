<?php

	class Livredor extends AbstractModule {
		
		var $database;
		
		var $save;
		var $delete;
		var $nameError;
		var $messageError;
		
		
		
		function install($data) {
			$query = 'CREATE TABLE IF NOT EXISTS `'.$this->sanitize($data['database']).'` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`nom` varchar(20) NOT NULL DEFAULT \'\',
				`message` text NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';
			mysql_query($query) OR DIE (mysql_error());
		}
		
		
		
		function uninstall() {
			$query = 'DROP TABLE IF EXISTS `'.$this->database.'`;';
			mysql_query($query) OR DIE (mysql_error());
		}
		
		
		
		function setAdditionalData($data) {
			if(!empty($data))
			{
				foreach($data as $key => $value)
				{
					$this->$key = $this->sanitize($value);
				}
			}
		}
		
		
		
		function getAdditionalData() {
			return array(
				'database' => array(
					'type' => 'text',
					'label' => htmlentities('Base de données'),
				),
			);
		}
		
		
		
		function preProcessPage($page, $action) {
			switch($action)
			{
				case 'ecrire':
					if (isset($_POST['nom']))
					{
						$this->nameError='';
						$this->messageError='';
						$this->save='';
						
						if (!empty($_POST['nom']) and !empty($_POST['message']))
						{
							$nom = mysql_real_escape_string($_POST['nom']);
							$message = mysql_real_escape_string($_POST['message']);
							mysql_query('INSERT INTO '.$this->database.' VALUES("", "'.$nom.'", "'.$message.'")') OR DIE (mysql_error());
							
							$this->save = 'Votre message à bien été enregistré.';
						}
						if (empty($_POST['nom']))
						{
							$this->nameError = 'Veillez remplir ce champ.';
						}
						if (empty($_POST['message']))
						{
							$this->messageError = 'Veillez remplir ce champ.';
						}
					}
					break;
			}
		}
		
		
		
		function preProcessAdmin($page, $action) {
			switch($action)
			{
				case 'suppression':
					if (isset($_POST['submit']))
					{
						$this->delete = '';
						$reponse=mysql_query('SELECT id FROM '.$this->database);
						while($donnees=mysql_fetch_array($reponse))
						{
							$id = intval($donnees['id']);
							if(isset($_POST[$id]))
							{
								mysql_query('DELETE FROM '.$this->database.' WHERE id='.$id) OR DIE (mysql_error());
								$this->delete = 'Suppression(s) effectuée(s)..';
							}
						}
					}
					break;
			}
		}
		
		
		
		function displayPage($page, $action) {
			$action=false;
			if (isset($_GET['action']))
			{
				if($_GET['action']=='ecrire')
					$action=true;
			}
			
			if ($action)
			{
?>
				<form method="post" action="?page=<?php echo $page; ?>&action=ecrire">
					<fieldset>
						<legend>Ecrire un message dans le livre d'or</legend>
							<p>
								<a href="?page=<?php echo $page; ?>" >Retourner aux messages</a>
							</p>
							<p class="center" >
								<label for="nom" >Votre nom : </label>
								<input type="text" name="nom" value="<?php echo (isset($_POST['nom']) && $this->save=='')? $_POST['nom']: ''; ?>" />
								<span class="error" ><?php echo $this->nameError; ?></span>
							</p>
							<p>
								<label for="nom" >Votre message : </label>
								<span class="error" ><?php echo $this->messageError; ?></span><br/>
								<textarea name="message" ><?php echo (isset($_POST['message']) && $this->save=='')? $_POST['message']: ''; ?></textarea>
							</p>
								<input type="submit" name="submit" class="submit" />
								<span class="error" ><?php echo $this->save; ?></span>
							</p>
					</fieldset>
				</form>
<?php
			}
			else
			{
				$reponse=mysql_query('SELECT COUNT(*) AS nb_messages FROM '.$this->database);
				$donnees=mysql_fetch_row($reponse);
				$nb_message=$donnees[0];
				$messageParPage=10;
				
				if ($nb_message!=0)
				{
					if (!isset($_GET['pager']))
					{
						$message=0;
						$pageActuelle=1;
					}
					else
					{
						$message=$messageParPage*($_GET['pager']-1);
						$pageActuelle=$_GET['pager'];
					}
					
					
					/********affichage des messages********/
					$reponse=mysql_query('SELECT * FROM '.$this->database.' ORDER BY id DESC LIMIT '.$message.','.$messageParPage.' ');
					while($donnees=mysql_fetch_array($reponse))
					{
						echo '<dl>';
						echo '<dt>';
						echo '<strong>'.nl2br(htmlentities($donnees['nom'])).' :</strong></dt>';
						echo '<dd>'.nl2br(htmlentities($donnees['message'])).'</dd>';
						echo '</dl>';
					}
				}
				else
				{
					echo '<p>Il n\'y a pas encore de message enregistré.</p>';
				}
					
				echo '<p class="center" ><a href="?page='.$page.'&action=ecrire" class="right" >Ecrire un message</a><a href="?page='.$page.'&action=ecrire" class="left" >Ecrire un message</a>';
				
				/********affichage du choix des pages********/
				if ( $nb_message > $messageParPage )
				{
					if (!isset($_GET['pager']))
					{
						$message=0;
						$pageActuelle=1;
					}
					else
					{
						$message=$messageParPage*($_GET['pager']-1);
						$pageActuelle=$_GET['pager'];
					}
					$pagesTotales=ceil($nb_message/$messageParPage);
					$pages=$pagesTotales;
					if ($pageActuelle!=1)
					{
						$pagePrec=$pageActuelle-1;
						echo '<a href="?page='.$page.'&pager='.$pagePrec.'" title="page précédante"><</a>&nbsp&nbsp;';
					}
					echo '<a href="?page='.$page.'&pager=1" title="première page">1..</a>&nbsp&nbsp;';
					$i=2;
					if ($pageActuelle<=5)
					{
						$i=2;
						if ($pages>9)
							$pages=9;
					}
					elseif ($pageActuelle>=($pagesTotales-4) and $pageActuelle>5)
					{
						if ($pagesTotales>=6)
							$i=$pagesTotales-7;
					}
					else
					{
						$i=$pageActuelle-3;
						$pages=$pageActuelle+3;
					}
					for ($i;$i<$pages;$i++)
					{
						echo '<a href="?page='.$page.'&pager='.$i.'">'.$i.'</a>&nbsp&nbsp;';
					}
					if ($pagesTotales!=1)
						echo '<a href="?page='.$page.'&pager='.$pagesTotales.'" title="dernière page">..'.$pagesTotales.'</a>&nbsp&nbsp;';
					if ($pageActuelle!=$pagesTotales)
					{
						$pageSuiv=$pageActuelle+1;
						echo '<a href="?page='.$page.'&pager='.$pageSuiv.'" title="page suivante">></a>';
					}
				}
				echo '</p>';
			}
		}
		
		
		
		function displayAdmin($page, $action) {
			$reponse=mysql_query('SELECT COUNT(*) AS nb_messages FROM '.$this->database);
			$donnees=mysql_fetch_row($reponse);
			$nb_message=$donnees[0];
			
			if ($nb_message!=0)
			{
				$messageParPage=10;
				if (!isset($_GET['pager']))
				{
					$message=0;
					$pageActuelle=1;
				}
				else
				{
					$message=$messageParPage*($_GET['pager']-1);
					$pageActuelle=$_GET['pager'];
				}
				
				
				/********affichage des messages********/
				echo '<form action="?page='.$page.'&action=suppression" method="post">';
				$reponse=mysql_query('SELECT * FROM '.$this->database.' ORDER BY id DESC LIMIT '.$message.','.$messageParPage.' ');
				while($donnees=mysql_fetch_array($reponse))
				{
					echo '<dl>';
					echo '<dt>';
					echo '<input type="checkbox" name="'.$donnees['id'].'"/>';
					echo '<strong>'.nl2br(htmlentities($donnees['nom'])).' :</strong></dt>';
					echo '<dd>'.nl2br(htmlentities($donnees['message'])).'</dd>';
					echo '</dl>';
				}
				echo '<p class="center" ><input type ="submit" name="submit" value="Supprimé les messages séléctionnés" />';
				echo '<span class="error">'.$this->delete.'</span>';
				echo '</p></form>';
				echo '<hr />';
				
				
				/********affichage du choix des pages********/
				if ( $nb_message > $messageParPage )
				{
					if (!isset($_GET['pager']))
					{
						$message=0;
						$pageActuelle=1;
					}
					else
					{
						$message=$messageParPage*($_GET['pager']-1);
						$pageActuelle=$_GET['pager'];
					}
					$pagesTotales=ceil($nb_message/$messageParPage);
					$pages=$pagesTotales;
					echo '<p class="center" >';
					if ($pageActuelle!=1)
					{
						$pagePrec=$pageActuelle-1;
						echo '<a href="?page='.$page.'&pager='.$pagePrec.'" title="page précédante"><</a>&nbsp&nbsp;';
					}
					echo '<a href="?page='.$page.'&pager=1" title="première page">1..</a>&nbsp&nbsp;';
					$i=2;
					if ($pageActuelle<=5)
					{
						$i=2;
						if ($pages>9)
							$pages=9;
					}
					elseif ($pageActuelle>=($pagesTotales-4) and $pageActuelle>5)
					{
						if ($pagesTotales>=6)
							$i=$pagesTotales-7;
					}
					else
					{
						$i=$pageActuelle-3;
						$pages=$pageActuelle+3;
					}
					for ($i;$i<$pages;$i++)
					{
						echo '<a href="?page='.$page.'&pager='.$i.'">'.$i.'</a>&nbsp&nbsp;';
					}
					if ($pagesTotales!=1)
						echo '<a href="?page='.$page.'&pager='.$pagesTotales.'" title="dernière page">..'.$pagesTotales.'</a>&nbsp&nbsp;';
					if ($pageActuelle!=$pagesTotales)
					{
						$pageSuiv=$pageActuelle+1;
						echo '<a href="?page='.$page.'&pager='.$pageSuiv.'" title="page suivante">></a>';
						}
					echo '<p/>';
				}
			}
			else
			{
				echo '<p>Il n\'y a pas encore de message enregistré.<br/>';
				echo '<span class="error">'.$this->delete.'</span></p>';
			}
		}
		
	}