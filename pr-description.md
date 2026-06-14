This adds automatic validation of `#[MapInput]` DTOs using Validator constraints, the same way `#[MapRequestPayload]` works in HttpKernel.

When the Validator component is available, constraints on DTO properties are automatically enforced after the input is resolved. When it's not installed, validation is silently skipped.

```php
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

class CreateUserInput
{
    #[Argument]
    #[Assert\NotBlank]
    public string $name;

    #[Option]
    #[Assert\Email]
    public ?string $email = null;
}

#[AsCommand('app:create-user')]
class CreateUserCommand
{
    public function __invoke(
        #[MapInput]
        CreateUserInput $input,
    ): int {
        // $input is guaranteed to be valid here
    }
}
```

Validation groups can be controlled via the `validationGroups` parameter:

```php
public function __invoke(
    #[MapInput(validationGroups: ['registration'])]
    CreateUserInput $input,
): int {
```

On validation failure, a `ValidationFailedException` is thrown with the list of constraint violations.
