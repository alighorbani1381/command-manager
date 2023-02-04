<?php

namespace Alighorbani\CommandManager;

final class MaintenanceMode
{
    public static function turn($mode)
    {
        $maintenanceModeOn = config('maintenance-mode.' . $mode);

        if (is_callable($maintenanceModeOn)) {
            return $maintenanceModeOn();
        }
    }

    public static function on()
    {
        return self::turn('on');
    }

    public static function off()
    {
        return self::turn('off');
    }
}
