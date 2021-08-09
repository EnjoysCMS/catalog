added to doctrine config
```php
$evm = new EventManager();
$treeListener = new TreeListener();
$evm->addEventSubscriber($treeListener);
```
load annotation classes
```php
use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::loadAnnotationClass(Gedmo\Mapping\Annotation\TreeLevel::class);
AnnotationRegistry::loadAnnotationClass(Gedmo\Mapping\Annotation\Tree::class);
AnnotationRegistry::loadAnnotationClass(Gedmo\Mapping\Annotation\TreeClosure::class);
AnnotationRegistry::loadAnnotationClass(Gedmo\Mapping\Annotation\TreeParent::class);
```
diff migration, migrate and clear cache-metadata
```php
./vendor/bin/doctrine-migrations diff --allow-empty-diff --formatted
./vendor/bin/doctrine-migrations migrate  --no-interaction
./vendor/bin/doctrine orm:clear-cache:metadata
```
setting elfinder 