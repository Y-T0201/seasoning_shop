<?php
$host = 'localhost';
$username = 'root';
$password = 'nRlkY30ag';
$dbname = 'ec_site';
$charset = 'utf8';

$img_dir = './item_img/'; // アップロードした画像ファイルの保存ディレクトリ
$err_msg = array();
$data = array();

// MySQL用のDSN文字列
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

if (isset($_POST['login']) === true ) {
    
    try {
        // データベースに接続
        $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        $user_name = '';
        // ユーザー名の登録
        if (isset($_POST['user_name']) === true) {
            $user_name = $_POST['user_name'];
            $user_name = str_replace(array(" "," "),"",$user_name);
        }
        
        // if (mb_strlen($user_name) < 6) {
        //     $err_msg[] = 'ユーザー名は6文字以上で入力してください';
        // }
    
        // if (preg_match('/^[a-zA-Z0-9]+$/', $user_name) !== 1) {
        //     $err_msg[] = 'ユーザー名は半角英数字で入力してください';
        // }
    
        $pw = '';
        // パスワードの登録
        if (isset($_POST['pw']) === true) {
            $pw = $_POST['pw'];
            $pw = str_replace(array(" "," "),"",$pw);
        }
        
        // if (mb_strlen($pw) < 6) {
        //     $err_msg[] = 'パスワードは6文字以上で入力してください';
        // }
    
        // if (preg_match('/^[a-zA-Z0-9]+$/', $pw) !== 1) {
        //     $err_msg[] = 'パスワードは半角英数字で入力してください';
        // }
    
        // ユーザー情報テーブルにデータ作成
        if (count($err_msg) === 0) {
            $sql = 'SELECT * FROM ec_user
                    WHERE user_name=? and password=?';
                    
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $user_name, PDO::PARAM_STR);
            $stmt->bindvalue(2, $pw, PDO::PARAM_STR);
            // SQLを実行
            $stmt->execute();
            $result = $stmt->fetch();
            
            if (isset($result['user_id']) === TRUE) {
                session_start();
                
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['user_name'] = $result['user_name'];

                if ($_SESSION['user_name'] === 'admin') {
                    header('Location: seasoning_tool.php');
                    exit;
                } else {
                    header('Location: seasoning_list.php');
                    exit;
                }
            } else {
                $err_msg[] = 'ユーザー名またはパスワードが誤りです。';
            }
        }
    } catch (PDOExeption $e) {
        echo 'データベース処理でエラーが発生しました。 理由:'.$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang = "ja">
<head>
    <meta charset = "utf-8">
    <title>ログイン</title>
    <style>
    body {
        margin-left: auto;
        margin-right: auto;
        background-image: url(table.jpg);
        background-size: 100%;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }
    
    h3, table, body, .submit, .user {
        width: 700px;
    }
    
    table, tr, th, td {
        border: solid 1px;
        padding: 10px;
    }
    
    h3, .user {
        background-color: #76A44A;    
    }
    
    h3 {
        padding: 10px;
        color: #FFFFFF;
    }
    
    table {
        border-collapse: collapse;
        background-color: #E4EBF1;
    }
    
    .flex, .top_flex {
        display: flex;
    }
    
    .red {
        background-color: red;
        height: 25px;
        width: 50px;
        text-align: center;
        font-weight: normal;
    }
    
    .submit {
        height: 40px;
        font-size: 16px;
    }
    
    .top_flex {
        align-items: baseline;
        border-bottom: solid 1px ;
    }
    
    .icon {
        max-height: 50px;
        margin-right: 5px;
        margin-bottom: -5px;
    }
    
    .link_top, .success, h2 {
        color: #463C21;
    }
    
    .link_top, .success {
        font-size: 16px;        
    }
    
    .link_top {
        text-decoration: none;
        display: block;
    }
    
    .user {
        line-height: 50px;
        font-size: 16px;
        color: #FFFFFF;
        text-decoration: none;
        display: block;
        text-align: center;
    }
    
    .alert {
        color: #FFFFFF;
        background-color: red;
        padding-left: 10px;
    }
    
    </style>
</head>
<body>
    <header>
        <div class = "top_flex">
            <a class = "link_top" href = "seasoning_list.php">
                <img class = "icon" src="apron.png">はじめての調味料
            </a>
        </div>
    </header>
    <main>
        <h2>会員登録されているお客様</h2>
        <h3>ご注文または会員サービスをご利用いただくにはログインは必要です。<br>
            ユーザー名とパスワードをご入力してください。</h3>
        <br>
        <?php foreach ($err_msg as $err) { ?>
        <p class = "alert"><?php print $err ?></p>
        <?php } ?>
        <div class = "flex">
            <p><div class = "red">必須</div>&nbsp;マークのある項目は全て入力してください。</p>
        </div>
        <form method = "post">
        <table>
            <tr>
                <th><div class = "flex">ユーザー名&nbsp;&nbsp;<div class = "red">必須</div></th></div>
                <td>
                    <input type = "text" name = "user_name">
                    <P>※半角英数字6文字以上</P>
                </td>
            </tr>
            <tr>
                <th><div class = "flex">パスワード&nbsp;&nbsp;<div class = "red">必須</div></th></div>
                <td>
                    <input type = "password" name = "pw">
                    <P>※半角英数字6文字以上</P>
                </td>
            </tr>
        </table><br>
        <input class = "submit" type = "submit" name = "login" value = "ログイン">
        </form>
        <br>
        <h2>会員登録されていないお客様</h2>
        <p>サイトのご利用には、会員登録を行っていただく必要があります。</p>
        <p>商品のご購入、便利なマイページをご利用いただけます。</p>
        <br>
        <a class = "user" href = "users_registration.php">新規会員登録</a>
    </main>
</body>
</html>