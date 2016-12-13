# Defaults

If you need dynamic values based on other values, you SHOULD implement a hook method as illustrated here.  This function will fire when we try to get the value of `$this->hasFavorites`.

    protected function get__hasFavorites($value, $default, $exists)
    {
        return boolval($exists ? $value : !empty($this->items));
    }
    
Notice the method receives three arguments:

1. The current value
1. The value as set up in `defaults()`.
1. If the default has been overridden (a value has been assigned).
