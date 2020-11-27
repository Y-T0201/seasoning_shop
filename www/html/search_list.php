<?php
$host = 'mysql';
$username = 'root';
$password = 'root';
$dbname = 'seasoning_shop';
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
    
    $seach_item = '';
    if (isset($_GET['contents']) === true) {
        $seach_item = $_GET['contents'];
    }
    
    // 検索が未入力の処理
    if ($seach_item === '') {
        $err_msg[] = '検索したいキーワードを入力してください';
    }
    
    // // キーワードが入力されているときはwhere以下を組み合わせる
    // if (strlen($seach_item) > 0) {
    //     // 受け取ったキーワードの全角スペースを変換する
    //     $seach_items = str_replace("　", " ", $seach_item);
        
    //     // キーワードを空白で分割する
    //     $array = explode(" ", $seach_items);
        
    //     // 分割された個々のキーワードをSQLの条件where句に反映する
    //     $where = "WHERE ";
        
    //     for ($i = 0; $i <count($array);$i++) {
    //         $where .= "(列名 LIKE '%$array[$i]%')";
            
    //         if ($i < count($array) -1) {
    //             $where .= " AND ";
    //         }
    //     }
    // }
    
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
            $sql = 'SELECT ec_item_master.item_id, item_name, price, item_img, item_status, item_comment, stock, user_item_id
                    FROM ec_item_master
                    JOIN ec_item_stock ON ec_item_master.item_id = ec_item_stock.item_id
                    LEFT JOIN ec_user_item ON ec_item_master.item_id = ec_user_item.item_id
                    WHERE 1 = 1 ';

            $array = [];
            // キーワードが入力されているときはwhere以下を組み合わせる
            if (strlen($seach_item) > 0) {
                // 受け取ったキーワードの全角スペースを変換する
                $seach_items = str_replace("　", " ", $seach_item);
                
                // キーワードを空白で分割する
                $array = explode(" ", $seach_items);
                
                // 分割された個々のキーワードをSQLの条件where句に反映する
                // $where = "WHERE ";
                
                // for ($i = 0; $i <count($array);$i++) {
                //     $sql .= "and (ec_item_master.item_name LIKE '%$array[$i]%' or ec_item_master.item_comment LIKE '%$array[$i]%') ";
                    
                // }
                for ($i = 0; $i <count($array);$i++) {
                    $sql .= "and (ec_item_master.item_name LIKE ? or ec_item_master.item_comment LIKE ?) ";
                    
                }
            }
            
            $sql .= 'ORDER BY ec_item_master.item_id DESC';
            
            $statement = $dbh->prepare($sql);
            for ($i = 0; $i <count($array);$i++) {
                $s_item = "%$array[$i]%";
                $statement->bindValue($i*2+1,$s_item, PDO::PARAM_STR);
                $statement->bindValue($i*2+2,$s_item, PDO::PARAM_STR);
            }
            $statement->execute();
            
            $rows = $statement->fetchAll();
            
            foreach ($rows as $row) {
                $r_item[] = $row;
            }
            
            $sql = 'SELECT ec_recipe_master.recipe_id, recipe_name, recipe_img, recipe_status, recipe_comment, item_name, user_recipe_id
                    FROM ec_recipe_master
                    JOIN ec_item_master ON ec_recipe_master.item_id = ec_item_master.item_id
                    LEFT JOIN ec_user_recipe ON ec_recipe_master.recipe_id = ec_user_recipe.recipe_id
                    WHERE 1 = 1 ';

            $recipe_array = [];
            // キーワードが入力されているときはwhere以下を組み合わせる
            if (strlen($seach_item) > 0) {
                // 受け取ったキーワードの全角スペースを変換する
                $seach_items = str_replace("　", " ", $seach_item);
                
                // キーワードを空白で分割する
                $recipe_array = explode(" ", $seach_items);
                
                // 分割された個々のキーワードをSQLの条件where句に反映する
                for ($j = 0; $j <count($recipe_array);$j++) {
                    $sql .= "and (ec_recipe_master.recipe_name LIKE ? or ec_recipe_master.recipe_comment LIKE ? or ec_item_master.item_name LIKE ?) ";
                }
            }

            $sql .= 'ORDER BY ec_recipe_master.recipe_id DESC';

            $statement = $dbh->prepare($sql);
            for ($j = 0; $j <count($array);$j++) {
                $s_recipe = "%$recipe_array[$j]%";
                $statement->bindValue($j*3+1,$s_recipe, PDO::PARAM_STR);
                $statement->bindValue($j*3+2,$s_recipe, PDO::PARAM_STR);
                $statement->bindValue($j*3+3,$s_recipe, PDO::PARAM_STR); 
            }               
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
    body, h2, h3, .success, .alert, .item_ul, .recipe_ul {
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
        width: 1040px;
    }
    
    .list {
        margin-left: 5px;
        margin-top: 5px;
        width: 500px;
        height: 260px;
        border: solid 1px;
        padding: 10px;
        display: inline-block;
        background-color: #ffffff;
        list-style-type: none; 
    }
    
    h3, .cart_price {
        background-color: #76A44A;
    }
    
    h3 {
        padding: 10px;
        color: #FFFFFF;
    }
    
    .item_ul, .recipe_ul {
        width: 1100px;
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
    
    .a_item_details {
        margin-left: 220px;
        display: block;
        text-decoration: none;
        border: solid 1px;
        width: 120px;
        height: 25px;
        text-align: center;
        color: #FFFFFF;
        background-color: #76A44A;
    }

    .cart-submit, .sold_out {
        margin-left: 20px;
    }
    
    .sold_out {
        width: 120px;
        height: 25px;
        background-color: red;
        text-align: center;
        color: #FFFFFF;
        margin-top: 0px;
    }
    
    .link_top, .recipe_link, .item_link {
        text-decoration: none;
        display: block;
    }
        
    .link_top {
        position: relative;
        top: 13px;
    }
    
    .item_img, .recipe_img {
        max-height: 170px;
    }
    
    .mg50 {
        margin-left: 50px;
    }
    
    .success {
        padding: 10px;
    }
    
    .alert {
        color: #FFFFFF;
        background-color: red;
        padding-left: 10px;
    }
    
    .recipe_link, .item_link {
        color: #000000;
    }
    
    .mg10 {
        margin: 0px 10px;
    }
    
    .recipe_name {
        margin: 5px;
        position: relative;
        right: 25px;
    }
    
    .item_name, .recipe_name {
        font-weight: bold;
    }

    .heart, .recipe_heart {
         width: 25px;
    }
    
    .recipe_heart {
        position: relative;
        left: 470px;
    }

    .a_recipe_details {
        position: relative;
        top: 15px;
        left: 370px;
        display: block;
        text-decoration: none;
        border: solid 1px;
        width: 120px;
        height: 25px;
        text-align: center;
        color: #FFFFFF;
        background-color: #76A44A;
    }

    .recipe_item_name {
        position: relative;
        right: 115px;
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
    <h2>「<?php print $seach_item ?>」の検索結果</h2>
    <?php if ($keyword == 'item') { ?>
        <h3>調味料</h3>
        <ul class = "item_ul">
            <?php foreach ($r_item as $value) { ?>
                <?php if ($value['item_status'] === 1) { ?>
                    <li class = "list">
                        <div class = "flex">
                        <a href="seasoning_details.php?item_id=<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                            <img class = "item_img" src="<?php print $item_img_dir . $value['item_img']; ?>">
                        </a>
                        <p class = "mg10"><?php print htmlspecialchars ($value['item_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                        <div class = "flex">
                            <p class = "item_name">調味料名:<?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                            <!--税率8%計算-->
                            <p class = "mg50">小計<?php print htmlspecialchars (number_format(round($value['price'] * 1.08)), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>
                        </div>
                        <div class = "flex">
                            <form method = "post">
                                <?php if ($value['user_item_id'] === null) { ?>                    
                                    <input type = "image" class = "heart" src = "heart_ck.png">
                                <?php } else { ?>
                                    <input type = "image" class = "heart" src = "heart.png">
                                <?php } ?>
                                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                <input type = "hidden" name = "heart" value = "item_heart">                    
                            </form>
                            <a class = "a_item_details" href="seasoning_details.php?item_id=<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8');?>">▶詳しく見る</a> 
                            <?php if ($value['stock'] === 0) { ?>
                                <P class = "sold_out">売り切れ</P>
                            <?php } else if ($value['stock'] > 0) { ?>
                                <form method = "post">    
                                    <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">
                                    <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                    <!--<input type = "hidden" name = "contents" value="<?php print $seach_item; ?>">-->
                                </form>
                            </div>
                        <?php } ?>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
    <?php } else if ($keyword == 'recipe') { ?>
        <h3>レシピ</h3>
        <ul class = "recipe_ul">
            <?php foreach ($r_recipe as $r_value) { ?>
                <?php if ($r_value['recipe_status'] === 1) { ?>
                    <li class = "list">
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
                        <div class = "flex">
                            <a href="recipe_details.php?recipe_id=<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
                                <img class = "recipe_img" src="<?php print $recipe_img_dir . $r_value['recipe_img']; ?>">
                            </a>
                            <p class ="mg10"><?php print htmlspecialchars ($r_value['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                        </a>
                        <div class = "flex">
                            <a class = "a_recipe_details" href="recipe_details.php?recipe_id=<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">▶詳しく見る</a>
                            <p class = "recipe_item_name">調味料名:<?php print htmlspecialchars ($r_value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
    <?php } else if ($keyword == 'all') { ?>
        <h3>調味料</h3>
        <ul class = "item_ul">
            <?php foreach ($r_item as $value) { ?>
                <?php if ($value['item_status'] === 1) { ?>
                    <li class = "list">
                        <div class = "flex">
                            <a href="seasoning_details.php?item_id=<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                <img class = "item_img" src="<?php print $item_img_dir . $value['item_img']; ?>">
                            </a>
                            <p class ="mg10"><?php print htmlspecialchars ($value['item_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                        <div class = "flex">
                            <p class = "item_name">調味料名:<?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                            <!--税率8%計算-->
                            <p class = "mg50">小計<?php print htmlspecialchars (number_format(round($value['price'] * 1.08)), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>
                        </div>
                        <div class = "flex">
                            <form method = "post">
                                <?php if ($value['user_item_id'] === null) { ?>                    
                                    <input type = "image" class = "heart" src = "heart_ck.png">
                                <?php } else { ?>
                                    <input type = "image" class = "heart" src = "heart.png">
                                <?php } ?>
                                <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                <input type = "hidden" name = "heart" value = "item_heart">                    
                            </form>
                            <a class = "a_item_details" href="seasoning_details.php?item_id=<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8');?>">▶詳しく見る</a> 
                            <?php if ($value['stock'] === 0) { ?>
                                <P class = "sold_out">売り切れ</P>
                            <?php } else if ($value['stock'] > 0) { ?>
                                <form method = "post">    
                                    <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">
                                    <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                                    <!--<input type = "hidden" name = "contents" value="<?php print $seach_item; ?>">-->
                                </form>    
                            <?php } ?>
                        </div>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
        <h3>レシピ</h3>
        <ul class = "recipe_ul">
            <?php foreach ($r_recipe as $r_value) { ?>
                <?php if ($r_value['recipe_status'] === 1) { ?>
                    <li class = "list">
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
                        <div class = "flex">
                            <a href="recipe_details.php?recipe_id=<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
                                <img class = "recipe_img" src="<?php print $recipe_img_dir . $r_value['recipe_img']; ?>">
                            </a>
                            <p class ="mg10"><?php print htmlspecialchars ($r_value['recipe_comment'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                        </a>
                        <div class = "flex">
                            <a class = "a_recipe_details" href="recipe_details.php?recipe_id=<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">▶詳しく見る</a>
                            <p class = "recipe_item_name">調味料名:<?php print htmlspecialchars ($r_value['item_name'], ENT_QUOTES, 'utf-8'); ?></p>
                        </div>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>    
    <?php } ?>
   </main>
</body>
</html>