<?
	// Стили, скрипты
	$CSS[] = "admin.css";
	$JS[] = "jquery.js";
	$JS[] = "jquery-ui.js";
	$JS[] = "admin.js";
?>
<!doctype html>
<head>
	<? head() ?>
</head>
<body>
	<div id='top'>
		<a href='<?= $ADMIN_URL ?>' class='logo'><img src='modules/img/adm.png'></a> <span>сайта <?= $CONFIG["title"] ?></span>
		<div>
			<a href='<?= $CONFIG_URL ?>'>Настройки</a> |
			<a href='?logout=1'>Выход</a>
		</div>
	</div>
	<div id='main'>
		<div id='nav'>
			<? run( "nav" ) ?>
		</div>
		<div id='content'>
			<div id='crumb'>
			<?
				// Хлебные крошки или переход к разделам
				if( $_GET["do"] == "admin" )
					run( "crumb", "•" );
				else
					echo "<a href='$ADMIN_URL'>Разделы</a>";
			?>
			</div>
			<? run( "content" ) ?>
			<div style='clear: left'></div>
		</div>
	</div>
	<div id='bottom'>
		<img src='modules/img/logo.png'>
		<? @include( "version" ) ?>
	</div>
</body>
