1. added to doctrine config in `/config/doctrine.php`
```php
use Gedmo\Tree\TreeListener;

$evm = new EventManager();
$treeListener = new TreeListener();
$evm->addEventSubscriber($treeListener);
```

2. load annotation classes in `/bootstrap.php` after `$loader = require_once __DIR__ . "/vendor/autoload.php";`
```php
use Doctrine\Common\Annotations\AnnotationRegistry;
use Gedmo\Mapping\Annotation\Timestampable;
use Gedmo\Mapping\Annotation\Tree;
use Gedmo\Mapping\Annotation\TreeClosure;
use Gedmo\Mapping\Annotation\TreeLevel;
use Gedmo\Mapping\Annotation\TreeParent;

AnnotationRegistry::loadAnnotationClass(TreeLevel::class);
AnnotationRegistry::loadAnnotationClass(Tree::class);
AnnotationRegistry::loadAnnotationClass(TreeClosure::class);
AnnotationRegistry::loadAnnotationClass(TreeParent::class);
AnnotationRegistry::loadAnnotationClass(Timestampable::class);
```
3. diff migration, migrate and clear cache-metadata
```
composer diff
composer migrate
```
or
```
./vendor/bin/doctrine-migrations diff --allow-empty-diff --formatted
./vendor/bin/doctrine-migrations migrate  --no-interaction
./vendor/bin/doctrine orm:clear-cache:metadata
```
4. setting elfinder 
