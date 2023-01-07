<?php
date_default_timezone_set("Asia/Tokyo");

//変数の初期化
$current_date = null;
$message = array();
$message_array = array();
$success_message = null;
$error_message = array();
$escaped = array();
$pdo = null;
$statment = null;
$res = null;
$id = null;

//データベース接続
try {
    $pdo = new PDO('mysql:charset=UTF8;dbname=todolist;host=localhost', 'root', '');
} catch (PDOException $e) {
    //接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}

//edit画面
if (isset($_POST["editButton"])) {
    $pdo->beginTransaction();
    echo $_POST['id'];
    try {
        $statment = $pdo->prepare("UPDATE todotable SET edit =1 WHERE id= :id");
        $statment->bindParam(':id', $_POST['id'], PDO::PARAM_STR);
        $res = $statment->execute();
        $res = $pdo->commit();
    }
    catch (Exception $e) {
    $error_message[] = $e->getMessage();
    $pdo->rollBack();
}
if ($res) {
    $success_message = "編集に成功しました";
} else {
    $error_message[] = "削除に失敗しました。";
}
$statment = null;
}

//delete機能
    if (isset($_POST["deleteButton"])) {
        $pdo->beginTransaction();
        try {
            $statment = $pdo->prepare("DELETE FROM todotable WHERE id= :id");
            $statment->bindParam(':id', $_POST['id'], PDO::PARAM_STR);
            $res = $statment->execute();
            $res = $pdo->commit();
        } catch (Exception $e) {
            //エラーが発生したときはロールバック(処理取り消し)
            $error_message[] = $e->getMessage();
            $pdo->rollBack();
        }
        if ($res) {
            $success_message = "削除に成功しました";
        } else {
            $error_message[] = "削除に失敗しました。";
        }
        $statment = null;
    }


//送信して受け取ったデータは$_POSTの中に自動的に入る。
//投稿データがあるときだけログを表示する。
if (isset($_POST["submitButton"])) {
    //表示名の入力チェック
    if (empty($_POST["username"])) {
        $error_message[] = "お名前を入力してください。";
    } else {
        $escaped['username'] = htmlspecialchars($_POST["username"], ENT_QUOTES, "UTF-8");
    }
    //コメントの入力チェック
    if (empty($_POST["comment"])) {
        $error_message[] = "コメントを入力してください。";
    } else {
        $escaped['comment'] = htmlspecialchars($_POST["comment"], ENT_QUOTES, "UTF-8");
    }

    //エラーメッセージが何もないときだけデータ保存できる
    if (empty($error_message)) {
        
        //ここからDB追加のときに追加
        $current_date = date("Y-m-d H:i:s");
        $pdo->beginTransaction();
        echo $_POST['id'];
        echo $_POST['edit'];
        echo $_POST['comment'];
        echo $_POST['username'];

        try {
            if ($_POST['edit']) {
                //SQL作成
                $statment = $pdo->prepare("UPDATE todotable SET edit =0,username=:username,comment=:comment,post_date=:current_date WHERE id= :id");
                //値をセット
                $statment->bindParam(':id', $_POST['id'], PDO::PARAM_STR);
                $statment->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
                $statment->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
                $statment->bindParam(':current_date', $current_date, PDO::PARAM_STR);
                //SQLクエリの実行
            } else {
                //SQL作成
                $statment = $pdo->prepare("INSERT INTO todotable (username, comment, post_date) VALUES (:username, :comment, :current_date)");

                //値をセット
                $statment->bindParam(':username', $escaped["username"], PDO::PARAM_STR);
                $statment->bindParam(':comment', $escaped["comment"], PDO::PARAM_STR);
                $statment->bindParam(':current_date', $current_date, PDO::PARAM_STR);
                //SQLクエリの実行
            }
            $res = $statment->execute();

            //ここまでエラーなくできたらコミット
            $res = $pdo->commit();
        } catch (Exception $e) {
            //エラーが発生したときはロールバック(処理取り消し)
            $error_message[] = $e->getMessage();
            $pdo->rollBack();
        }

        if ($res) {
            $success_message = "コメントを書き込みました。";
        } else {
            $error_message[] = "書き込みに失敗しました。";
        }

        $statment = null;
    }
}


//DBからコメントデータを取得する
$sql = "SELECT id ,username, comment, post_date ,edit FROM todotable ORDER BY post_date ASC";
$message_array = $pdo->query($sql);


//DB接続を閉じる
$pdo = null;
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>todolist</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1 class="title">todolist</h1>
    <hr>
    <div class="boardWrapper">
        <!-- メッセージ送信成功時 -->
        <?php if (!empty($success_message)) : ?>
            <p class="success_message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <!-- バリデーションチェック時 -->
        <?php if (!empty($error_message)) : ?>
            <?php foreach ($error_message as $value) : ?>
                <div class="error_message"><?php echo $value; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!--登録画面-->
        <section>
            <?php if (!empty($message_array)) : ?>
                <?php foreach ($message_array as $value):?>
                    <article>
                        <div class="wrapper">    
                                <form  action='' method="POST">
                                    <input type="hidden" name="id" value="<?php echo $value['id'];?> ">
                                         <button class ="deleteButton" type="submit"name="deleteButton" >done</button>      
                                <?php if (!$value['edit']) { ?>
                                    <button class="editButton" type="submit" name="editButton">edit</button>
                                    <div class="nameArea"> 
                                    <span>名前：</span>
                                    <p class="username"><?php echo $value['username']; ?></p>
                                </div>
                                <div>
                                    <time>：<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?></time>
                                    <p class="comment"><?php echo $value['comment']; ?></p>
                                </div>
                                <?php } else { ?>
                                <form method="POST" action="" class="formWrapper">
                                    <input type="hidden" name='edit' value="<?php echo $value['edit'];?>">
                                    <button class="editButton" type="submit" name="submitButton">submit</button>
                                    <div class="nameArea"> 
                                    <label for="usernameLabel">名前：</label>
                                    <input type="text" name="username" value="<?php echo $value['username']; ?>">
                                </div>
                                <div>
                                    <input type="date" value="<?php echo date('Y/m/d H:i', strtotime($value['post_date'])); ?>">
                                    <input type=textarea class="commentTextArea" name="comment" value="<?php echo $value['comment']; ?>">
                                </div>
                                </form>
                                <?php }?>
                                </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <form method="POST"  action="" class="formWrapper">
            <div>
            <p>Create New Schedule</p>
                <button type="submit"  name="submitButton">submit</button>
                <label for="usernameLabel">名前：</label>
                <input type="text" name="username">
                <input type="date" name="date">
            </div>
            <div>
                <textarea name="comment" class="commentTextArea"></textarea>
            </div>
        </form>
    </div>

</body>

</html>
