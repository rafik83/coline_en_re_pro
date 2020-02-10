<?php

namespace Fulldon\DonateurBundle\Service;
//header("Content-type: image/png");
/**

 * @desc Classe de generation de code barres 2D DataMatrix. Actuellement, seul le mode ASCII est supporte, ainsi que les plus patites tailles de matrices. Je travaille en ce moment a l'implementation d'autres modes - Ce code est sous liscence GNU LGPL. Vous trouverez les conditions d'utilisations complètes dans l'archive ou sur le site www.gnu.org . L'UTILISATION DE CE CODE OU D'UNE PARTIE DE CE CODE DANS LE CADRE D'UN PROJET NON LIBRE EST STRICTEMENT INTERDITE. Vous pourrez trouver la référence iso 16044 à l'adresse suivante: www.openbio.fr/pdf/iso16044.pdf

 * @brief Classe de generation de codes barres 2D DataMatrix

 * @author M. GEORGES alias TorTukiTu

 * @date 21/08/2010

 */

//Definition des CodeWords speciaux

define("PAD", 129);

define("C40", 230);

define("BASE64", 231);

define("FNC1", 232);

define("STRUCTURE_APPEND",233);

define("READER_PORGRAMMING", 234);

define("UPPER_SHIFT",235);

define("05MACRO", 236);

define("06MACRO", 237);

define("ANSIX12", 238);

define("TEXT", 239);

define("EDIFACT", 240);

define("ECI", 241);

define("C40SHIFT1", 0);

define("C40SHIFT2", 1);

define("C40SHIFT3", 2);



class Datamatrix{





    // Contient la taille approximativement voulue de l'image.

    private $approx_size;

    // Rapport multiplicatif permettant d'approcher au maximum la taille voulue

    private $resizingFactor;

    // Contient notre image

    private $im;

    //Contient notre chaine a encoder

    private $strToEncode;

    // Contient le nombre de lignes de la zone de donnees

    private $nrow;

    // Contient le nombre de colonnes de la zone de donnees

    private $ncol;

    // Contient la matrice de 0/1 pour tracer le code barre

    private $barcode;

    // Une matrice de donnees utilises lors de calculs internes

    private $dataArray;

    // Contient les donnes sur la taille de la matrice en fonction de la taille des donnees

    private $squareMatrixSizes=array('Alphanum' => array('Rows', 'Cols', 'Size', 'No', 'mappSize', 'Total_Data', 'Total_Error', 'RS_data', 'RS_error', 'interblocks', 'Num', 'Alphanum', 'Byte', 'prcent_cod_corr', 'max_xorr_err'),

        3 => array(10,10,8,1,8,3,5,3,5,1,6,3,1,62.5,'2/0'),

        6 => array(12,12,10,1,10,5,7,5,7,1,10,6,3,58.3,'3/0'),

        10 => array(14,14,12,1,12,8,10,8,10,1,16,10,6,55.6,'5/7'),

        16 => array(16,16,14,1,14,12,12,12,12,1,24,16,10,50,'5/9'),

        25 => array(18,18,16,1,16,18,14,14,18,14,1,36,25,16,43.8,'7/11'),

        31 => array(20,20,18,1,18,22,18,22,18,1,44,31,20,45,'9/15'),

        43 => array(22,22,20,1,20,30,20,30,20,1,60,43,28,40,'10/17'),

        52 => array(24,24,22,1,22,36,24,36,24,1,72,52,34,40,'12/21'),

        64 => array(26,26,24,1,24,44,28,44,28,1,88,64,42,38.9,'14/25'),

        91 => array(32,32,14,4,28,62,36,62,36,1,124,91,60,36.7,'18/33'),

        127 => array(36,36,16,4,32,86,42,86,42,1,172,127,84,32.8,'21/39'),

        169 => array(40,40,18,4,36,114,48,114,48,1,228,169,112,29.6,'24/45'),

        214 => array(44,44,20,4,40,144,56,144,56,1,288,214,142,28,'28/53'),

        259 => array(48,48,22,4,44,174,68,174,68,1,348,259,172,28.1,'34/65'),

        304 => array(52,52,24,4,48,204,84,102,42,2,408,304,202,29.2,'42/78'),

        418 => array(64,64,14,16,56,280,112,140,56,2,560,418,277,28.6,'56/106'),

        550 => array(72,72,16,16,64,368,144,925,36,4,736,550,365,28.1,'72/132'),

        682 => array(80,80,18,16,72,456,192,114,48,4,912,682,453,28.6,'96/180'),

        862 => array(88,88,20,16,80,576,224,144,56,4,1152,862,573,28,'112/212'),

        1042 => array(96,96,22,16,88,696,272,174,68,4,1392,1042,693,28.1,'136/260'),

        1222 => array(104,104,24,16,96,816,336,136,56,6,1632,1222,813,29.2,'168/318'),

        1573 => array(120,120,18,36,108,1050,408,175,68,6,2100,1573,1047,28,'204/390'),

        1954 => array(132,132,20,36,120,1304,496,163,62,8,2608,1954,1301,27.6,'248/472'),

        1555 => array(144,144,22,36,132,1558,620,156,62,8,3116,2335,1555,28.5,'310/590')

    );



