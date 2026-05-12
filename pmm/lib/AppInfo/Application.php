<?php
namespace OCA\Pmm\AppInfo;
use OCP\AppFramework\App;
class Application extends App
{
    public const APP_ID = 'pmm';
    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }
}
