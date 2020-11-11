<?php
$host = 'localhost';
$username = 'root';
$password = 'nRlkY30ag';
$dbname = 'ec_site';
$charset = 'utf8';

$img_dir = './item_img/'; // アップロードした画像ファイルの保存ディレクトリ
$err_msg = array();
$data = array();
$success = '';

// MySQL用のDSN文字列
$dsn = 'mysql:dbname='.$dbname.';host='.$host.';charset='.$charset;

session_start();

if (isset($_SESSION['user_id']) === TRUE) {
    $user_id = $_SESSION['user_id'];
} else {
    // ログインしていないので、ログインページに飛ばす
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
    
    $process_kind = '';
    
    if (isset($_POST['process_kind'])) {
        $process_kind = $_POST['process_kind'];
    }
    
    $update_amount = '';
    $item_id = '';
    
    if (isset($_POST['item_id']) === true) {
        $item_id = $_POST['item_id'];
    }
    
    if (count($err_msg) === 0) {
        $sql = 'SELECT item_name, stock, item_status
                FROM ec_item_master
                JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
                WHERE ec_item_master.item_id = ?';
                
        // SQL文を実行する準備
        $stmt = $dbh->prepare($sql);
        $stmt->bindvalue(1, $item_id,PDO::PARAM_INT);            
        // SQLを実行
        $stmt->execute();
        // レコードの取得
        $item = $stmt->fetch();

        
        if ($process_kind === 'update_amount') {
            // 数量の変更
            if (isset($_POST['update_amount']) === true) {
                $update_amount = $_POST['update_amount']; 
            }
            
            // 在庫が0のときの処理
            if ($item['stock'] === 0) {
                $err_msg[] = $item['item_name'] . '&nbsp;&nbsp;はただいま在庫がございません';
            }
            
            // 数量がマイナスの処理          
            if (preg_match('/^[1-9][0-9]*$/', $update_amount) !== 1) {
                $err_msg[] = '数量は1以上の整数を入力してください';
            }
            
            // 在庫 - 数量がマイナスの処理
            if ($item['stock'] - $update_amount < 0) {
                $err_msg[] = $item['item_name'] . '&nbsp;&nbsp;は' . $item['stock'] . '個まで購入が可能です';
            }

            // ステータスが非公開の処理
            if ($item['item_status'] === 0) {
                $err_msg[] = $item['item_name'] . 'はただいま購入することができません';
            }
            
            if (count($err_msg) === 0) {
                // 数量の情報テーブルにデータを更新        
                $sql = 'UPDATE ec_cart
                        SET amount = ?, update_datetime = NOW()
                        WHERE item_id = ?';
                        
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $update_amount, PDO::PARAM_INT);
                $stmt->bindvalue(2, $item_id, PDO::PARAM_INT);
                // SQLを実行
                $stmt->execute();
    
                $success = $item['item_name'] . 'の数量を変更しました';
            }
        }
        
        if ($process_kind === 'delete_amount') {
            // データの削除
    
            // 数量の情報テーブルにデータを更新
            if (count($err_msg) === 0) {
                $sql = 'DELETE
                        FROM ec_cart
                        WHERE item_id = ?';
                        
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $item_id,PDO::PARAM_INT);
                // SQLを実行
                $stmt->execute();
                
                $success = $item['item_name'] . 'を削除しました';
            }
        }
    }
    
    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT ec_item_master.item_id, item_name, price, item_img, amount
            FROM ec_item_master
            JOIN ec_cart ON ec_item_master.item_id = ec_cart.item_id
            WHERE user_id = ?'; 
    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindvalue(1, $user_id,PDO::PARAM_INT);  
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $rows = $stmt->fetchAll();
    // 1行ずつ結果を配列で取得
    foreach ($rows as $row) {
        $data[] = $row;
    }

    $sum_amount = 0;
    $sum_price = 0;
    foreach ($data as $value) {
        $sum_amount += $value['amount'];
        $sum_price += round($value['price'] * 1.08 * $value['amount']);
    }


} catch (PDOExeption $e) {
    echo 'データベース処理でエラーが発生しました。 理由:'.$e->getMessage();
}

