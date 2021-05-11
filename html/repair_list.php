<?php
session_start();

//$_SESSION['user_id']がNULLの場合、login.phpにリダイレクト
if ($_SESSION['user_id'] === NULL) {
    header('Location: login.php');
    exit;
} else {
    $user_id = $_SESSION['user_id'];
}

//修理登録完了メッセージ配列
$message = array();

//修理登録エラーメッセージ配列
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

    //修理登録のバリデーション
    if (isset($_POST['plans'])) {
        //お客様名のバリデージョン
        if (isset($_POST['customer'])) {
            $customer= trim(mb_convert_kana($_POST['customer'], 's', 'UTF-8'));
            if ($customer === '') {
                $errmessage[] = 'お客様名を入力してください';
            } else if (mb_strlen($customer) < 1 || mb_strlen($customer) > 20) {
                $errmessage[] = 'お客様名は1文字以上、20文字以内で入力してください';
            }
        }
        //修理製品のバリデーション
        if (isset($_POST['appliance_repair'])) {
            $appliance_repair = trim($_POST['appliance_repair']);
            if ($appliance_repair === '') {
                $errmessage[] = '修理する家電製品を入力してください';
            }
        }
        //到着予定時間（前）のバリデーション
        if (isset($_POST['from_time'])) {
            $from_time = trim($_POST['from_time']);
            if ($from_time === '') {
                $errmessage[] = '到着予定時間（前）を入力してください';
            }
        }
        //到着予定時間（後）のバリデーション
        if (isset($_POST['to_time'])) {
            $to_time = trim($_POST['to_time']);
            if ($to_time === '') {
                $errmessage[] = '到着予定時間（後）を入力してください';
            }
        }
        //修理予定時間のバリデーション
        if (isset($_POST['repair_time'])) {
            $repair_time = trim($_POST['repair_time']);
            if ($repair_time === '' || $repair_time === '00:00') {
                $errmessage[] = '修理予定時間を入力してください';
            }
        }
        //本日の修理予定登録
        if (count($errmessage) === 0) {
            $sql = 'insert into repair_plans(user_id, customer, appliance_repair, from_time, to_time, 
                    repair_time, create_datetime, update_datetime) values(?, ?, ?, ?, ?, ?, now(), now())';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $user_id,           PDO::PARAM_INT);
            $stmt->bindValue(2, $customer,          PDO::PARAM_STR);
            $stmt->bindValue(3, $appliance_repair,  PDO::PARAM_STR);
            $stmt->bindValue(4, $from_time,         PDO::PARAM_STR);
            $stmt->bindValue(5, $to_time,           PDO::PARAM_STR);
            $stmt->bindValue(6, $repair_time,       PDO::PARAM_STR);
            $stmt->execute();
            $message[] = '本日の修理予定を登録しました';
        }
    }
    //修理登録のバリデーション（編集）
    if (isset($_POST['repair_list_edit'])) {
        //ID確認
        if(isset($_POST['id'])) {
            $id = $_POST['id'];
        } else {
            $errmessage[] = 'IDが不正です';
        }
        //お客様名バリデージョン(編集）
        if (isset($_POST['customer'])) {
            $customer= trim(mb_convert_kana($_POST['customer'], 's', 'UTF-8'));
            if ($customer === '') {
                $errmessage[] = 'お客様名を入力してください';
            } else if (mb_strlen($customer) < 1 || mb_strlen($customer) > 20) {
                $errmessage[] = 'お客様名は1文字以上、20文字以内で入力してください';
            }
        }
        //修理製品のバリデーション（編集）
        if (isset($_POST['appliance_repair'])) {
            $appliance_repair = trim($_POST['appliance_repair']);
            if ($appliance_repair === '') {
                $errmessage[] = '修理する家電製品を入力してください';
            }
        }
        //到着予定時間（前）のバリデーション（編集）
        if (isset($_POST['from_time'])) {
            $from_time = trim($_POST['from_time']);
            if ($from_time === '') {
                $errmessage[] = '到着予定時間（前）を入力してください';
            }
        }
        //到着予定時間（後）のバリデーション（編集）
        if (isset($_POST['to_time'])) {
            $to_time = trim($_POST['to_time']);
            if ($to_time === '') {
                $errmessage[] = '到着予定時間（後）を入力してください';
            }
        }
        //修理予定時間のバリデーション（編集）
        if (isset($_POST['repair_time'])) {
            $repair_time = trim($_POST['repair_time']);
            if ($repair_time === '' || $repair_time === '00:00') {
                $errmessage[] = '修理予定時間を入力してください';
            }
        }
        //修理登録の編集
        if (count($errmessage) === 0) {
            $sql = 'update repair_plans set customer = ?, appliance_repair = ?, from_time = ?, to_time  = ?, repair_time = ?, 
                    update_datetime = now() where id = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $customer,          PDO::PARAM_STR);
            $stmt->bindValue(2, $appliance_repair,  PDO::PARAM_INT);
            $stmt->bindValue(3, $from_time,         PDO::PARAM_STR);
            $stmt->bindValue(4, $to_time,           PDO::PARAM_STR);
            $stmt->bindValue(5, $repair_time,       PDO::PARAM_STR);
            $stmt->bindValue(6, $id,                PDO::PARAM_INT);
            $stmt->execute();
            $message[] = '本日の修理予定を編集しました';
        }
    }
    //修理登録の削除
    if (isset($_POST['repair_list_delete'])) {
        //ID確認
        if(isset($_POST['id'])) {
            $id = $_POST['id'];
        } else {
            $errmessage[] = 'IDが不正です';
        }
        if (count($errmessage) === 0) {
            $sql = 'delete from repair_plans where id = ?';
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            $errmessage[] = '修理予定を削除しました';
        }
    }
    //本日の修理予定一覧
    $sql = 'select * from repair_plans where user_id = ? order by from_time asc';
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $repair_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $errmessage[] = 'DBエラー：' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>出張修理予定表</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" 
    integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <ul>
                <li>従業員番号：<?php print h($user_id); ?></li>
                <li><small><a href="logout.php">ログアウト</a></small></li>
            </ul>
        </nav>
    </header>

    <main>
        <!--修理登録エラーメッセージ-->
        <?php foreach($errmessage as $err) { ?>
            <div class="alert alert-danger" role="alert">
                <p><?php print h($err); ?></p>
            </div>
        <?php } ?>
        <!--修理登録完了メッセージ-->
        <?php foreach($message as $msg) { ?>
            <div class="alert alert-primary" role="alert">
                <p><?php print h($msg); ?></p>
            </div>
        <?php } ?>

        <!--ここから修理登録フォーム-->
        <h2>修理登録フォーム</h2>
        <form action="" method="post" class="repair_plans">
            <input type="hidden" name="csrf_token" value="<?php print h($csrf_token); ?>">
            お客様名<br>
            <input type="text" name="customer">
            <br>
            修理製品<br>
            <?php $appliances = array(
                '縦型洗濯機',
                'ドラム式洗濯機',
                '衣類乾燥機',
                '冷蔵庫',
                'エアコン',
                '大型空調',
                '電子レンジ',
                '温水便座',
                '給湯器');
            ?>
            <select name="appliance_repair">
                <?php foreach ($appliances as $appliance) { ?>
                    <option value="<?php print h($appliance); ?>"> <?php print h($appliance); ?></option>
                <?php } ?>
            </select>
            <br>
            到着予定時間<br>
            <input type="time" name="from_time">〜<input type="time" name="to_time"><br>
            修理予定時間<br>
            <input type="time" name="repair_time"><br>
            <p><input type="submit" name="plans" value="登録"><input type="reset" value="リセット"></p>
        </form>
        <hr>
        <?php if ($repair_list === []) { ?>
            <p>本日の修理予定はありません</p>
        <?php } else { ?>
        <!--ここから本日の修理予定一覧-->
            <h2>本日の修理予定</h2>
            <table border="1" cellspacing="0" align="center">
                <tr>
                    <th>お客様名</th>
                    <th>修理製品</th>
                    <th>到着予定時間</th>
                    <th>修理予定時間</th>
                </tr>
                <?php foreach($repair_list as $list) {?>
                    <form action="" method="post">
                        <input type="hidden" name="csrf_token" value="<?php print h($csrf_token) ?>">
                        <tr>
                            <td>
                                <input type="text" name="customer" value="<?php print h($list['customer']); ?>" >様
                            </td>
                            <td>
                                <select name="appliance_repair">
                                    <?php foreach ($appliances as $appliance) { ?>
                                        <option value="<?php print h($appliance); ?>"
                                        <?php if($list['appliance_repair'] === $appliance) print h('selected'); ?>>
                                        <?php print h($appliance); ?></option>
                                    <?php } ?>
                                </select>
                            </td>
                            <td>
                                <input type="time" name="from_time" value="<?php print h($list['from_time']); ?>" >
                                〜 <input type="time" name="to_time" value="<?php print h($list['to_time']); ?>" >
                            </td>
                            <td>
                                <input type="time" name="repair_time" value="<?php print h($list['repair_time']); ?>" >
                            </td>
                            <td>
                                <input type="submit" name="repair_list_edit" value="編集">
                                <input type="hidden" name="id" value="<?php print h($list['id']) ?>">
                            </td>
                            <td>
                                <input type="submit" name="repair_list_delete" value="削除">
                                <input type="hidden" name="id" value="<?php print h($list['id']) ?>">
                            </td>
                        </tr>
                    </form>
                <?php } ?>
            </table>
        <?php } ?>
    </main>

    <footer>
        <small>&copy; 2021 osakoyohei</small>
    </footer>
</body>
</html>