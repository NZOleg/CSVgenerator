<?php
/*
 * @author      Frank Wikström <frank@mossadal.se>
 * @copyright   2015 Frank Wikström
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
*/

namespace MathParser\Interpreting;

use MathParser\Interpreting\Visitors\Visitor;
use MathParser\Parsing\Nodes\Node;
use MathParser\Parsing\Nodes\ExpressionNode;
use MathParser\Parsing\Nodes\VariableNode;
use MathParser\Parsing\Nodes\FunctionNode;
use MathParser\Parsing\Nodes\ConstantNode;

use MathParser\Parsing\Nodes\IntegerNode;
use MathParser\Parsing\Nodes\RationalNode;
use MathParser\Parsing\Nodes\NumberNode;

use MathParser\Lexing\StdMathLexer;
use MathParser\Lexing\TokenAssociativity;

use MathParser\Exceptions\UnknownConstantException;

/**
 * Create LaTeX code for prettyprinting a mathematical expression
 * (for example via MathJax)
 *
 * Implementation of a Visitor, transforming an AST into a string
 * giving LaTeX code for the expression.
 *
 * The class in general does *not* generate the best possible LaTeX
 * code, and needs more work to be used in a production setting.
 *
 * ## Example:
 * ~~~{.php}
 * $parser = new StdMathParser();
 * $f = $parser->parse('exp(2x)+xy');
 * printer = new LaTeXPrinter();
 * result = $f->accept($printer);    // Generates "e^{2x}+xy"
 * ~~~
 *
 * Note that surrounding `$`, `$$` or `\begin{equation}..\end{equation}`
 * has to be added manually.
 *
 */
class LaTeXPrinter implements Visitor
{
    /** StdMathLexer $lexer */
    private $lexer;

    /** Constructor. Create a LaTeXPrinter. */
    public function __construct()
    {
        $this->lexer = new StdMathLexer();
    }

    /**
     * Generate LaTeX code for an ExpressionNode
     *
     * Create a string giving LaTeX code for an ExpressionNode `(x op y)`
     * where `op` is one of `+`, `-`, `*`, `/` or `^`
     *
     * ### Typesetting rules:
     *
     * - Adds parentheses around each operand, if needed. (I.e. if their precedence
     *   lower than that of the current Node.) For example, the AST `(^ (+ 1 2) 3)`
     *   generates `(1+2)^3` but `(+ (^ 1 2) 3)` generates `1^2+3` as expected.
     * - Multiplications are typeset implicitly `(* x y)` returns `xy` or using
     *   `\cdot` if the first factor is a FunctionNode or the (left operand) in the
     *   second factor is a NumberNode, so `(* x 2)` return `x \cdot 2` and `(* (sin x) x)`
     *   return `\sin x \cdot x` (but `(* x (sin x))` returns `x\sin x`)
     * - Divisions are typeset using `\frac`
     * - Exponentiation adds braces around the power when needed.
     *
     * @param ExpressionNode $node AST to be typeset
     * @retval string
     */
    public function visitExpressionNode(ExpressionNode $node)
    {

        $operator = $node->getOperator();
        $left = $node->getLeft();
        $right = $node->getRight();

        switch ($operator)
        {
            case '+':

                $leftValue = $left->accept($this);
                $rightValue = $this->parenthesize($right, $node);
                return "$leftValue+$rightValue";

            case '-':

                if ($right) {
                    // Binary minus

                    $leftValue = $left->accept($this);
                    $rightValue = $this->parenthesize($right, $node);
                    return "$leftValue-$rightValue";

                } else {
                    // Unary minus

                    $leftValue = $this->parenthesize($left, $node);
                    return "-$leftValue";
                }

            case '*':
                $operator = '';
                if ($left instanceof FunctionNode || $right instanceof NumberNode || $right instanceof IntegerNode || $right instanceof RationalNode || ($right instanceof ExpressionNode && $right->getLeft() instanceof NumberNode)) {
                    $operator = '\cdot ';
                }
                $leftValue = $this->parenthesize($left, $node);
                $rightValue = $this->parenthesize($right, $node);
                return "$leftValue$operator$rightValue";

            case '/':

                return '\frac{'.$left->accept($this).'}{'.$right->accept($this).'}';

            case '^':

                $leftValue = $this->parenthesize($left, $node, '', true);
                return $leftValue.'^'.$this->bracesNeeded($right);

        }
    }

    /**
     * Generate LaTeX code for a NumberNode
     *
     * Create a string giving LaTeX code for a NumberNode. Currently,
     * there is no special formatting of numbers.
     *
     * @param NumberNode $node AST to be typeset
     * @retval string
     */
    public function visitNumberNode(NumberNode $node)
    {
        $val = $node->getValue();
        return "$val";
    }

    public function visitIntegerNode(IntegerNode $node)
    {
        $val = $node->getValue();
        return "$val";
    }

    public function visitRationalNode(RationalNode $node)
    {
        $p = $node->getNumerator();
        $q = $node->getDenominator();

        if ($q == 1) return "$p";

        return "\\frac{{$p}}{{$q}}";
    }

    /**
     * Generate LaTeX code for a VariableNode
     *
     * Create a string giving LaTeX code for a VariableNode. Currently,
     * there is no special formatting of variables.
     *
     * @param VariableNode $node AST to be typeset
     * @retval string
     */
    public function visitVariableNode(VariableNode $node)
    {
        return $node->getName();
    }


