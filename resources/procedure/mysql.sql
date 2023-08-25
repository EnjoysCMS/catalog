CREATE FUNCTION CONVERT_PRICE(
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
