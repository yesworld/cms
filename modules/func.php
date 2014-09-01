<?php
	// Загрузить модули из каталога
	function load_modules( $mask )
	{
		$globs = array( "modules/$mask/*.php", "extra/*/$mask/*.php", "extra/*/$mask.php" );
		$inc = array();
		foreach( $globs as $g )
		{
			if( $a = glob($g) )
				$inc = array_merge( $inc, $a );
		}
		
		if( empty($inc) )
			return;
		
		extract( $GLOBALS, EXTR_REFS );
		
		foreach( $inc as $file )
			@include( $file );
	}
	
	// Создать хук
	function hook( $hookname, $func, $pos=50, $data=null )
	{
		global $HOOK;
		
		// Пытаемся встать в позицию pos
		if( $HOOK[$hookname] )
		{
			while( @array_key_exists($pos, $HOOK[$hookname]) )
			{
				$pos++;
			}
		}
		$HOOK[$hookname][$pos] = array( "func"=>$func, "data"=>$data );
	}
	
	// Удалить хук
	function unhook( $hookname, $func, $data=null )
	{
		global $HOOK;
		
		// all удаляет все хуки
		if( $func == "all" )
			unset( $HOOK[$hookname] );
		// Иначе выбранную функцию
		else
		{
			$idx = null;
			foreach( $HOOK[$hookname] as $k=>&$v )
			{
				if( $v["func"]==$func && ($data==null || $v["data"]==$data) )
					unset( $HOOK[$hookname][$k] );
			}
		}
	}
	
	// Выполнить все функции из хука
	function run( $hookname, $arg = 0 )
	{
		global $HOOK;
		
		if( is_array($HOOK[$hookname])  )
		{
			// Отсортировать по приотирету
			ksort( $HOOK[$hookname] );
			foreach( $HOOK[$hookname] AS &$v )
			{
				if( function_exists($v["func"]) )
					$v["func"]( $arg, $v["data"] );
				else
					trigger_error( "Function '{$v["func"]}' is not exist in hook '$hookname'!" );
			}
		}
	}
	
	// Возвращает объект для конфига
	function config_item( $v )
	{
		if( is_string($v) )
			return "'". str_replace("'", "\\'", $v) ."'";
		elseif( is_numeric($v) )
			return $v;
		elseif( is_bool($v) )
		{
			if( $v )
				return "true";
			else
				return "false";
		}
		elseif( is_array($v) )
		{
			$a = array();
			foreach( $v AS $k=>$v )
				$a[] = config_item( $k ) . "=>" . config_item( $v );
			return "array( ". implode(", ", $a) ." )";
		}
		else
			return "null";
	}
	
	// Сохранить массив $CONFIG в файле config.php
	function config_write( $array=null, $name="CONFIG", $file="config.php", $quiet=0 )
	{
		global $CONFIG;
		
		if( $array === null )
			$array = $CONFIG;
		
		if( !$f = fopen($file, "w") )
		{
			$_SESSION["notify"][] = array( "text"=>"Ошибка сохранения файла $file!", "type"=>"warning", "timeout"=>5000 );
			return;
		}
		
		fwrite( $f, "<?php\n" );
		foreach( $array AS $k => $v )
		{
			fwrite( $f, "\t\${$name}[" . config_item( $k ) . "] = " . config_item( $v ) . ";\n" );
		}
		fclose( $f );
		
		if( $name=="CONFIG" && !$quiet )
		{
			$_SESSION["notify"][] = array( "text"=>"Настройки сохранены", "type"=>"success" );
		}
	}
	
	
	// Всё, что в <head>
	function head()
	{
		global $BASEPATH, $HEAD, $CSS, $JS, $SCRIPT;
		
		echo "<meta charset='utf-8'>\n";
		
		if( $HEAD )
			foreach( $HEAD as $v )
				echo "\t$v\n";
		
		if( $CSS )
		{
			$CSS = array_unique( $CSS );
			foreach( $CSS as $v )
			{
				if( strpos($v, "//")===false )
					$v = $BASEPATH . $v;
				echo "\t<link rel='stylesheet' href='$v'>\n";
			}
		}
		
		if( $JS )
		{
			$JS = array_unique( $JS );
			foreach( $JS as $v )
			{
				if( strpos($v, "//")===false )
					$v = $BASEPATH . $v;
				echo "\t<script src='$v'></script>\n";
			}
		}
		
		if( $SCRIPT )
			foreach( $SCRIPT as $v )
				echo "\t<script>\n$v\n</script>\n";
	}
	
	
	// Перезагрузить страницу с очисткой post-данных
	function clear_post()
	{
		header( "Location: {$_SERVER["REQUEST_URI"]}" );
		die;
	}
	
	
	// Прочитать свойство
	function get_prop( $id, $field, $goto=0 )
	{
		$row = db_select_one( "SELECT value FROM prop WHERE id=$id AND field=". db_escape($field) );
		if( $goto )
			return $row["value"] ." ". admin_goto( $id, 1 );
		else
			return $row["value"];
	}
	
	// Установить свойство
	function set_prop( $id, $field, $value )
	{
		if( $value )
			db_insert( "prop", array("id"=>$id, "field"=>$field, "value"=>$value), 1 );
		else
			db_delete( "prop", "id=$id AND field=". db_escape($field) );
	}
	
	
	// Удалить загруженный файл
	function delete_file( $id )
	{
		// Ищем тип
		$row = db_select_one( "SELECT type FROM file WHERE id=$id" );
		
		// Удаляем из ФС
		unlink( "files/$id.{$row["type"]}" );
		
		// Удаляем вспомогательные файлы
		if( $files = glob("files/{$id}_*", GLOB_NOSORT) )
		{
			foreach( $files as $file )
				unlink( $file );
		}
		
		// И из БД
		db_delete( "file", "id=$id" );
	}
	
	
	function path( $id )
	{
		global $CONFIG, $BASEPATH;
		
		if( $id == $CONFIG["main"] )
			return ".";
		if( $path = get_prop($id, "path") )
			return $CONFIG["rewrite"] ? $BASEPATH.$path : $BASEPATH."?t=$path";
		
		return $BASEPATH ."?id=$id";
	}
	
	
	// Вывести текст раздела
	function get_text( $id, $goto=1 )
	{
		$row = db_select_one( "SELECT text FROM page WHERE id=$id" );
		if( $goto )
			return $row["text"] . admin_goto( $id, 0 );
		else
			return $row["text"];
	}
	
	
	// Базовый каталог для файла (для JS и шаблонов)
	function cur_dir( $file )
	{
		$base = str_replace( "index.php", "", $_SERVER["SCRIPT_FILENAME"] );
		$file = preg_replace( "|/[\w\d\._-]+$|", "", $file );
		
		return str_replace( $base, "", $file );
	}
