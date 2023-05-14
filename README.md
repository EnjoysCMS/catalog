1. Added to doctrine config in `/di/doctrine.php`

```php
use Gedmo\Tree\TreeListener;

$evm = new EventManager();
$treeListener = new TreeListener();
$evm->addEventSubscriber($treeListener);
```

2. Diff migration, migrate and clear cache-metadata

```shell
composer diff
composer migrate
```

3. Migration function

```shell
./vendor/bin/doctrine-migrations  migrations:generate
```

```php
//for Mysql insert to migration

$this->addSql(<<<SQL
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
SQL);
```

```shell
composer migrate
```

4. Setting elfinder 


# Options
