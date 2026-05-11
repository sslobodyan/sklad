<?php
namespace OCA\Sklad\Settings;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;
class AdminSettings implements ISettings
{
    private IConfig $config;
    public function __construct(IConfig $config)
    {
        $this->config = $config;
    }
    public function getForm(): TemplateResponse
    {
        return new TemplateResponse('sklad', 'admin', [
            'external_url' => $this->config->getAppValue('sklad', 'external_url', '/sklad/'),
        ]);
    }
    public function getSection(): string
    {
        return 'additional';
    }
    public function getPriority(): int
    {
        return 50;
    }
}
