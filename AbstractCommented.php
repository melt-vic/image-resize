<?php

abstract class Signia_ImageResize_Abstract
{

   protected $srcImage;
   protected $srcImageName;
   protected $srcWidth;
   protected $srcHeight;
   protected $srcRatio;
   protected $destImage;
   protected $destImageName;
   protected $destWidth;
   protected $destHeight;
   protected $destRatio;
   protected $widthMin;
   protected $heightMin;
   protected $widthMax;
   protected $heightMax;
   protected $background;
   protected $bincolor;
   protected $error;
   public $error_message;
   protected $messages = array(
       'src_not_found'      => "El fichero de origen no se encuentra",
       'not_enought_params' => "No se han definido los tamaños de salida",
       'size_values'        => "La imagen es demasiado pequeña para generar las copias",
       'ratio'              => "No se ha podido calcular el ratio destino de la copia"
   );

   public function __construct($srcImageName, $destImageName, $newSize)
   {
      $this->srcImageName   = $srcImageName;
      $this->destImageName  = $destImageName;
      $this->widthMin       = (!isset($newSize['width'])) ? 1 : $newSize['width'];
      $this->heightMin      = (!isset($newSize['height'])) ? 1 : $newSize['height'];
      $this->widthMax       = (!isset($newSize['widthMax']) || $newSize['widthMax'] > 8192) ? 8192 : $newSize['widthMax'];
      $this->heightMax      = (!isset($newSize['heightMax']) || $newSize['heightMax'] > 6144) ? 6144 : $newSize['heightMax'];
      $this->background     = (!isset($newSize['background'])) ? null : $newSize['background'];
      $this->sizeControlled = (!isset($newSize['sizeControlled'])) ? true : $newSize['sizeControlled'];
      if ($this->background) {
         $this->setBincolor();
      }
   }

   public function __destruct()
   {
      if (isset($this->destImage) && is_resource($this->destImage)) {
         imagedestroy($this->destImage);
      }
      if (isset($this->srcImage) && is_resource($this->srcImage)) {
         imagedestroy($this->srcImage);
      }
   }