    // C40 encodation sets

    private $C40EncodationSet0=array('','','', ' ', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'

    , 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'

    );



    private $C40EncodationSet1=array('NUL', 'SOH', 'STX',

        'STX', 'ETX', 'EQT', 'ENQ',

        'ACK', 'BEL', 'BS',

        'HT', 'LF', 'VT', 'FF',

        'CR', 'SO', 'SI', 'DLE',

        'DC1', 'DC2', 'DC3', 'DC4',

        'NAK', 'SYN', 'ETB', 'CAN',

        'EM', 'SUB', 'ESC', 'FS',

        'GS', 'RS', 'US'

    );



    private $C40EncodationSet2=array(

        '!' , '"', '#', '$',

        '%', '&', '\'', '(',

        ')', '*', '+', ',',

        '-', '.', '/', ':',

        ';', '<', '=', '>',

        '?', '@', '[', '\\',

        ']', '^', '_'

    );



    //TODO: MANQUE LE DEL: 127

    private $C40EncodationSet3=array('\'', 'a', 'b', 'c',

        'd', 'e', 'f', 'g',

        'h', 'i', 'j', 'k',

        'l', 'm', 'n', 'o',

        'p', 'q', 'r', 's',

        't', 'u', 'v', 'w',

        'x', 'y', 'z', '{' ,

        '|', '}', '~'

    );



    // Dans size se trouve les donnes sur la bonne taille de matrice pour notre chaine a encoder

    private $size;



    // Contient les valeurs (codewords) de notre chaine encodee en mode ASCII

    private $CWTextASCII=array();



    // Un array utilisé dans certains traitements internes

    private $interLeavedWords=array();



    // Constructeur de la classe, prend la chaine a encoder en parametre

    public function __construct(){



    }


    public function setStrToEncode($str) {
        $this->strToEncode = $str;
        $this->C40EncodationSet0=array_flip($this->C40EncodationSet0);

        $this->C40EncodationSet1=array_flip($this->C40EncodationSet1);

        $this->C40EncodationSet2=array_flip($this->C40EncodationSet2);

        $this->C40EncodationSet3=array_flip($this->C40EncodationSet3);

        $this->dataArray=array();

        $this->barcode=array();


        //echo $strToEncode.'<pre>';

        $this->getStringValue();

        $this->chooseMatrixSize();

        //$this->encodeC40();

        $this->addPadCWs();

        //print_r($this->CWTextASCII);

        // TODO: Check reed-8solomon

        $this->CWTextASCII = $this->reedSolomon($this->CWTextASCII,

            $this->countNotNull($this->CWTextASCII),

            $this->size[6],

            pow(2,8),

            301

        );

        array_pop($this->CWTextASCII);

        //print_r($this->CWTextASCII);

        //$this->addPadCWs();

        $this->interlaceBlocks();



        $this->setMatrix();

        // FAUX CAR ON DOIT ICI UTILISER LES CODEWORDS AU LIEU DE LA CHIANE DE DEPARD

        //$this->splitInInterleavedWords();

        //print_r($this->squareMatrixSizes);

        //echo "</pre>";
    }
    // Construction de la matrice, positionnement des CodeWords dans cette matrice