    /**
     * Generate LaTeX code for factorials
     *
     * @param FunctionNode $node AST to be typeset
     * @retval string
     */
    private function visitFactorialNode(FunctionNode $node)
    {
        $functionName = $node->getName();
        $op = $node->getOperand();
        $operand = $op->accept($this);

        // Add parentheses most of the time.
        if ($op instanceof NumberNode || $op instanceof IntegerNode || $op instanceof RationalNode) {
            if ($op->getValue() < 0) $operand = "($operand)";
        } elseif ($op instanceof VariableNode || $op instanceof ConstantNode) {
            // Do nothing
        } else {
            $operand = "($operand)";
        }

        return "$operand$functionName";
    }

    /**
     * Generate LaTeX code for a FunctionNode
     *
     * Create a string giving LaTeX code for a functionNode.
     *
     * ### Typesetting rules:
     *
     * - `sqrt(op)` is typeset as `\sqrt{op}
     * - `exp(op)` is either typeset as `e^{op}`, if `op` is a simple
     *      expression or as `\exp(op)` for more complicated operands.
     *
     * @param FunctionNode $node AST to be typeset
     * @retval string
     */

    public function visitFunctionNode(FunctionNode $node)
    {
        $functionName = $node->getName();

        $operand = $node->getOperand()->accept($this);

        switch($functionName) {
            case 'sqrt': return "\\$functionName{".$node->getOperand()->accept($this).'}';
            case 'exp':
                $operand = $node->getOperand();

                if ($operand->complexity() < 6) {
                    return 'e^'.$this->bracesNeeded($operand);
                }
                // Operand is complex, typset using \exp instead
                return '\exp('.$operand->accept($this).')';
            case 'sin':
            case 'cos':
            case 'tan':
            case 'arcsin':
            case 'arccos':
            case 'arctan':
                break;

            case 'log':
                $functionName = 'ln';
                break;

            case 'abs':
                $operand = $node->getOperand();
                return '\lvert ' . $operand->accept($this) . '\rvert ';

            case '!':
            case '!!':
                return $this->visitFactorialNode($node);

            default:
                $functionName = 'operatorname{'.$functionName.'}';
        }

        return "\\$functionName($operand)";
    }

    /**
     * Generate LaTeX code for a ConstantNode
     *
     * Create a string giving LaTeX code for a ConstantNode.
     * `pi` typesets as `\pi` and `e` simply as `e`.
     *
     * @throws UnknownConstantException for nodes representing other constants.
     * @param ConstantNode $node AST to be typeset
     * @retval string
     */
    public function visitConstantNode(ConstantNode $node)
    {
        switch($node->getName()) {
            case 'pi': return '\pi{}';
            case 'e': return 'e';
            case 'i': return 'i';
            case 'NAN': return '\operatorname{NAN}';
            case 'INF': return '\infty{}';
            default: throw new UnknownConstantException($node->getName());
        }
    }

    /**
     *  Add parentheses to the LaTeX representation of $node if needed.
     *
     *
     * @param Node $node        The AST to typeset
     * @param ExpressionNode $cutoff    A token representing the precedence of the parent
     *                          node. Operands with a lower precedence have parentheses
     *                          added.
     * @param bool $addSpace    Flag determining whether an additional space should
     *                          be added at the beginning of the returned string.
     * @retval string
     */
    public function parenthesize(Node $node, ExpressionNode $cutoff, $prepend='', $conservative=false)
    {
        $text = $node->accept($this);

        if ($node instanceof ExpressionNode) {

            // Second term is a unary minus
            if ($node->getOperator() == '-' && $node->getRight() == null) {
                return "($text)";
            }

            if ($cutoff->getOperator() == '-' && $node->lowerPrecedenceThan($cutoff)) {
                return "($text)";
            }
            if ($node->strictlyLowerPrecedenceThan($cutoff)) {
                return "($text)";
            }

            if ($conservative) {
                // Add parentheses more liberally for / and ^ operators,
                // so that e.g. x/(y*z) is printed correctly
                if ($cutoff->getOperator() == '/' && $node->lowerPrecedenceThan($cutoff)) {
                    return "($text)";
                }
                if ($cutoff->getOperator() == '^' && $node->getOperator() == '^') {
                    return '{'. $text . '}';
                }
            }

        }

        if (($node instanceof NumberNode || $node instanceof IntegerNode || $node instanceof RationalNode) && $node->getValue() < 0)
        {
            return "($text)";
        }

        return "$prepend$text";

    }

    /**
     * Add curly braces around the LaTex representation of $node if needed.
     *
     * Nodes representing a single ConstantNode, VariableNode or NumberNodes (0--9)
     * are returned as-is. Other Nodes get curly braces around their LaTeX code.
     *
     * @param Node $node    AST to parse
     * @retval string
     */
    public function bracesNeeded(Node $node)
    {
        if ($node instanceof VariableNode || $node instanceof ConstantNode) {
            return $node->accept($this);
        } elseif ($node instanceof IntegerNode && $node->getValue() >= 0 && $node->getValue() <= 9) {
            return $node->accept($this);
        } else {
            return '{'.$node->accept($this).'}';
        }
    }
}
