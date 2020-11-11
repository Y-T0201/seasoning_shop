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

try {
    // データベースに接続
    $dbh = new PDO($dsn, $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    
    $item_name = '';
    
    // 購入する商品の追加
    if (isset($_POST['new_post']) === true) {
        if (isset($_POST['item_name']) === true) {
            $item_name = $_POST['item_name'];
            $item_name = str_replace(array(" "," "),"",$item_name);
        }
        
        if (mb_strlen($item_name) === 0) {
            $err_msg[] = '商品名を入力してください';
        } else if (mb_strlen($item_name) > 12) {
            $err_msg[] = '商品名は12文字以内で入力してください';
        }
        
        $price = '';
        
        if (isset($_POST['price']) === true) {
            $price = $_POST['price'];
            $price = str_replace(array(" "," "),"",$price);
        }
        
        if (preg_match('/^[0-9]+$/', $price) !== 1) {
            $err_msg[] = '値段は0以上の整数を入力してください';
        }
        
        $stock = '';
        
        if (isset($_POST['stock']) === true) {
            $stock = $_POST['stock'];
            $stock = str_replace(array(" "," "),"",$stock);
        }
        
        if (preg_match('/^[0-9]+$/', $stock) !== 1) {
            $err_msg[] = '在庫数は0以上の整数を入力してください';
        }
        
        $item_img = '';
        
        // HTTP POST でファイルがアップロードされたかどうかチェック
        if (is_uploaded_file($_FILES['item_img']['tmp_name']) === true) {
            // 画像の拡張子を取得
            $extension = pathinfo($_FILES['item_img']['name'], PATHINFO_EXTENSION);
            // 指定の拡張子であるかどうかチェック
            if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png') {
                // 保存する新しいファイル名の生成(ユニークな値を設定する)
                $item_img = sha1(uniqid(mt_rand(), true)). '.' . $extension;
                // 同名ファイルが存在しているかチェック
                if (is_file($img_dir . $item_img) !== true) {
                    // アップロードされたファイルを指定ディレクトリに移動して保存
                    if (move_uploaded_file($_FILES['item_img']['tmp_name'], $img_dir . $item_img) !== true) {
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
        
        $item_status = '';
        
        if (isset($_POST['item_status']) === true) {
            $item_status = $_POST['item_status'];
        }    
        
        if ($item_status !== '0' && $item_status !== '1') {
            $err_msg[] = 'ステータスエラー';
        }
        
        $item_comment = '';
        
        if (isset($_POST['item_comment']) === true) {
            $item_comment = $_POST['item_comment'];
            $item_comment = str_replace(array(" "," "),"",$item_comment);
        }
        
        if (mb_strlen($item_comment) === 0) {
            $err_msg[] = '商品の詳細を入力してください';
        } else if (mb_strlen($item_comment) > 98) {
            $err_msg[] = '詳細は98文字以内で入力してください';
        } 
         
        if (count($err_msg) === 0) {
            // トランザクション開始
            $dbh->beginTransaction();
            try {
                // 商品情報テーブルにデータ作成
                $sql = 'insert into ec_item_master(item_name, price, item_img, item_status, item_comment, create_datetime)
                        VALUES(?, ?, ?, ?, ?, NOW());';
                        
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $item_name, PDO::PARAM_STR);
                $stmt->bindvalue(2, $price, PDO::PARAM_INT);
                $stmt->bindvalue(3, $item_img, PDO::PARAM_STR);
                $stmt->bindvalue(4, $item_status, PDO::PARAM_INT);
                $stmt->bindvalue(5, $item_comment, PDO::PARAM_STR);
                // SQLを実行
                $stmt->execute();
                // 登録したデータにIDを取得して出力
                $id = $dbh->lastInsertId();
                
                // 在庫数情報テーブルにデータを作成
                $sql = 'insert into ec_item_stock(item_id, stock, create_datetime, update_datetime)
                        VALUES(?, ?, NOW(), NOW());';
                
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $id, PDO::PARAM_INT);
                $stmt->bindvalue(2, $stock, PDO::PARAM_INT);        
                // SQLを実行
                $stmt->execute();
                // コミット処理
                $dbh->commit();
                echo 'データが登録できました';
               
            } catch (PDOExeption $e) {
                // ロールバック処理
                $dbh->rollback();
                // 例外をスロー
                throw $e;
            }
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
    $sql = 'SELECT ec_item_master.item_id, item_name, price, item_img, item_status, item_comment, stock
            FROM ec_item_master
            JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id';
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $rows = $stmt->fetchAll();
    // 1行ずつ結果を配列で取得
    foreach ($rows as $row) {
        $data[] = $row;
    }
    
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
<a class = "margin50" href = "recipe_tool.php">レシピ管理ページ</a>
<a class = "margin50" href = "users_tool.php">ユーザー管理ページ</a>
<a class = "margin50" href = "history_tool.php">購入履歴管理ページ</a>
<a class = "margin50" href = "seasoning_list.php">ECサイト</a>
<h2>商品の登録</h2>
<form method = "post" enctype = "multipart/form-data">
    <p>商品名(12文字以内):<input type = "text" name = "item_name"></p>
    <p>値段(税抜き):<input type = "text" name = "price"></p>
    <p>個数:<input type = "text" name = "stock"></p>
    <p>商品画像:<input type = "file" name = "item_img" ></p>
    <!--<p>調味料の種類:-->
    <!--<select size = "1" name = "status">-->
    <!--    <option value = ""></option>-->
    <!--</select>-->
    <p>ステータス:
        <select size = "1" name = "item_status">
            <option value = "1">公開</option>
            <option value = "0">非公開</option>
        </select>
    </p>
    <p>詳細(98文字以内):</p>
    <textarea name = "item_comment" row = "4" cols = "40"></textarea>
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
    <?php foreach ($data as $value) { ?>
        <!--非公開時の処理-->
        <?php if ($value['item_status'] === 0) { ?>
            <tr class = "gray">
        <!--公開時の処理-->
        <?php } else { ?>
            <tr>
        <?php } ?>
            <td><?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?></td>
            <!--画像を変更する-->
            <td>
                <img src = "<?php print $img_dir . $value['item_img']; ?>">
                <form method = "post" enctype = "multipart/form-data">    
                    <input type = "file" name = "update_item_img" ></p>
                    <input name = "update_post" type = "submit" value = "変更する"> 
                    <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                    <input type = "hidden" name = "process_kind" value = "update_item_img">
                </form>        
            </td>
            <!--商品名を変更する-->
            <form method = "post">
            <td>
                <input type = "text" name = "update_item_name" value = "<?php print htmlspecialchars($value['item_name'], ENT_QUOTES, 'utf-8'); ?>"><br>
                <input name = "update_post" type = "submit" value = "変更する"> 
                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "update_item_name">
            </td>
            </form>
            <!--価格を変更する-->
            <form method = "post">
            <td>
                <input type = "text" class = "wd100" name = "update_price" value = "<?php print htmlspecialchars(number_format($value['price']), ENT_QUOTES, 'utf-8'); ?>">円
                <input name = "update_post" type = "submit" value = "変更する"> 
                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "update_price">
            </td>
            </form>
            <!--<td></td>-->
            <!--商品の詳細を変更する-->
            <form method = "post">
            <td>
                <textarea name = "update_item_comment" row = "4" cols = "40"><?php print htmlspecialchars($value['item_comment'], ENT_QUOTES, 'utf-8'); ?></textarea>
                <input name = "update_post" type = "submit" value = "変更する"> 
                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "update_item_comment">
            </td>
            </form>
            <!--在庫数を変更する-->
            <form method = "post">
            <td>
                <input type = "text"  class = "wd100" name = "update_stock" value = "<?php print htmlspecialchars(number_format($value['stock']), ENT_QUOTES, 'utf-8'); ?>">個&nbsp;&nbsp;
                <input name = "update_post" type = "submit" value = "変更する"> 
                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "update_stock">
            </td>
            </form>
            <!--ステータスを変更する-->
            <form method = "post">
            <td>
                <!--非公開時の処理-->
                <?php if ($value['item_status'] === 0) { ?>
                <input type = "submit" value = "非公開→公開にする">
                <input type = "hidden" name = "change_item_status" value = "1">
                <!--公開時の処理-->
                <?php } else { ?>
                <input type = "submit" value = "公開→非公開にする">            
                <input type = "hidden" name = "change_item_status" value = "0">
                <?php } ?>
                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "change_item_status">
            </td>
            </form>
            <form method = "post">
            <td>
                <input type = "submit" value = "削除する">
                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "item_delete">
            </td>
            </form>
        </tr>
    <?php } ?>
</table>
</body>
</html>