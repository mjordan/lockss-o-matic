<?php

namespace LOCKSSOMatic\CrudBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Content Providers make deposits to LOCKSS via LOCKSSOMatic.
 *
 * @ORM\Table(name="content_providers")
 * @ORM\Entity
 */
class ContentProvider implements GetPlnInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * The UUID for the provider. SWORD requests must include this UUID in the
     * On-Behalf-Of header or in the URL.
     *
     * @var string
     *
     * @ORM\Column(name="uuid", type="string", length=36, nullable=false)
     * @Assert\Uuid(
     *  versions = {"Uuid:V4_RANDOM"},
     *  strict = false
     * )
     */
    private $uuid;

    /**
     * LOCKSS permission URL for the provider. Must be on the same host
     * as the content.
     *
     * @var string
     *
     * @ORM\Column(name="permissionUrl", type="string", length=255, nullable=false)
     */
    private $permissionurl;

    /**
     * Name of the provider.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * The maximum file size for the provider in 1000-byte units.
     *
     * @var int
     *
     * @ORM\Column(name="max_file_size", type="bigint", nullable=true)
     */
    private $maxFileSize;

    /**
     * The maximum AU size for the provider in 1000-byte units.
     *
     * @var int
     *
     * @ORM\Column(name="max_au_size", type="bigint", nullable=true)
     */
    private $maxAuSize;

    /**
     * The owner for the provider. Providers make deposit on behalf
     * of owners.
     *
     * @var ContentOwner
     *
     * @ORM\ManyToOne(targetEntity="ContentOwner", inversedBy="contentProviders")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="content_owner_id", referencedColumnName="id")
     * })
     */
    private $contentOwner;

    /**
     * PLN for the provider.
     *
     * @var Pln
     *
     * @ORM\ManyToOne(targetEntity="Pln", inversedBy="contentProviders")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pln_id", referencedColumnName="id")
     * })
     */
    private $pln;

    /**
     * The LOCKSS Plugin for the content owner.
     *
     * @var Plugin
     *
     * @ORM\ManyToOne(targetEntity="Plugin", inversedBy="contentProviders")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="plugin_id", referencedColumnName="id")
     * })
     */
    private $plugin;

    /**
     * List of AUs for the provider.
     *
     * @ORM\OneToMany(targetEntity="Au", mappedBy="contentProvider")
     *
     * @var Au[]
     */
    private $aus;

    /**
     * Deposits made by the provider.
     *
     * @ORM\OneToMany(targetEntity="Deposit", mappedBy="contentProvider")
     *
     * @var ArrayCollection|Deposit[]
     */
    private $deposits;

    /**
     * Build an empty content provider.
     */
    public function __construct() {
        $this->aus = new ArrayCollection();
        $this->deposits = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set uuid, converted to uppercase.
     *
     * @param string $uuid
     *
     * @return ContentProvider
     */
    public function setUuid($uuid) {
        $this->uuid = strtoupper($uuid);

        return $this;
    }

    /**
     * Get uuid.
     *
     * @return string
     */
    public function getUuid() {
        return strtoupper($this->uuid);
    }

    /**
     * Set permissionurl.
     *
     * @param string $permissionurl
     *
     * @return ContentProvider
     */
    public function setPermissionurl($permissionurl) {
        $this->permissionurl = $permissionurl;

        return $this;
    }

    /**
     * Get permissionurl.
     *
     * @return string
     */
    public function getPermissionurl() {
        return $this->permissionurl;
    }

    /**
     * Convenience method to get the host holding the permission statement
     * from the permission url.
     *
     * @return string
     */
    public function getPermissionHost() {
        return parse_url($this->getPermissionUrl(), PHP_URL_HOST);
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ContentProvider
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set maxFileSize.
     *
     * @param int $maxFileSize
     *
     * @return ContentProvider
     */
    public function setMaxFileSize($maxFileSize) {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    /**
     * Get maxFileSize.
     *
     * @return int
     */
    public function getMaxFileSize() {
        return $this->maxFileSize;
    }

    /**
     * Set maxAuSize.
     *
     * @param int $maxAuSize
     *
     * @return ContentProvider
     */
    public function setMaxAuSize($maxAuSize) {
        $this->maxAuSize = $maxAuSize;

        return $this;
    }

    /**
     * Get maxAuSize.
     *
     * @return int
     */
    public function getMaxAuSize() {
        return $this->maxAuSize;
    }

    /**
     * Set contentOwner.
     *
     * @param ContentOwner $contentOwner
     *
     * @return ContentProvider
     */
    public function setContentOwner(ContentOwner $contentOwner = null) {
        $this->contentOwner = $contentOwner;
        $contentOwner->addContentProvider($this);

        return $this;
    }

    /**
     * Get contentOwner.
     *
     * @return ContentOwner
     */
    public function getContentOwner() {
        return $this->contentOwner;
    }

    /**
     * Set pln.
     *
     * @param Pln $pln
     *
     * @return ContentProvider
     */
    public function setPln(Pln $pln = null) {
        $this->pln = $pln;
        $pln->addContentProvider($this);

        return $this;
    }

    /**
     * Get pln.
     *
     * @return Pln
     */
    public function getPln() {
        return $this->pln;
    }

    /**
     * Add aus.
     *
     * @param Au $aus
     *
     * @return ContentProvider
     */
    public function addAus(Au $aus) {
        $this->aus[] = $aus;

        return $this;
    }

    /**
     * Set plugin.
     *
     * @param Plugin $plugin
     *
     * @return ContentOwner
     */
    public function setPlugin(Plugin $plugin = null) {
        $this->plugin = $plugin;
        if ($plugin !== null) {
            $plugin->addContentProvider($this);
        }

        return $this;
    }

    /**
     * Get plugin.
     *
     * @return Plugin
     */
    public function getPlugin() {
        return $this->plugin;
    }

    /**
     * Remove aus.
     *
     * @param Au $aus
     */
    public function removeAus(Au $aus) {
        $this->aus->removeElement($aus);
    }

    /**
     * Get aus.
     *
     * @return Collection
     */
    public function getAus() {
        return $this->aus;
    }

    /**
     * Count the AUs associated with the provider.
     *
     * @return int
     */
    public function countAus() {
        return $this->aus->count();
    }

    /**
     * Add deposits.
     *
     * @param Deposit $deposits
     *
     * @return ContentProvider
     */
    public function addDeposit(Deposit $deposits) {
        $this->deposits[] = $deposits;

        return $this;
    }

    /**
     * Remove deposits.
     *
     * @param Deposit $deposits
     */
    public function removeDeposit(Deposit $deposits) {
        $this->deposits->removeElement($deposits);
    }

    /**
     * Get deposits.
     *
     * @return ArrayCollection|Deposit[]
     */
    public function getDeposits() {
        return $this->deposits;
    }

    /**
     * Get all of the content items associated with the provider.
     *
     * @return Content[]
     */
    public function getContent() {
        $contentList = array();
        foreach ($this->deposits as $deposit) {
            $content = $deposit->getContent();
            if ($content !== null && count($content) > 0) {
                $contentList = array_merge($contentList, $content->toArray());
            }
        }

        return $contentList;
    }

    /**
     * Synonym for getName().
     *
     * @return string
     */
    public function __toString() {
        return $this->name;
    }
}
