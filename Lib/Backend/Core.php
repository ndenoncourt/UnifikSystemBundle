<?php

namespace Unifik\SystemBundle\Lib\Backend;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\HttpFoundation\RequestStack;
use Unifik\SystemBundle\Entity\AppRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

use Unifik\SystemBundle\Entity\App;
use Unifik\SystemBundle\Entity\Section;
use Unifik\SystemBundle\Lib\ApplicationCoreInterface;
use Unifik\SystemBundle\Lib\Breadcrumbs;
use Unifik\SystemBundle\Lib\NavigationElementInterface;
use Unifik\SystemBundle\Lib\PageTitle;

/**
 * Backend Core Class
 *
 * @throws \Exception
 */
class Core implements ApplicationCoreInterface
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var String
     */
    private $locale;

    /**
     * @var Breadcrumbs
     */
    private $breadcrumbs;

    /**
     * @var PageTitle
     */
    private $pageTitle;

    /**
     * The current app entity
     * @var App
     */
    private $app;

    /**
     * The current section entity
     * @var Section
     */
    private $section;

    /**
     * The current element (can be any entity implementing NavigationElementInterface)
     * @var object
     */
    private $element;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var bool
     */
    protected $initialized;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initialized = false;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack()
    {
        return $this->requestStack;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;

        if ($requestStack->getCurrentRequest()) {
            $this->request = $requestStack->getCurrentRequest();
        }
    }

    /**
     * Init
     */
    public function init()
    {
        if (!$this->initialized) {
            if ($section = $this->getSection()) {

                foreach ($section->getParents() as $parent) {

                    $this->addNavigationElement($parent);
                }
                $this->addNavigationElement($section);
            }

            $this->initialized = true;
        }
    }

    /**
     * Add an element to the Breadcrumbs and the Page Title
     *
     * @param NavigationElementInterface $element The element to push in the navigation stack
     *
     * @throws \Exception
     */
    public function addNavigationElement(NavigationElementInterface $element)
    {
        $this->breadcrumbs->addElement($element);
        $this->pageTitle->addElement($element);

        $this->element = $element;
    }

    /**
     * Get the current Section ID
     *
     * @return integer
     */
    public function getSectionId()
    {
        $unifikRequest = $this->request->attributes->get('_unifikRequest');

        return $unifikRequest['sectionId'] ?: 0;
    }

    /**
     * Get the current Section
     *
     * @return Section
     */
    public function getSection()
    {
        if ($this->section) {
            return $this->section;
        }

        $em = $this->doctrine->getManager();

        $this->section = $em->getRepository('UnifikSystemBundle:Section')->find($this->getSectionId());

        return $this->section;
    }

    /**
     * Get the App
     *
     * @throws \Exception
     *
     * @return App The App entity
     */
    public function getApp()
    {
        if ($this->app) {
            return $this->app;
        }

        $appRepo = $this->doctrine->getManager()->getRepository('UnifikSystemBundle:App');
        if ($this->request->get('appSlug')) {
            $this->app = $appRepo->findOneBy(array('slug' => $this->request->get('appSlug')));
            $method = 'route parameter {appSlug}';
        } elseif ($this->getSection()) {
            $this->app = $this->getSection()->getApp();
            $method = 'the section entity';
        } else {
            $this->app = $appRepo->findFirstOneExcept(AppRepository::BACKEND_APP_ID);
            $method = 'first app entity in table (empty table?)';
        }

        if (false == $this->app) {
            throw new \Exception('the App entity could not be loaded. Method used: ' . $method);
        }

        return $this->app;
    }

    /**
     * Return the element
     *
     * @return object
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Set the Doctrine Registry
     *
     * @param Registry $doctrine The Doctrine Registry
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Set the Breadcrumbs
     *
     * @param Breadcrumbs $breadcrumbs
     */
    public function setBreadcrumbs($breadcrumbs)
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    /**
     * Set the Page Title
     *
     * @param PageTitle $pageTitle
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * Set the session object
     *
     * @param Session $session The Session
     *
     * @return void
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * Get the session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the locale
     *
     * @param string $locale The locale
     *
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get the locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * getEditLocale
     *
     * Returns the edit locale, only the forms use this locale, the system locale is used everywhere else.
     *
     * @return string
     */
    public function getEditLocale()
    {
        if ($this->request->get('edit-locale')) {
            $this->getSession()->set('edit-locale', $this->request->get('edit-locale'));
        }

        if (!$this->getSession()->get('edit-locale')) {
            $this->getSession()->set('edit-locale', $this->getLocale());
        }

        return $this->getSession()->get('edit-locale');
    }

    public function getAppName()
    {
        return 'backend';
    }

}
