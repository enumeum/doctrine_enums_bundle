# Doctrine Enums Bundle
Symfony bundle for [Doctrine enums extension](https://github.com/enumeum/doctrine_enums).


## Requirements
Minimum PHP version is 8.1.


## Installation
    composer require enumeum/doctrine-enums-bundle

## Bundle registration
If Symfony did not add bundle into ./config/bundles.php then do it manually.

```php
<?php

return [
    // ...
    Enumeum\DoctrineEnumBundle\DoctrineEnumBundle::class => ['all' => true],
];
```

## Setup
Create doctrine_enum.yaml file under Symfony config folder.
For single **default** connection setup is pretty easy
```yaml
doctrine_enum:
  types:
    - App\EnumType\StatusType
    - App\EnumType\AnotherStatusType
  paths:
    - dir: '%kernel.project_dir%/src/EnumFolder/One'
      namespace: App\EnumFolder\One
    - dir: '%kernel.project_dir%/src/EnumFolder/Two'
      namespace: App\EnumFolder\Two
```

For multiple **named** connections config also supports them. Connections naming should be similar to Doctrine.
```yaml
doctrine_enum:
  connections:
    first:
      types:
        - App\EnumType\AnotherStatusType
      paths:
        - dir: '%kernel.project_dir%/src/EnumFolder/One'
          namespace: App\EnumFolder\One
    second:
      types:
        - App\EnumType\StatusType
      paths:
        - dir: '%kernel.project_dir%/src/EnumFolder/Two'
          namespace: App\EnumFolder\Two
```

## Types and Entities
### Enumeum attribute for PHP enum:
- **#[Enumeum\DoctrineEnum\Attribute\EnumType(name: 'type_name')]** this attribute tells that this enum is database type.
  By default, it creates type in database with its own cases.

### Enum setup

```php
<?php
namespace App\Enums;

use Enumeum\DoctrineEnum\Attribute\EnumType;

#[EnumType(name: 'status_type')]
enum StatusType: string
{
    case STARTED = 'started';
    case PROCESSING = 'processing';
    case FINISHED = 'finished';
}
```

### Entity setup
Please note that the configuration of the entity is no different from the usual one. Doctrine supports "enumType" property and converts it transparently.
```php
<?php
namespace App\Entities;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\StatusType;

/**
 * @ORM\Entity
 * @ORM\Table(name="entity")
 */
#[ORM\Entity]
#[ORM\Table(name: 'entity')]
class Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private int $id;

    /**
     * @ORM\Column(type="string", enumType=StatusType::class, options={"comment":"Comment"})
     */
    #[ORM\Column(type: Types::STRING, enumType: StatusType::class, options: ['comment' => 'Comment'])]
    private StatusType $status;

    public function __construct(
        int $id,
        StatusType $status,
    ) {
        $this->id = $id;
        $this->status = $status;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): StatusType
    {
        return $this->status;
    }
}
```

### Commands
In order to automate enums management bundle provides some commands.
#### Diff
Diff command will create migration file using Doctrine Migrations bundle config.

    ./bin/console enumeum:migrations:diff

List of options is generally similar to Doctrine's **doctrine:migrations:diff** command, except one additional option.
"-U" allows to ignore enums already existing in database, otherwise diff command will try to drop them.
#### Schema validate
Schema validate command will check whether database enums are in sync with their definitions in application.

    ./bin/console enumeum:schema:validate

This command has optional "--em" and "--conn" options for those cases when application has different from "default" configuration.  
Option "-U" allows to ignore enums already existing in database.

### Integration with Doctrine's commands
Bundle also allows to use Enumeum commands with appropriate Doctrine's commands to perform better usability.

    enumeum:migrations:diff -> doctrine:migrations:diff
    enumeum:schema:validate -> doctrine:schema:validate

This is provided by Doctrine's commands overriding using commands decoration. Every Doctrine's command has additional option "-E" which allows to run Enumeum command before the general. 
Commands decoration is enabled by default and can be disabled by following configuration:
```yaml
doctrine_enum:
  doctrine:
    commands_decoration: false
```
