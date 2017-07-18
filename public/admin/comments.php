<?php
require_once("../../includes/initialize.php");

if(!$session->is_logged_in()) {
    redirect_to("login.php");
}
if(empty($_GET['id'])) {
    $session->message("No comment id was provided.");
    redirect_to('index.php');
}
$photo = Photograph::find_by_id($_GET['id']);
if(!$photo) {
    $session->message("The photo could not be located.");
    redirect_to('index.php');
}

$comments = $photo->comments();
$photos = Photograph::find_all();

include_layout_template('admin_header.php');
?>

<a href="list_photos.php">&laquo;</a>
<br>
<h2>Comments on <?php echo $photo->filename; ?></h2>
<?php echo output_message($message); ?>

<div id="comments">
<?php foreach($comments as $comment): ?>
    <div class="comment" style="margin-bottom: 2em;">
        <div class="author"></div>
        <?php echo htmlentities($comment->author); ?>
        <div class="body"></div>
        <?php echo strip_tags($comment->body, '<strong><em><p>'); ?>
        <div class="meta-info"></div>
        <?php echo datetime_to_text($comment->created); ?>
        <?php echo $comment->id; ?>
    </div>
    <div class="actions" style="font-size: 0.8em;">
        <a href="delete_comment.php?id=<?php echo $comment->id; ?>">Delete Comment</a>
    </div>
    <?php endforeach; ?>
    <?php if(empty($comments)) {echo "No Comments.";} ?>
</div>

<?php include_layout_template('admin_footer.php'); ?>