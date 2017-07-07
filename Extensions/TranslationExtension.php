<?php

namespace Unifik\SystemBundle\Extensions;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Unifik\SystemBundle\Lib\BaseEntity;
use Symfony\Component\Security\Core\Util\ClassUtils;

/**
 * Library of helper functions
 */
class TranslationExtension extends \Twig_Extension
{
    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var ArrayCollection
     */
    protected $locales;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * List of available filters
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('transTitle', [$this, 'transTitle']),
        );
    }

    /**
     * This filter try to generate a string representation of an entity in the current locale.
     * If there is no usable reprentation available, a fallback machanism is launch and every
     * active locales are tried until one provides an usable result.
     *
     * @param $entity
     *
     * @return string
     */
    public function transTitle($entity)
    {
        // Fallback not necessary, entity provides a usable string representation in the current locale.
        if ((string) $entity || !$this->isTranslatable($entity)) {
            return $entity;
        }

        $entityPreviousLocale = $entity->getCurrentLocale();

        if (false == $this->locales) {
            $this->locales = $this->doctrine->getManager()->getRepository('UnifikSystemBundle:Locale')->findBy(
                array('active' => true),
                array('ordering' => 'ASC')
            );
        }

        // fallback to other locales
        foreach ($this->locales as $locale) {

            if ($locale->getCode() === $entityPreviousLocale) {
                continue;
            }

            $entity->setCurrentLocale($locale->getCode());

            if ($fallback = (string) $entity) {
                $entity->setCurrentLocale($entityPreviousLocale);

                return $fallback;
            }
        }

        return '';
    }

    /**
     * Check if the entity is a Translatable entity
     *
     * @param $entity
     *
     * @return bool
     */
    protected function isTranslatable($entity)
    {
        if (!is_object($entity)) {
            return false;
        }

        // Support Doctrine Proxies
        $realClass = ClassUtils::getRealClass($entity);
        $reflClass = new \ReflectionClass($realClass);

        $traitNames = $reflClass->getTraitNames();

        return in_array('Unifik\DoctrineBehaviorsBundle\Model\Translatable\Translatable', $traitNames)
                && $reflClass->hasProperty('translations');
    }

    /**
     * Name of this extension
     *
     * @return string
     */
    public function getName()
    {
        return 'translation_extension';
    }
}
