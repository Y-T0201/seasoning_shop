<?php
$host = 'mysql';
$username = 'root';
$password = 'root';
$dbname = 'seasoning_shop';
$charset = 'utf8';

$img_dir = './recipe_img/'; // アップロードした画像ファイルの保存ディレクトリ
$err_msg = array();
$data = array();
$name = array();

// MySQL用のDSN文字列
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

session_start();

if (isset($_SESSION['user_id']) === TRUE) {
    $user_id = $_SESSION['user_id'];
} else {
    // ログインしてないので、ログインページに飛ばす
    header('Location: login.php');
    exit;
}

if (isset($_POST['btm_logout']) === true) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$recipe_id = '';
if (isset($_GET['recipe_id']) === true) {
    $recipe_id = $_GET['recipe_id'];
}

try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // レシピ情報の変更
    if (isset($_POST['update_post']) === true) {
        $update_recipe_name = '';
        // レシピ名
        if (isset($_POST['update_recipe_name']) === true) {
            $update_recipe_name = $_POST['update_recipe_name'];
            $update_recipe_name = str_replace(array(" "," "),"",$update_recipe_name);
        }
        
        if (mb_strlen($update_recipe_name) === 0) {
            $err_msg[] = '料理名を入力してください';
        } else if (mb_strlen($update_recipe_name) > 29) {
            $err_msg[] = '料理名は29文字以内で入力してください';
        }         

        $update_recipe_img = '';
        // 画像の変更
        if (isset($_POST['recipe_img']) === true) {
            $update_recipe_img = $_POST['recipe_img'];
        }

        $new_recipe_img = '';
        // 画像
        // HTTP POST でファイルがアップロードされたかどうかチェック
        if (is_uploaded_file($_FILES['update_recipe_img']['tmp_name']) === true) {
            // 画像の拡張子を取得
            $extension = pathinfo($_FILES['update_recipe_img']['name'], PATHINFO_EXTENSION);
            // 指定の拡張子であるかどうかチェック
            if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png') {
                // 保存する新しいファイル名の生成(ユニークな値を設定する)
                $new_recipe_img = sha1(uniqid(mt_rand(), true)). '.' . $extension;
                // 同名ファイルが存在しているかチェック
                if (is_file($img_dir . $new_recipe_img) !== true) {
                    // アップロードされたファイルを指定ディレクトリに移動して保存
                    if (move_uploaded_file($_FILES['update_recipe_img']['tmp_name'], $img_dir . $new_recipe_img) !== true) {
                        $err_msg[] = 'ファイルアップロードに失敗しました';
                    }
                } else {
                    $err_msg[] = 'ファイルアップロードに失敗しました。再度お試しください。';
                }
            } else {
                $err_msg[] = 'ファイル形式が異なります。画像ファイルはJPEG、またはPNGのみ利用可能です。';
            }
        // } else {
        //     $err_msg[] = 'ファイルを選択してください';
        }

        $update_item_id = '';
        // 調味料名
        if (isset($_POST['update_item_id']) === true) {
            $update_item_id = $_POST['update_item_id'];
        }

        $update_recipe_comment = '';
        // レシピ詳細
        if (isset($_POST['update_recipe_comment']) === true) {
            $update_recipe_comment = $_POST['update_recipe_comment'];
            $update_recipe_comment = str_replace(array(" "," "),"",$update_recipe_comment);
        }
        
        if (mb_strlen($update_recipe_comment) === 0) {
            $err_msg[] = '料理の詳細を入力してください';
        } else if (mb_strlen($update_recipe_comment) > 98) {
            $err_msg[] = '詳細は98文字以内で入力してください';
        }

        $change_recipe_status = '';
        // ステータス
        if (isset($_POST['change_recipe_status']) === true) {
            $change_recipe_status = $_POST['change_recipe_status'];
        }
        
        if ($change_recipe_status !== '0' && $change_recipe_status !== '1') {
            $err_msg[] = 'ステータスエラー';
        }

        $update_person = '';
        // 人数
        if (isset($_POST['update_person']) === true) {
            $update_person = $_POST['update_person'];
            $update_person = str_replace(array(" "," "),"",$update_person);
        }
        
        if (preg_match('/^[0-9]+$/', $update_person) !== 1) {
            $err_msg[] = '人数は0以上の整数を入力してください';
        } 

        $update_recipe_material = '';
        // 材料 
        if (isset($_POST['update_recipe_material']) === true) {
            $update_recipe_material = $_POST['update_recipe_material'];
        }

        $update_recipe = '';
        // 作り方      
        if (isset($_POST['update_recipe']) === true) {
            $update_recipe = $_POST['update_recipe'];
            $update_recipe = str_replace(array(" "," "),"",$update_recipe);
        }        

        $update_point = '';
        // コツ・ポイント        
        if (isset($_POST['update_point']) === true) {
            $update_point = $_POST['update_point'];
            $update_point = str_replace(array(" "," "),"",$update_point);
        }  
         
        if (count($err_msg) === 0) {
            if ($new_recipe_img !== "") {
                $update_recipe_img = $new_recipe_img;
            }
            // 商品情報テーブルにデータ作成
            $sql = 'UPDATE ec_recipe_master
                    JOIN ec_recipe_details ON ec_recipe_master.recipe_id = ec_recipe_details.recipe_id
                    SET recipe_name = ?, recipe_img = ?, item_id = ?, recipe_comment = ?, recipe_status = ?,
                        person = ?, recipe_material = ?, recipe = ?, point = ?
                    WHERE ec_recipe_master.recipe_id = ?';
                    
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_recipe_name, PDO::PARAM_STR);
            $stmt->bindvalue(2, $update_recipe_img, PDO::PARAM_STR);
            $stmt->bindvalue(3, $update_item_id, PDO::PARAM_INT);
            $stmt->bindvalue(4, $update_recipe_comment, PDO::PARAM_STR);
            $stmt->bindvalue(5, $change_recipe_status, PDO::PARAM_INT);
            $stmt->bindvalue(6, $update_person, PDO::PARAM_INT);
            $stmt->bindvalue(7, $update_recipe_material, PDO::PARAM_STR);
            $stmt->bindvalue(8, $update_recipe, PDO::PARAM_STR);
            $stmt->bindvalue(9, $update_point, PDO::PARAM_STR);
            $stmt->bindvalue(10, $recipe_id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();

            if(file_exists($img_dir . $_POST['recipe_img'])) {
                unlink($img_dir . $_POST['recipe_img']);
            } 

            echo 'データが登録できました';
        } else {
            if ($new_recipe_img !== "") {
                if(file_exists($img_dir . $new_recipe_img)) {
                    unlink($img_dir . $new_recipe_img);
                }
            }
        }
    }
        
    // 調味料名を表示
    // SQL文を作成
    $sql = 'SELECT item_id, item_name
            FROM ec_item_master';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $items = $stmt->fetchAll();
    // 1行ずつ結果を配列で取得
    foreach ($items as $item) {
        $name[] = $item;
    }   
    
    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT ec_recipe_master.recipe_id, recipe_name, recipe_img, ec_recipe_master.item_id, recipe_status, recipe_comment, item_name,
                person, recipe_material, recipe, point
            FROM ec_recipe_master
            JOIN ec_item_master ON ec_recipe_master.item_id = ec_item_master.item_id
            LEFT JOIN ec_recipe_details ON ec_recipe_master.recipe_id =  ec_recipe_details.recipe_id
            WHERE ec_recipe_master.recipe_id = ?
            ORDER BY recipe_id ASC';

    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1,$recipe_id,PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $recipe_details = $stmt->fetch();

} catch (PDOExeption $e) {
    echo 'データベース処理でエラーが発生しました。 理由:'.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang = "ja">
<head>
    <meta charset = "utf-8">
    <title>はじめての調味料　管理ページ</title>
    <style>
        table, .flex, .link {
            width: 1300px;
        }

        .flex, .link, .recipe_change, h2 {
            margin-left: auto;
            margin-right: auto;
        }

        .link {
            padding: 0 0 10px 0;
            border-bottom: solid 1px;
        }
    
        p {
            font-size: 24px;
        }

        h2 {
            font-size: 28px
        }

        .margin50 {
            margin-right: 50px;
        }

        .recipe_change, h2 {
            width: 700px;
        }
        
        body {
            background-color: #FFFFE0;
        }

        .recipe_change {
            background-color: #ffffff;
        }

        textarea {
            width: 500px;
            height: 250px;
        }
    
        .new_submit {
            display:block;
        }
        
        table {
            border-collapse: collapse;
        }
        
        table, tr, th, td {
            border: solid 1px;
            padding: 10px;
            text-align: center;
        }
        
        img {
            max-height: 125px;
            /*max-width: 100px;*/
        }
        
        .gray {
            background: gray;
        }

        .recipe_name {
            width: 500px;
        }
        
        .btm_logout, .btm_preview, .form_preview {
            margin: 8px 0px 0px 50px;
            padding: 0px;
            height: 50px;
            width: 100px;
        }
        
        .btm_preview {
            color: #ffffff;
            background-color: blue;
            border: 0px none;
        }

        .btm_update{
            margin: 20px 0px 0px 0px;
            height: 50px;
            width: 700px;
            font-size: 18px;
            background-color: #76A44A;
            border: 0px none;
        }

        .flex {
            display: flex;
        }
        
        .delete_form {
            margin-top: 30px;
        }

        .delete_btm {
            color: red;
        }

    </style>
</head>
<body>
<div class = "flex">    
    <h1>はじめての調味料　管理ページ</h1>
    <form class = "form_preview" action = "recipe_details.php" method = "get">
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input class = "btm_preview" type = "submit" name = "preview" value = "プレビュー">
    </form>
    <form class = "btm_logout" method = "post">
        <input class = "btm_logout" type = "submit" name = "btm_logout" value = "ログアウト">
    </form>
</div>
<?php foreach ($err_msg as $value) { ?>
    <p><?php print $value; ?></p>
<?php } ?>
<div class = "link">
    <a class = "margin50" href = "seasoning_tool.php">調味料管理ページ</a>
    <a class = "margin50" href = "recipe_tool.php">レシピ管理ページ</a>
    <a class = "margin50" href = "users_tool.php">ユーザー管理ページ</a>
    <a class = "margin50" href = "history_tool.php">購入履歴管理ページ</a>
    <a class = "margin50" href = "seasoning_list.php">ECサイト</a>
</div>
<h2>レシピ詳細の登録</h2>
<form class = "recipe_change" method = "post" enctype = "multipart/form-data">
    <p>料理名(29文字以内):</p>
    <input class = "recipe_name" type = "text" name = "update_recipe_name" value = "<?php print htmlspecialchars($recipe_details['recipe_name'], ENT_QUOTES, 'utf-8'); ?>">
    <p>料理画像:</p>
    <img src = "<?php print $img_dir . $recipe_details['recipe_img']; ?>">
    <input type = "hidden" name = "recipe_img" value = "<?php print $recipe_details['recipe_img']; ?>">
    <input type = "file" name = "update_recipe_img" >
    <p>調味料名:</p>
    <select size = "1" name = "update_item_id">
        <option value = "<?php print htmlspecialchars($recipe_details['item_id'], ENT_QUOTES, 'utf-8'); ?>"><?php print htmlspecialchars($recipe_details['item_name'], ENT_QUOTES, 'utf-8'); ?></option>
        <?php foreach ($name as $i_name) { ?>
            <option value = "<?php print htmlspecialchars($i_name['item_id'], ENT_QUOTES, 'utf-8'); ?>"><?php print htmlspecialchars($i_name['item_name'], ENT_QUOTES, 'utf-8'); ?></option>
        <?php } ?>
    </select>
    <p>レシピの詳細:</p>
    <textarea name = "update_recipe_comment" row = "4" cols = "40" value = "<?php print htmlspecialchars($recipe_details['recipe_comment'], ENT_QUOTES, 'utf-8'); ?>"><?php print htmlspecialchars($recipe_details['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></textarea>
    <p>ステータス:</p>
    <select size = "1" name = "change_recipe_status">
        <!-- 非公開時 -->
        <?php if ($recipe_details['recipe_status'] === 0) { ?>
            <option value = "0">非公開</option>
            <option value = "1">公開</option>
        <!-- 公開時 -->
        <?php } else { ?> 
            <option value = "1">公開</option>
            <option value = "0">非公開</option>
        <?php } ?>
    </select>
    <p>人数(人分):</p>
    <input type = "text" name = "update_person" value = "<?php print htmlspecialchars($recipe_details['person'], ENT_QUOTES, 'utf-8'); ?>">
    <p>材料:</p>
    <textarea name = "update_recipe_material" row = "4" cols = "40"><?php print htmlspecialchars($recipe_details['recipe_material'], ENT_QUOTES, 'utf-8'); ?></textarea>  
    <p>作り方:</p>
    <textarea name = "update_recipe" row = "4" cols = "40"><?php print htmlspecialchars($recipe_details['recipe'], ENT_QUOTES, 'utf-8'); ?></textarea>
    <p>コツ・ポイント:</p>
    <textarea name = "update_point" row = "4" cols = "40"><?php print htmlspecialchars($recipe_details['point'], ENT_QUOTES, 'utf-8'); ?></textarea>
    <br>
    <input class = "btm_update" name = "update_post" type = "submit" value = "変更する">
</form>
</body>
</html>