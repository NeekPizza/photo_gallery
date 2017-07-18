<?php
require_once("../includes/initialize.php");
if(!$session->is_logged_in()) {
    redirect_to("login.php");
}

$logfile = SITE_ROOT.DS.'logs'.DS.'log.txt';

if(isset($_GET['clear'])) {
    if($_GET['clear'] == 'true') {
        file_put_contents($logfile, '');
        log_action('Logs Cleared', "by User ID {$session->user_id}");
        redirect_to('logfile.php');
    }
}
?>
<?php include_layout_template('admin_header.php'); ?>

<link href="../public/stylesheets/main.css" media="all" rel="stylesheet" type="text/css">

<a href="../public/admin/index.php">&laquo; Back</a>
<br>
<h2>Log File</h2>
<p><a href="logfile.php?clear=true">Clear log file</a></p>



<?php
    if(file_exists($logfile) && is_readable($logfile) && $handle=fopen($logfile, 'r')) {

        echo "<ul class=\"log-entries\">";
        while(!feof($handle)){
            $entry = fgets($handle);
            if(trim($entry) != "") {
                echo "<li>{$entry}</li>";
            }
        }
         echo "</ul>";
        fclose($handle);
    } else {
    echo "Could not read from {$logfile}.";
    
    }
?>

<?php include_layout_template('admin_footer.php');?>