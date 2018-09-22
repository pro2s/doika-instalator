<?php
	session_start();
	$step = (!empty($_GET['step'])) ? intval($_GET['step']) : 1;
	$error = false;
	$patch = substr(__DIR__, 0, -8);
?>
<html>
<head>
</head>
<body>
<?php
	switch ($step) {
		case 1: // приветствие
			?>
			<p>Добро пожаловать. Прежде чем мы начнём, потребуется информация о базе данных. Вот что вам необходимо знать до начала процедуры установки.</p>
			<ol>
				<li>Имя базы данных</li>
				<li>Имя пользователя базы данных</li>
				<li>Пароль к базе данных</li>
				<li>Адрес сервера базы данных</li>
			</ol>
			<p>Мы используем эту информацию, чтобы создать файл <code>.env</code>.	<strong>Если по какой-то причине автоматическое создание файла не удалось, не волнуйтесь. Всё это предназначено лишь для заполнения файла настроек. Вы можете просто открыть <code>/doika/.env</code> в текстовом редакторе, внести вашу информацию и сохранить его.</strong>
				Нужна помощь? <a href="">Пожалуйста</a>.</p>
			<p>Скорее всего, эти данные были предоставлены вашим хостинг-провайдером. Если у вас нет этой информации, свяжитесь с их службой поддержки. А если есть…</p>

			<p class="step"><a href="?step=2" class="button button-large">Вперёд!</a></p>
			<?php
			break;
		case 2: // системные требования
			echo "<h1>PHP</h1>";
			if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
				echo "PHP  версии 7.0.0 - &#10004;";
			}else{
				$error = true;
				echo "PHP  версии 7.0.0 - &#10008;";
			}
			echo "<h2>Расшинения PHP</h2>";
			$extensions = array (
				'openssl',
				'pdo',
				'mbstring',
				'tokenizer',
				'JSON',
				'cURL',
			);
			foreach($extensions as $extension){
				if(extension_loaded('cURL')){
					echo "$extension - &#10004;";
				}else{
					echo "$extension - &#10008;";
					$error = true;
				}
				echo '<br>';
			}
			echo "<h1>apache</h1>";
			if(function_exists('apache_get_modules')){
				echo "mod_rewrite - &#10004;";
			}else{
				echo "mod_rewrite - &#10008;";
				$error = true;
			}
		
		if(!$error) {
			echo '<p class="step"><a href="?step=3" class="button button-large">Вперёд!</a></p>';
		}else{
			echo "Вы сможете продолжить после того,как исправите ошибки";
		}
			break;
		case 3: // проверка прав на папки
		
			$folders = array (
				'/doika/storage/framework/'     => '775',
				'/doika/storage/logs/'          => '775',
				'/doika/bootstrap/cache/'       => '775',
				'/doika/public/images/'       => '775'
			);
			foreach($folders as $folder => $permission){
				echo '<br>';
				 if(intval(substr(sprintf('%o', fileperms($patch . $folder)), -3)) >= intval($permission)){
					 echo $folder . " - &#10004;";
				 }else {
					 echo $folder . " - &#10008;";
					 $error = true;
				 }
			}
			if(!$error) {
				echo '<p class="step"><a href="?step=4" class="button button-large">Вперёд!</a></p>';
			}else{
				echo "Вы сможете продолжить после того,как исправите ошибки";
			}

			break;
		case 4: // доступ к базе данных
			?>
			<form method="post" action="?step=5">
				<p>Введите здесь информацию о подключении к базе данных. Если вы в ней не уверены, свяжитесь с хостинг-провайдером.</p>
				<table class="form-table">
					<tbody><tr>
						<th scope="row"><label for="dbname">Имя базы данных</label></th>
						<td><input name="dbname" id="dbname" type="text" size="25" value="<?php echo isset($_SESSION['dbname']) ? $_SESSION['dbname'] : 'doika'; ?>"></td>
						<td>Имя базы данных, в которую вы хотите установить Doika.</td>
					</tr>
					<tr>
						<th scope="row"><label for="uname">Имя пользователя</label></th>
						<td><input name="uname" id="uname" type="text" size="25" value="<?php echo isset($_SESSION['uname']) ? $_SESSION['uname'] : ''; ?>"></td>
						<td>Имя пользователя базы данных.</td>
					</tr>
					<tr>
						<th scope="row"><label for="pwd">Пароль</label></th>
						<td><input name="pwd" id="pwd" type="text" size="25" value="<?php echo isset($_SESSION['pwd']) ? $_SESSION['pwd'] : ''; ?>" autocomplete="off"></td>
						<td>Пароль пользователя базы данных.</td>
					</tr>
					<tr>
						<th scope="row"><label for="dbhost">Сервер базы данных</label></th>
						<td><input name="dbhost" id="dbhost" type="text" size="25" value="<?php echo isset($_SESSION['dbhost']) ? $_SESSION['dbhost']: 'localhost'; ?>"></td>
						<td>Если <code>localhost</code> не работает, нужно узнать правильный адрес в службе поддержки хостинг-провайдера.</td>
					</tr>
				</tbody></table>
				<p class="step"><input name="submit" type="submit" value="Отправить" class="button button-large"></p>
			</form>
			<?php
			break;
		case 5:
			if ($_SERVER['REQUEST_METHOD'] === 'POST') {
				$link = mysqli_connect($_POST['dbhost'], $_POST['uname'], $_POST['pwd'], $_POST['dbname']);

				if (!$link) {
					
					$_SESSION['dbhost'] = $_POST['dbhost'];
					$_SESSION['uname'] = $_POST['uname'];
					$_SESSION['pwd'] = $_POST['pwd'];
					$_SESSION['dbname'] = $_POST['dbname'];
					
					echo '<h1>Ошибка установки соединения с базой данных</h1>';
					echo '<p>Это значит, что либо имя пользователя и пароль неверны, либо нам не удалось связаться с сервером базы данных по адресу <code>'. $_POST['dbhost'] .'</code>. Возможно, сервер недоступен.</p>';
					echo '<ul>';
					echo '<li>Вы уверены, что указали правильное имя пользователя и пароль?</li>';
					echo '<li>Вы уверены, что ввели правильное имя сервера?</li>';
					echo '<li>Вы уверены, что сервер базы данных запущен?</li>';
					echo '</ul>';
					echo '<p>Если вы не знаете, что означают эти термины — возможно, стоит обратиться к хостинг-провайдеру.</p>';
					echo '<p></p><p class="step"><a href="?step=4" onclick="javascript:history.go(-1);return false;" class="button button-large">Попробовать ещё раз</a></p>';

					echo "Ошибка: Невозможно установить соединение с MySQL." ;
					echo "Код ошибки errno: " . mysqli_connect_errno();
					echo "Текст ошибки error: " . mysqli_connect_error();
				}else{
					echo "Усановка:<br>";
					
					$mysqlImportFilename ='../doika.sql';
					$command='mysql -h' .$_POST['dbhost'] .' -u' .$_POST['uname'] .' -p' .$_POST['pwd'] .' ' .$_POST['dbname'] .' < ' .$mysqlImportFilename;
					exec($command,$output=array(),$worked);
					switch($worked){
						case 0:
							echo 'Import file <b>' .$mysqlImportFilename .'</b> successfully imported to database <b>' .$_POST['dbname'] .'</b>';
							break;
						case 1:
							echo 'There was an error during import. Please make sure the import file is saved in the same folder as this script and check your values:<br/><br/><table><tr><td>MySQL Database Name:</td><td><b>' .$_POST['dbname'] .'</b></td></tr><tr><td>MySQL User Name:</td><td><b>' .$_POST['uname'] .'</b></td></tr><tr><td>MySQL Password:</td><td><b>NOTSHOWN</b></td></tr><tr><td>MySQL Host Name:</td><td><b>' .$_POST['dbhost'] .'</b></td></tr><tr><td>MySQL Import Filename:</td><td><b>' .$mysqlImportFilename .'</b></td></tr></table>';
							break;
					}
					echo "Загрузка данныз в MySQL<br>";
					echo "Создание .env<br>";
					$my_file = '../doika/.env';
					$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file); //implicitly creates file
					$data = "APP_NAME=Doika
APP_ENV=production
APP_KEY=base64:8ObMpr3jB1o5SQ3az2pqXo9tSPGAZOponr4eHBoDs9Y=

DB_CONNECTION=mysql
DB_HOST={$_POST['dbhost']}
DB_PORT=3306
DB_DATABASE={$_POST['dbname']}
DB_USERNAME={$_POST['uname']}
DB_PASSWORD={$_POST['pwd']}";
					fwrite($handle, $data);
					fclose($handle);

					echo "Сохранение .env файла";
					echo '<meta http-equiv="refresh" content="2;URL=?step=6" />';
					
				}
			}

			break;
		case 6:

			echo '<h1>Поздравляем!</h1>';
			echo '<p>Doika установлена. Желаем успешной работы!</p>';
			echo '<table class="form-table install-success">';
			echo '	<tbody><tr>';
			echo '		<th>Имя пользователя</th>';
			echo '		<td>sample@sample.com</td>';
			echo '	</tr>';
			echo '	<tr>';
			echo '		<th>Пароль</th>';
			echo '		<td>123456</p>';
			echo '		</td>';
			echo '	</tr>';
			echo '</tbody></table>';
			echo '<p class="step"><a href="/doika/login" class="button button-large">Войти</a></p>';

			session_destroy();
			function deleteDir($dirPath) {
				if (! is_dir($dirPath)) {
					throw new InvalidArgumentException("$dirPath must be a directory");
				}
				if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
					$dirPath .= '/';
				}
				$files = glob($dirPath . '*', GLOB_MARK);
				foreach ($files as $file) {
					if (is_dir($file)) {
						deleteDir($file);
					} else {
						unlink($file);
					}
				}
				rmdir($dirPath);
			}
			deleteDir('../install/');
			break;
	}
?>
</body>
</html>