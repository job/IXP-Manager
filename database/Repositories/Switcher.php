<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;
use Entities\SwitchPort;

/**
 * Switcher
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Switcher extends EntityRepository
{
    /**
     * The cache key for all switch objects
     * @var string The cache key for all switch objects
     */
    const ALL_CACHE_KEY = 'inex_switches';

    /**
     * Return an array of all switch objects from the database with caching
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     * @return array An array of all switch objects
     */
    public function getAndCache( $active = false, $type = 0 )
    {
        $dql = "SELECT s FROM Entities\\Switcher s WHERE 1=1";

        $key = $this->genCacheKey( $active, $type );

        if( $active )
            $dql .= " AND s.active = 1";

        if( $type )
            $dql .= " AND s.switchtype = " . intval( $type );

        return $this->getEntityManager()->createQuery( $dql )
            ->useResultCache( true, 3600, $key )
            ->getResult();
    }


    /**
     * Clear the cache of a given result set
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     */
    public function clearCache( $active = false, $type = 0 )
    {
        return $this->getEntityManager()->getConfiguration()->getQueryCacheImpl()->delete(
            $this->genCacheKey( $active, $type )
        );
    }

    /**
     * Generate a deterministic caching key for given parameters
     *
     * @param bool $active If `true`, return only active switches
     * @param int $type If `0`, all types otherwise limit to specific type
     * @return string The generate caching key
     */
    public function genCacheKey( $active, $type )
    {
        $key = self::ALL_CACHE_KEY;

        if( $active )
            $key .= '-active';
        else
            $key .= '-all';

        if( $type )
            $key .= '-' . intval( $type );
        else
            $key .= '-all';

        return $key;
    }

    /**
     * Return an array of all switch names where the array key is the switch id
     *
     * @param bool          $active If `true`, return only active switches
     * @param int           $type   If `0`, all types otherwise limit to specific type
     * @param \Entities\IXP $ixp    IXP to filter vlan names
     * @return array An array of all switch names with the switch id as the key.
     */
    public function getNames( $active = false, $type = 0, $ixp = false )
    {
        $switches = [];
        foreach( $this->getAndCache( $active, $type ) as $a )
        {
            if( !$ixp || ( $ixp->getInfrastructures()->contains( $a->getInfrastructure() ) ) )
                $switches[ $a->getId() ] = $a->getName();
        }

        asort( $switches );
        return $switches;
    }

    /**
     * Return an array of all switch names where the array key is the switch id
     *
     * @param bool          $active If `true`, return only active switches
     * @param int           $type   If `0`, all types otherwise limit to specific type
     * @param int           $idLocation  location requiered
     * @return array An array of all switch names with the switch id as the key.
     */
    public function getNamesByLocation( $active = false, $type = 0, $idLocation = null )
    {
        $switches = [];
        foreach( $this->getAndCache( $active, $type ) as $a ) {

            if($idLocation != null)
                if($a->getCabinet()->getLocation()->getId() == $idLocation)
                    $switches[ $a->getId() ] = $a->getName();
        }

        asort( $switches );
        return $switches;
    }


    /**
     * Return an array of configurations
     *
     * @param int $switchid Switcher id for filtering results
     * @param int $vlanid   Vlan id for filtering results
     * @param int $ixpid    IXP id for filtering results
     * @return array
     */
    public function getConfiguration( $switchid = null, $vlanid = null, $ixpid = null, $superuser = true )
    {
        $q =
            "SELECT s.name AS switchname, s.id AS switchid,
                    sp.name AS portname, sp.ifName AS ifName,
                    pi.speed AS speed, pi.duplex AS duplex, pi.status AS portstatus,
                    c.name AS customer, c.id AS custid, c.autsys AS asn,
                    vli.rsclient AS rsclient,
                    v.name AS vlan,
                    ipv4.address AS ipv4address, ipv6.address AS ipv6address

            FROM \\Entities\\VlanInterface vli
                JOIN vli.IPv4Address ipv4
                LEFT JOIN vli.IPv6Address ipv6
                LEFT JOIN vli.Vlan v
                LEFT JOIN vli.VirtualInterface vi
                LEFT JOIN vi.Customer c
                LEFT JOIN vi.PhysicalInterfaces pi
                LEFT JOIN pi.SwitchPort sp
                LEFT JOIN sp.Switcher s
                LEFT JOIN v.Infrastructure vinf
                LEFT JOIN vinf.IXP vixp
                LEFT JOIN s.Infrastructure sinf
                LEFT JOIN sinf.IXP sixp

            WHERE 1=1 ";

        if( $switchid !== null )
            $q .= 'AND s.id = ' . intval( $switchid ) . ' ';

        if( $vlanid !== null )
            $q .= 'AND v.id = ' . intval( $vlanid ) . ' ';

        if( $ixpid !== null )
            $q .= 'AND ( sixp.id = ' . intval( $ixpid ) . ' OR vixp.id = ' . intval( $ixpid ) . ' ) ';

        if( !$superuser && $ixpid )
            $q .= 'AND ?1 MEMBER OF c.IXPs ';

        $q .= "ORDER BY customer ASC";

        $query = $this->getEntityManager()->createQuery( $q );

        if( !$superuser && $ixpid )
            $query->setParameter( 1, $ixpid );
        
        return $query->getArrayResult();
    }


    /**
     * Get all active switches as Doctrine2 objects
     *
     * @return array
     */
    public function getActive()
    {
        $q = "SELECT s FROM \\Entities\\Switcher s WHERE s.active = 1";
        return $this->getEntityManager()->createQuery( $q )->getResult();
    }


    /**
     * Returns all available switch ports where available means not in use by a
     * patch panel port.
     *
     * Function specifically for use with the patch panel ports functionality.
     *
     * Not suitable for other generic use.
     *
     * @param int      $id     Switch ID - switch to query
     * @param int|null $cid    Customer ID, if set limit to a customer's ports
     * @param int|null $spid   Switch port ID, if set, this port is excluded from the results
     * @return array
     */
    public function getAllPorts( int $id, int $cid = null, int $spid = null ): array {

        /** @noinspection SqlNoDataSourceInspection */
        $dql = "SELECT sp.name AS name, sp.type AS type, sp.id AS id
                    FROM \\Entities\\SwitchPort sp
                        LEFT JOIN sp.Switcher s
                        LEFT JOIN sp.PhysicalInterface pi ";


        if( $cid != null ) {
            $dql .= " LEFT JOIN pi.VirtualInterface vi 
                      LEFT JOIN vi.Customer c";
        }

        // Remove the switch ports already in use by all patch panels
        $dql .= " WHERE sp.id NOT IN ( SELECT IDENTITY(ppp.switchPort)
                    FROM Entities\\PatchPanelPort ppp
                    WHERE ppp.switchPort IS NOT NULL";

        if( $spid != null ) {
            $dql .= " AND ppp.switchPort != $spid";
        }

        $dql .= ") AND s.id = ?1";

        if( $cid != null ) {
            $dql .= " AND c.id = $cid";
        }

        $dql .= " ORDER BY sp.id ASC";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id);

        $ports = $query->getArrayResult();

        foreach( $ports as $id => $port ){
            $ports[$id]['type'] = \Entities\SwitchPort::$TYPES[ $port['type'] ];
        }

        return $ports;
    }


    /**
     * Returns all available switch ports where available means not in use by a
     * patch panel port and not assigned to a physical interface.
     *
     * Function specifically for use with the patch panel ports functionality.
     *
     * Not suitable for other generic use.
     *
     * @param int      $id     Switch ID - switch to query
     * @param int|null $spid   Switch port ID, if set, this port is excluded from the results
     * @return array
     */
    public function getAllPortsPrewired( int $id, int $spid = null ): array {
        /** @noinspection SqlNoDataSourceInspection */
        $dql = "SELECT sp.name AS name, sp.type AS type, sp.id AS id
                    FROM \\Entities\SwitchPort sp
                        LEFT JOIN sp.Switcher s ";


        // Remove the switch port already use by a patch panel port
        $dql .= " WHERE sp.id NOT IN (SELECT IDENTITY(ppp.switchPort)
                                      FROM Entities\\PatchPanelPort ppp
                                      WHERE ppp.switchPort IS NOT NULL";

        if( $spid !== null ){
            $dql .= " AND ppp.switchPort != $spid";
        }

        $dql .= ") AND s.id = ?1";


        $dql .= " AND sp.id NOT IN (SELECT IDENTITY(pi.SwitchPort)
                                      FROM Entities\\PhysicalInterface pi)";

        $dql .= " AND ((sp.type = 0)  OR (sp.type = 1))";

        $dql .= " ORDER BY sp.id ASC";

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $id);

        $ports = $query->getArrayResult();

        foreach( $ports as $id => $port ){
            $ports[$id]['type'] = \Entities\SwitchPort::$TYPES[ $port['type'] ];
        }

        return $ports;
    }
}
