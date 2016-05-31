<?php

namespace LOCKSSOMatic\CrudBundle\Service;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Exception;
use LOCKSSOMatic\CrudBundle\Entity\Au;
use LOCKSSOMatic\CrudBundle\Entity\AuProperty;
use Monolog\Logger;
use Symfony\Component\Routing\Router;

class AuPropertyGenerator
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var Router
     */
    private $router;

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function setRegistry(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function buildProperty(Au $au, $key, $value = null, AuProperty $parent = null)
    {
        $property = new AuProperty();
        $property->setAu($au);
        $property->setPropertyKey($key);
        $property->setPropertyValue($value);
        $property->setParent($parent);
        $this->em->persist($property);

        return $property;
    }

    private function generate(AU $au, $name, $propertyValue)
    {
        $matches = array();
        if (preg_match('/^"([^"]*)"/', $propertyValue, $matches)) {
            $formatStr = $matches[1];
        } else {
            throw new Exception("$name property cannot be parsed: {$propertyValue}");
        }
        // substr/strlen skips the $formatstr part of the property
        $parts = preg_split('/, */', substr($propertyValue, strlen($formatStr) + 2));
        $values = array();
        foreach (array_slice($parts, 1) as $parameterName) {
            $values[] = $au->getAuPropertyValue($parameterName, false);
        }
        $paramCount = preg_match_all('/%[a-zA-Z]/', $formatStr);
        if ($paramCount != count($values)) {
            throw new Exception("Wrong number of parameters for format string: {$formatStr}/{$paramCount} --".print_r(array(
                $parts, ), true));
        }

        return vsprintf($formatStr, $values);
    }

    public function generateSymbol(Au $au, $name)
    {
        $plugin = $au->getPlugin();
        if (!$plugin) {
            throw new Exception("Au requires plugin to generate $name.");
        }
        $property = $plugin->getProperty($name);
        if ($property === null) {
            $this->logger->error("{$plugin->getName()} is missing parameter {$name}.");

            return;
        }
        if (!$property->isList()) {
            return $this->generate($au, $name, $property->getPropertyValue());
        }
        $values = array();
        foreach ($property->getPropertyValue() as $v) {
            $values[] = $this->generate($au, $name, $v);
        }

        return $values;
    }

    /**
     * Check each content item in the AU, and make sure that they all have 
     * the same definitional content properties.
     * 
     * @param Au $au
     */
    public function validateContent(Au $au)
    {
        $content = $au->getContent();
        if (count($content) === 0) {
            throw new Exception('AU must have content to generate properties.');
        }

        if (count($content) === 1) {
            return;
        }
        $plugin = $au->getPlugin();
        $definitional = $plugin->getDefinitionalProperties();
        $baseContent = $content[0];
        foreach (array_slice($content->toArray(), 1) as $c) {
            // compare each content item to the first one, looking for differences.
            foreach ($definitional as $prop) {
                if ($c->getContentPropertyValue($prop) !== $baseContent->getContentPropertyValue($prop)) {
                    throw new Exception("Content property mismatch in AU #{$au->getId()}: "
                    ."content {$c->getId()} {$prop} is ".$c->getContentPropertyValue($prop)
                    ."Expected {$baseContent->getContentPropertyValue($prop)} ");
                }
            }
        }
    }

    public function generateProperties(Au $au, $clear = false)
    {
        $this->validateContent($au);

        if ($clear) {
            foreach ($au->getAuProperties() as $prop) {
                $au->removeAuProperty($prop);
                $this->em->remove($prop);
            }
            $this->em->flush();
        }

        $rootName = str_replace('.', '', uniqid('lockssomatic', true));
        $content = $au->getContent()[0];

        $root = $this->buildProperty($au, $rootName);

        // config params are used to build other properties. So set them first.
        $offset = 0;

        $properties = array_merge(
            $au->getPlugin()->getDefinitionalProperties(),
            $au->getPlugin()->getNonDefinitionalProperties()
        );

        foreach ($properties as $index => $property) {
            if ($property === 'manifest_url') {
                $value = $this->router->generate('configs_manifest', array(
                    'plnId' => $au->getPln()->getId(),
                    'ownerId' => $au->getContentprovider()->getContentOwner()->getId(),
                    'providerId' => $au->getContentprovider()->getId(),
                    'auId' => $au->getId(),
                ), Router::ABSOLUTE_URL);
            } else {
                $value = $content->getContentPropertyValue($property);
            }
            $grouping = $this->buildProperty($au, 'param.'.($index + 1 + $offset), null, $root);
            $this->buildProperty($au, 'key', $property, $grouping);
            $this->buildProperty($au, 'value', $value, $grouping);
        }

        $this->buildProperty($au, 'journalTitle', $content->getContentPropertyValue('journalTitle'), $root);
        $this->buildProperty($au, 'title', 'LOCKSSOMatic AU '.$content->getTitle().' '.$content->getDeposit()->getTitle(), $root);
        $this->buildProperty($au, 'plugin', $au->getPlugin()->getPluginIdentifier(), $root);
        $this->buildProperty($au, 'attributes.publisher', $content->getContentPropertyValue('publisher'), $root);
        foreach ($content->getContentProperties() as $property) {
            $value = $property->getPropertyValue();
            if (is_array($value)) {
                $this->logger->warn("AU {$au->getId()} has unsupported property value list {$property->getPropertyKey()}");
                continue;
            }
            $this->buildProperty($au, 'attributes.pkppln.'.$property->getPropertyKey(), $value, $root);
        }

        $this->em->flush();
    }
}
