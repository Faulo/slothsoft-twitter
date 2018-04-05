<?php
declare(strict_types = 1);

use Slothsoft\Farah\Module\Module;
use Slothsoft\Farah\ModuleTests\AbstractModuleTest;
use Slothsoft\Farah\Module\FarahUrl\FarahUrlAuthority;

class AssetsModuleTest extends AbstractModuleTest
{
    protected static function loadModule() : Module {
        return new Module(
            FarahUrlAuthority::createFromVendorAndModule('slothsoft', 'twitter'),
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
        );
    }
}