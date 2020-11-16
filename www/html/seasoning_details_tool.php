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
      
    // 商品詳細の追加
    if (isset($_POST['new_post']) === true) {
        // ブランド名
        $brand = '';
        if (isset($_POST['brand']) === true) {
            $brand = $_POST['brand'];
            $brand = str_replace(array(" "," "),"",$brand);
        }
        
        if (mb_strlen($brand) === 0) {
            $err_msg[] = 'ブランド名を入力してください';
        }
        
        // メーカー名
        $maker = '';
        if (isset($_POST['maker']) === true) {
            $maker = $_POST['maker'];
            $maker = str_replace(array(" "," "),"",$maker);
        }
        
        if (mb_strlen($maker) === 0) {
            $err_msg[] = 'メーカー名を入力してください';
        }

        // 原産国名
        $country = '';
        if (isset($_POST['country']) === true) {
            $country = $_POST['maker'];
            $country = str_replace(array(" "," "),"",$country);
        }
        
        if (mb_strlen($country) === 0) {
            $err_msg[] = '原産国名を入力してください';
        }

        // 原材料
        $material = '';
        if (isset($_POST['material']) === true) {
            $material = $_POST['material'];
            $material = str_replace(array(" "," "),"",$material);
        }
        
        if (mb_strlen($material) === 0) {
            $err_msg[] = '原材料を入力してください';
        }

        // 梱包サイズ
        // 幅
        $width = '';
        
        if (isset($_POST['width']) === true) {
            $width = $_POST['width'];
            $width = str_replace(array(" "," "),"",$width);
        }
        
        if (preg_match('/^[0-9]+.[0-9]+$/', $width) !== 1) {
            $err_msg[] = '梱包サイズの幅は0以上の整数を入力してください';
        }
        
        // 奥行
        $depth = '';
        
        if (isset($_POST['depth']) === true) {
            $depth = $_POST['depth'];
            $depth = str_replace(array(" "," "),"",$depth);
        }
        
        if (preg_match('/^[0-9]+.[0-9]+$/', $depth) !== 1) {
            $err_msg[] = '梱包サイズの奥行は0以上の整数を入力してください';
        }

        // 高さ
        $height = '';

        if (isset($_POST['height']) === true) {
            $height = $_POST['height'];
            $height = str_replace(array(" "," "),"",$height);
        }
        
        if (preg_match('/^[0-9]+.[0-9]+$/', $height) !== 1) {
            $err_msg[] = '梱包サイズの高さは0以上の整数を入力してください';
        }
        
        // 重さ
        $weight = '';

        if (isset($_POST['weight']) === true) {
            $weight = $_POST['weight'];
            $weight = str_replace(array(" "," "),"",$weight);
        }
        
        if (preg_match('/^[0-9]+.[0-9]+$/', $weight) !== 1) {
            $err_msg[] = '梱包サイズの高さは0以上の整数を入力してください';
        }        
        
        // 既に登録されている場合はエラー表示をする
        

        if (count($err_msg) === 0) {

            // 商品情報テーブルにデータ作成
            $sql = 'insert into ec_item_details(item_id, brand, maker, country, material, width, depth, height, weight)
                    VALUES(?, ?, ?, ?, ?, ?, ?, ? ,?);';
                    
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $item_id, PDO::PARAM_INT);
            $stmt->bindvalue(2, $brand, PDO::PARAM_STR);
            $stmt->bindvalue(3, $maker, PDO::PARAM_STR);
            $stmt->bindvalue(4, $country, PDO::PARAM_STR);
            $stmt->bindvalue(5, $material, PDO::PARAM_STR);
            $stmt->bindvalue(6, $width, PDO::PARAM_INT);
            $stmt->bindvalue(7, $depth, PDO::PARAM_INT);
            $stmt->bindvalue(8, $height, PDO::PARAM_INT);
            $stmt->bindvalue(9, $weight, PDO::PARAM_INT);                
            // SQLを実行
            $stmt->execute();

            echo 'データが登録できました';
        } 
    }
    
    $process_kind = '';
    
    if (isset($_POST['process_kind'])) {
        $process_kind = $_POST['process_kind'];
    }
    
    // 送られてきた非表示データに応じて処理を振り分ける。
    
    $update_item_img = '';
    
    if ($process_kind === 'update_item_img') {
        // 画像の変更
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
        } else {
            $err_msg[] = 'ファイルを選択してください';
        }
    
        if (isset($_POST['item_id']) === true) {
            $id = $_POST['item_id'];
        }
        
        // 画像の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_item_master
                    SET item_img = ?, update_datetime = NOW()
                    WHERE item_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_item_img, PDO::PARAM_STR);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '画像の変更が成功しました。'; 
        }

    }
    
    $update_item_name = '';
    
    if ($process_kind === 'update_item_name') {
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
        
        if (isset($_POST['item_id']) === true) {
            $id = $_POST['item_id'];
        }
    
        // 商品名の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_item_master
                    SET item_name = ?, update_datetime = NOW()
                    WHERE item_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_item_name, PDO::PARAM_STR);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '商品名の変更が成功しました。';
        }
    }
    
    $update_price = '';
    
    if ($process_kind === 'update_price') {
        // 価格の変更
        if (isset($_POST['update_price']) === true) {
            $update_price = $_POST['update_price'];
            $update_price = str_replace(array(" "," "),"",$update_price);
        }
        
        if (preg_match('/^[0-9]+$/', $update_price) !== 1) {
            $err_msg[] = '値段は0以上の整数を入力してください';
        }
        
        if (isset($_POST['item_id']) === true) {
            $id = $_POST['item_id'];
        }
    
        // 価格の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_item_master
                    SET price = ?, update_datetime = NOW()
                    WHERE item_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_price, PDO::PARAM_INT);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '価格の変更が成功しました。';
        }
    }
    
    $update_item_comment = '';
    
    if ($process_kind === 'update_item_comment') {
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
        
        if (isset($_POST['item_id']) === true) {
            $id = $_POST['item_id'];
        }
    
        // 商品の詳細の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_item_master
                    SET item_comment = ?, update_datetime = NOW()
                    WHERE item_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_item_comment, PDO::PARAM_STR);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '商品の詳細の変更が成功しました。';
        }
    }    
    
    $update_stock = '';
    
    if ($process_kind === 'update_stock') {
        // 在庫数の変更
        if (isset($_POST['update_stock']) === true) {
            $update_stock = $_POST['update_stock'];
        }
        
        if (preg_match('/^[0-9]+$/', $update_stock) !== 1) {
            $err_msg[] = '在庫数は0以上の整数を入力してください';
        }
        
        if (isset($_POST['item_id']) === true) {
            $id = $_POST['item_id'];
        }
        
        // 在庫数の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_item_stock
                    SET stock = ?, update_datetime = NOW()
                    WHERE item_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_stock, PDO::PARAM_INT);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '在庫数の変更が成功しました。';
        }
    }
    
    $change_item_status = '';
    
    if ($process_kind === 'change_item_status') {
        // ステータスの変更
        if (isset($_POST['change_item_status']) === true) {
            $change_item_status = $_POST['change_item_status'];
        }
        
        if ($change_item_status !== '0' && $change_item_status !== '1') {
            $err_msg[] = 'ステータスエラー';
        }
        
        if (isset($_POST['item_id']) === true) {
            $id = $_POST['item_id'];
        }
        
        if (count($err_msg) === 0) {
            // ステータスをデータに更新
            $sql = 'UPDATE ec_item_master
                    SET item_status = ?, update_datetime = NOW()
                    WHERE item_id = ?';
                    
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1,$change_item_status,PDO::PARAM_INT);
            $stmt->bindValue(2,$id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo 'ステータスの変更が成功しました';
        }
    }

    if ($process_kind === 'item_delete') {
        // データの削除

        if (isset($_POST['item_id']) === true) {
            $id = $_POST['item_id'];
        }
        
        if (count($err_msg) === 0) {
            // ステータスをデータに更新
            $sql = 'DELETE
                    FROM ec_item_master
                    WHERE item_id = ?';
            
            // $sql = 'DELETE ec_item_master
            //         FROM ec_item_master
            //         JOIN ec_item_stock
            //         ON ec_item_master.item_id = ec_item_stock.item_id
            //         WHERE item_id = ?';
                    
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1,$id,PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            $sql = 'DELETE
                    FROM ec_item_stock
                    WHERE item_id = ?';
                    
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1,$id,PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
     
            echo '商品情報を削除しました';
        }
    }
    
    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT ec_item_master.item_id, item_name, price, item_img, item_status, item_comment, stock,
            brand, maker, country, material, width, depth, height, weight
            FROM ec_item_master
            JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
            JOIN LEFT ec_item_details ON ec_item_master.item_id = ec_item_details.item_id
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
        
        textarea {
            width: 250px;
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
<h2>商品詳細の登録</h2>
<form method = "post">
    <p>商品名:<?php print htmlspecialchars($item_details['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
    <p>ブランド:<input type = "text" name = "brand"></p>
    <p>メーカー:<input type = "text" name = "maker"></p>
    <p>原産国名:<input type = "text" name = "country"></p>
    <p>原材料(100文字以内):</p>
    <textarea name = "material" row = "4" cols = "40"></textarea>
    <p>梱包サイズ(cm):</p>
    <p>幅:<input type = "text" name = "width"></p>
    <p>奥行:<input type = "text" name = "depth"></p>
    <p>高さ:<input type = "text" name = "height"></p>
    <p>商品の重量(g):<input type = "text" name = "weight"></p>
    <div class = new_submit><input name = "new_post" type = "submit" value = "商品を登録する"></div>
</form>
<h2>商品情報の一覧・変更</h2>
<table>
    <tr>
        <th>No</th>
        <th>商品画像</th>
        <th>商品名</th>
        <th>価格(税抜き)</th>
        <!--<th>調味料の種類</th>-->
        <th>詳細</th>
        <th>在庫数</th>
        <th>ステータス</th>
        <th>操作</th>
    </tr>
    <!--非公開時の処理-->
    <?php if ($item_details['item_status'] === 0) { ?>
        <tr class = "gray">
    <!--公開時の処理-->
    <?php } else { ?>
        <tr>
    <?php } ?>
        <td><?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?></td>
        <!--画像を変更する-->
        <td>
            <img src = "<?php print $img_dir . $item_details['item_img']; ?>">
            <form method = "post" enctype = "multipart/form-data">    
                <input type = "file" name = "update_item_img" ></p>
                <input name = "update_post" type = "submit" value = "変更する"> 
                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "update_item_img">
            </form>        
        </td>
        <!--商品名を変更する-->
        <form method = "post">
        <td>
            <input type = "text" name = "update_item_name" value = "<?php print htmlspecialchars($item_details['item_name'], ENT_QUOTES, 'utf-8'); ?>"><br>
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_item_name">
        </td>
        </form>
        <!--価格を変更する-->
        <form method = "post">
        <td>
            <input type = "text" class = "wd100" name = "update_price" value = "<?php print htmlspecialchars(number_format($item_details['price']), ENT_QUOTES, 'utf-8'); ?>">円
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_price">
        </td>
        </form>
        <!--<td></td>-->
        <!--商品の詳細を変更する-->
        <form method = "post">
        <td>
            <textarea name = "update_item_comment" row = "4" cols = "40"><?php print htmlspecialchars($item_details['item_comment'], ENT_QUOTES, 'utf-8'); ?></textarea>
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_item_comment">
        </td>
        </form>
        <!--在庫数を変更する-->
        <form method = "post">
        <td>
            <input type = "text"  class = "wd100" name = "update_stock" value = "<?php print htmlspecialchars(number_format($item_details['stock']), ENT_QUOTES, 'utf-8'); ?>">個&nbsp;&nbsp;
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_stock">
        </td>
        </form>
        <!--ステータスを変更する-->
        <form method = "post">
        <td>
            <!--非公開時の処理-->
            <?php if ($item_details['item_status'] === 0) { ?>
            <input type = "submit" value = "非公開→公開にする">
            <input type = "hidden" name = "change_item_status" value = "1">
            <!--公開時の処理-->
            <?php } else { ?>
            <input type = "submit" value = "公開→非公開にする">            
            <input type = "hidden" name = "change_item_status" value = "0">
            <?php } ?>
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "change_item_status">
        </td>
        </form>
        <td>
        <form class = "delete_form" method = "post">
            <input class = "delete_btm" type = "submit" value = "削除する">
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "item_delete">
        </form>
        </td>
    </tr>
</table><br>
<table>
    <tr>
        <th>ブランド</th>
        <th>メーカー</th>
        <th>原産国名</th>
        <th>原材料</th>
        <th>梱包サイズ</th>
        <th>商品の重量</th>
    </tr>
    <!--非公開時の処理-->
    <?php if ($item_details['item_status'] === 0) { ?>
        <tr class = "gray">
    <!--公開時の処理-->
    <?php } else { ?>
        <tr>
    <?php } ?>
         <!--ブランド名を変更する-->
         <form method = "post">
        <td>
            <input type = "text" name = "update_maker" value = "<?php print htmlspecialchars($item_details['brand'], ENT_QUOTES, 'utf-8'); ?>"><br>
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_brand">
        </td>   
        <!--メーカー名を変更する-->
        <form method = "post">
        <td>
            <input type = "text" name = "update_maker" value = "<?php print htmlspecialchars($item_details['maker'], ENT_QUOTES, 'utf-8'); ?>"><br>
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_maker">
        </td>
        </form>
        <!--原産国名を変更する-->
        <form method = "post">
        <td>
            <input type = "text" name = "update_country" value = "<?php print htmlspecialchars($item_details['country'], ENT_QUOTES, 'utf-8'); ?>">
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_country">
        </td>
        </form>
        <!--原材料を変更する-->
        <form method = "post">
        <td>
            <textarea name = "update_material" row = "4" cols = "40"><?php print htmlspecialchars($item_details['material'], ENT_QUOTES, 'utf-8'); ?></textarea>
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_material">
        </td>
        </form>
        <!--梱包サイズを変更する-->
        <form method = "post">
        <td>
            幅:<input class = "wd100" type = "text" name = "update_width" value = "<?php print htmlspecialchars(number_format($item_details['width']), ENT_QUOTES, 'utf-8'); ?>"><br>
            奥行:<input class = "wd100" type = "text" name = "update_depth" value = "<?php print htmlspecialchars(number_format($item_details['depth']), ENT_QUOTES, 'utf-8'); ?>"><br>
            高さ:<input class = "wd100" type = "text" name = "update_height" value = "<?php print htmlspecialchars(number_format($item_details['height']), ENT_QUOTES, 'utf-8'); ?>">cm
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_size">
        </td>
        </form>
        <!--重量を変更する-->
        <form method = "post">
        <td>
            <input type = "text" name = "update_weight" value = "<?php print htmlspecialchars(number_format($item_details['weight']), ENT_QUOTES, 'utf-8'); ?>">g
            <input name = "update_post" type = "submit" value = "変更する"> 
            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($item_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
            <input type = "hidden" name = "process_kind" value = "update_weight">
        </td>
        </form>
    </tr>
</table>
</body>
</html>