<?php

namespace Acms\Plugins\V2\Services\Unit;

use Acms\Services\Common\Factory;

class UnitFactory extends Factory
{
    /**
     * Factory
     *
     * @param string $namespace
     *
     * @return UnitInterface
     * @throws \RuntimeException
     */
    public function get(string $namespace): UnitInterface
    {
        if (!array_key_exists($namespace, $this->_collection)) {
            throw new \RuntimeException('Not found unit.');
        }

        return new $this->_collection[$namespace]();
    }
}
