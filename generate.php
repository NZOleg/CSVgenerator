<?php include "header.php"; ?>
<div class="container-fluid">
<form method="post" action="cvs.php">
    <div class="row">
        <div class="col-sm-3 offset-sm-1"> Formula</div>
        <div class="col-sm-2"> Min</div>
        <div class="col-sm-2"> Max</div>

    </div>
<?php $rows = $_GET['rows'];
for ($i=1; $i<=$rows; $i++):
?>
    <div class="row">
        <div class="col-sm-1">Z<?=$i?></div>
        <input type="text" class="col-sm-3"  name="<?=$i ?>">
        <input type="text" class="col-sm-2" name="<?=$i ?>min">
        <input type="text" class="col-sm-2" name="<?=$i ?>max">

    </div>

</br>
<?php endfor; ?>
    <div class="row">
        <input type="hidden" value="<?=$rows?>" name="rows">
        <input type="submit" value="submit" class="col-sm-2 offset-sm-3">
    </div>
</form>
</div>
<?php include "footer.php"; ?>

