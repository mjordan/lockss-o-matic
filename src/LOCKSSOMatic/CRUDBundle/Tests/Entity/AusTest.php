<?php

namespace LOCKSSOMatic\CRUDBundle\Tests\Entity;

use LOCKSSOMatic\CRUDBundle\Entity\Aus;
use LOCKSSOMatic\CRUDBundle\Entity\Content;
use PHPUnit_Framework_TestCase;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2014-10-27 at 10:27:41.
 */
class AusTest extends PHPUnit_Framework_TestCase
{

    public function testGetEmptySize() {
        $au = new Aus();
        $this->assertEquals(0, $au->getContentSize());
    }
    
    public function testGetSize() {
        $au = new Aus();
        $content = new Content();
        $content->setSize(120);
        $au->addContent($content);
        $this->assertEquals(120, $au->getContentSize());
        
        $content = new Content();
        $content->setSize(105);
        $au->addContent($content);
        $this->assertEquals(225, $au->getContentSize());
    }
    
    public function testGetSizeWithNulls() {
        $au = new Aus();
        $content = new Content();
        $content->setSize(120);
        $au->addContent($content);
        $this->assertEquals(120, $au->getContentSize());
        
        $content = new Content(); 
        // no setSize()
        $au->addContent($content);
        $this->assertEquals(120, $au->getContentSize());
        
        $content = new Content();
        $content->setSize(105);
        $au->addContent($content);
        $this->assertEquals(225, $au->getContentSize());
    }
    
}
