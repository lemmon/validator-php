<?php

declare(strict_types=1);

namespace Lemmon\Validator;

/**
 * Validates that the value is an instance of a given class, interface, or enum (`instanceof`).
 *
 * Subclasses and implementations pass. Unlike {@see ObjectValidator}, which validates the
 * properties of a `stdClass` against a schema, this checks identity only and returns the
 * instance unchanged.
 */
class InstanceValidator extends FieldValidator
{
    /**
     * @var class-string
     */
    private string $className;

    /**
     * @param string $className Class, interface, or enum name; a leading backslash is ignored.
     * @throws \InvalidArgumentException When no such class, interface, or enum exists.
     */
    public function __construct(string $className)
    {
        $className = ltrim($className, '\\');
        if (!class_exists($className) && !interface_exists($className) && !enum_exists($className)) {
            throw new \InvalidArgumentException("Class, interface, or enum '{$className}' does not exist");
        }
        $this->className = $className;
    }

    /**
     * @inheritDoc
     */
    protected function getValidatorType(): string
    {
        return 'object';
    }

    /**
     * There is no meaningful conversion into an arbitrary object, so coercion only applies the
     * form-safe rule: an empty string means "no value provided".
     *
     * @inheritDoc
     */
    protected function coerceValue(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }

    /**
     * @inheritDoc
     */
    protected function validateType(mixed $value, string $key): mixed
    {
        if (!$value instanceof $this->className) {
            throw self::typeError("Value must be an instance of {$this->className}", $this->className);
        }
        return $value;
    }
}
