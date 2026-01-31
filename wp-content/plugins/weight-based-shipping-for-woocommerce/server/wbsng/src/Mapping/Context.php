<?php /** @noinspection PhpPropertyOnlyWrittenInspection */
declare(strict_types=1);

namespace Aikinomi\Wbsng\Mapping;

use Aikinomi\Wbsng\Mapping\Exceptions\InvalidType;
use Throwable;
use Traversable;


/**
 * @implements \IteratorAggregate<array-key, self>
 */
class Context implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @param mixed $value
     * @param ?callable(self, Throwable): Throwable $mapError
     */
    public static function of($value, ?callable $mapError = null, ?bool $convert = null): self
    {
        return new self($value, self::$implicitParent, '', $mapError, $convert);
    }

    /**
     * @param mixed $value
     * @param ?callable(self, Throwable): Throwable $mapError
     */
    private function __construct($value, ?self $parent, string $subpath, ?callable $mapError = null, ?bool $convert = null)
    {
        $this->parent = $parent;
        $this->value = $value;
        $this->subpath = $subpath;
        
        $this->mapError = $mapError;

        $this->convert = $convert ?? ($parent ? $parent->convert : false);

        self::$rc++;
    }

    public function __destruct()
    {
        self::$rc--;
        if (self::$rc === 0) {
            self::$origins = null;
        }
    }

    /**
     * @template T
     * @param callable(): T $f
     * @param array<mixed> $args Arguments for $f
     * @return T
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function map(callable $f, ...$args)
    {
        $p = self::$implicitParent;
        self::$implicitParent = $this;

        try {
            /** @noinspection PhpUnhandledExceptionInspection */
            return $this->doRun($f, ...$args);
        }
        finally {
            self::$implicitParent = $p;
        }
    }

    public function origin(Throwable $e): self
    {
        return self::$origins[$e] ?? $this;
    }

    public function path(): string
    {
        $p = $this->parent ? $this->parent->path() : '';

        if ($p !== '' && $this->subpath !== '') {
            $p .= '.';
        }

        $p .= $this->subpath;

        return $p;
    }

    public function convert(): bool
    {
        return $this->convert;
    }

    /**
     * @throws InvalidType
     */
    public function offsetGet($offset): self
    {
        $this->requireArray();
        return new self($this->value[$offset] ?? null, $this, (string)$offset);
    }

    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->value);
    }

    public function offsetSet($offset, $value): void
    {
        throw new \LogicException('unserialize data is immutable');
    }

    public function offsetUnset($offset): void
    {
        throw new \LogicException('unserialize data is immutable');
    }

    public function getIterator(): Traversable
    {
        if (!isset($this->value)) {
            return;
        }

        $this->requireArray();

        foreach ($this->value as $k => $_) {
            yield $k => $this[$k];
        }
    }


    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $subpath;

    /**
     * @var ?self
     */
    private $parent;

    /**
     * @var ?callable(self, Throwable): Throwable
     */
    private $mapError;

    /**
     * @var bool
     */
    private $convert;


    private function doRun(callable $f, ...$args)
    {
        try {
            return $f($this->value, ...$args);
        }
        catch (Throwable $e) {

            $this->register($e);

            if (isset($this->mapError)) {
                $pe = $e;
                $e = ($this->mapError)($this, $e);
                if ($e !== $pe) {
                    $this->register($e);
                }
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
        }
    }

    private function register(Throwable $e): void
    {
        if (isset(self::$origins[$e])) {
            return;
        }
        if (!isset(self::$origins)) {
            self::$origins = new \SplObjectStorage();
        }
        self::$origins[$e] = $this;
    }

    /**
     * @throws InvalidType
     */
    private function requireArray(): void
    {
        if (isset($this->value) && !is_array($this->value)) {
            $e = new InvalidType('array', gettype($this->value));
            $this->register($e);
            throw $e;
        }
    }

    /**
     * @var ?self
     */
    private static $implicitParent;

    /**
     * @var ?\SplObjectStorage<Throwable, self>
     */
    private static $origins;

    /**
     * @var int
     */
    private static $rc = 0;
}