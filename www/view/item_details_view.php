<!DOCTYPE html>
<html lang = "ja">
<head>
    <meta charset = "utf-8">
    <?php foreach ($data as $value) { ?>
        <title><?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></title>
    <?php } ?>
    <style>
    body, h2, h3,.item_table, .success, .alert, .item_flex {
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
    
    h2, h3, .success, .alert {
        width: 1026px;
    }
   
    .right {   
        position: relative;
        left: 930px;
    }
     
    .item_flex, .item_table, .right {
        width: 1000px;
    }
    
    .item_table, .item_th, .item_td {
        border-collapse: collapse;
        border: solid 1px;
    }
    
    .item_table {
        margin-top: 30px;
    }
    
    .item_th {
        width: 200px;
    }
    
    .item_td {
        padding: 10px 5px;
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
    
    .flex, .cart_flex, .flex_mypage, .flex_recipe, .top_flex, .item_flex, .heart_flex {
        display: flex;
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
    
    .link_top, .success, h2 {
        color: #463C21;
    }
    
    .link_top, .success, .cart_price, .price {
        font-size: 16px;
    }
    
    .item_comment {
        font-size: 20px;
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
        margin-left: 30px;
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
    
    .cart-submit, .sold_out {
        width: 190px;
        /*margin-left: 360px;*/
    }
    
    .sold_out {
        /*width: 190px;*/
        background-color: red;
        text-align: center;
        color: #FFFFFF;
        margin-top: 0px;
        /*margin-left: 370px;*/
    }
    
    .link_top, .item_link, .recipe_link {
        text-decoration: none;
        display: block;
    }
    
    .link_top {
        position: relative;
        top: 13px;
    }
    
    .item_link, .recipe_link {
        color: #000000;
    }
   
    .item_img {
        max-width: 500px;
        max-height: 350px;
    }
    
    .recipe_img {
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
    
    .recipe_name {
        margin: 5px;
        position: relative;
        right: 25px;
    }
    
    .mg10, .item_comment {
        margin: 0px 10px;
    }
    
    .heart, .recipe_heart {
         width: 25px;
    }
    
    .recipe_heart {
        position: relative;
        left: 470px;
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
                    <!--<option value = "item, recipe">キーワード</option>-->
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
        </div>
    </header>
    <main>
    <?php foreach ($err_msg as $value) { ?>
    <p class = "alert"><?php print $value; ?></p>
    <?php } ?>
    <p class = "success"><?php print $success; ?></p>
    <br>
    <?php foreach ($data as $value) { ?>
        <?php if ($value['item_status'] === 1) { ?>
            <h2><?php print htmlspecialchars ($value['item_name'], ENT_QUOTES, 'utf-8'); ?></h2>
            <div class = "item_flex">
                <img class = "item_img" src = "<?php print $item_img_dir . $value['item_img']; ?>">
                <p class = "item_comment"><?php print htmlspecialchars ($value['item_comment'], ENT_QUOTES, 'utf-8'); ?></p>
            </div> 
            <table class = "item_table">
                <tr>
                    <th class = "item_th">ブランド</th>
                    <td class = "item_td">●●●</td>
                </tr>
                <tr>
                    <th class = "item_th">メーカー</th>
                    <td class = "item_td">▲▲▲</td>
                </tr>            
                 <tr>
                    <th class = "item_th">原産国名</th>
                    <td class = "item_td">日本</td>
                </tr>
                <tr>
                    <th class = "item_th">原材料</th>
                    <td class = "item_td">大豆油、干しえび、干し貝柱、唐辛子(塩漬け)、香辛料(ニンニク、唐辛子、山椒、白胡椒)、そら豆みそ、砂糖、酵母エキス、調味料(核酸)、(原料の一部にえび・小麦・大豆を含む)</td>
                </tr>
                <tr>
                    <th class = "item_th">梱包サイズ</th>
                    <td class = "item_td">12x7.1x7.1cm</td>
                </tr>
                <tr>
                    <th class = "item_th">商品の重量</th>
                    <td class = "item_td">550g</td>
                </tr>
            </table>
            <div class = "right">
                <!--税率8%計算-->
                <p class = "price">小計<?php print htmlspecialchars (number_format(round($value['price'] * 1.08)), ENT_QUOTES, 'utf-8'); ?>円（税込み）</p>
                <div class = "heart_flex">
                        <form method = "post">
                            <?php if ($value['user_item_id'] === null) { ?>                    
                                <input type = "image" class = "heart" src = "../heart_ck.png">
                            <?php } else { ?>
                                <input type = "image" class = "heart" src = "../heart.png">
                            <?php } ?>
                            <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                            <input type = "hidden" name = "heart" value = "item_heart">                    
                        </form>  
                    <p class = "heart_font">お気に入り</p>
                </div>
                <?php if ($value['stock'] === 0) { ?>
                    <P class = "sold_out">売り切れ</P>
                <?php } else if ($value['stock'] > 0) { ?>
                    <form method = "post">
                        <input class = "cart-submit" type = "submit" name = "cart_post" value = "カートに入れる">
                        <input type = "hidden" name = "item_id" value = "<?php print htmlspecialchars($value['item_id'], ENT_QUOTES, 'utf-8'); ?>">
                    </form>                          
                <?php } ?>
            </div>
        </table>
        <?php } ?>
    <?php } ?>        
    <h3>おすすめレシピ</h3>
    <table class = "mg100">
        <tr>
        <?php foreach ($r_recipe as $r_value) { ?>
            <?php if ($r_value['recipe_status'] === 1) { ?>
                <td class = "list">
                    <div class = "flex">
                       <form method = "post">
                            <?php if ($r_value['user_recipe_id'] === null) { ?>                    
                                <input type = "image" class = "recipe_heart" src = "../heart_ck.png">
                            <?php } else { ?>
                                <input type = "image" class = "recipe_heart" src = "../heart.png">
                            <?php } ?>
                            <input type = "hidden" name = "recipe_id" value = "<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>">
                            <input type = "hidden" name = "heart" value = "recipe_heart">                    
                        </form>
                        <p class = "recipe_name"><?php print htmlspecialchars ($r_value['recipe_name'], ENT_QUOTES, 'utf-8'); ?></p>
                    </div>
                    <a class = "recipe_link" href = "../html/recipe/recipe_<?php print htmlspecialchars($r_value['recipe_id'], ENT_QUOTES, 'utf-8'); ?>.php">
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