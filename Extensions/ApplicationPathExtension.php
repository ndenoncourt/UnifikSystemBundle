<?php

namespace Unifik\SystemBundle\Extensions;

use Unifik\SystemBundle\Entity\MappingRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Used in the frontend to get application related information
 */
class ApplicationPathExtension extends \Twig_Extension {

    /**
     * @var doctrine
     */
    protected $doctrine;

    /**
     * @var Core
     */
    protected $systemCore;

    /**
     * @var MappingRepository
     */
    protected $mappingRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var RouterAutoParametersHandler
     */
    private $autoParametersHandler;


    public function getAppRoute($route_name, $app_id = null) {
        if (!$this->mappingRepository)
            $this->mappingRepository = $this->doctrine->getManager()->getRepository('UnifikSystemBundle:Mapping');
        $mapping = $this->mappingRepository->findOneBy(array(
            'app'=>($app_id ? $app_id: $this->systemCore->getApplicationCore()->getApp()->getId()),
            'type'=>'route',
            'target'=>$route_name,
        ), array('section'=>'ASC'));

        if ($mapping) {
            // Faut checker toutes les routes, à cause du mapping alias
            $routes = $this->router->getRouteCollection()->all();
            foreach ($routes as $name => $route) {
                if ($defaults = $route->getDefaults()) {
                    if (array_key_exists('_unifikRequest', $defaults) && array_key_exists('mappedRouteName', $defaults['_unifikRequest'])) {
                        $real_name = preg_replace('/^[aA-zZ]{2}__[A-Z]{2}__/', '', $defaults['_unifikRequest']['mappedRouteName']);

                        // On a trouvé la bonne route
                        if ($real_name == $route_name)
                            return preg_replace('/^[aA-zZ]{2}__[A-Z]{2}__/', '', $name);
                    }
                }
            }

            // On a rien trouvé... on retourne la route "par défaut" de la section
            return 'section_id_'.$mapping->getSection()->getId();
        }

        // Aucun mapping... on fallback sur la premiere route disponible (?)
        $mapping = $this->mappingRepository->findOneBy(array(
            'type'=>'route',
            'target'=>$route_name,
        ), array('section'=>'ASC'));

        if ($mapping)
            return 'section_id_'.$mapping->getSection()->getId();

        // Rien de concluant
        return null;
    }

    public function getAppPath($route_name, $parameters = array(), $app_id = null, $relative = false) {
        $route = $this->getAppRoute($route_name, $app_id);
        $parameters = $this->autoParametersHandler->inject($parameters);
        return $this->router->generate($route, $parameters, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    public function getAppUrl($route_name, $parameters = array(), $app_id = null, $relative = false) {
        $route = $this->getAppRoute($route_name, $app_id);
        $parameters = $this->autoParametersHandler->inject($parameters);
        return $this->router->generate($route, $parameters, $relative ? UrlGeneratorInterface::NETWORK_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Get Functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('app_route', [$this, 'getAppRoute']),
            new \Twig_SimpleFunction('app_path', [$this, 'getAppPath']),
            new \Twig_SimpleFunction('app_url', [$this, 'getAppUrl']),
        );
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'application_path_extension';
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
     * @param mixed $systemCore
     */
    public function setSystemCore($systemCore)
    {
        $this->systemCore = $systemCore;
    }

    /**
     * @param mixed $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @param RouterAutoParametersHandler $autoParametersHandler
     */
    public function setAutoParametersHandler($autoParametersHandler)
    {
        $this->autoParametersHandler = $autoParametersHandler;
    }
}
