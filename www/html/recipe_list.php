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
    
    $heart = '';
    // お気に入り登録
    if (isset($_POST['heart'])) {
        $heart = $_POST['heart'];
    }
    
    // 条件を振り分ける
    if ($heart === 'recipe_heart') {
        
        if (isset($_POST['recipe_id']) === true) {
            $recipe_id = $_POST['recipe_id']; 
        }
        
        if (count($err_msg) === 0) {
            $sql = 'SELECT recipe_status, recipe_name 
                    FROM ec_recipe_master
                    WHERE recipe_id = ?';
                    
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $recipe_id,PDO::PARAM_INT);            
            // SQLを実行
            $stmt->execute();
            // レコードの取得
            $ck_item = $stmt->fetch();

            // ステータスが非公開の処理
            if ($ck_item['recipe_status'] === 0) {
                $err_msg[] = $ck_item['recipe_name'] . 'はただいまお気に入り登録することができません';
            }
        
            if (count($err_msg) === 0) {
                $sql = 'SELECT * FROM ec_user_recipe
                        WHERE user_id = ? AND recipe_id = ?';
                        
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $user_id,PDO::PARAM_INT);
                $stmt->bindvalue(2, $recipe_id,PDO::PARAM_INT);            
                // SQLを実行
                $stmt->execute();
                $recipe = $stmt->fetch();
                
                if (isset($recipe['recipe_id']) === TRUE) {
                    //--- 商品が見つかった（delete） ---
                    $sql = 'DELETE
                            FROM ec_user_recipe
                            WHERE recipe_id = ?';
                            
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindValue(1,$recipe_id,PDO::PARAM_INT);
                    // SQLを実行
                    $stmt->execute();
                    
                    $success = $ck_item['recipe_name'] . 'をお気に入り登録から削除しました';
                
                } else {
                    // SQL文作成
                    $sql = 'INSERT INTO ec_user_recipe(user_id, recipe_id, create_datetime)
                            VALUES(?, ?, NOW());';
                            
                    // SQL文を実行する準備
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $user_id, PDO::PARAM_INT);
                    $stmt->bindvalue(2, $recipe_id, PDO::PARAM_INT);        
                    // SQLを実行
                    $stmt->execute();

                    $success = $ck_item['recipe_name'] . 'をお気に入り登録しました';   
                }
            }
        }
    }

    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT ec_recipe_master.recipe_id, recipe_name, recipe_img, recipe_status, recipe_comment, item_name, user_recipe_id
            FROM ec_recipe_master
            JOIN ec_item_master ON ec_recipe_master.item_id = ec_item_master.item_id
            LEFT JOIN ec_user_recipe ON ec_recipe_master.recipe_id = ec_user_recipe.recipe_id
            ORDER BY ec_recipe_master.recipe_id DESC';
            
    // SQL文を実行する準備            
    $statement = $dbh->prepare($sql);
    // SQLを実行
    $statement->execute();
    // レコードの取得    
    $rows = $statement->fetchAll();
     // 1行ずつ結果を配列で取得
    // var_dump($rows);   
    foreach ($rows as $row) {
        $r_recipe[] = $row;
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
        background-image: url(wood_bg.jpg);
        background-size: 100%;
        background-repeat: no-repeat;
        background-attachment: fixed;    
    }
    
    h2, h3, .success, .alert {
        width: 1026px;
    }
    
    .list {
        margin-left: 0.5px;
        margin-top: 0.5px;
        width: 500px;
        height: 250px;
        border: solid 1px;
        padding: 10px;
        display: inline-block;
        /*margin: 0px 0px 0px 100px;*/
    }
    
    h3, .cart_price {
        background-color: #76A44A;
    }
    
    h3 {
        padding: 10px;
        color: #FFFFFF;
    }
    
    .mg100 {
        margin-left: 100px;
    }
    
    .flex, .cart_flex, .flex_mypage, .flex_recipe, .top_flex {
        display: flex;
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
    
    .link_top, .success {
        color: #463C21;
    }
    
    .link_top, .success, .cart_price {
        font-size: 16px;
    }
    
    .cart, .btm_mypage {
        line-height: 50px;
        color: #FFFFFF;
        text-decoration: none;
        display: block;
        text-align: center;
        margin-left: auto;
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
    
    .btm_search {
        display: inline-flex;
        margin-left: 85px;
    }
    
    .btm_logout {
        height: 50px;
        position: relative;
        top: 20px;
    }
    
    .img_mypage, .img_recipe {
        height: 45px;
    }
    
    .img_recipe {
        position: relative;
        top: 5px;  
    }
    
    .btm_logout {
        margin-left: 30px;         
    }
    
    .cart-submit, .sold_out {
        margin-left: 360px;
    }
    
    .sold_out {
        width: 100px;
        background-color: red;
        text-align: center;
        color: #FFFFFF;
        margin-top: 3px;
        margin-left: 370px;
    }
    
    .link_top, .recipe_link {
        text-decoration: none;
        display: block;
    }
        
    .link_top {
        position: relative;
        top: 13px;
    }
    
    .recipe_link {
        color: #000000;
    }
    
    .item_img, .recipe_img {
        max-height: 170px;
    }
    
    .mg50 {
        margin-left: 50px;
    }
    
    .mg10 {
        margin: 0px 10px;
    }
    
    .success {
        padding: 10px;
    }
    
    .alert {
        color: #FFFFFF;
        background-color: red;
        padding-left: 10px;
    }
    
    .recipe_name {
        margin: 5px;
        position: relative;
        right: 25px;
    }
    
    .heart, .recipe_heart {
         width: 25px;
    }
    
    .recipe_heart {
        position: relative;
        left: 470px;
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
    <?php foreach ($err_msg as $value) { ?>
    <p class = "alert"><?php print $value; ?></p>
    <?php } ?>
    <p class = "success"><?php print $success; ?></p>
    <br>
    <h2>レシピの新着一覧</h2>
    <h3>レシピ</h3>
    <table class = "mg100">
        <tr>
        <?php foreach ($r_recipe as $r_value) { ?>
            <?php if ($r_value['recipe_status'] === 1) { ?>
                <td class = "list">
                    <div class = "flex">
                       <form method = "post">
                            <?php if ($r_value['user_recipe_id'] === null) { ?>                    
                                <input type = "image" class = "recipe_heart" src = "heart_ck.png">
                            <?php } else { ?>
                                <input type = "image" class = "recipe_heart" src = "heart.png">
                            <?php } ?>
                            <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
                            <input type = "hidden" name = "heart" value = "recipe_heart">                    
                        </form> 
                        <p class = "recipe_name"><?php print htmlspecialchars ($r_value['recipe_name'], ENT_QUOTES, 'utf-8'); ?></p>
                    </div>
                    <a class = "recipe_link" href = "recipe/recipe_<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>.php">
                        <div class = "flex">
                            <img class = "recipe_img" src = "<?php print $recipe_img_dir . $r_value['recipe_img']; ?>">
                            <p class ="mg10"><?php print htmlspecialchars ($r_value['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                    </a>
                    <p class = "center">調味料名:<?php print htmlspecialchars ($r_value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                </td>
            <?php } ?>
        <?php } ?>
        </tr>
    </table>
   </main>
</body>
</html>