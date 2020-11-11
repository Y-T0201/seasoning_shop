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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
        if (count($err_msg) === 0) {
            $sql = 'SELECT * 
                    FROM ec_item_master
                    JOIN ec_cart ON ec_item_master.item_id = ec_cart.item_id
                    JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
                    WHERE ec_cart.user_id = ?';
                
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $user_id,PDO::PARAM_INT);
            // SQLを実行
            $stmt->execute();
            $data = $stmt->fetchAll();
            
            $sum_amount = 0;
            $sum_price = 0;
            foreach ($data as $value) {
                $sum_amount += $value['amount'];
                $sum_price += round($value['price'] * 1.08 * $value['amount']);
            
                $amount = $value['amount'];
                // 数量がマイナスの処理
                if (preg_match('/^[1-9][0-9]*$/', $value['amount']) !== 1) {
                    $err_msg[] = '数量は1以上の整数を入力してください';
                }

                $item_id = '';
                $item_id = $value['item_id'];
                // 商品が未選択
                if ($value['item_id'] === '') {
                    $err_msg[] = '商品を選択してください';
                }
                
                // 在庫が0のときの処理
                if ($value['stock'] === 0) {
                    $err_msg[] = $value['item_name'] . '&nbsp;&nbsp;はただいま在庫がございません';
                }
                
                // 在庫 - 数量がマイナスの処理
                else if ($value['stock'] - $value['amount'] < 0) {
                    $err_msg[] = $value['item_name'] . '&nbsp;&nbsp;は' . $value['stock'] . '個まで購入が可能です';
                }
                
                // ステータスが非公開の処理
                if ($value['item_status'] === 0) {
                    $err_msg[] = $value['item_name'] . 'はただいま購入することができません';
                }
        
                if (count($err_msg) === 0) {
                    // トランザクション開始
                    $dbh->beginTransaction();
                    try {
                        // 在庫数量を減らす
                        $update_stock = $value['stock'] - $value['amount'];

                        $sql = 'UPDATE ec_item_stock
                                SET stock = ?, update_datetime = NOW()
                                WHERE item_id = ?';
                                
                        // SQL文を実行する準備
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $update_stock,PDO::PARAM_INT);
                        $stmt->bindvalue(2, $item_id,PDO::PARAM_INT);
                        // SQLを実行
                        $stmt->execute();
                        
                        // echo '在庫数の変更に成功しました';
                        
                        // カートの削除
                        $sql = 'DELETE
                                FROM ec_cart
                                WHERE user_id = ?';
                                
                        // SQL文を実行する準備
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindvalue(1, $user_id,PDO::PARAM_INT);
                        // SQLを実行
                        $stmt->execute();
                        
                        // echo 'カートを削除しました。';
                        
                        // 購入履歴テーブルに購入データを記録する
                        
                        $sql = 'insert into ec_history(user_id, item_id, amount, datetime)
                                VALUES(?, ?, ?, NOW());';
                        
                        // SQL文を実行する準備
                        $stmt = $dbh->prepare($sql);
                        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
                        $stmt->bindValue(2, $item_id, PDO::PARAM_INT);
                        $stmt->bindValue(3, $amount, PDO::PARAM_INT);
                        // SQLを実行
                        $stmt->execute();
                        // コミット処理
                        $dbh->commit();
                        
                        // echo '購入履歴に追加しました';
        
                    } catch (PDOExeption $e) {
                     
                        // ロールバック処理
                        $dbh->rollback();
                        // 例外をスロー
                        throw $e;
                    }
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
    <title>購入完了</title>
    <style>
    body, h2, table, .flex, .alert {
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

    .flex, tr, td, h2, table, .alert {
         width: 1000px;
    }
    
    table, tr, th, td {
        border: solid 1px;
        padding: 10px;
        text-align: center;
    }
    
    table {
        border-collapse: collapse;
    }
    
    .flex, .cart_flex, .flex_mypage, .top_flex, .bottom_flex, .logout_flex {
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
    
    .link_top, .success, .logout_font {
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
    
    .cart_price, .btm_buy, .list {
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

    .link_top {
        text-decoration: none;
        display: block;
        position: relative;
        top: 13px;
    }
    
    .bo_btm_logout, .list {
        margin-top: 80px;
    }
    
    .list {
        height: 50px;
        width: 150px;
        text-align: center;
        display: block;
        border-style: none;
        line-height: 50px;
        margin-left: 380px;
        text-decoration: none;
    }
    
    .bo_btm_logout {
        height: 60px;
        margin-left: 200px;
    }
    
    .logout_font {
        margin-top: 110px;
        margin-left: 10px;
    }

    
    .item_img {
        max-height: 125px;
    }
    
    .alert {
        background-color: red;
        color: #FFFFFF;
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
            <!--<form class = "btm_search" method = "post" action = "search_list.php">-->
            <form class = "btm_search" method = "get" action = "search_list.php">
                <select size = "1" name = "keyword">
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
    </header>
    <main>
        <br>
        <?php if (count($err_msg) === 0) { ?>
            <h2>ご購入ありがとうございました。</h2>
        <?php } else { ?>
            <?php foreach ($err_msg as $err) { ?>
                <p class = "alert"><?php print $err; ?></p>
            <?php } ?>
        <?php } ?>    
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
                <td><?php print htmlspecialchars (number_format($value['amount']), ENT_QUOTES, 'utf-8'); ?>個</td>
                <td><?php print htmlspecialchars (number_format(round($value['price'] * 1.08 * $value['amount'])), ENT_QUOTES, 'utf-8'); ?>円</td>
                <td></td>
            <?php } ?>
            </tr>
        </table>
        <div class = "flex">
            <p class = "pd100">ご注文点数:&nbsp;&nbsp;<?php print htmlspecialchars(number_format($sum_amount), ENT_QUOTES, 'utf-8'); ?>点</p>
            <p class = "pd100">ご請求合計金額(税込み):&nbsp;&nbsp;<?php print htmlspecialchars(number_format($sum_price), ENT_QUOTES, 'utf-8'); ?>円</p>
        </div>
        <div class = "bottom_flex">
            <a class = "list" href = "seasoning_list.php">商品一覧に戻る</a>
            <div class = "logout_flex"></div>
                <form method = "post">
                    <input class = "bo_btm_logout" type = "image" src = "logout.png">
                    <input type = "hidden" name = "btm_logout" value = "btm_logout">
                </form>
                <p class = "logout_font">ログアウト</p>
            </div>
        </div>
   </main>
</body>
</html>