?>
<!DOCTYPE html>
<html lang = "ja">
<head>
    <meta charset = "utf-8">
    <title>ショッピングカート</title>
    <style>
    body, h2, table, .flex, .btm_buy, .alert, .success {
        margin-left: auto;
        margin-right: auto;
    }
    
    body {
        width: 1250px;
        background-image: url(wood_bg.jpg);
        background-size: 100%;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }

    tr, h2, table, .alert, .success {
         width: 1000px;
    }
    
    .flex, .btm_buy {
        width: 800px;
    }
    
    table, tr, th, td {
        border: solid 1px;
        padding: 10px;
        text-align: center;
    }
    
    table {
        border-collapse: collapse;
    }
    
    .flex, .cart_flex, .flex_mypage, .top_flex {
        display: flex;
    }
    
    .flex {
        border: solid 1px;
        margin-top: 10px;
    }
    
    .pd100 {
        padding-left: 100px;
    }
    
    .top_flex {
        align-items: baseline;
        border-bottom: solid 1px;    
    }
    
    .icon {
        max-height: 50px;
        margin-right: 5px;
        margin-bottom: -5px;
    }
    
    .link_top, .success {
        color: #463C21;
    }
    
    .link_top, .success, .cart_price {
        font-size: 16px;
    }
    
    .cart_price {
        line-height: 50px;
        width: 280px;
        color: #FFFFFF;
        text-decoration: none;
        display: block;
        text-align: center;
        margin-left: 30px;
        position: relative;
        bottom: 2px;
    }
    
    .btm_mypage, .btm_recipe {
        line-height: 50px;
        color: #463C21;
        text-decoration: none;
        display: block;
        margin-left: 35px;
        position: relative;
        top: 15px;
    }
    
    .cart_img {
        width: 38px;
        position: relative;
        top: 13px;
    }
    
    .cart_price, .btm_buy {
        background-color: #76A44A;
        color: #FFFFFF;
        font-size: 16px;
    }
    
    .btm_search {
        display: inline-flex;
        margin-left: 85px;
    }
   
    .btm_logout {
        height: 50px;
        margin-left: 30px;
        position: relative;
        top: 20px;
    }
    
    .img_mypage, .img_recipe {
        height: 45px;
    }

    .cart-submit {
        margin-left: 360px;
    }
    
    .btm_buy {
        display: block;
        margin-top: 10px;
        border-style: none;
        height: 50px;
    }
    
    .link_top {
        text-decoration: none;
        display: block;
        position: relative;
        top: 13px;
    }
    
    .item_img {
        max-height: 125px;
    }
    
    .success {
        padding: 10px;
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
            <div class = "flex_recipe">
                <a class = "btm_recipe" href = "recipe_list.php">
                    <img class = "img_recipe" src = "recipe.png">
                    レシピ
                </a>    
            </div>
            <form class = "btm_search" method = "get" action = "search_list.php">
                <select size = "1" name = "keyword">
                    <!--<option value = "item, recipe">キーワード</option>-->
                    <option value = "all">キーワード</option>
                    <option value = "item">調味料</option>
                    <option value = "recipe">レシピ</option>
                </select>
                <input type = "text" name = "contents">
                <input type = "submit" name = "search" value = "検索">
            </form>
            <div class = "flex_mypage">
                <a class = "btm_mypage" href = "mypage.php">
                    <img class = "img_mypage" src = "mypage.png">
                    MyPage
                </a>    
            </div>
            <form method = "post">
                <input class = "btm_logout" type = "image" src = "logout.png">
                <input type = "hidden" name = "btm_logout" value = "btm_logout">
            </form>
            <div class = "cart_flex">
                <a class = "cart_price"  href = "shopping_cart.php">
                    <img class = "cart_img" src = "cart.png">
                    カートの中身&nbsp;&nbsp;<?php print htmlspecialchars(number_format($sum_price), ENT_QUOTES, 'utf-8'); ?>円
                </a>
            </div>
        </div>
    </header>
    <main>
    <?php foreach ($err_msg as $value) { ?>
    <p class = "alert"><?php print $value; ?></p>
    <?php } ?>
        <p class = "success"><?php print $success; ?></p>
        <br>
        <h2>カートに入れた商品</h2>
        <form method = "post">
        <!--<form method = "post" action = "buy_completion.php">   -->
        <table>
            <tr>
                <th>画像</th>
                <th>商品名</th>
                <th>税込価格</th>
                <th>数量</th>
                <th>小計</th>
                <th></th>
            </tr>
            <?php foreach ($data as $value) { ?>
                <tr>
                <td><img class = "item_img" src = "<?php print $img_dir . $value['item_img']; ?>"></td>
                <td><?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></td>
                <td><?php print htmlspecialchars (number_format(round($value['price'] * 1.08)), ENT_QUOTES, 'utf-8'); ?>円</td>
                <!--<form method = "post" action = "buy_completion.php">-->
                <form method = "post">
                    <td>
                        <input type = "text" name = "update_amount" value = "<?php print htmlspecialchars(number_format($value['amount']), ENT_QUOTES, 'utf-8'); ?>">個&nbsp;&nbsp;
                        <input name = "update_post" type = "submit" value = "変更">
                        <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                        <input type = "hidden" name = "process_kind" value = "update_amount">
                    </td>
                </form>
                <td><?php print htmlspecialchars(number_format(round($value['price'] * 1.08 * $value['amount'])), ENT_QUOTES, 'utf-8'); ?>円</td>
                <form method = "post">
                    <td>
                    <input type = "submit" name = "delete_amount" value = "削除する">
                    <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                    <input type = "hidden" name = "process_kind" value = "delete_amount">
                    </td>
                </form>
            <?php } ?>
            </tr>
        </table>
        <div class = "flex">
            <p class = "pd100">ご注文点数:&nbsp;&nbsp;<?php print htmlspecialchars(number_format($sum_amount), ENT_QUOTES, 'utf-8'); ?>点</p>
            <p class = "pd100">ご請求合計金額(税込み):&nbsp;&nbsp;<?php print htmlspecialchars(number_format($sum_price), ENT_QUOTES, 'utf-8'); ?>円</p>
        </div>
        <form method = "post" action = "buy_completion.php">
            <input class = "btm_buy" type = "submit" name = "buy" value = "購入する">
        </form>
   </main>
</body>
</html>