    private function setMatrix(){

        $this->nrow=$this->size[4];

        $this->ncol=$this->size[4];

        $this->ecc200();

        for($x=0; $x<$this->nrow; $x++){

            $this->barcode[$x]=array();

        }

        for($x=0; $x<$this->nrow; $x++){

            //echo "<br> [$x] ";

            for($y=0; $y<$this->ncol; $y++){

                $z=$this->dataArray[$x*$this->ncol+$y];

                //echo " $z ";

                if($z==0){

                    $this->barcode[$x][$y]=0;

                }elseif($z==1){

                    $this->barcode[$x][$y]=1;

                }else{

                    $z=$z-10;

                    $number=floor($z/10);

                    $octetNumber=floor(($z)%10);

                    $numberString=$this->intToBin($this->CWTextASCII[$number]);

                    @$this->barcode[$x][$y]=($numberString[$octetNumber-1]);

                }

            }

        }

    }



    // Compte les elements non égaux a zero d'un array

    private function countNotNull($array){

        $count=0;

        foreach($array as $elem){

            $tmp = ($elem==0) ? 0 : 1;

            $count += $tmp;

        }

        return $count;

    }





    // FONCTION D'interlacage des mots

    private function interlaceBlocks(){

        $decl=$this->size[3];

        $localBlocks=array();

        for($j=0; $j<$decl; $j++){

            $localBlocks[$j]=array();

        }

        //echo "NUMBER OF WORDS=$decl<br>";

        //echo "SIZE=".$this->size[4]."<br>";

        for($i=0; $i<($this->size[4]*$this->size[4]); $i+=$decl){

            for($j=0; $j<$decl; $j++){

                //echo "DATAARRAY[$i + $j] = ".$this->dataArray[$i + $j]."<br>";

                @array_unshift($localBlocks[$j], $this->dataArray[$i+$j]);

            }

        }

        for($j=0; $j<$decl; $j++){

            //echo "BLOCK [$j] == ";

            //print_r($localBlocks[$j]);

        }

    }



    private function splitInInterleavedWords(){

        $numebrOfWords=$this->size[9];

        for($i=0; $i<$numebrOfWords; $i++){

            $interLeavedWords[$i]=substr($this->strToEncode, $i, strlen($this->strToEncode)-$numebrOfWords+$i);

        }

    }



    //Convertit un decimal en binaire

    private function intToBin($number){

        $tmp = decbin($number);

        $zero="00000000";

        return substr($zero,0, 8-strlen($tmp)).$tmp;

    }



    // Permer de determiner la taille de la matrice suivant le nombre de CodeWords a encoder

    // La taille minimale possible pour le nombre de mots cles correspondants a nos donnees est

    // toujours choisie

    // cf. ISO 16044

    private function chooseMatrixSize(){

        $size=count($this->CWTextASCII);

        $isSet=false;

        $this->size=false;

        $keys=array_keys($this->squareMatrixSizes);

        sort($keys);

        $i=0;

        while($this->size==false){

            if(($size <= $this->squareMatrixSizes[$keys[$i]][5]) and is_numeric($this->squareMatrixSizes[$keys[$i]][5])){

                $this->size=$this->squareMatrixSizes[$keys[$i]];

            }

            $i++;

        }

    }



    // Retourne une suite de valeurs correspondant a notre chaine selon le codage ASCII

    // tel que defini dans l'iso 16044

    private function getStringValue(){

        $i=0;

        for($i=0; $i<strlen($this->strToEncode); $i++){

            $val=$this->getCharValue($i);

            if($val>=130){

                $i++;

            }

            array_push($this->CWTextASCII, $val);

        }

    }



    // Encodage C40 tel que defini dans l'iso 16044

    // TODO: fonction a terminer.

    private function encodeC40(){

        $i=0;

        array_push($this->CWTextASCII, C40);

        for($i=0; $i<strlen($this->strToEncode); $i+=3){

            $val=$this->getC40Value($i);

            array_push($this->CWTextASCII, $val[0]);

            array_push($this->CWTextASCII, $val[1]);

        }

    }



