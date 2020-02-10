<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$dns = 'mysql:host=172.99.0.33;dbname=fulldonTemp3';
$utilisateur = 'root';
$motDePasse = '33Dx8014';
$con_fd2 = new PDO( $dns, $utilisateur, $motDePasse );

$sql =  'select * from don where ispa=true';
$rows_dons = $con_fd2->query($sql);
