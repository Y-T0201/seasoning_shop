<?php
$host = 'localhost';
$username = 'root';
$password = 'nRlkY30ag';
$dbname = 'ec_site';
$charset = 'utf8';

$item_img_dir = '../item_img/'; // アップロードした画像ファイルの保存ディレクトリ
$recipe_img_dir = '../recipe_img/'; // アップロードした画像ファイルの保存ディレクトリ
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
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['btm_logout']) === true) {
    session_destroy();
    header('Location: ../login.php');
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
    
    $heart = '';
    // お気に入り登録
    if (isset($_POST['heart'])) {
        $heart = $_POST['heart'];
    }
    
    // 条件を振り分ける
    if ($heart === 'item_heart') {
        
        if (isset($_POST['item_id']) === true) {
            $item_id = $_POST['item_id']; 
        }
        
        if (count($err_msg) === 0) {
            $sql = 'SELECT item_status, item_name 
                    FROM ec_item_master
                    WHERE item_id = ?';
                    
            // SQL文を実行する準備
            $stmt = $dbh->prepare($sql);
            $stmt->bindvalue(1, $item_id,PDO::PARAM_INT);            
            // SQLを実行
            $stmt->execute();
            // レコードの取得
            $ck_item = $stmt->fetch();

            // ステータスが非公開の処理
            if ($ck_item['item_status'] === 0) {
                $err_msg[] = $ck_item['item_name'] . 'はただいまお気に入り登録することができません';
            }
        
            if (count($err_msg) === 0) {
                $sql = 'SELECT * FROM ec_user_item
                        WHERE user_id = ? AND item_id = ?';
                        
                // SQL文を実行する準備
                $stmt = $dbh->prepare($sql);
                $stmt->bindvalue(1, $user_id,PDO::PARAM_INT);
                $stmt->bindvalue(2, $item_id,PDO::PARAM_INT);            
                // SQLを実行
                $stmt->execute();
                $item = $stmt->fetch();
                
                if (isset($item['item_id']) === TRUE) {
                    //--- 商品が見つかった（delete） ---
                    $sql = 'DELETE
                            FROM ec_user_item
                            WHERE item_id = ?';
                            
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindValue(1,$item_id,PDO::PARAM_INT);
                    // SQLを実行
                    $stmt->execute();
                    
                    $success = $ck_item['item_name'] . 'をお気に入り登録から削除しました';
                
                } else {
                    // SQL文作成
                    $sql = 'INSERT INTO ec_user_item(user_id, item_id, create_datetime)
                            VALUES(?, ?, NOW());';
                            
                    // SQL文を実行する準備
                    $stmt = $dbh->prepare($sql);
                    $stmt->bindvalue(1, $user_id, PDO::PARAM_INT);
                    $stmt->bindvalue(2, $item_id, PDO::PARAM_INT);        
                    // SQLを実行
                    $stmt->execute();

                    $success = $ck_item['item_name'] . 'をお気に入り登録しました';   
                }
            }
        }
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
    
    // 料理番号入力
    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT ec_recipe_master.recipe_id, recipe_name, recipe_img, recipe_status, recipe_comment, ec_recipe_master.item_id, item_name, user_recipe_id
            FROM ec_recipe_master
            JOIN ec_item_master ON ec_recipe_master.item_id = ec_item_master.item_id
            LEFT JOIN ec_user_recipe ON ec_recipe_master.recipe_id = ec_user_recipe.recipe_id
            WHERE ec_recipe_master.recipe_id = 13
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
        
    foreach ($r_recipe as $value) {

        $select_item_id = $value['item_id'];
    }
    }
    
    // アップロードを表示
    // SQL文を作成
    $sql = 'SELECT ec_item_master.item_id, item_name, price, item_img, item_status, item_comment, stock, user_item_id
            FROM ec_item_master
            JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
            LEFT JOIN ec_user_item ON ec_item_master.item_id = ec_user_item.item_id          
            WHERE ec_item_master.item_id = ?
            ORDER BY ec_item_master.item_id DESC'; 

    // SQL文を実行する準備
    $stmt = $dbh->prepare($sql);
    $stmt->bindvalue(1, $select_item_id, PDO::PARAM_INT);
    // SQLを実行
    $stmt->execute();
    // レコードの取得
    $rows = $stmt->fetchAll();
    // 1行ずつ結果を配列で取得
    // var_dump($rows);
    
    foreach ($rows as $row) {
        $data[] = $row;
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
    <?php foreach ($r_recipe as $r_value) { ?>
    <title><?php print htmlspecialchars ($r_value['recipe_name'], ENT_QUOTES, 'utf-8'); ?></title>
    <?php } ?>
    <style>
    body, h2, h3, table, .success, .alert, .recipe_flex {
        margin-left: auto;
        margin-right: auto;
    }
    
    body {
        width: 1250px;
        background-image: url(../wood_bg.jpg);
        background-size: 100%;
        background-repeat: no-repeat;
        background-attachment: fixed;    
    }
    
    /*table {*/
    /*    table-layout: fixed;*/
    /*}*/
    
    h2, h3, .mg125, .success, .alert {
        width: 1026px;
    }
    
    .recipe_memo {
        width: 500px;
        border: solid 1px;
        background-color: #FFFFFF;
    }
    
    .recipe_flex, .cook {
        width: 1000px;
    }
    
    .list {
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
    
    .flex, .cart_flex, .flex_mypage, .recipe_flex, .top_flex, .recipe_heart_flex {
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
    
    .link_top, .success, h2 {
        color: #463C21;
    }
    
    .link_top, .success, .cart_price, .f16 {
        font-size: 16px;
    }
    
    .f20, .recipe_comment {
        font-size: 20px;
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
    
    .link_top, .recipe_link, .item_link {
        text-decoration: none;
        display: block;
    }
        
    .link_top {
        position: relative;
        top: 13px;
    }
    
    .recipe_link, .item_link {
        color: #000000;
    }
    
    .item_img {
        max-height: 170px;
    }
    
    .recipe_img {
        max-width: 500px;
        max-height: 350px;
    }    
    
    .mg50 {
        margin-left: 50px;
    }
    
    .mg10, .recipe_comment {
        margin: 0px 10px;
    }
    
    .food {
        border-bottom: solid 1px;
        position: relative;
        left: 5px;
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
    
    .recipe_heart_flex {
        margin-top: 5px;
        position: relative;
        left: 150px;
    }
    
    .heart_font {
        color: #DF5656;
        margin-top: 5px;
        margin-left:5px;
    }
    
    </style>
</head>
<body>
    <header>
        <div class = "top_flex">
            <a class = "link_top" href = "../seasoning_list.php">
                <img class = "icon" src="../apron.png">はじめての調味料
            </a>
           <div class = "flex_recipe">
                <a class = "btm_recipe" href = "../recipe_list.php">
                    <img class = "img_recipe" src = "../recipe.png">
                    レシピ
                </a>    
            </div>
            <form class = "btm_search" method = "get" action = "../search_list.php">
                <select size = "1" name = "keyword">
                    <option value = "all">キーワード</option>
                    <option value = "item">調味料</option>
                    <option value = "recipe">レシピ</option>
                </select>
                <input type = "text" name = "contents">
                <input type = "submit" name = "search" value = "検索">
            </form>
            <div class = "flex_mypage">
                <a class = "btm_mypage" href = "../mypage.php">
                    <img class = "img_mypage" src = "../mypage.png">
                    MyPage
                </a>    
            </div>
            <form method = "post">
                <input class = "btm_logout" type = "image" src = "../logout.png">
                <input type = "hidden" name = "btm_logout" value = "btm_logout">
            </form>
            <div class = "cart_flex">
                <a class = "cart_price"  href = "../shopping_cart.php">
                    <img class = "cart_img" src = "../cart.png">
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
    <?php foreach ($r_recipe as $r_value) { ?>
        <?php if ($r_value['recipe_status'] === 1) { ?>
            <h2><?php print htmlspecialchars ($r_value['recipe_name'], ENT_QUOTES, 'utf-8'); ?></h2>
            <div class = "recipe_flex">
                <img class = "recipe_img" src = "<?php print $recipe_img_dir . $r_value['recipe_img']; ?>">
                <p class ="recipe_comment"><?php print htmlspecialchars ($r_value['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></p>
            </div>
            <div class = "recipe_heart_flex">
                       <form method = "post">
                            <?php if ($r_value['user_recipe_id'] === null) { ?>                    
                                <input type = "image" class = "recipe_heart" src = "../heart_ck.png">
                            <?php } else { ?>
                                <input type = "image" class = "recipe_heart" src = "../heart.png">
                            <?php } ?>
                            <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
                            <input type = "hidden" name = "heart" value = "recipe_heart">                    
                        </form>
                <p class = "heart_font">お気に入り</p>
            </div>
            <table class = "recipe_memo">
                <tr><th colspan="3">材料（2人分）</th></tr>
                <tr class = "f16">
                    <td>
                        <p class = food>絹豆腐</p>
                        <p class = food>豚ミンチ</p>
                        <p class = food>XO醤</p>
                        <p class = food>にんにく（チューブ）</p>
                        <p class = food>酒</p>
                        <p class = food>ねぎ</p>
                        <p class = food>片栗粉</p>
                        <p class = food>☆みそ</p>
                        <p class = food>☆粉末和風だし</p>
                        <p class = food>☆粉末鶏がらスープ</p>
                        <p class = food>☆醤油</p>
                    </td>
                    <td>
                        <p>400g</p>
                        <p>100g</p>
                        <p>大さじ1</p>
                        <p>2cm</p>
                        <p>大さじ2</p>
                        <p>好きなだけ</p>
                        <p>適量</p>
                        <p>小さじ1</p>
                        <p>小さじ1/2</p>
                        <p>小さじ1/2</p>
                        <p>小さじ1</p>
                    </td>
                </tr>
            </table>
            <br>
            <table class = "cook">
                <tr><th>作り方</th></tr>
                <tr class = "f20">
                    <td>
                        <ol>
                            <li>豆腐を塩を入れた水で茹でる。（煮崩れ防止のため）</li><br>
                            <li>フライパンにXO醤とにんにくを入れて、火にかけ香りが出てきたら豚ミンチを炒めていく。酒を回し入れして、しばらく蒸し焼きする。</li><br>
                            <li>☆を入れてしっかりミンチに味を馴染ませておく。ひたひたの水を入れ、水きりした豆腐を入れしばらく煮る。</li><br>
                            <li>水溶き片栗粉でとろみをつけ、ねぎを散らして完成！</li><br>
                        </ol>                        
                    </td>
                </tr>
                <tr><th>コツ・ポイント</th></tr>
                <tr class = "f20">
                    <td><br>
                        豆腐は塩茹ですることで、余分な水分が出て、煮崩れしにくくなります。
                        ミンチにしっかり味付けしてから煮るのでコクのあるマーボー豆腐になります。辛いマーボー豆腐にする場合は豆板醤を加えてください。<br><br>
                    </td>
                </tr>
            </table>    
        <?php } ?>
    <?php } ?>
    <h3>使用した調味料</h3>
    <table class = "mg125">
        <tr>
        <?php foreach ($data as $value) { ?>
            <?php if ($value['item_status'] === 1) { ?>
                <td class = "list">
                    <a class = "item_link" href = "../item/item_<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>.php">
                        <div class = "flex">
                            <img class = "item_img" src = "<?php print $item_img_dir . $value['item_img']; ?>">
                            <p class = "mg10"><?php print htmlspecialchars ($value['item_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                    </a>
                    <div class = "flex">
                        <p>調味料名:<?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                        <!--税率8%計算-->
                        <p class = "mg50">小計<?php print htmlspecialchars (number_format(round($value['price'] * 1.08)), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>
                    </div>
                    <div class = "flex">
                        <form method = "post">
                            <?php if ($value['user_item_id'] === null) { ?>                    
                                <input type = "image" class = "heart" src = "../heart_ck.png">
                            <?php } else { ?>
                                <input type = "image" class = "heart" src = "../heart.png">
                            <?php } ?>
                            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                            <input type = "hidden" name = "heart" value = "item_heart">                    
                        </form>    
                        <?php if ($value['stock'] === 0) { ?>
                            <P class = "sold_out">売り切れ</P>
                        <?php } else if ($value['stock'] > 0) { ?>
                            <form method = "post">
                                <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">
                                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                            </form>                          
                        <?php } ?>
                    </div>
                </td>
            <?php } ?>
        <?php } ?>
        </tr>
    </table>
   </main>
</body>
</html>