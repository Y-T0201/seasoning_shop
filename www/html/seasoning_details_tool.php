<?php
$host = 'mysql';
$username = 'root';
$password = 'root';
$dbname = 'seasoning_shop';
$charset = 'utf8';

$img_dir = './item_img/'; // アップロードした画像ファイルの保存ディレクトリ
$err_msg = array();
$data = array();

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

// 詳細を表示する商品のidを取得する
$item_id = '';
if (isset($_GET['item_id']) === true) {
    $item_id = $_GET['item_id'];
}

try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      
    // 商品情報の変更
    if (isset($_POST['update_post']) === true) {
        // 商品名の変更
        if (isset($_POST['update_item_name']) === true) {
            $update_item_name = $_POST['update_item_name'];
            $update_item_name = str_replace(array(" "," "),"",$update_item_name);
        }
        
        if (mb_strlen($update_item_name) === 0) {
            $err_msg[] = '商品名を入力してください';
        } else if (mb_strlen($update_item_name) > 12) {
            $err_msg[] = '商品名は12文字以内で入力してください';
        }

        // 画像の変更
        // if (isset($_POST['update_item_img']) === true) {
            // HTTP POST でファイルがアップロードされたかどうかチェック
            if (is_uploaded_file($_FILES['update_item_img']['tmp_name']) === true) {
                // 画像の拡張子を取得
                $extension = pathinfo($_FILES['update_item_img']['name'], PATHINFO_EXTENSION);
                // 指定の拡張子であるかどうかチェック
                if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png') {
                    // 保存する新しいファイル名の生成(ユニークな値を設定する)
                    $update_item_img = sha1(uniqid(mt_rand(), true)). '.' . $extension;
                    // 同名ファイルが存在しているかチェック
                    if (is_file($img_dir . $update_item_img) !== true) {
                        
                        $sql = 'UPDATE ec_item_master
                        SET item_img = ?
                        WHERE ec_item_master.item_id = ?';
        
                        // SQL文を実行する準備
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $update_item_img, PDO::PARAM_STR);
                        $stmt->bindvalue(2, $item_id, PDO::PARAM_INT);
                        // SQLを実行
                        $stmt->execute();
                        
                        echo '画像を変更しました。';

                        // アップロードされたファイルを指定ディレクトリに移動して保存
                        if (move_uploaded_file($_FILES['update_item_img']['tmp_name'], $img_dir . $update_item_img) !== true) {
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
        // }

        // 価格の変更
        if (isset($_POST['update_price']) === true) {
            $update_price = $_POST['update_price'];
            $update_price = str_replace(array(" "," "),"",$update_price);
        }
        
        if (preg_match('/^[0-9]+$/', $update_price) !== 1) {
            $err_msg[] = '値段は0以上の整数を入力してください';
        }

        // 商品詳細の変更
        if (isset($_POST['update_item_comment']) === true) {
            $update_item_comment = $_POST['update_item_comment'];
            $update_item_comment = str_replace(array(" "," "),"",$update_item_comment);
        }
        
        if (mb_strlen($update_item_comment) === 0) {
            $err_msg[] = '商品の詳細を入力してください';
        } else if (mb_strlen($update_item_comment) > 98) {
            $err_msg[] = '詳細は98文字以内で入力してください';
        }

        // 在庫数の変更
        if (isset($_POST['update_stock']) === true) {
            $update_stock = $_POST['update_stock'];
        }
        
        if (preg_match('/^[0-9]+$/', $update_stock) !== 1) {
            $err_msg[] = '在庫数は0以上の整数を入力してください';
        }

        // ステータスの変更
        if (isset($_POST['change_item_status']) === true) {
            $change_item_status = $_POST['change_item_status'];
        }

        if ($change_item_status !== '0' && $change_item_status !== '1') {
            $err_msg[] = 'ステータスエラー';
        }

        // ブランド名
        if (isset($_POST['update_brand']) === true) {
            $update_brand = $_POST['update_brand'];
            $update_brand = str_replace(array(" "," "),"",$update_brand);
        }
        
        if (mb_strlen($update_brand) === 0) {
            $err_msg[] = 'ブランド名を入力してください';
        }
        
        // メーカー名
        if (isset($_POST['update_maker']) === true) {
            $update_maker = $_POST['update_maker'];
            $update_maker = str_replace(array(" "," "),"",$update_maker);
        }
        
        if (mb_strlen($update_maker) === 0) {
            $err_msg[] = 'メーカー名を入力してください';
        }

        // 原産国名
        if (isset($_POST['update_country']) === true) {
            $update_country = $_POST['update_country'];
            $update_country = str_replace(array(" "," "),"",$update_country);
        }
        
        if (mb_strlen($update_country) === 0) {
            $err_msg[] = '原産国名を入力してください';
        }

        // 原材料
        if (isset($_POST['update_material']) === true) {
            $update_material = $_POST['update_material'];
            $update_material = str_replace(array(" "," "),"",$update_material);
        }
        
        if (mb_strlen($update_material) === 0) {
            $err_msg[] = '原材料を入力してください';
        }

        // 梱包サイズ
        // 幅      
        if (isset($_POST['update_width']) === true) {
            $update_width = $_POST['update_width'];
            $update_width = str_replace(array(" "," "),"",$update_width);
        }
        
        // if (preg_match('/^[0-9]+.?[0-9]*$/', $update_width) !== 1) {
        if (preg_match('/^[0-9]+$/', $update_width) !== 1) {
            $err_msg[] = '梱包サイズの幅は整数を入力してください';
        }
        
        // 奥行
        if (isset($_POST['update_depth']) === true) {
            $update_depth = $_POST['update_depth'];
            $update_depth = str_replace(array(" "," "),"",$update_depth);
        }
        
        if (preg_match('/^[0-9]+$/', $update_depth) !== 1) {
            $err_msg[] = '梱包サイズの奥行は整数を入力してください';
        }

        // 高さ
        if (isset($_POST['update_height']) === true) {
            $update_height = $_POST['update_height'];
            $update_height = str_replace(array(" "," "),"",$update_height);
        }
        
        if (preg_match('/^[0-9]$/', $update_height) !== 1) {
            $err_msg[] = '梱包サイズの高さは整数を入力してください';
        }
        
        // 重さ
        if (isset($_POST['update_weight']) === true) {
            $update_weight = $_POST['update_weight'];
            $update_weight = str_replace(array(" "," "),"",$update_weight);
        }
        
        if (preg_match('/^[0-9]+$/', $update_weight) !== 1) {
            $err_msg[] = '商品の重量は0以上の整数を入力してください';
        }             

        if (count($err_msg) === 0) {
            // $sql = 'UPDATE ec_item_master
            //         JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
            //         JOIN ec_item_details ON ec_item_master.item_id = ec_item_details.item_id
            //         SET item_name = ?, item_img = ?, price = ?, item_comment = ?, stock = ?, item_status = ?,
            //             brand = ?, maker = ?, country = ?, material = ?, width = ?, depth = ?, height = ?, weight = ?
            //         WHERE ec_item_master.item_id = ?';
    
            // // SQL文を実行する準備
            // $stmt = $dbh->prepare($sql);
            // $stmt->bindvalue(1, $update_item_name, PDO::PARAM_STR);
            // $stmt->bindvalue(2, $update_item_img, PDO::PARAM_STR);
            // $stmt->bindvalue(3, $update_price, PDO::PARAM_INT);
            // $stmt->bindvalue(4, $update_item_comment, PDO::PARAM_STR);
            // $stmt->bindvalue(5, $update_stock, PDO::PARAM_INT);
            // $stmt->bindValue(6, $change_item_status, PDO::PARAM_INT);
            // $stmt->bindvalue(7, $update_brand, PDO::PARAM_STR);
            // $stmt->bindvalue(8, $update_maker, PDO::PARAM_STR);
            // $stmt->bindvalue(9, $update_country, PDO::PARAM_STR);
            // $stmt->bindvalue(10, $update_material, PDO::PARAM_STR);
            // $stmt->bindvalue(11, $update_width, PDO::PARAM_INT);
            // $stmt->bindvalue(12, $update_depth, PDO::PARAM_INT);
            // $stmt->bindvalue(13, $update_height, PDO::PARAM_INT);
            // $stmt->bindvalue(14, $update_weight, PDO::PARAM_INT); 
            // $stmt->bindvalue(15, $item_id, PDO::PARAM_INT);

            $sql = 'UPDATE ec_item_master
                    JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
                    JOIN ec_item_details ON ec_item_master.item_id = ec_item_details.item_id
                    SET item_name = ?, price = ?, item_comment = ?, stock = ?, item_status = ?,
                        brand = ?, maker = ?, country = ?, material = ?, width = ?, depth = ?, height = ?, weight = ?
                    WHERE ec_item_master.item_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_item_name, PDO::PARAM_STR);
            $stmt->bindvalue(2, $update_price, PDO::PARAM_INT);
            $stmt->bindvalue(3, $update_item_comment, PDO::PARAM_STR);
            $stmt->bindvalue(4, $update_stock, PDO::PARAM_INT);
            $stmt->bindValue(5, $change_item_status, PDO::PARAM_INT);
            $stmt->bindvalue(6, $update_brand, PDO::PARAM_STR);
            $stmt->bindvalue(7, $update_maker, PDO::PARAM_STR);
            $stmt->bindvalue(8, $update_country, PDO::PARAM_STR);
            $stmt->bindvalue(9, $update_material, PDO::PARAM_STR);
            $stmt->bindvalue(10, $update_width, PDO::PARAM_INT);
            $stmt->bindvalue(11, $update_depth, PDO::PARAM_INT);
            $stmt->bindvalue(12, $update_height, PDO::PARAM_INT);
            $stmt->bindvalue(13, $update_weight, PDO::PARAM_INT); 
            $stmt->bindvalue(14, $item_id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();            
            echo '商品情報を変更しました。';
        }
    }
    
    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT ec_item_master.item_id, item_name, price, item_img, item_status, item_comment, stock,
            brand, maker, country, material, width, depth, height, weight
            FROM ec_item_master
            JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
            LEFT JOIN ec_item_details ON ec_item_master.item_id = ec_item_details.item_id
            WHERE ec_item_master.item_id = ?';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindValue(1,$item_id,PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $item_details = $stmt->fetch();

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
        h2, table {
            width: 1300px;
        }
    
        h2 {
            border-top: solid 1px;
        }
    
        .margin50 {
            margin-right: 50px;
        }
        
        .item_change {
            margin-left: 50px;
        }
        textarea {
            width: 500px;
            height: 100px;
        }
    
        .new_submit {
            display: block;
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
        
        .wd100 {
            width: 100px;
        }
        
        .btm_logout {
            margin: 8px 0px 0px 50px;
            padding: 0px;
            height: 50px;
            width: 100px;
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
    <form class = "btm_logout" method = "post">
        <input class = "btm_logout" type = "submit" name = "btm_logout" value = "ログアウト">
    </form>
</div>
<?php foreach ($err_msg as $value) { ?>
    <p><?php print $value; ?></p>
<?php } ?>
<a class = "margin50" href = "seasoning_tool.php">調味料管理ページ</a>
<a class = "margin50" href = "recipe_tool.php">レシピ管理ページ</a>
<a class = "margin50" href = "users_tool.php">ユーザー管理ページ</a>
<a class = "margin50" href = "history_tool.php">購入履歴管理ページ</a>
<a class = "margin50" href = "seasoning_list.php">ECサイト</a>
<h2>商品情報の変更</h2>
<form class = "item_change" method = "post" enctype = "multipart/form-data">
    <p>商品名(12文字以内):<input type = "text" name = "update_item_name" value = "<?php print htmlspecialchars($item_details['item_name'], ENT_QUOTES, 'utf-8'); ?>">
    <p>画像:</P>
        <img src = "<?php print $img_dir . $item_details['item_img']; ?>">
        <input type = "file" name = "update_item_img">
    <p>価格:<input type = "text" class = "wd100" name = "update_price" value = "<?php print htmlspecialchars(number_format($item_details['price']), ENT_QUOTES, 'utf-8'); ?>">円</p>
    <p>商品の詳細(98文字以内):</p>
    <textarea name = "update_item_comment" row = "4" cols = "40"><?php print htmlspecialchars($item_details['item_comment'], ENT_QUOTES, 'utf-8'); ?></textarea>
    <p>在庫数:<input type = "text"  class = "wd100" name = "update_stock" value = "<?php print htmlspecialchars(number_format($item_details['stock']), ENT_QUOTES, 'utf-8'); ?>">個</p>
    <p>ステータス:
        <select size = "1" name = "change_item_status">
            <!-- 非公開時 -->
            <?php if ($item_details['item_status'] === 0) { ?>
                <option value = "0">非公開</option>
                <option value = "1">公開</option>
            <!-- 公開時 -->
            <?php } else { ?> 
                <option value = "1">公開</option>
                <option value = "0">非公開</option>
            <?php } ?>
        </select>
    </p>
    <p>ブランド:<input type = "text" name = "update_brand" value = "<?php print htmlspecialchars($item_details['brand'], ENT_QUOTES, 'utf-8'); ?>"></p>
    <p>メーカー:<input type = "text" name = "update_maker" value = "<?php print htmlspecialchars($item_details['maker'], ENT_QUOTES, 'utf-8'); ?>"></p>
    <p>原産国名:<input type = "text" name = "update_country" value = "<?php print htmlspecialchars($item_details['country'], ENT_QUOTES, 'utf-8'); ?>"></p>
    <p>原材料(100文字以内):</p>
    <textarea name = "update_material" row = "4" cols = "40"><?php print htmlspecialchars($item_details['material'], ENT_QUOTES, 'utf-8'); ?></textarea>
    <p>梱包サイズ(cm):</p>
    <p>幅:<input class = "wd100" type = "text" name = "update_width" value = "<?php print htmlspecialchars(number_format($item_details['width']), ENT_QUOTES, 'utf-8'); ?>">cm</p>
    <p>奥行:<input class = "wd100" type = "text" name = "update_depth" value = "<?php print htmlspecialchars(number_format($item_details['depth']), ENT_QUOTES, 'utf-8'); ?>">cm</p>
    <p>高さ:<input class = "wd100" type = "text" name = "update_height" value = "<?php print htmlspecialchars(number_format($item_details['height']), ENT_QUOTES, 'utf-8'); ?>">cm</p>
    <p>商品の重量(g):<input class = "wd100" type = "text" name = "update_weight" value = "<?php print htmlspecialchars(number_format($item_details['weight']), ENT_QUOTES, 'utf-8'); ?>">g</p>
    <input name = "update_post" type = "submit" value = "変更する"> 
</form>
</body>
</html>