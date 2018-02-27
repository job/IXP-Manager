<?php namespace IXP\Services\Grapher\Graph;

/*
 * Copyright (C) 2009-2016 Internet Neutral Exchange Association Company Limited By Guarantee.
 * All Rights Reserved.
 *
 * This file is part of IXP Manager.
 *
 * IXP Manager is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, version v2.0 of the License.
 *
 * IXP Manager is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use IXP\Services\Grapher;
use IXP\Services\Grapher\{
    Graph
};

use IXP\Exceptions\Services\Grapher\{
    ParameterException
};

use Entities\{
    User as UserEntity,
    VlanInterface as VlanInterfaceEntity
};

use Auth, D2EM;

/**
 * Grapher -> Smokeping Graph
 *
 * @author     Barry O'Donovan <barry@islandbridgenetworks.ie>
 * @category   Grapher
 * @package    IXP\Services\Grapher
 * @copyright  Copyright (C) 2009-2016 Internet Neutral Exchange Association Company Limited By Guarantee
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU GPL V2.0
 */
class Smokeping extends Graph {


    /**
     * Trunk to graph
     * @var string
     */
    private $vli = null;


    /**
     * Period of three hours for graphs
     */
    const PERIOD_3HOURS   = '3hours';

    /**
     * Period of thirty hours for graphs
     */
    const PERIOD_30HOURS  = '30hours';

    /**
     * Period of ten days for graphs
     */
    const PERIOD_10DAYS = '10days';

    /**
     * Period of one year for graphs
     */
    const PERIOD_1YEAR  = '1year';

    /**
     * Default period
     */
    const PERIOD_DEFAULT  = self::PERIOD_3HOURS;


    /**
     * Array of valid periods for drill down graphs
     */
    const PERIODS = [
        self::PERIOD_3HOURS     => "3hours",
        self::PERIOD_30HOURS    => "30hours",
        self::PERIOD_10DAYS     => "10days",
        self::PERIOD_1YEAR      => "1year"
    ];



    /**
     * Set the period we should use
     * @param int $v
     * @return Graph Fluid interface
     * @throws ParameterException
     */
    public function setPeriod( $v ): Graph {

        if( !isset( self::PERIODS[ $v ] ) ) {
            throw new ParameterException('Invalid period ' . $v );
        }

        if( $this->period() != $v ) {
            $this->wipe();
        }

        $this->period = $v;

        return $this;
    }

    /**
     * Get the period description for a given period identifier
     * @param string $period
     * @return string
     */
    public static function resolvePeriod( $period = null ): string {
        return self::PERIODS[ $period ] ?? 'Unknown';
    }


    /**
     * Constructor
     * @param  Grapher             $grapher
     * @param VlanInterfaceEntity $vli
     */
    public function __construct( Grapher $grapher, VlanInterfaceEntity $vli ) {
        parent::__construct( $grapher );
        $this->vli = $vli;
    }

    /**
     * Get the smokeping name we're meant to graph
     * @return VlanInterfaceEntity
     */
    public function vli(): VlanInterfaceEntity {
        return $this->vli;
    }

    /**
     * The name of a graph (e.g. member name, IXP name, etc)
     * @return string
     */
    public function name(): string {
        return sprintf( "smokeping :: %s",
            $this->vli()->getId()
        );
    }

    /**
     * A unique identifier for this 'graph type'
     *
     * E.g. for an IXP, it might be ixpxxx where xxx is the database id
     * @return string
     */
    public function identifier(): string {
        return sprintf( "smokeping-vli%s", $this->vli()->getId() );
    }


    /**
     * This function controls access to the graph.
     *
     * {@inheritDoc}
     *
     * For (public) vlan aggregate graphs we pretty much allow complete access.
     *
     * @return bool
     */
    public function authorise() : bool {
        if( Auth::check() && Auth::user()->isSuperUser() ) {
            return $this->allow();
        }

        if( config( 'grapher.access.smokeping', -1 ) == UserEntity::AUTH_PUBLIC ) {
            return $this->allow();
        } else if( Auth::check() && Auth::user()->getPrivs() >= config( 'grapher.access.smokeping', 0 ) ) {
            return $this->allow();
        }

        return $this->deny();
    }

    /**
     * Generate a URL to get this graphs 'file' of a given type
     *
     * @param array $overrides Allow standard parameters to be overridden (e.g. category)
     * @return string
     */
    public function url( array $overrides = [] ): string {
        return parent::url( $overrides ) . sprintf("&id=%d",
                isset( $overrides['id']   ) ? $overrides['id']   : $this->vli()->getId()
            );
    }

    /**
     * Get parameters in bulk as associative array
     *
     * Extends base function
     *
     * @return array $params
     */
    public function getParamsAsArray(): array {
        $p = parent::getParamsAsArray();
        $p['id'] = $this->vli()->getId();
        return $p;
    }


    /**
     * Process user input for the parameter: vlanint
     *
     * Does a abort(404) if invalid
     *
     * @param   int                     $vliid  The user input value
     * @return  VlanInterfaceEntity     $vli    VlanInterface object
     */
    public static function processParameterVlanInterface( int $vliid ): VlanInterfaceEntity {
        if( !$vliid || !( $vli = D2EM::getRepository( VlanInterfaceEntity::class )->find( $vliid ) ) ) {
            abort(404);
        }
        return $vli;
    }
}