 
        </div>
        <div id="footer">
             <?php echo date("Y",time()); ?>, Nick Piazza
        </div>
    </body>
</html>
<?php if(isset($database)) {
    $database->close_connection();
}
?>