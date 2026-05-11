<?php
namespace OCA\Sklad\AppInfo;
use OCP\AppFramework\App;
class Application extends App
{
    public const APP_ID = 'sklad';
    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }
}
