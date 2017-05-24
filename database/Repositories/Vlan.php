<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

use Entities\{
    Router as RouterEntity
};


use Cache;

/**
 * Vlan
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class Vlan extends EntityRepository
{
    /**
     * The cache key for all VLAN objects
     * @var string The cache key for all VLAN objects
     */
    const ALL_CACHE_KEY = 'inex_vlans';


    /**
     * Constant to represent normal and private VLANs
     * @var int Constant to represent normal and private VLANs
     */
    const TYPE_ALL     = 0;

    /**
     * Constant to represent normal VLANs only
     * @var int Constant to represent normal VLANs ony
     */
    const TYPE_NORMAL  = 1;

    /**
     * Constant to represent private VLANs only
     * @var int Constant to represent private VLANs ony
     */
    const TYPE_PRIVATE = 2;


    /**
     * Return an array of all VLAN objects from the database with caching
     * (and with the option to specify types - returns normal (non-private)
     * VLANs by default.
     *
     * @param $type int The VLAN types to return (see TYPE_ constants).
     * @return \Entities\Vlan[] An array of all VLAN objects
     */
    public function getAndCache( $type = self::TYPE_NORMAL )
    {
        switch( $type )
        {
            case self::TYPE_ALL:
                $where = "";
                break;

            case self::TYPE_PRIVATE:
                $where = "WHERE v.private = 1";
                break;

            default:
                $where = "WHERE v.private = 0";
                $type = self::TYPE_NORMAL;        // because we never validated $type
                break;
        }

        return $this->getEntityManager()->createQuery(
                "SELECT v FROM Entities\\Vlan v {$where} ORDER BY v.number ASC"
            )
            ->useResultCache( true, 3600, self::ALL_CACHE_KEY . "_{$type}" )
            ->getResult();
    }

    /**
     * Return an array of all VLAN names where the array key is the VLAN id (**not tag**).
     *
     * @param int           $type The VLAN types to return (see TYPE_ constants).
     * @param \Entities\IXP $ixp  IXP to filter vlan names
     * @return array An array of all VLAN names with the vlan id as the key.
     */
    public function getNames( $type = self::TYPE_NORMAL, $ixp = false )
    {
        $vlans = [];
        foreach( $this->getAndCache( $type ) as $a )
        {
            if( ( $ixp && $a->getInfrastructure()->getIXP() == $ixp ) || !$ixp )
                $vlans[ $a->getId() ] = $a->getName();
        }

        return $vlans;
    }

    /**
     * Return all active, trafficing and external VLAN interfaces on a given VLAN for a given protocol
     * (including customer details)
     *
     * Here's an example of the return:
     *
     *     array(56) {
     *         [0] => array(21) {
     *            ["ipv4enabled"] => bool(true)
     *            ["ipv4hostname"] => string(17) "inex.woodynet.net"
     *            ["ipv6enabled"] => bool(true)
     *            ["ipv6hostname"] => string(20) "inex-v6.woodynet.net"
     *            ....
     *            ["id"] => int(109)
     *                ["Vlan"] => array(5) {
     *                      ["name"] => string(15) "Peering VLAN #1"
     *                      ...
     *                }
     *                ["VirtualInterface"] => array(7) {
     *                    ["id"] => int(39)
     *                    ...
     *                }
     *                ["Customer"] => array(31) {
     *                    ["name"] => string(25) "Packet Clearing House DNS"
     *                   ...
     *                }
     *            }
     *         [1] => array(21) {
     *            ...
     *            }
     *        ...
     *     }
     *
     * @param int $vid The VLAN ID to find interfaces on
     * @param int $protocol The protocol to find interfaces on ( `4` or `6`)
     * @param bool $forceDb Set to true to ignore the cache and force the query to the database
     * @return An array as described above
     * @throws \IXP_Exception Thrown if an invalid protocol is specified
     */
    public function getInterfaces( $vid, $protocol, $forceDb = false )
    {
        if( !in_array( $protocol, [ 4, 6 ] ) )
            throw new \IXP_Exception( 'Invalid protocol' );

        $interfaces = $this->getEntityManager()->createQuery(
                "SELECT vli, v, vi, c

                FROM \\Entities\\VlanInterface vli
                    LEFT JOIN vli.Vlan v
                    LEFT JOIN vli.VirtualInterface vi
                    LEFT JOIN vi.Customer c

                WHERE

                    " . Customer::DQL_CUST_CURRENT . "
                    AND " . Customer::DQL_CUST_TRAFFICING . "
                    AND " . Customer::DQL_CUST_EXTERNAL . "
                    AND c.activepeeringmatrix = 1
                    AND v.id = ?1
                    AND vli.ipv{$protocol}enabled = 1

                ORDER BY c.autsys ASC"
            )
            ->setParameter( 1, $vid );

        if( !$forceDb )
            $interfaces->useResultCache( true, 3600 );

        return $interfaces->getArrayResult();
    }

    /**
     * Return all active, trafficing and external customers on a given VLAN for a given protocol
     * (indexed by ASN)
     *
     * Here's an example of the return:
     *
     *     array(56) {
     *         [42] => array(5) {
     *             ["autsys"] => int(42)
     *             ["name"] => string(25) "Packet Clearing House DNS"
     *             ["shortname"] => string(10) "pchanycast"
     *             ["rsclient"] => bool(true)
     *             ["custid"] => int(72)
     *         }
     *         [112] => array(5) {
     *             ["autsys"] => int(112)
     *             ...
     *         }
     *         ...
     *     }
     *
     * @see getInterfaces()
     * @param int $vid The VLAN ID to find interfaces on
     * @param int $protocol The protocol to find interfaces on ( `4` or `6`)
     * @param bool $forceDb Set to true to ignore the cache and force the query to the database
     * @return An array as described above
     * @throws \IXP_Exception Thrown if an invalid protocol is specified
     */
    public function getCustomers( $vid, $protocol, $forceDb = false )
    {
        $key = "vlan_customers_{$vid}_{$protocol}";

        if( !$forceDb && ( $custs = Cache::get( $key ) ) )
            return $custs;

        $acusts = $this->getInterfaces( $vid, $protocol, $forceDb );

        $custs = [];

        foreach( $acusts as $c )
        {
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ] = [];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['autsys']    = $c['VirtualInterface']['Customer']['autsys'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['name']      = $c['VirtualInterface']['Customer']['name'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['shortname'] = $c['VirtualInterface']['Customer']['shortname'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['rsclient']  = $c['rsclient'];
            $custs[ $c['VirtualInterface']['Customer']['autsys'] ]['custid']    = $c['VirtualInterface']['Customer']['id'];
        }

        Cache::put( $key, $custs, 86400 );

        return $custs;
    }

    /**
     * Find all VLANs marked for inclusion in the peering manager.
     *
     * @return Entities\Vlan[]
     */
    public function getPeeringManagerVLANs()
    {
        return $this->getEntityManager()->createQuery(
                "SELECT v FROM \\Entities\\Vlan v
                    WHERE
                        v.peering_manager = 1
                    ORDER BY v.number ASC"
            )
            ->getResult();
    }

    /**
    * Find all VLANs marked for inclusion in the peering matrices.
    *
    * @return Entities\Vlan[]
    */
    public function getPeeringMatrixVLANs()
    {
        return $this->getEntityManager()->createQuery(
                "SELECT v FROM \\Entities\\Vlan v
                    WHERE v.peering_matrix = 1
                ORDER BY v.number ASC"
            )
            ->getResult();
    }


    /**
     * Returns an array of private VLANs with their details and membership.
     *
     * A sample return would be:
     *
     *     [
     *         [8] => [             // vlanId
     *             [vlanid] => 8
     *             [name] => PV-BBnet-HEAnet
     *             [number] => 1300
     *             [members] => [
     *                 [764] => [            // cust ID
     *                     [id] => 764
     *                     [name] => CustA
     *                     [vintid] => 169   // virtual interface ID
     *                 ]
     *                 [60] => [
     *                     [id] => 60
     *                     [name] => CustB
     *                     [vintid] => 212
     *                 ]
     *             ]
     *         ]
     *         [....]
     *         [....]
     *     ]
     *
     * @return array
     */
    public function getPrivateVlanDetails( $infra = null )
    {
        $q = "SELECT vli, v, vi, pi, sp, s, l, cab, c, i, ixp
                FROM \\Entities\\Vlan v
                    LEFT JOIN v.VlanInterfaces vli
                    LEFT JOIN v.Infrastructure i
                    LEFT JOIN i.IXP ixp
                    LEFT JOIN vli.VirtualInterface vi
                    LEFT JOIN vi.Customer c
                    LEFT JOIN vi.PhysicalInterfaces pi
                    LEFT JOIN pi.SwitchPort sp
                    LEFT JOIN sp.Switcher s
                    LEFT JOIN s.Cabinet cab
                    LEFT JOIN cab.Location l

                WHERE

                    v.private = 1 ";

        if( $infra )
            $q .= ' AND i = :infra ';

        $q .= 'ORDER BY v.number ASC';

        $q = $this->getEntityManager()->createQuery( $q );

        if( $infra )
            $q->setParameter( 'infra', $infra );

        $vlans = $q->getArrayResult();

        if( !$vlans || !count( $vlans ) )
            return [];

        $pvs = [];

        foreach( $vlans as $v )
        {
            $pvs[ $v['id'] ]['vlanid']   = $v['id'];
            $pvs[ $v['id'] ]['name']     = $v['name'];
            $pvs[ $v['id'] ]['number']   = $v['number'];
            $pvs[ $v['id'] ]['members']  = [];
            $pvs[ $v['id'] ]['infrastructure']    = $v['Infrastructure']['shortname'];
            $pvs[ $v['id'] ]['ixp']      = $v['Infrastructure']['IXP']['shortname'];

            foreach( $v['VlanInterfaces'] as $vli )
            {
                if( !isset( $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ] ) )
                {
                    $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ] = [];
                    $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ]['id']     = $vli['VirtualInterface']['Customer']['id'];
                    $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ]['name']   = $vli['VirtualInterface']['Customer']['name'];
                    $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ]['vintid'] = $vli['VirtualInterface']['id'];
                }

                $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ]['locations'] = [];
                $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ]['switches']  = [];

                foreach( $vli['VirtualInterface']['PhysicalInterfaces'] as $pi )
                {
                    $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ]['locations'][]
                        = $pi['SwitchPort']['Switcher']['Cabinet']['Location']['name'];

                    $pvs[ $v['id'] ]['members'][ $vli['VirtualInterface']['Customer']['id'] ]['switches'][]
                        = $pi['SwitchPort']['Switcher']['name'];
                }
            }
        }

        return $pvs;
    }


    /**
     * Utility function to provide an array of all VLAN interface IP addresses
     * and hostnames on a given VLAN for a given protocol for the purpose of generating
     * an ARPA DNS zone.
     *
     * Returns an array of elements such as:
     *
     *     [
     *         [enabled]  => 1/0
     *         [hostname] => ixp.rtr.example.com
     *         [address]  => 192.0.2.0 / 2001:db8:67::56f
     *     ]
     *
     * @param \Entities\Vlan $vlan The VLAN
     * @param int $proto Either 4 or 6
     * @param bool $useResultCache If true, use Doctrine's result cache (ttl set to one hour)
     * @return array As defined above.
     * @throws \IXP_Exception On bad / no protocol
     */
    public function getArpaDetails( $vlan, $proto, $useResultCache = true )
    {
        if( !in_array( $proto, [ 4, 6 ] ) )
            throw new \IXP_Exception( 'Invalid protocol specified' );


        $qstr = "SELECT vli.ipv{$proto}enabled AS enabled, addr.address AS address,
                        vli.ipv{$proto}hostname AS hostname
                    FROM Entities\\VlanInterface vli
                        JOIN vli.IPv{$proto}Address addr
                        JOIN vli.Vlan v
                    WHERE
                        v = :vlan";

        $qstr .= " ORDER BY addr.address ASC";

        $q = $this->getEntityManager()->createQuery( $qstr );
        $q->setParameter( 'vlan', $vlan );
        $q->useResultCache( $useResultCache, 3600 );
        return $q->getArrayResult();
    }



    /**
     * Get the IPv4 or IPv6 list for a vlan
     *
     * @params  $request instance of the current HTTP request
     * @return  array of IPvX
     */
    public function getIPvAddress( int $vlan, int $ipType, int $vliid = null ) : array {


        if( $ipType == RouterEntity::PROTOCOL_IPV6 )
        {
            $af = 'ipv6'; $entity = 'IPv6Address';
        }
        else
        {
            $af = 'ipv4'; $entity = 'IPv4Address';
        }

        $dql = "SELECT {$af}.id AS id, {$af}.address AS address
                    FROM \\Entities\\{$entity} {$af}
                        LEFT JOIN {$af}.Vlan v
                        LEFT JOIN {$af}.VlanInterface vli
                    WHERE
                        v.id = ?1 ";

        if( $vliid !== null ){
            $dql .= 'AND ( vli.id IS NULL OR vli.id = ?2 )';
        } else{
            $dql .= 'AND vli.id IS NULL';
        }

        $query = $this->getEntityManager()->createQuery( $dql );
        $query->setParameter( 1, $vlan );

        if( $vliid !== null ){
            $query->setParameter( 2, $vliid );
        }
        
        return $query->getArrayResult();


    }

}
