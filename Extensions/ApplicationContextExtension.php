<?php

namespace Unifik\SystemBundle\Extensions;

use Symfony\Component\HttpFoundation\Request;

/**
 * Used in the frontend to get application related information
 */
class ApplicationContextExtension extends \Twig_Extension {

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser
     */
    protected $controllerNameConverter;

    /**
     * Set Request
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Set Controller Name Converter
     *
     * @param \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser $controllerNameConverter
     */
    public function setControllerNameConverter($controllerNameConverter)
    {
        $this->controllerNameConverter = $controllerNameConverter;
    }

    /**
     * Get Functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('get_bundle_name', [$this, 'getBundleName']),
            new \Twig_SimpleFunction('get_controller_name', [$this, 'getControllerName']),
            new \Twig_SimpleFunction('get_action_name', [$this, 'getActionName']),
        );
    }

    /**
     * Get current bundle name
     *
     * @return string|null
     */
    public function getBundleName()
    {
        try {
            $controller = $this->controllerNameConverter->parse($this->request->get('_controller'));
        } catch (\InvalidArgumentException $e) {
            $controller = $this->request->get('_controller');
        }

        $pattern = "#\\\([a-zA-Z]*)Bundle#";
        $matches = array();

        if (preg_match($pattern, $controller, $matches)) {
            return strtolower($matches[1]);
        }
    }

    /**
     * Get current controller name
     *
     * @return string|null
     */
    public function getControllerName()
    {
        try {
            $controller = $this->controllerNameConverter->parse($this->request->get('_controller'));
        } catch (\InvalidArgumentException $e) {
            $controller = $this->request->get('_controller');
        }

        $pattern = "#(.*)\\\([a-zA-Z]*)Controller(.*)#";
        $matches = array();

        if (preg_match($pattern, $controller, $matches)) {
            return strtolower($matches[2]);
        }
    }

    /**
     * Get current action name
     *
     * @return string|null
     */
    public function getActionName()
    {
        try {
            $controller = $this->controllerNameConverter->parse($this->request->get('_controller'));
        } catch (\InvalidArgumentException $e) {
            $controller = $this->request->get('_controller');
        }

        $pattern = "#::([a-zA-Z]*)Action#";
        $matches = array();

        if (preg_match($pattern, $controller, $matches)) {
            return $matches[1];
        }
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return 'application_context_extension';
    }
}
