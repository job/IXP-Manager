<?php

namespace IXP\Http\Controllers\Services;

/*
 * Copyright (C) 2009 - 2020 Internet Neutral Exchange Association Company Limited By Guarantee.
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

use Auth, D2EM;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use IXP\Models\Aggregators\RouterAggregator;
use IXP\Models\Customer;
use IXP\Models\Router;
use IXP\Models\User;

use Entities\{
    Router as RouterEntity
};

use ErrorException;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

use IXP\Contracts\LookingGlass as LookingGlassContract;

use IXP\Exceptions\Services\LookingGlass\GeneralException as LookingGlassGeneralException;

use IXP\Http\Controllers\Controller;


/**
 * LookingGlass Controller
 *
 * *************************************************
 * ***********      SECURITY NOTICE      ***********
 * *************************************************
 *
 * IF WE GET TO THIS CONTROLLER, WE CAN ASSUME THE
 * REQUEST HAS BEEN VALIDATED AND VERIFIED.
 *
 * THE LookingGlass MIDDLEWARE IS RESPONSIBLE FOR
 * SECURITY AND PARAMETER CHECKS
 *
 * *************************************************
 *
 * @author     Barry O'Donovan   <barry@islandbridgenetworks.ie>
 * @author    Yann Robin        <yann@islandbridgenetworks.ie>
 * @category   LookingGlass
 * @package    IXP\Services\LookingGlass
 * @copyright  Copyright (C) 2009 - 2020 Internet Neutral Exchange Association Company Limited By Guarantee
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU GPL V2.0
 */
class LookingGlass extends Controller
{
    /**
     * the LookingGlass
     *
     * @var LookingGlassContract
     */
    private $lg = null;

    /**
     * The request object
     *
     * @var Request $request
     */
    private $request = null;

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct( Request $request )
    {
        // NB: Constructor happens before middleware...
        $this->request = $request;
    }

    /**
     * Looking glass accessor
     *
     * @return LookingGlassContract
     *
     * @throws
     */
    private function lg(): LookingGlassContract
    {
        if( $this->lg === null ) {
            $this->lg = $this->request()->attributes->get('lg' );
            // if there's no graph then the middleware went wrong... safety net:
            if( $this->lg === null ) {
                throw new LookingGlassGeneralException('Middleware could not load looking glass but did not throw a 404' );
            }
        }
        return $this->lg;
    }

    /**
     * Request accessor
     *
     * @return Request
     */
    private function request(): Request
    {
        return $this->request;
    }

    /**
     * Add view parameters common for all requests.
     *
     * @param View $view
     *
     * @return View
     *
     * @throws
     */
    private function addCommonParams( View $view ): View
    {
        $cust = Auth::check() ? Customer::find( Auth::user()->getCustomer()->getId() ) : null;
        $user = Auth::check() ? User::find( Auth::user()->getId() ) : null;

        $view->with( 'status', json_decode( $this->lg()->status(), false ) );
        $view->with( 'lg',      $this->lg() );
        $view->with( 'routers', RouterAggregator::forDropdown( $cust, $user ) );
        $view->with( 'tabRouters', RouterAggregator::forTab( $cust, $user ) );

        return $view;
    }

    /**
     * Index page
     *
     * @return View
     *
     * @throws
     */
    public function index(): View
    {
        $cust = Auth::check() ? Customer::find( Auth::user()->getCustomer()->getId() ) : null;
        $user = Auth::check() ? User::find( Auth::user()->getId() ) : null;

        return view('services/lg/index' )->with( [
            'lg'            => false,
            'routers'       => RouterAggregator::forDropdown( $cust, $user ),
            'tabRouters'    => RouterAggregator::forTab( $cust, $user )
        ] );
    }

    /**
     * Returns the router's status as JSON
     *
     * @param string $handle
     *
     * @return Response JSON of status
     *
     * @throws
     */
    public function status( string $handle ): Response
    {
        // get the router status
        return response()
            ->make( $this->lg()->status() )
            ->header('Content-Type', 'application/json' );
    }

    /**
     * Returns the router's "bgp summary" as JSON
     *
     * @param string $handle
     * @return Response JSON of status
     *
     * @throws
     */
    public function bgpSummaryApi( string $handle ): Response
    {
        // get the router status
        return response()
            ->make( $this->lg()->bgpSummary() )
            ->header('Content-Type', 'application/json');
    }

