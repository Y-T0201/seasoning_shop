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

try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['user']) === true) {
            
            $user_name = '';
            // ユーザー名の登録
            if (isset($_POST['user_name']) === true) {
                $user_name = $_POST['user_name'];
                $user_name = str_replace(array(" "," "),"",$user_name);
            }
            
            if (mb_strlen($user_name) < 6) {
                $err_msg[] = 'ユーザー名は6文字以上で入力してください';
            }
        
            if (preg_match('/^[a-zA-Z0-9]+$/', $user_name) !== 1) {
                $err_msg[] = 'ユーザー名は半角英数字で入力してください';
            }
        
            $pw = '';
            // パスワードの登録
            if (isset($_POST['pw']) === true) {
                $pw = $_POST['pw'];
                $pw = str_replace(array(" "," "),"",$pw);
            }
            
            if (mb_strlen($pw) < 6) {
                $err_msg[] = 'パスワードは6文字以上で入力してください';
            }
        
            if (preg_match('/^[a-zA-Z0-9]+$/', $pw) !== 1) {
                $err_msg[] = 'パスワードは半角英数字で入力してください';
            }
        
            $mail = '';
            // メールアドレスの登録
            if (isset($_POST['mail']) === true) {
                $mail = $_POST['mail'];
                $mail = str_replace(array(" "," "),"",$mail);
            }
            
            if (preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $mail) !== 1) {
                $err_msg[] = 'メールアドレスの入力に誤りがあります。再度入力してください。';
            }    
        
            $gender = '';
            // 性別の登録
            if (isset($_POST['gender']) === true) {
                $gender = $_POST['gender'];
            }
        
            if ($gender === '') {
                $err_msg[] = '性別を選択してください。';    
            }    

            if (count($err_msg) === 0) {
                // ユーザー名が登録されていたらエラー処理
                $sql = 'SELECT * FROM ec_user
                        WHERE user_name = ?';
                        
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $user_name, PDO::PARAM_STR);
                // SQLを実行
                $stmt->execute();       
                $user = $stmt->fetch();
                
                if (isset($user['user_name']) === TRUE) {
                    $err_msg[] = 'そのユーザーはすでに登録されています';
                } else {
    
                    // ユーザー情報テーブルにデータ作成
    
                    $sql = 'insert into ec_user(user_name, password, mail, gender, create_datetime, update_datetime)
                            VALUES(?, ?, ?, ?, NOW(), NOW());';
                            
                    // SQL文を実行する準備
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $user_name, PDO::PARAM_STR);
                    $stmt->bindvalue(2, $pw, PDO::PARAM_STR);
                    $stmt->bindvalue(3, $mail, PDO::PARAM_STR);
                    $stmt->bindvalue(4, $gender, PDO::PARAM_STR);
                    // SQLを実行
                    $stmt->execute();
                }
            }

        }
    }   
} catch (PDOExeption $e) {
    echo 'データベース処理でエラーが発生しました。 理由:'.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang = "ja">
<head>
    <meta charset = "utf-8">
    <title>会員登録完了</title>
    <style>
    body {
        margin-left: auto;
        margin-right: auto;
        background-image: url(table.jpg);
        background-size: 100%;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }
    
    body, h3, .center, .btm_green {
        width: 500px;
    }

   .top_flex {
        display: flex;
        align-items: baseline;
        border-bottom: solid 1px;
    }
    
    .icon {
        max-height: 50px;
        margin-right: 5px;
        margin-bottom: -5px;
    }
    
    .link_top, .success, h3 {
        color: #463C21;
    }
    
    .link_top, .success {
        font-size: 16px;        
    }
    
    .link_top {
        text-decoration: none;
        display: block;
    }
    
    h3, .center, .btm_green {
        text-align: center;
    } 
    
    .btm_green {
        line-height: 50px;
        font-size: 16px;
        background-color: #76A44A;
        color: #FFFFFF;
        text-decoration: none;
        display: block;
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
        <?php if (count($err_msg) === 0) { ?>
            <br>
            <h3>ご登録ありがとうございます</h3><br>
            <p class = "center">会員登録が完了しました。</p>
            <a class = "btm_green" href = "login.php">ログインする</a>
        <?php } else { ?>
            <?php foreach ($err_msg as $err) { ?>
            <br>
            <p class = "alert"><?php print $err; ?></p>
            <?php } ?>
            <br>
            <a class = "btm_green" href = "users_registration.php">新規登録画面に戻る</a>
        <?php } ?>
        </form>
    </main>
</body>
</html>