<?php declare(strict_types=1);

namespace Aikinomi\Wbsng\Model\Config;

use Aikinomi\Wbsng\Mapping\Context;
use function Aikinomi\Wbsng\Common\any;


class Document
{
    use DocumentMapping;


    /**
     * @var list<Method>
     */
    public $methods;

    /**
     * @var Document\Settings
     */
    public $settings;

    /**
     * @param list<Method>|null $methods
     */
    public function __construct(?array $methods = null, ?Document\Settings $settings = null)
    {
        $this->methods = $methods ?? [];
        $this->settings = $settings ?? new Document\Settings();
    }

    public function active(): bool
    {
        return any($this->methods, function(Method $m) {
            return $m->active();
        });
    }
}


trait DocumentMapping
{
    public static function unserialize(array $data): self
    {
        $data = Context::of($data);

        $methods = [];
        foreach ($data['methods'] as $method) {
            $methods[] = $method->map([Method::class, 'unserialize']);
        }

        $settings = $data['settings']->map([Document\Settings::class, 'unserialize']);

        return new self($methods, $settings);
    }
}