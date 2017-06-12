<?php
include "header.php";

use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;

$parser = new StdMathParser();
$evaluator = new Evaluator();

$rows = $_POST['rows'];
for($i=1; $i<=$rows; $i++){
    $AST = $parser->parse($_POST[$i]);
    $evaluator->setVariables([ 'x' => rand($_POST["{$i}min"],$_POST["{$i}max"])]);
    $value = $AST->accept($evaluator);

    echo "value=$value ";
}








include "footer.php";