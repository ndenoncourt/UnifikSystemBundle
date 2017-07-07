<?php

namespace Unifik\SystemBundle\Controller\Backend\Translation;

use Symfony\Component\HttpFoundation\Response;

use Unifik\SystemBundle\Lib\Backend\BaseController;

/**
 * Navigation Controller
 */
class NavigationController extends BaseController
{

    /**
     * @param $_masterRoute
     * @return Response
     */
    public function sectionModuleBarAction($_masterRoute)
    {
        $selected = (0 === strpos($_masterRoute, 'unifik_system_backend_translation'));

        return $this->render('UnifikSystemBundle:Backend/Translation/Navigation:section_module_bar.html.twig', array(
            'selected' => $selected,
        ));
    }

    /**
     * Global Bundle Bar Action
     *
     * @param string $_masterRoute
     *
     * @return Response
     */
    public function globalModuleBarAction($_masterRoute)
    {
        // Access restricted to ROLE_BACKEND_ADMIN
        if (false === $this->get('security.authorization_checker')->isGranted('ROLE_BACKEND_ADMIN')) {
            return new Response();
        }

        $selected = (0 === strpos($_masterRoute, 'unifik_system_backend_translation'));

        return $this->render('UnifikSystemBundle:Backend/Translation/Navigation:global_bundle_bar.html.twig', array(
            'selected' => $selected
        ));
    }

}
