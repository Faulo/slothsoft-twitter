<?php
declare(strict_types = 1);

use Slothsoft\Farah\Configuration\AssetConfigurationField;
use Slothsoft\Farah\ModuleTests\AbstractSitemapTest;
use Slothsoft\Farah\Module\Node\Asset\AssetInterface;

class SitemapTest extends AbstractSitemapTest
{
    protected static function loadSitesAsset() : AssetInterface {
        return (new AssetConfigurationField('farah://slothsoft@twitter/sitemap'))->getValue();
    }
}