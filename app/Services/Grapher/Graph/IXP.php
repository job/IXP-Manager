<?php namespace IXP\Services\Grapher\Graph;

/*
 * Copyright (C) 2009-2016 Internet Neutral Exchange Association Limited.
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
use IXP\Services\Grapher\{Graph,Statistics};

use IXP\Exceptions\Services\Grapher\{BadBackendException,CannotHandleRequestException,ConfigurationException,ParameterException};

/**
 * Grapher -> Abstract Graph
 *
 * @author     Barry O'Donovan <barry@islandbridgenetworks.ie>
 * @category   Grapher
 * @package    IXP\Services\Grapher
 * @copyright  Copyright (c) 2009 - 2016, Internet Neutral Exchange Association Ltd
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU GPL V2.0
 */
class IXP extends Graph {

    /**
     * IXP to graph
     * @var \Entities\IXP
     */
    private $ixp = null;


    /**
     * Constructor
     */
    public function __construct( Grapher $grapher ) {
        parent::__construct( $grapher );

        // set a default IXP
        $this->ixp = d2r( 'IXP' )->getDefault();
    }




    /**
     * Get the IXP we're set to use
     * @return \Entities\IXP
     */
    public function ixp(): IXP {
        return $this->ixp;
    }

    /**
     * Set the IXP we should use
     * @param int $v
     * @return \IXP\Services\Grapher Fluid interface
     * @throws \IXP\Exceptions\Services\Grapher\ParameterException
     */
    public function setIXP( $v ): Grapher {
        if( $v == 0 || !( $ixp = d2r( 'IXP' )->find( $v ) ) ) {
            throw new ParameterException('Invalid IXP id ' . $v );
        }

        if( $this->ixp()->getId() != $v ) {
            $this->wipe();
        }

        $this->ixp = $ixp;
        return $this;
    }

    /**
     * Process user input for the parameter: ixp
     *
     * Note that this function just sets the default if the input is invalid.
     * If you want to force an exception in such cases, use setIXP()
     *
     * @param int $v The user input value
     * @return int The verified / sanitised / default value
     */
    public static function processParameterIXP( int $v ): IXP {
        if( !( $ixp = d2r( 'IXP' )->find( $v ) ) ) {
            $ixp = d2r( 'IXP' )->getDefault()->getId();
        }
        return $ixp;
    }


}
