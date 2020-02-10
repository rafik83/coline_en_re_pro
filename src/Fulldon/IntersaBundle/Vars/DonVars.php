<?php
/*
 * This file is part of the fulldon project
 *
 * (c) SAMI BOUSSACSOU <boussacsou@intersa.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Fulldon\IntersaBundle\Vars;

final class DonVars
{
const _BC_ = 'BC';
const _CS_ = 'CS';
const _ESPECES_ = 'ESPECES';
const _PA_ = 'PA';
const _VIREMENT_ = 'VIREMENT';

const _CHEQUE_MODE_= 'cheque';
const _VIREMENT_MODE_ = 'virement';
const _ESPECE_MODE_ = 'espece';
const _PA_MODE_ = 'pa';
const _CB_MODE_ = 'cb';
const _STATUT_ATTENTE_ = 1;
const _STATUT_TRAITEMENT_PAIEMENT_ = 2;
const _STATUT_DON_VALIDE_ = 3;
const _STATUT_DON_ANNULE_ = 4;
const _CODE_DON_VALIDE_ = 'don_valide';
const _COURRIER_ARRET_PA_ = 'arret_pa';
const _COURRIER_MAJ_COB_ = 'maj_cob';
const _COURRIER_MAJ_MO_PA_ = 'maj_mo_pa';
const _COURRIER_MAJ_PE_PA_ = 'maj_pe_pa';
const _COURRIER_MAJ_ADRESSE_ = 'maj_adresse';
const _COURRIER_CREATE_PA_ = 'create_pa';
const _COM_COURRIER_ = 'courrier';
const _COM_EMAIL_ = 'email';
const _COM_SMS_ = 'sms';

private $special_mode = array('ESPECES','VIREMENT');
}