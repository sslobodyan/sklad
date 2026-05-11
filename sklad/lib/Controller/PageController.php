<?php
namespace OCA\Sklad\Controller;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IGroupManager;
class PageController extends Controller
{
    private IURLGenerator $urlGenerator;
    private IConfig $config;
    private IUserSession $userSession;
    private IGroupManager $groupManager;
    public function __construct(
        string $appName,
        IRequest $request,
        IURLGenerator $urlGenerator,
        IConfig $config,
        IUserSession $userSession,
        IGroupManager $groupManager
    ) {
        parent::__construct($appName, $request);
        $this->urlGenerator = $urlGenerator;
        $this->config = $config;
        $this->userSession = $userSession;
        $this->groupManager = $groupManager;
    }
    /**
     * Отримати групи поточного користувача
     */
    private function getUserInfo(): array
    {
        $user = $this->userSession->getUser();
        if (!$user) {
            return ['uid' => '', 'displayName' => '', 'groups' => []];
        }
        $uid = $user->getUID();
        $displayName = $user->getDisplayName();
        $groups = $this->groupManager->getUserGroupIds($user);
        return [
            'uid' => $uid,
            'displayName' => $displayName,
            'groups' => $groups,
        ];
    }
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse
    {
        $skladUrl = $this->config->getAppValue('sklad', 'external_url', '/sklad/');
        $userInfo = $this->getUserInfo();
        return new TemplateResponse('sklad', 'index', [
            'skladUrl' => $skladUrl,
            'userInfo' => $userInfo,
        ]);
    }
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function catchAll(string $path): TemplateResponse
    {
        $skladUrl = $this->config->getAppValue('sklad', 'external_url', '/sklad/');
        $userInfo = $this->getUserInfo();
        return new TemplateResponse('sklad', 'index', [
            'skladUrl' => $skladUrl,
            'subPath' => $path,
            'userInfo' => $userInfo,
        ]);
    }
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function catchAllPost(string $path): TemplateResponse
    {
        $skladUrl = $this->config->getAppValue('sklad', 'external_url', '/sklad/');
        $userInfo = $this->getUserInfo();
        return new TemplateResponse('sklad', 'index', [
            'skladUrl' => $skladUrl,
            'subPath' => $path,
            'userInfo' => $userInfo,
        ]);
    }
}
