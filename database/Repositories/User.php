<?php

namespace Repositories;

use Auth;

use Entities\{
    User    as UserEntity
};
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

use Doctrine\ORM\EntityRepository;

/**
 * User
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class User extends EntityRepository
{

    /**
     * Get all users as an array. Optionally limited to a given privilege.

     * @param int $priv If not null, limit to given privilege level
     * @return array
     */
    public function asArray( int $priv = null ) {

        return $this->getEntityManager()->createQuery(
                "SELECT u FROM Entities\User u" . ( is_int($priv) ? ' WHERE u.privs = ' . $priv : '' ) )
            ->getArrayResult();
    }

    /**
     * Return an array of users with their last login time ordered from most recent to oldest.
     *
     * As an example, an element of the returned array contains:
     *
     *     [0] => array(6) {
     *         ["attribute"] => string(18) "auth.last_login_at"
     *         ["lastlogin"] => string(10) "1338329771"
     *         ["username"]  => string(4) "auser"
     *         ["email"]     => string(12) "auser@example.com"
     *         ["cust_name"] => string(4) "INEX"
     *         ["cust_id"]   => string(2) "15"
     *     }
     *
     *
     * @param int $limit Set this to limit the results to the last `$limit` users
     * @return array Users with their last login time ordered from most recent to oldest.
     */
    public function getLastLogins( $limit = null )
    {
        $q = $this->getEntityManager()->createQuery(
                "SELECT up.attribute AS attribute, up.value AS lastlogin, u.username AS username,
                        u.email AS email, c.name AS cust_name, c.id AS cust_id, u.id AS user_id
                    FROM \\Entities\\UserPreference up
                        JOIN up.User u
                        JOIN u.Customer c
                    WHERE up.attribute = ?1
                    ORDER BY up.value DESC"
            )
            ->setParameter( 1, 'auth.last_login_at' );
        
        if( $limit != null && is_numeric( $limit ) && $limit > 0 )
            $q->setMaxResults( $limit );
        
        return $q->getScalarResult();
    }

    /**
     * Return an array of users with their last login time ordered from most recent to oldest. (DQL)
     *
     * As an example, an element of the returned array contains:
     *
     *     [0] => array(6) {
     *         ["attribute"] => string(18) "auth.last_login_at"
     *         ["lastlogin"] => string(10) "1338329771"
     *         ["username"]  => string(4) "auser"
     *         ["email"]     => string(12) "auser@example.com"
     *         ["cust_name"] => string(4) "INEX"
     *         ["cust_id"]   => string(2) "15"
     *     }
     *
     * @param \stdClass $feParams
     *
     * @return array Users with their last login time ordered from most recent to oldest.
     */
    public function getLastLoginsForFeList( $feParams )
    {
        $dql = "SELECT  up.attribute AS attribute, 
                        up.value AS lastlogin, 
                        u.username AS username,
                        u.email AS email, 
                        c.name AS cust_name, 
                        c.id AS cust_id, 
                        u.id AS id
                    FROM Entities\\UserPreference up
                        JOIN up.User u
                        JOIN u.Customer c
                    WHERE up.attribute = 'auth.last_login_at'";


        if( isset( $feParams->listOrderBy ) ) {
            $dql .= " ORDER BY " . $feParams->listOrderBy . ' ';
            $dql .= isset( $feParams->listOrderByDir ) ? $feParams->listOrderByDir : 'ASC';
        }

        return $this->getEntityManager()->createQuery( $dql )->getArrayResult();
    }



    /**
     * Return an array of users subscribed (or not) to a given mailing list
     *
     * @param string $list The mailing list handle
     * @param bool $subscribed Set to false to get a list of users not subscribed
     * @param bool $withPassword Include passwords in the query
     * @return array Array of array of emails
     */
    public function getMailingListSubscribers( string $list, bool $subscribed = true, bool $withPassword = true ): array {
        $sql = "SELECT u.email AS email" . ( $withPassword ? ", u.password AS password" : "" ) . "
                    FROM \\Entities\\User u LEFT JOIN u.Preferences up
                    WHERE up.attribute = ?1 AND up.value = ?2
                    ORDER BY email ASC";
        
        return $this->getEntityManager()->createQuery( $sql )
            ->setParameter( 1, "mailinglist.{$list}.subscribed" )
            ->setParameter( 2, $subscribed )
            ->getScalarResult();
    }
    
    
    /**
     * Find all (active) users and arranged them in arrays by the privileges.
     *
     * Returns an array of the form:
     *
     *     [
     *         [3] => [
     *                    [0] => [
     *                               [username] => joe
     *                               [email] => joe@example.com
     *                               [password] => soopersecret
     *                               [privs] => 3
     *                               [custname] => SOME_IXP
     *                           ],
     *                    ...
     *                ],
     *         [2] => [
     *                    ...
     *                ],
     *         [1] => [
     *                    ...
     *                ]
     *     ]
     *
     * @return array As defined above
     */
    public function arrangeByType()
    {
        $users = $this->getEntityManager()->createQuery(
                "SELECT u.username AS username, u.email AS email, u.password AS password, u.privs AS privs,
                        c.name AS custname
                FROM \\Entities\\User u
                    Join u.Customer c
                WHERE
                    u.disabled = 0
                ORDER BY u.privs DESC, u.username ASC"
            )->getArrayResult();

        $arranged = [];
        foreach( $users as $u )
            $arranged[ $u['privs'] ][] = $u;
        
        return $arranged;
    }


    /**
     * Get all Users for listing on the frontend CRUD
     *
     * @see \IXP\Http\Controllers\Doctrine2Frontend
     *
     *
     * @param \stdClass $feParams
     * @param int|null $id
     *
     * @param UserEntity|null $user
     * @return array Array of User (as associated arrays) (or single element if `$id` passed)
     */
    public function getAllForFeList( \stdClass $feParams, int $id = null, UserEntity $user = null )
    {
        $where = false;
        $dql = "SELECT  u.id as id, 
                        u.name AS name,
                        u.username as username, 
                        u.email as email, 
                        u.privs AS privileges,
                        u.created as created, 
                        u.disabled as disabled, 
                        c.id as custid, 
                        c.name as customer,
                        u.lastupdated AS lastupdated
                  FROM Entities\\User u
                  LEFT JOIN u.Customer c
                  WHERE 1 = 1";




        if( $user && $user->isCustAdmin() ) {
            $dql .= " AND u.Customer = " . $user->getCustomer()->getId() . "
                      AND u.privs <= " . UserEntity::AUTH_CUSTUSER;
        }

        if( $id ) {
            $dql .= " AND u.id = " . $id ;
        }

        if( isset( $feParams->listOrderBy ) ) {
            $dql .= " ORDER BY " . $feParams->listOrderBy . ' ';
            $dql .= isset( $feParams->listOrderByDir ) ? $feParams->listOrderByDir : 'ASC';
        }

        return $this->getEntityManager()->createQuery( $dql )->getArrayResult();

    }


    /**
     * Find users by username
     *
     * Will support a username starts / ends with as it uses LIKE
     *
     * @param  string $username The username to search for
     *
     * @return \Entities\User[] Matching users
     */
    public function findByUsername( $username ){
        return $this->getEntityManager()->createQuery(
            "SELECT u
                  FROM \\Entities\\User u
                  WHERE u.username LIKE :username"
        )
            ->setParameter( 'username', $username )
            ->getResult();
    }

    /**
     * Find Users by email
     *
     * @param  string $email The email to search for
     *
     * @return \Entities\User[] Matching users
     */
    public function findByEmail( $email )
    {
        return $this->getEntityManager()->createQuery(
            "SELECT u

                 FROM \\Entities\\User u
      

                 WHERE u.email = :email"
        )
            ->setParameter( 'email', $email )
            ->getResult();
    }
}