   protected function resizeImage()
   {
      /*
       * aux1 = coordenada y del punto de origen
       * 
       */
      if (($this->srcRatio) > ($this->destRatio)) {
         if ($this->background) {
            $aux1 = floor($this->srcWidth / $this->destWidth * ($this->destHeight - $this->destWidth / $this->srcWidth * $this->srcHeight));
            $aux2 = floor(($this->destHeight - $this->destWidth / $this->srcWidth * $this->srcHeight) / 2);
            imagecopyresampled($this->destImage, $this->srcImage, 0, 0, 0, -$aux1 / 2, $this->destWidth, $this->destHeight, $this->srcWidth, $this->srcHeight + $aux1);
            $aux3 = imagecolorallocate($this->destImage, $this->bincolor[0], $this->bincolor[1], $this->bincolor[2]);
            imagefilledrectangle($this->destImage, 0, 0, $this->destWidth, $aux2 + 1, $aux3);
            imagefilledrectangle($this->destImage, 0, $this->destHeight - $aux2 - 1, $this->destWidth, $this->destHeight, $aux3);
         } else {
            $this->src2 = imagecreatetruecolor($this->destHeight * ($this->srcWidth / $this->srcHeight), $this->destHeight);
            imagealphablending($this->src2, false);
            imagesavealpha($this->src2, true);
            imagecopyresampled($this->src2, $this->srcImage, 0, 0, 0, 0, $this->destHeight * ($this->srcWidth / $this->srcHeight), $this->destHeight, $this->srcWidth, $this->srcHeight);
            $auxWidth   = $this->destHeight * ($this->srcWidth / $this->srcHeight);
            $auxHeight  = $this->destHeight;
            imagecopyresampled($this->destImage, $this->src2, 0, 0, ($auxWidth - $this->destWidth) / 2, 0, $this->destWidth, $this->destHeight, $this->destWidth, $auxHeight);
         }
      } elseif ($this->srcRatio < ($this->destRatio)) {
         if ($this->background) {
            $aux1 = floor($this->srcHeight / $this->destHeight * ($this->destWidth - $this->destHeight / $this->srcHeight * $this->srcWidth));
            $aux2 = floor(($this->destWidth - $this->destHeight / $this->srcHeight * $this->srcWidth) / 2);
            imagecopyresampled($this->destImage, $this->srcImage, 0, 0, -$aux1 / 2, 0, $this->destWidth, $this->destHeight, $this->srcWidth + $aux1, $this->srcHeight);
            $aux3 = imagecolorallocate($this->destImage, $this->bincolor[0], $this->bincolor[1], $this->bincolor[2]);
            imagefilledrectangle($this->destImage, 0, 0, $aux2 + 1, $this->destHeight, $aux3);
            imagefilledrectangle($this->destImage, $this->destWidth - $aux2 - 1, 0, $this->destWidth, $this->destHeight, $aux3);
         } else {
            $this->src2      = imagecreatetruecolor($this->destWidth, $this->destWidth * $this->srcHeight / $this->srcWidth);
            imagealphablending($this->src2, false);
            imagesavealpha($this->src2, true);
            imagecopyresampled($this->src2, $this->srcImage, 0, 0, 0, 0, $this->destWidth, $this->destWidth * $this->srcHeight / $this->srcWidth, $this->srcWidth, $this->srcHeight);
            $this->srcHeight = ($this->destWidth * $this->srcHeight / $this->srcWidth);
            $this->srcWidth  = $this->destWidth;
            imagecopyresampled($this->destImage, $this->src2, 0, 0, 0, ($this->srcHeight - $this->destHeight) / 2, $this->destWidth, $this->destHeight, $this->srcWidth, $this->destHeight);
         }
      } else {
         imagecopyresampled($this->destImage, $this->srcImage, 0, 0, 0, 0, $this->destWidth, $this->destHeight, $this->srcWidth, $this->srcHeight);
      }
   }

   protected function initCheck()
   {
      if (!file_exists($this->srcImageName)) {
         $this->error         = true;
         $this->error_message = $messages['src_not_found'];

         return false;
      }
      if (!$this->widthMax && !$this->heightMax && !$this->widthMin && !$this->heightMin) {
         $this->error         = true;
         $this->error_message = $messages['not_enought_params'];

         return false;
      }
      if (!is_resource($this->srcImage)) {
         return false;
      }
      $this->srcWidth  = imagesx($this->srcImage);
      $this->srcHeight = imagesy($this->srcImage);
      $this->error     = false;

      return true;
   }

