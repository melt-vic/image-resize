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

   protected function resizeImageWithBackground()
   {
      if ($this->srcRatio > $this->destRatio) {
         $aux1 = floor($this->srcWidth / $this->destWidth * ($this->destHeight - $this->destWidth / $this->srcWidth * $this->srcHeight));
         $aux2 = floor(($this->destHeight - $this->destWidth / $this->srcWidth * $this->srcHeight) / 2);
         imagecopyresampled($this->destImage, $this->srcImage, 0, 0, 0, -$aux1 / 2, $this->destWidth, $this->destHeight, $this->srcWidth, $this->srcHeight + $aux1);
         $aux3 = imagecolorallocate($this->destImage, $this->bincolor[0], $this->bincolor[1], $this->bincolor[2]);
         imagefilledrectangle($this->destImage, 0, 0, $this->destWidth, $aux2 + 1, $aux3);
         imagefilledrectangle($this->destImage, 0, $this->destHeight - $aux2 - 1, $this->destWidth, $this->destHeight, $aux3);
      } elseif ($this->srcRatio < $this->destRatio) {
         $aux1 = floor($this->srcHeight / $this->destHeight * ($this->destWidth - $this->destHeight / $this->srcHeight * $this->srcWidth));
         $aux2 = floor(($this->destWidth - $this->destHeight / $this->srcHeight * $this->srcWidth) / 2);
         imagecopyresampled($this->destImage, $this->srcImage, 0, 0, -$aux1 / 2, 0, $this->destWidth, $this->destHeight, $this->srcWidth + $aux1, $this->srcHeight);
         $aux3 = imagecolorallocate($this->destImage, $this->bincolor[0], $this->bincolor[1], $this->bincolor[2]);
         imagefilledrectangle($this->destImage, 0, 0, $aux2 + 1, $this->destHeight, $aux3);
         imagefilledrectangle($this->destImage, $this->destWidth - $aux2 - 1, 0, $this->destWidth, $this->destHeight, $aux3);
      }
   }

   /**
    * @param boolean $transparent Para preservar transparencia (imágenes gif y png)
    */
   protected function resizeImage($transparent = false)
   {
      if ($this->srcRatio > $this->destRatio) { // En el original hay más width por cada píxel de height.
         $canvas = imagecreatetruecolor($this->destHeight * $this->srcRatio, $this->destHeight);
         if ($transparent) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
         }
         $destWidth = $this->destHeight * $this->srcRatio;
         // $canvas contendrá la imagen original con el height del destino y el width recortado proporcionalmente según srcRatio:
         imagecopyresampled($canvas, $this->srcImage, 0, 0, 0, 0, $destWidth, $this->destHeight, $this->srcWidth, $this->srcHeight);
         $auxWidth  = $this->destHeight * $this->srcRatio; // $canvas no es recortada desde x = 0, es decir, no se recorta sólo por la derecha sino también por la izquierda
         imagecopyresampled($this->destImage, $canvas, 0, 0, ($auxWidth - $this->destWidth) / 2, 0, $this->destWidth, $this->destHeight, $this->destWidth, $this->destHeight);
      } elseif ($this->srcRatio < $this->destRatio) { // Menos width por cada height en el original.
         $inverseSrcRatio = $this->srcHeight / $this->srcWidth;
         $canvas          = imagecreatetruecolor($this->destWidth, $this->destWidth * $inverseSrcRatio);
         if ($transparent) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
         }
         imagecopyresampled($canvas, $this->srcImage, 0, 0, 0, 0, $this->destWidth, $this->destWidth * $inverseSrcRatio, $this->srcWidth, $this->srcHeight);
         $auxHeight = $this->destWidth * $inverseSrcRatio;
         imagecopyresampled($this->destImage, $canvas, 0, 0, 0, ($auxHeight - $this->destHeight) / 2, $this->destWidth, $this->destHeight, $this->destWidth, $this->destHeight);
      } else {
         imagecopyresampled($this->destImage, $this->srcImage, 0, 0, 0, 0, $this->destWidth, $this->destHeight, $this->srcWidth, $this->srcHeight);
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
      // Resolución de incongruencias del tamaño solicitado en $imageType
      if ($this->widthMin > $this->widthMax) {
         $this->widthMin = $this->widthMax;
      }
      if ($this->heightMin > $this->heightMax) {
         $this->heightMin = $this->heightMax;
      }
      // Fin resolución.
      $widthAux  = $this->srcWidth;
      $heightAux = $this->srcHeight;
      //Primero recorta por el ancho:
      if ($this->srcWidth > $this->widthMax) {
         $widthAux = $this->widthMax;
         $heightAux *= $this->widthMax / $this->srcWidth;
      }
      /* Después por el alto. Si la altura inicial es inferior a la mínima después 
       * de haber sido recortada heightAux = heightMin y widthAux = ...
       */
      if ($heightAux < $this->heightMin) {
         $heightAux = $this->heightMin;
         // Si no, si el alto es superior al máximo, se recorta, volviendo a adaptar el width.
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
}
