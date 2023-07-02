<?php

declare(strict_types=1);

namespace Acms\Plugins\V2\Entities;

/**
 * 位置情報
 */
class Geo
{
    /**
     * Zoom
     */
    private int $zoom;

    /**
     * 緯度
     */
    private float $longitude;

    /**
     * 経度
     */
    private float $latitude;

    public function getZoom(): int
    {
        return $this->zoom;
    }

    public function setZoom(int $zoom): void
    {
        $this->zoom = $zoom;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): void
    {
        $this->longitude = $longitude;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): void
    {
        $this->latitude = $$latitude;
    }
}
