<?php
include "header.php";

use MathParser\StdMathParser;
use MathParser\Interpreting\Evaluator;

$parser = new StdMathParser();
$evaluator = new Evaluator();
$variables = [];
$rows = $_POST['rows'];
for($i=1; $i<=$rows; $i++){
    global $variables;
    $AST = $parser->parse($_POST[$i]);
    $evaluator->setVariables(array_merge([ 'x' => rand($_POST["{$i}min"],$_POST["{$i}max"])], $variables));
    $value = $AST->accept($evaluator);
    $variables["z{$i}"] = $value;
    var_dump($variables);
    echo "value=$value";
}








include "footer.php";