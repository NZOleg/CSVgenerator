<div class="container-fluid">
<form method="post" action="cvs.php">
<?php
    $rows = $_GET['rows'];
    echo "<div class='row'><span class='col-sm-2'>Formula</span><span class='col-sm-2'>Min</span><span class='col-sm-2'>Max</span><span class='col-sm-2'>Scale of random</span><span class='col-sm-2'>Type</span></div>";
    echo '</br>';
    for ($i = 1; $i<=$rows; $i++){
        echo "<div class='row'>";
        echo "z{$i}:";
        echo "<input type='text' name='$i' class='col-sm-2'>";
        echo "<input type='text' name='{$i}min' class='col-sm-2'>";
        echo "<input type='text' name='{$i}max' class='col-sm-2'> ";
        echo "<input type='text' name='{$i}rand' class='col-sm-2'>";
        echo "<select class='col-sm-2'>
              <option value='int'>Integer</option>
              <option value='name'>Name</option>
              <option value='bin'>Yes and No</option>
              <option value='dec'>Decimal</option>
              </select>";
        echo "<input type='hidden' name='rows' value='{$rows}'>";
        echo '</div>';
    };
    echo '<input type="submit"  class="submit">';
?>
</form>
</div>

<?php include "footer.php"; ?>

