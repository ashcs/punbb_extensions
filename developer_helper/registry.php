<?php

class Registry extends ArrayObject
{
    /**
     * Registry object provides storage for shared objects.
     * @var Registry
     */
    private static $_registry = null;

    /**
     * Retrieves the registry instance.
     *
     * @return Registry
     */
    public static function get_instance()
    {
        if (self::$_registry === null) {
            self::$_registry = new self;
        }

        return self::$_registry;
    }

    public static function unset_instance()
    {
        self::$_registry = null;
    }

    public static function get($index)
    {
        $instance = self::get_instance();

        if (!$instance->exists_offset($index)) {
            throw new Exception("No entry is registered for key '$index'");
        }

        return $instance->offsetGet($index);
    }

    public static function set($index, $value)
    {
        self::get_instance()->offsetSet($index, $value);
    }

    public static function is_registered($index)
    {
        if (self::$_registry === null) {
            return false;
        }
        return self::$_registry->exists_offset($index);
    }

    /**
     * Constructs a parent ArrayObject with default
     * ARRAY_AS_PROPS to allow acces as an object
     *
     * @param array $array data array
     * @param integer $flags ArrayObject flags
     */
    public function __construct($array = array(), $flags = parent::ARRAY_AS_PROPS)
    {
        parent::__construct($array, $flags);
    }

    /**
     * @param string $index
     * @returns mixed
     *
     * Workaround for http://bugs.php.net/bug.php?id=40442 (ZF-960).
     */
    public function exists_offset($index)
    {
        return array_key_exists($index, $this);
    }

}