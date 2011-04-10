<?
	hook( "init", "base_init", 90 );
	
	function base_init()
	{
		global $id;
		global $gid;
		global $TYPE;
		global $PAGE_TYPE;
		global $config;
		
		// Список типов страниц
		$PAGE_TYPE["Страница"] = array();
		
		// Проверка есть ли страница
		$query = "SELECT gid, type FROM page WHERE id=$id";
		$res = mysql_query( $query );
		// Есть страница
		if( mysql_num_rows($res) )
		{
			$row = mysql_fetch_array( $res );
			$gid = $row["gid"];
			$TYPE = $row["type"];
			hook( "content", "base_content", 10 );
			hook( "base_show", "base_title", 10 );
			hook( "base_show", "base_type", 15 );
			hook( "base_show", "base_hide", 80 );
			hook( "base_show", "base_text", 90 );
			if( $config["rewrite"] )
				hook( "base_show", "base_path", 20 );
			
			
			// Обновление данных (в последнюю очередь, после всех init'ов)
			if( $_POST["title"] )
				hook( "init", "post_base_init", 99 );
		}
	}
	
	// Обновление данных
	function post_base_init()
	{
		global $id;
		$hide = (int)$_POST["hide"];
		
		$query = "UPDATE page set title='{$_POST["title"]}', text='{$_POST["text"]}', type='{$_POST["type"]}', hide=$hide WHERE id=$id";
		mysql_query( $query );
		
		// Путь для rewrite
		$p = $_POST["path"];
		$p = str_replace( " ", "_", $p );
		set_prop( $id, "path", $p );
		
		// Обновление
		run( "base_submit", $id );
		
		clear_post();
	}
	
	// Редактирование
	function base_content()
	{
		global $id;
		
		$query = "SELECT title, hide FROM page WHERE id=$id";
		$row = mysql_fetch_array( mysql_query($query) );
		
		if( $row["hide"] )
				$img = "<img src='modules/img/hide.png'>";
			else
				$img = "<img src='modules/img/edit.png'>";
		?>
			<h3 id='base_toggle'><?= $img ." ". $row["title"] ?></h3>
			<form method='post'>
				<? run( "base_show", $id ) ?>
				<input type='submit' value='Сохранить'>
			</form>
		<?
	}
	
	// Заголовок в редактировании
	function base_title( $id )
	{
		$query = "SELECT title FROM page WHERE id=$id";
		$row = mysql_fetch_array( mysql_query($query) );
		?>
			Заголовок: <input type='text' name='title' value='<?= $row["title"] ?>'><br>
		<?
	}
	
	// Текст в редактировании
	function base_text( $id )
	{
		$query = "SELECT text FROM page WHERE id=$id";
		$row = mysql_fetch_array( mysql_query($query) );
		?>
			Текст:<br>
			<textarea name='text' cols='80' rows='20' class='mce'><?= $row["text"] ?></textarea><br>
		<?
	}
	
	// Выбор типа в редактировании
	function base_type( $id )
	{
		global $TYPE;
		global $PAGE_TYPE;
		
		echo "Тип: <select name='type'>\n";
		foreach( $PAGE_TYPE as $k=>$v )
			if( $k == $TYPE )
				echo "<option selected>$k</option>\n";
			else
				echo "<option>$k</option>\n";
		
		echo "</select><br>\n";
	}
	
	function base_hide( $id )
	{
		$query = "SELECT hide FROM page WHERE id=$id";
		$row = mysql_fetch_array( mysql_query($query) );
		?>
			Скрывать в меню:
			<input type='radio' name='hide' value='0' <? if(!$row["hide"]) echo "checked" ?>> Нет
			<input type='radio' name='hide' value='1' <? if($row["hide"]) echo "checked" ?>> Да
			<br>
		<?
	}
	
	
	function base_path( $id )
	{
		echo "Путь: <input type='text' name='path' value='". get_prop( $id, "path" ) ."'><br>\n";
	}
?>
