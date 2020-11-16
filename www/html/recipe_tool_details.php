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
    
    // rレシピ詳細の追加
    if (isset($_POST['new_post']) === true) {
        $person = '';
        // 人数
        if (isset($_POST['person']) === true) {
            $person = $_POST['person'];
            $person = str_replace(array(" "," "),"",$person);
        }
        
        if (preg_match('/^[0-9]+$/', $person) !== 1) {
            $err_msg[] = '人数は0以上の整数を入力してください';
        } 
        
        $recipe_material = '';
        
        if (isset($_POST['recipe_material']) === true) {
            $recipe_material = $_POST['recipe_material'];
            $recipe_material = str_replace(array(" "," "),"",$recipe_material);
        }

        $recipe = '';
        
        if (isset($_POST['recipe']) === true) {
            $recipe = $_POST['recipe'];
            $recipe = str_replace(array(" "," "),"",$recipe);
        }        

        $point = '';
        
        if (isset($_POST['point']) === true) {
            $point = $_POST['point'];
            $point = str_replace(array(" "," "),"",$point);
        }  
         
        if (count($err_msg) === 0) {
            // 商品情報テーブルにデータ作成
            $sql = 'insert into ec_recipe_details(recipe_id, person, recipe_material, recipe, point)
                    VALUES(?, ?, ?, ?, ?);';
                    
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $recipe_id, PDO::PARAM_INT);
            $stmt->bindvalue(2, $person, PDO::PARAM_INT);
            $stmt->bindvalue(3, $recipe_material, PDO::PARAM_STR);
            $stmt->bindvalue(4, $recipe, PDO::PARAM_STR);
            $stmt->bindvalue(5, $point, PDO::PARAM_STR);
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
    
    $update_recipe_img = '';
    
    if ($process_kind === 'update_recipe_img') {
        // 画像の変更
        // HTTP POST でファイルがアップロードされたかどうかチェック

        if (is_uploaded_file($_FILES['update_recipe_img']['tmp_name']) === true) {
            // 画像の拡張子を取得
            $extension = pathinfo($_FILES['update_recipe_img']['name'], PATHINFO_EXTENSION);
            // 指定の拡張子であるかどうかチェック
            if ($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png') {
                // 保存する新しいファイル名の生成(ユニークな値を設定する)
                $update_recipe_img = sha1(uniqid(mt_rand(), true)). '.' . $extension;
                // 同名ファイルが存在しているかチェック
                if (is_file($img_dir . $update_recipe_img) !== true) {
                    // アップロードされたファイルを指定ディレクトリに移動して保存
                    if (move_uploaded_file($_FILES['update_recipe_img']['tmp_name'], $img_dir . $update_recipe_img) !== true) {
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
    
        if (isset($_POST['recipe_id']) === true) {
            $id = $_POST['recipe_id'];
        }
    
        // 画像の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_recipe_master
                    SET recipe_img = ?, update_datetime = NOW()
                    WHERE recipe_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_recipe_img, PDO::PARAM_STR);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '画像の変更が成功しました。';   
    
        }
    }
    
    $update_recipe_name = '';
    
    if ($process_kind === 'update_recipe_name') {
        // 商品名の変更
        if (isset($_POST['update_recipe_name']) === true) {
            $update_recipe_name = $_POST['update_recipe_name'];
            $update_recipe_name = str_replace(array(" "," "),"",$update_recipe_name);
        }
        
        if (mb_strlen($update_recipe_name) === 0) {
            $err_msg[] = '料理名を入力してください';
        } else if (mb_strlen($update_recipe_name) > 29) {
            $err_msg[] = '料理名は29文字以内で入力してください';
        } 
        
        if (isset($_POST['recipe_id']) === true) {
            $id = $_POST['recipe_id'];
        }
    
        // 商品名の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_recipe_master
                    SET recipe_name = ?, update_datetime = NOW()
                    WHERE recipe_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_recipe_name, PDO::PARAM_STR);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '料理名の変更が成功しました。';
        }
    }
    
   $update_item_id = '';
   
   if ($process_kind === 'update_item_id') {
       // 調味料名の変更
       if (isset($_POST['update_item_id']) === true) {
            $update_item_id = $_POST['update_item_id'];
        }       
        
        if ($update_item_id === '') {
            $err_msg[] = '調味料を選択してください';
        }
        
        if (isset($_POST['recipe_id']) === true) {
            $id = $_POST['recipe_id'];
        }
        
        // 商品の詳細の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_recipe_master
                    SET item_id = ?, update_datetime = NOW()
                    WHERE recipe_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_item_id, PDO::PARAM_INT);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '調味料名の変更が成功しました。';
        } 
   }
   
    $update_recipe_comment = '';
    
    if ($process_kind === 'update_recipe_comment') {
        // 商品詳細の変更
        if (isset($_POST['update_recipe_comment']) === true) {
            $update_recipe_comment = $_POST['update_recipe_comment'];
            $update_recipe_comment = str_replace(array(" "," "),"",$update_recipe_comment);
        }
        
        if (mb_strlen($update_recipe_comment) === 0) {
            $err_msg[] = '料理の詳細を入力してください';
        } else if (mb_strlen($update_recipe_comment) > 98) {
            $err_msg[] = '詳細は98文字以内で入力してください';
        }
        
        if (isset($_POST['recipe_id']) === true) {
            $id = $_POST['recipe_id'];
        }
    
        // 商品の詳細の情報テーブルにデータを更新
        if (count($err_msg) === 0) {
            $sql = 'UPDATE ec_recipe_master
                    SET recipe_comment = ?, update_datetime = NOW()
                    WHERE recipe_id = ?';
            
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $update_recipe_comment, PDO::PARAM_STR);
            $stmt->bindvalue(2, $id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '料理の詳細の変更が成功しました。';
        }
    }    
    
    $change_recipe_status = '';
    
    if ($process_kind === 'change_recipe_status') {
        // ステータスの変更
        if (isset($_POST['change_recipe_status']) === true) {
            $change_recipe_status = $_POST['change_recipe_status'];
        }
        
        if ($change_recipe_status !== '0' && $change_recipe_status !== '1') {
            $err_msg[] = 'ステータスエラー';
        }
        
        if (isset($_POST['recipe_id']) === true) {
            $id = $_POST['recipe_id'];
        }
        
        if (count($err_msg) === 0) {
            // ステータスをデータに更新
            $sql = 'UPDATE ec_recipe_master
                    SET recipe_status = ?, update_datetime = NOW()
                    WHERE recipe_id = ?';
                    
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1,$change_recipe_status,PDO::PARAM_INT);
            $stmt->bindValue(2,$id, PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo 'ステータスの変更が成功しました';
        }
    }

    if ($process_kind === 'recipe_delete') {
        // データの削除

        if (isset($_POST['recipe_id']) === true) {
            $id = $_POST['recipe_id'];
        }
        
        if (count($err_msg) === 0) {
            // ステータスをデータに更新
            $sql = 'DELETE
                    FROM ec_recipe_master
                    WHERE recipe_id = ?';
            
            $stmt = $dbh->prepare($sql);
            $stmt->bindValue(1,$id,PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            
            echo '料理情報を削除しました';
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
        h2, table {
            width: 1250px;
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
<h2>レシピ詳細の登録</h2>
<form method = "post" enctype = "multipart/form-data">
    <p>料理名:<?php print htmlspecialchars($recipe_details['recipe_name'], ENT_QUOTES, 'utf-8'); ?></p>
    <p>人数(人分):<input type = "text" name = "person" ></p>
    <p>材料:</p>
        <textarea name = "recipe_material" row = "4" cols = "40"></textarea>   
    <p>作り方:</p>
        <textarea name = "recipe" row = "4" cols = "40"></textarea>
    <p>コツ・ポイント:</p>
        <textarea name = "point" row = "4" cols = "40"></textarea>
    <div class = new_submit><input name = "new_post" type = "submit" value = "料理を登録する"></div>
</form>
<h2>料理情報の一覧・変更</h2>
<table>
    <tr>
        <th>No</th>
        <th>料理画像</th>
        <th>料理名</th>
        <th>調味料名</th>
        <!--<th>調味料コード</th>-->
        <th>詳細</th>
        <th>ステータス</th>
        <th>操作</th>
    </tr>
    <!--非公開時の処理-->
    <?php if ($recipe_details['recipe_status'] === 0) { ?>
        <tr class = "gray">
    <!--公開時の処理-->
    <?php } else { ?>
        <tr>
    <?php } ?>
        <td><?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?></td>
        <!--画像を変更する-->
        <td>
            <img src = "<?php print $img_dir . $recipe_details['recipe_img']; ?>">
            <form method = "post" enctype = "multipart/form-data">    
                <input type = "file" name = "update_recipe_img" ></p>
                <input name = "update_post" type = "submit" value = "変更する"> 
                <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
                <input type = "hidden" name = "process_kind" value = "update_recipe_img">
            </form>        
        </td>
    <!--料理名を変更する-->
    <form method = "post">        
    <td>
        <input type = "text" name = "update_recipe_name" value = "<?php print htmlspecialchars($recipe_details['recipe_name'], ENT_QUOTES, 'utf-8'); ?>" class = "height200"><br>
        <input name = "update_post" type = "submit" value = "変更する"> 
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "update_recipe_name">    
    </td>
    </form>
    <!--調味料を変更する-->
    <form method = "post">
    <td>
        <select size = "1" name = "update_item_id">
            <option value = "<?php print htmlspecialchars($recipe_details['item_id'], ENT_QUOTES, 'utf-8'); ?>"><?php print htmlspecialchars($recipe_details['item_name'], ENT_QUOTES, 'utf-8'); ?></option>
            <?php foreach ($name as $i_name) { ?>
            <option value = "<?php print htmlspecialchars($i_name['item_id'], ENT_QUOTES, 'utf-8'); ?>"><?php print htmlspecialchars($i_name['item_name'], ENT_QUOTES, 'utf-8'); ?></option>
        <?php } ?>
        </select>
        <input name = "update_post" type = "submit" value = "変更する"> 
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "update_item_id">
    </td>
    </form>
    <!--<td></td>-->
    <!--料理の詳細を変更する-->
    <form method = "post">
    <td>
        <textarea name = "update_recipe_comment" row = "4" cols = "40" value = "<?php print htmlspecialchars($recipe_details['recipe_comment'], ENT_QUOTES, 'utf-8'); ?>"><?php print htmlspecialchars($recipe_details['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></textarea>
        <input name = "update_post" type = "submit" value = "変更する"> 
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "update_recipe_comment">
    </td>
    </form>
    <!--ステータスを変更する-->
    <form method = "post">
    <td>
        <!--非公開時の処理-->
        <?php if ($recipe_details['recipe_status'] === 0) { ?>
        <input type = "submit" value = "非公開→公開にする">
        <input type = "hidden" name = "change_recipe_status" value = "1">
        <!--公開時の処理-->
        <?php } else { ?>
        <input type = "submit" value = "公開→非公開にする">            
        <input type = "hidden" name = "change_recipe_status" value = "0">
        <?php } ?>
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "change_recipe_status">
    </td>
    </form>
    <td>
    <form action = "recipe_details.php" method = "get">
        <input type = "submit" value = "プレビュー">
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['item_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "item_details">
    </form>
    <form class = "delete_form" method = "post">
        <input class = "delete_btm" type = "submit" value = "削除する">
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "recipe_delete">
    </td>
    </form>
    </tr>
</table>
<br>
<table>
    <tr>
        <th>人数</th>
        <th>材料</th>
        <th>作り方</th>
        <th>コツ・ポイント</th>
    </tr>
    <!--非公開時の処理-->
    <?php if ($recipe_details['recipe_status'] === 0) { ?>
        <tr class = "gray">
    <!--公開時の処理-->
    <?php } else { ?>
        <tr>
    <?php } ?>
    <!--人数を変更する-->
    <form method = "post">
    <td>
        <input type = "text" name = "update_person" value = "<?php print htmlspecialchars($recipe_details['person'], ENT_QUOTES, 'utf-8'); ?>" class = "height200"><br>
        <input name = "update_post" type = "submit" value = "変更する"> 
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "update_person">    
    </td>
    </form>
    <!--材料を登録する-->
    <form method = "post">
    <td>
        <textarea name = "update_recipe_material" row = "4" cols = "40" value = "<?php print htmlspecialchars($recipe_details['recipe_material'], ENT_QUOTES, 'utf-8'); ?>">
            <?php print htmlspecialchars($recipe_details['recipe_material'], ENT_QUOTES, 'utf-8'); ?>
        </textarea>
        <input name = "update_post" type = "submit" value = "変更する"> 
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "update_recipe_material">
    </td>
    </form>
    <!--作り方を登録する-->
    <form method = "post">
    <td>
        <textarea name = "update_recipe" row = "4" cols = "40" value = "<?php print htmlspecialchars($recipe_details['recipe'], ENT_QUOTES, 'utf-8'); ?>">
            <?php print htmlspecialchars($recipe_details['recipe'], ENT_QUOTES, 'utf-8'); ?>
        </textarea>
        <input name = "update_post" type = "submit" value = "変更する"> 
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "update_recipe">
    </td>
    </form>    
    <!--コツ・ポイントを変更する-->
    <form method = "post">        
    <td>
        <textarea name = "update_point" row = "4" cols = "40" value = "<?php print htmlspecialchars($recipe_details['point'], ENT_QUOTES, 'utf-8'); ?>">
            <?php print htmlspecialchars($recipe_details['point'], ENT_QUOTES, 'utf-8'); ?>
        </textarea>
        <input name = "update_post" type = "submit" value = "変更する"> 
        <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($recipe_details['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
        <input type = "hidden" name = "process_kind" value = "update_recipe_point">
    </td>
    </form>
    </tr>
</table>
</body>
</html>