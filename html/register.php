<?php
session_start();

//signup.phpから新規登録以外は、login.phpにリダイレクト
if(isset($_SESSION['register'])) {
    $_SESSION = array();
} else {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規登録完了ページ</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" 
    integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="alert alert-primary" role="alert">
        <h3>新規登録が完了しました！</h3>
    </div>
    <p><a href="login.php">ログインページ</a></p>
</body>
</html>