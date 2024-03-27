<?php
namespace framework\core;

use Closure;
use php\lib\reflect;
use php\lib\str;

/**
 * Class Component
 * @package framework\core
 *
 * @property string $id
 * @property Behaviour[] $behaviours
 */
abstract class Component
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Behaviour[]
     */
    public $__behaviours = [];

    /**
     * @var array
     */
    protected $eventHandlers = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var EventSignal[]
     */
    private $eventSignals = [];

    /**
     * Component constructor.
     */
    public function __construct()
    {
        $reflect = new \ReflectionClass($this);
        $props = $reflect->getProperties();

        foreach ($props as $prop) {
            if ($prop->isPublic() && !$prop->isStatic() && str::startsWith($prop->getName(), "on")) {
                if (str::contains($prop->getDocComment(), "@var EventSignal")) {
                    unset($this->{$prop->getName()});

                    $eventName = str::lowerFirst(str::sub($prop->getName(), 2));
                    $this->eventSignals[$prop->getName()] = new EventSignal($this, $eventName);
                }
            }
        }
    }


    /**
     * @return array
     */
    public function getProperties(): array
    {
        $reflect = new \ReflectionClass($this);
        $props = $reflect->getProperties();

        $result = [];

        foreach ($props as $prop) {
            if (!$prop->isStatic()) {
                $name = $prop->getName();

                if (method_exists($this, "get$name")) {
                    $result[$name] = $this->{"get$name"}();
                } else if (method_exists($this, "is$name")) {
                    $result[$name] = $this->{"is$name"}();
                }
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @param null|mixed $value
     * @return mixed
     */
    public function data(string $name, $value = null)
    {
        if (func_num_args() == 1) {
            return $this->data[$name];
        } else {
            $oldValue = $this->data[$name];
            $this->data[$name] = $value;

            return $oldValue;
        }
    }

    /**
     * @param $eventType
     * @param callable $handler
     * @param string $group
     */
    public function on(string $eventType, callable $handler, string $group = 'general')
    {
        $eventType = str::lower($eventType);
        $this->eventHandlers[$eventType][$group] = $handler;
    }

    /**
     * @param string $eventType
     * @param callable $handler
     * @return string
     */
    public function bind(string $eventType, callable $handler): string
    {
        $this->on($eventType, $handler, $group = str::uuid());
        return $group;
    }

    /**
     * @param string $eventType
     * @param null|string $group
     */
    public function off(string $eventType, ?string $group = null)
    {
        $eventType = str::lower($eventType);

        if ($group) {
            unset($this->eventHandlers[$eventType][$group]);
        } else {
            unset($this->eventHandlers[$eventType]);
        }
    }

    /**
     * @param Event $e
     * @param null|string $group
     * @internal param string $eventType
     */
    public function trigger(Event $e, ?string $group = null)
    {
        $eventType = str::lower($e->type);

        if ($group) {
            if ($handler = $this->eventHandlers[$eventType][$group]) {
                $handler($e);
            }
        } else {
            foreach ((array) $this->eventHandlers[$eventType] as $group => $handler) {
                $handler($e);

                if ($e->isConsumed()) {
                    break;
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    protected function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return Behaviour[]
     */
    protected function getBehaviours(): array
    {
        return $this->behaviours;
    }

    public function __get(string $name)
    {
        $method = "get$name";

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        $method = "is$name";
        if (method_exists($this, $method)) {
            return (bool) $this->{$method}();
        }

        if ($signal = $this->eventSignals[$name]) {
            return $signal;
        }

        throw new \Error("Property '$name' is not exists in class " . reflect::typeOf($this));
    }

    public function __set(string $name, $value)
    {
        if ($signal = $this->eventSignals[$name]) {
            $signal->set($value);
            return $value;
            //throw new \Exception("Property '$name' is readonly, to set event use ->\$name->set() method");
        }

        $method = "set$name";

        $data = [
            'property' => $name, 'value' => $value, 'oldValue' => $oldValue = $this->__get($name)
        ];

        $event = new Event("change-any", $this, null, $data);
        $this->trigger($event);

        if ($event->isConsumed()) {
            return $oldValue;
        }

        $event = new Event("change-$name", $this,null, $data);
        $this->trigger($event);

        if ($event->isConsumed()) {
            return $oldValue;
        }

        if (method_exists($this, $method)) {
            $closure = Closure::fromCallable([$this, $method]);
            return $closure($value);
        }

        throw new \Error("Property '$name' is not exists in class " . reflect::typeOf($this) . " or readonly");
    }
}