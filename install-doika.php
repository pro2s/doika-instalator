<?php
    error_reporting(E_ERROR | E_PARSE);
    session_start();
    $step = (! empty($_GET['step'])) ? intval($_GET['step']) : 1;
    $error = false;
    $install_folder = 'doika/'; // папка установки дойки
    $mysqlImportFilename = 'doika.sql';
?>
<!DOCTYPE html>
<html>
<head>
<style><?php include __DIR__ . '/install-doika/style.css'; ?></style>
</head>
<body>
<p id="logo"><a href="https://doika.falanster.by/" target="_blank" tabindex="-1">Doika</a></p>
<?php
    switch ($step) {
        case 1: // приветствие
            include __DIR__ . '/install-doika/steps/hello.html';
            break;
        case 2: // системные требования
            include __DIR__ . '/install-doika/steps/system-requirements.php';
            $nextStep = "?step=3";
            $repeatStep = "?step=2";
            include __DIR__ . '/install-doika/next-step.php';
            break;
        case 3: // проверка прав на папки
            include __DIR__ . '/install-doika/steps/folder-permisson.php';
            $nextStep = "?step=4";
            $repeatStep = "?step=3";
            include __DIR__ . '/install-doika/next-step.php';
            break;
        case 4: // доступ к базе данных
            $action = "?step=5";
            include __DIR__ . '/install-doika/steps/db-form.php';
            break;
        case 5:
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $mysqli = new mysqli($_POST['dbhost'], $_POST['uname'], $_POST['pwd'], $_POST['dbname']);
                $mysqli->set_charset('utf8');

                if ($mysqli->connect_errno) {
                    $_SESSION['dbhost'] = $_POST['dbhost'];
                    $_SESSION['uname'] = $_POST['uname'];
                    $_SESSION['pwd'] = $_POST['pwd'];
                    $_SESSION['dbname'] = $_POST['dbname'];

                    $action = '?step=4';
                    include __DIR__ . '/install-doika/db-error.php';
                } else {
                    echo '<h2>Установка</h2>';

                    $query = '';
                    $sqlScript = file($mysqlImportFilename);
                    foreach ($sqlScript as $line) {
                        $startWith = substr(trim($line), 0, 2);
                        $endWith = substr(trim($line), -1, 1);
                        if (empty($line) || $startWith == '--' || $startWith == '/*' || $startWith == '//') {
                            continue;
                        }
                        $query = $query.$line;
                        if ($endWith == ';') {
                            mysqli_query($mysqli, $query) or die('<div class="error-response sql-import-response">Problem in executing the SQL query <b>'.$query.'</b></div>');
                            $query = '';
                        }
                    }
                    $mysqli->close();
                    echo 'Файл $mysqlImportFilename успешно загружен в базу данных<br>';

                    $my_file = $install_folder.'.env';
                    $handle = fopen($my_file, 'w');
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

                    echo 'Файл конфигурации создан<br>';
                    if (! $error) {
                        echo '<meta http-equiv="refresh" content="2;URL=?step=6" />';
                    } else {
                        echo "<p class='error'>Что-то прошло не так.</p>";
                    }
                }
            }

            break;
        case 6:
            include __DIR__ . '/install-doika/steps/finish.html';

            session_destroy();
            unlink('install-doika.php');
            unlink('doika.sql');
            break;
    }
?>
</body>
</html>
