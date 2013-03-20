<?php

namespace Egzakt\SystemBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\ExecutionContext;

use Egzakt\SystemBundle\Lib\BaseEntity;

/**
 * Section
 */
class Section extends BaseEntity
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var ArrayCollection
     */
    protected $children;

    /**
     * @var Section
     */
    protected $parent;

    /**
     * @var string
     */
    protected $app;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var integer
     */
    protected $ordering;

    /**
     * @var array
     */
    protected $sectionBundles;

    /**
     * @var array
     */
    protected $routeParams;

    /**
     * @var array
     */
    protected $sectionNavigations;

    /**
     * @var array
     */
    protected $texts;

    /**
     * @var SectionTranslation
     */
    protected $translations;

    /**
     * @var ArrayCollection
     */
    protected $nonAutomaticallyLinkedBundles;

    /**
     * Construct
     *
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->sectionNavigations = new ArrayCollection();
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add children
     *
     * @param Section $children The Children to add
     */
    public function addChildren(Section $children)
    {
        $this->children[] = $children;
    }

    /**
     * Get children
     *
     * @return ArrayCollection
     */
    public function getChildren()
    {
        if ($this->hasChildren()) {

            // Temporary fix until we rewrite the navigation code
            if ($this->getSystemCore()->getCurrentAppName() != 'backend' && method_exists($this->children[0], 'getOrdering')) {

                $orderedChildren = array();
                foreach ($this->children as $child) {
                    $orderedChildren[$child->getOrdering()] = $child;
                }
                ksort($orderedChildren);

                return $orderedChildren;
            }

            return $this->children;
        }

        return array();
    }

    /**
     * Has children
     *
     * @return Boolean
     */
    public function hasChildren()
    {
        return (count($this->children));
    }

    /**
     * Set children
     *
     * @param array $children The children array to set
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Set parent
     *
     * @param Section $parent The Parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent
     *
     * @return Section
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get parents
     *
     * @return array
     */
    public function getParents()
    {
        $parents = array();
        $tempParents = array();
        $parent = $this->getParent();
        $level = 1;

        while ($parent && $parent->getId()) {
            $tempParents[] = $parent;
            $parent = $parent->getParent();
        }

        $tempParents = array_reverse($tempParents);
        foreach ($tempParents as $parent) {
            $parents[$level] = $parent;
            $level++;
        }

        return $parents;
    }

    /**
     * Get parents slugs
     *
     * @return array
     */
    public function getParentsSlugs()
    {
        $slugs = array();

        /** @var $parent Section */
        foreach ($this->getParents() as $parent) {
            $slugs[] = $parent->getSlug();
        }

        return $slugs;
    }

    /**
     * Set app
     *
     * @param string $app The App
     */
    public function setApp($app)
    {
        $this->app = $app;
    }

    /**
     * Get app
     *
     * @return string
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Add app
     *
     * @param App $app
     */
    public function addApp(App $app)
    {
        $this->app[] = $app;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * List of methods to check before allowing deletion
     *
     * @return array
     */
    public function getDeleteRestrictions()
    {
        return array('getChildren', 'getNonAutomaticallyLinkedBundles');
    }

    /**
     * Get bundles related to the section which are not automatically linked
     *
     * @return ArrayCollection
     */
    public function getNonAutomaticallyLinkedBundles()
    {
        if (!$this->nonAutomaticallyLinkedBundles) {
            $this->nonAutomaticallyLinkedBundles = new ArrayCollection();

            foreach ($this->sectionBundles as $sectionBundle) {
                if (!$sectionBundle->getBundle()->getParam('automatically_linked')) {
                    $this->nonAutomaticallyLinkedBundles[] = $sectionBundle->getBundle();
                }
            }
        }

        return $this->nonAutomaticallyLinkedBundles;
    }

    /**
     * getLevel
     *
     * @return integer
     */
    public function getLevel()
    {
        return count($this->getParents()) + 1;
    }


    /**
     * Get route
     *
     * @return string
     */
    public function getRouteFrontend()
    {
        return 'section_id_' . $this->getId();
    }

    /**
     * Get Frontend route params
     *
     * @param array $params Array of params to get
     *
     * @return array
     */
    public function getRouteFrontendParams($params = array())
    {
        return $params;
    }

    /**
     * Get route
     *
     * @return string
     */
    public function getRouteBackend()
    {
        if ($this->route) {
            return $this->route;
        }

        // The default navigation route is actually the first associated bundle in the section
        $sectionBundles = $this->getSectionBundlesBackend();

        if (count($sectionBundles)) {
            return $sectionBundles[0]->getRoute();
        }

        // No bundle associated, looking for childs with associated bundle
        /** @var $children \Egzakt\Backend\SectionBundle\Entity\Section */
        foreach ($this->getChildren() as $children) {

            if ($route = $children->getRoute()) {
                return $route;
            }
        }

        // No route could be found
        return false;
    }

    /**
     * Get Backend route params
     *
     * @return bool|array
     */
    public function getRouteBackendParams()
    {
        if ($this->routeParams) {
            return $this->routeParams;
        }

        // The defaults navigation params are from the first associated bundle
        $sectionBundles = $this->getSectionBundlesBackend();

        if (count($sectionBundles)) {
            return $sectionBundles[0]->getRouteParams();
        }

        // No bundle associated, looking for childs with associated bundle
        /** @var $children \Egzakt\Backend\SectionBundle\Entity\Section */
        foreach ($this->getChildren() as $children) {

            if ($children->getRoute()) {
                return $children->getRouteParams();
            }
        }

        // No params could be found
        return false;
    }

    /**
     * Add sectionBundles
     *
     * @param SectionBundle $sectionBundle The SectionBundle *entity*
     */
    public function addSectionBundles(SectionBundle $sectionBundle)
    {
        $this->sectionBundles[] = $sectionBundle;
    }

    /**
     * Get sectionBundles
     *
     * @return ArrayCollection
     */
    public function getSectionBundles()
    {
        return $this->sectionBundles;
    }

    /**
     * Set sectionBundles
     *
     * @param array $sectionBundles An array of sectionBundles
     */
    public function setSectionBundles($sectionBundles)
    {
        $this->sectionBundles = $sectionBundles;
    }

    /**
     * Get Section Bundles Backend
     *
     * @return array
     */
    public function getSectionBundlesBackend()
    {
        $sectionBundles = array();

        /** @var $sectionBundle SectionBundle */
        foreach ($this->sectionBundles as $sectionBundle) {

            if ($sectionBundle->getBundle()->getApp() == 'Backend') {
                $sectionBundles[] = $sectionBundle;
            }
        }

        return $sectionBundles;
    }

    /**
     * Set ordering
     *
     * @param integer $ordering The ordering number
     */
    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;
    }

    /**
     * Get ordering
     *
     * @return integer
     */
    public function getOrdering()
    {
        return $this->ordering;
    }

    /**
     * Set Route
     *
     * @param string $route A route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * Set Route Params
     *
     * @param array $params An array of params
     */
    public function setRouteParams($params)
    {
        $this->routeParams = $params;
    }

    /**
     * Get sectionNavigations
     *
     * @return ArrayCollection
     */
    public function getSectionNavigations()
    {
        return $this->sectionNavigations;
    }

    /**
     * Set sectionNavigations
     *
     * @param $sectionNavigations
     */
    public function setSectionNavigations($sectionNavigations)
    {
        $this->sectionNavigations = $sectionNavigations;
    }

    /**
     * Add children
     *
     * @param Section $children The Section to add as a child
     */
    public function addSection(Section $children)
    {
        $this->children[] = $children;
    }

    /**
     * Add sectionBundles
     *
     * @param SectionBundle $sectionBundle SectionBundle to add
     */
    public function addSectionBundle(SectionBundle $sectionBundle)
    {
        $this->sectionBundles[] = $sectionBundle;
    }

    /**
     * Add sectionNavigations
     *
     * @param SectionNavigation $sectionNavigation SectionNavigation to add
     */
    public function addSectionNavigation(SectionNavigation $sectionNavigation)
    {
        $this->sectionNavigations[] = $sectionNavigation;
    }

    /**
     * Add text
     *
     * @param Text $text Text to add
     */
    public function addText(Text $text)
    {
        $this->texts[] = $text;
    }

    /**
     * Get texts
     *
     * @return ArrayCollection
     */
    public function getTexts()
    {
        return $this->texts;
    }

    /**
     * Set the array of texts
     *
     * @param array $texts An array of texts
     */
    public function setTexts($texts)
    {
        $this->texts = $texts;
    }

    /**
     * Add translations
     *
     * @param SectionTranslation $translations
     */
    public function addSectionTranslation(SectionTranslation $translations)
    {
        $this->translations[] = $translations;
    }

    /**
     * Get translations
     *
     * @return ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Returns the complete path of the section (Section / Sub-Section / Sub-sub-section ... )
     *
     * @return string
     */
    public function getHierarchicalName()
    {
        $return = $this->__toString();
        if ($this->getParent()) {
            $return = $this->parent->getHierarchicalName() . " / " . $return;
        }
        return $return;
    }

    /**
     * Checks if the section is in a specific navigation
     *
     * @param $navigationName
     *
     * @return bool
     */
    public function hasNavigation($navigationName)
    {
        foreach ($this->sectionNavigations as $sectionNavigation) {
            if ($sectionNavigation->getNavigation()->getName() == $navigationName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Basic verification to ensure the headCode contains html
     *
     * @param ExecutionContext $context
     *
     * @return bool
     */
    public function isHeadCodeHtml(ExecutionContext $context)
    {

        if ($this->getHeadCode() != '' && $this->getHeadCode() == strip_tags($this->getHeadCode())) {
            $propertyPath = $context->getPropertyPath() . '.translation.headCode';
            $context->setPropertyPath($propertyPath);
            $context->addViolation('You must put your content in html tags.', array(), null);

            return false;
        }

        return true;
    }

    /**
     * Get the bundles
     *
     * @return ArrayCollection
     */
    public function getBundles()
    {
        $bundles = new ArrayCollection();

        foreach ($this->sectionBundles as $sectionBundle) {
            $bundles[] = $sectionBundle->getBundle();
        }

        return $bundles;
    }

    /**
     * Set the bundles
     *
     * @param $bundles ArrayCollection
     */
    public function setBundles($bundles)
    {
        $sectionBundles = new ArrayCollection();

        foreach ($bundles as $bundle) {

            $sectionBundle = new SectionBundle();
            $sectionBundle->setBundle($bundle);
            $sectionBundle->setSection($this);
            $sectionBundles[] = $sectionBundle;
        }

        $this->setSectionBundles($sectionBundles);
    }

    /**
     * Get the navigations
     *
     * @return ArrayCollection
     */
    public function getNavigations()
    {
        $navigations = new ArrayCollection();

        foreach ($this->sectionNavigations as $sectionNavigation) {
            $navigations[] = $sectionNavigation->getNavigation();
        }

        return $navigations;
    }

    /**
     * Set the navigations
     *
     * @param $navigations ArrayCollection
     */
    public function setNavigations($navigations)
    {
        $sectionNavigations = new ArrayCollection();

        foreach ($navigations as $navigation) {

            $sectionNavigation = new SectionNavigation();
            $sectionNavigation->setNavigation($navigation);
            $sectionNavigation->setSection($this);
            $sectionNavigations[] = $sectionNavigation;
        }

        $this->setSectionNavigations($sectionNavigations);
    }
}