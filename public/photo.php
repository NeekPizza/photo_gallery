<?php
require_once("../includes/initialize.php");

if(empty($_GET['id'])) {
    $session->message("No photograph id was provided.");
    redirect_to('index.php');
}

$photo = Photograph::find_by_id($_GET['id']);
if(!$photo) {
    $session->message("The photo could not be located.");
    redirect_to('index.php');
}

if(isset($_POST['submit'])) {
    
    $author = trim($_POST['author']);
    $body = trim($_POST['body']);
    $new_comment = Comment::make($photo->id, $author, $body);
 if($new_comment && $new_comment->save()) {
    } else {
        $message = "There was an error that pevented the comment from being saved.";
    }
} else {
    $author = "";
    $body = "";
}

$comments = $photo->comments();

include_layout_template('header.php'); ?>

<a href="index.php">&laquo; Back</a>
<br>

<div>    
    <img src="<?php echo $photo->image_path(); ?>">
    <p><?php echo $photo->caption; ?></p>
</div>  
<br>
<br>

<div id="comments">
<?php foreach($comments as $comment): ?>
    <div class="comment" style="margin-bottom: 2em;">
        <div class="author"></div>
        <?php echo htmlentities($comment->author); ?>
        <div class="body"></div>
        <?php echo strip_tags($comment->body, '<strong><em><p>'); ?>
        <div class="meta-info"></div>
        <?php echo datetime_to_text($comment->created); ?>
    </div>
    <?php endforeach; ?>
    <?php if(empty($comments)) {echo "No Comments.";} ?>
</div>

<div id="comment-form">
    <h3>New Comment</h3>
    <?php echo output_message($message); ?>
    <form action="photo.php?id=<?php echo $photo->id; ?>" method="POST">
        <table>
            <tr>
                <td>Your Name:</td>
                <td><input type="text" name="author" value="<?php echo $author; ?>"></td>
            </tr>
             <tr>
                <td>Your comment:</td>
                <td><textarea name="body" cols="40" rows="8"><?php echo $body; ?></textarea></td>
            </tr>
             <tr>
                <td>&nbsp;</td>
                <td><input type="submit" name="submit" value="Submit Comment"></td>
            </tr>
        </table>
    </form>
</div>

<?php include_layout_template('footer.php'); ?>