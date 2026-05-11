<?php
namespace OCA\Sklad\Controller;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;
class SettingsController extends Controller
{
    private IConfig $config;
    public function __construct(string $appName, IRequest $request, IConfig $config)
    {
        parent::__construct($appName, $request);
        $this->config = $config;
    }
    /**
     * @param string $external_url
     * @return JSONResponse
     */
    public function save(string $external_url = '/sklad/'): JSONResponse
    {
        $this->config->setAppValue('sklad', 'external_url', $external_url);
        return new JSONResponse(['status' => 'ok', 'url' => $external_url]);
    }
}