<?php
$host = 'localhost';
$username = 'root';
$password = 'nRlkY30ag';
$dbname = 'ec_site';
$charset = 'utf8';

$item_img_dir = './item_img/'; // アップロードした画像ファイルの保存ディレクトリ
$recipe_img_dir = './recipe_img/'; // アップロードした画像ファイルの保存ディレクトリ
$err_msg = array();
$data = array();
$r_item = array();
$r_recipe = array();
$sum =array();
$success = '';
$item_name = '';
$sum_price = 0;

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

    if (isset($_POST['cart_post']) === true) {
        // 商品をカートに追加
        $amount = 1;

        $item_id = '';
            
        if (isset($_POST['item_id']) === true) {
            $item_id = $_POST['item_id']; 
        }
  
        if (count($err_msg) === 0) {
            $sql = 'SELECT stock, item_status, item_name 
                    FROM ec_item_master
                    JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
                    WHERE ec_item_master.item_id = ?';
                    
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $item_id,PDO::PARAM_INT);            
            // SQLを実行
            $stmt->execute();
            // レコードの取得
            $ck_item = $stmt->fetch();

            // 在庫が0のときの処理
            if ($ck_item['stock'] === 0) {
                $err_msg[] = $ck_item['item_name'] . '&nbsp;&nbsp;はただいま在庫がございません';
            }
            
            // ステータスが非公開の処理
            if ($ck_item['item_status'] === 0) {
                $err_msg[] = $ck_item['item_name'] . 'はただいま購入することができません';
            }
        
            if (count($err_msg) === 0) {
                $sql = 'SELECT * FROM ec_cart
                        WHERE user_id = ? AND item_id = ?';
                        
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $user_id,PDO::PARAM_INT);
                $stmt->bindvalue(2, $item_id,PDO::PARAM_INT);            
                // SQLを実行
                $stmt->execute();
                $item = $stmt->fetch();
                
                if (isset($item['item_id']) === TRUE) {
                    //--- 商品が見つかった（UPDATE） ---
    
                    $sql = 'UPDATE ec_cart
                            SET amount = amount + ?, update_datetime = NOW()
                            WHERE user_id = ? AND item_id = ?';
                            
                    // SQL文を実行する準備
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $amount,PDO::PARAM_INT);
                    $stmt->bindvalue(2, $user_id,PDO::PARAM_INT);
                    $stmt->bindvalue(3, $item_id,PDO::PARAM_INT);
                    // SQLを実行
                    $stmt->execute();
    
                    $success = $ck_item['item_name'] . 'の数量を追加しました';
                    
                } else {
                    //--- 見つからなかった（INSERT INTO） ---
                    // カート情報テーブルにデータ作成
                    $sql = 'INSERT into ec_cart(user_id, item_id, amount, create_datetime, update_datetime)
                            VALUES(?, ?, ?, NOW(), NOW());';
                            
                    // SQL文を実行する準備
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $user_id, PDO::PARAM_INT);
                    $stmt->bindvalue(2, $item_id, PDO::PARAM_INT);
                    $stmt->bindvalue(3, $amount, PDO::PARAM_INT);
            
                    // カート情報テーブルにデータ作成
                    // SQLを実行
                    $stmt->execute();
    
                    $success = $ck_item['item_name'] . 'をカートに入れました';
                }
            }
        }
    }
    
    $seach_item = '';
    if (isset($_GET['contents']) === true) {
        $seach_item = $_GET['contents'];
    }
    
    // 検索が未入力の処理
    if ($seach_item === '') {
        $err_msg[] = '検索したいキーワードを入力してください';
    }
    
    $keyword = '';
    if (isset($_GET['keyword']) === true) {
        $keyword = $_GET['keyword'];
    }
    
    // ジャンルが未選択の処理
    if ($keyword === '') {
        $err_msg[] = '検索したい項目を選択してください';
    }
    
    // 未設定のジャンルが選択された処理
    if ($keyword !== 'all' && $keyword !== 'item' && $keyword !== 'recipe') {
        $err_msg[] = '指定の検索項目を選択してください';
    }
    
