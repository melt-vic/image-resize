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
   protected $aMessages = array(
       'src_not_found'      => "El fichero de origen no se encuentra",
       'not_enought_params' => "No se han definido los tamaños de salida",
       'size_values'        => "La imagen es demasiado pequeña para generar las copias",
       'ratio'              => "No se ha podido calcular el ratio destino de la copia"
   );
   protected $aResult   = [];

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

   /**
    * @todo Puede que alphablending sea para imágenes transparentes (png y gif).
    * imagesavealpha parece que es para los png transparentes.
    * Como el escalado es diferente cuando hay borde y withMax = withMin y heightMax = heightMin y se sube una imagen de
    * diferente ratio, en sizeControl() debería comprobarse en sizeControl()
    */
   protected function resizeImage()
   {
      if ($this->background) { // Se debería controlar también el ratio
         Signia_Utils::writeLog('newSize.txt', 'background');
         $canvas          = imagecreatetruecolor($this->widthMax, $this->heightMax);
         $color           = imagecolorallocate($canvas, $this->bincolor[0], $this->bincolor[1], $this->bincolor[2]);
         imagefill($canvas, 0, 0, $color);
         // Este código está funcionando, el problema es que si subo algo más grande de 1000*400 lo recorta a 1000*400 y
         // entonces sobreescribe todo el rectángulo rojo.
         imagecopyresampled($this->destImage, $this->srcImage, 0, 0, 0, 0, $this->aResult[0], $this->aResult[1], $this->srcWidth, $this->srcHeight);
         imagecopy($canvas, $this->destImage, round(($this->widthMax - $this->aResult[0]) / 2), 0, 0, 0, $this->aResult[0], $this->aResult[1]);
         $this->destImage = $canvas;
      } else {
         if ($this->srcRatio > $this->destRatio) { // Este tampoco funciona. Calcula correctamente el tamaño de la imagen de destino pero la deforma (supongo que no calcula bien las coordenadas del origen a coger)
            imagealphablending($this->destImage, false);
            imagesavealpha($this->destImage, true);
            imagecopyresampled($this->destImage, $this->srcImage, 0, 0, 0, 0, $this->aResult[0], $this->aResult[1], $this->srcWidth, $this->srcHeight);
         } else { // Esta es la parte que no funciona
            $inverseSrcRatio = $this->srcHeight / $this->srcWidth;
            $canvas          = imagecreatetruecolor($this->destWidth, $this->destWidth * $inverseSrcRatio);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopyresampled($canvas, $this->srcImage, 0, 0, 0, 0, $this->destWidth, $this->destWidth * $inverseSrcRatio, $this->srcWidth, $this->srcHeight);
            $this->srcHeight = $this->destWidth * $inverseSrcRatio;
            $this->srcWidth  = $this->destWidth;
            imagecopyresampled($this->destImage, $canvas, 0, 0, 0, ($this->srcHeight - $this->destHeight) / 2, $this->destWidth, $this->destHeight, $this->srcWidth, $this->destHeight);
         }
      }
   }

   protected function initCheck()
   {
      if (!file_exists($this->srcImageName)) {
         $this->error         = true;
         $this->error_message = $this->aMessages['src_not_found'];

         return false;
      }
      if (!$this->widthMax && !$this->heightMax && !$this->widthMin && !$this->heightMin) {
         $this->error         = true;
         $this->error_message = $this->aMessages['not_enought_params'];

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
            $this->error_message = $this->aMessages['size_values'];

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
      //Primero recorta por el ancho:
      if ($this->srcWidth > $this->widthMax) {
         $widthAux  = $this->widthMax;
         $heightAux *= $this->widthMax / $this->srcWidth;
      }
      /* Después por el alto. Si la altura inicial es inferior a la mínima después 
       * de haber sido recortada heightAux = heightMin y widthAux = ...
       */
      if ($heightAux < $this->heightMin) {
         $heightAux = $this->heightMin;
         // Sinó, si el alto es superior al máximo, se recorta, volviendo a adaptar el width.
      } elseif ($heightAux > $this->heightMax) {
         if (($this->heightMax / $heightAux) * $widthAux < $this->widthMin) { // Siempre >= 1
            $widthAux = $this->widthMin;
         } else {
            $widthAux *= $this->heightMax / $heightAux;
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
      /* El factor de escalado debe ser otro
        if ($this->srcRatio > $this->destRatio) {
        $b                = [$this->srcWidth, $this->srcHeight, 1];
        }else{
        $b                = [$this->srcHeight, $this->srcWidth, 1];
        }
       */
      $b                = [$this->srcWidth, $this->srcHeight, 1];
      $this->aResult    = $this->matrixMultiplication($a, $b);
      Signia_Utils::writeLog('newSize.txt', 'destRatio=' . $this->destRatio);
      Signia_Utils::writeLog('newSize.txt', 'srcRatio=' . $this->srcRatio);
      Signia_Utils::writeLog('newSize.txt', print_r($a, true));
      Signia_Utils::writeLog('newSize.txt', print_r($this->aResult, true));

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
      $this->aResult = []; // Tal vez no sea necesario inicializar. Ver cuando también se creen los tamaños cms y x.

      foreach ($a as $k => $line) {
         foreach ($line as $element) {
            if (!isset($this->aResult[$k])) { // Evita el notice cuando el elemento del array no existe.
               $this->aResult[$k] = 0;
            }
            $this->aResult[$k] += $element * $b[$k];
         }
      }

      return array_map('round', $this->aResult); // Devuelve el resultado redondeado.
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