    // TODO: suite du codage en C40

    private function getC40Value($pos){

        // TODO: Tenir compte des regles supplementaires pour le charset C40

        echo $this->C40EncodationSet0[$this->strToEncode[$pos]];

        $value=(1600*$this->C40EncodationSet0[$this->strToEncode[$pos]])+(40*$this->C40EncodationSet0[$this->strToEncode[$pos+1]])+$this->C40EncodationSet0[$this->strToEncode[$pos+2]]+1;

        $val=array();

        $val[0]=floor($value/256);

        $val[1]=$value%256;

        return $val;

    }



    //TODO: Ajouter le mode d'ncodage TEXT

    private function encodeTEXT(){



    }





    // Retourne la valeur a encoder d'un caractere ou d'un decimal

    private function getCharValue($position){

        if(is_numeric($this->strToEncode[$position]) and is_numeric($this->strToEncode[$position+1])){

            $num=$this->strToEncode[$position]*10 + $this->strToEncode[$position+1];

            return $num+130;

        }else{

            return ord($this->strToEncode[$position])+1;

        }



    }



    // Ajoute des padword tant que la matrice est pas completement pleine

    // cf. iso 16044

    private function addPadCWs(){

        $sizeOfMatrix=$this->size[5];

        if(count($this->CWTextASCII) <  $sizeOfMatrix){

            array_push($this->CWTextASCII, PAD);

            while(count($this->CWTextASCII) <  $sizeOfMatrix){

                array_push($this->CWTextASCII, $this->state_253(PAD, count($this->CWTextASCII)));

            }

        }

    }



    //Genere un pad word aleatoire selon l'algorithme 253 state tel que defini dans

    //l'iso 16044

    private function state_253($pad_codeword_value, $pad_codeword_position){

        $pseudo_random_number = ((149*$pad_codeword_position)%253)+1;

        $temp_variable = $pad_codeword_value + $pseudo_random_number;

        if($temp_variable<=254){

            return $temp_variable;

        }else{

            return $temp_variable-254;

        }

    }



    //Algorithme de correction d'erreur Reed-solomon cf. iso 16044

    private function reedSolomon($wd, $nd, $nc, $gf, $pp){

        $c=array();

        $log = array();

        $alog = array();

        $log[0]=1-$gf;

        $alog[0]=1;

        for($i=1; $i<$gf; $i++){

            $alog[$i]=$alog[$i-1]*2;

            if($alog[$i] >= $gf){

                $alog[$i] = $alog[$i] ^ $pp;

            }

            $log[$alog[$i]] = $i;

        }



        for($i=1; $i<=$nc; $i++){

            $c[$i] = 0;

        }

        $c[0] = 1;

        for($i=1; $i<=$nc; $i++){

            $c[$i]=$c[$i-1];

            for($j=($i-1) ; $j>=1; $j--){

                $c[$j]=($c[$j-1]) ^ ($this->prod($c[$j], $alog[$i], $log, $alog, $gf));

            }

            $c[0] = $this->prod($c[0], $alog[$i], $log, $alog, $gf);

        }



        for($i=$nd; $i<=($nd+$nc); $i++){

            $wd[$i]=0;

        }



        for($i=0; $i<$nd; $i++){

            $k=$wd[$nd]^$wd[$i];

            for($j=0; $j<$nc; $j++){

                $wd[$nd+$j]=$wd[$nd+$j+1] ^ $this->prod($k, $c[$nc-$j-1], $log, $alog, $gf);

            }

        }

        return $wd;

    }



    private function prod($x, $y , $log, $alog, $gf){

        if( ($x==0) || ($y==0) ){

            return 0;

        }else{

            return $alog[($log[$x]+$log[$y])%($gf-1)];

        }

    }



    // Fonction permettant de tracer notre code barre

    public function stroke($approx_size,$name){

        $this->approx_size = $approx_size;

        $this->makeFullImageBody();

        $this->fulfillImage2();

        ImagePNG($this->im, "./barecode/".$name.".png");

    }



