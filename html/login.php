<?php
session_start();

//ログイン済みの場合、トップページへ
if (isset($_SESSION['user_id'])) {
    header('Location: repair_list.php');
    exit;
}

//ログインエラーメッセージ配列
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

//データベースに接続
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

    //ログイン処理
    if (isset($_POST['login'])) {
        //ユーザー名ログインバリデージョン
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
        //パスワードログインバリデージョン
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
        //ユーザーパスワード確認
        if (count($errmessage) === 0) {
            $sql = 'select * from users where user_name = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $user_name, PDO::PARAM_STR);
            $stmt->execute();
            $users = $stmt->fetchAll();
            if (password_verify($user_password, $users[0]['password'])) {
                $_SESSION['user_id'] = $users[0]['user_id'];
                header('Location: repair_list.php');
                exit;
            } else {
                $errmessage[] = 'ユーザー名またはパスワードが正しくありません';
            }
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
    <title>ログインページ</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" 
    integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li><h4>ログインページ</h4></li>
                <li><small><a href="signup.php">サインアップページへ</a></small></li>
            </ul>
        </nav>
    </header>

    <main>
        <!--ログインエラーメッセージ-->
        <?php foreach($errmessage as $err) { ?>
            <div class="alert alert-danger" role="alert">
                <p><?php print h($err); ?></p>
            </div>
        <?php } ?>

        <!--ログインフォーム-->
        <form action="" method="post">
            <input type="hidden" name="csrf_token" value="<?php print h($csrf_token); ?>">
            ユーザー名<br>
            <input type="text" name="user_name" placeholder="ユーザー名"><br>
            パスワード<br>
            <input type="password" name="user_password" placeholder="パスワード"><br>
            <br>
            <p><input type="submit" name="login" value="ログイン"><p>
        </form>
    </main>

    <footer>
        <small>&copy; 2021 osakoyohei</small>
    </footer>
</body>
</html>