//   // 未設定のジャンルが選択された処理
//     if ($keyword != 'item' && $keyword !== 'recipe') {
//         $err_msg[] = '指定の検索項目を選択してください';
//     }
    
    if (count($err_msg) === 0) {
    
        // // ジャンルが調味料
        // if ($keyword === 'item') {
        //     $sql = 'SELECT * FROM ec_item_master
        //             JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
        //             WHERE item_name LIKE :item_name OR item_comment LIKE :item_comment';
                    
        //     $statement = $dbh->prepare($sql);
    
        //     $s_item = '%'.$seach_item.'%';
        //     $statement->bindValue(':item_name',$s_item, PDO::PARAM_STR);
        //     $statement->bindValue(':item_comment',$s_item, PDO::PARAM_STR);        
        //     $statement->execute();
            
        //     $rows = $statement->fetchAll();
            
        //     foreach ($rows as $row) {
        //         $r_item[] = $row;
        //     }
                
        // // ジャンルがレシピ 
        // } else if ($keyword === 'recipe') {
        //     $sql = 'SELECT * FROM ec_recipe_master
        //             WHERE recipe_name LIKE :recipe_name OR recipe_comment LIKE :recipe_comment OR item_name LIKE :item_name';
                    
        //     $statement = $dbh->prepare($sql);
    
        //     $s_recipe = '%'.$seach_item.'%';
        //     $statement->bindValue(':recipe_name',$s_recipe, PDO::PARAM_STR);
        //     $statement->bindValue(':recipe_comment',$s_recipe, PDO::PARAM_STR);        
        //     $statement->bindValue(':item_name',$s_recipe, PDO::PARAM_STR);              
        //     $statement->execute();
            
        //     $rows = $statement->fetchAll();
            
        //     foreach ($rows as $row) {
        //         $r_recipe[] = $row;
                    
        //     }
            
        // // ジャンルがキーワード
        // } else if ($keyword === 'all') {
            $sql = 'SELECT * FROM ec_item_master
                    JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
                    WHERE item_name LIKE :item_name OR item_comment LIKE :item_comment
                    ORDER BY ec_item_master.item_id DESC';
                    
            $statement = $dbh->prepare($sql);
    
            $s_item = '%'.$seach_item.'%';
            $statement->bindValue(':item_name',$s_item, PDO::PARAM_STR);
            $statement->bindValue(':item_comment',$s_item, PDO::PARAM_STR);
            $statement->execute();
            
            $rows = $statement->fetchAll();
            
            foreach ($rows as $row) {
                $r_item[] = $row;
            }
            
            $sql = 'SELECT * FROM ec_recipe_master
                    WHERE recipe_name LIKE :recipe_name OR recipe_comment LIKE :recipe_comment OR item_name LIKE :item_name
                    ORDER BY ec_recipe_master.recipe_id DESC';
                    
            $statement = $dbh->prepare($sql);
    
            $s_recipe = '%'.$seach_item.'%';
            $statement->bindValue(':recipe_name',$s_recipe, PDO::PARAM_STR);
            $statement->bindValue(':recipe_comment',$s_recipe, PDO::PARAM_STR);
            $statement->bindValue(':item_name',$s_recipe, PDO::PARAM_STR);            
            $statement->execute();
            
            $rows = $statement->fetchAll();
            
            foreach ($rows as $row) {
                $r_recipe[] = $row;
                    
            }
        // }    
    }    
    
    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT price, amount
            FROM ec_item_master
            JOIN ec_cart ON ec_item_master.item_id = ec_cart.item_id
            WHERE user_id = ?';

    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindvalue(1, $user_id, PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $rows = $stmt->fetchAll();
    // 1行ずつ結果を配列で取得
    foreach ($rows as $row) {
        $sum[] = $row;
    }
    
    foreach ($sum as $value) {
        // 合計金額の表示
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
    <title>調味料一覧</title>
    <style>
    body, h2, h3, .success, .alert {
        margin-left: auto;
        margin-right: auto;
    }
    
    body {
        width: 1250px;
    }
    
    h2, h3, .success, .alert {
        width: 1026px;
        
    }
    
    .list {
        width: 500px;
        height: 250px;
        border: solid 1px;
        padding: 10px;
        display: inline-block;
        /*margin: 0px 0px 0px 100px;*/
    }
    
    h3, .cart {
        background-color: #339802;    
    }
    
    h3 {
        padding: 10px;
        color: #FFFFFF;
    }
    
    .mg125 {
        margin-left: 100px;
    }
    
    .flex {
        display: flex;
    }
    
    .top_flex {
        display: flex;
        align-items: baseline;
        border-bottom: solid 1px ;
    }
    
    .icon {
        max-height: 50px;
        margin-right: 5px;
        margin-bottom: -5px;
    }
    
    .rogo {
        color: #714720;
    }
    
    .cart {
        line-height: 50px;
        width: 300px;
        font-size: 16px;
        color: #FFFFFF;
        text-decoration: none;
        display: block;
        text-align: center;
        margin-left: auto;
    }
    
    .btm_search {
        display: inline-flex;
        margin-left: 200px;
    }
    
    .btm_logout {
        margin-left: 140px;
        height: 50px;
        width: 100px;
    }
    
    .cart-submit, .sold_out {
        margin-left: 380px;
    }
    
    .sold_out {
        background-color: red;
        text-align: center;
        color: #FFFFFF;
    }
    
    .link_top {
        text-decoration: none;
        display: block;
    }
    
    .item_img {
        max-height: 125px;
    }
    
    .recipe_img {
        max-height: 125px;
    }
    
    .center {
        text-align: center;
    }
    
    .mg50 {
        margin-left: 50px;
        margin-bottom: 0px;
    }
    
    .success {
        padding: 10px;
        color: #714720;
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
            <a class = "link_top" href = "seasoning_list.php"><p class = "rogo"><img class = "icon" src="apron.png">はじめての調味料</p></a>
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
                <form method = "post">
                <input class = "btm_logout" type = "submit" name = "btm_logout" value = "ログアウト">
            </form>
            <a class = "cart" href = "shopping_cart.php">カートの中身&nbsp;&nbsp;<?php print htmlspecialchars($sum_price, ENT_QUOTES, 'utf-8'); ?>円</a>
        </div>
    </header>
    <main>
    <?php foreach ($err_msg as $value) { ?>
    <p class = "alert"><?php print $value; ?></p>
    <?php } ?>
    <p class = "success"><?php print $success; ?></p>
    <br>
    <h2>「<?php print $seach_item ?>」の検索結果</h2>
    <?php if ($keyword == 'item') { ?>
        <h3>調味料</h3>
        <table class = "mg125">
            <tr>
            <?php foreach ($r_item as $value) { ?>
                <?php if ($value['item_status'] === 1) { ?>
                    <td class = "list">
                        <div class = "flex">
                            <img class = 'item_img' src = "<?php print $item_img_dir . $value['item_img']; ?>">
                            <p><?php print htmlspecialchars ($value['item_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                        <p class = "center">調味料名:<?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                        <!--税率8%計算-->
                        <p class = "center">小計<?php print htmlspecialchars (round($value['price'] * 1.08), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>
                        <?php if ($value['stock'] === 0) { ?>
                            <P class = "sold_out">売り切れ</P>
                        <?php } else if ($value['stock'] > 0) { ?>
                            <form method = "post">    
                                <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">
                                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                <!--<input type = "hidden" name = "contents" value="<?php print $seach_item; ?>">-->
                            </form>    
                        <?php } ?>
                    </td>
                <?php } ?>
            <?php } ?>
            </tr>
        </table>
    <?php } else if ($keyword == 'recipe') { ?>
        <h3>レシピ</h3>
        <table class = "mg125">
            <tr>
            <?php foreach ($r_recipe as $r_value) { ?>
                <?php foreach ($r_item as $i_value) { ?>
                    <?php if ($r_value['recipe_status'] === 1 && $i_value['item_status'] === 1) { ?>
                        <td class = "list">
                            <div class = "flex">
                                <img class = 'recipe_img' src = "<?php print $recipe_img_dir . $r_value['recipe_img']; ?>">
                                <p><?php print htmlspecialchars ($r_value['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                            </div>
                            <p class = "center">料理名:<?php print htmlspecialchars ($r_value['recipe_name'], ENT_QUOTES, 'utf-8'); ?></p>
                            <div class = "flex">
                                <p class = "mg50">調味料名:<?php print htmlspecialchars ($r_value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                                <!--税率8%計算-->
                                <p class = "mg50">小計<?php print htmlspecialchars (round($i_value['price'] * 1.08), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>
                            </div>
                            <?php if ($i_value['stock'] === 0) { ?>
                                <P class = "sold_out">売り切れ</P>
                            <?php } else if ($i_value['stock'] > 0) { ?>
                                <form method = "post">    
                                    <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">
                                    <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($i_value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                </form>
                            <?php } ?>    
                        </td>
                    <?php } ?>
                <?php } ?>
            <?php } ?>
            </tr>
        </table>
    <?php } else if ($keyword == 'all') { ?>
        <h3>調味料</h3>
        <table class = "mg125">
            <tr>
            <?php foreach ($r_item as $value) { ?>
                <?php if ($value['item_status'] === 1) { ?>
                    <td class = "list">
                        <div class = "flex">
                            <img class = 'item_img' src = "<?php print $item_img_dir . $value['item_img']; ?>">
                            <p><?php print htmlspecialchars ($value['item_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                        <p class = "center">調味料名:<?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                        <!--税率8%計算-->
                        <p class = "center">小計<?php print htmlspecialchars (round($value['price'] * 1.08), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>
                        <?php if ($value['stock'] === 0) { ?>
                            <P class = "sold_out">売り切れ</P>
                        <?php } else if ($value['stock'] > 0) { ?>
                            <form method = "post">    
                                <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">
                                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                <!--<input type = "hidden" name = "contents" value="<?php print $seach_item; ?>">-->
                            </form>    
                        <?php } ?>
                    </td>
                <?php } ?>
            <?php } ?>
            </tr>
        </table>
        <h3>レシピ</h3>
        <table class = "mg125">
            <tr>
            <?php foreach ($r_recipe as $r_value) { ?>
                <!--<?php foreach ($r_item as $i_value) { ?>-->
                    <?php if ($r_value['recipe_status'] === 1) { ?>
                    <!--<?php if ($r_value['recipe_status'] === 1 && $i_value['item_status'] === 1) { ?>-->
                        <td class = "list">
                            <div class = "flex">
                                <img class = 'recipe_img' src = "<?php print $recipe_img_dir . $r_value['recipe_img']; ?>">
                                <p><?php print htmlspecialchars ($r_value['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                            </div>
                            <p class = "center">料理名:<?php print htmlspecialchars ($r_value['recipe_name'], ENT_QUOTES, 'utf-8'); ?></p>
                            <div class = "flex">
                                <p class = "mg50">調味料名:<?php print htmlspecialchars ($r_value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                                <!--税率8%計算-->
                            <!--    <p class = "mg50">小計<?php print htmlspecialchars (round($i_value['price'] * 1.08), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>-->
                            <!--</div>-->
                            <!--<?php if ($i_value['stock'] === 0) { ?>-->
                            <!--    <P class = "sold_out">売り切れ</P>-->
                            <!--<?php } else if ($i_value['stock'] > 0) { ?>-->
                            <!--    <form method = "post">    -->
                            <!--        <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">-->
                            <!--        <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($i_value['item_id'], ENT_QUOTES, 'utf-8'); ?>">-->
                                </form>
                            <!--<?php } ?>   -->
                        </td>
                    <?php } ?>
                <!--<?php } ?>-->
            <?php } ?>
            </tr>
        </table>    
    <?php } ?>
    <?php } ?>
   </main>
</body>
</html>