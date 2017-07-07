<?php

namespace Unifik\SystemBundle\Listener;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unifik\SystemBundle\Entity\User;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpFoundation\Response;
use Unifik\SystemBundle\Entity\AppRepository;
use Unifik\SystemBundle\Lib\RouterAutoParametersHandler;
use JMS\I18nRoutingBundle\Router\I18nRouter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginBackendListener
{
    /** @var AuthorizationCheckerInterface*/
    private $authChecker;

    /** @var \Doctrine\Bundle\DoctrineBundle\Registry */
    private $doctrine;

    /** @var \JMS\I18nRoutingBundle\Router\I18nRouter */
    private $router;

    public function __construct(AuthorizationCheckerInterface $authChecker, Doctrine $doctrine, I18nRouter $router)
    {
        $this->authChecker = $authChecker;
        $this->doctrine = $doctrine;
        $this->router = $router;
    }

    /**
     * Forcing the request locale if the user entity uses a custom locale that is set on the entity
     *
     * @param InteractiveLoginEvent $event
     */
    public function onLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        // Skipping login that is not coming from the backend User entity
        if (false == $user instanceof User) {
            return;
        }

        if ($locale = $user->getLocale()) {
            $event->getRequest()->setLocale($locale);
        }

        // Redirection on the first application to which the User has access
        $appRepo = $this->doctrine->getRepository('UnifikSystemBundle:App');
        $apps = $appRepo->findAllHasAccess($this->authChecker, $user->getId());
        if ($apps && !in_array($apps[0]->getId(), array(AppRepository::FRONTEND_APP_ID, AppRepository::BACKEND_APP_ID))) {
            $event->getRequest()->request->set('_target_path',
                $this->router->generate('unifik_system_backend_switch_managed_app',
                    array('appSlug' => $apps[0]->getSlug()),
                    UrlGeneratorInterface::ABSOLUTE_URL)
                );
        }
    }
}