    // Permet de tracer le corps du code barre

    private function makeFullImageBody(){

        $this->getResizingFactor();

        $this->im = imagecreatetruecolor($this->size[0]*$this->resizingFactor, $this->size[1]*$this->resizingFactor);

        $this->black = imagecolorallocate($this->im, 0, 0, 0);

        $this->white = imagecolorallocate($this->im, 255, 255, 255);

        $this->makeBorders();

    }



    // Fonction de calcul du facteur d'agrandissement

    private function getResizingFactor(){

        $this->resizingFactor=floor($this->approx_size/$this->size[0]);

    }



    // Trace les bordures du code barre

    private function makeBorders(){

        ImageFillToBorder($this->im, 0, 0, $this->white, $this->white);

        $rac_tmp=floor(sqrt($this->size[3]));

        for($i=0; $i<$rac_tmp; $i++){

            for($j=0; $j<$rac_tmp; $j++){

                $this->traceMatrixFulfill($this->size[2], $i*($this->size[2]+2), $j*($this->size[2]+2));

            }

        }

    }



    // Remplis le corps du code barre

    private function traceMatrixFulfill($sizeOfData, $posX, $posY){

        imagefilledrectangle($this->im,

            $posX*$this->resizingFactor,

            $posY*$this->resizingFactor,

            ($posX+1)*$this->resizingFactor-1,

            ($posY+$sizeOfData+1)*$this->resizingFactor,

            $this->black

        );



        imagefilledrectangle(

            $this->im,

            $posX*$this->resizingFactor,

            ($posY+$sizeOfData+1)*$this->resizingFactor,

            ($posX+$sizeOfData+2)*$this->resizingFactor,

            ($posY+$sizeOfData+1+1)*$this->resizingFactor-1,

            $this->black

        );

        for($i=1; $i<=$sizeOfData; $i+=2){

            $this->imagesetpixel_replace($this->im, $posX+$sizeOfData+1,$posY+$i, $this->black);

            $this->imagesetpixel_replace($this->im, $posX+$i+1,$posY, $this->black);

        }

    }



    // Methode de remplacement pour imagesetpixel

    private function imagesetpixel_replace($image, $posX, $posY, $color){

        imagefilledrectangle($image,

            $posX*$this->resizingFactor,

            $posY*$this->resizingFactor,

            ($posX+1)*$this->resizingFactor-1,

            ($posY+1)*$this->resizingFactor-1,

            $color

        );

    }





    // Fonction remplissant le centre du code barre

    private function fulfillImage2(){

        $dataX=0;

        $dataY=0;

        $tracePosX=0;

        $tracePosY=0;

        for($y=0; $y<$this->nrow; $y++){

            $dataX=0;

            $tracePosX=0;

            $tracePosY++;

            if($dataY==$this->size[2]){

                $dataY=0;

                $tracePosY+=2;

            }

            $dataY++;

            for($x=0; $x<$this->ncol; $x++){

                $tracePosX++;

                if($dataX==$this->size[2]){

                    $dataX=0;

                    $tracePosX+=2;

                }

                $dataX++;

                if($this->barcode[$y][$x]==1){

                    $this->imagesetpixel_replace($this->im, $tracePosX, $tracePosY, $this->black);

                }

            }

        }

    }



    // Les fonctions ci dessous correspondent a l'algorithme de positionnement

    // des blocs tels que defini dans l'iso 16044

    private function module($row, $col, $chr, $bit){

        if($row<0){

            $row+=$this->nrow;

            $col += 4-(($this->nrow+4)%8);

        }

        if($col<0){

            $col+=$this->ncol;

            $row+=4-(($this->ncol+4)%8);

        }

        $this->dataArray[$row*$this->ncol+$col]=10*$chr+$bit;

    }



    private function utah($row, $col, $chr){

        $this->module($row-2, $col-2, $chr, 1);

        $this->module($row-2, $col-1, $chr, 2);

        $this->module($row-1, $col-2, $chr, 3);

        $this->module($row-1, $col-1, $chr, 4);

        $this->module($row-1, $col, $chr, 5);

        $this->module($row, $col-2, $chr, 6);

        $this->module($row, $col-1, $chr, 7);

        $this->module($row, $col, $chr, 8);

    }



