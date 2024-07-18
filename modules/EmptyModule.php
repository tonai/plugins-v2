<?php

	class EmptyModule extends AbstractModule {
	
		function displayMenu($modules, $data) {
			echo '<li class="'.strtolower($this->sanitize($data['menu'])).'" ><span title="'.$data['menu'].'" >'.$data['menu'].'</span>';
			echo '<ul>';
			$buff2 = mysql_query('SELECT id, menu FROM page_manager WHERE pid='.$data['id'].' ORDER BY sort');
			while($data2 = mysql_fetch_array($buff2)) {
				if (!$modules[$data2['id']]->displayMenu($modules, $data))
				{
					$title = htmlentities($data2['menu']);
					echo '<li><a href="?page='.$data2['id'].'" title="'.$title.'" >'.$title.'</a></li>';
				}
			}
			echo '</ul></li>';
			return true;
		}
		
	}