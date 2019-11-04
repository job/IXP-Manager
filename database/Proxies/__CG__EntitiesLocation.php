<?php

namespace Proxies\__CG__\Entities;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Location extends \Entities\Location implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Common\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array properties to be lazy loaded, with keys being the property
     *            names and values being their default values
     *
     * @see \Doctrine\Common\Proxy\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = [];



    /**
     * @param \Closure $initializer
     * @param \Closure $cloner
     */
    public function __construct($initializer = null, $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }







    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', 'name', 'shortname', 'tag', 'address', 'country', 'city', 'nocphone', 'nocfax', 'nocemail', 'officephone', 'officefax', 'officeemail', 'id', 'Cabinets', 'notes', '' . "\0" . 'Entities\\Location' . "\0" . 'pdb_facility_id'];
        }

        return ['__isInitialized__', 'name', 'shortname', 'tag', 'address', 'country', 'city', 'nocphone', 'nocfax', 'nocemail', 'officephone', 'officefax', 'officeemail', 'id', 'Cabinets', 'notes', '' . "\0" . 'Entities\\Location' . "\0" . 'pdb_facility_id'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Location $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy->__getLazyProperties() as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', []);
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', []);
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setName', [$name]);

        return parent::setName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', []);

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function setShortname($shortname)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setShortname', [$shortname]);

        return parent::setShortname($shortname);
    }

    /**
     * {@inheritDoc}
     */
    public function getShortname()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getShortname', []);

        return parent::getShortname();
    }

    /**
     * {@inheritDoc}
     */
    public function setTag($tag)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setTag', [$tag]);

        return parent::setTag($tag);
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTag', []);

        return parent::getTag();
    }

    /**
     * {@inheritDoc}
     */
    public function setAddress($address)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAddress', [$address]);

        return parent::setAddress($address);
    }

    /**
     * {@inheritDoc}
     */
    public function getAddress()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAddress', []);

        return parent::getAddress();
    }

    /**
     * {@inheritDoc}
     */
    public function setNocphone($nocphone)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setNocphone', [$nocphone]);

        return parent::setNocphone($nocphone);
    }

    /**
     * {@inheritDoc}
     */
    public function getNocphone()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getNocphone', []);

        return parent::getNocphone();
    }

    /**
     * {@inheritDoc}
     */
    public function setNocfax($nocfax)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setNocfax', [$nocfax]);

        return parent::setNocfax($nocfax);
    }

    /**
     * {@inheritDoc}
     */
    public function getNocfax()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getNocfax', []);

        return parent::getNocfax();
    }

    /**
     * {@inheritDoc}
     */
    public function setNocemail($nocemail)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setNocemail', [$nocemail]);

        return parent::setNocemail($nocemail);
    }

    /**
     * {@inheritDoc}
     */
    public function getNocemail()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getNocemail', []);

        return parent::getNocemail();
    }

    /**
     * {@inheritDoc}
     */
    public function setOfficephone($officephone)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOfficephone', [$officephone]);

        return parent::setOfficephone($officephone);
    }

    /**
     * {@inheritDoc}
     */
    public function getOfficephone()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOfficephone', []);

        return parent::getOfficephone();
    }

    /**
     * {@inheritDoc}
     */
    public function setOfficefax($officefax)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOfficefax', [$officefax]);

        return parent::setOfficefax($officefax);
    }

    /**
     * {@inheritDoc}
     */
    public function getOfficefax()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOfficefax', []);

        return parent::getOfficefax();
    }

    /**
     * {@inheritDoc}
     */
    public function setOfficeemail($officeemail)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOfficeemail', [$officeemail]);

        return parent::setOfficeemail($officeemail);
    }

    /**
     * {@inheritDoc}
     */
    public function getOfficeemail()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOfficeemail', []);

        return parent::getOfficeemail();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        if ($this->__isInitialized__ === false) {
            return (int)  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', []);

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function addCabinet(\Entities\Cabinet $cabinets)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'addCabinet', [$cabinets]);

        return parent::addCabinet($cabinets);
    }

    /**
     * {@inheritDoc}
     */
    public function removeCabinet(\Entities\Cabinet $cabinets)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'removeCabinet', [$cabinets]);

        return parent::removeCabinet($cabinets);
    }

    /**
     * {@inheritDoc}
     */
    public function getCabinets()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCabinets', []);

        return parent::getCabinets();
    }

    /**
     * {@inheritDoc}
     */
    public function setNotes($notes)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setNotes', [$notes]);

        return parent::setNotes($notes);
    }

    /**
     * {@inheritDoc}
     */
    public function getNotes()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getNotes', []);

        return parent::getNotes();
    }

    /**
     * {@inheritDoc}
     */
    public function setPdbFacilityId($pdbFacilityId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPdbFacilityId', [$pdbFacilityId]);

        return parent::setPdbFacilityId($pdbFacilityId);
    }

    /**
     * {@inheritDoc}
     */
    public function getPdbFacilityId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPdbFacilityId', []);

        return parent::getPdbFacilityId();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountry(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCountry', []);

        return parent::getCountry();
    }

    /**
     * {@inheritDoc}
     */
    public function setCountry(string $country): \Entities\Switcher
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCountry', [$country]);

        return parent::setCountry($country);
    }

    /**
     * {@inheritDoc}
     */
    public function getCity(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCity', []);

        return parent::getCity();
    }

    /**
     * {@inheritDoc}
     */
    public function setCity(string $city): \Entities\Switcher
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCity', [$city]);

        return parent::setCity($city);
    }

}