   protected function sizeControl()
   {
      if (!isset($this->background) && $this->sizeControlled) {
         if ($this->srcWidth < $this->widthMin || $this->srcHeight < $this->heightMin) {
            $this->error         = true;
            $this->error_message = $this->messages['size_values'];
            
            return false;
         }
      }

      // Cagadas del programador en $imageType del config_gestorweb
      if ($this->widthMin > $this->widthMax) {
         $this->widthMin = $this->widthMax;
      }
      if ($this->heightMin > $this->heightMax) {
         $this->heightMin = $this->heightMax;
      }
      // Fin cagadas
      $widthAux  = $this->srcWidth;
      $heightAux = $this->srcHeight;
      //Primero recorta por el ancho: (1)
      if ($this->srcWidth > $this->widthMax) {
         $widthAux = $this->widthMax;
         $heightAux *= $this->widthMax / $this->srcWidth;
      }
      /* Después por el alto. Si la altura inicial es inferior a la mínima después 
       * de haber sido recortada heightAux = heightMin y widthAux = ...
       */
      echo "$heightAux|$widthAux";
      if ($heightAux < $this->heightMin) { // (2)
         /* Esto no se va a ejecutar nunca, porque para que ratio * $widthAux < $this->widthMin, ratio debería estar 
          * entre 0 y 1, i.e., heightAux > $this->heightMin y esto nunca pasará porque las entradas que no respetan 
          * heightMin producen error y si entró en el if (1) heigthAux queda reducida y nunca < heightMin
          */
         if ($this->heightMin / $heightAux * $widthAux < $this->widthMin) {
            $widthAux = $this->widthMin;
            // Sinó, ancho adaptado al ratio heightMin / heightAux > widthMax?
         } elseif ($this->heightMin / $heightAux * $widthAux > $this->widthMax) {
            $widthAux = $this->widthMax;
            // Si entra por elseif ($this->srcWidth > $this->widthMax) no hace falta esto, ya la hace.
         } else {
            /* Esto tampoco se ejecuta, porque (2) sólo será cierta si se ha recortado (error en caso contrario).
             * Al recortar, $widthAux = $this->widthMax y heightMin / heightAux >= 1 siempre pues si heightAux > heightMin
             * no se cumple (2) y nunca entra.
             */
            $widthAux *= $this->heightMin / $heightAux;
         }
         $heightAux = $this->heightMin;
         // Sinó, si el alto es superior al máximo, se recorta, volviendo a adaptar el width.
      } elseif ($heightAux > $this->heightMax) { // (3)
         if (($this->heightMax / $heightAux) * $widthAux < $this->widthMin) {
            $widthAux = $this->widthMin;
         } else {
            if (($this->heightMax / $heightAux) * $widthAux > $this->widthMax) {
               /* Esto tampoco se ejecuta nunca pues llegado a este punto withAux <= widthMax y por (3) nunca
                * heightMax / heightAux > 1
                */
               $widthAux = $this->widthMax;
            } else {
               $widthAux *= $this->heightMax / $heightAux;
            }
         }
         $heightAux = $this->heightMax;
      }
      $this->srcRatio   = $this->srcWidth / $this->srcHeight;
      $this->destRatio  = $widthAux / $heightAux;
      $this->destWidth  = (int) $widthAux;
      $this->destHeight = (int) $heightAux;
      // No importa si el escalado no es proporcional pues realmente sólo estamos calculando las coordenadas (x, y) de destino
      $a                = [
          [$widthAux / $this->srcWidth, 0, 0],
          [0, $heightAux / $this->srcHeight, 0],
          [0, 0, 1]
      ];
      $b                = [$this->srcWidth, $this->srcHeight, 1];
      $result           = $this->matrixMultiplication($a, $b);
      Signia_Utils::writeLog('newSize.txt', print_r($a, true));
      Signia_Utils::writeLog('newSize.txt', print_r($result, true));

      return $this;
   }

   private function setBincolor()
   {
      $hexcolor       = str_split($this->background, 2);
      $bincolor[0]    = hexdec('0x{' . $hexcolor[0] . '}');
      $bincolor[1]    = hexdec('0x{' . $hexcolor[1] . '}');
      $bincolor[2]    = hexdec('0x{' . $hexcolor[2] . '}');
      $this->bincolor = $bincolor;

      return $this;
   }

   /**
    * 
    * @param array $a Matriz de escalado 3*3
    * @param array $b Vector 3*1: (ancho, alto, 1)
    * @return array Resultado de multiplicar a*b
    */
   private function matrixMultiplication($a, $b)
   {
      $result = [];

      foreach ($a as $k => $line) {
         foreach ($line as $element) {
            if (!isset($result[$k])) { // Evita el notice cuando el elemento del array no existe.
               $result[$k] = 0;
            }
            $result[$k] += $element * $b[$k];
         }
      }

      return array_map('round', $result); // Devuelve el resultado redondeado.
   }
   /*
    * 
     $a = [
     [0.5714, 0, 0],
     [0, 0.5714, 0],
     [0, 0, 1]
     ];
     $b = [700, 400, 1];

     var_dump(matrixMultiplication($a, $b));
    */
}
