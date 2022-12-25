<?php

/**
 * Handy trait provides methods to handle dynamic properties.
 */
trait WPCF7_PocketHolder {
    protected array $pocket = [];

    public function pull($key) {
        if (isset($this->pocket[$key])) {
            return $this->pocket[$key];
        }
    }

    public function push($key, $value): void {
        $this->pocket[$key] = $value;
    }
}
