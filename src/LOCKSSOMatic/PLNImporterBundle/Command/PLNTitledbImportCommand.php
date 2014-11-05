<?php

// src/LOCKSSOMatic/PLNImporterBundle/Command/PLNTitledbImportCommand.php

namespace LOCKSSOMatic\PLNImporterBundle\Command;

use Doctrine\Common\Util\Debug;
use Doctrine\ORM\EntityManager;
use Exception;
use LOCKSSOMatic\CRUDBundle\Entity\AuProperties;
use LOCKSSOMatic\CRUDBundle\Entity\Aus;
use LOCKSSOMatic\CRUDBundle\Entity\ContentOwners;
use LOCKSSOMatic\CRUDBundle\Entity\Plugins;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Private Lockss network plugin import command-line
 */
class PLNTitledbImportCommand extends ContainerAwareCommand
{

    /**
     * @var EntityManager
     */
    private $em;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->em = $container->get('doctrine')->getManager();
    }

    protected function configure()
    {
        $this->setName('lockssomatic:plntitledbimport')
            ->setDescription('Import PLN titledb file.')
            ->addArgument('path_to_titledb', InputArgument::REQUIRED,
                'Local path to the titledb xml file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pathToTitledb = $input->getArgument('path_to_titledb');
        if (!file_exists($pathToTitledb)) {
            $output->writeln("Cannot find {$pathToTitledb}");
            return;
        }

        $xml = simplexml_load_file($pathToTitledb);
        $titlesXml = $xml->xpath('//lockss-config/property[@name="org.lockss.title"]/property');
        $total = count($titlesXml);
        $output->writeln("Found {$total} title elements.");
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);

        $errors = array();
        $i = 0;
        foreach ($titlesXml as $titleXml) {
            $result = $this->processTitle($titleXml);
            if($result !== null) {
                $errors[$result] = (array_key_exists($result, $errors) ? $errors[$result] + 1 : 1);
            }
            $i++;
            if ($i % 200 === 0) {
                $this->progressReport($output, $total, $i);
            }
        }
        $this->progressReport($output, $total, $total);
        if (count($errors) > 0) {
            foreach ($errors as $k => $v) {
                $output->writeln("Error ($v) $k");
            }
        }
    }

    protected function progressReport(OutputInterface $output, $total, $i) {
        $this->em->flush();
        $this->em->clear();
        gc_collect_cycles();
        $output->writeln(" $i / $total - " . sprintf('%dM', memory_get_usage() / (1024 * 1024)) . '/' . ini_get('memory_limit'));
    }
    
    protected function processTitle(SimpleXMLElement $titleXml)
    {
        try {
            $this->addAu($titleXml);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return null;
    }

    protected function getPropertyValue(SimpleXMLElement $xml, $name)
    {
        $dataNodes = $xml->xpath("property[@name='{$name}']/@value");
        if (count($dataNodes) === 0) {
            return null;
        }
        if (count($dataNodes === 1)) {
            return (string) $dataNodes[0];
        }
        throw new Exception('Too many elements for property name ' . $name);
    }

    protected function addAu(SimpleXMLElement $xml)
    {
        $pluginId = $this->getPropertyValue($xml, 'plugin');
        $plugin = $this->getPlugin($pluginId);
        if (!$this->em->contains($plugin)) {
            die("not contains: " . Debug::dump($plugin));
        }
        $publisherName = (string) $this->getPropertyValue($xml, 'attributes.publisher');
        $owner = $this->getContentOwner($publisherName, $plugin);

        $aus = new Aus();
        $plugin->addAus($aus);
        $aus->setPlugin($plugin);
        $this->em->persist($aus);

        $auProperties = new AuProperties();
        $auProperties->setAu($aus);
        $auProperties->setPropertyKey((string) $xml->attributes()->name);
        $this->em->persist($auProperties);
        $propRoot = $auProperties;

        foreach ($xml->xpath('property[starts-with(@name, "param.")]') as $node) {
            $nameData = $node->xpath('property[@name="key"]/@value');
            $name = $nameData[0];
            $valueData = $node->xpath('property[@name="value"]/@value');
            $value = $valueData[0];

            $childProp = new AuProperties();
            $childProp->setAu($aus);
            $childProp->setParent($propRoot);
            $childProp->setPropertyKey($node->attributes()->name);
            $this->em->persist($childProp);

            $keyProp = new AuProperties();
            $keyProp->setAu($aus);
            $keyProp->setParent($childProp);
            $keyProp->setPropertyKey('key');
            $keyProp->setPropertyValue($name);
            $this->em->persist($keyProp);

            $valProp = new AuProperties();
            $valProp->setAu($aus);
            $valProp->setParent($childProp);
            $valProp->setPropertyKey('value');
            $valProp->setPropertyValue($value);
            $this->em->persist($valProp);
        }
    }

    /**
     * Get a plugin based on its identifier property.
     *
     * @todo some caching here would be very good.
     *
     * @param string $pluginId
     *
     * @return Plugins
     */
    protected function getPlugin($pluginId)
    {
        static $cache = array();
        // $this->em->clear() may disconnect entities in this cache
        // for some reason.
        if (array_key_exists($pluginId, $cache) && $this->em->contains($cache[$pluginId])) {
            return $cache[$pluginId];
        }

        $property = $this->em->getRepository('LOCKSSOMaticCRUDBundle:PluginProperties')
            ->findOneBy(array(
            'propertyKey'   => 'plugin_identifier',
            'propertyValue' => $pluginId
        ));

        if ($property === null) {
            throw new Exception("Unknown pluginId property: {$pluginId}");
        }

        $cache[$pluginId] = $property->getPlugin();
        return $property->getPlugin();
    }

    protected function getContentOwner($name, Plugins $plugin)
    {
        static $cache = array();

        if (array_key_exists($name, $cache)) {
            return $cache[$name];
        }

        $owner = $this->em->getRepository('LOCKSSOMaticCRUDBundle:ContentOwners')
            ->findOneBy(array(
            'name' => $name
        ));
        if ($owner === null) {
            $owner = new ContentOwners();
            $owner->setName($name);
            $owner->setEmailAddress('unknown');
            $owner->setPlugin($plugin);
            $this->em->persist($owner);
        }
        $cache[$name] = $owner;
        return $cache[$name];
    }

}
