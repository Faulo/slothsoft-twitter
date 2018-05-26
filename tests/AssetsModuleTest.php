<?php
declare(strict_types = 1);

use Slothsoft\Farah\FarahUrl\FarahUrlAuthority;
use Slothsoft\Farah\ModuleTests\AbstractManifestTest;

class AssetsModuleTest extends AbstractManifestTest
{
    protected static function getManifestAuthority() : FarahUrlAuthority {
        return FarahUrlAuthority::createFromVendorAndModule('slothsoft', 'twitter');
    }
}