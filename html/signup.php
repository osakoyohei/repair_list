<?php
session_start();

//新規登録エラーメッセージ配列
$errmessage = array();

//エスケープ処理の関数
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

//CSRF対策　フォームからのトークンチェック
if (isset($_POST['csrf_token']) && $_POST["csrf_token"] !== $_SESSION['csrf_token']) {
    $errmessage[] = '不正なリクエストです';
    $_SESSION = array();
    session_destroy();
}

//CSRF対策のトークン作成
$toke_byte = openssl_random_pseudo_bytes(16);
$csrf_token = bin2hex($toke_byte);
$_SESSION['csrf_token'] = $csrf_token;

//データーベースに接続
$host     = 'mysql';
$username = 'testuser'; 
$password = 'password';
$dbname   = 'sample';
$charset  = 'utf8';

$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

try {
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    //新規登録処理
    if (isset($_POST['signup'])) {
        //ユーザー名登録バリデージョン
        if (isset($_POST['user_name'])) {
            $user_name = trim($_POST['user_name']);
            if ($user_name === '') {
                $errmessage[] = 'ユーザー名を入力してください';
            } else if (!preg_match('/^[0-9a-zA-Z]*$/',$user_name)) {
                $errmessage[] = 'ユーザー名は半角英数字で入力してくだい';
            } else if (mb_strlen($user_name) < 6 || mb_strlen($user_name) > 20) {
                $errmessage[] = 'ユーザー名は6文字以上、20文字以内で入力してください';
            }
        }
        //パスワード登録バリデージョン
        if (isset($_POST['user_password'])) {
            $user_password = trim($_POST['user_password']);
            if ($user_password === '') {
                $errmessage[] = 'パスワードを入力してください';
            } else if (!preg_match('/^[0-9a-zA-Z]*$/', $user_password)) {
                $errmessage[] = 'パスワードは半角英数字で入力してください';
            } else if (mb_strlen($user_password) < 6 || mb_strlen($user_password) > 20) {
                $errmessage[] = 'パスワードは6文字以上、20文字以内で入力してください';
            }
        }
        //パスワード（確認用）登録バリデージョン
        if (isset($_POST['password_confirmation'])) {
            $password_confirmation = trim($_POST['password_confirmation']);
            if ($password_confirmation === '') {
                $errmessage[] = 'パスワード（確認用）を入力してください';
            } else if (!preg_match('/^[0-9a-zA-Z]*$/', $password_confirmation)) {
                $errmessage[] = 'パスワード（確認用）は半角英数字で入力してください';
            } else if (mb_strlen($password_confirmation) < 6 || mb_strlen($password_confirmation) > 20) {
                $errmessage[] = 'パスワード（確認用）は6文字以上、20文字以内で入力してください';
            }
        }
        //パスワード一致確認
        if (count($errmessage) === 0 && $user_password !== $password_confirmation) {
            $errmessage[] = 'パスワードが一致しません';
        } else {
            //パスワードハッシュ化
            $user_password = password_hash($user_password, PASSWORD_DEFAULT);
        }
        //ユーザー名重複確認
        if (count($errmessage) === 0) {
            $sql = 'select user_name from users where user_name = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $user_name, PDO::PARAM_STR);
            $stmt->execute();
            $users = $stmt->fetchAll();
            if (count($users) > 0) {
                $errmessage[] = 'そのユーザー名はすでに登録されています';
            }
        }
        //DBにユーザー新規登録
        if (count($errmessage) === 0) {
            $sql = 'insert into users(user_name, password, create_datetime, update_datetime) values(?, ?, now(), now())';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $user_name, PDO::PARAM_STR);
            $stmt->bindValue(2, $user_password, PDO::PARAM_STR);
            $stmt->execute();
            $_SESSION['register'] = '新規登録完了';
            header('Location: register.php');
            exit;
        }
    }
} catch (PDOException $e) {
    $errmessage[] = 'DBエラー：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規登録ページ</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" 
    integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><h4>新規登録ページ</h4></li>
                <li><small><a href="login.php">ログインページへ</a></small></li>
            </ul>
        </nav>
    </header>

    <main>
        <!--新規登録エラーメッセージ-->
        <?php foreach($errmessage as $err) { ?>
            <div class="alert alert-danger" role="alert">
                <p><?php print h($err); ?></p>
            </div>
        <?php } ?>

        <!--新規登録フォーム-->
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php print h($csrf_token); ?>">
            ユーザー名<br>
            <input type="text" name="user_name" placeholder="ユーザー名"><br>
            パスワード<br>
            <input type="password" name="user_password" placeholder="パスワード"><br>
            パスワード(確認用)<br>
            <input type="password" name="password_confirmation" placeholder="パスワード(確認用)"><br>
            <br>
            <p><input type="submit" name="signup" value="新規登録"><p>
        </form>
    </main>

    <footer>
        <small>&copy; 2021 osakoyohei</small>
    </footer>
</body>
</html>