    /**
     * @param string $handle
     *
     * @return View
     *
     * @throws
     */
    public function bgpSummary(string $handle ): View
    {
        // get bgp protocol summary
        $view = view('services/lg/bgp-summary' )->with([
            'content' => json_decode( $this->lg()->bgpSummary(), false ),
        ]);

        return $this->addCommonParams( $view );
    }

    /**
     * @param string $handle
     * @param string $table
     *
     * @return RedirectResponse|Redirector|View
     *
     * @throws
     */
    public function routesForTable( string $handle, string $table )
    {
        $tooManyRoutesMsg = "The routing table <code>{$table}</code> has too many routes to display in the web interface. Please use "
            . "<a href=\"" . route( 'lg::route-search', [ 'handle' => $this->lg()->router()->handle ] )
            . "\">the route search tool</a> to query this table.";

        try{
            $routes = $this->lg()->routesForTable( $table );
        } catch( ErrorException $e ) {
            if( strpos( $e->getMessage(), 'HTTP/1.0 403' ) !== false ) {
                return redirect( 'lg/' . $handle )->with( 'msg', $tooManyRoutesMsg );
            }
            return redirect( 'lg/' . $handle )->with('msg', 'An error occurred - please contact our support team if you wish.' );
        }

        if( $routes === "" ) {
            return redirect( 'lg/' . $handle )->with( 'msg', $tooManyRoutesMsg );
        }

        $view = view('services/lg/routes' )->with([
            'content' => json_decode( $routes, false ),
            'source' => 'table', 'name' => $table
        ]);

        return $this->addCommonParams( $view );
    }

    /**
     * @param string $handle
     * @param string $protocol
     *
     * @return View
     *
     * @throws
     */
    public function routesForProtocol( string $handle, string $protocol ): View
    {
        // get bgp protocol summary
        $view = view('services/lg/routes' )->with([
            'content' => json_decode( $this->lg()->routesForProtocol( $protocol ), false ),
            'source' => 'protocol', 'name' => $protocol
        ]);
        return $this->addCommonParams( $view );
    }

    /**
     * @param string $handle
     * @param string $protocol
     *
     * @return View
     *
     * @throws
     */
    public function routesForExport( string $handle, string $protocol ): View
    {
        // get bgp protocol summary
        $view = view('services/lg/routes' )->with([
            'content'   => json_decode( $this->lg()->routesForExport( $protocol ), false ),
            'source'    => 'export to protocol',
            'name'      => $protocol
        ]);
        return $this->addCommonParams( $view );
    }

    /**
     * @param string $handle
     * @param string $network
     * @param string $mask
     * @param string $protocol
     *
     * @return View
     *
     * @throws
     */
    public function routeProtocol( string $handle, string $network, string $mask, string $protocol ): View
    {
        return view('services/lg/route' )->with([
            'content' => json_decode( $this->lg()->protocolRoute( $protocol, $network, (int)$mask ), false ),
            'source'  => 'protocol',
            'name'    => $protocol,
            'lg'      => $this->lg(),
            'net' => urldecode( $network.'/'.$mask ),
        ]);
    }

    /**
     * @param string $handle
     * @param string $network
     * @param string $mask
     * @param string $table
     *
     * @return View
     *
     * @throws
     */
    public function routeTable( string $handle, string $network, string $mask, string $table ): View
    {
        return view('services/lg/route')->with( [
            'content' => json_decode( $this->lg()->protocolTable( $table, $network, (int)$mask ), false ),
            'source'  => 'table',
            'name'    => $table,
            'lg'      => $this->lg(),
            'net'     => urldecode($network . '/' . $mask),
        ]);
    }

    /**
     * @param string $handle
     * @param string $network
     * @param string $mask
     * @param string $protocol
     *
     * @return View
     *
     * @throws
     */
    public function routeExport( string $handle, string $network, string $mask, string $protocol ): View
    {
        return view('services/lg/route' )->with([
            'content'   => json_decode( $this->lg()->exportRoute( $protocol, $network, (int)$mask ), false ),
            'source'    => 'export',
            'name'      => $protocol,
            'lg'        => $this->lg(),
            'net'       => urldecode( $network . '/' . $mask ),
        ]);
    }

    /**
     * @param string $handle
     *
     * @return View
     *
     * @throws
     */
    public function routeSearch( string $handle ): View
    {
        $view = view('services/lg/route-search' )->with( [
            'content' => json_decode( $this->lg()->symbols(), false ),
        ]);
        return $this->addCommonParams( $view );
    }
}