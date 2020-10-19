# nomess-orm


nomess / orm is a very powerful orm requiring little configuration.

It supports Mysql and PostgreSql.

### Methods:

> find($classname : string, $idOrSql : int|string|null, $parameters = [] : array): object|array|null

```php
  use Nomess\Component\Orm\EntityManagerInterface;
  
  $entityManager = new EntityManager(...)
  
  // On select all
  $samples = $entityManager->find(Sample::class); 
  
  // Select one
  $sample = $entityManager->find(Sample::class, 1);
  
  // Select with Sql
  $samples = $entityManager->find(Sample::class, 'username = :username', [
    'username' => 'nomess'
  ]);
  
  
```
