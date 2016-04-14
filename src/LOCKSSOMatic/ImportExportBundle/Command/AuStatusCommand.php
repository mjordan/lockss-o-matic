<?php

namespace LOCKSSOMatic\ImportExportBundle\Command;

use DateTime;
use Doctrine\ORM\EntityManager;
use LOCKSSOMatic\CrudBundle\Entity\Au;
use LOCKSSOMatic\CrudBundle\Entity\AuStatus;
use LOCKSSOMatic\CrudBundle\Entity\Box;
use LOCKSSOMatic\CrudBundle\Entity\Content;
use LOCKSSOMatic\CrudBundle\Entity\Deposit;
use LOCKSSOMatic\CrudBundle\Entity\Pln;
use LOCKSSOMatic\CrudBundle\Service\AuIdGenerator;
use Monolog\Logger;
use SoapClient;
use SoapFault;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AuStatusCommand extends ContainerAwareCommand {

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var Box[]
	 */
	private $boxes;

	/**
	 * @var AuIdGenerator
	 */
	private $idGenerator;

	public function configure() {
		$this->setName('lom:au:status');
		$this->setDescription('Check the status of the LOCKSS AUs');
		$this->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Export only, do not update any internal configs.');
	}

	public function setContainer(ContainerInterface $container = null) {
		parent::setContainer($container);
		$this->logger = $container->get('logger');
		$this->em = $container->get('doctrine')->getManager();
		$this->idGenerator = $this->getContainer()->get('crud.au.idgenerator');
	}

	protected function loadBoxes(Pln $pln) {
		$boxes = $pln->getBoxes();
		foreach ($boxes as $box) {
			$statusClient = new SoapClient("http://{$box->getIpAddress()}:8081/ws/DaemonStatusService?wsdl", array(
				'soap_version' => SOAP_1_1,
				'login' => 'lockss-u',
				'password' => 'lockss-p',
				'trace' => true,
				'exceptions' => true,
				'cache' => WSDL_CACHE_NONE,
			));
			$readyResponse = $statusClient->isDaemonReady();
			if($readyResponse) {
				$this->boxes[] = $box;
			} else {
				$this->logger->error("Box {$box->getId()} is not ready.");
			}
		}
	}

	protected function checkAu(Au $au) {
		$auid = $this->idGenerator->fromAu($au);
		$statuses = array();
		foreach ($this->boxes as $box) {
			$url = "http://{$box->getIpAddress()}:{$box->getWebServicePort()}/ws/DaemonStatusService?wsdl";
			$statusClient = new SoapClient($url, array(
				'soap_version' => SOAP_1_1,
				'login' => $box->getUsername(),
				'password' => $box->getPassword(),
				'trace' => true,
				'exceptions' => true,
				'cache' => WSDL_CACHE_NONE,
			));
			$statusResponse = $statusClient->getAuStatus(array(
				'auId' => $auid,
			));
			$auStatus = new AuStatus();
			$auStatus->setAu($au);
			$auStatus->setBox($box);
			$auStatus->setQueryDate(new DateTime());
			$auStatus->setStatus(get_object_vars($statusResponse->return));
			$statuses[] = $auStatus;
		}
		return $statuses;
	}

	/**
	 * @return Pln
	 * @param array|null $plnIds
	 */
	protected function getPlns($plnIds = null) {
		$pln = $this->em->getRepository('LOCKSSOMaticCrudBundle:Pln')->find(1);
		return $pln;
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$pln = $this->getPlns();		
		$this->loadBoxes($pln);
		foreach($pln->getAus() as $au) {
			$statuses = $this->checkAu($au);
			foreach($statuses as $status) {
				$this->em->persist($status);
			}
			$this->em->flush();
		}
	}
}