    private function corner1($chr){

        $this->module($this->nrow-1, 0, $chr, 1);

        $this->module($this->nrow-1, 1, $chr, 2);

        $this->module($this->nrow-1, 2, $chr, 3);

        $this->module(0, $this->ncol-2, $chr, 4);

        $this->module(0, $this->ncol-1, $chr, 5);

        $this->module(1, $this->ncol-1, $chr, 6);

        $this->module(2, $this->ncol-1, $chr, 7);

        $this->module(3, $this->ncol-1, $chr, 8);

    }



    private function corner2($chr){

        $this->module($this->nrow-3, 0, $chr, 1);

        $this->module($this->nrow-2, 0, $chr, 2);

        $this->module($this->nrow-1, 0, $chr, 3);

        $this->module(0, $this->ncol-4, $chr, 4);

        $this->module(0, $this->ncol-3, $chr, 5);

        $this->module(0, $this->ncol-2, $chr, 6);

        $this->module(0, $this->ncol-1, $chr, 7);

        $this->module(1, $this->ncol-1, $chr, 8);

    }



    private function corner3($chr){

        $this->module($this->nrow-3, 0, $chr, 1);

        $this->module($this->nrow-2, 0, $chr, 2);

        $this->module($this->nrow-1, 0, $chr, 3);

        $this->module(0, $this->ncol-2, $chr, 4);

        $this->module(0, $this->ncol-1, $chr, 5);

        $this->module(1, $this->ncol-1, $chr, 6);

        $this->module(2, $this->ncol-1, $chr, 7);

        $this->module(3, $this->ncol-1, $chr, 8);

    }



    private function corner4($chr){

        $this->module($this->nrow-1, 0, $chr, 1);

        $this->module($this->nrow-1, $this->ncol-1, $chr, 2);

        $this->module(0, $this->ncol-3, $chr, 3);

        $this->module(0, $this->ncol-2, $chr, 4);

        $this->module(0, $this->ncol-1, $chr, 5);

        $this->module(1, $this->ncol-3, $chr, 6);

        $this->module(1, $this->ncol-2, $chr, 7);

        $this->module(1, $this->ncol-1, $chr, 8);

    }



    private function ecc200(){

        for($row=0; $row<$this->nrow; $row++){

            for($col=0; $col<$this->ncol; $col++){

                $this->dataArray[$row*$this->ncol+$col]=0;

            }

        }

        $chr=1;

        $row=4;

        $col=0;

        do{

            (($row==$this->nrow) && ($col==0)) ? $this->corner1($chr++) : NULL ;

            (($row==$this->nrow-2) && ($col==0) && (($this->ncol%4)!=0)) ? $this->corner2($chr++) : NULL;

            (($row==$this->nrow-2) && ($col==0) && (($this->ncol%8)==4)) ? $this->corner3($chr++) : NULL;

            (($row==$this->nrow+4) && ($col==2) && (($this->ncol%8)==0)) ? $this->corner4($chr++) : NULL;

            do{

                if(($row < $this->nrow) && ($col>=0) && ($this->dataArray[$row*$this->ncol+$col]==0)){

                    $this->utah($row, $col, $chr++);

                }

                $row -=2;

                $col +=2;

            }while(($row >= 0) && ($col < $this->ncol));

            $row +=1;

            $col +=3;

            do{

                if(($row >= 0) && ($col < $this->ncol) && ($this->dataArray[$row*$this->ncol+$col]==0)){

                    $this->utah($row, $col, $chr++);

                }

                $row+=2;

                $col-=2;

            }while(($row < $this->nrow) && ($col >= 0));

            $row +=3;

            $col +=1;

        }while(($row < $this->nrow) || ($col < $this->ncol));

        if($this->dataArray[$this->nrow*$this->ncol-1]==0){

            $this->dataArray[$this->nrow*$this->ncol-1] = $this->dataArray[$this->nrow*$this->ncol-2] = 1;

        }

    }





    public function __destruct(){

    }



}

	



	