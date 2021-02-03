<?php

/*
 *  LMS version 1.11-git
 *
 *  Copyright (C) 2001-2017 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

/*!
 * \class Kd_tree
 * \brief Class used to find nearest neighbor for point.
 */
class Kd_tree
{

    private $root;
    private $k;

    // using for find nearest neighbor
    private $best;
    private $best_dist;

    public function __construct()
    {
        $this->root = null;

        // implemented only for 2D trees.
        $this->k = 2;
    }

    /*!
     * \brief Insert point to root.
     *
     * \param array $P Point coordinates.
     */
    public function insert(array $P)
    {
        $this->root = $this->_insert($this->root, $P);
    }

    /*!
     * \brief Clear tree.
     */
    public function clear()
    {
        $this->root      = null;
        $this->best      = null;
        $this->best_dist = PHP_INT_MAX;
    }

    /*!
     * \brief Insert point to root.
     *
     * \param  object $root  Root
     * \param  array  $point Point coordinates.
     * \param  int    $cd    Current dimension.
     * \return object        Root
     */
    private function _insert($root, array $P, $cd = 0)
    {
        if ($root == null) {
            return new Kd_node($P);
        }

        // current dimension
        $cd = $cd % $this->k;

        // add point to subtree
        if ($P[$cd] < $root->loc[$cd]) {
            $root->left  = $this->_insert($root->left, $P, $cd+1);
        } else {
            $root->right = $this->_insert($root->right, $P, $cd+1);
        }

        return $root;
    }

    /*!
     * \brief Find nearest neighbor for point.
     *
     * \param  array $P Point coordinates.
     * \return array    Nearest neighobr coordinates.
     */
    public function findNN(array $P)
    {
        // clear values
        $this->best      = null;
        $this->best_dist = PHP_INT_MAX;

        // find nearest neighbor
        $this->_findNN($P, $this->root);

        return $this->best;
    }

    /*!
     * \brief Find nearest neighbor for point.
     *
     * \param  array   $P     Point coordinates
     * \param  kd_node $root
     * \param  int     $cd    Current dimension
     */
    public function _findNN(array $P, $root, $cd = 0)
    {

        // if root is empty or distance to splitting line is higher
        // that current best distance then return
        if ($root == null) {
            return null;
        }

        // current dimension
        $cd = $cd % $this->k;

        // if this point is better than the best then set new champion
        $dist = $this->getDist($P, $root->loc);

        if ($dist < $this->best_dist) {
            $this->best      = $root->loc;
            $this->best_dist = $dist;
        }

        // visit subtrees is most promising order
        if ($P[$cd] < $root->loc[$cd]) {
            $this->_findNN($P, $root->left, $cd + 1);
            if ($this->getDist($P, $this->getLineClosestPoint($P, $root->loc, $cd + 1)) < $this->best_dist) {
                $this->_findNN($P, $root->right, $cd + 1);
            }
        } else {
            $this->_findNN($P, $root->right, $cd + 1);
            if ($this->getDist($P, $this->getLineClosestPoint($P, $root->loc, $cd + 1)) < $this->best_dist) {
                $this->_findNN($P, $root->left, $cd + 1);
            }
        }
    }

    /*!
     * \brief Method returns distance between points in K-dimension tree.
     *
     * \param  array   $P1   First point coordinates.
     * \param  array   $P2   Second point coordinates.
     * \return float
     * \return boolean false Not supported dimension value.
     */
    public function getDist(array $P1, array $P2)
    {

        switch ($this->k) {
            // compare 2D points
            case 2:
                return sqrt(pow($P2[0]-$P1[0], 2) + pow($P2[1]-$P1[1], 2));
            break;

            default:
                throw new Exception('Unsupported dimension number exception.');
        }
    }

    /*!
     * \brief Method find closest point on splitting line for searched point.
     *
     * \param  array $P        Searched point.
     * \param  array $root_loc Any point on splitting line.
     * \param  int   $cd       Current dimension.
     * \return array
     */
    private function getLineClosestPoint($P, $root_loc, $cd = 0)
    {
        $cd = $cd % $this->k;
        $root_loc[$cd] = $P[$cd];

        return $root_loc;
    }
}
