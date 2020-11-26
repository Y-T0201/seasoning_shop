<!DOCTYPE html>
<html lang = "ja">
<head>
    <meta charset = "utf-8">
    <title>会員登録</title>
    <style>
    body {
        margin-left: auto;
        margin-right: auto;
        background-image: url(table.jpg);
        background-size: 100%;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }
    
    h3, table, body, .submit{
        width: 500px;
    }
    
    table, tr, th, td {
        border: solid 1px;
        padding: 10px;
    }
    
    h3 {
        background-color: #76A44A;
        padding: 10px;
        color: #FFFFFF;
    }
    
    table {
        border-collapse: collapse;
        background-color: #E4EBF1;
    }
    
    .flex, .top_flex {
        display: flex;
    }
    
    .red {
        background-color: #FFFFFF;
        border: solid 1px ;
        border-color: red;
        color: red;
        height: 25px;
        width: 50px;
        text-align: center;
        font-weight: normal;
    }
    
    .submit {
        height: 40px;
        font-size: 16px;
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
    
    .link_top, .success, h1 {
        color: #463C21;
    }
    
    .link_top, .success {
        font-size: 16px;        
    }
    
    .link_top {
        text-decoration: none;
        display: block;
    }
    
    </style>
</head>
<body>
    <header>
        <div class = "top_flex">
            <a class = "link_top" href = "seasoning_list.php">
                <img class = "icon" src="apron.png">はじめての調味料
            </a>
        </div>
    </header>
    <main>
        <h1>会員登録</h1>
        <h3>下記の項目を入力してください。</h3>
        <div class = "flex">
            <p><div class = "red">必須</div>&nbsp;マークのある項目は全て入力してください。</p>
        </div>
        <form method = "post" action = "users_registration.completion.php">
        <!--<form method = "post" action = "users_registration.php">-->
        <table>
            <tr>
                <th><div class = "flex">ユーザー名&nbsp;&nbsp;<div class = "red">必須</div></th></div>
                <td>
                    <input type = "text" name = "user_name">
                    <P>※半角英数字6文字以上</P>
                </td>
            </tr>
            <tr>
                <th><div class = "flex">パスワード&nbsp;&nbsp;<div class = "red">必須</div></th></div>
                <td>
                    <input type = "password" name = "pw">
                    <P>※半角英数字6文字以上</P>
                </td>
            </tr>
            <tr>
                <th><div class = "flex">メールアドレス&nbsp;&nbsp;<div class = "red">必須</div></th></div>
                <td><input type = "email" name = "mail"></td>
            </tr>    
            <tr>
                <th><div class = "flex">性別&nbsp;&nbsp;<div class = "red">必須</div></th></div>
                <td>
                    <input type = "radio" name = "gender" value = "male">男
                    <input type = "radio" name = "gender" value = "female">女
                </td>
            </tr>
        </table><br>
            <input class = "submit" type = "submit" name = "user" value = "登録">
        </form>
    </main>
</body>
</html>