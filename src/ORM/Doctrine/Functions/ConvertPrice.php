<?php

declare(strict_types=1);


namespace EnjoysCMS\Module\Catalog\ORM\Doctrine\Functions;


use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

class ConvertPrice extends FunctionNode
{
    public $price = null;
    public $currencyMain = null;
    public $convertCurrency = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER); // (2)
        $parser->match(Lexer::T_OPEN_PARENTHESIS); // (3)
        $this->price = $parser->ArithmeticPrimary(); // (4)
        $parser->match(Lexer::T_COMMA); // (5)
        $this->currencyMain = $parser->ArithmeticPrimary(); // (6)
        $parser->match(Lexer::T_COMMA); // (5)
        $this->convertCurrency = $parser->ArithmeticPrimary(); // (6)
        $parser->match(Lexer::T_CLOSE_PARENTHESIS); // (3)
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'CONVERT_PRICE(' .
            $this->price->dispatch($sqlWalker) . ', ' .
            $this->currencyMain->dispatch($sqlWalker) . ', ' .
            $this->convertCurrency->dispatch($sqlWalker) .
            ')';
    }
}

/*

CREATE FUNCTION `arowana-dev`.CONVERT_PRICE(
    `price` INT,
	`main_currency` VARCHAR(3),
	`convert_currency` VARCHAR(3)
) RETURNS double
    DETERMINISTIC
BEGIN
	IF (main_currency = convert_currency)
	THEN
		SET @result = price;
	ELSE
		SET @rate = (SELECT rate FROM catalog_currency_rate WHERE
			main = main_currency
			AND `convert` = convert_currency
		);
		SET @result = price * @rate;
	END IF;
RETURN @result;

END;



 */
