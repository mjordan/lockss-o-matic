<?php

namespace LOCKSSOMatic\LockssBundle\Tests\Command;

use Exception;
use LOCKSSOMatic\CoreBundle\Utilities\AbstractTestCase;
use LOCKSSOMatic\CrudBundle\Entity\Plugin;
use LOCKSSOMatic\LockssBundle\Command\TitledbImportCommand;
use SimpleXMLElement;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2015-09-21 at 09:13:43.
 */
class TitledbImportCommandTest extends AbstractTestCase
{
    /**
     * @var PLNTitledbImportCommand
     */
    protected $command;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new TitledbImportCommand();
        $this->command->setContainer($this->getContainer());
    }

    /**
     * @param string $id
     *
     * @return Plugin
     */
    protected function getPlugin($id)
    {
        $repo = $this->em->getRepository('LOCKSSOMaticCrudBundle:Plugin');

        return $repo->findOneByIdentifier($id);
    }

    public function testGetPropertyValue()
    {
        $xmlStr = <<<'ENDXML'
  <property name="BioOneAtyponPluginRadiationResearch169">
   <property name="attributes.publisher" value="Radiation Research Society" />
   <property name="issn" value="0033-7587" />
  </property>
ENDXML;
        $xml = new SimpleXMLElement($xmlStr);
        $this->assertEquals(
            'Radiation Research Society',
            $this->command->getPropertyValue($xml, 'attributes.publisher')
        );
        $this->assertEquals(null, $this->command->getPropertyValue($xml, 'foobar'));
    }

    /**
     * @expectedException Exception
     */
    public function testGetPropertyValueException()
    {
        $xmlStr = <<<'ENDXML'
  <property name="BioOneAtyponPluginRadiationResearch169">
   <property name="attributes.publisher" value="Radiation Research Society" />
   <property name="attributes.publisher" value="0033-7587" />
  </property>
ENDXML;
        $xml = new SimpleXMLElement($xmlStr);
        $this->assertEquals(
            'Radiation Research Society',
            $this->command->getPropertyValue($xml, 'attributes.publisher')
        );
    }

    /**
     * @covers LOCKSSOMatic\LockssBundle\Command\PLNTitledbImportCommand::getContentOwner
     *
     * @todo   Implement testGetContentOwner().
     */
    public function testGetContentOwner()
    {
        $plugin = $this->getPlugin('ca.sfu.test');
        $owner = $this->command->getContentOwner('Test Owner', $plugin);
        $this->assertNotNull($owner);
        $this->assertInstanceOf('LOCKSSOMatic\CrudBundle\Entity\ContentOwner', $owner);
        $this->assertEquals('Test Owner', $owner->getName());

        // fetch it again, test the caching.
        $cachedOwner = $this->command->getContentOwner('Test Owner', $plugin);
        $this->assertNotNull($cachedOwner);
        $this->assertInstanceOf('LOCKSSOMatic\CrudBundle\Entity\ContentOwner', $cachedOwner);
        $this->assertEquals('Test Owner', $cachedOwner->getName());
    }

    /**
     * @covers LOCKSSOMatic\LockssBundle\Command\PLNTitledbImportCommand::addAu
     *
     * @todo   Implement testAddAu().
     */
    public function testAddAu()
    {
        $xml = new SimpleXMLElement($this->getXml());
        $this->command->buildAu($xml);

        $plugin = $this->getPlugin('ca.sfu.test');
        $this->assertEquals(2, count($plugin->getAus()));
    }

    private function getXml()
    {
        $str = <<<'ENDXML'
<property name="Foo">
   <property name="attributes.publisher" value="Radiation Society" />
   <property name="journalTitle" value="Radiation Research" />
   <property name="issn" value="1234-5678" />
   <property name="eissn" value="3321-1234" />
   <property name="type" value="journal" />
   <property name="title" value="Foo Research Volume 16" />
   <property name="plugin" value="ca.sfu.test" />
   <property name="param.1">
    <property name="key" value="base_url" />
    <property name="value" value="http://foo.example.com/" />
   </property>
   <property name="param.2">
    <property name="key" value="journal_id" />
    <property name="value" value="rare" />
   </property>
   <property name="param.3">
    <property name="key" value="volume_name" />
    <property name="value" value="16" />
   </property>
   <property name="attributes.where" value="AUtest" />
   <property name="attributes.year" value="2010" />
  </property>
ENDXML;

        return $str;
    }
}
